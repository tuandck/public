#!/usr/bin/env python3
"""
Zenclau Worker V1
=================
Read products from a WordPress MySQL database, combine optional YouTube transcript
with official product/PDF Markdown, ask Gemini for structured JSON,
then insert pending drafts into the posts_queue table.

Required packages:
    pip install python-dotenv mysql-connector-python requests beautifulsoup4 markdownify youtube-transcript-api google-genai pypdf

.env example:
    GEMINI_API_KEY=your_key_here
    DB_HOST=127.0.0.1
    DB_PORT=3306
    DB_NAME=local
    DB_USER=root
    DB_PASSWORD=root
    WP_TABLE_PREFIX=wp_

Optional table overrides:
    PRODUCTS_TABLE=products
    POSTS_QUEUE_TABLE=posts_queue

Usage:
    python zenclau_worker_v1.py --product-id 12
    python zenclau_worker_v1.py --youtube-url "https://www.youtube.com/watch?v=VIDEO_ID"
    python zenclau_worker_v1.py --youtube-url "..." --product-id 12 --dry-run
"""

from __future__ import annotations

import argparse
import base64
import hashlib
import json
import logging
import os
import re
import sys
import time
from dataclasses import dataclass
from pathlib import Path
from typing import Any
from urllib.parse import parse_qs, urlparse

try:
    sys.stdout.reconfigure(encoding="utf-8")
    sys.stderr.reconfigure(encoding="utf-8")
except Exception:
    pass


def ensure_user_site_packages() -> None:
    """Make packages installed with --user visible when PHP launches Python."""
    candidates: list[Path] = []
    try:
        import site

        user_site = site.getusersitepackages()
        if user_site:
            candidates.append(Path(user_site))
    except Exception:
        pass

    candidates.append(Path.home() / "AppData" / "Roaming" / "Python" / f"Python{sys.version_info.major}{sys.version_info.minor}" / "site-packages")
    candidates.append(Path("C:/Users/Tuan/AppData/Roaming/Python") / f"Python{sys.version_info.major}{sys.version_info.minor}" / "site-packages")

    for path in candidates:
        path_text = str(path)
        if path.exists() and path_text not in sys.path:
            sys.path.insert(0, path_text)


ensure_user_site_packages()

try:
    from youtube_transcript_api import YouTubeTranscriptApi
    from youtube_transcript_api._errors import TranscriptsDisabled, NoTranscriptFound, VideoUnavailable
except ImportError:
    sys.exit("[ERROR] Missing dependency: youtube-transcript-api. Install with: pip install youtube-transcript-api")


SCRIPT_DIR = Path(__file__).resolve().parent
DEFAULT_ENV_PATH = SCRIPT_DIR / ".env"
LOG_PATH = SCRIPT_DIR / "zenclau_worker_v1.log"
DEFAULT_MODEL_NAME = "gemini-flash-latest"
HTTP_TIMEOUT = 30
MAX_MARKDOWN_CHARS = 64000
MAX_TRANSCRIPT_CHARS = 24000
MAX_PDF_IMAGE_DIMENSION = 1200
PDF_MIME_TYPES = {"application/pdf", "application/x-pdf"}
DEFAULT_PROMPT_RULES = """
- Use Vietnamese.
- Write a complete, information-rich product article, not a short summary.
- Prefer detailed paragraphs, concrete specs, practical notes, and source-backed explanation.
- Be factual and cautious. If evidence is missing, say it needs verification.
- First mentally normalize the YouTube transcript by restoring sentence boundaries and punctuation from context.
- Do not add facts during transcript normalization; only use it to understand the source better.
- If YouTube transcript is available, use it for hands-on observations.
- If YouTube URL is empty, write from Official URL and PDF URL only.
- Use Official URL and PDF URL source text for specifications and official claims.
- Do not invent prices or certifications.
- Keep comparison_table to 6-12 rows when the schema includes it.
- Do not use Markdown heading syntax in generated article fields: no ### headings and no bullet-only outline.
- Start section headings with plain numbering such as I., II., III. or 1., 2., 3.; bold is allowed only for product names or short key terms.
""".strip()
DEFAULT_JSON_SCHEMA = """
{
  "title": "string",
  "summary": "string",
  "review_content": "string",
  "comparison_table": [
    {
      "criteria": "string",
      "product_value": "string",
      "notes": "string"
    }
  ]
}
""".strip()


def build_logger(verbose: bool = False) -> logging.Logger:
    logger = logging.getLogger("zenclau_worker_v1")
    logger.handlers.clear()
    logger.setLevel(logging.DEBUG if verbose else logging.INFO)

    formatter = logging.Formatter("%(asctime)s %(levelname)-8s %(message)s", "%Y-%m-%dT%H:%M:%SZ")

    console = logging.StreamHandler(sys.stdout)
    console.setFormatter(formatter)
    console.setLevel(logging.DEBUG if verbose else logging.INFO)

    file_handler = logging.FileHandler(LOG_PATH, encoding="utf-8")
    file_handler.setFormatter(formatter)
    file_handler.setLevel(logging.DEBUG)

    logger.addHandler(console)
    logger.addHandler(file_handler)
    return logger


log = build_logger()


class ZenclauWorkerError(RuntimeError):
    """Controlled worker error with a user-readable message."""


def import_mysql_connector():
    try:
        import mysql.connector
        from mysql.connector import Error as mysql_error
    except ImportError as exc:
        raise ZenclauWorkerError("Missing dependency: mysql-connector-python. Install with: pip install mysql-connector-python") from exc

    return mysql.connector, mysql_error


