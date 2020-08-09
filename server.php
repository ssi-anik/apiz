<?php
$request_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if ($request_path == '/favicon.ico') {
    return;
}

$method = strtoupper($_SERVER['REQUEST_METHOD']);
$headers = getallheaders();
$length = $headers['Content-Length'] ?? 0;

$data = [
    'path'           => $request_path,
    'method'         => $method,
    'content-length' => $length,
    'headers'        => $headers,
    'data'           => $_REQUEST,
];
$response = [
    'error'    => false,
    'message'  => 'Handled ' . $method . ' request',
//    'received' => $data,
];
header('Content-type: application/json');
http_response_code(200);
echo json_encode($response);