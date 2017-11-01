<?php

/*
 grants wordpress admin portion
 -  make sure you have a "grants" post type defined
 -  make sure you have advanced custom fields plugin installed and the below custom fields for "grants" post type

 Description - wysiwig - grant_description
 City - text - grant_city --> Might just have to delete all of these entries(replaced)
 Amount - text - grant_amount
 Year - text - grant_year
 Program Area - text - grant_program_area
 Community - text- grant_community
 Grant ID - text - grant_id

 New fields:
 Organization Name - organization_name (in)
 Term - grant_period
 Disposition Date - approval_date

*/

class Grant_Admin
{
	
	public function __construct()
	{
		add_action( 'admin_menu', array( $this, 'add_import_link' ) );

		add_action( 'pre_get_posts', array( $this, 'admin_search_grants' ) );

		add_filter( 'posts_clauses', array( $this, 'intercept_query_clauses' ), 20, 1 );
	}

	function intercept_query_clauses( $pieces ) {

		global $wp_query;
		global $wpdb;

		if( ! is_admin() )
		{
	        return $pieces;
		}
	   // die($_GET['post_type']);
	   if( isset( $_GET['s'] ) && 
	   		! empty( $_GET['s'] ) && isset( $_GET['post_type'] ) && $_GET['post_type'] == 'grants' &&
	   		$wp_query->query_vars['post_type'] == 'grants'
	    ) {

	  		$where = $pieces['where'];
	
	  		$where = substr( $where, 5 );
	  		$where_is = strpos( $where, " AND " . $wpdb->prefix . "posts.post_type = 'grants'" );
	  		$where_out = substr( $where, 0, $where_is );

	  		$where_replace = str_replace( ' AND ', ' OR ', $where_out );
	  		$where = str_replace();
	  		$pieces['where'] = str_replace( $where_out, ' ( ( ' . $where_replace . ' ) ) ', $pieces['where'] );

	  		$pieces['where'] = str_replace( "'grant_year' OR CAST", "'grant_year' AND CAST", $pieces['where'] );
	  		$pieces['where'] = str_replace( "'organization_name' OR CAST", "'organization_name' AND CAST", $pieces['where'] );


	  	}


	    return $pieces;
	}

	function admin_search_grants( $query ) {
	    // make sure we update the archive query only for the WP Admin

	    if( ! is_admin() ) {
	        return $query;
	    }

	    if( is_post_type_archive( 'grants' ) && $query->is_main_query() ) {
	        
	    	$meta_query = array();
			// filter partners by speciality
			if( isset( $_GET['s'] ) && ! empty( $_GET['s'] ) )
			{

				$meta_query[] = array(
					'relation' => 'OR',
					array(
						'key' => 'grant_year',
						'value' =>  $_GET['s'], // the relationship db value looks like this(serialized array): a:1:{i:0;s:3:"123";}
						'compare' => 'LIKE'
					),
					array(
						'key' => 'organization_name',
						'value' =>  $_GET['s'], // the relationship db value looks like this(serialized array): a:1:{i:0;s:3:"123";}
						'compare' => 'LIKE'
					)
				);
			
				$query->set( 'meta_query', $meta_query );
			}
	    } 
	}


	//add the sublink under Grants menu
    public function add_import_link()
    {
        add_submenu_page( 'edit.php?post_type=grants', __( 'Import Grants', 'grants-import' ), __( 'Import', 'grants-import' ), 'manage_options', 'grants-import', array( $this, 'grants_import' ) );
    }

    //list dashboard views
    public function grants_import()
    {	
    	//user wants to import
    	if( @$_POST['import_now'] )
		{
			$valid = true;

			//check data
			if ( ! wp_verify_nonce( $_POST['import_now'], 'grant_import_once' ) )
			{
				echo '<div class="error"><p>Security check failed. Please refresh the page.</p></div>'; 
				$valid = false;
			}
			else
			{

		    		$Reader 		= new Salesforce_API();
		    		$reader_data 	= $Reader->send_data();//Get all grants from salesforce --> Being done succesfuly

					$failed 	= 0;
					$new 		= 0;
					$updated 	= 0;
					
					foreach( $reader_data as $data )//This is checking the grant_ids
					{
						
						if( ! empty( $data->Id ) )
						{
						
							$args = array(
							    'meta_query' => array(
							        array(
							            'key' => 'grant_id',
							            'value' => $data->Id
							        )
							    ),
							    'post_type' => 'grants',
							    'posts_per_page' => -1
							);

							$posts = get_posts($args);
							if( ! $posts )
							{
								//new grant
								$post_id = wp_insert_post(
									array(
										'comment_status'	=>	'closed',
										'ping_status'		=>	'closed',
										'post_title'		=>	$data->Account->Name,
										'post_status'		=>	'publish',
										'post_type'			=>	'grants'
									)
								);
								
								if( $post_id )
								{
									add_post_meta( $post_id, 'organization_name', $data->Account->Name );
									add_post_meta( $post_id, 'grant_amount', $data->Grant_Amount__c );
									add_post_meta( $post_id, 'grant_description', $data->Description );
									add_post_meta( $post_id, 'approval_date', $data->Disposition_Date__c );
									add_post_meta( $post_id, 'grant_id', $data->Id );
									add_post_meta( $post_id, 'grant_period', $data->Grant_Period__c );
									add_post_meta( $post_id, 'organization_website', $data->Account->Website );
									$new++;
								}
								else
								{
									$failed++;
								}

							} 
							else
							{
								//update grant
								foreach( $posts as $p )
								{
									update_post_meta( $post_id, 'organization_name', $data->Account->Name );
									update_post_meta( $post_id, 'grant_amount', $data->Grant_Amount__c  );
									update_post_meta( $post_id, 'grant_description', $data->Description );
									update_post_meta( $post_id, 'approval_date', $data->Disposition_Date__c  );
									update_post_meta( $post_id, 'grant_period', $data->Grant_Period__c );
									update_post_meta( $post_id, 'grant_period', $data->Account->Website );
										
								}
								$updated++;
							}

						}
					}
					update_option( 'grants_last_import', date( 'm/d/Y H:i:s' ) );
					echo '<div class="updated"><p>Data was imported successfully. Summary: ' . $new . ' new records, ' . $updated . ' updated records, ' . $failed . ' fails.</p></div>'; 
				// }
			}
			
		}

		$last_import = get_option( 'grants_last_import', false );

        ?>
            <style type="text/css">
            .cpac-edit { display: none !important; }
            </style>
            <div class="wrap">
                <div id="icon-users" class="icon32"></div>
                <h2>Import Grants</h2>
                <p>Last Import: <?php echo ( $last_import ? esc_html( $last_import ) : 'N/A' ); ?></p>
            	<p>Please hit the import button to download the latest grants from Salesforce. The following will be returned:</p>
            	<p>Grant ID, Grant Name,Organization, Amount, Description, Approval Date, Account Website,Term</p>
            	<form action="<?php echo esc_attr( $_SERVER['REQUEST_URI'] ) ?>" method="post" enctype="multipart/form-data">
            		<!-- <input type="file" name="grant_import_csv" value="" /> -->
            		<input type="hidden" name="import_now" value="<?php echo wp_create_nonce( 'grant_import_once' ); ?>" />
            		<button class="button button-primary button-large">Import</button>
            	</form>
            </div>
        <?php
    }

}

new Grant_Admin();

?>