def import_dotenv_loader():
    try:
        from dotenv import load_dotenv
    except ImportError as exc:
        raise ZenclauWorkerError("Missing dependency: python-dotenv. Install with: pip install python-dotenv") from exc

    return load_dotenv


def import_scraping_dependencies():
    try:
        import requests
        from bs4 import BeautifulSoup
        from markdownify import markdownify as html_to_markdown
        from requests.adapters import HTTPAdapter
        from urllib3.util.retry import Retry
    except ImportError as exc:
        raise ZenclauWorkerError("Missing scraping dependencies. Install with: pip install requests beautifulsoup4 markdownify") from exc

    return requests, BeautifulSoup, html_to_markdown, HTTPAdapter, Retry


def import_genai_dependencies():
    try:
        from google import genai
        from google.genai import types
    except ImportError as exc:
        raise ZenclauWorkerError("Missing dependency: google-genai. Install with: pip install google-genai") from exc

    return genai, types


@dataclass(frozen=True)
class ProductV1:
    id: int
    official_url: str
    pdf_url: str
    search_keywords: str


@dataclass(frozen=True)
class WorkerConfigV1:
    db_host: str
    db_port: int
    db_name: str
    db_user: str
    db_password: str
    products_table: str
    posts_queue_table: str


def require_env(name: str) -> str:
    value = os.getenv(name, "").strip()
    if not value:
        raise ZenclauWorkerError(f"Missing required environment variable: {name}")
    return value


def load_config(env_path: Path) -> WorkerConfigV1:
    load_dotenv = import_dotenv_loader()
    load_dotenv(env_path)

    table_prefix = os.getenv("WP_TABLE_PREFIX", "wp_").strip()
    products_table = os.getenv("PRODUCTS_TABLE", f"{table_prefix}zenclau_v1_products").strip()
    posts_queue_table = os.getenv("POSTS_QUEUE_TABLE", f"{table_prefix}zenclau_v1_posts_queue").strip()

    return WorkerConfigV1(
        db_host=require_env("DB_HOST"),
        db_port=int(os.getenv("DB_PORT", "3306")),
        db_name=require_env("DB_NAME"),
        db_user=require_env("DB_USER"),
        db_password=os.getenv("DB_PASSWORD", ""),
        products_table=products_table,
        posts_queue_table=posts_queue_table,
    )


def get_model_name() -> str:
    return os.getenv("GEMINI_MODEL", DEFAULT_MODEL_NAME).strip() or DEFAULT_MODEL_NAME


def validate_table_name(name: str) -> str:
    if not re.fullmatch(r"[A-Za-z0-9_]+", name):
        raise ZenclauWorkerError(f"Unsafe table name: {name}")
    return name


def connect_mysql(config: WorkerConfigV1):
    mysql_connector, mysql_error = import_mysql_connector()
    try:
        return mysql_connector.connect(
            host=config.db_host,
            port=config.db_port,
            database=config.db_name,
            user=config.db_user,
            password=config.db_password,
            charset="utf8mb4",
            collation="utf8mb4_unicode_ci",
            autocommit=False,
        )
    except mysql_error as exc:
        raise ZenclauWorkerError(f"MySQL connection failed: {exc}") from exc


def fetch_products(connection, config: WorkerConfigV1, product_id: int | None = None) -> list[ProductV1]:
    _, mysql_error = import_mysql_connector()
    table = validate_table_name(config.products_table)
    where_sql = ""
    params: tuple[Any, ...] = ()
    if product_id is not None:
        where_sql = " WHERE id = %s"
        params = (product_id,)

    sql = f"SELECT id, official_url, pdf_url, search_keywords FROM `{table}`{where_sql} ORDER BY id ASC"
    try:
        with connection.cursor(dictionary=True) as cursor:
            cursor.execute(sql, params)
            rows = cursor.fetchall()
    except mysql_error as exc:
        raise ZenclauWorkerError(f"Could not read products: {exc}") from exc

    products: list[ProductV1] = []
    for row in rows:
        if not row.get("search_keywords"):
            log.warning("Skip product #%s because search_keywords is empty.", row.get("id"))
            continue
        products.append(
            ProductV1(
                id=int(row["id"]),
                official_url=str(row.get("official_url") or ""),
                pdf_url=str(row.get("pdf_url") or ""),
                search_keywords=str(row["search_keywords"]),
            )
        )
    return products


def parse_youtube_video_id(url: str) -> str:
    parsed = urlparse(url.strip())
    host = parsed.netloc.lower().replace("www.", "")

    if host in {"youtube.com", "m.youtube.com"}:
        query_id = parse_qs(parsed.query).get("v", [""])[0]
        if query_id:
            return query_id
        shorts_match = re.match(r"^/shorts/([^/?#]+)", parsed.path)
        if shorts_match:
            return shorts_match.group(1)

    if host == "youtu.be":
        video_id = parsed.path.strip("/").split("/")[0]
        if video_id:
            return video_id

    raise ZenclauWorkerError(f"Could not parse YouTube video id from URL: {url}")


