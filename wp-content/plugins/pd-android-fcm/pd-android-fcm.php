<?php
/*
* @package pd-android-fcm
* Plugin Name: pd Android FCM Push Notification
* Plugin URI: https://proficientdesigners.in/creations/pd-android-fcm-push-notification/
* Description: pd Android FCM Push Notification is a plugin through which you can send push notifications directly from your WordPress site to android devices via <a href='https://firebase.google.com/' target='_blank'>Firebase Cloud Messaging</a> service. When a new blog is posted or existing blog is updated, a push notification sent to android device.
* Version: 1.1.8
* WC requires at least: 3.4.2
* WC tested up to: 4.6.1
* Author: Proficient Designers
* Author URI: https://proficientdesigners.com/
* Licence: GPLv2 or Later
* Text Domain: pd-android-fcm
*/

/**
 * Version checks
 */
global $wp_version;

if (version_compare($wp_version, '4.0', '<')) {
	wp_die(
		'Sorry, <b>pd Android FCM Push Notification</b> plugin requires WordPress 4.0 or newer. <p><a class="button" href="http://codex.wordpress.org/Upgrading_WordPress">Please update!</a></p>',
		'Warning !!',
		['back_link' => true]
	);
}

if (version_compare(phpversion(), '5.6', '<')) {
	wp_die(
		'Sorry, <b>pd Android FCM Push Notification</b> plugin requires php version 5.6 or above',
		'Warning !!',
		['back_link' => true]
	);
}
/**
 * * ABSPATH check
 */
defined('ABSPATH') || wp_die('Sorry, you can\'t do what you want to do !!.', 'Warning !!', ['back_link' => true]);

/**
 * pd Android FCM Plugin Class
 */
