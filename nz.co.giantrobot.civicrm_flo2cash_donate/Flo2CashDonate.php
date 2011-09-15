<?php 

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 2.0                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2007                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007.                                       |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License along with this program; if not, contact CiviCRM LLC       |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*/
 
/** 
 * 
 * @package CRM 
 * @copyright CiviCRM LLC (c) 2004-2007 
 * $Id$ 
 * 
 */ 

require_once 'CRM/Core/Payment.php';

/**
 * SQL to add this payment processor
 *********************************************************************************

INSERT INTO civicrm_payment_processor_type ( 
  domain_id, name, title, description, 
  is_active, is_default, user_name_label, 
  password_label, signature_label, subject_label, 
  class_name, url_site_default, url_api_default, 
  url_recur_default, url_button_default, 
  url_site_test_default, url_api_test_default, 
  url_recur_test_default, url_button_test_default, 
  billing_mode, is_recur 
 ) values ( 
  -- domain_id
  1,
  -- name
  'Flo2Cash_Donate',
  -- title
  'Flo2Cash Donate', 
  -- description
  NULL,
  -- is_active
  1,
  -- is_default
  0,
  -- user_name_label
  'Account ID',
  -- password_label
  NULL,
  -- signature_label
  NULL,
  -- subject_label
  NULL,
  -- class_name
  'Payment_Flo2CashDonate',
  -- url_site_default
  '',
  -- url_api_default
  NULL,
  -- url_recur_default
  NULL,
  -- url_button_default
  NULL,
  -- url_site_test_default
  '',
  -- url_api_test_default
  NULL,
  -- url_recur_test_default
  NULL,
  -- url_button_test_default
  NULL,
  -- billing_mode
  4,
  -- is_recur
  1
);

 ********************************************************************************
 */

class CRM_Core_Payment_Flo2CashDonate extends CRM_Core_Payment { 
    /**
     * mode of operation: live or test
     *
     * @var object
     * @static
     */
    static protected $_mode = null;


    static private $_singleton = null;

    static function &singleton( $mode, &$paymentProcessor ) {
        $processorName = $paymentProcessor['name'];
        if (self::$_singleton[$processorName] === null ) {
            self::$_singleton[$processorName] = new CRM_Core_Payment_Flo2CashDonate( $mode, $paymentProcessor );
        }
        return self::$_singleton[$processorName];
    }

    /** 
     * Constructor 
     *
     * @param string $mode the mode of operation: live or test
     * 
     * @return void 
     */ 
    function __construct( $mode, &$paymentProcessor ) {
        $this->_mode = $mode;
        $this->_paymentProcessor = $paymentProcessor;
        $this->_processorName    = ts('Flo2Cash');

        $config = CRM_Core_Config::singleton();
        $this->_setParam( 'apiLogin'   , $paymentProcessor['user_name'] );
        $this->_setParam( 'paymentKey' , $paymentProcessor['password']  );
        $this->_setParam( 'md5Hash'    , $paymentProcessor['signature'] );
        
        $this->_setParam( 'emailCustomer', 'TRUE' );
        $this->_setParam( 'timestamp', time( ) );
        srand( time( ) );
        $this->_setParam( 'sequence', rand( 1, 1000 ) );
    }

    /** 
     * This function checks to see if we have the right config values 
     * 
     * @return string the error message if any 
     * @public 
     */ 
    function checkConfig( ) {
        $config =& CRM_Core_Config::singleton( );

        $error = array( );

        if ( empty( $this->_paymentProcessor['user_name'] ) ) {
            $error[] = ts( 'Account ID is not set in the Administer CiviCRM &raquo; Payment Processor.' );
        }
        
        if ( ! empty( $error ) ) {
            return implode( '<p>', $error );
        } else {
            return null;
        }
    }

    /**
     * @see Flo2CashWebService for direct payment implementation
     */
    function doDirectPayment( &$params ) {
        CRM_Core_Error::fatal( ts( 'This function is not implemented' ) );
    }

