<?php

use GlpiPlugin\Qrservice\Cliente;

include('../../../inc/includes.php');

Session::checkLoginUser();

Html::header(
    Cliente::getTypeName(2),
    $_SERVER['PHP_SELF'],
    'admin',
    'GlpiPlugin\\Qrservice\\Cliente'
);

if (isset($_GET['id']) && $_GET['id'] > 0) {
    Html::redirect('cliente.form.php?id=' . (int) $_GET['id']);
}

Search::show(Cliente::class);

Html::footer();
