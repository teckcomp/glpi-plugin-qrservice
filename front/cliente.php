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

if (Cliente::canCreate()) {
    echo "<div class='d-flex justify-content-between mb-3'>";
    echo "<a href='/plugins/qrservice/front/cliente.form.php' 
             class='btn btn-primary'>
             <i class='ti ti-plus'></i> " . __('Adicionar') . "
          </a>";
    echo "</div>";
}

Search::show(Cliente::class);
Html::footer();