def fetch_transcript(video_url: str, languages: list[str]) -> str:
    video_id = parse_youtube_video_id(video_url)
    log.info("Fetching YouTube transcript for video_id=%s", video_id)

    try:
        if hasattr(YouTubeTranscriptApi, "get_transcript"):
            segments = YouTubeTranscriptApi.get_transcript(video_id, languages=languages)
        else:
            transcript = YouTubeTranscriptApi().fetch(video_id, languages=languages)
            segments = transcript.to_raw_data()
    except (TranscriptsDisabled, NoTranscriptFound, VideoUnavailable) as exc:
        raise ZenclauWorkerError(f"YouTube transcript unavailable: {exc}") from exc
    except Exception as exc:
        raise ZenclauWorkerError(f"Could not fetch YouTube transcript: {exc}") from exc

    text = " ".join(str(item.get("text", "")).replace("\n", " ").strip() for item in segments)
    text = re.sub(r"\s+", " ", text).strip()
    if not text:
        raise ZenclauWorkerError("YouTube transcript is empty.")
    return text[:MAX_TRANSCRIPT_CHARS]


def build_http_session() -> requests.Session:
    requests, _, _, HTTPAdapter, Retry = import_scraping_dependencies()
    session = requests.Session()
    retry = Retry(
        total=3,
        backoff_factor=1.2,
        status_forcelist=(429, 500, 502, 503, 504),
        allowed_methods={"GET"},
        raise_on_status=False,
    )
    adapter = HTTPAdapter(max_retries=retry)
    session.mount("http://", adapter)
    session.mount("https://", adapter)
    session.headers.update(
        {
            "User-Agent": "ZenclauWorkerV1/1.0 (+https://banhangdientu.com)",
            "Accept": "text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8",
        }
    )
    return session


def is_pdf_url(url: str) -> bool:
    parsed = urlparse(url.strip())
    return parsed.path.lower().endswith(".pdf")


def extract_pdf_text(pdf_bytes: bytes, official_url: str) -> str:
    try:
        from io import BytesIO
        from pypdf import PdfReader
    except ImportError as exc:
        raise ZenclauWorkerError("Missing dependency pypdf for PDF extraction. Install with: pip install pypdf") from exc

    try:
        reader = PdfReader(BytesIO(pdf_bytes))
    except Exception as exc:
        raise ZenclauWorkerError(f"Could not read PDF from official_url={official_url}: {exc}") from exc

    pages: list[str] = []
    for page in reader.pages:
        try:
            pages.append(page.extract_text() or "")
        except Exception as exc:
            raise ZenclauWorkerError(f"Could not extract PDF text from official_url={official_url}: {exc}") from exc

    text = clean_source_text_noise("\n\n".join(pages))
    if not text:
        raise ZenclauWorkerError(f"Official PDF produced empty text: {official_url}")
    return text[:MAX_MARKDOWN_CHARS]


def clean_source_text_noise(text: str) -> str:
    """Remove encoded blobs and extraction junk before sending source text to Gemini."""
    if not text:
        return ""

    text = re.sub(r"data:[^,\s]+;base64,[A-Za-z0-9+/=_-]+", " ", text, flags=re.IGNORECASE)
    text = re.sub(r"(?<![A-Za-z0-9+/=_-])(?:PHN2Zy|PD94bWw|iVBORw0KGgo|R0lGODlh|/9j/)[A-Za-z0-9+/=_-]{24,}", " ", text)
    text = re.sub(r"(?<![A-Za-z0-9+/=_-])[A-Za-z0-9+/=_-]{80,}(?![A-Za-z0-9+/=_-])", " ", text)

    cleaned_lines: list[str] = []
    for raw_line in text.splitlines():
        line = re.sub(r"[ \t]+", " ", raw_line).strip()
        if not line:
            cleaned_lines.append("")
            continue

        compact = re.sub(r"\s+", "", line)
        long_tokens = re.findall(r"(?<![A-Za-z0-9+/=_-])[A-Za-z0-9+/=_-]{48,}(?![A-Za-z0-9+/=_-])", line)
        long_token_chars = sum(len(token) for token in long_tokens)
        if (
            len(compact) >= 48
            and long_token_chars / max(1, len(compact)) > 0.7
            and not re.search(r"[^\x00-\x7F]", compact)
        ):
            continue

        line = re.sub(r"(?<![A-Za-z0-9+/=_-])[A-Za-z0-9+/=_-]{48,79}(?![A-Za-z0-9+/=_-])", " ", line)
        line = re.sub(r"[ \t]{2,}", " ", line).strip()
        if line:
            cleaned_lines.append(line)

    text = "\n".join(cleaned_lines)
    text = re.sub(r"\n{3,}", "\n\n", text)
    return text.strip()


def safe_asset_slug(value: str) -> str:
    slug = re.sub(r"[^a-zA-Z0-9_-]+", "-", value.strip().lower()).strip("-")
    return slug[:80] or "pdf"


def clean_pdf_caption(value: str) -> str:
    caption = re.sub(r"\s+", " ", value or "").strip(" -:\t\r\n")
    if len(caption) > 120:
        caption = caption[:120].rsplit(" ", 1)[0].strip()
    return caption


def looks_like_pdf_caption(value: str) -> bool:
    text = clean_pdf_caption(value)
    if not text:
        return False
    words = re.findall(r"[\wÀ-ỹ]+", text, flags=re.UNICODE)
    if len(text) > 70 or len(words) > 8:
        return False
    if text.endswith((".", ";", ",")):
        return False
    if re.search(r"\b(the|this|these|those|which|with|from|using|contains|description)\b", text, re.IGNORECASE) and len(words) > 3:
        return False
    return True


