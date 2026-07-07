<?php

use GlpiPlugin\Qrservice\QrCode;
use GlpiPlugin\Qrservice\Cliente;

// -------------------------------------------------------------------
// Endpoint PUBLICO (sem login) usado pelo formulário anônimo para
// carregar a árvore de Localizações em cascata (Associado -> Loja).
// -------------------------------------------------------------------
include('../../../inc/includes.php');

header('Content-Type: application/json; charset=utf-8');

$token  = $_GET['token'] ?? '';
$action = $_GET['action'] ?? '';

if (empty($token)) {
    http_response_code(400);
    echo json_encode(['error' => 'token ausente']);
    exit;
}

$qrcodeData = QrCode::getByToken($token);
if ($qrcodeData === null) {
    http_response_code(404);
    echo json_encode(['error' => 'qrcode não encontrado']);
    exit;
}

$cliente = new Cliente();
if (!$cliente->getFromDB((int) $qrcodeData['plugin_qrservice_clientes_id'])) {
    http_response_code(404);
    echo json_encode(['error' => 'cliente não encontrado']);
    exit;
}

if ($action === 'lojas') {
    $associadoID = (int) ($_GET['parent'] ?? 0);

    if (!$cliente->localizacaoPertenceAoCliente($associadoID)) {
        http_response_code(403);
        echo json_encode(['error' => 'localização fora do escopo deste cliente']);
        exit;
    }

    echo json_encode(Cliente::getLojasDoAssociado($associadoID));
    exit;
}

if ($action === 'endereco') {
    $lojaID = (int) ($_GET['id'] ?? 0);

    if (!$cliente->localizacaoPertenceAoCliente($lojaID)) {
        http_response_code(403);
        echo json_encode(['error' => 'localização fora do escopo deste cliente']);
        exit;
    }

    echo json_encode(['endereco' => Cliente::getEnderecoDaLoja($lojaID)]);
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'ação inválida']);
