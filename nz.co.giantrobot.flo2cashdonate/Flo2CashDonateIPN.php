<?php

require_once 'CRM/Core/Payment/BaseIPN.php';

class nz_co_giantrobot_Flo2CashDonateIPN extends CRM_Core_Payment_BaseIPN {

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
    function __construct( $mode ) {
        parent::__construct( );
        
        $this->_mode = $mode;
    }

    /**  
     * singleton function used to manage this object  
     *  
     * @param string $mode the mode of operation: live or test
     *  
     * @return object  
     * @static  
     */  
    static function &singleton( $mode, $component ) {
        if ( self::$_singleton === null ) {
            self::$_singleton = new nz_co_giantrobot_Flo2CashDonateIPN( $mode );
        }
        return self::$_singleton;
    }
    
    static function retrieve( $name, $type, $location = 'POST', $abort = true ) 
    {
        static $store = null;
        $value = CRM_Utils_Request::retrieve( $name, $type, $store,
                                              false, null, $location );
        if ( $abort && $value === null ) {
            CRM_Core_Error::debug_log_message( "Could not find an entry for $name in $location" );
            echo "Failure: Missing Parameter $name in $location<p>";
            exit( );
        }
        return $value;
    }

    /**  
     * The function retrieves the amount the contribution is for, based on the order-no google sends
     *  
     * @param int $orderNo <order-total> send by google
     *  
     * @return amount  
     * @access public 
     */  
    function getAmount($orderNo) {
        require_once 'CRM/Contribute/DAO/Contribution.php';
        $contribution = new CRM_Contribute_DAO_Contribution( );
        $contribution->invoice_id = $orderNo;
        if ( ! $contribution->find( true ) ) {
            CRM_Core_Error::debug_log_message( "Could not find contribution record with invoice id: $orderNo" );
            echo "Failure: Could not find contribution record with invoice id: $orderNo <p>";
            exit( );
        }
        return $contribution->total_amount;
    }

    /**
     * This method is handles the response that will be invoked (from extern/googleNotify) every time
     * a notification or request is sent by the Google Server.
     *
     */
    static function main() 
    {
/* for IPN debugging - store the IPN data in /tmp/ and process manually */
//        if ( $_SERVER['REMOTE_ADDR'] != '10.0.0.5' ) {
//            file_put_contents('/tmp/f2c_ext.' . date('YmdHis') . '.txt', var_export(array('_GET' => $_GET, '_POST' => $_POST),1));
//            die();
//        }

        require_once 'CRM/Core/Config.php';
        require_once 'CRM/Utils/Request.php';
        require_once 'CRM/Contribute/DAO/Contribution.php';
        $config = CRM_Core_Config::singleton();

        //Setup the log file
        define('RESPONSE_HANDLER_LOG_FILE', $config->uploadDir . 'CiviCRM.Flo2Cash.log');
        if (!$message_log = fopen(RESPONSE_HANDLER_LOG_FILE, "a")) {
            error_func("Cannot open " . RESPONSE_HANDLER_LOG_FILE . " file.\n", 0);
            exit(1);
        }
        CRM_Core_Error::debug_log_message( "Transaction Received" );

        // GET
        $module                = self::retrieve( 'module',                'String',  'GET',  true );
        $contact_id            = self::retrieve( 'contactID',             'Integer', 'GET',  true );
        $contribution_id       = self::retrieve( 'contributionID',        'Integer', 'GET',  true );
        // POST
        $donation_id           = self::retrieve( 'Donation_ID',           'Integer', 'POST', true );
        $f2c_reference_id      = self::retrieve( 'F2C_Reference_ID',      'String', 'POST', true );
        $transaction_status_id = self::retrieve( 'Transaction_Status_ID', 'String',  'POST', true );
        $invoice_id            = self::retrieve( 'Invoice_ID',            'String',  'POST', true );
        $amount                = self::retrieve( 'Amount',                'String',  'POST', true );
        $donation_type         = self::retrieve( 'Donation_Type',         'String',  'POST', true );

        $contribution = new CRM_Contribute_DAO_Contribution( );
        $contribution->id = $contribution_id;
        if ( ! $contribution->find( true ) ) {
            CRM_Core_Error::debug_log_message( "Could not find contribution record with invoice id: $contribution_id" );
            echo "Failure: Could not find contribution record with invoice id: $orderNo <p>";
            exit( );
        }

        $mode  = ($contribution->is_test) ? 'test' : 'live';
        $ipn   =& self::singleton( $mode, $module );
        $input = $objects = $ids = array( );
        $input['component'] = strtolower($module);

        if ( $contribution->contribution_status_id == 1 ) {
            CRM_Core_Error::debug_log_message( "Contribution already handled (ContributionID = $contribution_id)." );
            echo "Contribution already handled.<p>";            
            exit( );
        }

        $objects['contribution'] =& $contribution;
        $ids['contribution']     =  $contribution->id;
        $ids['contact']          =  $contribution->contact_id;

        $ids['event'] = $ids['participant'] = $ids['membership'] = null;
        $ids['contributionRecur'] = $ids['contributionPage'] = null;
                
        list( $ids['membership'], $ids['related_contact'], $ids['onbehalf_dupe_alert'] ) = 
            explode( CRM_Core_DAO::VALUE_SEPARATOR, $contribution->trxn_id );

        foreach ( array('membership', 'related_contact', 'onbehalf_dupe_alert') as $fld ) {
            if ( ! is_numeric( $ids[$fld] ) ) {
                unset( $ids[$fld] );
            }
        }

        $ipn->loadObjects( $input, $ids, $objects, true );

        require_once 'CRM/Core/Transaction.php';
        $transaction = new CRM_Core_Transaction( );

        switch ($transaction_status_id) {
        case 0:
            //transaction failed.
            $ipn->failed($objects, $transaction);
            print "Failed";
            break;
        case 1:
            $input['amount']     = $contribution->total_amount;
            $input['fee_amount'] = null;
            $input['net_amount'] = null;
            $input['trxn_id']    = $f2c_ref;
            $input['is_test']    = $contribution->is_test;

            $ipn->completeTransaction( $input, $ids, $objects, $transaction );
            print "Completed";
            break;
        default:
            CRM_Core_Error::debug_log_message( "Contribution IPN had unrecognised Transaction Status." );
            echo "Failure: Unrecognised Transaction Status.<p>";
            exit( );
        }
        
    }

}
