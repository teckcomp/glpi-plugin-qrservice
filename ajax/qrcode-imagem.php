<?php
use GlpiPlugin\Qrservice\QrCode;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;

include('../../../inc/includes.php');
Session::checkLoginUser();
Session::checkRight('plugin_qrservice', READ);
require_once __DIR__ . '/../vendor/autoload.php';

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) { http_response_code(400); exit('ID inválido'); }

$qrcode = new QrCode();
if (!$qrcode->getFromDB($id)) { http_response_code(404); exit('QR Code não encontrado'); }

$url      = $qrcode->getPublicUrl();

// Gera QR puro sem logo
$builderParams = [
    'writer'               => new PngWriter(),
    'data'                 => $url,
    'encoding'             => new Encoding('UTF-8'),
    'errorCorrectionLevel' => ErrorCorrectionLevel::High,
    'size'                 => 400,
    'margin'               => 20,
    'roundBlockSizeMode'   => RoundBlockSizeMode::Margin,
];

$builder = new Builder(...$builderParams);
$result  = $builder->build();
$qrImg   = imagecreatefromstring($result->getString());


if (isset($_GET['download'])) {
    header('Content-Disposition: attachment; filename="qrcode-' . $id . '.png"');
} else {
    header('Content-Disposition: inline; filename="qrcode-' . $id . '.png"');
}
header('Content-Type: image/png');

if ($qrImg) {
    imagepng($qrImg);
    imagedestroy($qrImg);
} else {
    echo $result->getString();
}
exit;
