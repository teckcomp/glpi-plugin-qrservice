<?php
include('../../../inc/includes.php');
use GlpiPlugin\Qrservice\QrCode;

Session::checkLoginUser();

$qrcode = new QrCode();

if (isset($_POST['update'])) {
    $qrcode->check($_POST['id'], UPDATE);
    $qrcode->update($_POST);
    Html::back();
} elseif (isset($_POST['add'])) {
    $qrcode->check(-1, CREATE);
    $newID = $qrcode->add($_POST);
    Html::redirect(QrCode::getFormURL() . '?id=' . $newID);
} elseif (isset($_POST['delete'])) {
    $qrcode->check($_POST['id'], DELETE);
    $qrcode->delete($_POST);
    Html::redirect(QrCode::getSearchURL());
} elseif (isset($_POST['purge'])) {
    $qrcode->check($_POST['id'], PURGE);
    $qrcode->delete($_POST, true);
    Html::redirect(QrCode::getSearchURL());
} else {
    $id = (int)($_GET['id'] ?? -1);
    Html::header(QrCode::getTypeName(1), '', 'admin', 'GlpiPlugin\Qrservice\QrCode');
    $qrcode->display(['id' => $id]);
    Html::footer();
}
