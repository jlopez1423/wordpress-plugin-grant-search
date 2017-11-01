<?php
define( "SALESFORCE_USERNAME", "fc@stuartfoundation.org" );
define( "SALESFORCE_PASSWORD", "W3lcom3W3lcom3" ); 
define( "SALESFORCE_SECURITY_TOKEN", "HZXkwcrSChL7wWFQCMWudTrUP" ); //If this changes the whole thing will break until new creds are added in
plugin_dir_path( __FILE__ ); //relateive to the current file
define( "SALESFORCE_ENTERPRISE_WSDL", plugin_dir_path( __FILE__ ) . 'enterprise-12-07-2016-2.wsdl.xml' );

require_once( plugin_dir_path( __FILE__ ) . 'SforceEnterpriseClient.php' );/*make reltive to plugin*/

?>