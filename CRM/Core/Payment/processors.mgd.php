<?php
/**
 * The record will be automatically inserted, updated, or deleted from the
 * database as appropriate. For more details, see "hook_civicrm_managed" at:
 * http://wiki.civicrm.org/confluence/display/CRMDOC/Hook+Reference
 */
return array(
  0 => array(
    'name' => 'Flo2Cash Donate',
    'entity' => 'PaymentProcessorType',
    'params' => array(
      'version' => 3,
      'title' => 'Flo2Cash Donate',
      'name' => 'flo2cashdonate',
      'description' => 'Flo2Cash Donate Payment Processor',
      'user_name_label' => 'Account ID',
      'password_label' => NULL,
      'signature_label' => NULL,
      'class_name' => 'Payment_Flo2cashdonate',
      'url_site_default' => 'https://secure.flo2cash.co.nz/donations/FIXME/donate.aspx',
      'url_api_default' => NULL,
      'url_recur_default' => 'https://secure.flo2cash.co.nz/donations/FIXME/donate.aspx',
      'url_site_test_default' => 'http://demo.flo2cash.co.nz/donations/FIXME/donate.aspx',
      'url_recur_test_default' => 'http://demo.flo2cash.co.nz/donations/FIXME/donate.aspx',
      'billing_mode' => 4,
      'payment_type' => 1,
      'is_recur' => 1,
    ),
  ),
);
