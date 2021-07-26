<?php
define('WP_CACHE', true); // WP-Optimize Cache
 // WP-Optimize Cache
 // WP-Optimize Cache
define('WP_AUTO_UPDATE_CORE', 'minor');// This setting is required to make sure that WordPress updates can be properly managed in WordPress Toolkit. Remove this line if this WordPress website is not managed by WordPress Toolkit anymore.
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the
 * installation. You don't have to use the web site, you can
 * copy this file to "wp-config.php" and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * MySQL settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://codex.wordpress.org/Editing_wp-config.php
 *
 * @package WordPress
 */
define( 'WP_DEBUG', false );
// ** MySQL settings ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'wp_wsknv' );
/** MySQL database username */
define( 'DB_USER', 'wp_0ovff' );
/** MySQL database password */
define( 'DB_PASSWORD', 'EO6BUocr7GA?PY&4' );
/** MySQL hostname */
define( 'DB_HOST', 'localhost:3306' );
/** Database Charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );
/** The Database Collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );
/**
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY', 'jM!9m7qq3a|5)TLm0xy~wwKEEN:!7o6Oji!2:)Tk;7+G79Nl[ltA0(hoY)/IUL|5');
define('SECURE_AUTH_KEY', ';05p*pP30e*8G||A]78rb+0--u4f+2]8bUx#035)66A70L]9-w7;ez[J533Ec3hX');
define('LOGGED_IN_KEY', '#(_#k8wb@tkd8#q40TC)dh4~1|UVc8x7x*Z3*A|@-+T|l35ia869u1V/l_#:2g8#');
define('NONCE_KEY', '37L*cw02q7/d/*]rOM~:v3s9(H~D2Z0I11P62R55P_02Cc8vIY7!cE/mHo&OU-jK');
define('AUTH_SALT', ')HWY)I8!7%@TzmY6d3M/#9-G8tc:Pg%+CBrph1A9!7s9gaOW8j;1bFK7/:n~&Otl');
define('SECURE_AUTH_SALT', 'F(o:ohY2o#04+1*]XAfT]]R(zIMl7mxiQ73Hl0e@C(_+GUkKM2+888RJ12*%d57#');
define('LOGGED_IN_SALT', 'F3_8N58#1c0|*_W[@yhe~1u_y&2w@79;~24yuv*JS_aU9O%59I5RVe#ZT1]#+H2B');
define('NONCE_SALT', '_r92ogBDyg6N6Q3LwCMUoYE3L!a(SV;|64709j_!F33-B4:E0Lp/3-TDJ_03rNKM');
/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = '2JQEQ7Bn_';
/* That's all, stop editing! Happy blogging. */
/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) )
	define( 'ABSPATH', dirname( __FILE__ ) . '/' );
/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';