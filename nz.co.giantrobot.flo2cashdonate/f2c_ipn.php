<?php

session_start( );
require_once '../civicrm.config.php';
require_once 'CRM/Core/Config.php';

$config = CRM_Core_Config::singleton();

// Probably there's a cleaner way to require an extension's base file?
// 
// Looking at OgoneNotify.php [1] it appears that the extension
// directory has already been added to the path, but my experience didn't
// match this.
//
// [1] https://github.com/cray146/CiviCRM-Ogone-Payment-Processor/blob/master/org.civicrm.payment.ogone/OgoneNotify.php
require_once 'CRM/Core/Extensions/Extension.php';
$ext = new CRM_Core_Extensions_Extension( 'nz.co.giantrobot.flo2cashdonate' );
if ( !empty( $ext->path ) ) {
    require_once $ext->path . '/Flo2CashDonate.php';
}

if ( class_exists( 'nz_co_giantrobot_Flo2CashDonate' ) ) {
    nz_co_giantrobot_Flo2CashDonate::paymentNotify();
    $notifyHandled = TRUE;
}
else {
    $error = 'Extension nz.co.giantrobot.Flo2CashDonate not enabled or not installed.';
    CRM_Core_Error::debug_log_message( $error );
    die( $error );
}
