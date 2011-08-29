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

require_once 'CRM/Core/Payment/BaseIPN.php';

class CRM_Core_Payment_Flo2CashHosted extends CRM_Core_Payment_BaseIPN { 

    /**
     * We only need one instance of this object. So we use the singleton
     * pattern and cache the instance in this variable
     *
     * @var object
     * @static
     */
    static private $_singleton = null;

    /**
     * mode of operation: live or test
     *
     * @var object
     * @static
     */
    static protected $_mode = null;

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
        $component = strtolower( $component );
        
        //Create a new shopping cart object
        $merchant_id  = $this->_paymentProcessor['user_name'];   // Merchant ID
        $merchant_key = $this->_paymentProcessor['password'];    // Merchant Key
        $server_type  = ( $this->_mode == 'test' ) ? 'sandbox' : '';
        
        // return to appropriate page in system
        if ( $component == "event" ) {
            $returnURL = CRM_Utils_System::url( 'civicrm/event/register',
                                                "_qf_ThankYou_display=1&_qfKey={$params['qfKey']}", 
                                                true, null, false );
        } elseif ( $component == "contribute" ) {
            $returnURL = CRM_Utils_System::url( 'civicrm/contribute/transact',
                                                "_qf_ThankYou_display=1&_qfKey={$params['qfKey']}",
                                                true, null, false );
        }

        $cancelURL = CIVICRM_UF_BASEURL . 'civicrm/contribute/transact?reset=1&id=' . $params['contributionPageID'] ;

        $notifyURL = 
            $config->userFrameworkResourceURL . 
            "extern/f2c_ipn.php?reset=1&contactID={$params['contactID']}" .
            "&contributionID={$params['contributionID']}" .
            "&module={$component}";
          
        $headerImage = $config->userFrameworkResourceURL . '/i/powered-by.png' ;

        $form_vars = array( 
               'cmd'                      => '_xcart',
               'account_id'               => $this->_paymentProcessor['user_name'],
               'return_url'               => $returnURL,
               'reference'                => $params['invoiceID'],
               'item_name1'               => $params['description'],
               'item_price1'              => $params['amount'],
               'item_code1'               => '',
               'item_qty1'                => '1',
               // @todo - these next few should be more easily
               // customisable - in particular header image wants to
               // be the site's logo, not civicrm's.
               'header_bottom_border'     => 'B8D432',
               'header_background_colour' => 'ffffff',
               // @todo - these should be customisable
               'notification_url'         => $notifyURL,
               'header_image'             => $headerImage,
                );
        
        $url = $this->_paymentProcessor['url_site'] . '?' ;
      
        foreach ( $form_vars as $key => $value ) {
          $url .= "$key=" . urlencode($value) . '&' ;
        }
      
        // prob not required
        $url = trim($url,'&');

