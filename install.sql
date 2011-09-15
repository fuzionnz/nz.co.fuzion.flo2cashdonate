INSERT INTO civicrm_payment_processor_type ( 
  name, title, description, 
  is_active, is_default, user_name_label, 
  password_label, signature_label, subject_label, 
  class_name, url_site_default, url_api_default, 
  url_recur_default, url_button_default, 
  url_site_test_default, url_api_test_default, 
  url_recur_test_default, url_button_test_default, 
  billing_mode, is_recur 
 ) values ( 
  -- name 
  'Flo2Cash_Donate', 
  -- title 
  'Flo2Cash Donate', 
  -- description 
  '', 
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