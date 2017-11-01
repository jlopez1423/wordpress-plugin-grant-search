<?php

if( session_status() == PHP_SESSION_NONE )
{
	session_start();
}

/**
 * Helper class to handle Salesforce SOAP API connection and requests
 * 
 **/
class Salesforce_API
{
	/**
	 * Class Constructor
	 */
	public function __construct()
	{
		$this->load_config();

	}

	/**
	 * Load in the Salesforce config file.
	 **/
	public function load_config()
	{
		require_once( 'config.php' );
	}

	/**
	 * Create a new Salesforce connection and submit data
	 * @param array $account_fields  - array of Salesforce Account fields.
	 * @param array $donation_fields - array of Salesforce Oppurtunity fields.
	 */
	public function send_data()
	{

		//Create a new Salesforce Partner object
		$mySforceConnection = new SforceEnterpriseClient();

		//Create the SOAP connection to Salesforce
		try
		{
			$mySforceConnection->createConnection( SALESFORCE_ENTERPRISE_WSDL );

			if( isset( $_SESSION['salesforceSessionId'] ) )
			{
				$mySforceConnection->setEndpoint( $_SESSION['salesforceLocation'] );
				$mySforceConnection->setSessionHeader( $_SESSION['salesforceSessionId'] );
				
			}
			else
			{	
				//Pass login details to Salesforce
				try
				{
					$mySforceConnection->login( SALESFORCE_USERNAME, SALESFORCE_PASSWORD.SALESFORCE_SECURITY_TOKEN );
					$_SESSION['salesforceLocation']	 	= $mySforceConnection->getLocation();
					$_SESSION['salesforceSessionId'] 	= $mySforceConnection->getSessionId();
				}
				catch( Exception $e )
				{
					//Make sure your username and password is correct
					//Otherwise, handle this exception
					echo 'Caught login() exception using SforceEnterpriseClient: ' . $e->getMessage();
					die; 
				}
			}
			if( isset( $_SESSION['salesforceSessionId'] ) )
			{	
				//Maybe this is where I want to submit a new query
				$results_arr = array();
				$all_data = $this->get_all_grants( $mySforceConnection, $results_arr );

				return $all_data;
			}

		}	
			catch( Exception $e )
			{ 
				//Salesforce could be down, or you have an erro in the configuration
				//Check your WSDL path
				//Otherwise, handle this exception
				echo 'Caught createConnection() exception using SforceEnterpriseClient: '. $e->getMessage();
				die;
			}
		return $all_data;
	}

	public function get_all_grants( $connection, $arr ) 
	{	
		$result_list 		= array();
		$id_list 			= array();
		$id_list_array 		= array();
	
			$query 		= "SELECT Id, Project_Title__c, Account.Name, AccountId, Grant_Amount__c, Name, CloseDate, Disposition_Date__c, Grant_Period__c, Description, Disposition__c, Account.Website 
						   FROM Opportunity
						   WHERE Disposition__c = 'Approved' 
						   AND   Disposition_Date__c >= 2013-01-01 
						   AND Grant_Amount__c >= 10000 
						   AND Lead_Program_Name__c <> 'Foundation Special Interest'
							AND Lead_Program_Name__c <> 'Board Special Interest'
							AND Opportunity_Fund_Class__c <> 'Membership'
						   AND (NOT Account.Name LIKE '%CAE%')";

			$sObjects 	= $connection->query( $query );

			array_push( $result_list, $sObjects );

			!$done = false;

			if( $sObjects->size > 0 )
			{
				while( !$done )
				{
					if( $sObjects->done != true)
					{
						try
						{

							$sObjects 	= $connection->queryMore( $sObjects->queryLocator );
							array_push( $result_list, $sObjects );

						}  catch (Exception $e) {
								// die("end it");
    				     		print_r($mySforceConnection->getLastRequest());
	          					echo $e->faultstring;
        					}

					}
					else {
						$done = true;
					}
				}
				
			}
			$final_result = array();
			foreach( $result_list as $k=>$v)
			{
				$new[$k] = $v->records;
			}
			foreach( $new as $single )
			{
				foreach($single as &$r )
				{
					array_push($final_result, $r); 
				}		
			}
		return $final_result;
	}

	
}



?>