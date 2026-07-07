<?php

use GlpiPlugin\Qrservice\Campo;
use GlpiPlugin\Qrservice\QrCode;

include('../../../inc/includes.php');

Session::checkLoginUser();

$campo = new Campo();

if (isset($_POST['add'])) {
    $campo->check(-1, CREATE, $_POST);
    $campo->add($_POST);
    Html::redirect(
        Plugin::getWebDir('qrservice') . '/front/qrcode.form.php?id=' . (int) $_POST['plugin_qrservice_qrcodes_id']
        . '&forcetab=GlpiPlugin\\Qrservice\\QrCode$1'
    );
} elseif (isset($_POST['update'])) {
    $campo->check($_POST['id'], UPDATE);
    $campo->update($_POST);
    Html::back();
} elseif (isset($_POST['purge'])) {
    $campo->check($_POST['id'], PURGE);
    $qrcodeID = 0;
    if ($campo->getFromDB($_POST['id'])) {
        $qrcodeID = (int) $campo->fields['plugin_qrservice_qrcodes_id'];
    }
    $campo->delete($_POST, 1);
    Html::redirect(
        Plugin::getWebDir('qrservice') . '/front/qrcode.form.php?id=' . $qrcodeID
        . '&forcetab=GlpiPlugin\\Qrservice\\QrCode$1'
    );
} else {
    $id = (int) ($_GET['id'] ?? 0);
    $qrcodeID = (int) ($_GET['plugin_qrservice_qrcodes_id'] ?? 0);

    if ($id > 0) {
        $campo->getFromDB($id);
        $qrcodeID = (int) $campo->fields['plugin_qrservice_qrcodes_id'];
    } else {
        $campo->getEmpty();
    }

    Html::header(
        Campo::getTypeName(1),
        $_SERVER['PHP_SELF'],
        'admin',
        'GlpiPlugin\\Qrservice\\QrCode'
    );

    $campo->showForm($id, ['plugin_qrservice_qrcodes_id' => $qrcodeID]);

    Html::footer();
}
