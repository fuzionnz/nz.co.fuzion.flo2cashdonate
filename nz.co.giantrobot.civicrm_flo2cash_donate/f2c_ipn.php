<?php

file_put_contents('/tmp/f2c.' . date('YmdHis') . '.txt', var_export(array('_GET' => $_GET, '_POST' => $_POST),1));

session_start( );

require_once '../civicrm.config.php';
require_once 'CRM/Core/Config.php';

$config = CRM_Core_Config::singleton();
require_once 'CRM/Core/Payment/Flo2CashDonateIPN.php';
CRM_Core_Payment_Flo2CashDonateIPN::main();

/* one-off */
/*
array (
  '_GET' => 
  array (
    'reset' => '1',
    'module' => 'contribute',
    'contactID' => '1',
    'contributionID' => '6',
  ),
  '_POST' => 
  array (
    'Donation_ID' => '6',
    'F2C_Reference_ID' => 'P110900000075011',
    'Transaction_Status_ID' => '1',
    'Invoice_ID' => 'cdf69b551f0308b30e8cf9e85dfdbf00',
    'Amount' => '$10.00',
    'Donation_Type' => 'One-off',
  ),
  '_SESSION' => NULL,
  '_COOKIE' => 
  array (
  ),
)
*/

/* recurring - redirect values */
/*
_qf_ThankYou_display=1
qfKey=612669dcc2bc1e063f2d95415daf4807_6632
Donation_ID=7
F2C_Reference_ID=1221
Transaction_Status_ID=1
Invoice_ID=4c323ab9fa3d4acb0255f8cd3a244f9a
Amount=$10.00
Donation_Type=Recurring CC
*/

/* recurring */
/*
array (
  '_GET' => 
  array (
    'reset' => '1',
    'module' => 'contribute',
    'contactID' => '1',
    'contributionID' => '7',
  ),
  '_POST' => 
  array (
    'Donation_ID' => '7',
    'F2C_Reference_ID' => '1221',
    'Transaction_Status_ID' => '1',
    'Invoice_ID' => '4c323ab9fa3d4acb0255f8cd3a244f9a',
    'Amount' => '$10.00',
    'Donation_Type' => 'Recurring CC',
  ),
  '_SESSION' => NULL,
  '_COOKIE' => 
  array (
  ),
)
*/

