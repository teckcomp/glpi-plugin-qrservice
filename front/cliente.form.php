<?php

use GlpiPlugin\Qrservice\Cliente;

include('../../../inc/includes.php');

Session::checkLoginUser();

$cliente = new Cliente();

if (isset($_POST['add'])) {
    $cliente->check(-1, CREATE, $_POST);
    $newID = $cliente->add($_POST);
    Cliente::syncMarcas((int) $newID, $_POST['marcas'] ?? []);
    Session::addMessageAfterRedirect(__('Cliente criado com sucesso', 'qrservice'), true, INFO);
    Html::redirect(Plugin::getWebDir('qrservice') . '/front/cliente.php');
} elseif (isset($_POST['update'])) {
    $cliente->check($_POST['id'], UPDATE);
    $cliente->update($_POST);
    Cliente::syncMarcas((int) $_POST['id'], $_POST['marcas'] ?? []);
    Html::back();
} elseif (isset($_POST['purge'])) {
    $cliente->check($_POST['id'], PURGE);
    $cliente->delete($_POST, 1);
    Cliente::syncMarcas((int) $_POST['id'], []);
    Html::redirect(Plugin::getWebDir('qrservice') . '/front/cliente.php');
} else {
    Html::header(
        Cliente::getTypeName(1),
        $_SERVER['PHP_SELF'],
        'admin',
        'GlpiPlugin\\Qrservice\\Cliente'
    );

    $id = (int) ($_GET['id'] ?? -1);
    if ($id > 0) {
        $cliente->getFromDB($id);
    } else {
        $cliente->getEmpty();
    }

    $cliente->showForm($id);

    Html::footer();
}
