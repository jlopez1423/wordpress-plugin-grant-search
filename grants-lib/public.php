<?php


/*
	Class grants public
*/

class Grant_Public
{

	public function __construct()
	{

		add_action( 'wp_ajax_search_grants', array( $this, 'search_grants' ) );
		add_action( 'wp_ajax_nopriv_search_grants', array( $this, 'search_grants' ) );

		wp_enqueue_script( 'grants-custom', plugin_dir_url( __FILE__ ) . 'skin/global.js', array(), '1.0.1', 'all' );
        wp_enqueue_script( 'select2', plugin_dir_url( __FILE__ ) . 'skin/select2.min.js', array(), '1.0.1', 'all' );
        wp_enqueue_script( 'customjs', plugin_dir_url( __FILE__ ) . 'skin/custom.js', array(), '1.0.1', 'all' );



		//redirect to home page if archive is accessed
		add_filter( 'archive_template', array( $this, 'archive_template' ) );
		add_filter( "single_template", array( $this, 'single_template' ) );

		global $wpdb;

		$approval_date 	= $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT meta_value FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value != '' AND post_id IN (SELECT ID FROM {$wpdb->posts} WHERE post_type = %s AND post_status = %s) ORDER BY meta_value ASC", 'approval_date', 'grants', 'publish' ) );
		$organization 	= $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT meta_value FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value != '' AND post_id IN (SELECT ID FROM {$wpdb->posts} WHERE post_type = %s AND post_status = %s) ORDER BY meta_value ASC", 'organization_name', 'grants', 'publish' ) );
		$term 	= $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT meta_value FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value != '' AND post_id IN (SELECT ID FROM {$wpdb->posts} WHERE post_type = %s AND post_status = %s) ORDER BY meta_value ASC", 'grant_period', 'grants', 'publish' ) );

			wp_localize_script(
				'grants-custom',
				'phpParams',
				array(
					'grantsApproval' 			=> json_encode( $approval_date ),
					'grantsOrganizaiton' 		=> json_encode( $organization ),
					'grantsPeriod' 				=> json_encode( $term )
				)
			);

	}


	public function archive_template( $template )
	{
		global $post;

    	if( is_post_type_archive ( 'grants' ) )
    	{
        	wp_redirect( home_url() );
         	die;
	    }

	    return $template;
	}


	public function single_template( $template )
	{
		global $post;

    	if( $post->post_type == 'grants' )
    	{
        	wp_redirect( home_url() );
         	die;
	    }

	    return $template;
	}


