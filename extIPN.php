<?php

/**
 * Until CiviCRM ships with a method to allow extensions to receive IPN (CRM-10249)
 * this file needs to be copied to your civicrm/extern folder for IPN functionality.
 */

session_start( );
require_once '../civicrm.config.php';
require_once 'CRM/Core/Config.php';

$config = CRM_Core_Config::singleton();

// unless there's a cleaner way to load extensions?
// http://forum.civicrm.org/index.php/topic,21573.0.html
require_once 'CRM/Core/Extensions/Extension.php';
$ext = new CRM_Core_Extensions_Extension( 'nz.co.giantrobot.flo2cashdonate' );
if ( !empty( $ext->path ) ) {
    require_once $ext->path . '/Flo2CashDonate.php';
}

if ( class_exists( 'nz_co_giantrobot_Flo2CashDonate' ) ) {
    nz_co_giantrobot_Flo2CashDonate::handlePaymentNotification();
    $notifyHandled = TRUE;
}
else {
    $error = 'Extension nz.co.giantrobot.Flo2CashDonate not enabled or not installed.';
    CRM_Core_Error::debug_log_message( $error );
    die( $error );
}