    /**  
     * Sets appropriate parameters for checking out to google
     *  
     * @param array $params  name value pair of contribution datat
     *  
     * @return void  
     * @access public 
     *  
     */  
    function doTransferCheckout( &$params, $component ) {
        $config =& CRM_Core_Config::singleton( );

        $component = strtolower( $component );
        
        $server_type  = ( $this->_mode == 'test' ) ? 'sandbox' : '';
        
        $notifyURL = 
            $config->userFrameworkResourceURL . 
            "extern/f2c_ipn.php?reset=1&module={$component}" ;
                
        $notifyParams = array('contactID', 'contributionID', 'eventID', 'participantID');
        foreach ( $notifyParams as $notifyParam ) {
            if ( isset($params[$notifyParam]) ) {
                $notifyURL .= "&{$notifyParam}={$params[$notifyParam]}";
            }
        }

        $url    = ( $component == 'event' ) ? 'civicrm/event/register' : 'civicrm/contribute/transact';
        $cancel = ( $component == 'event' ) ? '_qf_Register_display'   : '_qf_Main_display';
        $returnURL = CRM_Utils_System::url( $url,
                                            "_qf_ThankYou_display=1&qfKey={$params['qfKey']}",
                                            true, null, false );
        $cancelURL = CRM_Utils_System::url( $url,
                                            "$cancel=1&cancel=1&qfKey={$params['qfKey']}",
                                            true, null, false );

        // ensure that the returnURL is absolute.
        if ( substr( $returnURL, 0, 4 ) != 'http' ) {
            require_once 'CRM/Utils/System.php';
            $fixUrl = CRM_Utils_System::url("civicrm/admin/setting/url", '&reset=1');
            CRM_Core_Error::fatal( ts( 'Sending a relative URL to Flo2Cash is erroneous. Please make your resource URL (in <a href="%1">Administer CiviCRM &raquo; Global Settings &raquo; Resource URLs</a> ) complete.', array( 1 => $fixUrl ) ) );
        }

        // Allow further manipulation of the arguments via custom hooks ..
        CRM_Utils_Hook::alterPaymentProcessorParams( $this, $params, $paypalParams );

        // could also use "$params['is_recur'] + 1"
        $donation_type = ( $params['is_recur'] ) ? 2 : 1 ;

        $frequencies = array( '1'  => 'day',
                              '2'  => 'week',
                              '7'  => 'month',
                              '13' => 'year' ) ;
        if ( !isset( $params['installments'] ) || empty( $params['installments'] ) ) {
            $params['installments'] = 0 ;
        }

        //$cancelURL = CIVICRM_UF_BASEURL . 'civicrm/contribute/transact?reset=1&id=' . $params['contributionPageID'] ;
        
        $form_vars = array( 
            'Donation_ID'              => $params['contributionID'],
            'First_Name'               => isset($params['first_name']) ? $params['first_name'] : ' ',
            'Last_Name'                => isset($params['last_name']) ? $params['last_name'] : ' ',
            'email'                    => $params['email-5'],
            'Amount'                   => $params['amount'],
            'Donation_Type'            => $donation_type,
            'Notification_URL'         => $notifyURL,
            'Return_URL'               => $returnURL,
            'Frequency_ID'             => array_search( $params['frequency_unit'], $frequencies ),
            'Installment_Number'       => $params['installments'],
            'Invoice_ID'               => $params['invoiceID'],
            'Cancel_URL'               => $cancelURL,
            //                      'account_id'               => $this->_paymentProcessor['user_name'],
        );
        
        if ( empty( $form_vars['Frequency_ID'] ) ) {
            $form_vars['Frequency_ID'] = 1 ;
        }
        
        $url = $this->_paymentProcessor['url_site'] . '?' ;

        foreach ( $form_vars as $key => $value ) {
            $url .= "$key=" . urlencode($value) . '&' ;
        }

        // prob not required
        $url = trim($url,'&');

                                $dbg = array('form_vars' => $form_vars, 'params' => $params, 'this' => $this,);
        watchdog( 'civicrm', '<pre>!dbg</pre>', array('dbg' => print_r($dbg,1)), WATCHDOG_DEBUG ) ;

        CRM_Utils_System::redirect( $url );
        exit( );
    }

    /**
     * Checks to see if invoice_id already exists in db
     * @param  int     $invoiceId   The ID to check
     * @return bool                 True if ID exists, else false
     */
    function _checkDupe( $invoiceId ) {
        require_once 'CRM/Contribute/DAO/Contribution.php';
        $contribution = new CRM_Contribute_DAO_Contribution( );
        $contribution->invoice_id = $invoiceId;
        return $contribution->find( );
    }

    /**
     * Get the value of a field if set
     *
     * @param string $field the field
     * @return mixed value of the field, or empty string if the field is
     * not set
     */
    function _getParam( $field ) {
        return CRM_Utils_Array::value( $field, $this->_params, '' );
    }

    function &error( $errorCode = null, $errorMessage = null ) {
        $e =& CRM_Core_Error::singleton( );
        if ( $errorCode ) {
            $e->push( $errorCode, 0, null, $errorMessage );
        } else {
            $e->push( 9001, 0, null, 'Unknown System Error.' );
        }
        return $e;
    }

    /**
     * Set a field to the specified value.  Value must be a scalar (int,
     * float, string, or boolean)
     *
     * @param string $field
     * @param mixed $value
     * @return bool false if value is not a scalar, true if successful
     */ 
    function _setParam( $field, $value ) {
        if ( ! is_scalar($value) ) {
            return false;
        } else {
            $this->_params[$field] = $value;
        }
    }

}
