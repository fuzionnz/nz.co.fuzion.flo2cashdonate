CiviCRM Flo2Cash Donate Payment Processor
=========================================

* License: GPLv3+
* &copy; 2012 Giant Robot Ltd
* Author: Chris Burgess <chris@giantrobot.co.nz>

Flo2Cash's Donate interface is designed for NFP (Not For Profit) organisations.
It is a hosted solution (donors are redirected to the F2C page) and it can
support recurring payments.

* [Flo2Cash services for Not For Profits](http://www.flo2cash.co.nz/notforprofit.php)
* [Contact Flo2Cash](http://www.flo2cash.co.nz/contact.php) for more information and pricing.

Installation
------------

**Installing the Extension**

See [CiviCRM's documentation](http://wiki.civicrm.org/confluence/display/CRMDOC/Extensions)
for instructions on installing CiviCRM extensions.

**Configuration**

* Visit CiviCRM's Payment Processors page
* Configure the live and test URLs with those provided by Flo2Cash, eg
  * Live: https://secure.flo2cash.co.nz/donations/YOURORGNAME/donate.aspx
  * Test: http://demo.flo2cash.co.nz/donations/YOURORGNAME/donate.aspx
* Save
* Select F2C Donate processor instance when editing CiviCRM Contribution pages etc.

**IPN Support**

CiviCRM has built-in IPN support from v4.2.

Upgrading to the latest version of CiviCRM is recommended. However, if you are
using an earlier version of CiviCRM, you will also need to copy the file
`extIPN.php` from the extension directory to `civicrm/extern/extIPN.php`

TODO
----

Items to do before v1.0 release:

* Link to Donate interface docs.
* Test with Event rego.
* Verify removal of Account ID and URL Recur with F2C
