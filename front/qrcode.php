<?php
use GlpiPlugin\Qrservice\QrCode;
include('../../../inc/includes.php');
Session::checkLoginUser();

Html::header(
    QrCode::getTypeName(2),
    $_SERVER['PHP_SELF'],
    'admin',
    'GlpiPlugin\\Qrservice\\QrCode'
);

Search::show(QrCode::class);
Html::footer();
