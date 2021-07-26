<?php
/**
 * Plugin Name: Schema Pro
 * Plugin URI: https://wpschema.com
 * Author: Brainstorm Force
 * Author URI: https://www.brainstormforce.com
 * Description: Integrate Schema.org JSON-LD code in your website and improve SEO.
 * Version: 2.1.1
 * Text Domain: wp-schema-pro
 * License: GPL2
 *
 * @package Schema Pro
 */

/**
 * If this file is called directly, abort.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

// Refresh bundled products on activate.
register_activation_hook( __FILE__, 'on_bsf_aiosrs_pro_activate' );
register_deactivation_hook( __FILE__, 'on_bsf_aiosrs_pro_deactivate' );

/**
 * Bsf aiosrs pro activate
 *
 * @since 1.1.0
 * @return void
 */
function on_bsf_aiosrs_pro_activate() {
	update_site_option( 'bsf_force_check_extensions', true );
	set_transient( 'wp-schema-pro-activated', true );
	BSF_AIOSRS_Pro_Helper::bsf_schema_pro_set_default_options();
}

/**
 * Bsf aiosrs pro deactivate
 *
 * @since 1.3.0
 * @return void
 */
function on_bsf_aiosrs_pro_deactivate() {
	delete_option( 'sp_hide_label' );

	if ( is_network_admin() ) {
		$branding = get_site_option( 'wp-schema-pro-branding-settings' );
	} else {
		$branding = get_option( 'wp-schema-pro-branding-settings' );
	}

	if ( isset( $branding['sp_hide_label'] ) && false !== $branding['sp_hide_label'] ) {

		$branding['sp_hide_label'] = 'disabled';

		if ( is_network_admin() ) {

			update_site_option( 'wp-schema-pro-branding-settings', $branding );

		} else {
			update_option( 'wp-schema-pro-branding-settings', $branding );
		}
	}
}
/**
 * Set constants.
 */
define( 'BSF_AIOSRS_PRO_FILE', __FILE__ );
define( 'BSF_AIOSRS_PRO_BASE', plugin_basename( BSF_AIOSRS_PRO_FILE ) );
define( 'BSF_AIOSRS_PRO_DIR', plugin_dir_path( BSF_AIOSRS_PRO_FILE ) );
define( 'BSF_AIOSRS_PRO_URI', plugins_url( '/', BSF_AIOSRS_PRO_FILE ) );
define( 'BSF_AIOSRS_PRO_VER', '2.1.1' );
define( 'BSF_AIOSRS_PRO_CACHE_KEY', 'wp_schema_pro_optimized_structured_data' );

/**
 * Initial file.
 */
require_once BSF_AIOSRS_PRO_DIR . 'classes/class-bsf-aiosrs-pro-helper.php';
require_once BSF_AIOSRS_PRO_DIR . 'classes/class-bsf-aiosrs-pro.php';


if ( is_admin() ) {
	// Load Astra Notices library.
	require_once BSF_AIOSRS_PRO_DIR . '/lib/notices/class-astra-notices.php';
}

/**
 * BSF analytics.
 */
require_once BSF_AIOSRS_PRO_DIR . 'admin/bsf-analytics/class-bsf-analytics.php';

/**
 * Brainstorm Updater.
 */
require_once BSF_AIOSRS_PRO_DIR . 'classes/class-brainstorm-update-aiosrs-pro.php';
