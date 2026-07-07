<?php
include('../../../inc/includes.php');


$file = $_GET['file'] ?? '';

if (empty($file) || !preg_match('/^logo_\d+_\d+\.(png|jpg)$/i', $file)) {
    http_response_code(400);
    exit('Arquivo inválido');
}

$path = __DIR__ . '/../img/logos/' . $file;

if (!file_exists($path)) {
    http_response_code(404);
    exit('Não encontrado');
}

$ext  = strtolower(pathinfo($path, PATHINFO_EXTENSION));
$mime = $ext === 'png' ? 'image/png' : 'image/jpeg';

header('Content-Type: ' . $mime);
header('Cache-Control: max-age=86400');
readfile($path);
exit;