def find_caption_below_image(page: Any, image_rect: Any) -> tuple[str, Any | None]:
    try:
        blocks = page.get_text("blocks")
    except Exception:
        return "", None

    candidates: list[tuple[float, str, Any]] = []
    page_bottom_guard = float(page.rect.height) - 45
    for block in blocks:
        if len(block) < 5:
            continue
        x0, y0, x1, y1, text = block[:5]
        text = clean_pdf_caption(str(text))
        if not text or len(text) < 3 or not looks_like_pdf_caption(text):
            continue
        if y0 < image_rect.y1 - 3 or y0 > image_rect.y1 + 45 or y0 > page_bottom_guard:
            continue
        overlap = min(float(x1), image_rect.x1) - max(float(x0), image_rect.x0)
        if overlap < min(40.0, image_rect.width * 0.12):
            continue
        if re.search(r"\b(modified|copyright|page\s+\d+|/\s*\d+)\b", text, re.IGNORECASE):
            continue
        distance = float(y0) - image_rect.y1
        candidates.append((distance, text, (x0, y0, x1, y1)))

    if not candidates:
        return "", None

    candidates.sort(key=lambda item: (item[0], len(item[1])))
    _, caption, rect = candidates[0]
    try:
        import fitz

        return caption, fitz.Rect(rect)
    except Exception:
        return caption, None


def extract_pdf_images(
    session: requests.Session,
    product: ProductV1,
    asset_dir: str,
    asset_url_base: str,
    max_images: int = 6,
) -> list[dict[str, Any]]:
    if not product.pdf_url.strip() or not asset_dir.strip() or not asset_url_base.strip():
        return []

    try:
        import fitz  # PyMuPDF
    except ImportError as exc:
        raise ZenclauWorkerError("Missing dependency pymupdf for PDF image extraction. Install with: pip install pymupdf") from exc

    requests, _, _, _, _ = import_scraping_dependencies()
    try:
        response = session.get(product.pdf_url, timeout=HTTP_TIMEOUT)
        response.raise_for_status()
    except requests.exceptions.RequestException as exc:
        raise ZenclauWorkerError(f"Could not download PDF for image extraction: {exc}") from exc

    try:
        doc = fitz.open(stream=response.content, filetype="pdf")
    except Exception as exc:
        raise ZenclauWorkerError(f"Could not open PDF for image extraction: {exc}") from exc

    target_dir = Path(asset_dir).expanduser().resolve() / f"product-{product.id}" / safe_asset_slug(hashlib.sha1(product.pdf_url.encode("utf-8")).hexdigest()[:12])
    target_dir.mkdir(parents=True, exist_ok=True)
    base_url = asset_url_base.rstrip("/") + f"/product-{product.id}/{target_dir.name}"
    images: list[dict[str, Any]] = []
    seen: set[str] = set()

    try:
        for page_index in range(len(doc)):
            page = doc[page_index]
            for image_index, image_info in enumerate(page.get_images(full=True), start=1):
                if len(images) >= max_images:
                    break
                xref = image_info[0]
                try:
                    base_pix = fitz.Pixmap(doc, xref)
                    if base_pix.width < 180 or base_pix.height < 120 or (base_pix.width * base_pix.height) < 30000:
                        base_pix = None
                        continue
                    image_rects = page.get_image_rects(xref)
                    image_rect = image_rects[0] if image_rects else None
                    caption = ""
                    caption_rect = None
                    pix = base_pix
                    if image_rect:
                        caption, caption_rect = find_caption_below_image(page, image_rect)
                        if caption_rect:
                            clip = image_rect | caption_rect
                            clip.x0 = max(0, clip.x0 - 8)
                            clip.y0 = max(0, clip.y0 - 8)
                            clip.x1 = min(page.rect.width, clip.x1 + 8)
                            clip.y1 = min(page.rect.height, clip.y1 + 8)
                            pix = page.get_pixmap(clip=clip, matrix=fitz.Matrix(2, 2), alpha=False)
                            base_pix = None
                    if pix.n >= 5:
                        pix = fitz.Pixmap(fitz.csRGB, pix)
                    while max(pix.width, pix.height) > MAX_PDF_IMAGE_DIMENSION:
                        pix.shrink(1)
                    digest = hashlib.sha1(pix.tobytes("png")).hexdigest()
                    if digest in seen:
                        pix = None
                        continue
                    seen.add(digest)
                    caption = caption or f"PDF page {page_index + 1} image {image_index}"
                    filename = f"{safe_asset_slug(caption)}-page-{page_index + 1}-{digest[:10]}.png"
                    output_path = target_dir / filename
                    pix.save(str(output_path))
                    images.append(
                        {
                            "url": f"{base_url}/{filename}",
                            "filename": filename,
                            "source_page": page_index + 1,
                            "width": int(pix.width),
                            "height": int(pix.height),
                            "caption": caption,
                        }
                    )
                    pix = None
                except Exception as exc:
                    log.debug("Skipping PDF image xref=%s page=%s: %s", xref, page_index + 1, exc)
                    continue
            if len(images) >= max_images:
                break
    finally:
        doc.close()

    return images


def extract_pdf_images_from_url(
    session: requests.Session,
    pdf_url: str,
    asset_dir: str,
    asset_url_base: str,
    folder_id: int,
    label: str,
    max_images: int = 6,
) -> list[dict[str, Any]]:
    product = ProductV1(id=folder_id, official_url="", pdf_url=pdf_url, search_keywords=label)
    images = extract_pdf_images(session, product, asset_dir, asset_url_base, max_images)
    if not images:
        return images
    source_label = label.strip() or "Compare product 2"
    for image in images:
        image["caption"] = f"{image.get('caption', '')} - {source_label}".strip()
    return images


