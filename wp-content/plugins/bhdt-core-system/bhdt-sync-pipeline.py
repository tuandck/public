#!/usr/bin/env python3
"""
BHDT Sync Pipeline
==================
Batch-sync product review data from local JSON files to the WordPress
BHDT Core System REST endpoint (bhdt/v1/update-product).

Author : Võ Văn Tuấn & Copilot
Version: 1.0 — companion to bhdt-core-system.php V1.0

V16.x note: Replace the file-based input stage with a database or
scraper adapter once the multi-source pipeline is built out.

Usage
-----
# Dry-run: validate JSON files without posting
    python bhdt-sync-pipeline.py --dry-run

# Single file, local environment (no auth needed)
    python bhdt-sync-pipeline.py --input products.json

# Directory of JSON files, local environment
    python bhdt-sync-pipeline.py --input ./review-data/

# Production with WordPress Application Password
    python bhdt-sync-pipeline.py \\
        --input ./review-data/ \\
        --env production \\
        --wp-user apiuser \\
        --wp-app-password "xxxx xxxx xxxx xxxx xxxx xxxx"

# Override base URL at runtime (useful for staging)
    python bhdt-sync-pipeline.py \\
        --input products.json \\
        --base-url https://staging.banhangdientu.com

Input JSON format
-----------------
Single product  : a JSON object with the keys below.
Multiple products: a JSON array of such objects.

Required keys:
    model_name      (string)  e.g. "ESP32 DevKit V1"
    ai_summary      (string)  HTML or plain text summary
    specifications  (object)  arbitrary key/value spec map

Optional keys:
    _skip           (bool)    set true to skip a record without deleting it

Example:
    [
        {
            "model_name": "ESP32 DevKit V1",
            "ai_summary": "<p>Module WiFi/BLE cho dự án IoT.</p>",
            "specifications": {
                "core": "Xtensa dual-core LX6",
                "wifi": "802.11 b/g/n",
                "logic_voltage": "3.3V"
            }
        }
    ]
"""

from __future__ import annotations

import argparse
import json
import logging
import os
import sys
import time
from dataclasses import dataclass, field
from datetime import datetime, timezone
from pathlib import Path
from typing import Any, Generator

# ---------------------------------------------------------------------------
# Optional dependency: requests
# ---------------------------------------------------------------------------
try:
    import requests
    from requests.adapters import HTTPAdapter
    from urllib3.util.retry import Retry  # type: ignore[import-untyped]
except ImportError:
    sys.exit(
        "[ERROR] The 'requests' package is required.\n"
        "Install it with:  pip install requests\n"
    )

# ---------------------------------------------------------------------------
# Configuration defaults
# ---------------------------------------------------------------------------
DEFAULT_LOCAL_BASE_URL = "http://banhangdientulocal.local"
DEFAULT_PROD_BASE_URL = "https://banhangdientu.com"
ENDPOINT_PATH = "/wp-json/bhdt/v1/update-product"

# Throttle: seconds to wait between requests.
# Raise this value for production to stay inside the server-side rate limit
# (default 60 req / 60 sec → 1 req/sec is safe; set INTER_REQUEST_DELAY >= 1).
INTER_REQUEST_DELAY: float = 1.2

# Retry policy for transient HTTP errors (503, 502, 429, connection drops).
RETRY_TOTAL = 5
RETRY_BACKOFF_FACTOR = 1.5   # sleep = backoff × (2 ** (attempt - 1))
RETRY_STATUS_FORCELIST = (429, 500, 502, 503, 504)

# Log file written next to this script.
LOG_FILE = Path(__file__).with_suffix(".log")


# ---------------------------------------------------------------------------
# Logging setup
# ---------------------------------------------------------------------------
def build_logger(verbose: bool = False) -> logging.Logger:
    level = logging.DEBUG if verbose else logging.INFO
    fmt = "%(asctime)s  %(levelname)-8s  %(message)s"
    datefmt = "%Y-%m-%dT%H:%M:%SZ"

    logger = logging.getLogger("bhdt_sync")
    logger.setLevel(level)

    console = logging.StreamHandler(sys.stdout)
    console.setLevel(level)
    console.setFormatter(logging.Formatter(fmt, datefmt=datefmt))

    file_handler = logging.FileHandler(LOG_FILE, encoding="utf-8")
    file_handler.setLevel(logging.DEBUG)
    file_handler.setFormatter(logging.Formatter(fmt, datefmt=datefmt))

    logger.addHandler(console)
    logger.addHandler(file_handler)
    return logger


log = build_logger()


# ---------------------------------------------------------------------------
# Data model
# ---------------------------------------------------------------------------
@dataclass
class SyncResult:
    model_name: str
    source_file: str
    status: str = "pending"     # ok | skipped | error | dry-run
    action: str = ""            # created | updated
    post_id: int = 0
    http_status: int = 0
    error_message: str = ""


