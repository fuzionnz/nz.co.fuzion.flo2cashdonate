<?php

/*
 +--------------------------------------------------------------------+
 | Flo2Cash Donate v0.1                                               |
 +--------------------------------------------------------------------+
 | Copyright Giant Robot Ltd (c) 2007-2011                            |
 +--------------------------------------------------------------------+
 | This file is a payment processor for CiviCRM.                      |
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
 * @copyright Giant Robot Ltd (c) 2007-2011
 * $Id$
 *
 */

require_once 'CRM/Core/Payment.php';
require_once 'CRM/Core/BAO/PaymentProcessor.php';

class nz_co_giantrobot_flo2cashdonate extends CRM_Core_Payment {
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
            self::$_singleton[$processorName] = new nz_co_giantrobot_flo2cashdonate( $mode, $paymentProcessor );
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

        // these will be set to demo or live values accordingly
        $this->_setParam( 'userName', $paymentProcessor['user_name'] );
        $this->_setParam( 'url_site', $paymentProcessor['url_site'] );
        $this->_setParam( 'url_recur', $paymentProcessor['url_recur'] );

        $this->_setParam( 'emailCustomer', 'TRUE' );
        $this->_setParam( 'timestamp', time( ) );
        srand( time( ) );
        $this->_setParam( 'sequence', rand( 1, 1000 ) );

        // Check not needed if CRM_Core_Payment::handleIPN() exists.
        // CRM-9779
        if (!method_exists('CRM_Core_Payment', 'handleIPN')) {
            // If the IPN handler isn't installed, notify admin
            if ( CRM_Core_Permission::check( 'administer CiviCRM' ) ) {
                // check for presence of extIPN.php in civicrm/extern,
                // and advise admin of need to manually install if not
                global $civicrm_root;
                $ipn_php = 'extIPN.php' ;
                $expected_path = $civicrm_root . '/extern/' . $ipn_php ;
                $source_path = dirname(__FILE__) . '/' . $ipn_php ;
                if ( file_exists($source_path) && !file_exists($civicrm_root . '/extern/extIPN.php' ) ) {
                    CRM_Core_Session::setStatus( "To complete installation of the Flo2CashDonate payment processor, please copy the file <strong>$ipn_php</strong><br />from <code>$source_path</code><br />to <code>$expected_path</code>." );
                }
            }
        }
    }

    /**
     * This function checks to see if we have the right config values.
     *
     * @return string the error message if any
     * @public
     */
    function checkConfig( ) {
        $config =& CRM_Core_Config::singleton( );

        $error = array( );

        if ( empty( $this->_paymentProcessor['user_name'] ) ) {
            $error[] = ts( 'Account ID is not set in Administer CiviCRM &raquo; Configure &raquo; Global Settings &raquo; Payment Processors &raquo; ' . $this->_paymentProcessor['name'] );
        }

        if ( empty( $this->_paymentProcessor['url_site'] ) ) {
            $error[] = ts( 'Site URL is not set in Administer CiviCRM &raquo; Configure &raquo; Global Settings &raquo; Payment Processors &raquo; ' . $this->_paymentProcessor['name'] );
        }

        if ( empty( $this->_paymentProcessor['user_name'] ) ) {
            $error[] = ts( 'Recurring Payments URL is not set in Administer CiviCRM &raquo; Configure &raquo; Global Settings &raquo; Payment Processors &raquo; ' . $this->_paymentProcessor['name'] );
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
        $processor = CRM_Core_BAO_PaymentProcessor::getPayment($params['payment_processor_id'], $this->_mode);
        $ipn_query_data['reset'] = 1;

        /**
         * CRM_Core_Payment::handleIPN() exists if CRM-9779 is fixed.
         * If not, then fall back to extIPN.php for older versions.
         */
        if (method_exists('CRM_Core_Payment', 'handleIPN')) {
            if ($this->_mode) {
                $ipn_query_data['mode'] = $this->_mode;
            }
            $ipn_query_data['module'] = $component;
            $ipn_query_data['processor_name'] = $processor['name'];
            $notifyURL = CRM_Utils_System::url('civicrm/payment/ipn', '', TRUE, NULL, FALSE);
        }
        else {
            $ipn_query_data['module'] = $component;
            $ipn_query_data['extension'] = 'nz.co.giantrobot.flo2cashdonate';
            $notifyURL =
                $config->userFrameworkResourceURL .
                "extern/extIPN.php" ;
        }

        $notifyParams = array('contactID', 'contributionID', 'eventID', 'participantID');
        foreach ( $notifyParams as $notifyParam ) {
            if ( isset($params[$notifyParam]) ) {
                $ipn_query_data[$notifyParam] = $params[$notifyParam];
            }
        }

        $ipn_query = http_build_query($ipn_query_data, NULL, '&');
        $notifyURL .= $ipn_query;
        // error_log('Notify URL: '.$notifyURL);

        $url    = ( $component == 'event' ) ? 'civicrm/event/register' : 'civicrm/contribute/transact';
        $cancel = ( $component == 'event' ) ? '_qf_Register_display'   : '_qf_Main_display';
        $returnURL = CRM_Utils_System::url( $url,
                                            "_qf_ThankYou_display=1&qfKey={$params['qfKey']}",
                                            TRUE, NULL, FALSE );
        $cancelURL = CRM_Utils_System::url( $url,
                                            "$cancel=1&cancel=1&qfKey={$params['qfKey']}",
                                            TRUE, NULL, FALSE );

        // ensure that the returnURL is absolute.
        if ( substr( $returnURL, 0, 4 ) != 'http' ) {
            require_once 'CRM/Utils/System.php';
            $fixUrl = CRM_Utils_System::url("civicrm/admin/setting/url", '&reset=1');
            CRM_Core_Error::fatal( ts( 'Sending a relative URL to Flo2Cash is erroneous. Please make your resource URL (in <a href="%1">Administer CiviCRM &raquo; Global Settings &raquo; Resource URLs</a> ) complete.', array( 1 => $fixUrl ) ) );
        }

        // Allow further manipulation of the arguments via custom hooks ..
        CRM_Utils_Hook::alterPaymentProcessorParams( $this, $params, $paypalParams );

        // could also use "$params['is_recur'] + 1"
        $donation_type = ( isset($params['is_recur']) ) ? 2 : 1 ;

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
            'Frequency_ID'             => isset($params['frequency_unit']) ? array_search( $params['frequency_unit'], $frequencies ) : NULL,
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

    /**
     * Handle a notification request from a payment gateway.
     *
     * Might be useful to pass in the paymentProcessor object.
     *
     * $_GET and $_POST are already available in IPN so no point passing them?
     */
    function handlePaymentNotification() {
        require_once 'flo2cashdonateipn.php';
        nz_co_giantrobot_Flo2CashDonateIPN::main();
    }

}