	//search grants ajax call
	public function search_grants()
	{
		$search_word	= $_GET['search_word'];
		$keyword 		= $_GET['keyword'];
		$term 			= $_GET['term'];
		$app_date 		= $_GET['app_date'];
		$org 			= stripslashes( $_GET['org'] );
		$grant 			= $_GET['grant'];
		$pagenum		= $_GET['pagenum'];
		$ids = array();


		$results_per_page = 20;
		$args = array(
		    'post_type' => 'grants',
		    'posts_per_page' => -1,
		    'paged' => $pagenum,
		    'post_status'	=> 'publish',
			'orderby'  => array( 'title' => 'ASC' )
		);

		// Using keyword
		if( $keyword )
		{
			foreach( $keyword as $k )
			{
				$posts = get_posts( 's=' . $k . '&posts_per_page=-1&post_type=grants&post_status=publish' );

				if( $posts )
				{
					foreach( $posts as $p )
					{
						$ids[] = $p->ID;
					}
				}
			}

			$args['post__in'] = $ids;
		}

		if($term || $app_date || $org || $grant || $search_word )
		{
			$meta_query = array(
				'relation' => 'AND'
			);

			if( $search_word )
			{
				$meta_query[] = array(
					'key' => 'grant_description',
					'value' => $search_word,
					'compare' => 'LIKE'
				);
			}

			if( $org )
			{
				$meta_query[] = array(
					'key' => 'organization_name',
					'value' => $org,
					'compare' => 'LIKE'
				);
			}

			if( $term )
			{
				$meta_query[] = array(
					'key' => 'grant_period',
					'value' => $term,
					'compare' => '=',
					'type'    => 'numeric',
				);
			}

			if( $app_date )
			{
				$meta_query[] = array(
					'key' => 'approval_date',
					'value' => $app_date,
					'compare' => '=',
					'type'    => 'numeric',
				);
			}

			if( $grant != 'Grant Amount' )
			{
				$amount = explode( '-', $grant );

				if( ! $amount[0] )
				{
					$meta_query[] = array(
						'key' => 'grant_amount',
						'value' => $amount[1],
						'compare' => '<=',
						'type'    => 'numeric',
					);
				}
				elseif( ! $amount[1] )
				{
					$meta_query[] = array(
						'key' => 'grant_amount',
						'value' => $amount[0],
						'compare' => '>',
						'type'    => 'numeric'
					);
				}
				else
				{
					$meta_query[] = array(
						'key' => 'grant_amount',
						'value' => $amount[0],
						'compare' => '>=',
						'type'    => 'numeric'
					);
					$meta_query[] = array(
						'key' => 'grant_amount',
						'value' => $amount[1],
						'compare' => '<=',
						'type'    => 'numeric'
					);
				}
			}

			$args['meta_query'] = $meta_query;
		}

		if( @$_GET['pagenum'] )
		{
			$args['paged'] = ( $_GET['pagenum'] + 1 );

		}


		$post_query = new WP_Query( $args );
		$the_count 	= $post_query->found_posts;

		$custom_query = get_posts( $args );



		if( $custom_query )
		{
			?>
			<?php if( ! @$args['paged'] ): ?>
				<h2>Results (<?= $the_count; ?> Found)</h2>
				<div class="results-table">
					<div class="table-header">
						<div class="table-cell" id="org-name">Organization Name</div>
						<div class="table-cell" id="grant-amount">Grant Amount</div>
						<div class="table-cell" id="grant-period">Term</div>
						<div class="table-cell" id="approval-date">Approval Year</div>
					</div>
			<?php endif; ?>
			<?
			$counter = 0;
			foreach( $custom_query as $p )
			{

				//see if we have a next page
				if( $counter > $results_per_page )
				{
					continue;
				}
				//show results
				?>
				<a class="result-wrapper table-row" href="http://<?php echo esc_html( get_field( 'organization_website', $p->ID ) ); ?>">
					<div class="table-cell org-column" org-name="<?php echo esc_html( get_field( 'organization_name', $p->ID ) ); ?>"><span><?php echo esc_html( get_field( 'organization_name', $p->ID ) ); ?></span></div>
					
					<div class="table-cell grant-amount-column" grant-amount="<?php echo esc_html( number_format( get_field( 'grant_amount', $p->ID ), 2 ) ); ?>">$<?php echo esc_html( number_format( get_field( 'grant_amount', $p->ID ), 2 ) ); ?></div>
					<?php if( get_field('grant_period', $p->ID) <= 1 ): ?>
						<div class="table-cell grant-period-column" grant-period="<?php echo esc_html( get_field( 'grant_period', $p->ID ) ); ?>"><?php echo esc_html( get_field( 'grant_period', $p->ID ) ); ?> months</div>
					<?php else: ?>

						<!-- Month/Year Check -->
						<?php if( (int)get_field( 'grant_period', $p->ID ) < 12 ): ?>
							<div class="table-cell grant-period-column" grant-period="<?php echo esc_html( get_field( 'grant_period', $p->ID ) ); ?>"><?php echo esc_html( get_field( 'grant_period', $p->ID ) ); ?> months</div>
						<?php else: ?>
							<?php 
								$calc_term  = (int)get_field( 'grant_period', $p->ID );
								$year 		=  floor( $calc_term/12 ); 
								$total_year = ( ( $calc_term % 12 )  <= 5 ? $year : ( $year+1 ) );	
							?>
							<?php if( $total_year == 1 ): ?>
								<div class="table-cell grant-period-column" grant-period="<?php echo esc_html( get_field( 'grant_period', $p->ID ) ); ?>"><?php echo esc_html( $total_year ) ?> year</div>	
							<?php else: ?>
								<div class="table-cell grant-period-column" grant-period="<?php echo esc_html( get_field( 'grant_period', $p->ID ) ); ?>"><?php echo esc_html( $total_year ) ?> years</div>
							<?php endif; ?>
						<?php endif; ?>
						<!-- End month/year check -->
					
					<?php endif; ?>
					<div class="table-cell approval-column" approval-date="<?php echo esc_html(  substr( get_field( 'approval_date', $p->ID ),0,4 ) ); ?>"><?php echo esc_html(  substr( get_field( 'approval_date', $p->ID ),0,4 ) ); ?></div>
				</a>
				<?

			}?>


			<?php $args['paged'] = ( @$args['paged'] + 1 );
			if( $args['paged'] <= 1 ) { $args['paged']++; }
			if( count( get_posts( $args ) ) )
			{
				?>
				
				<?php
			}
		}
		else
		{
			echo '<p class="grant_no_results">Sorry, we couldn\'t find any results.</p>';
		}
		die;
		echo '</div>';
	}

}


new Grant_Public();

?>