        CRM_Utils_System::redirect( $url );
        exit( );
    }

    /**
     * This method is handles the response that will be invoked (from extern/googleNotify) every time
     * a notification or request is sent by the Google Server.
     *
     */
  function confirm($response) {        
        $config =& CRM_Core_Config::singleton();
        define('RESPONSE_HANDLER_LOG_FILE', $config->uploadDir . 'CiviCRM.Flo2Cash.log');
        
        //Setup the log file
        if (!$message_log = fopen(RESPONSE_HANDLER_LOG_FILE, "a")) {
            error_func("Cannot open " . RESPONSE_HANDLER_LOG_FILE . " file.\n", 0);
            exit(1);
        }
        
        // Retrieve the XML sent in the HTTP POST request to the ResponseHandler
        if (get_magic_quotes_gpc()) {
            $xml_response = stripslashes($xml_response);
        }

        require_once 'CRM/Utils/System.php';
        $headers = CRM_Utils_System::getAllHeaders();
        fwrite($message_log, sprintf("\n\r%s:- %s\n",date("D M j G:i:s T Y"),
                                     print_r($response,1)));

        // sample MNS code from Flo2Cash
        $PostData = ""; 
        
        // add the cmd param to the values we'll send back
        $_POST['cmd'] = '_xverify-transaction';
        // only send these values (and send them in this order, too?)
        $parms = array( 'verifier', 'transaction_id', 'transaction_status', 'cmd', 
                        'account_id', 'reference', 'card_type', 'receipt_id',
                        'response_text' ) ;
        
        //iterate thru the values required
        foreach ($parms as $key) 
        { 
         $PostData .= $key . "=" . urlencode($_POST[$key]) . "&"; 
        } 

        // remove the final ampersand
        $PostData = trim( $PostData, '&' );
         
        //post data to the server using CURL to get the response 
        $ch = curl_init('http://demo.flo2cash.co.nz/web2pay/MNSHandler.aspx'); 
         
        //set various options for a CURL transfer 
        curl_setopt ($ch, CURLOPT_POST, 1); 
        curl_setopt ($ch, CURLOPT_POSTFIELDS, $PostData); 
        curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);  
         
        $response = curl_exec ($ch); 
        curl_close($ch); 

        fwrite($message_log, sprintf("\n\r%s:- %s\n",date("D M j G:i:s T Y"),
                                     print_r($PostData,1)));
        fwrite($message_log, sprintf("\n\r%s:- %s\n",date("D M j G:i:s T Y"),
                                     print_r($response,1)));

  return ;
        
        // Retrieve the root and data from the xml response
        $xmlParser = new XmlParser($xml_response);
        $root      = $xmlParser->GetRoot();
        $data      = $xmlParser->GetData();
        
        $orderNo   = $data[$root]['google-order-number']['VALUE'];
        
        // lets retrieve the private-data
        $privateData = $data[$root]['shopping-cart']['merchant-private-data']['VALUE'];
        $privateData = $privateData ? self::stringToArray($privateData) : '';
        
        list( $mode, $module, $paymentProcessorID ) = self::getContext($xml_response, $privateData, $orderNo, $root);
        $mode   = $mode ? 'test' : 'live';

        require_once 'CRM/Core/BAO/PaymentProcessor.php';
        $paymentProcessor = CRM_Core_BAO_PaymentProcessor::getPayment( $paymentProcessorID,
                                                                       $mode );
        
        $ipn    =& self::singleton( $mode, $module, $paymentProcessor );
        
        // Create new response object
        $merchant_id  = $paymentProcessor['user_name'];
        $merchant_key = $paymentProcessor['password'];
        $server_type  = ($mode == 'test') ? "sandbox" : '';
        
        $response = new GoogleResponse($merchant_id, $merchant_key,
                                       $xml_response, $server_type);
        fwrite($message_log, sprintf("\n\r%s:- %s\n",date("D M j G:i:s T Y"),
                                     $response->root));

        //Check status and take appropriate action
        $status = $response->HttpAuthentication($headers);
        
        switch ($root) {
            
        case "request-received":
        case "error":
        case "diagnosis":
        case "checkout-redirect":
        case "merchant-calculation-callback":
            break;

        case "new-order-notification": {
            $response->SendAck();
            $ipn->newOrderNotify($data[$root], $privateData, $module);
            break;
        }

        case "order-state-change-notification": {
            $response->SendAck();
            $new_financial_state = $data[$root]['new-financial-order-state']['VALUE'];
            $new_fulfillment_order = $data[$root]['new-fulfillment-order-state']['VALUE'];
            
            switch($new_financial_state) {

            case 'CHARGEABLE':
                $amount = $ipn->getAmount($orderNo);
                if ($amount) {
                    $response->SendChargeOrder($data[$root]['google-order-number']['VALUE'], 
                                               $amount, $message_log);
                    $response->SendProcessOrder($data[$root]['google-order-number']['VALUE'], 
                                                $message_log);
                }
                break;

            case 'CHARGED':
            case 'PAYMENT_DECLINED':
            case 'CANCELLED':
                $ipn->orderStateChange($new_financial_state, $data[$root], $module);
                break;

            case 'REVIEWING':
            case 'CHARGING':
            case 'CANCELLED_BY_GOOGLE':
                break;

            default:
                break;
            }
        }

        case "charge-amount-notification":
        case "chargeback-amount-notification":
        case "refund-amount-notification":
        case "risk-information-notification":
            $response->SendAck();
            break;

        default:
            break;

        }
        
  }

    /**  
     * singleton function used to manage this object  
     *  
     * @param string $mode the mode of operation: live or test
     *  
     * @return object  
     * @static  
     */  
    static function &singleton( $mode, $component, &$paymentProcessor ) {
        if ( self::$_singleton === null ) {
            self::$_singleton = new CRM_Core_Payment_GoogleIPN( $mode, $paymentProcessor );
        }
        return self::$_singleton;
    }  

}

?>