def scrape_official_markdown(session: requests.Session, official_url: str) -> str:
    if not official_url.strip():
        log.info("Official URL is empty; using YouTube transcript as the primary source.")
        return (
            "Official URL was not provided for this product. "
            "Use the YouTube transcript as the primary source, and mark official specifications as needing verification."
        )

    requests, BeautifulSoup, html_to_markdown, _, _ = import_scraping_dependencies()
    log.info("Scraping official URL: %s", official_url)
    try:
        response = session.get(official_url, timeout=HTTP_TIMEOUT)
        response.raise_for_status()
    except requests.exceptions.RequestException as exc:
        raise ZenclauWorkerError(f"Could not scrape official_url={official_url}: {exc}") from exc

    content_type = response.headers.get("Content-Type", "").split(";", 1)[0].strip().lower()
    if is_pdf_url(official_url) or content_type in PDF_MIME_TYPES:
        log.info("Extracting official PDF content: %s", official_url)
        return extract_pdf_text(response.content, official_url)

    soup = BeautifulSoup(response.text, "html.parser")
    for tag in soup(["script", "style", "noscript", "svg", "iframe", "form"]):
        tag.decompose()

    main = soup.find("main") or soup.find("article") or soup.body or soup
    markdown = html_to_markdown(str(main), heading_style="ATX", strip=["a"])
    markdown = clean_source_text_noise(markdown)
    if not markdown:
        raise ZenclauWorkerError(f"Official page produced empty Markdown: {official_url}")
    return markdown[:MAX_MARKDOWN_CHARS]


def build_official_sources_markdown(session: requests.Session, product: ProductV1) -> str:
    sections: list[str] = []

    if product.official_url.strip():
        official_text = scrape_official_markdown(session, product.official_url)
        sections.append(
            "## Official manufacturer page\n"
            f"Source URL: {product.official_url}\n\n"
            f"{official_text}"
        )

    if product.pdf_url.strip():
        pdf_text = scrape_official_markdown(session, product.pdf_url)
        sections.append(
            "## Official PDF\n"
            f"PDF URL: {product.pdf_url}\n\n"
            f"{pdf_text}"
        )

    if sections:
        return "\n\n---\n\n".join(sections)

    log.info("Official URL and PDF URL are empty; using YouTube transcript as the primary source.")
    return (
        "No Official URL or PDF URL was provided. "
        "Use the YouTube transcript as the primary source, and mark specifications as needing verification."
    )


def build_compare_sources_markdown(
    session: requests.Session,
    product_name: str,
    official_url: str,
    pdf_url: str,
) -> str:
    sections: list[str] = []
    label = product_name.strip() or "Compare product 2"

    if official_url.strip():
        official_text = scrape_official_markdown(session, official_url)
        sections.append(
            f"## {label} official manufacturer page\n"
            f"Source URL: {official_url}\n\n"
            f"{official_text}"
        )

    if pdf_url.strip():
        pdf_text = scrape_official_markdown(session, pdf_url)
        sections.append(
            f"## {label} official PDF\n"
            f"PDF URL: {pdf_url}\n\n"
            f"{pdf_text}"
        )

    if sections:
        return "\n\n---\n\n".join(sections)

    return "No compare product 2 Official URL or PDF URL was provided."


def decode_prompt_rules(encoded_rules: str | None) -> str:
    if not encoded_rules:
        return DEFAULT_PROMPT_RULES
    try:
        decoded = base64.b64decode(encoded_rules.encode("ascii"), validate=True).decode("utf-8").strip()
    except Exception as exc:
        raise ZenclauWorkerError(f"Could not decode prompt rules: {exc}") from exc
    return decoded or DEFAULT_PROMPT_RULES


def decode_json_schema(encoded_schema: str | None) -> str:
    if not encoded_schema:
        return DEFAULT_JSON_SCHEMA
    try:
        decoded = base64.b64decode(encoded_schema.encode("ascii"), validate=True).decode("utf-8").strip()
    except Exception as exc:
        raise ZenclauWorkerError(f"Could not decode JSON schema prompt: {exc}") from exc
    return decoded or DEFAULT_JSON_SCHEMA


def decode_optional_text(encoded_text: str | None, label: str) -> str:
    if not encoded_text:
        return ""
    try:
        return base64.b64decode(encoded_text.encode("ascii"), validate=True).decode("utf-8").strip()
    except Exception as exc:
        raise ZenclauWorkerError(f"Could not decode {label}: {exc}") from exc


def read_optional_text_file(path_text: str | None, label: str) -> str:
    if not path_text:
        return ""
    try:
        return Path(path_text).expanduser().read_text(encoding="utf-8").strip()
    except Exception as exc:
        raise ZenclauWorkerError(f"Could not read {label}: {exc}") from exc


