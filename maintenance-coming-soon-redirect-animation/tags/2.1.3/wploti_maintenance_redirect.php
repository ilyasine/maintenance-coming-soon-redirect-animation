<?php
/*
Plugin Name:		Maintenance & Coming Soon Redirect Animation
Plugin URI:			https://wordpress.org/plugins/maintenance-coming-soon-redirect-animation/
Description:		Make your website in maintenance mode in seconds with great looking animations and configure settings to allow specific users to bypass the maintenance mode.
Version:			2.1.3
Stable tag:	 		2.1.3
Requires at least:	4.6
Tested up to:		6.6
Requires PHP:		5.4

Text Domain: 		maintenance-coming-soon-redirect-animation
Domain Path: 		/languages

License:			GPLv3
License URI:		https://www.gnu.org/licenses/gpl-3.0.html

Author:				Yassine Idrissi 
Author URI:			https://profiles.wordpress.org/yasinedr/

Copyright:			2022 Yassine Idrissi	(email: ydrissi9@gmail.com)
				
   			

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
		
		private $admin_options_name;
		private $maintenance_html;
		private $maintenance_head;
		
				
		/**
		 * (php) constructor
		 *
		 * @since 1.0.0
		 * @access public
		 * @return void
		 */

		function __construct() {
			$this->admin_options_name	= "wploti_mr";
			global $wploti_ajax_nonce, $headers, $wploti_header;

			$wploti_header = get_option('wploti_header_type');

			//set headers here , otherwrise we will get headers already sent warning

			$headers = array();
			$headers[] = array('title' => '200 OK' , 'code' => '200' , 'description' => __( "Best used for when the site is under development." , "maintenance-coming-soon-redirect-animation" ));
			$headers[] = array('title' => '202 Accepted' , 'code' => '202' , 'description' => __( "The request has been accepted for processing, but the processing has not been completed." , "maintenance-coming-soon-redirect-animation" ));
			$headers[] = array('title' => '206 Partial Content' , 'code' => '206' , 'description' => __( "The request has succeeded and the body contains the requested ranges of data, as described in the Range header of the request." , "maintenance-coming-soon-redirect-animation" ));
			$headers[] = array('title' => '302 Found' , 'code' => '302' , 'description' => __( "The target resource resides temporarily under a different URI" , "maintenance-coming-soon-redirect-animation" ));
			$headers[] = array('title' => '307 Temporary Redirect' , 'code' => '307' , 'description' => __( "The resource requested has been temporarily moved to a different URI" , "maintenance-coming-soon-redirect-animation" ));
			$headers[] = array('title' => '503 Service Temporarily Unavailable' , 'code' => '503' , 'description' => __( "Best for when the site is temporarily taken offline for small amendments. If used for a long period of time, 503 can damage your Google ranking." , "maintenance-coming-soon-redirect-animation" ));

			foreach ($headers as $header) {
				switch ($wploti_header) {
					case $header['code']:

						if (!is_admin()) {
							header('HTTP/1.1 ' . $header['title']);
							header('Status: ' . $header['title']);
							header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1.
							header("Pragma: no-cache"); // HTTP 1.0.
							header("Expires: 0"); // Proxies.
						}

						break;
				}

				header('Retry-After: 600');
			}

		}

		/**
		 * (php) initialize
		 *
		 * @since 1.0.0
		 * @access public
		 * @return void
		 */
		
		function init() {
			global $wpdb, $wploti_whitelisted_roles;
			
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

			$wploti_whitelisted_roles = array('administrator');
			$wploti_whitelisted_users = array();
			$wploti_message = __( 'This site is currently undergoing maintenance. Please check back later', 'maintenance-coming-soon-redirect-animation' );

			// setup options
			
			add_option('wploti_animation', wploti_animation_dir . 'default-animation.json');
				
			update_option('wploti_activation_notice', 1);
			update_option('wploti_notes_notice', 1 );
			update_option('wploti_status', '0');
			update_option('wploti_header_type', '200');
			
			update_option('wploti_whitelisted_roles', $wploti_whitelisted_roles);						
			update_option('wploti_whitelisted_users', $wploti_whitelisted_users);		
			update_option('wploti_message', $wploti_message);
			
		}

	
		/**
		 * (php) add custom class to plugin settings page
		 *
		 * @since 2.0.0
		 * @access public
		 * @return string
		 */
		
		 function wploti_body_class($classes) {

			$wploti_current_screen = get_current_screen();

			if ($wploti_current_screen->base === "toplevel_page_wploti-settings") 
			
				$classes .= ' wploti_settings_page';
								
			return $classes;
		}


		/**
		 * (php) Get input message value
		 *
		 * @since 1.0.0
		 * @access public
		 * @return string
		 */


		function wploti_ajax_message() {

			global $refresh_active;

			check_ajax_referer('wploti_nonce', 'security');

			if ( isset($_POST['message']) ) {

				$wploti_message = wp_kses_post($_POST['message']);

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

		/**  (php) show Maintenance Mode notice on WP login form
		* 
		* @since 2.0.0
		* @access public
		* @return void
		*/ 

		function login_message($message)
		{
			if ( $this->wploti_active() == '1') {
				$message .= '<div class="message">' . __('Maintenance Mode is <b>enabled</b>.', 'maintenance-coming-soon-redirect-animation') . '</div>';
			}
	
			return $message;
		} 

		/**  (php) show Maintenance Mode notice on Browser console
		* 
		* @since 2.1.3
		* @access public
		* @return void
		*/ 

		function console_message(){

			$message = '';
			$console = '';
			$console_style = '';
			$console_style .= 'margin: 20px auto;font-family: cursive;';
			$console_style .= 'font-size: 30px; font-weight: bold;';
			$console_style .= 'color: #CFC547; text-align: center;';
			$console_style .= 'letter-spacing: 5px;';
			$console_style .= 'text-shadow: 3px 0px 2px rgba(81,67,21,0.8), -3px 0px 2px rgba(81,67,21,0.8),0px 4px 2px rgba(81,67,21,0.8);';

			if ( $this->wploti_active() == '1') {
				$message =  __('Maintenance Mode is enabled.', 'maintenance-coming-soon-redirect-animation');
			}else{
				$message =  __('Maintenance Mode is disabled.', 'maintenance-coming-soon-redirect-animation');
			}

			$console = 'console.warn("%c ' . $message . '", "'. $console_style . '")';
	
			return $console;
		} 

		/**
		 * (php)  add settings link to plugin page
		 *
		 * @since 1.0.0
		 * @version 2.0.0 updated
		 * @access public
		 * @return array
		 */	

		function wploti_action_links ( $actions ) {

			global $page , $plugin_file , $context , $s , $plugin_data , $plugin_id_attr;
			$new_actions = array();
			$new_actions[] = sprintf( '<a href="'.wploti_admin_url.'">%s</a>', __('Settings', 'maintenance-coming-soon-redirect-animation') );
			$new_actions = array_merge($new_actions, $actions);
			//$uninstall_url =admin_url() .'uninstall.php'.'&amp;action=uninstall&amp;_wpnonce='.wp_create_nonce('wploti_uninstall_'.get_current_user_id().'_wpnonce');
			//$new_actions[] = '<span class="delete"><a href="'.$uninstall_url.'" class="delete">'.__('Uninstall','=maintenance-coming-soon-redirect-animation').'</a></span>';
			/* if ( current_user_can( 'delete_plugins' ) && ! is_plugin_active( $plugin_file ) ) {
				$new_actions['delete'] = sprintf(
					'<a href="%s" id="delete-%s" class="delete" aria-label="%s">%s</a>',
					wp_nonce_url( 'plugins.php?action=delete-selected&amp;checked[]=' . urlencode( $plugin_file ) . '&amp' ),
					esc_attr( $plugin_id_attr ),
					
					esc_attr( sprintf( _x( 'Delete %s', 'plugin' ), $plugin_data['Name'] ) ),
					__( 'Delete' )
				);
			} */
			return $new_actions;
		}

		
		/**
		 * Filters the array of row meta for each plugin in the Plugins list table.
		 *
		 * @param string[] $plugin_meta An array of the plugin's metadata.
		 * @param string   $plugin_file Path to the plugin file relative to the plugins directory.
		 * @return string[] An array of the plugin's metadata.
		 */
		public function wploti_plugin_row_meta( array $plugin_meta, $plugin_file ) {
			if ( 'maintenance-coming-soon-redirect-animation/wploti_maintenance_redirect.php' !== $plugin_file ) {
				return $plugin_meta;
			}

			$plugin_meta[] = sprintf(
				'<a href="%1$s">%2$s</a>',
				'https://wordpress.org/support/plugin/maintenance-coming-soon-redirect-animation/',
				esc_html_x( 'Support', 'verb', 'maintenance-coming-soon-redirect-animation' )
			);
			$plugin_meta[] .= sprintf(
				'<a href="%1$s"><span class="dashicons dashicons-star-filled" aria-hidden="true" style="font-size:14px;line-height:1.3"></span>%2$s</a>',
				'https://www.paypal.me/yassineidrissi',
				esc_html_x( 'Sponsor', 'verb', 'maintenance-coming-soon-redirect-animation' )
			);

			return $plugin_meta;
		}

		/**  (php) display admin topbar notice
		* 
		* @since 1.1.1 
		* @version 2.0.0 updated
		* @access public
		* @return void
		*/ 

		function wploti_admin_bar(){

			global $wp_admin_bar;
			$wploti_ajax_nonce = wp_create_nonce( "wploti_nonce" );
			$wploti_settings_img = '<div class="wploti_settings_img svg" style="background-image: url(&quot;'. wploti_icon .'&quot;) !important;" aria-hidden="true"><br></div>';
			$wploti_state_image = '<div class="wploti_animation_state"><lottie-player autoplay="true" loop src="'. esc_attr ( $this->wploti_active() == '1' ? IMG_path .'/green-on.json' : IMG_path .'/red-off.json'  ).'"  class="animation-state"></lottie-player></div>';
			$topbar = $wploti_state_image.'<div class="wploti_menu_text">'. __( "Maintenance Status" , "maintenance-coming-soon-redirect-animation" ) .' : </div><div class="toggle-wrapper"><div id="wploti-status-menubar" class="toggle-checkbox"></div><div id="wploti-toggle-adminbar" class="status-' . esc_attr( $this->wploti_active() ) . '" data-security="'. esc_attr($wploti_ajax_nonce) .'"><span class="toggle_handler"></span></div></div>';

	    	//Add the main siteadmin menu item
	        $wp_admin_bar->add_menu( array(
	            'id'     => 'wploti-activation-status',
				'title'  => $topbar,
	            'href'   => admin_url().'admin.php?page=wploti-settings',
	            'parent' => 'top-secondary',
	        ) );

			$wp_admin_bar->add_node(array(
				'id'     => 'wploti-preview',
				'title'  => '<span class="wploti_preview dashicons dashicons-external"></span>' . esc_attr__('Preview', 'maintenance-coming-soon-redirect-animation'),
				'meta'   => array('target' => 'blank'),
				'href'   => get_home_url() . '/?wploti_preview',
				'parent' => 'wploti-activation-status'
			));
			$wp_admin_bar->add_node(array(
				'id'     => 'wploti-settings',
				'title'  =>  $wploti_settings_img . esc_attr__('Settings', 'maintenance-coming-soon-redirect-animation'),
				'href'   => admin_url('admin.php?page=wploti-settings'),
				'parent' => 'wploti-activation-status',			
			));
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

			global $submenu;
	
			add_menu_page(
				__( 'Maintenance redirect Settings', 'maintenance-coming-soon-redirect-animation' ),
				__( 'Maintenance', 'maintenance-coming-soon-redirect-animation' ),
				'manage_options',
				'wploti-settings',
				[$this,'print_admin_page'],
				 wploti_icon,
				2
			);

			$wploti_menus = array(
				'header' => __( 'Header Type', 'maintenance-coming-soon-redirect-animation' ),
				'ip' => __( 'Unrestricted IP addresses ', 'maintenance-coming-soon-redirect-animation' ),				
				'keys' => __( 'Access Keys', 'maintenance-coming-soon-redirect-animation' ),
                'animation' => __( 'Animation', 'maintenance-coming-soon-redirect-animation' ), 
                'message' => __( 'Message', 'maintenance-coming-soon-redirect-animation' ), 
                'extra' => __( 'Extra', 'maintenance-coming-soon-redirect-animation' ),
			);

			/** Register submenus */
			foreach( $wploti_menus as $menu_key => $menu_label ) {
				add_submenu_page( 
					'wploti-settings',
					$menu_label , 
					$menu_label , 
					'manage_options', 
					'wploti-settings#' . $menu_key , 
					array( $this,'print_admin_page' ),
				);
			}

			$submenu['wploti-settings'][0] = array( __( 'All settings' ), 'manage_options', 'wploti-settings', 'Maintenance redirect Settings' );

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

			update_option('wploti_animation', wploti_animation_dir . $selected_animation);

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

					
				while ( $counter < $limit  )  :   ?>
					<div class="animation-grid">
						<div id="lottiecontainer">
						<?php if (explode( wploti_animation_dir , get_option("wploti_animation"))[1] == $animations[$counter] ) : ?>
							<div class="selected-bg">
								<span class="selected-btn wp-core-ui button-secondary"><?php _e('Selected', 'maintenance-coming-soon-redirect-animation'); ?></span>
							</div>
						<?php endif; ?>										
							<div class="select-bg">
								<span class="select-btn wp-core-ui button-primary"><?php _e('Select', 'maintenance-coming-soon-redirect-animation') ?></span>
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

						selected_bg = '<div class="selected-bg"><span class="selected-btn wp-core-ui button-secondary"><?php _e('Selected', 'maintenance-coming-soon-redirect-animation'); ?></span></div>';

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

			$select_slyle_src = plugin_dir_url( __FILE__ ) .'css/select2.min.css';

			$select_script_src = plugin_dir_url( __FILE__ ) .'js/select2.min.js';

			$admin_script_src = plugin_dir_url( __FILE__ ) .'js/admin-script.js';

			wp_enqueue_style( 'wploti-admin-style', $style_src, array(), WPLOTI_VERSION, 'all' );		

			wp_enqueue_style( 'admin-bar-style-src', $admin_bar_style_src, array(), WPLOTI_VERSION, 'all' );

			wp_enqueue_style( 'select-slyle-src', $select_slyle_src, array(), WPLOTI_VERSION, 'all' );

			wp_enqueue_script( 'select-script', $select_script_src, array(), WPLOTI_VERSION, false );

			wp_enqueue_script( 'lottiplayer-script', $loti_script_src, array(), WPLOTI_VERSION, false );

			wp_enqueue_script( 'admin-script', $admin_script_src, array('lottiplayer-script','select-script'), WPLOTI_VERSION, false );

			wp_enqueue_script('jquery-ui-tabs');

			wp_add_inline_script('admin-script', $this->console_message());

		}

		/**
		 * (php) enqueue public style and script
		 *
		 * @since 1.0.0
		 * @access public
		 * @return void
		 */

		function wploti_enqueue_style_and_script_public() {

			$loti_script_src = plugin_dir_url( __FILE__ ) .'js/lottie-player-script.js';

			$admin_bar_style_src = plugin_dir_url( __FILE__ ) .'css/wploti-admin-bar.css';
	
			wp_enqueue_style( 'admin_bar_style_src', $admin_bar_style_src, array(), WPLOTI_VERSION, 'all' );

			wp_enqueue_script( 'lottiplayer-script', $loti_script_src, array(), WPLOTI_VERSION, false );

			wp_add_inline_script('lottiplayer-script', $this->console_message());

		}

		/**
		 * (php) load translation files
		 *
		 * @since 1.1.2
		 * @access public
		 * @return void
		 */
		
		function wploti_translation() {

			load_plugin_textdomain( 'maintenance-coming-soon-redirect-animation', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

		}

		/**
		 * (php) handle external translation files
		 *
		 * @since 1.1.2
		 * @access public
		 * @return void
		 */
		
		function wploti_translations_script() {
			global $wploti_ajax_nonce, $wploti_message, $refresh_active;
			// Strings to be translated
			$translation_array = array(
				'be_careful' => __( 'Please Be careful', 'maintenance-coming-soon-redirect-animation' ),
				'option_reset_txt' => __( 'This option will reset all your selections to defaults options and will delete the IP addresses and access keys as well', 'maintenance-coming-soon-redirect-animation' ),
				'pls_wait' => __( 'Please Wait', 'maintenance-coming-soon-redirect-animation' ),
				'save_content' => __( 'Save Content', 'maintenance-coming-soon-redirect-animation' ),
				'saved_content' => __( 'Content Saved', 'maintenance-coming-soon-redirect-animation' ),
				'logged_out_description' => __( 'Execute a complete log-out for all currently signed-in users with a single click.', 'maintenance-coming-soon-redirect-animation' ),
				'logged_out_success' => __( 'All users have been successfully logged out.', 'maintenance-coming-soon-redirect-animation' ),
				'wploti_whitelisted_users_placeholder' => esc_attr__('Select whitelisted user(s)', 'maintenance-coming-soon-redirect-animation'),
			);

			// Localize the script with new data
			wp_localize_script( 'admin-script', 'wploti_translate', $translation_array );
	
			// variables to js
			$wploti_var = array(
				'IMG_path' => plugin_dir_url( __FILE__ ) .'images',
				'wploti_nonce' => esc_attr($wploti_ajax_nonce) ,
				'wploti_msg_value' => esc_attr( get_option('wploti_message', $wploti_message) ) ,			

			);

			// Localize the script with new data
			wp_localize_script( 'admin-script', 'wploti_var', $wploti_var );

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

			global $wpdb , $wploti_message;

			try {
				// check capabilities
				if ( ! current_user_can('manage_options') ) {
					throw new Exception( __( 'You do not have access to this resource.', 'maintenance-coming-soon-redirect-animation' ) );
				}

				// check nonce
				check_ajax_referer( 'wploti_nonce', 'security' );

				// update options using the default values

				$wploti_whitelisted_roles = array('administrator');
				$wploti_whitelisted_users = array();
				$wploti_message = __( 'This site is currently undergoing maintenance. Please check back later', 'maintenance-coming-soon-redirect-animation' );


				update_option('wploti_animation', wploti_animation_dir . 'default-animation.json');
				update_option('wploti_header_type', '200');
				update_option('wploti_message', $wploti_message);
				update_option('wploti_whitelisted_roles', $wploti_whitelisted_roles);						
				update_option('wploti_whitelisted_users', $wploti_whitelisted_users);

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
		 * (php)  add site favicon on admin panel
		 *
		 * @since 2.0.0
		 * @access public
		 * @return void
		 */

		 function add_site_favicon() {

			$favicon_link = '<link rel="icon" type="image/x-icon" href="'.  plugin_dir_url( __FILE__ ).'/images/alert-icon.png' .'">';

			echo wp_kses( $favicon_link,
				array(
					'link' => array(
						'href' => array(),
						'type' => array(),
						'rel' => array(),
					)
				)
			);
		}

		/**
		 * (php)  generate maintenance page
		 *
		 * @since 1.0.0
		 * @access public
		 * @return void
		 */

		function generate_maintenance_page(){

			global $wploti_ajax_nonce;
			$maintenance_head = '';

			$console = wp_kses_post(get_option('wploti_message'));
			$console_style = '';
			$console_style .= 'margin: 20px auto;font-family: cursive;';
			$console_style .= 'font-size: 30px; font-weight: bold;';
			$console_style .= 'color: #CFC547; text-align: center;';
			$console_style .= 'letter-spacing: 5px;';
			$console_style .= 'text-shadow: 3px 0px 2px rgba(81,67,21,0.8), -3px 0px 2px rgba(81,67,21,0.8),0px 4px 2px rgba(81,67,21,0.8);';
	
			$maintenance_head .= '
								<link href=" '. plugin_dir_url( __FILE__ ) ."css/front-style.css" .'" rel="stylesheet" type="text/css" />
								<script src="'. plugin_dir_url( __FILE__ ) ."js/lottie-player-script.js" .'"></script>
								<script>console.warn("%c' . $console . '", "' . $console_style . '")</script>
								<title>'. get_bloginfo( 'name' ) .'</title>
								<meta charset="utf-8">
								<meta http-equiv="X-UA-Compatible" content="IE=edge">
								
								<link rel="icon" type="image/x-icon" href="'.  plugin_dir_url( __FILE__ ).'/images/alert-icon.png' .'">
								<meta name="viewport" content="width=device-width, initial-scale=1">';
								/* if($refresh_active ) :
									$maintenance_head .= '<meta http-equiv="refresh" content="5">';
								endif; */

						
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
			$maintenance_html .= '<lottie-player autoplay="true" loop src="'. esc_attr( get_option('wploti_animation', 'default-animation.json') ) .'"></lottie-player>';
			$maintenance_html .= '</body></html>';
								  								
			
			echo wp_kses($maintenance_html , 	
				array(		
					'lottie-player' => array(
						'autoplay' => true,
						'loop' => true,
						'src' => array(),
					)
				)
			);

			echo wp_kses_post(get_option('wploti_message'));

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
			$current_user = wp_get_current_user();
			
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

						//skip Whitelisted User Roles
						if($this->user_has_role( get_option('wploti_whitelisted_roles'))){
							$wploti_matches[] = "<!-- wploti_MR: ROLE MATCH -->";
						}

						//skip Whitelisted Users
						if( in_array($current_user->ID , get_option('wploti_whitelisted_users')) ){
							$wploti_matches[] = "<!-- wploti_MR: USER MATCH -->";
						}
						
						if( count( $wploti_matches ) == 0 ) {
						
							// no match found. show maintenance page

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
				_e( 'Unable to add IP because of a database error. Please reload the page.' , 'maintenance-coming-soon-redirect-animation' );
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
				_e( 'Unable to delete IP because of a database error. Please reload the page.' , 'maintenance-coming-soon-redirect-animation' );
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
				$subject    = sprintf( /* translators: %s = name of the website/blog */ __( "Access Key Link for %s" , "maintenance-coming-soon-redirect-animation" ), get_bloginfo() );
				$full_msg   = sprintf( /* translators: %s = name of the website/blog */ __( "The following link will provide you temporary access to %s:" , "maintenance-coming-soon-redirect-animation" ), get_bloginfo() ) . "\n\n"; 
				$full_msg  .= __( "Please note that you must have cookies enabled for this to work." , "maintenance-coming-soon-redirect-animation" ) . "\n\n";
				$full_msg  .= get_bloginfo('url') . '?wploti_mr_temp_access_key=' . $access_key;
				$mail_sent  = wp_mail( $email, $subject, $full_msg );
				echo ( esc_html($mail_sent) ) ? '<!-- SEND_SUCCESS -->' : '<!-- SEND_FAILURE -->';
				// send table data
				$this->print_access_keys();
			}else{
				_e( "Unable to add Access Key because of a database error. Please reload the page." , "maintenance-coming-soon-redirect-animation" );
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
				_e( 'Unable to delete Access Key because of a database error. Please reload the page.' , 'maintenance-coming-soon-redirect-animation');
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
				$subject    = sprintf( /* translators: %s = name of the website/blog */ __( "Access Key Link for %s" , "maintenance-coming-soon-redirect-animation"), get_bloginfo() );
				$full_msg   = sprintf( /* translators: %s = name of the website/blog */ __( "The following link will provide you temporary access to %s:" , "maintenance-coming-soon-redirect-animation"), get_bloginfo() ) . "\n\n"; 
				$full_msg  .= __( "Please note that you must have cookies enabled for this to work." , "maintenance-coming-soon-redirect-animation") . "\n\n";
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
						<th class="column-wploti-ip-name"><?php _e( "Name" , "maintenance-coming-soon-redirect-animation" ); ?></th>
						<th class="column-wploti-ip-ip"><?php _e( "IP" , "maintenance-coming-soon-redirect-animation"); ?></th>
						<th class="column-wploti-ip-active"><?php _e( "Creation Date" , "maintenance-coming-soon-redirect-animation"); ?></th>
						<th class="column-wploti-ip-creation-date"><?php _e( "Active" , "maintenance-coming-soon-redirect-animation"); ?></th>
						<th class="column-wploti-actions"><?php _e( "Actions" , "maintenance-coming-soon-redirect-animation"); ?></th>
					</tr>
				</thead>

				<tfoot>
					<tr>
						<th class="column-wploti-ip-name"><?php _e( "Name" , "maintenance-coming-soon-redirect-animation"); ?></th>
						<th class="column-wploti-ip-ip"><?php _e( "IP" , "maintenance-coming-soon-redirect-animation"); ?></th>
						<th class="column-wploti-ip-creation-date"><?php _e( "Creation Date" , "maintenance-coming-soon-redirect-animation"); ?></th>
						<th class="column-wploti-ip-active"><?php _e( "Active" , "maintenance-coming-soon-redirect-animation"); ?></th>						
						<th class="column-wploti-actions"><?php _e( "Actions" , "maintenance-coming-soon-redirect-animation"); ?></th>
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
								<td class="column-wploti-ip-active" id="wploti_mr_ip_status_<?php echo esc_attr($ip_id); ?>" ><?php echo ( sanitize_text_field($ip->active) == 1) ? '<span class="green">' .  __( 'Yes' , 'maintenance-coming-soon-redirect-animation' ) .' </span>' : '<span class="red">' . __( 'No' , 'maintenance-coming-soon-redirect-animation' ) .' </span>' ; ?></td>
								<td class="column-wploti-actions">
									<span class='edit' id="wploti_mr_ip_status_<?php echo esc_attr($ip_id); ?>_action">
										<?php if( $ip->active == 1 ){ ?>
											<a href="javascript:wploti_mr_toggle_ip( 0, <?php echo esc_attr($ip_id) ?> );"><?php _e( "Disable" , "maintenance-coming-soon-redirect-animation" ); ?></a> | 
										<?php }else{ ?>
											<a href="javascript:wploti_mr_toggle_ip( 1, <?php echo esc_attr($ip_id) ?> );"><?php _e( "Enable" , "maintenance-coming-soon-redirect-animation" ); ?></a> | 
										<?php } ?>
									</span>
									<span class='delete'>
										<a class='submitdelete' href="javascript:wploti_mr_delete_ip( <?php echo esc_attr($ip_id) ?>, '<?php echo addslashes( esc_attr($ip_address) ) ?>' );" ><?php _e( "Delete" , "maintenance-coming-soon-redirect-animation" ); ?></a>
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
							<input class="wploti_mr_disabled_field" type="text" id="wploti_mr_new_ip_name" name="wploti_mr_new_ip_name" placeholder="<?php _e( "Enter Name:" , "maintenance-coming-soon-redirect-animation"); ?>">
							
						</td>
						<td class="column-wploti-ip-ip">
							<input class="wploti_mr_disabled_field" type="text" id="wploti_mr_new_ip_ip" name="wploti_mr_new_ip_ip" placeholder="<?php _e( "Enter IP:" , "maintenance-coming-soon-redirect-animation"); ?>">
						</td>
						<td class="column-wploti-ip-active">&nbsp;</td>
						<td class="column-wploti-ip-creation-date">&nbsp;</td>
						<td class="column-wploti-actions">
							<span class='edit' id="wploti_mr_add_ip_link">
								<a href="javascript:wploti_mr_add_new_ip( );"><?php _e( "Add New IP" , "maintenance-coming-soon-redirect-animation"); ?></a>
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
						<th class="column-wploti-ak-name"><?php _e( "Name" , "maintenance-coming-soon-redirect-animation"); ?></th>
						<th class="column-wploti-ak-email"><?php _e( "Email" , "maintenance-coming-soon-redirect-animation"); ?></th>
						<th class="column-wploti-ak-key"><?php _e( "Access Key" , "maintenance-coming-soon-redirect-animation"); ?></th>
						<th class="column-wploti-ak-key-creation-date"><?php _e( "Creation Date" , "maintenance-coming-soon-redirect-animation"); ?></th>
						<th class="column-wploti-ak-active"><?php _e( "Active" , "maintenance-coming-soon-redirect-animation"); ?></th>
						<th class="column-wploti-actions"><?php _e( "Actions" , "maintenance-coming-soon-redirect-animation"); ?></th>
					</tr>
				</thead>

				<tfoot>
					<tr>
						<th class="column-wploti-ak-name"><?php _e( "Name" , "maintenance-coming-soon-redirect-animation"); ?></th>
						<th class="column-wploti-ak-email"><?php _e( "Email" , "maintenance-coming-soon-redirect-animation"); ?></th>
						<th class="column-wploti-ak-key"><?php _e( "Access Key" , "maintenance-coming-soon-redirect-animation"); ?></th>
						<th class="column-wploti-ak-key-creation-date"><?php _e( "Creation Date" , "maintenance-coming-soon-redirect-animation"); ?></th>
						<th class="column-wploti-ak-active"><?php _e( "Active" , "maintenance-coming-soon-redirect-animation"); ?></th>
						<th class="column-wploti-actions"><?php _e( "Actions" , "maintenance-coming-soon-redirect-animation"); ?></th>
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
								<td class="column-wploti-ak-active" id="wploti_mr_ak_status_<?php echo esc_attr($ak_code); ?>" ><?php echo ( $code->active == 1) ? '<span class="green">' .  __( "Yes" , "maintenance-coming-soon-redirect-animation") .' </span>' :  '<span class="red">' .  __( "No" , "maintenance-coming-soon-redirect-animation") .' </span>'; ?></td>
								<td class="column-wploti-actions">
									<span class='edit' id="wploti_mr_ak_status_<?php echo esc_attr($ak_code); ?>_action">
										<?php if( $code->active == 1 ){ ?>
											<a href="javascript:wploti_mr_toggle_ak( 0, <?php echo esc_attr($ak_code); ?> );"><?php _e( "Disable" , "maintenance-coming-soon-redirect-animation"); ?></a> | 
										<?php }else{ ?>
											<a href="javascript:wploti_mr_toggle_ak( 1, <?php echo esc_attr($ak_code); ?> );"><?php _e( "Enable" , "maintenance-coming-soon-redirect-animation"); ?></a> | 
										<?php } ?>
									</span>
									<span class='resend'>
										<a class='submitdelete' href="javascript:wploti_mr_resend_ak( <?php echo esc_attr($ak_code) ?>, '<?php echo addslashes( esc_attr($ak_name) ) ?>', '<?php echo addslashes( esc_attr($ak_email) ) ?>' );" ><?php _e( "Resend Code" , "maintenance-coming-soon-redirect-animation"); ?></a> | 
									</span>
									<span class='delete'>
										<a class='submitdelete' href="javascript:wploti_mr_delete_ak( <?php echo esc_attr($ak_code) ?>, '<?php echo addslashes( esc_attr($ak_name) ) ?>' );" ><?php _e( "Delete" , "maintenance-coming-soon-redirect-animation" ); ?></a>
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
							<input class="wploti_mr_disabled_field" type="text" id="wploti_mr_new_ak_name" name="wploti_mr_new_ak_name" placeholder="<?php _e( "Enter Name:" , "maintenance-coming-soon-redirect-animation"); ?>">
						</td>
						<td class="column-wploti-ak-email">
							<input class="wploti_mr_disabled_field" type="text" id="wploti_mr_new_ak_email" name="wploti_mr_new_ak_email" placeholder="<?php _e( "Enter Email:" , "maintenance-coming-soon-redirect-animation"); ?>">
						</td>
						<td class="column-wploti-ak-key">&nbsp;</td>
						<td class="column-wploti-ak-active">&nbsp;</td>
						<td class="column-wploti-ak-key-creation-date">&nbsp;</td>
						<td class="column-wploti-actions">
							<span class='edit' id="wploti_mr_add_ak_link">
								<a href="javascript:wploti_mr_add_new_ak( );"><?php _e( "Add New Access Key" , "maintenance-coming-soon-redirect-animation"); ?></a>
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

			if ( get_option( 'wploti_activation_notice' ) && current_user_can('manage_options')) {
	
					// load the notices view
					$current_screen = get_current_screen();
					$current_user = wp_get_current_user();
					$settingslink = ( $current_screen->id != "settings_page_wploti-settings" ) ? ' <a href="'.wploti_admin_url.'">'.__( 'Settings' , 'maintenance-coming-soon-redirect-animation' ).'</a>' : '';
					$welcomemsg = '';
					$welcomemsg .='<div class="wploti-notice notice-success is-dismissable" id="wploti_enabled_notice">';
					$welcomemsg .='<lottie-player autoplay="true" loop src="'. esc_attr(IMG_path .'/wploti-bg.json').'"  class="wploti_animation"></lottie-player>';
					$welcomemsg .='<div class="notice-activation-text-wrapper">';
					$welcomemsg .='<p><h3 class="main_redirect_msg">' .  __( "Thank you "  . $current_user->display_name . " for installing Maintenance & Coming Soon Redirect Animation Plugin!" , "maintenance-coming-soon-redirect-animation" ) . '</h3>';
					$welcomemsg .='<span>'. __( "You can activate the Maintenance Mode in" , "maintenance-coming-soon-redirect-animation" ) . $settingslink . ' ' .  __( "or by Maintenance Status Top bar icon and choose your animation !", "maintenance-coming-soon-redirect-animation" ) .'</span>';
					$welcomemsg .='</div>';
					$welcomemsg .='<div class="wploti-dismiss"><a href="#dismiss" data-security="'. esc_attr($wploti_ajax_nonce) .'" name="wploti-activation-dismiss" class="wploti-activation-dismiss">' . __( "Dismiss" , "maintenance-coming-soon-redirect-animation" ) .' </a></div>';
					$welcomemsg .='</p></div>';

					$allowed_tags = [

						'div' => [
							'class' 	=> true,			
							'id'		=> true,			
						],
						'h3' => [
							'class'		=> true,			
						],
						'img' => [
							'src'		=> true,
							'class'     => array()
						],
						'a' => [
							'href'			 => true,
							'data-security'  => true,
							'name'	  		 => true,
							'class'   		 => true,		
						],
						'span' => [
							'class' => array(),
						],
						'lottie-player' => [
							'autoplay'  => true,
							'loop'      => true,
							'src' 		=> array(),
							'class'		=> array(),
						]
					
					];
					
					echo wp_kses($welcomemsg , $allowed_tags);

			}

			return;

		}

		/**
		 * (php)  remove jquery migrate console
		 *
		 * @since 2.0.0
		 * @access public
		 * @return void
		 */	

		function remove_jquery_migrate_console($scripts) {
			if (!empty($scripts->registered['jquery'])) {
				$scripts->registered['jquery']->deps = array_diff($scripts->registered['jquery']->deps, ['jquery-migrate']);
			}
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
			
			global $wploti_header;
			
			$result = array(
				'label'       => __( 'Maintenance Redirect is not enabled' , 'maintenance-coming-soon-redirect-animation' ),
				'status'      => 'good',
				'badge'       => array(
					'label' => __( 'Visibility' ),
					'color' => 'blue',
				),
				'description' => sprintf(
					'<p>%s</p>',
					__( 'Maintenance is not enabled and your site is visible to visitors.' , 'maintenance-coming-soon-redirect-animation' )
				),
				'actions'     => sprintf(
					'<p><a href="options-general.php?page=wploti-settings">%s</a></p>',
					__( 'Settings' )
				),
				'test'        => 'wploti_status',
			);

			if ( $this->wploti_active() == '1' ) {
			
				if ( $wploti_header == "503" ) {

					$result['status'] = 'critical';
					$result['badge']['color'] = 'red';
					$result['label'] = __( 'Maintenance is enabled' , 'maintenance-coming-soon-redirect-animation' );
					$result['description'] = sprintf(
						'<p>%s</p>',
						__( 'Maintenance is enabled and your site is not visible to visitors. Your redirection type is set to 503, which could harm your Google ranking if left on for any length of time.' , 'maintenance-coming-soon-redirect-animation')
					
					);
				} else {

					if( $wploti_header != '503' ) :
						$result['status'] = 'recommended';
						$result['label'] = __( 'Maintenance Redirect is enabled' , 'maintenance-coming-soon-redirect-animation' );
						$result['description'] = sprintf(
							'<p>%s</p>',
							__( 'Maintenance is enabled and your site is not visible to visitors. Your redirection type is set to ' . $wploti_header  , 'maintenance-coming-soon-redirect-animation' )
						);
					endif;
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

		function header_tab(){ 
			
			global $wploti_ajax_nonce, $headers;

			?>

			<div class="wploti_mr_admin_section" >
				<h3 class="big-title"><?php _e( "Header Type:" , "maintenance-coming-soon-redirect-animation" ); ?></h3>
				<p><?php _e( "When redirect is enabled you can send different header types:" , "maintenance-coming-soon-redirect-animation" ); ?> </p>			
				<dl>

				<?php	foreach ( $headers as $header ) {  ?>

							<dt>
								<input type="radio" id="<?php echo esc_attr($header['code']) ?>" name="wploti_header_type" class="wploti_header_type" data-security="<?php echo esc_attr($wploti_ajax_nonce) ; ?>" <?php checked( get_option('wploti_header_type') ,  esc_attr($header['code']) ) ?> value="<?php echo esc_attr($header['code']) ?>">
								<label for="<?php echo esc_attr($header['code']) ?>"><?php _e( $header['title'] , "maintenance-coming-soon-redirect-animation") ?></label><br>
							</dt>
							<dd><?php  echo $header['description']  ; ?></dd>

				<?php	} ?>
				
				</dl>
			</div>

		 <?php }   //header_tab

		function ip_tab(){ ?>

			<div class="wploti_mr_admin_section" >
				<h3 class="big-title"><?php _e( "Unrestricted IP addresses:" , "maintenance-coming-soon-redirect-animation" ); ?>&nbsp;<span class="wploti_mr_small_dim">( <?php _e( "Your IP address is:" , "maintenance-coming-soon-redirect-animation" ); ?>&nbsp;<?php echo $this->get_user_ip(); ?> - <?php _e( "Your Class C is:" , "maintenance-coming-soon-redirect-animation" ); ?>&nbsp;<?php echo $this->get_user_class_c(); ?> )</span></h3>
				<p><?php _e( "Users with unrestricted IP addresses will bypass maintenance mode entirely. Using this option is useful to an entire office of clients to view the site without needing to jump through any extra hoops." , "maintenance-coming-soon-redirect-animation" ); ?></p> 
				
				<div id="wploti_mr_ip_tbl_container">
					<?php $this->print_unrestricted_ips(); ?>
				</div>
			</div>

		 <?php } //ip_tab /


		function key_tab(){ ?>

			<div class="wploti_mr_admin_section">
				<h3 class="big-title"><?php _e( "Access Keys :" , "maintenance-coming-soon-redirect-animation"); ?></h3>
				<p><?php _e( "You can allow users temporary access by sending them the access key. When a new key is created, a link to create the access key cookie will be emailed to the email address provided. Access can then be revoked either by disabling or deleting the key." , "maintenance-coming-soon-redirect-animation" ); ?></p>
				
				<div id="wploti_mr_ak_tbl_container">
					<?php $this->print_access_keys(); ?>
				</div>
			</div>

		 <?php } 


		function animation_tab(){ 
			
			?>

			<div class="wploti_mr_admin_section">	
				<h3 class="big-title"><?php _e( "Maintenance Animation :" , "maintenance-coming-soon-redirect-animation"); ?></h3>
				
				<h4 class="small-title"><?php _e( "Active Animation :" , "maintenance-coming-soon-redirect-animation"); ?></h4>

				<div class="selected-animation">										
					<lottie-player autoplay="true" loop src="<?php echo esc_attr( get_option("wploti_animation", 'default-animation.json') ) ?>" class="lottieanimation"></lottie-player>
				</div>

				<?php $this->upload_animation(); ?>

				<h4 class="small-title"><?php _e( "Or select one from the animations library :" , "maintenance-coming-soon-redirect-animation" ); ?></h4>
				<?php $animations = array_slice(scandir(__DIR__.'/animations'),2); ?>
				<div animations-count="<?php echo esc_attr(count($animations)); ?>" class="animations"></div>

				<div id ="load-animations-message"></div>
			
			</div>

		 <?php } 

		function wploti_mime_types($mimes) {
			$mimes['json'] = 'text/plain'; // Usually the MIME type for JSON used here would be 'application/json', but because of a current WordPress core bug it’s being interpreted as 'text/plain'
			return $mimes; 
		} 

		 function upload_animation(){
			?>

				<div class="img-select-container" style="display: flex; align-items: center; gap: 40px;">
					<input id="upload_image" type="hidden" size="36" name="wploti_upload_animation" value=<?php echo get_option('wploti_upload_animation'); ?> /> 
					<input type="button"  name="wploti_upload_animation" accept="*.json, *.gif" class="button button-secondary upload-button" value="<?php echo esc_attr( "Upload Your own animation" , "maintenance-coming-soon-redirect-animation"); ?>" data-group="1">
					<?php _e( "Please upload your animation in JSON format, otherwise it will not be displayed" , "maintenance-coming-soon-redirect-animation" ); ?>
				</div>
					<?php 

					wp_enqueue_media(); ?>

					<script>
						jQuery(document).ready( function($) {

							// Uploading files
							var mediaUploader;

							$('.upload-button').on('click', function( event ){

								event.preventDefault();

								var buttonID = $(this).data('id');

								// If the media frame already exists, reopen it.
								if ( mediaUploader ) {
									// Open frame
									mediaUploader.id = buttonID;
									mediaUploader.open();
									return;
								}

								// Create the media frame.
								mediaUploader = wp.media.frames.file_frame = wp.media({
									id: buttonID,
									title: 'Select a file to upload',
									button: {
										text: 'Select',
									},
									multiple: false , // Set to true to allow multiple files to be selected
									uploader: {
									type: 'text/plain'
									}
								});

								// When an image is selected, run a callback.
								mediaUploader.on( 'select', function() {
									
									attachment = mediaUploader.state().get('selection').first().toJSON();				

									var uploaded_animation = attachment.url;

									$('.selected-bg').remove();

									$('.selected-animation > lottie-player').remove();
									$('.selected-animation').append(
										$('<lottie-player/>')
										.attr("autoplay", "true")
										.attr("loop", "true")
										.attr("src", uploaded_animation)							                       
									)    
									
									$.ajax({
										url: ajaxurl,
										data: {
											uploaded_animation: uploaded_animation,
											action: 'wploti_uploaded_animation_save',								
										},
										type: 'post',
							
										success: function (result, textstatus) {
											/* console.log(result);
											console.log('sucess'); */
											
										},
										error: function (result) {
											/* console.log(result);
											console.log('fail'); */
										},
							
									})

								});


								// Finally, open the modal
								mediaUploader.open();
							});


						});
					</script>
			<?php
		 }

		function wploti_uploaded_animation_save_option(){

			$uploaded_animation = $_POST['uploaded_animation'];

			update_option('wploti_animation' , $uploaded_animation);

			wp_die();
		} 

		function wploti_add_whitelisted_roles_option(){

			if( isset($_POST['role']) ) {

				$wploti_whitelisted_roles = get_option('wploti_whitelisted_roles');
				
				$whitelisted_role = $_POST['role'];

					if (!in_array($whitelisted_role, $wploti_whitelisted_roles)){

						array_push( $wploti_whitelisted_roles , $whitelisted_role ) ;
						
					}

				update_option('wploti_whitelisted_roles' , $wploti_whitelisted_roles);

			}

			wp_die();
		}

		function wploti_remove_whitelisted_roles_option(){

			if( isset($_POST['role']) ) {

				$wploti_whitelisted_roles = get_option('wploti_whitelisted_roles');

				$whitelisted_role = $_POST['role'];

				//Delete element key that value match $whitelisted_role
				foreach (array_keys($wploti_whitelisted_roles, $whitelisted_role, true) as $key) {
					unset($wploti_whitelisted_roles[$key]);
				}

				update_option('wploti_whitelisted_roles' , $wploti_whitelisted_roles);

			}

			wp_die();
		}

		/**
		 * (php) Add whitelisted users option
		 *
		 * @since 2.0.0
		 * @access public
		 * @return void
		 */

		 function wploti_add_whitelisted_users_option(){

			if ( isset($_POST['user_id']) ) {

				$wploti_whitelisted_users = get_option('wploti_whitelisted_users');
				
				$whitelisted_user = sanitize_text_field($_POST['user_id']);

					if (!in_array($whitelisted_user, $wploti_whitelisted_users)){

						array_push( $wploti_whitelisted_users , $whitelisted_user ) ;
						
					}

				update_option('wploti_whitelisted_users', $wploti_whitelisted_users);
				
			}
		
			wp_die();
		
		}

		//

		/**
		 * (php) Remove whitelisted users option
		 *
		 * @since 2.0.0
		 * @access public
		 * @return void
		 */

		 function wploti_remove_whitelisted_users_option(){

			if( isset($_POST['user_id']) ) {

				$wploti_whitelisted_users = get_option('wploti_whitelisted_users');
				
				$whitelisted_user = sanitize_text_field($_POST['user_id']);

				//Delete element key that value match $whitelisted_user
				foreach (array_keys($wploti_whitelisted_users, $whitelisted_user, true) as $key) {
					unset($wploti_whitelisted_users[$key]);
				}

				update_option('wploti_whitelisted_users' , $wploti_whitelisted_users);

			}
		
			wp_die();
		
		}

		
		/**  (php)  toggle activation for admin menu icon
		* 
		* @since 1.1.1
		* @access public
		* @return void
		*/ 

		function wploti_logout_users_callback() {
			// Check for ajax payload
			if (isset($_POST['payload']) && $_POST['payload'] == 'wploti_logout_users') {
				// Verify nonce
				check_ajax_referer('wploti_nonce', 'security');
		
				// Verify user rights
				if (!current_user_can('manage_options')) {
					wp_die("Oh no you don't!");
					return;
				}
		
				// Get All users except administrator
				$args = [						
					'role__not_in' => ['administrator'],
					'fields' => 'ID',
				];
				$users = get_users($args);
				// Loop through the user IDs and log them out
				foreach ($users as $user) {
					if (is_user_logged_in($user)) {

						// Get all sessions for the user with ID 
						$sessions = WP_Session_Tokens::get_instance($user);
						
						// Destroy all sessions
						$sessions->destroy_all();
						
					}
				}
		
				//wp_send_json_success('logout success');
				wp_die();
			}
		}
		

	
		function message_tab() { 
			
			global $wploti_ajax_nonce;

			?>
			
			<div id="wploti_text_message">
			<strong><?php _e( "Maintenance Mode Message (optional) :" , "maintenance-coming-soon-redirect-animation"); ?></strong>
				<p><?php _e( "You can write a brief message that will be displayed under animation :" , "maintenance-coming-soon-redirect-animation"); ?></p>
				<?php
				 $wploti_message = __( 'This site is currently undergoing maintenance. Please check back later', 'maintenance-coming-soon-redirect-animation' );
				 wp_editor( get_option('wploti_message', $wploti_message), 'content', array('tabfocus_elements' => 'insert-media-button,save-post', 'editor_height' => 250, 'resize' => 1, 'editor_class' => 'wploti_message', 'textarea_name' => 'wploti_message', 'drag_drop_upload' => 1)); ?>
			</div>
			

		 <?php } 

		function extra_tab(){ 
			$wploti_ajax_nonce = wp_create_nonce( "wploti_nonce" );
			?>

			<div class="wploti-tab-content">
				<table class="form-table"><tbody>
					<?php //Whitelisted User Roles  
					$wploti_whitelisted_roles[] = get_option('wploti_whitelisted_roles');
					$wploti_whitelisted_users[] = get_option('wploti_whitelisted_users');
					$wploti_roles = new WP_Roles();
					$roles = $wploti_roles->get_names();
					$users = array();
					
					?>
					<tr valign="top">					
						<th scope="row"><?php _e( "Whitelisted User Roles" , "maintenance-coming-soon-redirect-animation"); ?> :</th>
						<td>
							<?php
							$current_user = wp_get_current_user();							

								foreach ($roles as $role_value => $role_label) {

									?>
									<input 
										type="checkbox" 
										value="<?php echo esc_attr($role_value); ?>" 
										class="wploti_whitelisted_roles"
										data-security="<?php echo esc_attr($wploti_ajax_nonce) ?>"
										<?php if(in_array( $role_value , $wploti_whitelisted_roles[0] )){
											echo "checked";
										}?>
									/>
									<label><?php _e($role_label , "maintenance-coming-soon-redirect-animation"); ?></label>
									<br />
							<?php } 
							?>
						</td>
						
					</tr>
					<tr class="description">
						<td  colspan="2"><p ><?php echo __('Selected user roles will <b>not</b> be affected by the maintenance mode and will always see the "normal" site. Default: administrator.', 'maintenance-coming-soon-redirect-animation') ?> </p> </td>
					</tr>
					
					<?php //Whitelisted Users 

					$tmp_users = get_users(array('fields' => array('id', 'display_name')));
					foreach ($tmp_users as $user) {
						$users[] = array('val' => $user->id, 'label' => $user->display_name);
					}
					?>			
					<tr valign="top">
					
						<th scope="row">
							<label for="wploti_whitelisted_users"><?php _e( "Whitelisted Users :" , "maintenance-coming-soon-redirect-animation"); ?></label>
						</th>
						<td>
						<select id="wploti_whitelisted_users" class="select2" style="width: 100%; max-width: 300px;" name="wploti_whitelisted_users" multiple>						
						<?php 
							$this->create_wploti_select_options($users , get_option('wploti_whitelisted_users') , true) ;
						?>
						</select>	
						</td>

					</tr>
					<tr class="description">
						<td colspan="2"><p><?php _e('Selected users (when logged in) will <b>not</b> be affected by the maintenance mode and will always see the "normal" site.', 'maintenance-coming-soon-redirect-animation') ?> </p> </td>
					</tr>

					<tr class="logout-extra">
						<td><input type="button" value="<?php esc_attr_e( 'Logout All users', 'maintenance-coming-soon-redirect-animation' ); ?>" class="button button-secondary wploti_logout_users" data-security="<?php echo esc_attr($wploti_ajax_nonce) ; ?>" name="logout" /></td>
						<td colspan="2"><p id="logged_out_description"><?php _e('Execute a complete log-out for all currently signed-in users with a single click.', 'maintenance-coming-soon-redirect-animation') ?> </p> </td>
					</tr>
					
				</table>
			</div>
					
		<?php
		  }  

		/**
		 * (php)  check if user has the specified role
		 *
		 * @since 2.0.0
		 * @access public
		 * @return boolean
		 */
		function user_has_role($roles){
			$current_user = wp_get_current_user();

			if ($current_user->roles) {
				$user_role = $current_user->roles[0];
			} else {
				$user_role = 'guest';
			}

			return in_array($user_role, $roles);
		} // user_has_role

		
		/**
		 * (php)  helper function for creating dropdowns
		 *
		 * @since 2.0.0
		 * @access public
		 * @return boolean
		 */
		function create_wploti_select_options($options, $selected = null, $output = true)
		{
			$out = "\n";

			if (!is_array($selected)) {
				$selected = array($selected);
			}

			foreach ($options as $tmp) {
				$data = '';
				if (isset($tmp['disabled'])) {
					$data .= ' disabled="disabled" ';
				}
				if (in_array($tmp['val'], $selected)) {
					$out .= "<option selected=\"selected\" value=\"{$tmp['val']}\"{$data}>{$tmp['label']}&nbsp;</option>\n";
				} else {
					$out .= "<option value=\"{$tmp['val']}\"{$data}>{$tmp['label']}&nbsp;</option>\n";
				}
			} // foreach

			if ($output) {
				//echo ($out);
				echo wp_kses( $out,
				array(
					'option' => array(
						'selected' => array(),
						'value' => array(),
						'rel' => array(),
					)
				)
			);
			} else {
				return $out;
			}
		} // create_wploti_select_options


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
			echo '<div class="updated" style="display: none" ><p><strong>'. __("Settings Saved" , "maintenance-coming-soon-redirect-animation" ) .'</strong></p></div>';

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
								<img class="alert-icon" src="<?php echo esc_attr ( plugin_dir_url( __FILE__ ).'/images/alert-icon.png' )?>" alt="Alert Icon" />
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
									<img class="alert-icon" src="<?php echo esc_attr ( plugin_dir_url( __FILE__ ).'/images/alert-icon.png' )?>" alt="Alert Icon" />
								</div>
								<div class="messages">
									<div>${options.message}</div>
								</div>
								<div class="modal-footer">
									<button value="OK" class="button button-primary ok_wploti_confirm" name="ok_wploti_alert">${options.okText}</button>
									<button value="${options.cancelText}" class="button button-secondary cancel_wploti_confirm" name="cancel_wploti_alert"><?php _e('Cancel', 'maintenance-coming-soon-redirect-animation' ) ?></button>
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
					if( jQuery('#wploti_mr_new_ip_name').val() == ''                              ) error_msg += '<?php _e( "You must enter a Name" , "maintenance-coming-soon-redirect-animation"); ?>.\n<br>';
					if( jQuery('#wploti_mr_new_ip_name').val() == '<?php _e( "Enter Name:" , "maintenance-coming-soon-redirect-animation"); ?>' ) error_msg += '<?php _e( "You must enter a Name" , "maintenance-coming-soon-redirect-animation"); ?>.\n<br>';
					if( jQuery('#wploti_mr_new_ip_ip'  ).val() == ''                              ) error_msg += '<?php _e( "You must enter an IP" , "maintenance-coming-soon-redirect-animation"); ?>.\n<br>';
					if( jQuery('#wploti_mr_new_ip_ip'  ).val() == '<?php _e( "Enter IP:" , "maintenance-coming-soon-redirect-animation"); ?>'   ) error_msg += '<?php _e( "You must enter an IP" , "maintenance-coming-soon-redirect-animation"); ?>.\n<br>';
					if( ValidateIPaddress( jQuery('#wploti_mr_new_ip_ip'  ).val() ) != true   ) error_msg += '<?php _e( "IP address not valid" , "maintenance-coming-soon-redirect-animation"); ?>.\n<br>';
					if( error_msg != '' ){							
						wploti_alert('<?php _e( "There is a problem with the information you have entered" , "maintenance-coming-soon-redirect-animation"); ?>.\n\n' , error_msg )

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
								jQuery('#wploti_mr_ip_status_' + split_response[1] ).html( '<span class="green">Yes</span>' );
								jQuery('#wploti_mr_ip_status_' + split_response[1] + '_action' ).html( '<a href="javascript:wploti_mr_toggle_ip( 0, ' + split_response[1] + ' );"><?php _e( "Disable" , "maintenance-coming-soon-redirect-animation"); ?></a> | ' );
							}else{
								// disabled
								jQuery('#wploti_mr_ip_status_' + split_response[1] ).html( '<span class="red">No</span>' );
								jQuery('#wploti_mr_ip_status_' + split_response[1] + '_action' ).html( '<a href="javascript:wploti_mr_toggle_ip( 1, ' + split_response[1] + ' );"><?php _e( "Enable" , "maintenance-coming-soon-redirect-animation"); ?></a> | ' );
							} 
						}else{
							wploti_alert( '<?php _e( "There was a database error. Please reload this page" , "maintenance-coming-soon-redirect-animation"); ?>' );
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

						message: '<?php _e( "You are about to delete the IP address:" , "maintenance-coming-soon-redirect-animation"); ?>\n\n\'' + ip_addr + '\'\n\n',
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
					if( jQuery('#wploti_mr_new_ak_name' ).val() == ''                               ) error_msg += '<?php _e( "You must enter a Name" , "maintenance-coming-soon-redirect-animation"); ?>.<br>\n';
					if( jQuery('#wploti_mr_new_ak_name' ).val() == '<?php _e( "Enter Name:" , "maintenance-coming-soon-redirect-animation"); ?>'  ) error_msg += '<?php _e( "You must enter a Name" , "maintenance-coming-soon-redirect-animation"); ?>.<br>\n';
					if( jQuery('#wploti_mr_new_ak_email').val() == ''                               ) error_msg += '<?php _e( "You must enter an Email" , "maintenance-coming-soon-redirect-animation"); ?>.<br>\n';
					if( jQuery('#wploti_mr_new_ak_email').val() == '<?php _e( "Enter Email:" ); ?>' ) error_msg += '<?php _e( "You must enter an Email" , "maintenance-coming-soon-redirect-animation"); ?>.<br>\n';
					if( error_msg != '' ){
						wploti_alert( '<?php _e( "There is a problem with the information you have entered" , "maintenance-coming-soon-redirect-animation"); ?>.\n\n' , error_msg );
						
					}else{

						wploti_confirm.open({

							message : '<?php _e( "You are about to email an Access Key link to" , "maintenance-coming-soon-redirect-animation"); ?> <b>' + ak_email + '</b> !<br> <?php _e( "If you do not see the email in a few secondes," , "maintenance-coming-soon-redirect-animation") ?> <br> <?php _e("Please check your “junk mail” or “spam” folder." , "maintenance-coming-soon-redirect-animation") ?> \n\n',
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
								jQuery('#wploti_mr_ak_status_' + split_response[1] ).html( '<span class="green">Yes</span>' );
								jQuery('#wploti_mr_ak_status_' + split_response[1] + '_action' ).html( '<a href="javascript:wploti_mr_toggle_ak( 0, ' + split_response[1] + ' );"><?php _e( "Disable" , "maintenance-coming-soon-redirect-animation"); ?></a> | ' );
							}else{
								// disabled
								jQuery('#wploti_mr_ak_status_' + split_response[1] ).html( '<span class="red">No</span>' );
								jQuery('#wploti_mr_ak_status_' + split_response[1] + '_action' ).html( '<a href="javascript:wploti_mr_toggle_ak( 1, ' + split_response[1] + ' );"><?php _e( "Enable" , "maintenance-coming-soon-redirect-animation"); ?></a> | ' );
							} 
						}else{
							wploti_alert( '<?php _e( "There was a database error. Please reload this page" , "maintenance-coming-soon-redirect-animation"); ?>' , ' ' );
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

						message: '<?php _e( "You are about to delete this Access Key:" , "maintenance-coming-soon-redirect-animation" ); ?>\n\n\'' + ak_name + '\'\n\n',
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
						message : '<?php _e( "You are about to resend an Access Key link to" , "maintenance-coming-soon-redirect-animation" ); ?> <b>' + ak_email + '</b> !<br> <?php _e( "If you do not see the email in a few secondes," , "maintenance-coming-soon-redirect-animation") ?> <br> <?php _e("Please check your “junk mail” or “spam” folder." , "maintenance-coming-soon-redirect-animation" ) ?> \n\n',
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
									wploti_alert( '<?php _e( "Notification Sent." , "maintenance-coming-soon-redirect-animation" ); ?>','' );
								}else{
									wploti_alert( '<?php _e( "Notification Failure. Please check your server settings." , "maintenance-coming-soon-redirect-animation" ); ?>','' );
								}
							});
						}
					})
					
				}

				/**
				 * (js) RESET SETTINGS
				 *
				 * @since 1.0.0
				 * @return void
				 */

				jQuery('.wploti_reset_settings').click(function() {
					var security = jQuery(this).data('security');
					wploti_confirm.open({						
						message: '<?php _e( "Please Be careful ! " , "maintenance-coming-soon-redirect-animation" ) . '<br><br>' . _e( "Please Be careful ! " , "maintenance-coming-soon-redirect-animation" ) ?>',
						onok: () => {
							jQuery.ajax({
								url: ajaxurl,
								data: {
									action: 'wploti_reset_settings',
									security: security,
								},
								type: 'post',
								success: function(result, textstatus) {
									window.location.reload(true);
								},
								error: function(result) {
								},
							})
						}
					})
				})
			
			</script>
			
			<!-- **************  JS  ************** -->

			<div class="wrap">
					<h2></h2>
					<div class="wploti-head">
						<div class="wploti_animation_state">
							<lottie-player autoplay="true" loop src="<?php echo esc_attr ( $this->wploti_active() == '1' ? IMG_path .'/green-on.json' : IMG_path .'/red-off.json'  )?>"  class="animation-state"></lottie-player>
						</div>
						<h1 class="big-title"><?php echo esc_html(get_admin_page_title() , "maintenance-coming-soon-redirect-animation"); ?></h1>				
					</div>
							
				<p><?php _e( "Make your website in maintenance mode in seconds with great looking animations and configure settings to allow specific users to bypass the maintenance mode functionality in order to preview the site prior to public launch. Any logged in user with WordPress administrator privileges will be allowed to view the site regardless of the settings below." , "maintenance-coming-soon-redirect-animation" ); ?></p>
				<?php if ( get_option('wploti_notes_notice') ) : ?>
				<div class=" notice-success is-dismissable" id="wploti_note_notice">
					<div class="notice-activation-text-wrapper">
						<div class="note_head">
						<img class="alert-icon" src="<?php echo esc_attr ( IMG_path.'/alert-icon.png' )?>" alt="Alert Icon" />
							<h3 class="main_redirect_msg"><?php _e( "Notes : " , "maintenance-coming-soon-redirect-animation" )?></h3>
						</div>
						<ul class="note_text">
							<li><?php _e( "This plugin will override any other maintenance plugin you use ." , "maintenance-coming-soon-redirect-animation" ); ?></li>
							<li><?php _e( "All settings are auto-updated , you don't need to save anything ." , "maintenance-coming-soon-redirect-animation" ); ?></li>
						</ul>
						<div class="wploti-leave-feedback">
							<div><a href="#dismiss" data-security="<?php echo esc_attr($wploti_ajax_nonce) ?>" name="wploti-activation-dismiss" class="wploti-note-dismiss"><?php _e('Dismiss' , "maintenance-coming-soon-redirect-animation"); ?></a></div>
						</div>
					</div>
				</div>
				<?php endif; ?>						
				<h3 class="big-title"><?php _e( "Enable Maintenance Mode:" , "maintenance-coming-soon-redirect-animation" ); ?></h3>
				<div class="enable-maintenance-mode">
					<div class="wploti-maintenance-toggle">
						<div class="toggle-wrapper">
							<input type="checkbox" data-security="<?php echo esc_attr($wploti_ajax_nonce) ?>" name="wploti_status" id="wploti-status" class="toggle-checkbox" <?php checked( '1', $this->wploti_active() );  ?>>
							<label for="wploti-status" class="toggle"><span class="toggle_handler"></span></label> 
						</div>
					</div>
				</div>

				<style>
					.wploti-maintenance-toggle .toggle:before {
						content: "<?php _e("Disabled", "maintenance-coming-soon-redirect-animation") ?>";
					}
					.wploti-maintenance-toggle .toggle:after {
						content: "<?php _e("Enabled", "maintenance-coming-soon-redirect-animation") ?>";
					}
				</style>


				<div id="wploti_main_options" style="display: <?php echo ( $this->wploti_active() == '1' ) ? 'block' : 'none'; ?> " >
							
					<?php 
				
					$tabs = array();
					$tabs[] = array('id' => 'header', 'icon' => 'dashicons-welcome-learn-more', 'class' => '', 'label' => __('Header Type', 'maintenance-coming-soon-redirect-animation'), 'callback' => array(__CLASS__, 'header_tab'));
					$tabs[] = array('id' => 'ip', 'icon' => 'dashicons-location', 'class' => '', 'label' => esc_attr__('IP addresses', 'maintenance-coming-soon-redirect-animation'), 'callback' => array(__CLASS__, 'ip_tab'));
					$tabs[] = array('id' => 'keys', 'icon' => 'dashicons-admin-network', 'class' => '', 'label' => esc_attr__('Access Keys', 'maintenance-coming-soon-redirect-animation'), 'callback' => array(__CLASS__, 'key_tab'));
					$tabs[] = array('id' => 'animation', 'icon' => 'dashicons-welcome-view-site', 'class' => '', 'label' => esc_attr__('Animation', 'maintenance-coming-soon-redirect-animation'), 'callback' => array(__CLASS__, 'animation_tab'));
					$tabs[] = array('id' => 'message', 'icon' => 'dashicons-admin-comments', 'class' => '', 'label' => esc_attr__('Message', 'maintenance-coming-soon-redirect-animation'), 'callback' => array(__CLASS__, 'message_tab'));
					$tabs[] = array('id' => 'extra', 'icon' => 'dashicons-awards', 'class' => '', 'label' => esc_attr__('Extra', 'maintenance-coming-soon-redirect-animation'), 'callback' => array(__CLASS__, 'extra_tab'));
			
					$tabs = apply_filters('wploti_tabs', $tabs);
			
					echo '<div id="wploti_tabs" class="ui-tabs" style="display: none;">';
					echo '<ul class="wploti-main-tab">';
					foreach ($tabs as $tab) {
						if (!empty($tab['label'])) {
							echo '<li><a href="#' . esc_attr($tab['id']) . '" class="' . esc_attr($tab['class']) . '"><span class="icon"><span class="dashicons ' . esc_attr($tab['icon']) . '"></span></span><span class="label">' . esc_attr($tab['label']) . '</span></a></li>';
						}
					}
					echo '</ul>';
			
					foreach ($tabs as $tab) {
						if (is_callable($tab['callback'])) {
							echo '<div style="display: none;" id="' . esc_attr($tab['id']) . '">';
							call_user_func($tab['callback']);
							echo '</div>';
						}
					} // foreach
					echo '</div>'; // wploti_tabs
					
					?>

					<div class="submit">
						<?php wp_nonce_field( 'wploti_nonce' ); ?>
						<!-- <input type="submit" name="update_wp_maintenance_redirect_settings" class="wp-core-ui button-primary" value="<?php _e( 'Update Settings' ); ?>" /> -->
						<input type="button" value="<?php esc_attr_e( 'Reset settings', 'maintenance-coming-soon-redirect-animation' ); ?>" class="button button-secondary wploti_reset_settings" data-security="<?php echo esc_attr($wploti_ajax_nonce) ; ?>" name="submit" />
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
					__("Maintenance Redirect Options" , 'maintenance-coming-soon-redirect-animation'),
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

	define('WPLOTI_VERSION','2.1.2');
	define('wploti_icon', 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHhtbG5zOnhsaW5rPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5L3hsaW5rIiBpZD0ic3ZnIiB2ZXJzaW9uPSIxLjEiIHdpZHRoPSI0MDAiIGhlaWdodD0iMjg4LjE4MjM2MzUyNzI5NDU3IiB2aWV3Qm94PSIwLCAwLCA0MDAsMjg4LjE4MjM2MzUyNzI5NDU3Ij48ZyBpZD0ic3ZnZyI+PHBhdGggaWQ9InBhdGgwIiBkPSJNMTQuOTk3IDAuMzQ0IEMgNy40NzkgMi4yNDcsMS43NzMgOC4xMzQsMC4zMzEgMTUuNDc3IEMgLTAuMjkzIDE4LjY1MSwtMC4xNDcgMTk5LjExNSwwLjQ4MiAyMDIuMTcwIEMgMi40MDggMjExLjUzMCw5LjM3NyAyMTkuODY3LDE3Ljk5NiAyMjMuMTIxIEMgMTkuNTE0IDIyMy42OTQsMjAuOTcyIDIyNC4yNTksMjEuMjM2IDIyNC4zNzUgQyAyMi4zMTYgMjI0Ljg1MiwyNS4yNzAgMjI1LjA3MywzMy41OTMgMjI1LjMwMiBDIDM4LjQxMCAyMjUuNDM0LDQ0LjAyNSAyMjUuNjA1LDQ2LjA3MSAyMjUuNjgyIEMgNDguMTE2IDIyNS43NTksNTUuODM3IDIyNS45MTgsNjMuMjI3IDIyNi4wMzYgQyA3MC42MTggMjI2LjE1NSw4MC41NTIgMjI2LjM2Miw4NS4zMDMgMjI2LjQ5OCBDIDkwLjA1NCAyMjYuNjMzLDEwMC41ODIgMjI2Ljg1OCwxMDguNjk4IDIyNi45OTggQyAxMTYuODE1IDIyNy4xMzgsMTI4LjM2OCAyMjcuMzUyLDEzNC4zNzMgMjI3LjQ3NSBDIDE0MC4zNzggMjI3LjU5NywxNTEuMjU3IDIyNy43NzIsMTU4LjU0OCAyMjcuODY0IEwgMTcxLjgwNiAyMjguMDMxIDE3MS44MDYgMjI5LjAyOCBDIDE3MS44MDYgMjI5LjU3NywxNzEuNzAxIDIzMC42MTMsMTcxLjU3MyAyMzEuMzMwIEMgMTcxLjQ0NSAyMzIuMDQ3LDE3MS4yMjMgMjMzLjc2NywxNzEuMDgwIDIzNS4xNTMgQyAxNzAuNTM5IDI0MC4zOTUsMTcwLjExNyAyNDQuMjQ2LDE2OS43NzYgMjQ3LjAzMSBDIDE2OS41ODIgMjQ4LjYxNCwxNjkuMjU5IDI1MS40NzYsMTY5LjA1NyAyNTMuMzg5IEMgMTY4Ljg1NiAyNTUuMzAzLDE2OC41ODQgMjU3LjY3OCwxNjguNDU0IDI1OC42NjggQyAxNjguMzIzIDI1OS42NTgsMTY4LjE4NyAyNjEuMTQyLDE2OC4xNTEgMjYxLjk2NiBDIDE2OC4xMTYgMjYyLjc5MCwxNjcuOTk1IDI2My40NjUsMTY3Ljg4MiAyNjMuNDY2IEMgMTY3Ljc3MCAyNjMuNDY3LDE2Ny43MTcgMjYzLjk1MywxNjcuNzY0IDI2NC41NDcgQyAxNjcuODExIDI2NS4xNDEsMTY3Ljc1NyAyNjUuNjI3LDE2Ny42NDQgMjY1LjYyNyBDIDE2Ny41MzEgMjY1LjYyNywxNjcuNDc3IDI2Ni4xMTMsMTY3LjUyNCAyNjYuNzA3IEMgMTY3LjU3MSAyNjcuMzAxLDE2Ny41MzcgMjY3Ljc4NiwxNjcuNDQ3IDI2Ny43ODYgQyAxNjcuMzU4IDI2Ny43ODYsMTY3LjIwOCAyNjguODY2LDE2Ny4xMTUgMjcwLjE4NiBDIDE2Ni45NDYgMjcyLjU3MiwxNjYuOTQxIDI3Mi41ODUsMTY2LjMxNiAyNzIuNTg2IEMgMTYxLjA1MSAyNzIuNTg5LDE0Ny45NDkgMjczLjE2MCwxNDYuOTA2IDI3My40MzIgQyAxNDMuODU0IDI3NC4yMjcsMTQyLjkwMSAyNzUuNTIzLDE0Mi4wMzQgMjgwLjA1OSBDIDE0MS44NzQgMjgwLjg5NywxNDEuNjYxIDI4MS42OTIsMTQxLjU2MCAyODEuODI0IEMgMTQwLjI3NiAyODMuNTEzLDE0MS44ODIgMjg3LjU0MiwxNDQuMDUwIDI4OC4wNjkgQyAxNDQuMzM2IDI4OC4xMzksMTczLjAyMyAyODguMTY2LDIwNy43OTggMjg4LjEyOSBMIDI3MS4wMjYgMjg4LjA2MiAyNzEuOTg5IDI4Ny41NDcgQyAyNzQuMTM4IDI4Ni4zOTUsMjc1LjA1MSAyODQuNzI0LDI3NC41NTkgMjgyLjgzOSBDIDI3NC40MTMgMjgyLjI4MCwyNzQuMDg1IDI4MS4wMTEsMjczLjgzMCAyODAuMDE3IEMgMjczLjU3NiAyNzkuMDI0LDI3My4wMTcgMjc3LjA4MCwyNzIuNTg5IDI3NS42OTggQyAyNzAuNjQzIDI2OS40MDgsMjcwLjg5MiAyNjkuNTQ0LDI2MS41NDggMjY5LjcwMiBDIDI1Ny43ODYgMjY5Ljc2NiwyNTMuNzkzIDI2OS44MjAsMjUyLjY3MiAyNjkuODIyIEwgMjUwLjYzNiAyNjkuODI2IDI1MC4yMjMgMjY3LjMwNyBDIDI0OS45OTcgMjY1LjkyMSwyNDkuNzAzIDI2NC4wODUsMjQ5LjU3MSAyNjMuMjI3IEMgMjQ5LjMzNyAyNjEuNzEzLDI0OS4xNTIgMjYwLjYxOSwyNDguMTU0IDI1NC44MjkgQyAyNDcuODkzIDI1My4zMTEsMjQ3LjU1NyAyNTEuMzE0LDI0Ny40MDggMjUwLjM5MCBDIDI0Ny4yNTkgMjQ5LjQ2NiwyNDYuOTE5IDI0Ny40MTUsMjQ2LjY1NCAyNDUuODMxIEMgMjQ2LjM4OCAyNDQuMjQ3LDI0Ni4wNzIgMjQyLjM1OCwyNDUuOTUxIDI0MS42MzIgQyAyNDUuODMxIDI0MC45MDYsMjQ1LjYzMyAyMzkuNzcyLDI0NS41MTEgMjM5LjExMiBDIDI0NS4zODggMjM4LjQ1MiwyNDUuMTYzIDIzNy4xMDMsMjQ1LjAwOSAyMzYuMTEzIEMgMjQ0Ljg1NiAyMzUuMTIzLDI0NC42MzAgMjMzLjc3MywyNDQuNTA3IDIzMy4xMTMgQyAyNDQuMzg0IDIzMi40NTQsMjQ0LjIxNSAyMzEuMzY0LDI0NC4xMzIgMjMwLjY5MiBMIDI0My45ODAgMjI5LjQ3MSAyNDcuNzg1IDIyOS4zMTcgQyAyNDkuODc3IDIyOS4yMzIsMjU0LjgyOSAyMjkuMjY4LDI1OC43ODggMjI5LjM5NyBDIDI2Ny43MjcgMjI5LjY4OCwzMjQuMjQ1IDIzMC4xMDksMzUzLjY4OSAyMzAuMTAzIEMgMzc2Ljk0NCAyMzAuMDk4LDM3OC41NDkgMjMwLjAzMywzODEuNjk0IDIyOC45NTcgQyAzODIuNDU4IDIyOC42OTYsMzgzLjczMSAyMjguMjkxLDM4NC41MjMgMjI4LjA1OCBDIDM5MS4yMDQgMjI2LjA5MCwzOTYuNTkwIDIyMC41NzgsMzk4LjIyMyAyMTQuMDM3IEMgMzk4Ljc1NCAyMTEuOTExLDM5OS4wMjEgMjA2LjM5MiwzOTkuMjg2IDE5Mi4wOTkgQyAzOTkuNDIzIDE4NC43MTgsMzk5LjY0MiAxNzguMjM5LDM5OS43NzIgMTc3LjcwMiBDIDQwMC4wNzAgMTc2LjQ4MCwzOTkuODkyIDk1Ljk0NiwzOTkuNTY3IDg0LjcwMyBDIDM5OS4yOTEgNzUuMTI5LDM5OC44ODcgNjUuNjk5LDM5OC41NzUgNjEuNTQ4IEMgMzk4LjQ0NiA1OS44MzIsMzk4LjI3MyA1Ni45MTcsMzk4LjE5MCA1NS4wNjkgQyAzOTcuOTE0IDQ4LjkzMSwzOTYuMjY0IDMxLjM5NywzOTUuNTQ4IDI2Ljk5NSBDIDM5NS4zNTUgMjUuODA3LDM5NS4wODMgMjQuMDc5LDM5NC45NDMgMjMuMTU1IEMgMzkzLjcwNiAxNC45NjgsMzg4LjA4NSA4LjcxNywzNzkuNzI0IDYuMjI5IEMgMzc4LjIzMCA1Ljc4NSwzNjQuNTIxIDUuMjA1LDM0Ni4wMTEgNC44MDMgQyAzMzkuNjEwIDQuNjY0LDMzMS40NTggNC40NDYsMzI3Ljg5NCA0LjMxOSBDIDMyNC4zMzEgNC4xOTIsMzEyLjY2OSAzLjkyOSwzMDEuOTgwIDMuNzMzIEMgMjkxLjI5MCAzLjUzOCwyNzkuNTc0IDMuMjY3LDI3NS45NDUgMy4xMzIgQyAyNzIuMzE2IDIuOTk3LDI2NS4xODkgMi44MjUsMjYwLjEwOCAyLjc1MCBDIDI1MS4zNjIgMi42MjAsMjI5LjIzNSAyLjE2MiwyMDkuMTE4IDEuNjk1IEMgMTk4LjM4OSAxLjQ0NSwxNzUuMDU2IDEuMDQwLDE1MS41MzAgMC42OTQgQyAxNDIuMjkyIDAuNTU4LDEyOC40NzAgMC4zNTQsMTIwLjgxNiAwLjI0MSBDIDk3LjgxMiAtMC4xMDAsMTYuNDM2IC0wLjAyMCwxNC45OTcgMC4zNDQgTTUwLjk5MCAxNy43NTggQyA3Ny40ODcgMTguMzc2LDgyLjc2MiAxOC40ODYsOTAuOTQyIDE4LjU5NiBDIDk1Ljc1OSAxOC42NjEsMTAwLjc4MCAxOC43NjcsMTAyLjEwMCAxOC44MzEgQyAxMDMuNDE5IDE4Ljg5NiwxMDcuMzA3IDE5LjAxMCwxMTAuNzM4IDE5LjA4NCBDIDE0Ny4yODMgMTkuODgwLDE3NC4zMTUgMjAuNDMwLDE4Ni45MjMgMjAuNjM1IEMgMjA3LjQxOSAyMC45NjksMjM2LjMzMCAyMS41MzEsMjU1LjkwOSAyMS45NzYgQyAyNjQuNzUxIDIyLjE3NywyNzUuNjAzIDIyLjM5OCwyODAuMDI0IDIyLjQ2OCBDIDI4NC40NDUgMjIuNTM4LDI4OC44MTggMjIuNjM0LDI4OS43NDIgMjIuNjgyIEMgMjkwLjY2NiAyMi43MjksMjk2LjI4MSAyMi44MjEsMzAyLjIyMCAyMi44ODUgQyAzMDguMTU4IDIyLjk0OSwzMTMuMTc5IDIzLjA2MSwzMTMuMzc3IDIzLjEzMyBDIDMxMy41NzUgMjMuMjA2LDMxOC4yMTggMjMuMzIwLDMyMy42OTUgMjMuMzg2IEMgMzM5LjY3NCAyMy41NzksMzYxLjEzNSAyNC4xODAsMzYyLjQzNiAyNC40NzIgQyAzNjUuNTc4IDI1LjE3NywzNjguMjcyIDI3LjQxOSwzNjkuNTU2IDMwLjM5NSBMIDM3MC4zNjYgMzIuMjc0IDM3MC4zNjYgNTUuOTA5IEMgMzcwLjM2NiA5OC4xNjcsMzY5Ljk3OSAxODYuNzA5LDM2OS43NzggMTkwLjQwMiBDIDM2OS41NzAgMTk0LjIxOCwzNjkuMzk5IDE5NS4zOTEsMzY5LjAzMSAxOTUuNTIxIEMgMzY4LjkwNiAxOTUuNTY2LDM2OC44NTkgMTk1LjY5MiwzNjguOTI3IDE5NS44MDIgQyAzNjguOTk1IDE5NS45MTIsMzY4Ljg4MCAxOTYuMTczLDM2OC42NzEgMTk2LjM4MSBDIDM2OC40NjMgMTk2LjU5MCwzNjguMzYzIDE5Ni43NjEsMzY4LjQ0OSAxOTYuNzYxIEMgMzY4LjY1NCAxOTYuNzYxLDM2Ny43MDUgMTk4LjMxMywzNjYuNzc5IDE5OS40OTMgQyAzNjUuOTc1IDIwMC41MTcsMzYzLjE1NSAyMDIuNzU5LDM2Mi42NzIgMjAyLjc1OSBDIDM2Mi41MDQgMjAyLjc1OSwzNjIuMzIzIDIwMi44OTQsMzYyLjI2OCAyMDMuMDU5IEMgMzYyLjE5MSAyMDMuMjkwLDM2MC42MzkgMjAzLjM1OSwzNTUuNTg5IDIwMy4zNTkgQyAzNDkuMDE2IDIwMy4zNTksMzQ0LjE3NyAyMDMuMzAzLDI5My45NDEgMjAyLjY0MSBDIDI0OC44NDMgMjAyLjA0NywyMDkuMDI3IDIwMS41ODYsMTkwLjQwMiAyMDEuNDQzIEMgMTgwLjYzNiAyMDEuMzY4LDE1Ni40NDkgMjAxLjEwMCwxMzYuNjUzIDIwMC44NDcgQyAxMTYuODU3IDIwMC41OTMsOTQuNjY3IDIwMC4zMjUsODcuMzQzIDIwMC4yNTEgQyA4MC4wMTggMjAwLjE3Niw3Mi45NDUgMjAwLjA2NCw3MS42MjYgMjAwLjAwMiBDIDcwLjMwNiAxOTkuOTM5LDYyLjE1NCAxOTkuODMxLDUzLjUwOSAxOTkuNzYyIEMgMjkuNDg3IDE5OS41NzAsMjkuMDU5IDE5OS41MzksMjUuNDM1IDE5Ny43NjkgQyAyMi4zODkgMTk2LjI4MiwyMC4zMjUgMTkzLjY4MywxOS40ODkgMTkwLjI4MiBDIDE4Ljk3MSAxODguMTc0LDE4LjY3MiA3Mi44MTUsMTkuMDkyIDM3LjU1MiBMIDE5LjIzNiAyNS41NTUgMjAuMTcxIDIzLjU5OSBDIDIxLjI1NSAyMS4zMzIsMjIuOTI5IDE5LjY3NSwyNS4zMzcgMTguNDg2IEMgMjcuOTYwIDE3LjE5MCwyNy4zNTcgMTcuMjA3LDUwLjk5MCAxNy43NTggTTE4NC4xNDQgMzEuMjk2IEMgMTgyLjcyMCAzMS43MzYsMTgxLjc3OSAzMi41MjYsMTgxLjAxNCAzMy45MjIgTCAxODAuMzQwIDM1LjE1MyAxODAuMTkwIDQ0Ljc1MSBDIDE3OS45ODQgNTcuOTU2LDE3OS44NjAgNTguMjE1LDE3Mi4yODYgNjEuMTgwIEMgMTY0Ljc5MyA2NC4xMTMsMTY0LjUyMyA2NC4wMTAsMTU1LjU2MiA1NC44MDEgQyAxNDguNDA4IDQ3LjQ0OSwxNDguMTgwIDQ3LjI2NCwxNDYuMDM3IDQ3LjA5MiBDIDE0My41NDAgNDYuODkyLDE0My4wNjcgNDcuMjE1LDEzNi4zMTQgNTMuNzUyIEMgMTI2LjgxMCA2Mi45NTIsMTI2LjgwNiA2Mi42NDUsMTM2LjU3MCA3Mi42ODQgQyAxNDUuNjc4IDgyLjA0OCwxNDUuODE0IDgyLjQzNiwxNDIuNTcwIDg5Ljc0MiBDIDE0MC41NDUgOTQuMzAxLDEzOS43OTUgOTUuMzM3LDEzNy42OTMgOTYuNDc3IEwgMTM2LjE3MyA5Ny4zMDEgMTI2LjA5NSA5Ny4zMDEgTCAxMTYuMDE3IDk3LjMwMSAxMTQuODcyIDk3Ljk3NCBDIDExMi4yODkgOTkuNDkyLDExMi4wNjEgMTAwLjU2NCwxMTIuMDU5IDExMS4xNjUgQyAxMTIuMDU3IDEyMy41MjIsMTExLjUzNyAxMjMuMDU2LDEyNS41NTYgMTIzLjI5NSBMIDEzNC45ODQgMTIzLjQ1NSAxMzYuNTg2IDEyNC4yNDQgQyAxMzkuMDg3IDEyNS40NzYsMTM5Ljc2NyAxMjYuNDI4LDE0MS43MDkgMTMxLjQxOSBDIDE0NC44MzYgMTM5LjQ1NywxNDQuODYyIDEzOS40MDQsMTMxLjE3MSAxNTIuMzg0IEMgMTI1Ljk5MyAxNTcuMjkzLDEyNi4zNDQgMTU4LjY1NCwxMzUuMDYyIDE2Ny40ODIgQyAxNDQuMTQ3IDE3Ni42ODEsMTQzLjU1NiAxNzYuNjk4LDE1My42MTMgMTY2Ljk2NCBDIDE2Mi43MDggMTU4LjE2MiwxNjIuOTExIDE1OC4wOTMsMTcwLjM0MCAxNjEuMjY1IEMgMTc1LjE5MyAxNjMuMzM4LDE3Ni40ODUgMTY0LjI5MywxNzcuNjA3IDE2Ni42NDEgTCAxNzguMjk4IDE2OC4wODYgMTc4LjI1OSAxNzcuNjkyIEMgMTc4LjIwMiAxOTEuODQ0LDE3Ny42MzQgMTkxLjIzMSwxOTEuMDE0IDE5MS40MjYgQyAyMDQuNDc3IDE5MS42MjIsMjAzLjk4NCAxOTIuMTEyLDIwNC4yMzEgMTc4LjI4NCBDIDIwNC40MTEgMTY4LjIyMywyMDQuNDM3IDE2OC4wMjIsMjA1Ljg3NiAxNjUuOTMxIEMgMjA2Ljg0OSAxNjQuNTE3LDIwOC4xMDEgMTYzLjcwNCwyMTEuMjE4IDE2Mi40NjIgTCAyMTMuNzk5IDE2MS40MzQgMjEyLjYxNSAxNjAuMDgxIEMgMjExLjk2NCAxNTkuMzM3LDIxMC4xNDMgMTU3LjQ0OSwyMDguNTY5IDE1NS44ODYgTCAyMDUuNzA2IDE1My4wNDQgMjAzLjI2NyAxNTMuNjU5IEMgMTY4LjMwMiAxNjIuNDc3LDEzOC4xNTMgMTI3LjY3NiwxNTIuMjIyIDk0LjczOCBDIDE2NS45MTggNjIuNjc0LDIwOS40ODMgNTguODY5LDIyOC42NjQgODguMDYyIEMgMjM1LjY3MCA5OC43MjYsMjM3LjY4OCAxMTQuMzE5LDIzMy40ODggMTI1LjMzOCBDIDIzMy4yMzUgMTI2LjAwMiwyMzMuMzMzIDEyNi4xMjgsMjM3LjIxOSAxMzAuMTM3IEMgMjM5LjQxMyAxMzIuNDAxLDI0MS4yODIgMTM0LjI4MiwyNDEuMzcxIDEzNC4zMTcgQyAyNDEuNDU5IDEzNC4zNTEsMjQyLjA1MyAxMzMuMTQxLDI0Mi42OTEgMTMxLjYyNiBDIDI0My45MDIgMTI4Ljc0OSwyNDQuOTQwIDEyNy4yNzQsMjQ2LjQwNiAxMjYuMzUyIEMgMjQ4LjI4MCAxMjUuMTczLDI0OC41NjAgMTI1LjE0OCwyNTguNTQ4IDEyNS4yMjcgQyAyNzIuNzQ1IDEyNS4zMzksMjcyLjI2MyAxMjUuODAyLDI3Mi4zODQgMTExLjkzOCBDIDI3Mi40OTIgOTkuNTQzLDI3Mi4zNjcgOTkuMzg0LDI2Mi41MDcgOTkuMzMzIEMgMjQ1Ljg3MyA5OS4yNDcsMjQ1LjcwNSA5OS4xNzgsMjQyLjQ3MiA5MS4xODggQyAyMzkuNDU1IDgzLjczNSwyMzkuNTc1IDgzLjQxMywyNDguNjQyIDc0LjY0MyBDIDI1NS40NDQgNjguMDYyLDI1Ni4xNjEgNjcuMjI4LDI1Ni40MDQgNjUuNjEwIEMgMjU2LjgxNiA2Mi44NjIsMjU2LjUwNCA2Mi4zODUsMjQ5LjY5OSA1NS4zNDYgQyAyNDAuNTQzIDQ1Ljg3NCwyNDAuODU5IDQ1Ljg2NCwyMzAuNTEzIDU1LjkyMyBDIDIyMS40MjAgNjQuNzYzLDIyMC45MDQgNjQuOTQ2LDIxMy44MTUgNjEuODUxIEMgMjA2LjAxNiA1OC40NDUsMjA1Ljk4NiA1OC4zODAsMjA2LjE4NyA0NS4zNTcgQyAyMDYuNDEyIDMwLjgxNSwyMDYuODczIDMxLjMyNCwxOTMuMjgxIDMxLjExOCBDIDE4Ny4xMDAgMzEuMDI0LDE4NC44ODYgMzEuMDY3LDE4NC4xNDQgMzEuMjk2IE0xOTEuNjk1IDgwLjEzMyBDIDE4OS45NjcgODAuMzExLDE4Ni4yOTggODEuMDI5LDE4NS44OTggODEuMjY3IEMgMTg1LjUzOSA4MS40ODIsMTg2Ljg2NyA4Mi45NzQsMTkyLjY5NyA4OC45MDIgQyAxOTguMDc1IDk0LjM3MiwxOTkuODQyIDk2LjQ0MCwyMDAuOTA2IDk4LjUxMiBDIDIwNy4yNTggMTEwLjg3NywxOTIuNzg5IDEyNS42ODcsMTgwLjA0NCAxMTkuODY3IEMgMTc2LjkxNyAxMTguNDM5LDE3NS41MTQgMTE3LjE4MiwxNjguMTE4IDEwOS4xNzggQyAxNjUuNDk2IDEwNi4zNDEsMTYzLjE3NSAxMDMuOTkyLDE2Mi45NTkgMTAzLjk1OSBDIDE2MS40MzMgMTAzLjcyNiwxNjAuNTk5IDEwNy42MzksMTYwLjg1MCAxMTMuODU3IEMgMTYxLjIwNyAxMjIuNjc1LDE2NC40NDggMTI5LjYzMiwxNzEuMTI3IDEzNS45MjAgQyAxNzkuODQzIDE0NC4xMjUsMTkwLjEzOCAxNDYuNTU5LDIwMi4xNjAgMTQzLjI1OSBDIDIwNy4yODggMTQxLjg1MSwyMDUuMDI1IDE0MC4xNDIsMjI2LjY1OCAxNjEuNzYxIEMgMjM2LjgzMyAxNzEuOTI5LDI0NS42NDUgMTgwLjU2NSwyNDYuMjQwIDE4MC45NTIgQyAyNTQuOTc3IDE4Ni42MzUsMjY2LjUwMCAxNzYuNDc4LDI2MS45MzggMTY3LjExNSBDIDI2MS4wMjYgMTY1LjI0NSwyNjAuNjMyIDE2NC44MzAsMjQyLjM0OCAxNDYuNDkxIEMgMjMyLjA4NSAxMzYuMTk3LDIyMy41NjkgMTI3LjUzOSwyMjMuNDIyIDEyNy4yNTAgQyAyMjIuOTYwIDEyNi4zNDMsMjIzLjEyOCAxMjQuNzg0LDIyMy45NzIgMTIyLjE1OCBDIDIzMC45NjEgMTAwLjM5MSwyMTMuNjY0IDc3Ljg2OSwxOTEuNjk1IDgwLjEzMyBNMjUyLjc4OSAxNjYuMjU4IEMgMjU3LjY3NCAxNjguNDk4LDI1Ni4zMDcgMTc1LjY0NSwyNTAuOTk0IDE3NS42NDUgQyAyNDYuNTQxIDE3NS42NDUsMjQ0LjM3NSAxNzAuNDk2LDI0Ny41MDggMTY3LjM1OCBDIDI0OS4wMjEgMTY1Ljg0MiwyNTAuOTkwIDE2NS40MzIsMjUyLjc4OSAxNjYuMjU4IE0yMC42NjYgMjA2Ljg5MSBDIDIwLjc1OSAyMDcuMDQwLDIwLjg4NSAyMDcuMDE4LDIwLjk5OSAyMDYuODM0IEMgMjEuMTQxIDIwNi42MDMsMjIuOTQ1IDIwNi41NzMsMjkuMTI3IDIwNi42OTcgQyAzMy40OTcgMjA2Ljc4NCw1Mi43MjkgMjA3LjEyMiw3MS44NjYgMjA3LjQ0NyBDIDkxLjAwMiAyMDcuNzcyLDEyNS4zOTMgMjA4LjM2MiwxNDguMjkwIDIwOC43NTkgQyAxNzEuMTg4IDIwOS4xNTYsMjE1LjA4MSAyMDkuOTEwLDI0NS44MzEgMjEwLjQzNSBDIDI3Ni41ODEgMjEwLjk1OSwzMjEuMDY4IDIxMS43MTksMzQ0LjY5MSAyMTIuMTI0IEMgMzY5LjcxMCAyMTIuNTUyLDM4Ny44NzQgMjEyLjk1NSwzODguMTk2IDIxMy4wODkgQyAzODkuODQwIDIxMy43NzMsMzg4LjM1OSAyMTMuODY3LDM3OC41NDggMjEzLjcwNiBDIDM0NS4xNDkgMjEzLjE1NiwyODEuMDY0IDIxMi4wODksMjY4Ljg2NiAyMTEuODc5IEMgMjEyLjcwMCAyMTAuOTE2LDE1Mi4yNDggMjA5Ljg5OSw5Ny42NjAgMjA4Ljk5OSBDIDM4Ljc0OCAyMDguMDI3LDEyLjQwMCAyMDcuNTM5LDEyLjIwMSAyMDcuNDE2IEMgMTIuMDg5IDIwNy4zNDcsMTEuOTk4IDIwNy4wNjgsMTEuOTk4IDIwNi43OTUgQyAxMS45OTggMjA2LjI1MCwyMC4zMjAgMjA2LjM0MiwyMC42NjYgMjA2Ljg5MSBNMjA0Ljc0NiAyMTMuMDkyIEMgMjA3LjA1NSAyMTMuNzMzLDIwOC4zNjYgMjE2LjE0OCwyMDcuNjgxIDIxOC41MDIgQyAyMDYuNTg1IDIyMi4yNzMsMjAxLjQ1NyAyMjIuODAxLDE5OS43MDAgMjE5LjMyMyBDIDE5Ny45MDQgMjE1Ljc2OCwyMDAuOTI4IDIxMi4wMzIsMjA0Ljc0NiAyMTMuMDkyIE0yMDEuOTIwIDIxNC40ODQgQyAyMDAuMTQ4IDIxNS41NzgsMTk5LjcyMCAyMTcuMDM4LDIwMC42NDUgMjE4LjgzNCBDIDIwMS44MTUgMjIxLjEwNywyMDQuODQzIDIyMS4yNDcsMjA2LjI5MyAyMTkuMDk1IEMgMjA4LjE4NiAyMTYuMjg2LDIwNC43OTQgMjEyLjcwOCwyMDEuOTIwIDIxNC40ODQgIiBzdHJva2U9Im5vbmUiIGZpbGw9IiNhN2FhYWQiIGZpbGwtcnVsZT0iZXZlbm9kZCIvPjwvZz48L3N2Zz4=');
	define('wploti_animation_dir', plugin_dir_url( __FILE__ ) .'animations/');
	define('IMG_path', plugin_dir_url( __FILE__ ) .'images');
	define('wploti_admin_url', admin_url().'admin.php?page=wploti-settings');

	// notice_dismiss
	add_action('wp_ajax_wploti_ajax_dismiss_activation_notice', array( $my_wploti_maintenance_redirect, 'wploti_ajax_dismiss_activation_notice' ) );
	add_action('wp_ajax_wploti_ajax_dismiss_notes_notice', array( $my_wploti_maintenance_redirect, 'wploti_ajax_dismiss_notes_notice' ) );
	// animation_select
	add_action('wp_ajax_wploti_animation_select', array( $my_wploti_maintenance_redirect, 'animation_select' ) );
	add_action('wp_ajax_wploti_animation_ajax_load', array( $my_wploti_maintenance_redirect, 'load_animations' ) );
	// actions & filters
	add_action('admin_menu', 'wploti_maintenance_redirect_ap' );
	add_action('admin_menu', array( $my_wploti_maintenance_redirect, 'wploti_maintenance_redirect_menu' ));
	add_action('send_headers', array( $my_wploti_maintenance_redirect, 'process_redirect'), 0 );
	add_action('admin_notices', array( $my_wploti_maintenance_redirect, 'display_status_if_active' ) );
	//add_action( 'admin_notices', array( $my_wploti_maintenance_redirect, 'loti_notice' ) );
	add_action('wp_before_admin_bar_render', array( $my_wploti_maintenance_redirect, 'wploti_admin_bar' ) );
	//enqueue styles and scripts
	add_action('admin_enqueue_scripts', array( $my_wploti_maintenance_redirect ,'wploti_enqueue_style_and_script_admin' ) );	
	add_action('wp_enqueue_scripts', array( $my_wploti_maintenance_redirect ,'wploti_enqueue_style_and_script_public' ) );	
	//register script for translation
	add_action('admin_enqueue_scripts', array( $my_wploti_maintenance_redirect ,'wploti_translations_script' ) );	

	//add_filter('plugin_action_links_'.plugin_basename(__FILE__), array( $my_wploti_maintenance_redirect, 'plugin_settings_link' ) );
	add_filter('site_status_tests', array( $my_wploti_maintenance_redirect, 'wploti_add_site_health' ) );
	add_filter('admin_body_class',  array( $my_wploti_maintenance_redirect,'wploti_body_class' ) );
	add_filter('login_message', array( $my_wploti_maintenance_redirect, 'login_message'));
	add_filter('upload_mimes', array( $my_wploti_maintenance_redirect, 'wploti_mime_types'));
	add_filter('plugin_action_links_' . plugin_basename(__FILE__), array( $my_wploti_maintenance_redirect, 'wploti_action_links' ) );
	add_filter('plugin_row_meta', array( $my_wploti_maintenance_redirect, 'wploti_plugin_row_meta' ), 10, 2 );


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
	add_action('wp_ajax_wploti_uploaded_animation_save', array( $my_wploti_maintenance_redirect, 'wploti_uploaded_animation_save_option') );
	add_action('wp_ajax_wploti_add_whitelisted_roles', array( $my_wploti_maintenance_redirect, 'wploti_add_whitelisted_roles_option') );
	add_action('wp_ajax_wploti_remove_whitelisted_roles', array( $my_wploti_maintenance_redirect, 'wploti_remove_whitelisted_roles_option') );
	add_action('wp_ajax_wploti_add_whitelisted_users', array( $my_wploti_maintenance_redirect, 'wploti_add_whitelisted_users_option') );
	add_action('wp_ajax_wploti_remove_whitelisted_users', array( $my_wploti_maintenance_redirect, 'wploti_remove_whitelisted_users_option') );
	add_action('wp_ajax_wploti_logout_users', array( $my_wploti_maintenance_redirect, 'wploti_logout_users_callback') );
	
	// activation & deactivation 
	register_activation_hook( __FILE__, array( $my_wploti_maintenance_redirect, 'init' ) );
	register_deactivation_hook( __FILE__, array( $my_wploti_maintenance_redirect, 'wploti_deactivate' ) );

	// Reset Settings action
	add_action( 'wp_ajax_wploti_reset_settings', array( $my_wploti_maintenance_redirect, 'reset_plugin_settings' ) );

	// Translation
    add_action( 'plugins_loaded', array( $my_wploti_maintenance_redirect, 'wploti_translation' ));

	add_action('admin_head', array( $my_wploti_maintenance_redirect, 'add_site_favicon'));
	//add_action('wp_default_scripts', array( $my_wploti_maintenance_redirect, 'remove_jquery_migrate_console') );
	

}
