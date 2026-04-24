<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$body = file_get_contents('php://input');

$apiMap = [
    '/api/browse'   => 'https://www.webfic.com/webfic/home/browse',
    '/api/detail'   => 'https://www.webfic.com/webfic/book/detail',
    '/api/chapters'  => 'https://www.webfic.com/webfic/book/chapter/list',
];

$endpoint = null;
foreach ($apiMap as $route => $url) {
    if (strpos($path, $route) !== false) {
        $endpoint = $url;
        break;
    }
}

if (!$endpoint) {
    http_response_code(404);
    echo json_encode(['error' => 'Unknown route', 'path' => $path]);
    exit;
}

$ch = curl_init($endpoint);
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $body ?: '{}',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 15,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'pline: DRAMABOX',
        'language: th',
        'User-Agent: Mozilla/5.0 (Linux; Android 13) AppleWebKit/537.36 Chrome/120.0.0.0 Mobile Safari/537.36',
    ],
]);

$response = curl_exec($ch);
$err = curl_error($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($err) {
    http_response_code(502);
    echo json_encode(['error' => 'cURL error', 'message' => $err]);
    exit;
}

http_response_code($httpCode);
echo $response;