def build_prompt(
    product: ProductV1,
    youtube_url: str,
    transcript: str,
    official_markdown: str,
    compare_product_name: str,
    compare_official_url: str,
    compare_pdf_url: str,
    compare_markdown: str,
    prompt_rules: str,
    json_schema: str,
) -> str:
    source_mode = "Official/PDF only" if not youtube_url else "YouTube transcript plus Official/PDF"
    return f"""
You are Youtube Content System. Create a Vietnamese technical review draft for the product below.

Return ONLY valid JSON. No Markdown fences. No commentary.
Fill every field in the requested schema with useful detail when evidence exists.
Make review_content long-form and practical: include overview, key specifications, notable features, setup/usage notes, strengths, limitations, and who should buy/use it.
Do not compress the article into a brief abstract. Aim for depth and clarity while staying faithful to the sources.
In generated article fields, do not write ### headings. Use plain numbered section headings such as I., II., III. or 1., 2., 3. Bold is allowed only for product names or short key terms.

Required JSON schema:
{json_schema}

Rules:
{prompt_rules}

Product ID: {product.id}
Source mode: {source_mode}
Search keywords: {product.search_keywords}
Official URL: {product.official_url}
PDF URL: {product.pdf_url}
YouTube URL: {youtube_url}

Compare product 2 name: {compare_product_name}
Compare product 2 Official URL: {compare_official_url}
Compare product 2 PDF URL: {compare_pdf_url}

YouTube transcript:
{transcript}

Product 1 official manufacturer/PDF sources:
{official_markdown}

Product 2 official manufacturer/PDF sources:
{compare_markdown}
""".strip()


def parse_json_response(raw_text: str) -> dict[str, Any]:
    text = raw_text.strip()
    if text.startswith("```"):
        text = re.sub(r"^```(?:json)?", "", text, flags=re.IGNORECASE).strip()
        text = re.sub(r"```$", "", text).strip()

    try:
        data = json.loads(text)
    except json.JSONDecodeError:
        start = text.find("{")
        end = text.rfind("}")
        if start < 0 or end <= start:
            raise ZenclauWorkerError("Gemini response did not contain a JSON object.")
        try:
            data = json.loads(text[start : end + 1])
        except json.JSONDecodeError as exc:
            raise ZenclauWorkerError(f"Gemini response JSON parse failed: {exc}") from exc

    if not isinstance(data, dict):
        raise ZenclauWorkerError("Gemini response must be a JSON object.")

    required = {"title"}
    missing = required - data.keys()
    if missing:
        raise ZenclauWorkerError(f"Gemini response missing required keys: {sorted(missing)}")
    if "comparison_table" in data and not isinstance(data["comparison_table"], list):
        raise ZenclauWorkerError("Gemini response comparison_table must be a list.")

    return data


def extract_token_usage(response: Any, prompt: str, raw_text: str) -> dict[str, Any]:
    """Extract Gemini token usage, falling back to rough char-based estimates."""
    usage = getattr(response, "usage_metadata", None)

    def read_usage_int(name: str) -> int:
        if not usage:
            return 0
        if isinstance(usage, dict):
            return int(usage.get(name) or 0)
        return int(getattr(usage, name, 0) or 0)

    input_tokens = read_usage_int("prompt_token_count")
    output_tokens = read_usage_int("candidates_token_count")
    total_tokens = read_usage_int("total_token_count")

    estimated = False
    if not input_tokens:
        input_tokens = max(1, round(len(prompt) / 4))
        estimated = True
    if not output_tokens:
        output_tokens = max(1, round(len(raw_text) / 4))
        estimated = True
    if not total_tokens:
        total_tokens = input_tokens + output_tokens

    return {
        "input_tokens": input_tokens,
        "output_tokens": output_tokens,
        "total_tokens": total_tokens,
        "estimated": estimated,
    }


def generate_content_json(
    client: genai.Client,
    product: ProductV1,
    youtube_url: str,
    transcript: str,
    official_markdown: str,
    compare_product_name: str,
    compare_official_url: str,
    compare_pdf_url: str,
    compare_markdown: str,
    prompt_rules: str,
    json_schema: str,
    max_output_tokens: int,
) -> dict[str, Any]:
    _, types = import_genai_dependencies()
    prompt = build_prompt(product, youtube_url, transcript, official_markdown, compare_product_name, compare_official_url, compare_pdf_url, compare_markdown, prompt_rules, json_schema)
    model_name = get_model_name()
    log.info("Calling Gemini model=%s for product_id=%s", model_name, product.id)

    try:
        response = client.models.generate_content(
            model=model_name,
            contents=prompt,
            config=types.GenerateContentConfig(
                temperature=0.35,
                max_output_tokens=max_output_tokens,
                response_mime_type="application/json",
            ),
        )
    except Exception as exc:
        raise ZenclauWorkerError(f"Gemini generation failed for product_id={product.id}: {exc}") from exc

    raw_text = getattr(response, "text", "") or ""
    if not raw_text:
        raise ZenclauWorkerError(f"Gemini returned empty text for product_id={product.id}.")
    content_json = parse_json_response(raw_text)
    content_json["_token_usage"] = extract_token_usage(response, prompt, raw_text)
    return content_json