@dataclass
class SyncReport:
    started_at: str = field(default_factory=lambda: datetime.now(timezone.utc).isoformat())
    results: list[SyncResult] = field(default_factory=list)

    def summary(self) -> dict[str, int]:
        counts: dict[str, int] = {}
        for r in self.results:
            counts[r.status] = counts.get(r.status, 0) + 1
        return counts

    def save(self, path: Path) -> None:
        data = {
            "started_at": self.started_at,
            "finished_at": datetime.now(timezone.utc).isoformat(),
            "summary": self.summary(),
            "results": [r.__dict__ for r in self.results],
        }
        path.write_text(json.dumps(data, ensure_ascii=False, indent=2), encoding="utf-8")


# ---------------------------------------------------------------------------
# HTTP session
# ---------------------------------------------------------------------------
def build_session(
    wp_user: str | None = None,
    wp_app_password: str | None = None,
    timeout: int = 30,
) -> requests.Session:
    session = requests.Session()

    retry = Retry(
        total=RETRY_TOTAL,
        backoff_factor=RETRY_BACKOFF_FACTOR,
        status_forcelist=RETRY_STATUS_FORCELIST,
        allowed_methods={"POST"},
        raise_on_status=False,
    )
    adapter = HTTPAdapter(max_retries=retry)
    session.mount("http://", adapter)
    session.mount("https://", adapter)

    session.headers.update(
        {
            "Content-Type": "application/json; charset=utf-8",
            "Accept": "application/json",
            "User-Agent": f"BHDT-SyncPipeline/1.0 Python/{sys.version.split()[0]}",
        }
    )

    if wp_user and wp_app_password:
        session.auth = (wp_user, wp_app_password)
        log.debug("Session auth: WordPress Application Password (user=%s)", wp_user)
    else:
        log.debug("Session auth: none (local / open endpoint)")

    session.timeout_value = timeout  # stored for use in post()
    return session


# ---------------------------------------------------------------------------
# JSON loading
# ---------------------------------------------------------------------------
def load_records(path: Path) -> list[dict[str, Any]]:
    """
    Load one or more product records from *path*.
    Accepts a single JSON object or a JSON array.
    """
    raw = path.read_text(encoding="utf-8")
    data = json.loads(raw)

    if isinstance(data, dict):
        return [data]
    if isinstance(data, list):
        return data

    raise ValueError(
        f"Expected a JSON object or array, got {type(data).__name__} in {path}"
    )


def iter_json_files(input_path: Path) -> Generator[Path, None, None]:
    """Yield .json files from a file or directory recursively."""
    if input_path.is_file():
        if input_path.suffix.lower() == ".json":
            yield input_path
        else:
            raise ValueError(f"Input file must be .json, got: {input_path}")
    elif input_path.is_dir():
        files = sorted(input_path.rglob("*.json"))
        if not files:
            raise FileNotFoundError(f"No .json files found under: {input_path}")
        for f in files:
            yield f
    else:
        raise FileNotFoundError(f"Input path not found: {input_path}")


# ---------------------------------------------------------------------------
# Validation
# ---------------------------------------------------------------------------
REQUIRED_KEYS = {"model_name", "ai_summary", "specifications"}


def validate_record(record: dict[str, Any], source: str) -> list[str]:
    errors: list[str] = []
    missing = REQUIRED_KEYS - record.keys()
    if missing:
        errors.append(f"{source}: missing required keys: {sorted(missing)}")
    if "model_name" in record and not str(record["model_name"]).strip():
        errors.append(f"{source}: 'model_name' must not be empty")
    if "specifications" in record and not isinstance(record["specifications"], (dict, list)):
        errors.append(
            f"{source}: 'specifications' must be an object or array, "
            f"got {type(record['specifications']).__name__}"
        )
    return errors


# ---------------------------------------------------------------------------
# Sync logic
# ---------------------------------------------------------------------------
def sync_record(
    session: requests.Session,
    endpoint: str,
    record: dict[str, Any],
    source_file: str,
    dry_run: bool = False,
) -> SyncResult:
    model_name = str(record.get("model_name", "")).strip()
    result = SyncResult(model_name=model_name, source_file=source_file)

    if record.get("_skip"):
        result.status = "skipped"
        log.info("  SKIP  %-48s  (field _skip=true)", model_name[:48])
        return result

    if dry_run:
        result.status = "dry-run"
        log.info("  DRYRUN %-47s  OK", model_name[:47])
        return result

    payload = {
        "model_name": model_name,
        "ai_summary": str(record.get("ai_summary", "")),
        "specifications": record.get("specifications", {}),
    }

    try:
        response = session.post(
            endpoint,
            json=payload,
            timeout=getattr(session, "timeout_value", 30),
        )
        result.http_status = response.status_code

        if response.ok:
            body = response.json()
            result.status = "ok"
            result.action = body.get("action", "")
            result.post_id = int(body.get("post_id", 0))
            log.info(
                "  OK    %-42s  [%s] post_id=%d  HTTP %d",
                model_name[:42],
                result.action,
                result.post_id,
                result.http_status,
            )
        else:
            result.status = "error"
            try:
                body = response.json()
                result.error_message = body.get("message", response.text[:200])
            except ValueError:
                result.error_message = response.text[:200]
            log.error(
                "  ERROR %-42s  HTTP %d  %s",
                model_name[:42],
                result.http_status,
                result.error_message[:120],
            )

    except requests.exceptions.Timeout:
        result.status = "error"
        result.error_message = "Request timed out"
        log.error("  ERROR %-42s  timeout", model_name[:42])

    except requests.exceptions.ConnectionError as exc:
        result.status = "error"
        result.error_message = f"Connection error: {exc}"
        log.error("  ERROR %-42s  connection error: %s", model_name[:42], exc)

    return result


