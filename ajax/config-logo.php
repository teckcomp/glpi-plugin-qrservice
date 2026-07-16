<?php
include('../../../inc/includes.php');
Session::checkLoginUser();

global $DB;

// --- SERVE a logo atual ---
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    Session::checkRight('plugin_qrservice', READ);
    $res  = $DB->doQuery("SELECT logo_empresa FROM glpi_plugin_qrservice_config WHERE id=1");
    $row  = $DB->fetchAssoc($res);
    $file = $row['logo_empresa'] ?? '';

    if (!empty($file)) {
        $path = __DIR__ . '/../img/empresa/' . basename($file);
        if (file_exists($path)) {
            $ext  = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            $mime = $ext === 'png' ? 'image/png' : 'image/jpeg';
            header('Content-Type: ' . $mime);
            header('Cache-Control: max-age=86400');
            readfile($path);
            exit;
        }
    }
    http_response_code(404);
    exit;
}

// --- SALVA nova logo ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Session::checkRight('plugin_qrservice', UPDATE);
    // CSRF ja e validado pelo core do GLPI (csrf_compliant no setup.php).
    // Chamar Session::checkCSRF() aqui derruba o request: o token e de uso
    // unico e ja foi consumido pela verificacao do core.
    if (empty($_FILES['logo_empresa']['tmp_name']) ||
        ($_FILES['logo_empresa']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['erro' => 'Nenhum arquivo enviado']);
        exit;
    }

    $file    = $_FILES['logo_empresa'];
    $allowed = ['image/png' => 'png', 'image/jpeg' => 'jpg'];
    $mime    = mime_content_type($file['tmp_name']);

    if (!isset($allowed[$mime])) {
        http_response_code(400);
        echo json_encode(['erro' => 'Apenas PNG e JPG são aceitos']);
        exit;
    }

    if ($file['size'] > 1048576) {
        http_response_code(400);
        echo json_encode(['erro' => 'Tamanho máximo: 1MB']);
        exit;
    }

    // Remove logo antiga
    $res = $DB->doQuery("SELECT logo_empresa FROM glpi_plugin_qrservice_config WHERE id=1");
    $row = $DB->fetchAssoc($res);
    if (!empty($row['logo_empresa'])) {
        $old = __DIR__ . '/../img/empresa/' . basename($row['logo_empresa']);
        if (file_exists($old)) unlink($old);
    }

    $dir = __DIR__ . '/../img/empresa/';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $ext      = $allowed[$mime];
    $filename = 'empresa_' . time() . '.' . $ext;
    $dest     = $dir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        http_response_code(500);
        echo json_encode(['erro' => 'Erro ao salvar arquivo']);
        exit;
    }

    chown($dest, 'www-data');
    $DB->doQuery("UPDATE glpi_plugin_qrservice_config SET logo_empresa='$filename', date_mod=NOW() WHERE id=1");

    // Redireciona de volta ao painel
    header('Location: /plugins/qrservice/front/config.php?logo_salva=1');
    exit;
}