def insert_queue_item(connection, config: WorkerConfigV1, product_id: int, youtube_url: str, content_json: dict[str, Any]) -> int:
    _, mysql_error = import_mysql_connector()
    table = validate_table_name(config.posts_queue_table)
    json_payload = json.dumps(content_json, ensure_ascii=False, separators=(",", ":"))
    sql = f"""
        INSERT INTO `{table}` (product_id, youtube_url, content_json, status)
        VALUES (%s, %s, %s, %s)
    """
    try:
        with connection.cursor() as cursor:
            cursor.execute(sql, (product_id, youtube_url, json_payload, "pending"))
            queue_id = int(cursor.lastrowid)
        connection.commit()
        return queue_id
    except mysql_error as exc:
        connection.rollback()
        raise ZenclauWorkerError(f"Could not insert posts_queue row for product_id={product_id}: {exc}") from exc


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(description="Youtube Content System worker.")
    parser.add_argument("--youtube-url", default="", help="Optional target YouTube video URL.")
    parser.add_argument("--product-id", type=int, default=None, help="Optional product id filter. Default: process all products.")
    parser.add_argument("--env-file", default=str(DEFAULT_ENV_PATH), help="Path to .env file. Default: plugin/.env")
    parser.add_argument("--languages", default="vi,en", help="Transcript language priority, comma separated. Default: vi,en")
    parser.add_argument("--delay", type=float, default=1.0, help="Delay between products in seconds.")
    parser.add_argument("--dry-run", action="store_true", help="Run scraping/Gemini but do not insert into posts_queue.")
    parser.add_argument("--allow-missing-transcript", action="store_true", help="Continue with Official URL/PDF URL content if the YouTube transcript is unavailable.")
    parser.add_argument("--transcript-only", action="store_true", help="Print fetched YouTube transcript as JSON and exit.")
    parser.add_argument("--official-text-only", action="store_true", help="Print extracted Official URL and PDF URL text as JSON and exit.")
    parser.add_argument("--prompt-rules-b64", default="", help="Base64-encoded custom Gemini prompt rules.")
    parser.add_argument("--json-schema-b64", default="", help="Base64-encoded custom Gemini JSON schema prompt.")
    parser.add_argument("--transcript-override-b64", default="", help="Base64-encoded edited transcript text from preview.")
    parser.add_argument("--official-text-override-b64", default="", help="Base64-encoded edited Official/PDF source text from preview.")
    parser.add_argument("--transcript-override-file", default="", help="UTF-8 file containing edited transcript text from preview.")
    parser.add_argument("--official-text-override-file", default="", help="UTF-8 file containing edited Official/PDF source text from preview.")
    parser.add_argument("--max-output-tokens", type=int, default=8192, help="Gemini max output tokens. Default: 8192, max: 65536.")
    parser.add_argument("--asset-dir", default="", help="Directory where extracted PDF images should be written.")
    parser.add_argument("--asset-url-base", default="", help="Public URL base matching --asset-dir.")
    parser.add_argument("--max-pdf-images", type=int, default=6, help="Maximum PDF images to extract. Default: 6.")
    parser.add_argument("--compare-product-name", default="", help="Optional second product name for comparison template.")
    parser.add_argument("--compare-official-url", default="", help="Optional second product official URL.")
    parser.add_argument("--compare-pdf-url", default="", help="Optional second product PDF URL.")
    parser.add_argument("--verbose", "-v", action="store_true", help="Enable verbose logging.")
    return parser.parse_args()


