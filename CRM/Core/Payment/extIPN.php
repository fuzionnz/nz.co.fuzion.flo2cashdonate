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
// http://issues.civicrm.org/jira/browse/CRM-9779
require_once 'CRM/Core/Extensions/Extension.php';
$ext = new CRM_Core_Extensions_Extension( 'nz.co.fuzion.flo2cashdonate' );
if ( !empty( $ext->path ) ) {
    require_once $ext->path . '/CRM/Core/Payment/flo2cashdonate.php';
}

if ( class_exists( 'CRM_Core_Payment_Flo2cashdonate' ) ) {
    CRM_Core_Payment_Flo2cashdonate::handlePaymentNotification();
    $notifyHandled = TRUE;
}
else {
    $error = 'Extension CRM_Core_Payment_Flo2cashdonate not enabled or not installed.';
    CRM_Core_Error::debug_log_message( $error );
    die( $error );
}