if (!class_exists('PDANDROIDFCM')) {
	class PDANDROIDFCM
	{
		public function __construct()
		{
			add_action('init', [$this, 'pdandroidfcm_cpt']);
			add_action('init', [$this, 'pdandroidfcm_send_push_notification_cpt']);
			add_action('init', [$this, 'create_subscriptions_hierarchical_taxonomy'], 0);
			/*Registering Custom Columns*/
			add_filter('manage_pdandroidfcm_posts_columns', 'pdandroidfcm_set_columns');
			add_action('manage_pdandroidfcm_posts_custom_column', 'pdandroidfcm_custom_columns', 10, 2);
			add_action('manage_edit-pdandroidfcm_sortable_columns', 'pdandroidfcm_make_registered_column_sortable', 10, 1);

			/*Registering Custom Metaboxes*/
			add_action('add_meta_boxes', 'pdandroidfcm_add_meta_box', 10, 1);
			/*add_action( 'save_post', 'pdandroidfcm_device_data' );*/
			add_filter('plugin_action_links', [$this, 'pdandroidfcm_plugin_add_settings_link'], 10, 5);
			add_filter('bulk_actions-edit-post', [$this, 'bulk_action']);
			//check for this
			add_filter('post_row_actions', [$this, 'remove_row_actions'], 10, 2);
			// wp footer
			add_action('admin_footer', [$this, 'pd_fcm_js_scripts']);
		}

		public function activate()
		{
			/*register custom database*/
			$this->create_pdandroidfcm_table();
			/*flush rewrite rules to avoid rare conflicts*/
			flush_rewrite_rules();
		}
		public function remove_row_actions($actions, $post)
		{
			if ($post->post_type == 'pdandroidfcm') {
				unset($actions['edit']);
				unset($actions['view']);
				unset($actions['trash']);
				unset($actions['inline hide-if-no-js']);
				return $actions;
			} else {
				return $actions;
			}
		}

		public function bulk_action($actions)
		{
			$actions['send_fcm_notification'] = 'Send FCM Notification';
			return $actions;
		}

		public function pdandroidfcm_cpt()
		{

			$post_type = 'pdandroidfcm';

			$labels = [
				'name' 					=> __('All Registered Devices'),
				'singular_name' 		=> __('Device'),
				'add_new'				=> __('Add New Device'),
				'add_new item'			=> __('Add New Device'),
				'search_items' 			=> __('Search for Email'),
				'edit_item'				=> __('Edit Device'),
				'new_item' 				=> __('Device'),
				'menu_name'				=> __('pd Android FCM'),
				'all_items'          	=> __('All Devices'),
				'name_custom_bar'		=> __('Device'),
				'not_found'           	=> __('No device(s) found'),
				'not_found_in_trash'  	=> __('No device(s) found in Trash')
			];

			$args = [
				'labels'				=> $labels,
				'show_ui'				=> true,
				'show_in_menu'			=> true,
				'capability_type'		=> 'post',
				'hierarchical'			=> false,
				'menu_position'			=> 27,
				'public'				=> false,
				'has_archive'			=> false,
				'publicaly_querable'	=> false,
				'query_var'				=> false,
				'supports'				=> false,
				'menu_icon' 			=> 'data:image/svg+xml;base64,PHN2ZyB2ZXJzaW9uPSIxLjEiIGlkPSJMYXllcl8xIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHhtbG5zOnhsaW5rPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5L3hsaW5rIiB4PSIwcHgiIHk9IjBweCINCgkgdmlld0JveD0iMCAwIDIwIDIwIiBzdHlsZT0iZW5hYmxlLWJhY2tncm91bmQ6bmV3IDAgMCAyMCAyMDsiIHhtbDpzcGFjZT0icHJlc2VydmUiPg0KPHN0eWxlIHR5cGU9InRleHQvY3NzIj4NCgkuc3Qwe2ZpbGw6I2ZmZmZmZjt9DQo8L3N0eWxlPg0KPGc+DQoJPGc+DQoJCTxwYXRoIGNsYXNzPSJzdDAiIGQ9Ik0xLjA3LDYuOTdjMC4yOSwwLDAuNTQsMC4xMSwwLjc1LDAuMzFzMC4zMSwwLjQ2LDAuMzEsMC43NXY0LjQ2YzAsMC4zLTAuMSwwLjU1LTAuMzEsMC43Ng0KCQkJYy0wLjIxLDAuMjEtMC40NSwwLjMxLTAuNzUsMC4zMXMtMC41NS0wLjExLTAuNzYtMC4zMUMwLjExLDEzLjAzLDAsMTIuNzgsMCwxMi40OFY4LjAyYzAtMC4yOSwwLjExLTAuNTQsMC4zMS0wLjc1DQoJCQlDMC41Miw3LjA3LDAuNzcsNi45NywxLjA3LDYuOTd6IE05LjY1LDIuOTdjMC43NCwwLjM4LDEuMzMsMC45MSwxLjc3LDEuNTljMC40NCwwLjY4LDAuNjYsMS40MiwwLjY2LDIuMjNIMi41DQoJCQljMC0wLjgxLDAuMjItMS41NSwwLjY2LTIuMjNDMy42MSwzLjg4LDQuMiwzLjM1LDQuOTQsMi45N0w0LjIxLDEuNjFDNC4xNiwxLjUxLDQuMTcsMS40NSw0LjI2LDEuNGMwLjA5LTAuMDQsMC4xNi0wLjAyLDAuMjEsMC4wNg0KCQkJbDAuNzUsMS4zN0M1Ljg2LDIuNTQsNi41NiwyLjQsNy4yOSwyLjRzMS40MywwLjE1LDIuMDgsMC40M2wwLjc1LTEuMzdjMC4wNS0wLjA4LDAuMTItMC4xMSwwLjIxLTAuMDYNCgkJCWMwLjA4LDAuMDUsMC4xLDAuMTIsMC4wNSwwLjIxTDkuNjUsMi45N3ogTTEyLjA1LDcuMTZ2Ni45YzAsMC4zMi0wLjExLDAuNTktMC4zMywwLjgxYy0wLjIyLDAuMjItMC40OSwwLjMzLTAuOCwwLjMzaC0wLjc4djIuMzUNCgkJCWMwLDAuMy0wLjExLDAuNTUtMC4zMSwwLjc2Yy0wLjIxLDAuMjEtMC40NiwwLjMxLTAuNzYsMC4zMXMtMC41NS0wLjExLTAuNzYtMC4zMWMtMC4yMS0wLjIxLTAuMzEtMC40Ni0wLjMxLTAuNzZWMTUuMkg2LjU5djIuMzUNCgkJCWMwLDAuMy0wLjExLDAuNTUtMC4zMSwwLjc2Yy0wLjIxLDAuMjEtMC40NiwwLjMxLTAuNzYsMC4zMWMtMC4yOSwwLTAuNTQtMC4xMS0wLjc1LTAuMzFjLTAuMjEtMC4yMS0wLjMxLTAuNDYtMC4zMS0wLjc2DQoJCQlMNC40NSwxNS4ySDMuNjljLTAuMzIsMC0wLjU5LTAuMTEtMC44MS0wLjMzYy0wLjIyLTAuMjItMC4zMy0wLjQ5LTAuMzMtMC44MXYtNi45SDEyLjA1eiBNNC44Myw0Ljk0QzQuOTEsNS4wMiw1LDUuMDYsNS4xMSw1LjA2DQoJCQljMC4xMSwwLDAuMjEtMC4wNCwwLjI4LTAuMTJjMC4wOC0wLjA4LDAuMTItMC4xNywwLjEyLTAuMjhTNS40Nyw0LjQ1LDUuMzksNC4zN0M1LjMxLDQuMjksNS4yMiw0LjI1LDUuMTEsNC4yNQ0KCQkJQzUsNC4yNSw0LjksNC4yOSw0LjgzLDQuMzdDNC43NSw0LjQ1LDQuNzIsNC41NCw0LjcyLDQuNjVTNC43NSw0Ljg2LDQuODMsNC45NHogTTkuMiw0Ljk0YzAuMDgsMC4wOCwwLjE3LDAuMTIsMC4yOCwwLjEyDQoJCQljMC4xMSwwLDAuMi0wLjA0LDAuMjgtMC4xMmMwLjA4LTAuMDgsMC4xMS0wLjE3LDAuMTEtMC4yOFM5Ljg0LDQuNDUsOS43Niw0LjM3UzkuNTksNC4yNSw5LjQ4LDQuMjVTOS4yNyw0LjI5LDkuMiw0LjM3DQoJCQlDOS4xMiw0LjQ1LDkuMDgsNC41NCw5LjA4LDQuNjVTOS4xMiw0Ljg2LDkuMiw0Ljk0eiBNMTQuNTksOC4wMnY0LjQ2YzAsMC4zLTAuMTEsMC41NS0wLjMxLDAuNzZjLTAuMjEsMC4yMS0wLjQ2LDAuMzEtMC43NiwwLjMxDQoJCQljLTAuMjksMC0wLjU0LTAuMTEtMC43NS0wLjMxYy0wLjIxLTAuMjEtMC4zMS0wLjQ2LTAuMzEtMC43NlY4LjAyYzAtMC4zLDAuMTEtMC41NSwwLjMxLTAuNzVjMC4yMS0wLjIxLDAuNDYtMC4zMSwwLjc1LTAuMzENCgkJCWMwLjMsMCwwLjU1LDAuMSwwLjc2LDAuMzFDMTQuNDksNy40NywxNC41OSw3LjczLDE0LjU5LDguMDJ6Ii8+DQoJPC9nPg0KCTxnPg0KCQk8cGF0aCBjbGFzcz0ic3QwIiBkPSJNMTIuNDgsNC4wMWMwLjA0LTAuMDIsMC4wNy0wLjA0LDAuMTEtMC4wNGw3LjE0LTAuNzZjMC4xLTAuMDEsMC4xNywwLjAyLDAuMjIsMC4xMWMwLjA1LDAuMDksMC4wNSwwLjE3LDAsMC4yNQ0KCQkJbC0wLjg0LDEuMzZsLTUuMjYtMC40NGw0Ljk0LDEuM2wwLjc3LDEuMDVjMC4wMywwLjA0LDAuMDUsMC4wOSwwLjA1LDAuMTRzLTAuMDEsMC4xLTAuMDQsMC4xNGMtMC4wMSwwLjAyLTAuMDMsMC4wNC0wLjA1LDAuMDYNCgkJCWMtMC4wNiwwLjA1LTAuMTMsMC4wNi0wLjIsMC4wNGwtMS4zOC0wLjM2bC0wLjk2LDEuNTZjLTAuMDIsMC4wMy0wLjA0LDAuMDUtMC4wNiwwLjA3Yy0wLjAzLDAuMDItMC4wNywwLjA0LTAuMTEsMC4wNQ0KCQkJYy0wLjA4LDAuMDEtMC4xNC0wLjAxLTAuMi0wLjA2bC00LjE1LTQuMDdjLTAuMDctMC4wNy0wLjA5LTAuMTYtMC4wNi0wLjI1QzEyLjQxLDQuMDgsMTIuNDQsNC4wNCwxMi40OCw0LjAxeiIvPg0KCTwvZz4NCjwvZz4NCjwvc3ZnPg0K',
				'taxonomies'			=> array('subscriptions'),
				'capabilities' => array(
					'create_posts' 		=> false
				),
				'map_meta_cap'        	=> true
			];

			if (current_user_can('manage_woocommerce') || current_user_can('activate_plugins')) {
				register_post_type($post_type, $args);
			}
		}

		public function pdandroidfcm_send_push_notification_cpt()
		{

			$post_type = 'pdandroidfcm_msg';

			$labels = [
				'name' 					=> __('Notifications'),
				'singular_name' 		=> __('Notification'),
				'add_new'				=> __('Create New Notification Message'),
				'add_new item'			=> __('Create New Notification Message'),
				'search_items' 			=> __('Search for Sent Notification'),
				'edit_item'				=> __('Edit Notification'),
				'new_item' 				=> __('Notification'),
				'menu_name'				=> __('Push Notifications'),
				'name_custom_bar'		=> __('Notifications '),
				'not_found'           	=> __('No Notification(s) found'),
				'not_found_in_trash'  	=> __('No Notification(s) found in Trash')
			];

			$args = [
				'labels'				=> $labels,
				'show_ui'				=> true,
				'show_in_menu'			=> true,
				'capability_type'		=> 'post',
				'hierarchical'			=> false,
				'public'				=> false,
				'has_archive'			=> false,
				'publicaly_querable'	=> false,
				'query_var'				=> false,
				'menu_position'			=> 28,
				'supports'				=> array('title', 'excerpt', 'featured_image', 'thumbnail'),
				'show_in_menu' 			=> 'edit.php?post_type=pdandroidfcm',
				'taxonomies'			=> array('subscriptions')
			];

			if (current_user_can('manage_woocommerce') || current_user_can('activate_plugins')) {
				register_post_type($post_type, $args);
			}
		}

		public function create_subscriptions_hierarchical_taxonomy()
		{

			$labels = [
				'name' 					=> _x('Subscriptions', 'taxonomy general name'),
				'singular_name' 		=> _x('Subscription', 'taxonomy singular name'),
				'search_items' 			=>  __('Search Subscriptions'),
				'all_items' 			=> __('All Subscriptions'),
				'parent_item' 			=> __('Parent Subscription'),
				'parent_item_colon' 	=> __('Parent Subscription:'),
				'edit_item' 			=> __('Edit Subscription'),
				'update_item' 			=> __('Update Subscription'),
				'add_new_item' 			=> __('Add New Subscription'),
				'new_item_name' 		=> __('New Subscription Name'),
				'menu_name' 			=> __('Subscriptions'),
			];

			register_taxonomy(
				'subscriptions',
				[],
				[
					'hierarchical' 		=> true,
					'parent_item'  		=> false,
					'parent_item_colon' => false,
					'labels' 			=> $labels,
					'show_ui' 			=> true,
					'show_admin_column' => true,
					'show_in_rest' 		=> true,
					'query_var' 		=> true,
					'rewrite' 			=> array('slug' => 'subscriptions'),
				]
			);
		}

		public function create_pdandroidfcm_table()
		{
			require_once plugin_dir_path(__FILE__) . 'db.php';
		}

		public function pd_fcm_js_scripts()
		{
?>
			<script>
				let generatePdFcmApi = document.getElementById('generatePdFcmApi');
				if (generatePdFcmApi) {
					generatePdFcmApi.addEventListener('click', function(e) {
						e.preventDefault();
						let ajaxUrl = '<?php echo admin_url("admin-ajax.php") ?>';
						let xhttp = new XMLHttpRequest();
						xhttp.onreadystatechange = function() {
							if (this.readyState == 4 && this.status == 200) {
								let StringResponse = xhttp.responseText;
								let jsonResponse = JSON.parse(StringResponse);
								if (jsonResponse.success === true) {
									document.getElementById("PD_FCM_API_SECRET_KEY").value = jsonResponse.data;
								}
							}
						}
						let params = '?action=generate_pd_fcm_api';
						xhttp.open("GET", ajaxUrl + params, true);
						xhttp.send();
					});
				}
				const copy_url = (element) => {
					var text = document.querySelector(element);
					var selection = window.getSelection();
					var range = document.createRange();
					range.selectNodeContents(text);
					selection.removeAllRanges();
					selection.addRange(range);
					document.execCommand('copy');
				};
				document.getElementById('PD_FCM_ENVIRONMENT').addEventListener('change', function(e) {
					document.getElementById('PD_FIREBASE_API_KEY').closest('tr').style.display = 'none';
				});
			</script>
<?php
		}

		public function pdandroidfcm_plugin_add_settings_link($actions, $plugin_file)
		{
			static $plugin;

			if (!isset($plugin))

				$plugin = plugin_basename(__FILE__);

			if ($plugin == $plugin_file) {

				$settings 	= ['settings' => '<a href="edit.php?post_type=pdandroidfcm&page=settings">' . __('Settings') . '</a>'];
				$docs_link 	= ['docs' => '<a href="https://proficientdesigners.in/creations/pd-android-fcm-push-notification/" target="_blank">' . __('Documentation') . '</a>'];

				$actions = array_merge($settings, $actions);
				$actions = array_merge($docs_link, $actions);
			}

			return $actions;
		}
	}
	$pdandroidfcm = new PDANDROIDFCM();
}

// activation
register_activation_hook(__FILE__, [$pdandroidfcm, 'activate']);

/*----------------------------------------------------*/

foreach (glob(plugin_dir_path(__FILE__) . "ajax/*.php") as $file) {
	include $file;
}

foreach (glob(plugin_dir_path(__FILE__) . "custom/*.php") as $file) {
	include $file;
}

foreach (glob(plugin_dir_path(__FILE__) . "class/*.php") as $file) {
	include $file;
}

foreach (glob(plugin_dir_path(__FILE__) . "wp_api/*.php") as $file) {
	include $file;
}

foreach (glob(plugin_dir_path(__FILE__) . "functions/*.php") as $file) {
	include $file;
}

require plugin_dir_path(__FILE__) . 'pages.php';