def main() -> int:
    args = parse_args()
    global log
    log = build_logger(args.verbose)
    prompt_rules = decode_prompt_rules(args.prompt_rules_b64)
    json_schema = decode_json_schema(args.json_schema_b64)
    transcript_override = read_optional_text_file(args.transcript_override_file, "transcript override file") or decode_optional_text(args.transcript_override_b64, "transcript override")
    official_text_override = read_optional_text_file(args.official_text_override_file, "official text override file") or decode_optional_text(args.official_text_override_b64, "official text override")
    max_output_tokens = min(65536, max(1024, int(args.max_output_tokens or 8192)))
    max_pdf_images = min(12, max(1, int(args.max_pdf_images or 6)))
    if args.compare_pdf_url.strip():
        max_pdf_images = 12
    max_images_per_pdf = min(6, max(1, max_pdf_images))

    languages = [item.strip() for item in args.languages.split(",") if item.strip()]
    if not languages:
        log.error("At least one transcript language is required.")
        return 1

    if args.transcript_only:
        log.handlers.clear()
        log.addHandler(logging.NullHandler())
        log.propagate = False
        if not args.youtube_url:
            print(json.dumps({"error": "YouTube URL is required for transcript-only mode."}, ensure_ascii=False))
            return 1
        try:
            transcript = fetch_transcript(args.youtube_url, languages)
            payload = {
                "video_id": parse_youtube_video_id(args.youtube_url),
                "languages": languages,
                "chars": len(transcript),
                "transcript": transcript,
            }
            print(json.dumps(payload, ensure_ascii=False))
            return 0
        except ZenclauWorkerError as exc:
            print(json.dumps({"error": str(exc)}, ensure_ascii=False))
            return 1

    if args.official_text_only:
        log.handlers.clear()
        log.addHandler(logging.NullHandler())
        log.propagate = False

        env_path = Path(args.env_file).expanduser().resolve()
        if not env_path.exists():
            print(json.dumps({"error": f".env file not found: {env_path}"}, ensure_ascii=False))
            return 2

        try:
            config = load_config(env_path)
            connection = connect_mysql(config)
            products = fetch_products(connection, config, args.product_id)
            connection.close()
            if not products:
                raise ZenclauWorkerError("No product found for official text extraction.")

            product = products[0]
            http = build_http_session()
            official_text = build_official_sources_markdown(http, product)
            if args.compare_official_url.strip() or args.compare_pdf_url.strip():
                compare_text = build_compare_sources_markdown(
                    http,
                    args.compare_product_name,
                    args.compare_official_url,
                    args.compare_pdf_url,
                )
                official_text = official_text + "\n\n---\n\n# Compare product 2 sources\n\n" + compare_text
            official_text = clean_source_text_noise(official_text)
            pdf_images: list[dict[str, Any]] = []
            pdf_image_error = ""
            try:
                pdf_images = extract_pdf_images(http, product, args.asset_dir, args.asset_url_base, max_images_per_pdf)
                if args.compare_pdf_url.strip():
                    compare_limit = min(6, max(0, max_pdf_images - len(pdf_images)))
                    if compare_limit > 0:
                        pdf_images.extend(
                            extract_pdf_images_from_url(
                                http,
                                args.compare_pdf_url,
                                args.asset_dir,
                                args.asset_url_base,
                                product.id * 1000 + 2,
                                args.compare_product_name or "Compare product 2",
                                compare_limit,
                            )
                        )
            except ZenclauWorkerError as exc:
                pdf_image_error = str(exc)
            payload = {
                "product_id": product.id,
                "official_url": product.official_url,
                "pdf_url": product.pdf_url,
                "chars": len(official_text),
                "text": official_text,
                "pdf_images": pdf_images,
                "pdf_image_error": pdf_image_error,
            }
            print(json.dumps(payload, ensure_ascii=False))
            return 0
        except ZenclauWorkerError as exc:
            print(json.dumps({"error": str(exc)}, ensure_ascii=False))
            return 1

    env_path = Path(args.env_file).expanduser().resolve()
    if not env_path.exists():
        log.error(".env file not found: %s", env_path)
        return 2

    try:
        config = load_config(env_path)

        connection = connect_mysql(config)
        products = fetch_products(connection, config, args.product_id)
        if not products:
            raise ZenclauWorkerError("No products found to process.")

        require_env("GEMINI_API_KEY")
        if transcript_override:
            transcript = transcript_override[:MAX_TRANSCRIPT_CHARS]
        elif args.youtube_url:
            try:
                transcript = fetch_transcript(args.youtube_url, languages)
            except ZenclauWorkerError as exc:
                if not args.allow_missing_transcript:
                    raise
                log.warning("Continuing without YouTube transcript: %s", exc)
                transcript = (
                    "YouTube transcript was unavailable for this run. "
                    "Use Official URL/PDF URL content for specifications and mark hands-on observations as needing verification."
                )
        else:
            transcript = (
                "No YouTube URL was provided for this run. "
                "Use Official URL and PDF URL content as the primary sources. "
                "Do not claim hands-on testing or video observations unless supported by the official sources."
            )
        http = build_http_session()
        genai, _ = import_genai_dependencies()
        gemini_client = genai.Client()

        log.info("Zenclau Worker V1 started. products=%d dry_run=%s", len(products), args.dry_run)
        if args.compare_pdf_url.strip():
            log.info("Compare product 2 PDF URL enabled: %s", args.compare_pdf_url)
        failures = 0

        for index, product in enumerate(products, start=1):
            log.info("[%d/%d] Processing product_id=%s", index, len(products), product.id)
            try:
                official_markdown = clean_source_text_noise(official_text_override if official_text_override else build_official_sources_markdown(http, product))[:MAX_MARKDOWN_CHARS]
                compare_markdown = build_compare_sources_markdown(
                    http,
                    args.compare_product_name,
                    args.compare_official_url,
                    args.compare_pdf_url,
                )
                pdf_images: list[dict[str, Any]] = []
                try:
                    pdf_images = extract_pdf_images(http, product, args.asset_dir, args.asset_url_base, max_images_per_pdf)
                    if args.compare_pdf_url.strip():
                        compare_image_limit = min(6, max(0, max_pdf_images - len(pdf_images)))
                    else:
                        compare_image_limit = 0
                    if compare_image_limit > 0:
                        log.info("Extracting compare product 2 PDF images. limit=%d url=%s", compare_image_limit, args.compare_pdf_url)
                        pdf_images.extend(
                            extract_pdf_images_from_url(
                                http,
                                args.compare_pdf_url,
                                args.asset_dir,
                                args.asset_url_base,
                                product.id * 1000 + 2,
                                args.compare_product_name or "Compare product 2",
                                compare_image_limit,
                            )
                        )
                except ZenclauWorkerError as exc:
                    log.warning("PDF image extraction skipped: %s", exc)
                content_json = generate_content_json(
                    client=gemini_client,
                    product=product,
                    youtube_url=args.youtube_url,
                    transcript=transcript,
                    official_markdown=official_markdown,
                    compare_product_name=args.compare_product_name,
                    compare_official_url=args.compare_official_url,
                    compare_pdf_url=args.compare_pdf_url,
                    compare_markdown=compare_markdown,
                    prompt_rules=prompt_rules,
                    json_schema=json_schema,
                    max_output_tokens=max_output_tokens,
                )
                if pdf_images:
                    content_json["pdf_images"] = pdf_images

                if args.dry_run:
                    log.info("DRY RUN product_id=%s title=%s", product.id, content_json.get("title", ""))
                else:
                    queue_id = insert_queue_item(connection, config, product.id, args.youtube_url, content_json)
                    log.info("Inserted pending posts_queue id=%s for product_id=%s", queue_id, product.id)

            except ZenclauWorkerError as exc:
                failures += 1
                log.error("Product product_id=%s failed: %s", product.id, exc)
            except Exception as exc:  # Last-resort guard so one product cannot kill the batch.
                failures += 1
                log.exception("Unexpected failure for product_id=%s: %s", product.id, exc)

            if index < len(products):
                time.sleep(max(0.0, args.delay))

        connection.close()
        log.info("Zenclau Worker V1 finished. success=%d failures=%d", len(products) - failures, failures)
        return 1 if failures else 0

    except ZenclauWorkerError as exc:
        log.error("%s", exc)
        return 1
    except KeyboardInterrupt:
        log.warning("Interrupted by user.")
        return 130


if __name__ == "__main__":
    sys.exit(main())
