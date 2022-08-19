<?php
/*
Plugin Name:		Maintenance & Coming Soon Redirect Animation
Plugin URI:			https://wordpress.org/plugins/maintenance-coming-soon-redirect-animation/
Description:		Make your website in maintenance mode in seconds with great looking animations and configure settings to allow specific users to bypass the maintenance mode functionality in order to preview the site prior to public launch.
Version:			1.1.1
Stable tag:	 		1.1.1
Requires at least:	4.6
Tested up to:		6.0.1
Requires PHP:		5.2.4

Text Domain: 		maintenance-coming-soon-redirect-animation

License:			GPLv3
License URI:		https://www.gnu.org/licenses/gpl-3.0.html

Author:      		Yassine Idrissi 
Author URI:          https://profiles.wordpress.org/yasinedr/

Copyright:			2022 Yassine Idrissi	(email: ydrissi9@gmail.com)
				based on Jack Finch Original: 2010-2012  
   			

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License, version 3, as
	published by the Free Software Foundation.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

    

*/

// Exit if accessed directly

defined( 'ABSPATH' ) || exit;


if( !class_exists("wploti_maintenance_redirect") ) {


	class wploti_maintenance_redirect {
		
		var $admin_options_name;
		var $maintenance_html;
		var $maintenance_head;
		
				
		/**
		 * (php) constructor
		 *
		 * @since 1.0.0
		 * @access public
		 * @return void
		 */
		function __construct() {
			$this->admin_options_name	= "wploti_mr";

			//set headers here , otherwrise we will get headers already sent warning

			if ( !is_admin() ) :

				if( get_option('wploti_header_type') === "200" ) {
					
					header('HTTP/1.1 200 OK');
					header('Status: 200 OK');
					
				}else{
					
					header('HTTP/1.1 503 Service Temporarily Unavailable');
					header('Status: 503 Service Temporarily Unavailable');
					
				}
				
				header('Retry-After: 600');

			endif;
		
		}

		/**
		 * (php) initialize
		 *
		 * @since 1.0.0
		 * @access public
		 * @return void
		 */
		
		function init() {
			global $wpdb;
			
			// create keys table if needed.
			$tbl = $wpdb->prefix . $this->admin_options_name . "_access_keys";
			if( $wpdb->get_var( "SHOW TABLES LIKE '". esc_sql($tbl) ."'" ) != esc_sql($tbl) ) {
				$sql = "create table $tbl ( id int auto_increment primary key, name varchar(100), access_key varchar(20), email varchar(100), created_at datetime not null default '0000-00-00 00:00:00', active int(1) not null default 1 )";
				$wpdb->query($sql);
			}
			
			// create IPs table if needed
			$tbl = $wpdb->prefix . $this->admin_options_name . "_unrestricted_ips";
    			if( $wpdb->get_var( "SHOW TABLES LIKE '". esc_sql($tbl) ."'" ) != esc_sql($tbl) ) {
				$sql = "create table $tbl ( id int auto_increment primary key, name varchar(100), ip_address varchar(20), created_at datetime not null default '0000-00-00 00:00:00', active int(1) not null default 1 )";
				$wpdb->query($sql);
				
			}

			// setup options
			add_option('wploti_maintenance_redirect_version', '1.1.1');
			add_option('wploti_animation', 'default-animation.json');
				
			update_option('wploti_activation_notice', 1);
			update_option('wploti_notes_notice', 1 );
			update_option('wploti_status', '0');
			update_option('wploti_header_type', '200');
			update_option('wploti_message', 'This site is currently undergoing maintenance. Please check back later');
		}

		/**
		 * (php) Get input message value
		 *
		 * @since 1.0.0
		 * @access public
		 * @return string
		 */


		function wploti_ajax_message() {

			check_ajax_referer('wploti_nonce', 'security');

			if ( isset($_POST['message']) ) {

				$wploti_message = sanitize_text_field($_POST['message']);

				update_option('wploti_message', $wploti_message);
	
			}

			wp_die();

		}

		/**
		 * (php) Update header type
		 *
		 * @since 1.0.0
		 * @access public
		 * @return string
		 */

		function wploti_ajax_header_type(){

			check_ajax_referer('wploti_nonce', 'security');

			if ( isset($_POST['header_type']) ) {

				$header_type = sanitize_text_field($_POST['header_type']);

				update_option('wploti_header_type', $header_type);

			}			

			wp_die();
			
		}

		/**
		 * Dismiss activation notice
		 *
		 * @since 1.1.1
		 * @access public
		 * @return void
		 */
		
		function wploti_ajax_dismiss_activation_notice() {

			check_ajax_referer( 'wploti_nonce', 'security' );

			// user has dismissed the welcome notice
			update_option( 'wploti_activation_notice', 0 );

			wp_die();
			
		}

		/**
		 * Dismiss notes notice
		 *
		 * @since 1.1.1
		 * @access public
		 * @return void
		 */
		
		function wploti_ajax_dismiss_notes_notice() {

			check_ajax_referer( 'wploti_nonce', 'security' );

			// user has dismissed the notes notice
			update_option( 'wploti_notes_notice', 0 );

			wp_die();
			
		}

		/**  (php)  display admin topbar notice
		* 
		* @since 1.1.1
		* @access public
		* @return void
		*/ 

		function wploti_admin_bar(){

			global $wp_admin_bar;
			$wploti_ajax_nonce = wp_create_nonce( "wploti_nonce" );
			$wploti_menu_image = '<div class="wploti_menu_image" style=" background-image: url(&quot;'. wploti_icon .'&quot;) !important;" aria-hidden="true"></div>';
			$topbar = $wploti_menu_image.'<div class="wploti_menu_text">'. __( "Maintenance Status" ) .' : </div><div class="toggle-wrapper"><div id="wploti-status-menubar" class="toggle-checkbox"></div><div id="wploti-toggle-adminbar" class="status-' . esc_attr( $this->wploti_active() ) . '" data-security="'. esc_attr($wploti_ajax_nonce) .'"><span class="toggle_handler"></span></div></div>';

	    	//Add the main siteadmin menu item
	        $wp_admin_bar->add_menu( array(
	            'id'     => 'wploti-activation-status',
				'title'  => $topbar,
	            'href'   => admin_url().'admin.php?page=wploti-settings',
	            'parent' => 'top-secondary',
	        ) );
		}

		/**  (php)  toggle activation for admin menu icon
		* 
		* @since 1.1.1
		* @access public
		* @return void
		*/ 

		function wploti_ajax_toggle_activation() {
			
			// check for ajax payoload
			if ( isset( $_POST['payload'] )  && $_POST['payload'] == 'toggle_wploti_status' ) {

				// verify nonce
				check_ajax_referer( 'wploti_nonce' ,'security');

				// verify user rights
				if ( ! current_user_can('manage_options') ) {
					wp_die("Oh no you don't!");
					return;
				}

				if ( $this->wploti_active() === '0' ) {
					update_option('wploti_status', '1');	
				} else {
					update_option('wploti_status', '0');
				}

				wp_die();
				
			}
		}
		
		
		/**
		 * (php) add top-level administrative menu
		 *
		 * @since 1.0.0
		 * @return void
		 */
	
		function wploti_maintenance_redirect_menu() {
	
			add_menu_page(
				'Maintenance redirect Settings',
				'Maintenance',
				'manage_options',
				'wploti-settings',
				[$this,'print_admin_page'],
				 wploti_icon,
				2
			);			
		} 

		/**
		 * Animation Select
		 *
		 * @since 1.0.1
		 * @access public
		 * @return void
		 */

		function animation_select(){

			$selected_animation =  basename(sanitize_text_field($_POST['animation_url']));

			update_option('wploti_animation', $selected_animation);

			wp_die();

		}

		/**
		 * (php) load animations by infinite scroll
		 *
		 * @since 1.0.0
		 * @access public
		 * @return void
		 */	

		function load_animations(){
			
			
			if (isset($_POST['start'], $_POST['limit'])){ 

				$animations = array_slice(scandir(__DIR__.'/animations'),2);

				$counter = sanitize_text_field($_POST['start']);
				$limit = sanitize_text_field($_POST['limit']) ;
					
				while ( $counter < $limit  )  :  ?>
					<div class="animation-grid">
						<div id="lottiecontainer">
						<?php if ( get_option("wploti_animation") == $animations[$counter] ) : ?>
							<div class="selected-bg">
								<span class="selected-btn wp-core-ui button-secondary"><?php _e('Selected') ?></span>
							</div>
						<?php endif; ?>										
							<div class="select-bg">
								<span class="select-btn wp-core-ui button-primary"><?php _e('Select') ?></span>
							</div>										
							<lottie-player autoplay="true" loop src="<?php echo wploti_animation_dir .$animations[esc_attr($counter)] ?>" class="lottieanimation"></lottie-player>
						</div>
					</div>
					<?php 

					$counter++;

				endwhile;
				
			 } ?>

				<script>
				jQuery(document).ready(function($) {

						$('.select-btn').click(function(){	

						animation = $(this).parent().next('lottie-player') ;

						animation_url = animation.get(0).src ;

						selected_bg = '<div class="selected-bg"><span class="selected-btn wp-core-ui button-secondary">Selected</span></div>';

						$('.selected-animation > lottie-player').remove();

						animation.clone().appendTo( ".selected-animation" );

						$('.selected-bg').remove();

						$(this).parents("#lottiecontainer").prepend(selected_bg);

						$('.selected-animation').find('lottie-player').attr('src', animation_url)

						$.ajax({
							url: ajaxurl,
							data: {
								action: 'wploti_animation_select',
								animation_url: animation_url,
							},
							type: 'post',

							success: function (result, textstatus) {
								$(".updated").fadeIn(1000).delay(7000).fadeOut("slow");
								/* console.log(result);
								console.log('sucess'); */							
							},
							error: function (result) {
								/* console.log(result);
								console.log('fail'); */
							},

						})

					})
				});				
				</script>

			<?php wp_die();
		}


		/**
		 * (php) deactivate action
		 *
		 * @since 1.0.0
		 * @access public
		 * @return void
		 */

		function wploti_deactivate(){
			
			update_option('wploti_activation_notice', 0);

		}

		/**
		 * (php) enqueue admin style and script
		 *
		 * @since 1.0.0
		 * @access public
		 * @return void
		 */

		function wploti_enqueue_style_and_script_admin() {
	
			$style_src = plugin_dir_url( __FILE__ ) .'css/admin-style.css';

			$admin_bar_style_src = plugin_dir_url( __FILE__ ) .'css/wploti-admin-bar.css';

			$loti_script_src = plugin_dir_url( __FILE__ ) .'js/lottie-player-script.js';

			$admin_script_src = plugin_dir_url( __FILE__ ) .'js/admin-script.js';

			wp_enqueue_style( 'wploti-admin-style', $style_src, array(), null, 'all' );

			wp_enqueue_style( 'admin_bar_style_src', $admin_bar_style_src, array(), null, 'all' );

			wp_enqueue_script( 'lottiplayer-script', $loti_script_src, array(), null, false );

			wp_enqueue_script( 'admin-script', $admin_script_src, array(), null, false );

		}

		/**
		 * (php) enqueue public style and script
		 *
		 * @since 1.0.0
		 * @access public
		 * @return void
		 */

		function wploti_enqueue_style_and_script_public() {

			$admin_bar_style_src = plugin_dir_url( __FILE__ ) .'css/wploti-admin-bar.css';
	
			wp_enqueue_style( 'admin_bar_style_src', $admin_bar_style_src, array(), null, 'all' );

		}

		/**
		 * (php) find user IP
		 *
		 * @since 1.0.0
		 * @access public
		 * @return string
		 */

		function get_user_ip(){
			$ip = ( isset( $_SERVER['HTTP_X_FORWARD_FOR'] ) ) ? sanitize_text_field($_SERVER['HTTP_X_FORWARD_FOR']) : sanitize_text_field($_SERVER['REMOTE_ADDR']);
			return $ip;
		}
		
		/**
		 * (php) determine user class c
		 *
		 * @since 1.0.0
		 * @access public
		 * @return string
		 */

		function get_user_class_c(){
			$ip = $this->get_user_ip();
			$ip_parts = explode( '.', $ip );
			$class_c = $ip_parts[0] . '.' . $ip_parts[1] . '.' .$ip_parts[2] . '.*';
			return $class_c;
		}


		/**
		 * Reset settings 
		 *
		 * @since 1.0.0
		 * @throws Exception
		 */
		public function reset_plugin_settings() {

			global $wpdb;

			try {
				// check capabilities
				if ( ! current_user_can('manage_options') ) {
					throw new Exception( __( 'You do not have access to this resource.', 'wploti-maintenance-mode' ) );
				}

				// check nonce
				check_ajax_referer( 'wploti_nonce', 'security' );

				// update options using the default values			
				update_option('wploti_animation', 'default-animation.json');
				update_option('wploti_header_type', '200');
				update_option('wploti_message', 'This site is currently undergoing maintenance. Please check back later');

				// delete ip_adresses & access keys
				$ip_adresses = $wpdb->prefix . $this->admin_options_name . '_unrestricted_ips';
				$access_keys = $wpdb->prefix . $this->admin_options_name . '_access_keys';
				$wpdb->query( " DELETE FROM `". esc_sql($ip_adresses) ."` " );
				$wpdb->query( " DELETE FROM `". esc_sql($access_keys) ."` " );
				
				wp_send_json_success();

			} catch ( Exception $ex ) {
				wp_send_json_error( $ex->getMessage() );
			}
		}

		/**
		 * (php) generate key
		 *
		 * @since 1.0.0
		 * @access public
		 * @return string
		 */					

		function alphastring( $len = 20, $valid_chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789' ){
			$str  = '';
			$chrs = explode( ' ', $valid_chars );
			for( $i=0; $i<$len; $i++ ){
				$str .= $valid_chars[ rand( 1, strlen( $valid_chars ) - 1 ) ];
			}
			return $str;
		}


		/**
		 * (php)  generate maintenance page
		 *
		 * @since 1.0.0
		 * @access public
		 * @return void
		 */

		function generate_maintenance_page(){

			$maintenance_head = '';
			$maintenance_head .= '
								<link href=" '. plugin_dir_url( __FILE__ ) ."css/front-style.css" .'" rel="stylesheet" type="text/css" />
								<script src="'. plugin_dir_url( __FILE__ ) ."js/lottie-player-script.js" .'"></script>
								<title>'. get_bloginfo( 'name' ) .'</title>
								<meta charset="utf-8">
								<meta http-equiv="X-UA-Compatible" content="IE=edge">
								<link rel="icon" type="image/x-icon" href="'.  plugin_dir_url( __FILE__ ).'/images/alert-icon.png' .'">
								<meta name="viewport" content="width=device-width, initial-scale=1">';
						
			echo wp_kses($maintenance_head ,
				array(
					'link' => array(
						'href' => array(),
						'type' => array(),
						'rel' => array(),
					),
					'meta' => array(
						'name' => array(),
						'content' => array(),
						'charset' => array(),
						'http-equiv' => array(),
					),
					'script' => array(
						'src' => array(),
					),
					'title' => array(),
					
				)
			);
			
			$maintenance_html ='';
			$maintenance_html .= '<!DOCTYPE html><html><body>';																	
			$maintenance_html .= '<lottie-player autoplay="true" loop src="'. wploti_animation_dir . esc_attr( get_option('wploti_animation', 'default-animation.json') ) .'"></lottie-player>';
			$maintenance_html .= '<span class="title">'. esc_html( get_option('wploti_message') ) .'</span>';
			$maintenance_html .= '</body></html>';
								  								
			
			echo wp_kses($maintenance_html , 	
				array(
					'span' => array(
						'class' => array(),					
					),
					'lottie-player' => array(
						'autoplay' => true,
						'loop' => true,
						'src' => array(),
					),
				)
			);

			exit();
		}
		
		/**
		 * (php)  #####  main function  ##### 
		 * 		#####  Redirection process  #####
		 * 		find out if we need to redirect or not
		 *
		 * @since 1.0.0
		 * @access public
		 * @return void
		 */			
		
		function process_redirect() {
			global $wpdb;
			$valid_ips      = array();
			$valid_class_cs = array();
			$valid_aks      = array();
			$wploti_matches  = apply_filters( 'wploti_matches', array() );
			
			// set cookie if needed
			if ( isset( $_GET['wploti_mr_temp_access_key'] ) && trim( $_GET['wploti_mr_temp_access_key'] ) != '' ) {
				// get valid access keys
				$sql = $wpdb->prepare("select access_key from " . $wpdb->prefix . $this->admin_options_name . "_access_keys where active = 1");			
				$aks = $wpdb->get_results($sql, OBJECT);
				if( $aks ){
					foreach( $aks as $ak ){
						$valid_aks[] = $ak->access_key;
					}
				}
				
				// set cookie if there's a match
				if( in_array( $_GET['wploti_mr_temp_access_key'], $valid_aks ) ){
					$wploti_mr_cookie_time = time()+(60*60*24*365);
					setcookie( 'wploti_mr_access_key', sanitize_text_field($_GET['wploti_mr_temp_access_key']), $wploti_mr_cookie_time, '/' );
					$_COOKIE['wploti_mr_access_key'] = sanitize_text_field($_GET['wploti_mr_temp_access_key']);
				}
			}
			
			
			// skip admin pages by default
			$url_parts = explode( '/', sanitize_text_field($_SERVER['REQUEST_URI']) );
			if( in_array( 'wp-admin', $url_parts ) ) {
				$wploti_matches[] = "<!-- wploti_MR: SKIPPING ADMIN -->";
			}else{
				// determine if user is admin.. if so, bypass all of this.
				if ( current_user_can( apply_filters( 'wploti_user_can', 'manage_options' ) ) ) {
					$wploti_matches[] = "<!-- wploti_MR: USER IS ADMIN -->";
				}else{
					if( $this->wploti_active() == '1' ) {
						// get valid unrestricted IPs

						// prepare warning
						$sql = "select ip_address from " . $wpdb->prefix . $this->admin_options_name . "_unrestricted_ips where active = 1";
						$ips = $wpdb->get_results($sql, OBJECT);
						if( $ips ){
							foreach( $ips as $ip ){
								$ip_parts = explode( '.', $ip->ip_address );
								if( $ip_parts[3] == '*' ){
									$valid_class_cs[] = $ip_parts[0] . '.' . $ip_parts[1] . '.' . $ip_parts[2];
								}else{
									$valid_ips[] = $ip->ip_address;
								}
							}
						}
						
						// get valid access keys
						$valid_aks = array();
						
						$sql = "select access_key from " . $wpdb->prefix . $this->admin_options_name . "_access_keys where active = 1";
						$aks = $wpdb->get_results($sql, OBJECT);
						if( $aks ){
							foreach( $aks as $ak ){
								$valid_aks[] = $ak->access_key;
							}
						}
						
						// manage cookie filtering
						if( isset( $_COOKIE['wploti_mr_access_key'] ) && $_COOKIE['wploti_mr_access_key'] != '' ){
							// check versus active codes
							if( in_array( $_COOKIE['wploti_mr_access_key'], $valid_aks ) ){
								$wploti_matches[] = "<!-- wploti_MR: COOKIE MATCH -->";
							}
						}
						
						// manage ip filtering 
						if( in_array( $this->get_user_ip(), $valid_ips ) ) {
							$wploti_matches[] = "<!-- wploti_MR: IP MATCH -->";
						}else{
							// check for partial ( class c ) match
							$ip_parts     = explode( '.', $this->get_user_ip() );
							$user_class_c = $ip_parts[0] . '.' . $ip_parts[1] . '.' . $ip_parts[2];
							if( in_array( $user_class_c, $valid_class_cs ) ) {
								$wploti_matches[] = "<!-- wploti_MR: CLASS C MATCH -->";
							}
						}
						
						if( count( $wploti_matches ) == 0 ) {
						
							// no match found. show maintenance page / message

							$this->generate_maintenance_page();
				
						}
					}else{
						$wploti_matches[] = "<!-- wploti_MR: REDIR DISABLED -->";
					}
				}
			}
		}
		
		/**
		 * (php)  add new IP
		 *
		 * @since 1.0.0
		 * @access public
		 * @return void
		 */			
		
		function add_new_ip() {
			if ( !current_user_can('manage_options') ) wp_die("Oh no you don't!");
			check_ajax_referer( 'wploti_nonce', 'security' );
			global $wpdb;
			$tbl       = $wpdb->prefix . $this->admin_options_name . '_unrestricted_ips';
			$name       = stripslashes( sanitize_text_field($_POST['wploti_mr_ip_name']) ) ;
			$ip_address = stripslashes( trim( sanitize_text_field($_POST['wploti_mr_ip_ip']) ) ) ;	
			$sql        = $wpdb->prepare("insert into ".esc_sql($tbl)." ( name, ip_address, created_at ) values ( '".esc_sql($name)."', '".esc_sql($ip_address)."', NOW() )");		
			$rs         = $wpdb->query( $sql );
			if( $rs ){
				// send table data
				$this->print_unrestricted_ips();
			}else{
				_e( 'Unable to add IP because of a database error. Please reload the page.' );
			}
			die();
		}

		/**
		 * (php)  toggle IP status
		 *
		 * @since 1.0.0
		 * @access public
		 * @return void
		 */
				
		function toggle_ip_status(){
			if ( !current_user_can('manage_options') ) wp_die("Oh no you don't!");
			check_ajax_referer( 'wploti_nonce', 'security' );
			global $wpdb;
			$tbl       = $wpdb->prefix . $this->admin_options_name . '_unrestricted_ips';
			$ip_id     = sanitize_text_field( $_POST['wploti_mr_ip_id'] );
			$ip_active = ( sanitize_text_field($_POST['wploti_mr_ip_active']) == 1 ) ? 1 : 0;
			$sql       = $wpdb->prepare("update ". esc_sql($tbl)." set active = '". esc_sql($ip_active)."' where id = '". esc_sql($ip_id) ."'");
			$rs        = $wpdb->query( $sql );
			if( $rs ){
				
				echo 'SUCCESS' . '|' . esc_html($ip_id) . '|' . esc_html($ip_active);
			}else{
				
				echo 'ERROR';
			}
			die();
		}
		
		/**
		 * (php)  delete IP
		 *
		 * @since 1.0.0
		 * @access public
		 * @return void
		 */
		
		function delete_ip(){
			if ( !current_user_can('manage_options') ) wp_die("Oh no you don't!");
			check_ajax_referer( 'wploti_nonce', 'security' );
			global $wpdb;
			$tbl       = $wpdb->prefix . $this->admin_options_name . '_unrestricted_ips';
			$ip_id     = sanitize_text_field( $_POST['wploti_mr_ip_id'] );
			$sql       = $wpdb->prepare("delete from ". esc_sql($tbl)." where id = '". esc_sql($ip_id) ."'");			
			$rs        = $wpdb->query( $sql );
			if( $rs ){
				$this->print_unrestricted_ips();
			}else{
				_e( 'Unable to delete IP because of a database error. Please reload the page.' );
			}
			die();
		}
		
		/**
		 * (php)  add new Access Key
		 *
		 * @since 1.0.0
		 * @access public
		 * @return void
		 */

		function add_new_ak() {
			if ( !current_user_can('manage_options') ) wp_die("Oh no you don't!");
			check_ajax_referer( 'wploti_nonce', 'security' );
			global $wpdb;
			$tbl        = $wpdb->prefix . $this->admin_options_name . '_access_keys';
			$name       = sanitize_text_field( stripslashes( $_POST['wploti_mr_ak_name'] ) );
			$email      = sanitize_email( $_POST['wploti_mr_ak_email'] );
			$access_key = sanitize_text_field( $this->alphastring(20) );
			$sql        = $wpdb->prepare("insert into ".esc_sql($tbl)." ( name, email, access_key, created_at ) values ( '".esc_sql($name)."', '".esc_sql($email)."', '".esc_sql($access_key)."', NOW() )");
			$rs         = $wpdb->query( $sql );
			if( $rs ){
				// email user
				$subject    = sprintf( /* translators: %s = name of the website/blog */ __( "Access Key Link for %s" ), get_bloginfo() );
				$full_msg   = sprintf( /* translators: %s = name of the website/blog */ __( "The following link will provide you temporary access to %s:" ), get_bloginfo() ) . "\n\n"; 
				$full_msg  .= __( "Please note that you must have cookies enabled for this to work." ) . "\n\n";
				$full_msg  .= get_bloginfo('url') . '?wploti_mr_temp_access_key=' . $access_key;
				$mail_sent  = wp_mail( $email, $subject, $full_msg );
				echo ( esc_html($mail_sent) ) ? '<!-- SEND_SUCCESS -->' : '<!-- SEND_FAILURE -->';
				// send table data
				$this->print_access_keys();
			}else{
				_e( "Unable to add Access Key because of a database error. Please reload the page." );
			}
			die();
		}
		
		/**
		 * (php)  toggle Access Key status
		 *
		 * @since 1.0.0
		 * @access public
		 * @return void
		 */
		
		function toggle_ak_status(){
			if ( !current_user_can('manage_options') ) wp_die("Oh no you don't!");
			check_ajax_referer( 'wploti_nonce', 'security' );
			global $wpdb;
			$tbl       = $wpdb->prefix . $this->admin_options_name . '_access_keys';
			$ak_id     = sanitize_text_field( $_POST['wploti_mr_ak_id'] );
			$ak_active = ( sanitize_text_field($_POST['wploti_mr_ak_active']) == 1 ) ? 1 : 0;
			$sql       = $wpdb->prepare("update ". esc_sql($tbl)." set active = '". esc_sql($ak_active) ."' where id = ' ". esc_sql($ak_id) ." '");
			$rs        = $wpdb->query( $sql );
			if( $rs ){
				
				echo 'SUCCESS' . '|' . esc_html($ak_id) . '|' . esc_html($ak_active);
			}else{
				
				echo 'ERROR';
			}
			die();
		}
		
		/**
		 * (php)  delete Access Key
		 *
		 * @since 1.0.0
		 * @access public
		 * @return void
		 */
		
		function delete_ak(){
			if ( !current_user_can('manage_options') ) wp_die("Oh no you don't!");
			check_ajax_referer( 'wploti_nonce', 'security' );
			global $wpdb;
			$tbl       = $wpdb->prefix . $this->admin_options_name . '_access_keys';
			$ak_id     = sanitize_text_field( $_POST['wploti_mr_ak_id'] );
			$sql       = $wpdb->prepare("delete from ". esc_sql($tbl) ." where id = '". esc_sql($ak_id) ."'");			
			$rs        = $wpdb->query( $sql );
			if( $rs ){
				$this->print_access_keys();
			}else{
				_e( 'Unable to delete Access Key because of a database error. Please reload the page.' );
			}
			die();
		}
		
		/**
		 * (php)  resend Access Key email
		 *
		 * @since 1.0.0
		 * @access public
		 * @return void
		 */
		
		function resend_ak(){
			if ( !current_user_can('manage_options') ) wp_die("Oh no you don't!");
			check_ajax_referer( 'wploti_nonce', 'security' );
			global $wpdb;
			$tbl       = $wpdb->prefix . $this->admin_options_name . '_access_keys';
			$ak_id     = sanitize_text_field( $_POST['wploti_mr_ak_id'] );
			$sql       = $wpdb->prepare("select * from ". esc_sql($tbl) ." where id = '". esc_sql($ak_id) ."'");
			$ak        = $wpdb->get_row( $sql );
			if( $ak ){
				$subject    = sprintf( /* translators: %s = name of the website/blog */ __( "Access Key Link for %s" ), get_bloginfo() );
				$full_msg   = sprintf( /* translators: %s = name of the website/blog */ __( "The following link will provide you temporary access to %s:" ), get_bloginfo() ) . "\n\n"; 
				$full_msg  .= __( "Please note that you must have cookies enabled for this to work." ) . "\n\n";
				$full_msg  .= get_bloginfo('url') . '?wploti_mr_temp_access_key=' . esc_html($ak->access_key);
				$mail_sent  = wp_mail( $ak->email, $subject, $full_msg );
				echo ( esc_html($mail_sent) ) ? 'SEND_SUCCESS' : 'SEND_FAILURE';
			}else{
				echo 'ERROR' ;
			}
			die();
		}
		
		/**
		 * (php)  generate IP table data
		 *
		 * @since 1.0.0
		 * @access public
		 * @return void
		 */
		 
		function print_unrestricted_ips(){
			global $wpdb; ?>

			<table class="widefat fixed" cellspacing="0">
				<thead>
					<tr>
						<th class="column-wploti-ip-name"><?php _e( "Name" ); ?></th>
						<th class="column-wploti-ip-ip"><?php _e( "IP" ); ?></th>
						<th class="column-wploti-ip-active"><?php _e( "Creation Date" ); ?></th>
						<th class="column-wploti-ip-creation-date"><?php _e( "Active" ); ?></th>
						<th class="column-wploti-actions"><?php _e( "Actions" ); ?></th>
					</tr>
				</thead>

				<tfoot>
					<tr>
						<th class="column-wploti-ip-name"><?php _e( "Name" ); ?></th>
						<th class="column-wploti-ip-ip"><?php _e( "IP" ); ?></th>
						<th class="column-wploti-ip-creation-date"><?php _e( "Creation Date" ); ?></th>
						<th class="column-wploti-ip-active"><?php _e( "Active" ); ?></th>						
						<th class="column-wploti-actions"><?php _e( "Actions" ); ?></th>
					</tr>
				</tfoot>

				<tbody>
					<?php
					
					$sql = "select * from " . $wpdb->prefix . $this->admin_options_name . "_unrestricted_ips order by name";
					$ips = $wpdb->get_results($sql, OBJECT);
					$ip_row_class = 'alternate';
					if( $ips ){
						foreach( $ips as $ip ) : 
							$ip_name = sanitize_text_field($ip->name);
							$ip_address = sanitize_text_field($ip->ip_address);
							$ip_creation_date = sanitize_text_field($ip->created_at);
							$ip_id = sanitize_text_field($ip->id);
						?>
							<tr id="wploti-ip-<?php echo esc_attr($ip_id); ?>" valign="middle"  class="<?php echo esc_attr($ip_row_class); ?>">
								<td class="column-wploti-ip-name"><?php echo esc_html($ip_name); ?></td>
								<td class="column-wploti-ip-ip"><?php echo esc_html($ip_address); ?></td>
								<td class="column-wploti-ip-creation-date"><?php echo esc_html($ip_creation_date); ?></td>
								<td class="column-wploti-ip-active" id="wploti_mr_ip_status_<?php echo esc_attr($ip_id); ?>" ><?php echo ( sanitize_text_field($ip->active) == 1) ? __('Yes') : __('No'); ?></td>
								<td class="column-wploti-actions">
									<span class='edit' id="wploti_mr_ip_status_<?php echo esc_attr($ip_id); ?>_action">
										<?php if( $ip->active == 1 ){ ?>
											<a href="javascript:wploti_mr_toggle_ip( 0, <?php echo esc_attr($ip_id) ?> );"><?php _e( "Disable" ); ?></a> | 
										<?php }else{ ?>
											<a href="javascript:wploti_mr_toggle_ip( 1, <?php echo esc_attr($ip_id) ?> );"><?php _e( "Enable" ); ?></a> | 
										<?php } ?>
									</span>
									<span class='delete'>
										<a class='submitdelete' href="javascript:wploti_mr_delete_ip( <?php echo esc_attr($ip_id) ?>, '<?php echo addslashes( esc_attr($ip_address) ) ?>' );" ><?php _e( "Delete" ); ?></a>
									</span>
								</td>
							</tr>
							<?php
							$ip_row_class = ( $ip_row_class == '' ) ? 'alternate' : '';
						endforeach;
					}
					?>
					
					<tr id="wploti-ip-NEW" valign="middle"  class="<?php echo esc_attr($ip_row_class); ?>">
						<td class="column-wploti-ip-name">
							<input class="wploti_mr_disabled_field" type="text" id="wploti_mr_new_ip_name" name="wploti_mr_new_ip_name" placeholder="<?php _e( "Enter Name:" ); ?>" onfocus="wploti_mr_undim_field('wploti_mr_new_ip_name','<?php _e( "Enter Name:" ); ?>') onblur="wploti_mr_dim_field('wploti_mr_new_ip_name','<?php _e( "Enter Name:" ); ?>');">
						</td>
						<td class="column-wploti-ip-ip">
							<input class="wploti_mr_disabled_field" type="text" id="wploti_mr_new_ip_ip" name="wploti_mr_new_ip_ip" placeholder="<?php _e( "Enter IP:" ); ?>" onfocus="wploti_mr_undim_field('wploti_mr_new_ip_ip','<?php _e( "Enter IP:" ); ?>');" onblur="wploti_mr_dim_field('wploti_mr_new_ip_ip','<?php _e( "Enter IP:" ); ?>');">
						</td>
						<td class="column-wploti-ip-active">&nbsp;</td>
						<td class="column-wploti-ip-creation-date">&nbsp;</td>
						<td class="column-wploti-actions">
							<span class='edit' id="wploti_mr_add_ip_link">
								<a href="javascript:wploti_mr_add_new_ip( );"><?php _e( "Add New IP" ); ?></a>
							</span>
						</td>
					</tr>					
				</tbody>
			</table>
			<?php
		}
		
		/**
		 * (php)  genereate Access Key table data
		 *
		 * @since 1.0.0
		 * @access public
		 * @return void
		 */		
		
		function print_access_keys(){
			global $wpdb; ?>
			<table class="widefat fixed" cellspacing="0">
				<thead>
					<tr>
						<th class="column-wploti-ak-name"><?php _e( "Name" ); ?></th>
						<th class="column-wploti-ak-email"><?php _e( "Email" ); ?></th>
						<th class="column-wploti-ak-key"><?php _e( "Access Key" ); ?></th>
						<th class="column-wploti-ak-key-creation-date"><?php _e( "Creation Date" ); ?></th>
						<th class="column-wploti-ak-active"><?php _e( "Active" ); ?></th>
						<th class="column-wploti-actions"><?php _e( "Actions" ); ?></th>
					</tr>
				</thead>

				<tfoot>
					<tr>
						<th class="column-wploti-ak-name"><?php _e( "Name" ); ?></th>
						<th class="column-wploti-ak-email"><?php _e( "Email" ); ?></th>
						<th class="column-wploti-ak-key"><?php _e( "Access Key" ); ?></th>
						<th class="column-wploti-ak-key-creation-date"><?php _e( "Creation Date" ); ?></th>
						<th class="column-wploti-ak-active"><?php _e( "Active" ); ?></th>
						<th class="column-wploti-actions"><?php _e( "Actions" ); ?></th>
					</tr>
				</tfoot>
				
				<tbody>
					<?php
					
					$sql   = "select * from " . $wpdb->prefix . $this->admin_options_name . "_access_keys order by name";
					$codes = $wpdb->get_results($sql, OBJECT);
					$ak_row_class = 'alternate';
					if( $codes ){
						foreach( $codes as $code ) : 
							$ak_name = sanitize_text_field($code->name);
							$ak_email = sanitize_email($code->email);
							$ak_key = sanitize_text_field($code->access_key);
							$ak_creation_date = sanitize_text_field($code->created_at);
							$ak_code = sanitize_text_field($code->id);					
						?>
							<tr id="wploti-ak-<?php echo esc_attr($ak_code) ?>" valign="middle"  class="<?php echo esc_attr($ak_row_class) ?>">
								<td class="column-wploti-ak-name"><?php echo esc_html($ak_name); ?></td>
								<td class="column-wploti-ak-email"><a href="mailto:<?php echo esc_attr($ak_email) ?>" title="email : <?php echo esc_attr($ak_email) ?>"><?php echo esc_html($ak_email) ?></a></td>
								<td class="column-wploti-ak-key"><?php echo esc_html($ak_key); ?></td>
								<td class="column-wploti-ak-key-creation-date"><?php echo esc_html($ak_creation_date); ?></td>
								<td class="column-wploti-ak-active" id="wploti_mr_ak_status_<?php echo esc_attr($ak_code); ?>" ><?php echo ( $code->active == 1) ? 'Yes' : 'No'; ?></td>
								<td class="column-wploti-actions">
									<span class='edit' id="wploti_mr_ak_status_<?php echo esc_attr($ak_code); ?>_action">
										<?php if( $code->active == 1 ){ ?>
											<a href="javascript:wploti_mr_toggle_ak( 0, <?php echo esc_attr($ak_code); ?> );"><?php _e( "Disable" ); ?></a> | 
										<?php }else{ ?>
											<a href="javascript:wploti_mr_toggle_ak( 1, <?php echo esc_attr($ak_code); ?> );"><?php _e( "Enable" ); ?></a> | 
										<?php } ?>
									</span>
									<span class='resend'>
										<a class='submitdelete' href="javascript:wploti_mr_resend_ak( <?php echo esc_attr($ak_code) ?>, '<?php echo addslashes( esc_attr($ak_name) ) ?>', '<?php echo addslashes( esc_attr($ak_email) ) ?>' );" ><?php _e( "Resend Code" ); ?></a> | 
									</span>
									<span class='delete'>
										<a class='submitdelete' href="javascript:wploti_mr_delete_ak( <?php echo esc_attr($ak_code) ?>, '<?php echo addslashes( esc_attr($ak_name) ) ?>' );" ><?php _e( "Delete" ); ?></a>
									</span>
								</td>
							</tr>
							<?php
							$ak_row_class = ( $ak_row_class == '' ) ? 'alternate' : '';
						endforeach;
					}
					?>
					<tr id="wploti-ak-NEW" valign="middle"  class="<?php echo esc_attr($ak_row_class); ?>">
						<td class="column-wploti-ak-name">
							<input class="wploti_mr_disabled_field" type="text" id="wploti_mr_new_ak_name" name="wploti_mr_new_ak_name" placeholder="<?php _e( "Enter Name:" ); ?>" onfocus="wploti_mr_undim_field('wploti_mr_new_ak_name','<?php _e( "Enter Name:" ); ?>');" onblur="wploti_mr_dim_field('wploti_mr_new_ak_name','<?php _e( "Enter Name:" ); ?>');">
						</td>
						<td class="column-wploti-ak-email">
							<input class="wploti_mr_disabled_field" type="text" id="wploti_mr_new_ak_email" name="wploti_mr_new_ak_email" placeholder="<?php _e( "Enter Email:" ); ?>" onfocus="wploti_mr_undim_field('wploti_mr_new_ak_email','<?php _e( "Enter Email:" ); ?>');" onblur="wploti_mr_dim_field('wploti_mr_new_ak_email','<?php _e( "Enter Email:" ); ?>');">
						</td>
						<td class="column-wploti-ak-key">&nbsp;</td>
						<td class="column-wploti-ak-active">&nbsp;</td>
						<td class="column-wploti-ak-key-creation-date">&nbsp;</td>
						<td class="column-wploti-actions">
							<span class='edit' id="wploti_mr_add_ak_link">
								<a href="javascript:wploti_mr_add_new_ak( );"><?php _e( "Add New Access Key" ); ?></a>
							</span>
						</td>
					</tr>					
				</tbody>
			</table>
			<?php
		}
		
		/**
		 * (php)  display activation notice
		 *
		 * @since 1.0.0
		 * @access public
		 * @return void
		 */	
		
		function display_status_if_active(){
			global $wpdb , $pagenow , $wploti_ajax_nonce ;				

			if ( get_option( 'wploti_activation_notice' ) ) {
	
					// load the notices view
					$current_screen = get_current_screen();
					$settingslink = ( $current_screen->id != "settings_page_wploti-settings" ) ? ' <a href="options-general.php?page=wploti-settings">'.__( 'Settings' ).'</a>' : '';
					$welcomemsg = '';
					$welcomemsg .='<div class="notice notice-success is-dismissable" id="wploti_mr_enabled_notice">';
					$welcomemsg .='<img class="main-logo" src="'. plugin_dir_url( __FILE__ ).'\images\main-logo.png">';
					$welcomemsg .='<div class="notice-activation-text-wrapper">';
					$welcomemsg .='<p><h3 class="main_redirect_msg">Thank you for installing Maintenance & Coming Soon Redirect Animation Plugin!</h3>';
					$welcomemsg .='<span>You can activate the Maintenance Mode in '. $settingslink .' or by Maintenance Status Top bar icon and choose your animation !</span>';
					$welcomemsg .='<div class="wploti-leave-feedback">';
					$welcomemsg .='<div><a href="#dismiss" data-security="'. esc_attr($wploti_ajax_nonce) .'" name="wploti-activation-dismiss" class="wploti-activation-dismiss">Dismiss</a></div>';
					$welcomemsg .='</div>';
					$welcomemsg .='</div>';
					$welcomemsg .='</p></div>';
					
					echo wp_kses_post ($welcomemsg);
	
			}
			return;

		}
		
		/**
		 * (php)  add settings link to plugin page
		 *
		 * @since 1.0.0
		 * @access public
		 * @return void
		 */	
		
		function plugin_settings_link($links) { 
			$settings_link = '<a href="options-general.php?page=wploti-settings">Settings</a>'; 
			array_unshift($links, $settings_link); 
			return $links; 
		}
		
		/**
		 * (php)  Add site health test
		 *
		 * @since 1.0.0
		 * @access public
		 * @return void
		 */		
		
		function wploti_add_site_health( $tests ) {
			$tests['direct']['wploti_status'] = array(
				'label' => __( 'Maintenance' ),
				'test'  => array( $this, 'wploti_site_health' ),
			);
			return $tests;
		}


		/**
		 * (php)  verify site health status
		 *
		 * @since 1.0.0
		 * @access public
		 * @return array
		 */			

		function wploti_site_health() {			
			
			$result = array(
				'label'       => __( 'Maintenance Redirect is not enabled' ),
				'status'      => 'good',
				'badge'       => array(
					'label' => __( 'Visibility' ),
					'color' => 'blue',
				),
				'description' => sprintf(
					'<p>%s</p>',
					__( 'Maintenance is not enabled and your site is visible to visitors.' )
				),
				'actions'     => sprintf(
					'<p><a href="options-general.php?page=wploti-settings">%s</a></p>',
					__( 'Settings' )
				),
				'test'        => 'wploti_status',
			);

			if ( $this->wploti_active() == '1' ) {
			
				if ( get_option('wploti_header_type') == "200" ) {
					$result['status'] = 'recommended';
					$result['label'] = __( 'Maintenance Redirect is enabled' );
					$result['description'] = sprintf(
						'<p>%s</p>',
						__( 'Maintenance is enabled and your site is not visible to visitors.' )
					);
				} else {
					$result['status'] = 'critical';
					$result['badge']['color'] = 'red';
					$result['label'] = __( 'Maintenance is enabled' );
					$result['description'] = sprintf(
						'<p>%s</p>',
						__( 'Maintenance is enabled and your site is not visible to visitors. Your redirection type is set to 503, which could harm your Google ranking if left on for any length of time.' )
					);
				}
				
			}
			return $result;
		}

		/**
		 * (php)  Return active status 0 or 1
		 * 
		 * Default is 0 after plugin installation 
		 * @since 1.1.1
		 * @return string
		 */

		public function wploti_active() {		
			return get_option('wploti_status', '0') === '0' ? '0': '1';
		}


		/**
		 * (php)  create the admin page
		 *
		 * @since 1.0.0
		 * @access public
		 * @return array
		 */
		
		function print_admin_page() {
			global $wpdb;
			global $wploti_ajax_nonce;

			// display update notice 
			echo '<div class="updated" style="display: none" ><p><strong>Settings Saved</strong></p></div>';

			 ?>

			<!-- **************  JS  ************** -->
			
			<script type="text/javascript" charset="utf-8">
				
				/**
				 * (js) custom alert
				 *
				 * @since 1.0.0
				 * @param string msg1
				 * @param string msg2
				 * @return string
				 */	
				
				function wploti_alert(msg1, msg2) {
					var alertContent = `
					<div class="modal">
						<div class="modal-content">
							<div class="modal-header">				
								<img class="alert-icon" src="data:image/jpeg;base64,<?php echo esc_attr(base64_encode(file_get_contents( plugin_dir_url( __FILE__ ).'/images/alert-icon.png' ))); ?>" alt="Alert Icon" />
							</div>
							<div class="messages">
								<div>${msg1}</div>
								<div>${msg2}</div>							
							</div>
							<div class="modal-footer">
								<button value="Reset settings" class="button button-primary ok_wploti_alert" name="ok_wploti_alert">OK</button>
							</div>
						</div>
					</div>`
					var modal = document.createElement("div");
					modal.innerHTML = alertContent;
					document.body.appendChild(modal); 

					jQuery('.ok_wploti_alert').click( function(){
						jQuery('.modal').fadeOut('1000');
					})
				}

				/**
				 * (js) custom confirm
				 *
				 * @since 1.0.0
				 * @param {object options} The properties to create the element with
				 * @return object
				 */	

				const wploti_confirm = {
					open (options) {
						options = Object.assign({}, {
							message: '',
							okText: 'OK',
							cancelText: 'Cancel',
							onok: function () {},
							oncancel: function () {}
						}, options);
					
			
						var confirmContent = `
						<div class="modal">
							<div class="modal-content">
								<div class="modal-header">
									<img class="alert-icon" src="data:image/jpeg;base64,<?php echo esc_attr(base64_encode(file_get_contents( plugin_dir_url( __FILE__ ).'/images/alert-icon.png' ))) ?>" alt="Alert Icon" />
								</div>
								<div class="messages">
									<div>${options.message}</div>
								</div>
								<div class="modal-footer">
									<button value="OK" class="button button-primary ok_wploti_confirm" name="ok_wploti_alert">${options.okText}</button>
									<button value="Cancel" class="button button-secondary cancel_wploti_confirm" name="cancel_wploti_alert">${options.cancelText}</button>
								</div>
							</div>
						</div>`
						var modal = document.createElement("div");
						modal.innerHTML = confirmContent;
						document.body.appendChild(modal); 

						jQuery('.cancel_wploti_confirm').click( function(){
							options.oncancel();
							jQuery('.modal').fadeOut('1000');
						})

						jQuery('.ok_wploti_confirm').click( function(){
							options.onok();
							jQuery('.modal').fadeOut('1000');
						})
					}
				}
				
				/**
				 * (js) undim field
				 *
				 * @since 1.0.0
				 * @param string field_id
				 * @param string default_text
				 * @return string
				 */	
				
				function wploti_mr_undim_field( field_id, default_text ) {
					if( jQuery('#'+field_id).val() == default_text ) jQuery('#'+field_id).val('');
					jQuery('#'+field_id).css('color','#000');
				}

				/**
				 * (js) dim field
				 *
				 * @since 1.0.0
				 * @param string field_id
				 * @param string default_text
				 * @return string
				 */
				
				function wploti_mr_dim_field( field_id, default_text ) {
					if( jQuery('#'+field_id).val() == '' ) {
						jQuery('#'+field_id).val(default_text);
						jQuery('#'+field_id).css('color','#888');
					}
				}
				
				/**
				 * (js) validate IP4 address
				 *
				 * @since 1.0.0
				 * @param string ipaddress
				 * @return boolean
				 */	

				function ValidateIPaddress(ipaddress) {  
					if (/^(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.((25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.((25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)|\*))$/.test(ipaddress)) {  
						return true 
					}  
				}

				/**
				 * (js) add new IP
				 *
				 * @since 1.0.0
				 * @return void
				 */	
				
				function wploti_mr_add_new_ip () {
					// validate entries before posting ajax call
					var error_msg = '';
					if( jQuery('#wploti_mr_new_ip_name').val() == ''                              ) error_msg += '<?php _e( "You must enter a Name" ); ?>.\n<br>';
					if( jQuery('#wploti_mr_new_ip_name').val() == '<?php _e( "Enter Name:" ); ?>' ) error_msg += '<?php _e( "You must enter a Name" ); ?>.\n<br>';
					if( jQuery('#wploti_mr_new_ip_ip'  ).val() == ''                              ) error_msg += '<?php _e( "You must enter an IP" ); ?>.\n<br>';
					if( jQuery('#wploti_mr_new_ip_ip'  ).val() == '<?php _e( "Enter IP:" ); ?>'   ) error_msg += '<?php _e( "You must enter an IP" ); ?>.\n<br>';
					if( ValidateIPaddress( jQuery('#wploti_mr_new_ip_ip'  ).val() ) != true   ) error_msg += '<?php _e( "IP address not valid" ); ?>.\n<br>';
					if( error_msg != '' ){							
						wploti_alert('<?php _e( "There is a problem with the information you have entered" ); ?>.\n\n' , error_msg )

					}else{
						// prepare ajax data
						var data = {
							action:		'wploti_mr_add_ip',
							security:		'<?php echo $wploti_ajax_nonce; ?>',
							wploti_mr_ip_name:	jQuery('#wploti_mr_new_ip_name').val(),
							wploti_mr_ip_ip:	jQuery('#wploti_mr_new_ip_ip').val() 
						};
						
						// set section to loading img
						var img_url = '<?php echo plugins_url( 'images/ajax_loader_16x16.gif', __FILE__ ); ?>';
						jQuery( '#wploti_mr_ip_tbl_container' ).html('<img src="' + img_url + '">');
						
						// send ajax request
						jQuery.post( ajaxurl, data, function(response) {
							jQuery('#wploti_mr_ip_tbl_container').html( response );
						});
					}
				}
				
				/**
				 * (js) toggle IP status
				 *
				 * @since 1.0.0
				 * @param status boolean
				 * @param ip_id number
				 * @return void
				 */
				
				function wploti_mr_toggle_ip ( status, ip_id ) {
					// prepare ajax data
					var data = {
						action:             	'wploti_mr_toggle_ip',
						security:			'<?php echo $wploti_ajax_nonce; ?>',
						wploti_mr_ip_active: 	status,
						wploti_mr_ip_id:     	ip_id 
					};
					
					// (js) set status to loading img
					var img_url = '<?php echo plugins_url( 'images/ajax_loader_16x16.gif', __FILE__ ); ?>';
					jQuery( '#wploti_mr_ip_status_' + ip_id ).html('<img src="' + img_url + '">');
					
					// send ajax request
					jQuery.post( ajaxurl, data, function(response) {
						var split_response = response.split('|');
						if( split_response[0] == 'SUCCESS' ){
							var ip_id     = split_response[1];
							var ip_active = split_response[1];
							// update divs / 1 = id / 2 = status
							if( split_response[2] == '1' ){
								// active
								jQuery('#wploti_mr_ip_status_' + split_response[1] ).html( 'Yes' );
								jQuery('#wploti_mr_ip_status_' + split_response[1] + '_action' ).html( '<a href="javascript:wploti_mr_toggle_ip( 0, ' + split_response[1] + ' );"><?php _e( "Disable" ); ?></a> | ' );
							}else{
								// disabled
								jQuery('#wploti_mr_ip_status_' + split_response[1] ).html( 'No' );
								jQuery('#wploti_mr_ip_status_' + split_response[1] + '_action' ).html( '<a href="javascript:wploti_mr_toggle_ip( 1, ' + split_response[1] + ' );"><?php _e( "Enable" ); ?></a> | ' );
							} 
						}else{
							wploti_alert( '<?php _e( "There was a database error. Please reload this page" ); ?>' );
						}
					});
				}
				
				/**
				 * (js) delete IP
				 *
				 * @since 1.0.0
				 * @param ip_addr string
				 * @param ip_id number
				 * @return void
				 */
				
				function wploti_mr_delete_ip ( ip_id, ip_addr ) {

					wploti_confirm.open({

						message: '<?php _e( "You are about to delete the IP address:"); ?>\n\n\'' + ip_addr + '\'\n\n',
						onok: () => {
								// prepare ajax data
								var data = {
									action:		'wploti_mr_delete_ip',
									security:		'<?php echo $wploti_ajax_nonce; ?>',
									wploti_mr_ip_id:   ip_id
								};
								
								// set section to loading img
								var img_url = '<?php echo plugins_url( 'images/ajax_loader_16x16.gif', __FILE__ ); ?>';
								jQuery( '#wploti_mr_ip_tbl_container' ).html('<img src="' + img_url + '">');
								
								// send ajax request
								jQuery.post( ajaxurl, data, function(response) {
									jQuery('#wploti_mr_ip_tbl_container').html( response );
								});
							}
						})
				}

				/**
				 * (js) add new Access Key
				 *
				 * @since 1.0.0
				 * @param ak_email string
				 * @return void
				 */
				
				function wploti_mr_add_new_ak (ak_email) {
					// validate entries before posting ajax call
					var error_msg = '';
					var ak_email = jQuery('#wploti_mr_new_ak_email').val()
					if( jQuery('#wploti_mr_new_ak_name' ).val() == ''                               ) error_msg += '<?php _e( "You must enter a Name"); ?>.<br>\n';
					if( jQuery('#wploti_mr_new_ak_name' ).val() == '<?php _e( "Enter Name:" ); ?>'  ) error_msg += '<?php _e( "You must enter a Name"); ?>.<br>\n';
					if( jQuery('#wploti_mr_new_ak_email').val() == ''                               ) error_msg += '<?php _e( "You must enter an Email"); ?>.<br>\n';
					if( jQuery('#wploti_mr_new_ak_email').val() == '<?php _e( "Enter Email:" ); ?>' ) error_msg += '<?php _e( "You must enter an Email"); ?>.<br>\n';
					if( error_msg != '' ){
						wploti_alert( '<?php _e( "There is a problem with the information you have entered"); ?>.\n\n' , error_msg );
						
					}else{

						wploti_confirm.open({

							message : '<?php _e( "You are about to email an Access Key link to "); ?><b>' + ak_email + '</b> !<br> <?php _e( "If you do not see the email in a few secondes,") ?> <br> <?php _e("Please check your “junk mail” or “spam” folder.") ?> \n\n',
							onok: () => {
								// prepare ajax data
								var data = {
									action:		'wploti_mr_add_ak',
									security:		'<?php echo $wploti_ajax_nonce; ?>',
									wploti_mr_ak_name:  jQuery('#wploti_mr_new_ak_name').val(),
									wploti_mr_ak_email: jQuery('#wploti_mr_new_ak_email').val() 
								};

								// set section to loading img
								var img_url = '<?php echo plugins_url( 'images/ajax_loader_16x16.gif', __FILE__ ); ?>';
								jQuery( '#wploti_mr_ak_tbl_container' ).html('<img src="' + img_url + '">');

								// send ajax request
								jQuery.post( ajaxurl, data, function(response) {
									jQuery('#wploti_mr_ak_tbl_container').html( response );
								});
							}
						})

					}
				}
				/**
				 * (js) toggle Access Key status ( Enable || disable  Access Key )
				 *
				 * @since 1.0.0
				 * @param status string
				 * @param ak_id number
				 * @return void
				 */
				
				function wploti_mr_toggle_ak ( status, ak_id ) {
					// prepare ajax data
					var data = {
						action:			'wploti_mr_toggle_ak',
						security:			'<?php echo $wploti_ajax_nonce; ?>',
						wploti_mr_ak_active: 	status,
						wploti_mr_ak_id:     	ak_id 
					};

					// set status to loading img
					var img_url = '<?php echo plugins_url( 'images/ajax_loader_16x16.gif', __FILE__ ); ?>';
					jQuery( '#wploti_mr_ak_status_' + ak_id ).html('<img src="' + img_url + '">');

					// send ajax request
					jQuery.post( ajaxurl, data, function(response) {
						var split_response = response.split('|');
						if( split_response[0] == 'SUCCESS' ){
							var ak_id     = split_response[1];
							var ak_active = split_response[1];
							// update divs / 1 = id / 2 = status
							if( split_response[2] == '1' ){
								// active
								jQuery('#wploti_mr_ak_status_' + split_response[1] ).html( 'Yes' );
								jQuery('#wploti_mr_ak_status_' + split_response[1] + '_action' ).html( '<a href="javascript:wploti_mr_toggle_ak( 0, ' + split_response[1] + ' );"><?php _e( "Disable" ); ?></a> | ' );
							}else{
								// disabled
								jQuery('#wploti_mr_ak_status_' + split_response[1] ).html( 'No' );
								jQuery('#wploti_mr_ak_status_' + split_response[1] + '_action' ).html( '<a href="javascript:wploti_mr_toggle_ak( 1, ' + split_response[1] + ' );"><?php _e( "Enable" ); ?></a> | ' );
							} 
						}else{
							wploti_alert( '<?php _e( "There was a database error. Please reload this page" ); ?>' , ' ' );
						}
					});
				}

				/**
				 * (js) delete Access Key
				 *
				 * @since 1.0.0
				 * @param ak_id number
				 * @param ak_name string
				 * @return void
				 */
				
				function wploti_mr_delete_ak ( ak_id, ak_name ) {

					wploti_confirm.open({

						message: '<?php _e( "You are about to delete this Access Key:"); ?>\n\n\'' + ak_name + '\'\n\n',
						onok: () => {
								// prepare ajax data
								var data = {
									action:		'wploti_mr_delete_ak',
									security:		'<?php echo $wploti_ajax_nonce; ?>',
									wploti_mr_ak_id:	ak_id
								};

								// set section to loading img
								var img_url = '<?php echo plugins_url( 'images/ajax_loader_16x16.gif', __FILE__ ); ?>';
								jQuery( '#wploti_mr_ak_tbl_container' ).html('<img src="' + img_url + '">');

								// send ajax request
								jQuery.post( ajaxurl, data, function(response) {
									jQuery('#wploti_mr_ak_tbl_container').html( response );
								});
							}
						})
				}
				
				
				/**
				 * (js) re-send Access Key
				 *
				 * @since 1.0.0
				 * @param ak_id number
				 * @param ak_name string
				 * @param ak_email string
				 * @return void
				 */
				
				function wploti_mr_resend_ak ( ak_id, ak_name, ak_email ) {
					
					wploti_confirm.open({
						message : '<?php _e( "You are about to resend an Access Key link to "); ?><b>' + ak_email + '</b> !<br> <?php _e( "If you do not see the email in a few secondes,") ?> <br> <?php _e("Please check your “junk mail” or “spam” folder.") ?> \n\n',
						onok: () => {
							// prepare ajax data
							var data = {
								action:		'wploti_mr_resend_ak',
								security:		'<?php echo $wploti_ajax_nonce; ?>',
								wploti_mr_ak_id:	ak_id
							};
							
							// send ajax request
							jQuery.post( ajaxurl, data, function(response) {
								if( response == 'SEND_SUCCESS' ){
									wploti_alert( '<?php _e( "Notification Sent." ); ?>','' );
								}else{
									wploti_alert( '<?php _e( "Notification Failure. Please check your server settings." ); ?>','' );
								}
							});
						}
					})
					
				}
			
			</script>
			
			<!-- **************  JS  ************** -->

			<div class="wrap">
				
				<h1 class="big-title">Maintenance Redirect</h1>
				
				<p><?php _e( "Make your website in maintenance mode in seconds with great looking animations and configure settings to allow specific users to bypass the maintenance mode functionality in order to preview the site prior to public launch. Any logged in user with WordPress administrator privileges will be allowed to view the site regardless of the settings below." ); ?></p>
				<?php if ( get_option('wploti_notes_notice') ) : ?>
				<div class=" notice-success is-dismissable" id="wploti_note_notice">
					<div class="notice-activation-text-wrapper">
						<div class="note_head">
							<img class="alert-icon" src="data:image/jpeg;base64,<?php echo esc_attr(base64_encode(file_get_contents( plugin_dir_url( __FILE__ ).'/images/alert-icon.png' ))) ?>" alt="Alert Icon" />
							<h3 class="main_redirect_msg"><?php _e( "Notes : " )?></h3>
						</div>
						<ul class="note_text">
							<li><?php _e( "This plugin will override any other maintenance plugin you use ." ); ?></li>
							<li><?php _e( "All settings are auto-updated , you don't need to save anything ." ); ?></li>
						</ul>
						<div class="wploti-leave-feedback">
							<div><a href="#dismiss" data-security="<?php echo esc_attr($wploti_ajax_nonce) ?>" name="wploti-activation-dismiss" class="wploti-note-dismiss">Dismiss</a></div>
						</div>
					</div>
				</div>
				<?php endif; ?>						
				<h3 class="big-title"><?php _e( "Enable Maintenance Mode:" ); ?></h3>
				<div class="enable-maintenance-mode">
					<div class="wploti-maintenance-toggle">
						<div class="toggle-wrapper">
							<input type="checkbox" data-security="<?php echo esc_attr($wploti_ajax_nonce) ?>" name="wploti_status" id="wploti-status" class="toggle-checkbox" <?php checked( '1', $this->wploti_active() );  ?>>
							<label for="wploti-status" class="toggle"><span class="toggle_handler"></span></label> 
						</div>
					</div>
				</div>
	
				<div id="wploti_main_options" style="display: <?php echo ( $this->wploti_active() == '1' ) ? 'block' : 'none'; ?> " >
					
					<div class="wploti_mr_admin_section" >
						<h3 class="big-title"><?php _e( "Header Type:" ); ?></h3>
						<p><?php _e( "When redirect is enabled we can send 2 different header types:" ); ?> </p>
						
						<dl>
							<dt>
								<input type="radio" id="200" name="wploti_header_type" class="wploti_header_type" data-security="<?php echo esc_attr($wploti_ajax_nonce) ; ?>" <?php checked( get_option('wploti_header_type') , "200" ) ?> value="200">
								<label for="200">200 OK</label><br>
							</dt>
							<dd><?php _e( "Best used for when the site is under development." ); ?></dd>
							<dt>
								<input type="radio" id="503" name="wploti_header_type" class="wploti_header_type" data-security="<?php echo esc_attr($wploti_ajax_nonce) ; ?>" <?php checked( get_option('wploti_header_type') , "503" ) ?> value="503">
								<label for="503">503 Service Temporarily Unavailable</label><br>
							</dt>
							<dd><?php _e( "Best for when the site is temporarily taken offline for small amendments." ); ?> <em><?php _e( "If used for a long period of time, 503 can damage your Google ranking." ); ?></em></dd>
						</dl>
					</div>
					<div class="wploti_mr_admin_section" >
						<h3 class="big-title"><?php _e( "Unrestricted IP addresses:" ); ?>&nbsp;<span class="wploti_mr_small_dim">( <?php _e( "Your IP address is:" ); ?>&nbsp;<?php echo $this->get_user_ip(); ?> - <?php _e( "Your Class C is:" ); ?>&nbsp;<?php echo $this->get_user_class_c(); ?> )</span></h3>
						<p><?php _e( "Users with unrestricted IP addresses will bypass maintenance mode entirely. Using this option is useful to an entire office of clients to view the site without needing to jump through any extra hoops." ); ?></p> 
						
						<div id="wploti_mr_ip_tbl_container">
							<?php $this->print_unrestricted_ips(); ?>
						</div>
					</div>
					
					<div class="wploti_mr_admin_section">
						<h3 class="big-title"><?php _e( "Access Keys:" ); ?></h3>
						<p><?php _e( "You can allow users temporary access by sending them the access key. When a new key is created, a link to create the access key cookie will be emailed to the email address provided. Access can then be revoked either by disabling or deleting the key." ); ?></p>
						
						<div id="wploti_mr_ak_tbl_container">
							<?php $this->print_access_keys(); ?>
						</div>
					</div>
					
					<div class="wploti_mr_admin_section">	
						<h3 class="big-title"><?php _e( "Maintenance Animation :" ); ?></h3>
						
						<h4 class="small-title"><?php _e( "Active Animation :" ); ?></h4>

						<div class="selected-animation">										
							<lottie-player autoplay="true" loop src="<?php echo wploti_animation_dir . esc_attr( get_option("wploti_animation", 'default-animation.json') ) ?>" class="lottieanimation"></lottie-player>
						</div>

						<h4 class="small-title"><?php _e( "Here you can select your Animation :" ); ?></h4>
						<?php $animations = array_slice(scandir(__DIR__.'/animations'),2); ?>
						<div animations-count="<?php echo esc_attr(count($animations)); ?>" class="animations"></div>
		
						<div id ="load-animations-message"></div>
					
						<div id="wploti_bottom_message">
							<strong><?php _e( "Maintenance Mode Message (optional) :" ); ?></strong>
							<p><?php _e( "You can write a brief message that will be displayed under animation :" ); ?></p>
							<p><input type="text" data-security="<?php echo esc_attr($wploti_ajax_nonce) ; ?>" name="wploti_message" value="<?php echo esc_attr( get_option('wploti_message') ); ?>" id="wploti_message" style="width:100%"></p>
						</div>
					</div>

					<div class="submit">
						<?php wp_nonce_field( 'wploti_nonce' ); ?>
						<!-- <input type="submit" name="update_wp_maintenance_redirect_settings" class="wp-core-ui button-primary" value="<?php _e( 'Update Settings' ); ?>" /> -->
						<input type="button" value="<?php esc_attr_e( 'Reset settings', 'wploti-maintenance-mode' ); ?>" class="button button-secondary wploti_reset_settings" data-security="<?php echo esc_attr($wploti_ajax_nonce) ; ?>" name="submit" />
					</div>

				</div>
					
			</div>
				
			<?php
	
			
		} // end function print_admin_page()


	} // end class wploti_maintenance_redirect
}

if (class_exists("wploti_maintenance_redirect")) {
	$my_wploti_maintenance_redirect = new wploti_maintenance_redirect();
}


if (!function_exists("wploti_maintenance_redirect_ap")) {

	/**
	 * (php) initialize the admin and users panel
	 *
	 * @since 1.0.0
	 * @return void
	 */

	function wploti_maintenance_redirect_ap() {
		if( current_user_can('manage_options') ) {
			global $my_wploti_maintenance_redirect;
			global $wploti_ajax_nonce; 
				 $wploti_ajax_nonce = wp_create_nonce( "wploti_nonce" ); 
			
			if( !isset($my_wploti_maintenance_redirect) ) return;

			
			if (function_exists('add_options_page')) {
				add_options_page( 
					__("Maintenance Redirect Options" ),
					__("Maintenance" ), 
						'manage_options', 
						'wploti-settings', 
						array( $my_wploti_maintenance_redirect, 'print_admin_page' ));
			}
		}
	}
}



// actions and filters	

if( isset( $my_wploti_maintenance_redirect ) ) {
	//global constants

	define('wploti_icon', 'data:image/svg+xml;base64,PHN2ZyB2ZXJzaW9uPSIxLjEiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgeG1sbnM6eGxpbms9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkveGxpbmsiIHg9IjBweCIgeT0iMHB4IiB2aWV3Qm94PSIwIDAgMTAwMCAxMDAwIiBlbmFibGUtYmFja2dyb3VuZD0ibmV3IDAgMCAxMDAwIDEwMDAiIHhtbDpzcGFjZT0icHJlc2VydmUiPgo8Zz48cGF0aCBmaWxsID0iI2ZmZmYiIGQ9Ik05NTcuMiw0MjEuM0g4NTUuNGMtMTgsMC0zNy41LTE0LTQzLjItMzEuMWwtMTMuOS0zMi45Yy04LjMtMTYtNC42LTM5LjYsOC4yLTUyLjRsNzIuNC03Mi40YzEyLjgtMTIuOCwxMi44LTMzLjYsMC00Ni40bC02NS02NWMtMTIuOC0xMi44LTMzLjYtMTIuOC00Ni40LDBMNjk0LjcsMTk0Yy0xMi44LDEyLjgtMzYuNCwxNi42LTUyLjUsOC40TDYxMCwxODkuMmMtMTcuMi01LjYtMzEuMi0yNC45LTMxLjItNDIuOVY0Mi44YzAtMTgtMTQuOC0zMi44LTMyLjgtMzIuOGgtOTEuOWMtMTgsMC0zMi44LDE0LjgtMzIuOCwzMi44djEwMy40YzAsMTgtMTQsMzcuNC0zMS4yLDQyLjlsLTMyLjIsMTMuMmMtMTYuMSw4LjEtMzkuNyw0LjMtNTIuNS04LjRMMjMyLjQsMTIxYy0xMi44LTEyLjgtMzMuNi0xMi44LTQ2LjQsMGwtNjUsNjVjLTEyLjgsMTIuOC0xMi44LDMzLjYsMCw0Ni40bDcyLjQsNzIuNGMxMi44LDEyLjgsMTYuNCwzNi4zLDguMiw1Mi40bC0xMy45LDMyLjljLTUuNywxNy4xLTI1LjEsMzEuMS00My4yLDMxLjFINDIuOGMtMTgsMC0zMi44LDE0LjgtMzIuOCwzMi44djkxLjljMCwxOCwxNC44LDMyLjgsMzIuOCwzMi44aDEwMC43YzE4LDAsMzcuMywxNC4xLDQyLjgsMzEuM2wxNCwzNC4zYzguMSwxNi4xLDQuMywzOS44LTguNSw1Mi41TDEyMSw3NjcuNmMtMTIuOCwxMi44LTEyLjgsMzMuNiwwLDQ2LjRsNjUsNjVjMTIuOCwxMi44LDMzLjYsMTIuOCw0Ni40LDBsNzAuMi03MC4yYzEyLjgtMTIuOCwzNi4zLTE2LjUsNTIuNC04LjJsMzUsMTQuNmMxNy4yLDUuNiwzMS4yLDI1LDMxLjIsNDN2OTljMCwxOCwxNC44LDMyLjgsMzIuOCwzMi44aDkxLjljMTgsMCwzMi44LTE0LjgsMzIuOC0zMi44di05OWMwLTE4LDE0LTM3LjQsMzEuMi00M2wyNS41LTEwLjZMNTg1LjksNzU1Yy0yNy4zLDkuMy01Ni4zLDE0LjEtODUuOSwxNC4xYy03MS4zLDAtMTM4LjMtMjcuOC0xODguNy03OC4yYy01MC40LTUwLjQtNzguMi0xMTcuNC03OC4yLTE4OC43czI3LjgtMTM4LjMsNzguMi0xODguN2M1MC40LTUwLjQsMTE3LjQtNzguMiwxODguNy03OC4yczEzOC4zLDI3LjgsMTg4LjcsNzguMmM1MC40LDUwLjQsNzguMiwxMTcuNCw3OC4yLDE4OC43YzAsMjktNC42LDU3LjMtMTMuNSw4NC4xbDQ5LjcsNDkuN2wxMC42LTI2YzUuNS0xNy4yLDI0LjctMzEuMyw0Mi44LTMxLjNoMTAwLjdjMTgsMCwzMi44LTE0LjgsMzIuOC0zMi44di05MS45Qzk5MCw0MzYsOTc1LjIsNDIxLjMsOTU3LjIsNDIxLjNMOTU3LjIsNDIxLjN6IE03MDEuMiw2MDcuNmMtMTAuNS0xMC4zLTE1LjQtMTguNi05LjUtMzUuMWMzMC45LTg3LjEsMi42LTE3Ni40LTczLjItMjI5LjRjLTQ3LjgtMzMuNC0xMDEuMi00My0xNjAuMS0yNS41YzIyLDI1LjksNDYuOCw0Ny40LDcwLDcwLjVjNDUuNiw0NS4zLDQ2LjMsMTAxLjMsMi44LDE0NC4yYy00Mi4yLDQxLjUtOTkuMSw0MC4yLTE0Mi45LTRjLTIzLTIzLjItNDUuMS00Ny4yLTY3LjctNzAuOGMtMi43LDEuNi01LjQsMy4yLTguMSw0LjhjLTEzLDcxLjEsMy4xLDEzNC42LDU2LjUsMTg1LjZjNTYuOSw1NC4yLDEyNC45LDY5LjYsMTk5LjUsNDQuNWMxOS4xLTYuNCwyOS4yLTMsNDIuMSwxMC4xQzY2Ni4zLDc1OC44LDc2Nyw4NTQuNiw4MjMuNyw5MTBjMjcuNCwyNi44LDYxLjEsMjcuNyw4OC4xLDQuMmMzMy41LTI5LjEsMzUuMi02Ny42LDMuMS05OS44Qzg1OC45LDc1OC40LDc1Ny44LDY2Myw3MDEuMiw2MDcuNkw3MDEuMiw2MDcuNnogTTg2NC43LDg5MC4zYy0xNy4yLDAtMzEuMi0xNC0zMS4yLTMxLjJzMTQtMzEuMiwzMS4yLTMxLjJzMzEuMiwxNCwzMS4yLDMxLjJDODk1LjksODc2LjQsODgyLDg5MC4zLDg2NC43LDg5MC4zeiIvPjwvZz4KPC9zdmc+');
	define('wploti_animation_dir', plugin_dir_url( __FILE__ ) .'animations/');
	// notice_dismiss
	add_action( 'wp_ajax_wploti_ajax_dismiss_activation_notice', array( $my_wploti_maintenance_redirect, 'wploti_ajax_dismiss_activation_notice' ) );
	add_action( 'wp_ajax_wploti_ajax_dismiss_notes_notice', array( $my_wploti_maintenance_redirect, 'wploti_ajax_dismiss_notes_notice' ) );
	// animation_select
	add_action( 'wp_ajax_wploti_animation_select', array( $my_wploti_maintenance_redirect, 'animation_select' ) );
	add_action( 'wp_ajax_wploti_animation_ajax_load', array( $my_wploti_maintenance_redirect, 'load_animations' ) );
	// actions & filters
	add_action( 'admin_menu',  'wploti_maintenance_redirect_ap' );
	add_action( 'admin_menu',   array( $my_wploti_maintenance_redirect, 'wploti_maintenance_redirect_menu' ));
	add_action( 'send_headers',  array( $my_wploti_maintenance_redirect, 'process_redirect'), 0 );
	add_action( 'admin_notices', array( $my_wploti_maintenance_redirect, 'display_status_if_active' ) );
	add_action( 'wp_before_admin_bar_render', array( $my_wploti_maintenance_redirect, 'wploti_admin_bar' ) );
	//enqueue styles and scripts
	add_action( 'admin_enqueue_scripts', array( $my_wploti_maintenance_redirect ,'wploti_enqueue_style_and_script_admin' ) );	
	add_action( 'wp_enqueue_scripts', array( $my_wploti_maintenance_redirect ,'wploti_enqueue_style_and_script_public' ) );	

	add_filter( 'plugin_action_links_'.plugin_basename(__FILE__), array( $my_wploti_maintenance_redirect, 'plugin_settings_link' ) );
	add_filter( 'site_status_tests', array( $my_wploti_maintenance_redirect, 'wploti_add_site_health' ) );

	// ajax actions
	add_action('wp_ajax_wploti_toggle_activation', array( $my_wploti_maintenance_redirect, 'wploti_ajax_toggle_activation') );
	add_action('wp_ajax_wploti_header_type',  array( $my_wploti_maintenance_redirect, 'wploti_ajax_header_type') );
	add_action('wp_ajax_wploti_mr_add_ip',    array( $my_wploti_maintenance_redirect, 'add_new_ip'       ) );
	add_action('wp_ajax_wploti_mr_toggle_ip', array( $my_wploti_maintenance_redirect, 'toggle_ip_status' ) );
	add_action('wp_ajax_wploti_mr_delete_ip', array( $my_wploti_maintenance_redirect, 'delete_ip'        ) );
	add_action('wp_ajax_wploti_mr_add_ak',    array( $my_wploti_maintenance_redirect, 'add_new_ak'       ) );
	add_action('wp_ajax_wploti_mr_toggle_ak', array( $my_wploti_maintenance_redirect, 'toggle_ak_status' ) );
	add_action('wp_ajax_wploti_mr_delete_ak', array( $my_wploti_maintenance_redirect, 'delete_ak'        ) );
	add_action('wp_ajax_wploti_mr_resend_ak', array( $my_wploti_maintenance_redirect, 'resend_ak'        ) );
	add_action('wp_ajax_wploti_ajax_message', array( $my_wploti_maintenance_redirect, 'wploti_ajax_message') );
	
	// activation & deactivation 
	register_activation_hook( __FILE__, array( $my_wploti_maintenance_redirect, 'init' ) );
	register_deactivation_hook( __FILE__, array( $my_wploti_maintenance_redirect, 'wploti_deactivate' ) );

	// Reset Settings action
	add_action( 'wp_ajax_wploti_reset_settings', array( $my_wploti_maintenance_redirect, 'reset_plugin_settings' ) );

}
