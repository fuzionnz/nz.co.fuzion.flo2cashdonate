<?php
/**
 * Flo2Cash.Import API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRM/API+Architecture+Standards
 */
function _civicrm_api3_flo2cash_create_spec(&$spec) {
  $spec['trxn_id'] = array(
    'api.is_required' => 1,
    'title' => 'Transaction ID',
    'name' => 'trxn_id',
  );
  $spec['amount'] = array(
    'title' => 'Amount',
    'name' => 'amount',
  );
  $spec['status_id'] = array(
    'title' => 'Status',
    'name' => 'status_id',
  );
  $spec['receive_date'] = array(
    'title' => 'Recive Date',
    'type' => CRM_Utils_Type::T_DATE,
    'name' => 'receive_date',
  );
  $spec['identifier'] = array(
    'title' => 'Identifier',
    'name' => 'identifier',
  );
}

/**
 * Flo2Cash.Import API
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_flo2cash_create($params) {
 print_r($params);
 try{
  $cparams = array(
    'return' => array('contribution_source', 'contribution_page_id', 'contribution_recur_id', 'contact_id', 'financial_type')
  );
  if(!empty($params['identifier'])) {
    $cparams['trxn_id'] = $params['identifier'];
  }
  else {
    $cparams['contribution_recur_id'] = $params['contribution_recur_id'];
    $cparams['options'] = array('limit' => 1, 'sort' => 'receive_date');
  }
  $origCont = civicrm_api3('contribution', 'getsingle', $cparams);

//  $dateParts = explode('/', $params['receive_date']);
//  dpm($d)
//  $params['receive_date'] = $dateParts[0] . "-" . $dateParts[1] . "-" .  $dateParts[2];
  $statusmap = array(
    'Successful' => 'Completed',
    'Bank Declined' => 'Failed',
    'Declined - Authority cancelled' => 'Cancelled',
    'Declined - Insufficient funds' => 'Failed',
    'Processing' => 'Pending',
  );
  /*
  print_r(array(
    'total_amount' => $params['amount'],
    'receive_date' => $params['receive_date'],
    'contribution_recur_id' => $origCont['contribution_recur_id'],
    'contact_id' => $origCont['contact_id'],
    'financial_type_id' => $origCont['financial_type_id'],
    'trxn_id' => $params['trxn_id'],
    'contribution_page_id' => $origCont['contribution_page_id'],
    'contribution_status_id' => $statusmap[$params['status_id']],
    'source' => ts(' Flo2Cash (repeat)') . $origCont['source'],
  ));*/
  $result = civicrm_api3('contribution', 'create', array(
    'total_amount' => $params['amount'],
    'receive_date' => $params['receive_date'],
    'contribution_recur_id' => $origCont['contribution_recur_id'],
    'contact_id' => $origCont['contact_id'],
    'financial_type_id' => $origCont['financial_type_id'],
    'trxn_id' => $params['trxn_id'],
    'contribution_page_id' => $origCont['contribution_page_id'],
    'contribution_status_id' => $statusmap[$params['status_id']],
    'source' => ts(' Flo2Cash (repeat)') . $origCont['contribution_source'],
  )
  );

  $recur = civicrm_api3('contribution_recur', 'getsingle', array('id' => $origCont['contribution_recur_id']));
  if(strtotime($params['receive_date']) > strtotime($recur['next_sched_contribution_date'])) {
    $nextDate = date('Y-m-d', strtotime("+ " . $recur['frequency_interval'] . " " . $recur['frequency_unit'], strtotime($params['receive_date'])));
    civicrm_api3('contribution_recur', 'create', array('id' => $recur['id'], 'next_sched_contribution_date' => $nextDate));
  }
 }
 catch (EXCEPTION $e) {
 }
  // Spec: civicrm_api3_create_success($values = 1, $params = array(), $entity = NULL, $action = NULL)
  return civicrm_api3_create_success($result['values'], $params, 'Flo2CashDD', 'import');
}
