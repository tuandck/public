<?php
$themePath = __DIR__;
$functionsFile = $themePath . DIRECTORY_SEPARATOR . 'functions.php';

$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($themePath));
$sourceStrings = [];
$pattern = '/(?:__|_e|esc_html__|esc_html_e|esc_attr__|esc_attr_e)\s*\(\s*([\'\"])(.*?)\1/s';

foreach ($files as $file) {
    if (!$file->isFile() || $file->getExtension() !== 'php') {
        continue;
    }
    if ($file->getFilename() === 'scan_missing_translations.php') {
        continue;
    }

    $path = $file->getPathname();
    $content = file_get_contents($path);
    if ($content === false) {
        continue;
    }

    if (preg_match_all($pattern, $content, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $m) {
            $str = $m[2];
            if (!preg_match('/[\x{00C0}-\x{1EF9}]/u', $str)) {
                continue;
            }
            if (!isset($sourceStrings[$str])) {
                $line = 1 + substr_count(substr($content, 0, strpos($content, $m[0])), "\n");
                $sourceStrings[$str] = [
                    'file' => str_replace($themePath . DIRECTORY_SEPARATOR, '', $path),
                    'line' => $line,
                ];
            }
        }
    }
}

$mappingKeys = [];
$functionsContent = file_get_contents($functionsFile);
if ($functionsContent !== false) {
    if (preg_match('/\$translations\s*=\s*array\s*\((.*?)\);\s*\n\s*return isset/s', $functionsContent, $mapMatch)) {
        if (preg_match_all('/[\'\"](.*?)[\'\"]\s*=>\s*[\'\"]/s', $mapMatch[1], $k)) {
            foreach ($k[1] as $key) {
                $mappingKeys[$key] = true;
            }
        }
    }
}

$missing = [];
foreach ($sourceStrings as $str => $meta) {
    if (!isset($mappingKeys[$str])) {
        $missing[$str] = $meta;
    }
}

ksort($missing);
echo 'MISSING_COUNT=' . count($missing) . PHP_EOL;
foreach ($missing as $str => $meta) {
    echo $meta['file'] . ':' . $meta['line'] . ' => ' . $str . PHP_EOL;
}

$jsonRows = [];
foreach ($missing as $str => $meta) {
    $jsonRows[] = [
        'file' => $meta['file'],
        'line' => $meta['line'],
        'text' => $str,
    ];
}

file_put_contents(
    $themePath . DIRECTORY_SEPARATOR . 'missing-translations.json',
    json_encode(
        [
            'missing_count' => count($missing),
            'items' => $jsonRows,
        ],
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
    )
);