# ---------------------------------------------------------------------------
# CLI
# ---------------------------------------------------------------------------
def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(
        description="BHDT Sync Pipeline — batch upload product reviews to WordPress.",
        formatter_class=argparse.RawDescriptionHelpFormatter,
    )
    parser.add_argument(
        "--input", "-i",
        required=True,
        help="Path to a .json file or a directory containing .json files.",
    )
    parser.add_argument(
        "--env",
        choices=["local", "production"],
        default="local",
        help="Target environment (default: local). Affects default base URL.",
    )
    parser.add_argument(
        "--base-url",
        default=None,
        help="Override base URL, e.g. https://staging.banhangdientu.com",
    )
    parser.add_argument(
        "--wp-user",
        default=None,
        help="WordPress username for Application Password auth (production only).",
    )
    parser.add_argument(
        "--wp-app-password",
        default=None,
        help="WordPress Application Password (space-separated groups).",
    )
    parser.add_argument(
        "--delay",
        type=float,
        default=INTER_REQUEST_DELAY,
        metavar="SECONDS",
        help=f"Delay between requests in seconds (default: {INTER_REQUEST_DELAY}).",
    )
    parser.add_argument(
        "--timeout",
        type=int,
        default=30,
        metavar="SECONDS",
        help="HTTP request timeout in seconds (default: 30).",
    )
    parser.add_argument(
        "--dry-run",
        action="store_true",
        help="Validate input files without sending any requests.",
    )
    parser.add_argument(
        "--report",
        default=None,
        metavar="PATH",
        help="Write a JSON summary report to this path after the run.",
    )
    parser.add_argument(
        "--verbose", "-v",
        action="store_true",
        help="Enable debug logging.",
    )
    return parser.parse_args()


# ---------------------------------------------------------------------------
# Entry point
# ---------------------------------------------------------------------------
def main() -> int:
    args = parse_args()

    if args.verbose:
        log.setLevel(logging.DEBUG)
        for h in log.handlers:
            h.setLevel(logging.DEBUG)

    # Resolve endpoint URL.
    if args.base_url:
        base_url = args.base_url.rstrip("/")
    elif args.env == "production":
        base_url = DEFAULT_PROD_BASE_URL
    else:
        base_url = DEFAULT_LOCAL_BASE_URL
    endpoint = base_url + ENDPOINT_PATH

    log.info("=" * 64)
    log.info("BHDT Sync Pipeline v1.0")
    log.info("Endpoint : %s", endpoint)
    log.info("Env      : %s", args.env)
    log.info("Dry-run  : %s", args.dry_run)
    log.info("=" * 64)

    # Collect and validate all records before any network I/O.
    input_path = Path(args.input).expanduser().resolve()
    all_records: list[tuple[dict[str, Any], str]] = []
    validation_errors: list[str] = []

    try:
        for json_file in iter_json_files(input_path):
            records = load_records(json_file)
            for record in records:
                errs = validate_record(record, str(json_file))
                if errs:
                    validation_errors.extend(errs)
                else:
                    all_records.append((record, str(json_file)))
    except (FileNotFoundError, ValueError) as exc:
        log.error("Input error: %s", exc)
        return 1

    if validation_errors:
        log.error("Validation failed — fix the following before syncing:")
        for err in validation_errors:
            log.error("  %s", err)
        return 2

    total = len(all_records)
    log.info("Records ready to sync: %d", total)

    if args.dry_run:
        log.info("Dry-run complete — no requests sent.")
        for record, src in all_records:
            log.info("  DRYRUN %-47s", str(record.get("model_name", "?"))[:47])
        return 0

    # Build HTTP session.
    session = build_session(
        wp_user=args.wp_user,
        wp_app_password=args.wp_app_password,
        timeout=args.timeout,
    )

    report = SyncReport()

    for idx, (record, source_file) in enumerate(all_records, start=1):
        log.info("[%d/%d]", idx, total)
        result = sync_record(session, endpoint, record, source_file)
        report.results.append(result)

        # Rate-limit: pause between requests (skip after last one).
        if idx < total and not result.status == "skipped":
            time.sleep(args.delay)

    # Summary.
    summary = report.summary()
    log.info("=" * 64)
    log.info("Run complete")
    for status, count in sorted(summary.items()):
        log.info("  %-12s %d", status, count)
    log.info("=" * 64)

    # Optional JSON report.
    if args.report:
        report_path = Path(args.report).expanduser().resolve()
        report.save(report_path)
        log.info("Report saved: %s", report_path)

    # Exit non-zero if any records errored.
    return 1 if summary.get("error", 0) > 0 else 0


if __name__ == "__main__":
    sys.exit(main())
