<?php
require_once('config.php'); // keep if you need session/role checks

// Get raw path
$path = isset($_GET['path']) ? $_GET['path'] : '';
if ($path === '') {
    http_response_code(404);
    exit('File not found');
}

// Base directory for uploads (absolute)
$baseDir = realpath(__DIR__ . '/uploads');
if ($baseDir === false) {
    http_response_code(500);
    exit('Uploads folder not found');
}

// Normalize: allow either "filename.mp3" or "uploads/filename.mp3"
$path = ltrim($path, '/\\');
if (strpos($path, 'uploads/') === 0) {
    $path = substr($path, strlen('uploads/'));
}

// Build and resolve real path
$requested = $baseDir . DIRECTORY_SEPARATOR . $path;
$filePath = realpath($requested);

// Security checks
if ($filePath === false || strpos($filePath, $baseDir) !== 0 || !is_file($filePath)) {
    http_response_code(403);
    exit('Access denied');
}
if (!is_readable($filePath)) {
    http_response_code(403);
    exit('Access denied');
}

// MIME type
$ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
$mime = 'application/octet-stream';
if ($ext === 'mp3') $mime = 'audio/mpeg';
elseif ($ext === 'wav') $mime = 'audio/wav';
elseif ($ext === 'ogg' || $ext === 'oga') $mime = 'audio/ogg';

// Download vs inline
$forceDownload = isset($_GET['download']) && $_GET['download'] == '1';

// File size
$filesize = filesize($filePath);

// Byte-range support
$offset = 0;
$length = $filesize;
$end = $filesize - 1;

header('Accept-Ranges: bytes'); // advertise support[10][7]

if (isset($_SERVER['HTTP_RANGE']) && preg_match('/bytes=(\d+)-(\d+)?/', $_SERVER['HTTP_RANGE'], $m)) {
    $offset = (int)$m[1];
    if (isset($m[2]) && $m[2] !== '') {
        $end = (int)$m[2];
    }
    if ($end >= $filesize) {
        $end = $filesize - 1;
    }
    if ($offset > $end) {
        header('HTTP/1.1 416 Range Not Satisfiable');
        header("Content-Range: bytes */$filesize");
        exit;
    }
    $length = $end - $offset + 1;
    header('HTTP/1.1 206 Partial Content'); // partial response[7][10]
    header("Content-Range: bytes $offset-$end/$filesize");
}

// Common headers
header('Content-Type: ' . $mime);
if ($forceDownload) {
    header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
} else {
    header('Content-Disposition: inline; filename="' . basename($filePath) . '"');
}
header('Content-Length: ' . $length);

// Stream the file
$chunk = 2097152; // 2MB
$fp = fopen($filePath, 'rb');
if ($fp === false) {
    http_response_code(500);
    exit('Failed to open file');
}
if ($offset > 0) {
    fseek($fp, $offset);
}
while (!feof($fp) && $length > 0) {
    $read = ($length > $chunk) ? $chunk : $length;
    $buffer = fread($fp, $read);
    if ($buffer === false) break;
    echo $buffer;
    flush();
    $length -= strlen($buffer);
}
fclose($fp);
exit;
