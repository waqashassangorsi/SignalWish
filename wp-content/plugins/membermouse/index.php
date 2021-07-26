<?php
/**
 * @package MemberMouse
 * @version 2.3.3
 *
 * Plugin Name: MemberMouse Platform
 * Plugin URI: http://membermouse.com
 * Description: MemberMouse is an enterprise-level membership platform that allows you to quickly and easily manage a membership site or subscription business. MemberMouse is designed to deliver digital content, automate customer self-service and provide you with advanced marketing tools to maximize the profitability of your continuity business.
 * Author: MemberMouse, LLC
 * Version: 2.3.3
 * Author URI: http://membermouse.com
 * Text Domain: membermouse
 * Domain Path: /languages/
 * Copyright: 2009-2019 MemberMouse LLC. All rights reserved.
 */

require_once("includes/mm-constants.php");
require_once("includes/mm-functions.php");
require_once("lib/class.membermousestream.php");
require_once("includes/init.php");


if(isLocalInstall())
{
	error_reporting(E_ALL);
	ini_set("display_errors","On");
}

if (class_exists("MM_DiagnosticLog") && MM_DiagnosticLog::isEnabled())
{	
	function membermouseDiagnosticCapture()
	{
		$error = @error_get_last();
	    if (($error != null) && ($error['type'] & (E_ERROR | E_USER_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_RECOVERABLE_ERROR)))
	    {
	    	@MM_DiagnosticLog::logItem($error['file'],MM_DiagnosticLog::$PHP_ERROR, $error['message'], $error['line']);   
	    } 
	}
	register_shutdown_function('membermouseDiagnosticCapture');
	$previous = set_error_handler(function ($errno, $errstr, $errfile, $errline, $errcontext) use (&$previous) {
	    MM_DiagnosticLog::logPHPErrors($errno, $errstr, $errfile, $errline, $errcontext);
	    
	    // If another error handler was defined, call it, otherwise use the default PHP error handler
	    if ($previous) 
	    {
	        return $previous($errno, $errstr, $errfile, $errline, $errcontext);
	    } 
	    return false;
	});
}

if(!class_exists('MemberMouse',false)) 
{
	class MemberMouse 
	{
 		private static $menus=""; 
		private $option_name = 'membermouse-settings';
		private $defaults = array('count'=>10, 'append'=>1);
		private $metaname = '_associated_membermouse';
		private $installerRan = false;
		private static $pluginVersion = "2.3.3";

		public function __construct() 
		{
			// check if the plugin has been upgraded to a new major version and if so, run the installer
			if(!isset($_GET["release"]))
			{
				$crntMajorVersion = self::getPluginVersion();
				$lastMajorVersion = MM_OptionUtils::getOption(MM_OptionUtils::$OPTION_KEY_MAJOR_VERSION);
				if (!empty($lastMajorVersion) && (version_compare($lastMajorVersion, $crntMajorVersion) < 0))
				{
					$this->install();
				}
			}
			
			if(class_exists("MM_MemberMouseService") && class_exists("MM_License"))
			{
				$license = new MM_License();
				if (strpos($_SERVER["PHP_SELF"],"plugins.php") === false)
				{
					MM_MemberMouseService::validateLicense($license);
				}
				
				if($license->isValid())
				{
					if(MM_MemberMouseService::hasPermission(MM_MemberMouseService::$FEATURE_PHP_INTERFACE))
					{
						require_once("includes/php_interface.php");
					}
				}
			}
			
			$this->addActions();
			$this->addFilters();
		}

		public function addFilters() 
		{
			global $wpdb;
			
			if (class_exists("MM_TagProcessor"))
			{
				remove_filter('the_title', 'do_shortcode', 9);
				remove_filter('wp_title', 'do_shortcode', 9);
					
				add_filter('the_title', 'MM_TagProcessor::processSmartTags', 9);
				add_filter('wp_title', 'MM_TagProcessor::processSmartTags', 9);
				add_filter('the_content', 'MM_TagProcessor::processSmartTags', 9);
				
				add_filter('document_title_parts', function($title)
				{
				    if (is_array($title))
				    {
				        $title = array_map(array("MM_TagProcessor","processSmartTags"),$title);
				    }
				    return $title;
				    
				}, 9, 1 ); 
			}
			
			$user_hooks = new MM_UserHooks();
			add_filter('login_redirect', array($user_hooks, 'loginRedirect'), 1, 3);
			add_filter('template_redirect', array($user_hooks, 'handlePageAccess'));
			
			$useMMLoginPage = MM_OptionUtils::getOption(MM_OptionUtils::$OPTION_KEY_USE_MM_LOGIN_PAGE);
			if($useMMLoginPage == "1")
			{
				add_filter('login_url',  array($user_hooks, "loginUrl"),  1, 2);
			}
			
			add_filter('logout_url',  array($user_hooks, "logoutUrl"),  1, 2);

			// add WP menu filters
			$showLoginLogoutLink = MM_OptionUtils::getOption(MM_OptionUtils::$OPTION_KEY_SHOW_LOGIN_LOGOUT_LINK);
			if($showLoginLogoutLink == "1")
			{
				add_filter('wp_nav_menu_items', array($user_hooks, 'showLoginLogoutLinkInMenu'), 10, 2);
			}
			
			$hideMenuItems = MM_OptionUtils::getOption(MM_OptionUtils::$OPTION_KEY_HIDE_PROTECTED_MENU_ITEMS);
			if($hideMenuItems == "1")
			{
				add_filter('wp_setup_nav_menu_item', array($user_hooks, 'hideProtectedMenuItems'));
			}
			
			add_filter('the_title', 'do_shortcode', 9);
			add_filter('wp_title', 'do_shortcode', 9);
			
			// add MM link footer
			if(class_exists("MM_MemberMouseService") && MM_MemberMouseService::hasPermission(MM_MemberMouseService::$SHOW_MM_FOOTER) == MM_MemberMouseService::$ACTIVE)
			{
				add_filter('wp_footer', array($this,'addMMFooter'), 9);
			}
			
// 			add_filter('mm_stripe_billing_statement_descriptor', function($descriptor, $order){ 
// 				return "Your Order Description - ".array_pop($order->orderProducts)->description;
// 			},1,2);
			
			$post_hook = new MM_PostHooks();
			add_filter('rest_prepare_post', array($post_hook, "doRestFilter"), 12, 3);
			add_filter('rest_prepare_page', array($post_hook, "doRestFilter"), 12, 3);
			add_filter('manage_posts_columns', array($post_hook,'postsColumns'), 5);
			add_filter('manage_pages_columns', array($post_hook,'pagesColumns'), 5);
			add_filter('posts_where', array($post_hook, 'handlePostWhere'), 1, 1);
			add_filter('wp_head', array($post_hook, "checkPosts"));
			
			add_filter('the_content', array($this, "filterContent"), 10);
			
			//prevent members with pending status from logging in
			add_filter('authenticate', array($user_hooks,'checkLogin'), 100, 3);
 
			//prevent wptexturize from running on mm_form tags
			add_filter( 'no_texturize_shortcodes', function ($shortcodes) {
				$shortcodes[] = 'MM_Form';
				return $shortcodes;
			});
			
			//default password strength validation - minimum of 8 chars
			if (class_exists("MM_Filters"))
			{
			     add_filter(MM_Filters::$PASSWORD_STRENGTH_VALIDATOR,array('MM_UserHooks',"passwordStrengthValidator"),10,1);
			}
					
		}
		
		public function addActions() 
		{
			if(function_exists("add_action"))
			{  
  				// Hook for PHPMailer to detect for any SMTP plugins that might be configured to send mail via SSL (Ex. AWS SES).
  				// add_action( 'phpmailer_init', array('MM_Email', 'mm_phpMailer'), 5 );
  			
				add_filter("plugin_action_links", array($this, "updateVersion"), 5, 3);
				
				$user_hooks = new MM_UserHooks();
				add_action('wp_login_failed', array($user_hooks,'loginFailed'), 9999);
				add_action( 'authenticate', array($user_hooks,'authenticateLogin'));
				
				//session functions
				add_action('init',function() {
					if (MM_Session::sessionExists())
					{
						MM_Session::sessionStart();
					}
				});
				
				add_action( 'init', function() {
					load_plugin_textdomain( 'membermouse', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
				});
				
				add_action('shutdown', array('MM_Session','sessionWrite'));
				add_action('wp_logout', array($this, "reapSession"));
					
				add_action('init', array($user_hooks, "doAutoLogin"));
				add_action('init', array($user_hooks, "doAutoLogout"));
				add_action('init', array($user_hooks, "removeWPAutoPOnCorePages"));
				add_action("init", array($user_hooks, "setupDefinitions"));
				add_action("template_redirect", array($user_hooks, "checkCorePageTypeInput"));
				add_action("wp_footer", array($user_hooks, "pageBasedActions"));
				add_action( 'delete_user', array($user_hooks, "deleteUser"));
				add_action( 'profile_update', array($user_hooks, "handleProfileUpdate"));
				
				add_action("wp_head", array($this, "loadPreviewBar"));
				add_action("admin_bar_menu", array($this, "customizeAdminBar"));
				
				add_action("wp_enqueue_scripts", array($this, 'loadResources'));
				
				if (MM_Utils::inAdmin())
				{
					add_action("admin_enqueue_scripts", array($this, 'loadResources'));
					add_action('admin_init', array($user_hooks, "checkEmployeeAccess"));
					add_action('admin_menu', array($this, 'buildAdminMenu'));
					add_action("admin_notices", array($this, "showNotices"));
					add_action("admin_notices", array($this, "activationFailed"));
					add_action("admin_init", array($this, 'configurePostMeta'));
				}
				
				if (class_exists("MM_SmartTagLibraryView"))
				{
					$smartTagLibrary = new MM_SmartTagLibraryView();
					add_action("admin_footer", array($smartTagLibrary, "addDialogContainers"));
					add_action('media_buttons_context', array($smartTagLibrary, "customMediaButtons"));
				}
				
				if (class_exists("MM_PaymentUtilsView"))
				{
					$paymentUtils = new MM_PaymentUtilsView();
					add_action("admin_footer", array($paymentUtils, "addDialogContainers"));
					add_action("wp_footer", array($paymentUtils, "addDialogContainers"));
				}
				
				$post_hook = new MM_PostHooks();
				add_action("trashed_post", array($post_hook, "trashPostHandler"));
				add_action("deleted_post", array($post_hook, "deletePostHandler"));
				add_action('manage_posts_custom_column', array($post_hook,'postCustomColumns'), 5, 2);
				add_action('manage_pages_custom_column', array($post_hook,'postCustomColumns'), 5, 2);
				add_action('restrict_manage_posts', array($post_hook,'editPostsFilter'));
				add_action('publish_page',array($post_hook,'publishPageHandler'),10,2);
				
				/// saveCorePages
				if(class_exists("MM_CorePageEngine"))
				{
					$corePageEngine = new MM_CorePageEngine();
					add_action('save_post', array($corePageEngine, 'saveCorePages'),10,2);
					
					if (MM_Utils::inAdmin())
					{
						add_action( 'update_option_permalink_structure' , array("MM_CorePageEngine","createCorePageCache")); 
					}
				}
				
				if(class_exists("MM_ProtectedContentEngine"))
				{
					$protectedContent = new MM_ProtectedContentEngine();
					add_action('save_post', array($protectedContent, 'saveSmartContent'),10,2);
				}
				
				add_action('wp_ajax_module-handle', array($this, 'handleAjaxCallback'));
				add_action('wp_ajax_nopriv_module-handle', array($this, 'handleAjaxCallback'));
				add_action('wp_ajax_member-types', array($this, 'handleAjaxCallback'));	
				
				// load MemberMouse widgets
				if(class_exists("MM_SmartWidget"))
				{
					add_action('widgets_init', function() 
					{
					    return register_widget("MM_SmartWidget");
					});
					
				}
				
				if(class_exists("MM_DripContentWidget"))
				{
					add_action('widgets_init', function()
				    {
				        return register_widget("MM_DripContentWidget");
				    });
				}
				
				// add event listeners
				if(class_exists("MM_Event"))
				{
					if(class_exists("MM_PushNotificationEngine"))
					{
						$pne = new MM_PushNotificationEngine();
						add_action(MM_Event::$MEMBER_ADD, array($pne, 'memberAdded'), 100, 2);
						add_action(MM_Event::$MEMBER_STATUS_CHANGE, array($pne, 'memberStatusChanged'), 100, 2);
						add_action(MM_Event::$MEMBER_MEMBERSHIP_CHANGE, array($pne, 'memberMembershipChanged'), 100, 2);
						add_action(MM_Event::$MEMBER_ACCOUNT_UPDATE, array($pne, 'memberAccountUpdated'), 100, 2);
						add_action(MM_Event::$MEMBER_DELETE, array($pne, 'memberDeleted'), 100, 2);
						add_action(MM_Event::$BUNDLE_ADD, array($pne, 'bundleAdded'), 100, 2);
						add_action(MM_Event::$BUNDLE_STATUS_CHANGE, array($pne, 'bundleStatusChanged'), 100, 2);
						add_action(MM_Event::$PAYMENT_RECEIVED, array($pne, 'paymentReceived'), 100, 2);
						add_action(MM_Event::$PAYMENT_REBILL, array($pne, 'rebillPaymentReceived'), 100, 2);
						add_action(MM_Event::$PAYMENT_REBILL_DECLINED, array($pne, 'rebillPaymentDeclined'), 100, 2);
						add_action(MM_Event::$REFUND_ISSUED, array($pne, 'refundIssued'), 100, 2);
						add_action(MM_Event::$BILLING_SUBSCRIPTION_UPDATED, array($pne, 'billingSubscriptionUpdated'), 100, 2);
						add_action(MM_Event::$COMMISSION_INITIAL, array($pne, 'initialCommission'), 100, 2);
						add_action(MM_Event::$COMMISSION_REBILL, array($pne, 'rebillCommission'), 100, 2);
						add_action(MM_Event::$CANCEL_COMMISSION, array($pne, 'cancelCommission'), 100, 2);
						add_action(MM_Event::$PRODUCT_PURCHASE, array($pne, 'productPurchase'), 100, 2);
						
						// isset methods are only necessary when customers are upgrading from MM 2.1.1 to 2.1.2. This can safely be removed
						// once all customers are on 2.1.2 or above
						if(isset(MM_Event::$BILLING_SUBSCRIPTION_REBILL_DATE_CHANGED))
						{
							add_action(MM_Event::$BILLING_SUBSCRIPTION_REBILL_DATE_CHANGED, array($pne, 'billingSubscriptionRebillDateChanged'), 100, 2);
						}
						
						if(isset(MM_Event::$BILLING_SUBSCRIPTION_CANCELED))
						{
							add_action(MM_Event::$BILLING_SUBSCRIPTION_CANCELED, array($pne, 'billingSubscriptionCanceled'), 100, 2);
						}
						
						if(isset(MM_Event::$AFFILIATE_INFO_CHANGED))
						{
							add_action(MM_Event::$AFFILIATE_INFO_CHANGED, array($pne, 'affiliateInfoChanged'), 100, 2);
						}
					}
					
					if(class_exists("MM_CronEngine") && class_exists("MM_MemberMouseService"))
					{
						//add a special filter for the scheduled event queue check
						add_filter('cron_schedules', array('MM_CronEngine','addQueueCheckIntervalRecurrenceOption'));
						MM_CronEngine::setup();
					}
					
					if(class_exists("MM_AffiliateController"))
					{
						MM_AffiliateController::setup();
					}
					
					if(class_exists("MM_EmailServiceProviderController"))
					{
						MM_EmailServiceProviderController::setup();
					}
					
					if(class_exists("MM_MemberDetailsView"))
					{
						$mdv = new MM_MemberDetailsView();
						add_action(MM_Event::$PAYMENT_REBILL, array($mdv, 'handleRebillPaymentReceivedEvent'), 10, 2);
						add_action(MM_Event::$PAYMENT_REBILL_DECLINED, array($mdv, 'handleRebillPaymentDeclinedEvent'), 10, 2);
					}
				}
				
				//add payment service hooks
				if (class_exists("MM_PaymentService"))
				{
					add_action('init', array('MM_PaymentService', "performInitActions"), 9);
				}
				
				//// capture any session changes to the override key 
				if (class_exists("MM_PaymentServiceFactory"))
				{
					add_action('wp_loaded', array('MM_PaymentServiceFactory', "syncPaymentServiceKeyOveride"));
				}
				
				//add extension hooks
				if(class_exists("MM_Extension"))
				{
					add_action("init", array('MM_Extension', "performInitActions"), 9);
				}
				
				add_action( 'init', array($this,"loadTextDomain") );
				// add_filter( 'gettext', array($this,"filterTranslation"), 20, 3 );
				
				register_activation_hook( __FILE__, array($this, 'install'));
				register_deactivation_hook( __FILE__, array($this, 'onDeactivate'));				
			 
				
				add_action('wp_footer', function() 
				{
					if (MM_Session::sessionExists() && (MM_Session::$MM_SESSION_DELAYED_CREATE === true))
					{
						echo MM_Session::generateDelayedCreateJavascript();
					}
				}); 
				
				//async task manager hook
				add_action('admin_post_mm_async_request', array("MM_AsyncTaskManager","handleAsyncRequest"));
			}
		} 
		
		public function filterTranslation($translatedText, $text, $domain)
		{
    		return $translatedText;
		}
		
		
		public function loadTextDomain()
		{
			load_plugin_textdomain("mm",false,dirname(plugin_basename(__FILE__))."/languages"); 
		}
		
		
		public function addMMFooter($content)
		{
			echo "<div style='width: 100%; padding-top: 10px; text-align:right; height: 50px; font-size: 12px;'>
	<a href=\"http://www.membermouse.com?ref=".urlencode(get_option("siteurl"))."&src=badge\">Membership Software</a> Provided by MemberMouse
	&nbsp;&nbsp;
</div>";
		}
		
		public function filterContent($content)
		{
			global $wp_query; 
			
			if(!is_feed() && !is_search() && !is_archive())
			{
				return $content;
			}
			
			$protectedContent = new MM_ProtectedContentEngine();
			$postId = $wp_query->query_vars["page_id"];
		
			if ($protectedContent->protectContent($postId))
			{
			    $wpPost = get_post($postId);
				setup_postdata($wpPost);
				
				if($wpPost && ($wpPost->post_status == "publish" || $wpPost->post_status == "inherit")
						&& ($wpPost->post_type == "post" || $wpPost->post_type == "page" || MM_Utils::isCustomPostType($wpPost->post_type)))
				{ 
				    $excerptPosition = strpos($wpPost->post_content, "<!--more");
				    if (($excerptPosition !== false) && ($protectedContent->hasMoreTag($wpPost->post_content)))
				    {  
				        return substr($wpPost->post_content, 0, $excerptPosition)." <a href=\"".get_permalink($postId)."\">Read More</a>";
				    }				
					return $content;
				}
				else 
				{
					return $content;	
				}
			}
			
			$post = get_post($postId);
			setup_postdata($post);
			$excerptPosition = strpos($post->post_content, "<!--more");
			if (($excerptPosition !== false) && ($protectedContent->hasMoreTag($post->post_content)))
			{
			    return substr($post->post_content, 0, $excerptPosition)." <a href=\"".get_permalink($postId)."\">Read More</a>";
			}
			
			return "This content is for members only";
		}
		
		public function reapSession()
		{
			MM_Session::sessionReap(MM_Session::getSessionId());
		}
		
		public function updateVersion($a,$b,$c)
		{
			if ($b==plugin_basename(__FILE__))
			{
				$crntMajorVersion = self::getPluginVersion();
				$upgradeVersion = MM_OptionUtils::getOption(MM_OptionUtils::$OPTION_KEY_UPGRADE_NOTICE);
				if(!empty($upgradeVersion) && $crntMajorVersion != $upgradeVersion)
				{
					$current = get_site_transient('update_plugins');
					@$current->response["membermouse/index.php"]->slug = "membermouse";
					@$current->response["membermouse/index.php"]->package = MM_CENTRAL_SERVER_URL."/major-versions/".$upgradeVersion.".zip";
					@$current->response["membermouse/index.php"]->new_version = $upgradeVersion;
					set_site_transient('update_plugins', $current);
				}
			}
			return $a;
		}
		
		public function checkVersion()
		{
			if(class_exists("MM_MemberMouseService", false))
			{
				$crntMajorVersion = self::getPluginVersion();
				$upgradeVersion = MM_OptionUtils::getOption(MM_OptionUtils::$OPTION_KEY_UPGRADE_NOTICE);
				if(!empty($upgradeVersion) && $crntMajorVersion != $upgradeVersion)
				{
					if($upgradeVersion !== false)
					{
						$current = get_site_transient( 'update_plugins' );
						@$current->response["membermouse/index.php"]->slug = "membermouse";
						@$current->response["membermouse/index.php"]->package = MM_PRETTY_CENTRAL_SERVER_URL."/major-versions/".$upgradeVersion.".zip";
						@$current->response["membermouse/index.php"]->new_version = $upgradeVersion;
						set_site_transient('update_plugins',$current);
					}
				}
			}
		}
		
		public function loadPreviewBar()
		{
			if(class_exists("MM_PreviewView"))
			{
				if((MM_Employee::isEmployee() == true || current_user_can('manage_options')) && !is_admin()) 
				{
					MM_PreviewView::show();
				}
			}
		}
		
		public function customizeAdminBar()
		{
			if(MM_Employee::isEmployee())
			{
				global $wp_admin_bar;
				
				$wp_admin_bar->add_menu( array(
					'id'    => 'mm-menu',
					'title' => '<img src="'.MM_Utils::getImageUrl('mm-logo-svg').'" style="width:22px; margin-bottom:2px;" />',
					'href'  => MM_ModuleUtils::getUrl(MM_MODULE_DASHBOARD),
					'meta'  => array('title' => __('MemberMouse')),
				));
				
				$wp_admin_bar->add_menu(array(
					"id" => "mm-manage-members",
					"title" => "Manage Members",
					"href" => MM_ModuleUtils::getUrl(MM_MODULE_MANAGE_MEMBERS),
					"parent" => "mm-menu"
				));
				
				$wp_admin_bar->add_menu(array(
					"id" => "mm-browse-transactions",
					"title" => "Browse Transactions",
					"href" => MM_ModuleUtils::getUrl(MM_MODULE_MANAGE_TRANSACTIONS),
					"parent" => "mm-menu"
				));
				
				$wp_admin_bar->add_menu(array(
					"id" => "mm-reporting-suite",
					"title" => "Reporting Suite",
					"href" => MM_ModuleUtils::getUrl(MM_MODULE_REPORTING),
					"parent" => "mm-menu"
				));
				
				$wp_admin_bar->add_menu(array(
					"id" => "mm-product-settings",
					"title" => "Product Settings",
					"href" => MM_ModuleUtils::getUrl(MM_MODULE_PRODUCT_SETTINGS),
					"parent" => "mm-menu"
				));
				
				$wp_admin_bar->add_menu(array(
					"id" => "mm-support-center",
					"title" => "Support Center",
					"href" => "http://support.membermouse.com/support/home",
					"parent" => "mm-menu",
					"meta" => array("target" => "blank")
				));
			}
			
			if(!is_admin() && MM_OptionUtils::getOption(MM_OptionUtils::$OPTION_KEY_SHOW_PREVIEW_BAR) == "1" && MM_Employee::isEmployee())
			{
			?>
			<style>
			#wpadminbar {
				background: linear-gradient(to top, #373737 0px, #464646 0px) repeat scroll 0 0 #464646;
				background-image: -webkit-linear-gradient(bottom,#373737 0,#464646 1px);
				border-bottom: 1px #555555 solid;
			}
			
			#wpadminbar .ab-top-secondary {
				background: linear-gradient(to top, #373737 0px, #464646 0px) repeat scroll 0 0 #464646;
				background-image: -webkit-linear-gradient(bottom,#373737 0,#464646 1px);
				border-bottom: 1px #555555 solid;
			}
			
			body {
				margin-top: 34px;
			}
			</style>
			<script type='text/javascript'>
			jQuery(document).ready(function() {
				mmPreviewJs.hideNonMemberItems();
			});
			</script>
			<?php
			}
		}
		
		public function loadResources()
		{
			global $wp_scripts;
			
			$customCssFiles = array();
			$customCssFiles["main"] = 'resources/css/common/mm-main.css';
			$customCssFiles["buttons"] = 'resources/css/common/mm-buttons.css';
			
			$module = MM_ModuleUtils::getModule();
			if ($module == MM_MODULE_REPORTING)
			{
				$customCssFiles["reporting"] = 'resources/css/admin/reporting/mm-reporting.css';
			}
			
			$useJQueryUI = MM_OptionUtils::getOption(MM_OptionUtils::$OPTION_KEY_USE_JQUERY_UI);
			
			if ($useJQueryUI == "1" || is_admin())
			{
				if (function_exists("wp_scripts"))
				{
					wp_scripts();
				}
				
				$jquery_ui_version = "1.11.2"; //fallback version
				if ($wp_scripts instanceof WP_Scripts)
				{
					//if available, get registered script object for jquery-ui, use that to load the appropriate theme version from CDN to match the js version
					$jqueryUICore = $wp_scripts->query('jquery-ui-core');
					$jquery_ui_version = isset($jqueryUICore->ver)?$jqueryUICore->ver:$jquery_ui_version;
				}
				wp_enqueue_style("membermouse-jquery-css", "//ajax.googleapis.com/ajax/libs/jqueryui/{$jquery_ui_version}/themes/" . MM_JQUERY_UI_THEME . "/jquery-ui.css", array(), $jquery_ui_version);
			}
			$version = self::getPluginVersion();
			
			foreach ($customCssFiles as $cssId=>$cssFile)
			{
				wp_enqueue_style("membermouse-".$cssId, plugins_url($cssFile, __FILE__), array(), $version);
			}
			
			$subfolder = ($module == MM_MODULE_REPORTING)?"reporting/":"";
			if(file_exists(MM_PLUGIN_ABSPATH."/resources/css/admin/{$subfolder}mm-".$module.".css")) 
			{
				wp_enqueue_style('membermouse-'.$module, plugins_url("resources/css/admin/{$subfolder}mm-".$module.'.css', __FILE__), array());	
			}
			
			wp_enqueue_style('membermouse-font-awesome', '//maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css', array());
			
			$this->loadJavascript($module);
		}
		
		public function loadJavascript($module="") 
		{
			$isAdminArea = is_admin();
			
			$url = MM_OptionUtils::getOption("siteurl");
			$adminUrl = admin_url();
			
			if(isset($_SERVER["HTTP_HOST"]))
			{
				$thisUrl = $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"];
				if (strpos($thisUrl,"http") === false)
				{
					$thisUrl = (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on")?"https://{$thisUrl}":"http://{$thisUrl}";
				}
			}
			
			if ((isset($_SERVER["HTTPS"]) && ($_SERVER["HTTPS"] == "on")) || (strpos($thisUrl,"https") !== false))
			{
				$url = str_replace("http:","https:",$url);
				$adminUrl = str_replace("http:","https:",$adminUrl);
			}
			
			//first include global script
			$version = self::getPluginVersion(); //use plugin major version to control caching
			wp_enqueue_script("membermouse-global", plugins_url("/resources/js/global.js",__FILE__), array('jquery'),$version);
			$javascriptData = array("jsIsAdmin"=>$isAdminArea,
					  "adminUrl" =>$adminUrl,
					  "globalurl"=>MM_PLUGIN_URL,
					  "checkoutProcessingPaidMessage" => MM_OptionUtils::getOption(MM_OptionUtils::$OPTION_KEY_CHECKOUT_PAID_MESSAGE),
					  "checkoutProcessingFreeMessage" => MM_OptionUtils::getOption(MM_OptionUtils::$OPTION_KEY_CHECKOUT_FREE_MESSAGE),
					  "checkoutProcessingMessageCSS" => MM_OptionUtils::getOption(MM_OptionUtils::$OPTION_KEY_CHECKOUT_MESSAGE_CSS)
					);
			if (($module == MM_MODULE_REPORTING) && function_exists("_mmt"))
			{
			     $javascriptData += array("reportingNoDataMessage" => _mmt("No data was returned"));  
			}
			
			if (class_exists("MM_CurrencyUtil"))
			{
				$javascriptData["currencyInfo"] = MM_CurrencyUtil::getActiveCurrencyMetadata();
			}
			wp_localize_script("membermouse-global","MemberMouseGlobal",$javascriptData);
			
			$jsFiles = array();
			$userJSDir = "/resources/js/user/";
			$adminJSDir = "/resources/js/admin/";
			$commonJSDir = "/resources/js/common/";
			
			if ((isLocalInstall() && file_exists(MM_PLUGIN_ABSPATH."{$commonJSDir}class.js")) || !file_exists(MM_PLUGIN_ABSPATH."{$commonJSDir}mm-common-core.js"))
			{
				$commonJsFiles = array(
					'jquery.bgiframe-3.0.1.js' => array(),
					'jquery.ajaxfileupload.js' => array(),
					'class.js' => array(),
					'mm-cache.js'=> array(),
					'mm-main.js'=> array(),
					'class.ajax.js'=> array('class.js'),
					'mm-dialog.js'=> array('class.js'),
					'mm-core.js'=> array('class.js'),
					'mm-iframe.js'=> array('class.js'),
					'class.form.js'=> array('class.js'),
					'mm-smarttag_library.js'=> array('mm-core.js','mm-dialog.js'),
					'mm-payment_utils.js'=> array('mm-core.js'),
				);
			}
			else 
			{
				$commonJsFiles = array('mm-common-core.js' => array());	
			}

			$useJQueryUI = MM_OptionUtils::getOption(MM_OptionUtils::$OPTION_KEY_USE_JQUERY_UI);
				
			$wordPressjQueryUiHandles = array();
				
			if($useJQueryUI == "1" || is_admin())
			{
				$wordPressjQueryUiHandles[] = 'jquery-ui-accordion';
        	 	$wordPressjQueryUiHandles[] = 'jquery-ui-button';
				$wordPressjQueryUiHandles[] = 'jquery-ui-datepicker';
				$wordPressjQueryUiHandles[] = 'jquery-ui-dialog';
				$wordPressjQueryUiHandles[] = 'jquery-ui-draggable';
				$wordPressjQueryUiHandles[] = 'jquery-ui-droppable';
				$wordPressjQueryUiHandles[] = 'jquery-ui-mouse';
				$wordPressjQueryUiHandles[] = 'jquery-ui-position';
				$wordPressjQueryUiHandles[] = 'jquery-ui-progressbar';
				$wordPressjQueryUiHandles[] = 'jquery-ui-resizable';
				$wordPressjQueryUiHandles[] = 'jquery-ui-selectable';
				$wordPressjQueryUiHandles[] = 'jquery-ui-sortable';
				$wordPressjQueryUiHandles[] = 'jquery-ui-widget';
			}
			
			foreach($commonJsFiles as $file => $dependencies)
			{
				if(file_exists(MM_PLUGIN_ABSPATH."{$commonJSDir}{$file}"))
				{
					$jsFiles[plugins_url("{$commonJSDir}{$file}", __FILE__)] = $dependencies;
				}
			}
			
			if(!$isAdminArea)
			{
				$userFiles = array(
					'mm-preview.js',
				);
				
				foreach ($userFiles as $file)
				{
					if (file_exists(MM_PLUGIN_ABSPATH."{$userJSDir}{$file}"))
					{
						$jsFiles[plugins_url("{$userJSDir}{$file}", __FILE__)] = array();
					}
				}
			}
			else
			{
				// load UserVoice SDK
				$jsFiles["//widget.uservoice.com/xCTpbo1WnzFyomHpkrBryQ.js"] = array();
				$jsFiles[plugins_url("{$adminJSDir}mm-corepages.js", __FILE__)] = array();
				$jsFiles[plugins_url("{$adminJSDir}mm-accessrights.js", __FILE__)] = array();
				
				if (!empty($module))
				{
					// load JavaScript classes dynamically based on the module
					$moduleJSDir = $adminJSDir;
					$jsFileName = $module;
					if ($module == MM_MODULE_REPORTING)
					{
						$jsFiles[plugins_url("{$adminJSDir}mm-reportjsbase.js", __FILE__)] = array();
						$moduleJSDir.="/reports/";
						$jsFileName = MM_ModuleUtils::getPage();
					}
					
					if (file_exists(MM_PLUGIN_ABSPATH."{$moduleJSDir}mm-{$jsFileName}.js"))
					{
						$jsFiles[plugins_url("{$moduleJSDir}mm-{$jsFileName}.js", __FILE__)] = array();
					}
				}
			}
			
			foreach ($jsFiles as $file_to_include => $dependencies)
			{
				$scriptname = basename($file_to_include);
				$allDependencies = is_array($dependencies)?$dependencies:array($dependencies);
				$allDependencies = array_merge(array('membermouse-global'),$allDependencies);
				wp_enqueue_script($scriptname, $file_to_include, $allDependencies, $version);
			}
			
			foreach($wordPressjQueryUiHandles as $handle)
			{
				wp_enqueue_script($handle);
			}
		}
		
		public function onDeactivate()
		{	
	 		if(class_exists("MM_MemberMouseService"))
	 		{
		 		MM_DiagnosticLog::log(MM_DiagnosticLog::$MM_ERROR,"plugin deactivated from index::onDeactivate()");
				MM_MemberMouseService::deactivatePlugin();
	 		}
	 		
	 		// clean up MM crons
	 		MM_CronEngine::cleanup();
		}
		
		public function activationFailed()
		{
			if(isset($_GET[MM_Session::$PARAM_COMMAND_DEACTIVATE]) && isset($_GET[MM_Session::$PARAM_MESSAGE_KEY]))
			{
				echo "<div class='updated'>";
				echo "<p>".urldecode($_GET[MM_Session::$PARAM_MESSAGE_KEY])."</p>";
				echo "</div>";
				MM_DiagnosticLog::log(MM_DiagnosticLog::$MM_ERROR,"plugin deactivated from index::activationFailed()");
				@deactivate_plugins(MM_PLUGIN_ABSPATH."/index.php", false);
			}
		}
		
		public function showNotices()
		{
			$this->checkVersion();
			
			if(!function_exists("hash_hmac"))
			{
			    if (class_exists("MM_PaymentServiceFactory",false) && class_exists("MM_ScheduledPaymentService",false))
			    {
			        $activePaymentService = MM_PaymentServiceFactory::getOnsitePaymentService();
			        if ($activePaymentService instanceof MM_ScheduledPaymentService)
			        {
			            $translatableHeader = _mmt("MemberMouse Missing Components");
			            $translatableMsg = _mmt("hash_hmac PHP function and sha256 algorithm are not available on your server. Contact your hosting provide to address this");
			            $learnMoreMsg = _mmt("Learn More");
			            $learnMoreUrl = "http://support.membermouse.com/solution/articles/9000147182-warning-sha-256-and-hash-hmac-are-not-available-on-your-server";

			            $errorMsg = "<i class=\"fa fa-exclamation-triangle\"></i> <strong>{$translatableHeader}</strong>";
			            $errorMsg .= "<div style='margin-left:20px;'>";
			            
		                $errorMsg .= "<i class=\"fa fa-caret-right\"></i> {$translatableMsg} ";
		                $errorMsg .= "<a href='{$learnMoreUrl}'>{$learnMoreMsg}</a><br/>";
			            
			            $errorMsg .= "</div>";
			            MM_Messages::addError($errorMsg);
			        }
			    }
			}
			else
			{
				$algorithms = hash_algos();
				if (is_array($algorithms) && !in_array("sha256",$algorithms))
				{
				    if (class_exists("MM_PaymentServiceFactory",false) && class_exists("MM_ScheduledPaymentService",false))
				    {
				        $activePaymentService = MM_PaymentServiceFactory::getOnsitePaymentService();
				        if ($activePaymentService instanceof MM_ScheduledPaymentService)
				        {
				            $translatableHeader = _mmt("MemberMouse Missing Components");
				            $translatableMsg = _mmt("The sha256 algorithm is not available on your server. Contact your hosting provide to address this");
				            $learnMoreMsg = _mmt("Learn More");
				            $learnMoreUrl = "http://support.membermouse.com/solution/articles/9000147182-warning-sha-256-and-hash-hmac-are-not-available-on-your-server";
				            
				            $errorMsg = "<i class=\"fa fa-exclamation-triangle\"></i> <strong>{$translatableHeader}</strong>";
				            $errorMsg .= "<div style='margin-left:20px;'>";
				            
				            $errorMsg .= "<i class=\"fa fa-caret-right\"></i> {$translatableMsg} ";
				            $errorMsg .= "<a href='{$learnMoreUrl}'>{$learnMoreMsg}</a><br/>";
				            
				            $errorMsg .= "</div>";
				            MM_Messages::addError($errorMsg);
				        }
				    }				    
				}
			}
		
			// check to see if cache is being used
			$writeableDir = MM_PLUGIN_ABSPATH."/com/membermouse/cache";
        	$usingDbCache = false;
        	if (class_exists("MM_Session"))
        	{
        		$usingDbCache = MM_Session::value(MM_Session::$KEY_USING_DB_CACHE);
        		if (empty($usingDbCache))
        		{
        			$usingDbCache = false;
        		}
        	}
        	
        	if(!isset($_GET['module']) || ($_GET['module'] != MM_MODULE_REPAIR_INSTALL))
        	{
        		$cacheRepairUrl = MM_ModuleUtils::getUrl(MM_MODULE_GENERAL_SETTINGS, MM_MODULE_REPAIR_INSTALL);
	        	if(!file_exists($writeableDir) || (is_dir($writeableDir) && !is_writeable($writeableDir)))
	        	{
	        		MM_Messages::addMessage("Currently MemberMouse can't utilize the cache. <a href='{$cacheRepairUrl}'>Click here to correct this.</a>");
	        		if (!file_exists($writeableDir))
	        		{
	        			@mkdir($writeableDir);	//if the cache directory is missing, attempt to create it silently if possible
	        		}
	        	}
	        	else if($usingDbCache)
	        	{
	        		//this means the dbcache is in use, but the cache is now writeable, show banner and see if refresh is available
	        		MM_Messages::addMessage("Currently MemberMouse can't utilize the cache. <a href='{$cacheRepairUrl}'>Click here to correct this.</a>");
	        		
	        		$lastAuth = MM_OptionUtils::getOption(MM_OptionUtils::$OPTION_KEY_LAST_CODE_REFRESH);
					$minInterval = time() - 60; //(1 min)
					
					if (class_exists("MM_MemberMouseService") && (empty($lastAuth) || ($lastAuth <= $minInterval)))
					{
						$refreshSuccess = MM_MemberMouseService::authorize();
						MM_OptionUtils::setOption(MM_OptionUtils::$OPTION_KEY_LAST_CODE_REFRESH, time());
					}
					
					MM_Session::clear(MM_Session::$KEY_USING_DB_CACHE);
	        	}
        	}
        	
        	// check to see if this is a beta version
        	if(MM_IS_BETA == true)
        	{
        		$mmVersion = self::getPluginVersion();
        		$msg = "<div style='width:750px;'><em class='mm-beta'>beta</em><strong>MemberMouse {$mmVersion}</strong>";
        		$msg .= "<div style='margin-left:20px; margin-top:5px; line-height:22px;'>This is a Beta version of MemberMouse.<br/>";
        		$msg .= "<i class=\"fa fa-caret-right\"></i> <a href='http://membermouse.com/beta-release-notes.php?version={$mmVersion}' target='_blank'>Beta {$mmVersion} Release Notes</a></div>";
        		$msg .= "</div>";
        		MM_Messages::addError($msg);
        	}
			
        	// check to see if there's a new version of MM available
			if(class_exists("MM_MemberMouseService"))
			{
				// check if there's an upgrade available
				$crntMajorVersion = self::getPluginVersion();
				$upgradeVersion = MM_OptionUtils::getOption(MM_OptionUtils::$OPTION_KEY_UPGRADE_NOTICE);
				
				if(!empty($upgradeVersion))
				{
					if ((version_compare($upgradeVersion, $crntMajorVersion, ">")) 
							|| (MM_IS_BETA == true && version_compare($upgradeVersion, $crntMajorVersion, "==")))
					{
						// verify that customer is on PHP 5.3+
						if ((double)phpversion() < 5.3)
						{
							$phpWarning = "<div style='width:750px;'><i class=\"fa fa-exclamation-triangle\"></i> <strong>Warning:</strong> A new version of MemberMouse is available. In order to upgrade to the latest version of MemberMouse you will need to upgrade to PHP 5.3 or higher.</div>";
							MM_Messages::addError($phpWarning);
						}
						else if ((strpos($_SERVER["PHP_SELF"],"plugins.php") === false) && !(isset($_GET["action"]) && ($_GET["action"] == "upgrade-plugin")))
						{
							MM_Messages::addMessage("<a href='http://support.membermouse.com/support/solutions/articles/9000020462-membermouse-versions' target='_blank'>MemberMouse {$upgradeVersion}</a> is available! <a href='plugins.php?plugin_update=membermouse&version={$upgradeVersion}'>Please update now</a>.");
						}
					}
				}
				
				// check if plugin needs to be upgraded
				global $wpdb;
				
				$sql = "SELECT count(u.wp_user_id) as total FROM ".MM_TABLE_USER_DATA." u, ".MM_TABLE_MEMBERSHIP_LEVELS." m WHERE ";
				$sql .= "u.membership_level_id = m.id AND (u.status = '".MM_Status::$ACTIVE."' OR u.status = '".MM_Status::$PENDING_CANCELLATION."') ";
				
				$result = $wpdb->get_row($sql);
				
				if($result)
				{
					$activeMembers = intval($result->total);
					$memberLimit = intval(MM_MemberMouseService::getMemberLimit());
					$upgradeUrl = MM_MemberMouseService::getUpgradeUrl();
					if($memberLimit != -1 && $activeMembers > $memberLimit)
					{
						MM_Messages::addMessage("MemberMouse is currently over the limit of ".number_format($memberLimit)." members and will be deactivated within a week of going over the limit. Please <a href='{$upgradeUrl}' target='_blank'>upgrade your account</a> to avoid any service interruptions.");
					}
				}
				
				// check to see if in Safe Mode
				$safeMode = MM_SafeMode::getMode();
				
				if($safeMode == MM_SafeMode::$MODE_ENABLED)
				{
					$safeModeUrl = MM_ModuleUtils::getUrl(MM_MODULE_GENERAL_SETTINGS, MM_MODULE_SAFE_MODE);
					MM_Messages::addError("<i class=\"fa fa-life-saver\"></i> MemberMouse Safe Mode is Enabled. <a href='{$safeModeUrl}'>Safe Mode Settings</a>");
				}
				
				
				//check to see if payment subsystem is in test mode or if test data is being used.
				$testPaymentSrvcEnabled = (class_exists("MM_TestPaymentService") && MM_TestPaymentService::isSiteUsingTestService());
				$testDataEnabled = (MM_OptionUtils::getOption(MM_OptionUtils::$OPTION_KEY_USE_CHECKOUT_FORM_TEST_DATA) == "1") ? true : false;
				
				if ($testPaymentSrvcEnabled || $testDataEnabled)
				{
					$errorMsg = "<i class=\"fa fa-flask\"></i> <strong>MemberMouse Testing Enabled</strong>";
					$errorMsg .= "<div style='margin-left:20px;'>";
					
					if($testPaymentSrvcEnabled)
					{
						$paymentSettingsUrl = MM_ModuleUtils::getUrl(MM_MODULE_PAYMENT_SETTINGS, MM_MODULE_PAYMENT_METHODS);
						$errorMsg .= "<i class=\"fa fa-caret-right\"></i> Test Payment Service is enabled. ";
						$errorMsg .= "All charges will be processed by the test payment service. ";
						$errorMsg .= "<a href='{$paymentSettingsUrl}'>Payment Method Settings</a><br/>";
					}
					
					if($testDataEnabled)
					{
						$testDataSettingsUrl = MM_ModuleUtils::getUrl(MM_MODULE_PAYMENT_SETTINGS, MM_MODULE_TEST_DATA);
						$errorMsg .= "<i class=\"fa fa-caret-right\"></i> Test Data is enabled. All checkout forms will be prepopulated with test data. <a href='{$testDataSettingsUrl}'>Test Data Settings</a>";
					}
					
					$errorMsg .= "</div>";
					MM_Messages::addError($errorMsg);
				}
			}
			
			// check PHP version
			if(( double ) phpversion () < 5.3)
			{
				$phpWarning = "<div style='width:750px;'><i class=\"fa fa-exclamation-triangle\"></i> <strong>Warning:</strong> Your webserver is running PHP ";
				$phpWarning .= phpversion();
				$phpWarning .= ", which is an obsolete version of PHP. MemberMouse isn't compatible with versions of PHP lower than 5.3 and you will experience issues ";
				$phpWarning .= "using the MemberMouse plugin. Please contact your hosting provider and request a more recent version of PHP. ";
				$phpWarning .= "For more information, <a href='http://support.membermouse.com/support/solutions/articles/9000020502-end-of-php-5-2-support' target='_blank'>click here</a>.</div>";
				MM_Messages::addError($phpWarning);
			}
			
			// check to see if any trouble plugins are activated
			MM_Utils::getPluginWarnings();
			
			// get error messages
			$errors = MM_Messages::get(MM_Session::$KEY_ERRORS);
			
			$output = "";
			
			if(is_array($errors) && count($errors) > 0)
			{	
				$output .= "<div class=\"error\">";
				foreach($errors as $msg)
				{
					$output .= "<p>{$msg}</p>";
				}
				$output .= "</div>";
			}
			
			// get notices
			$messages = MM_Messages::get(MM_Session::$KEY_MESSAGES);
			
			if(is_array($messages) && count($messages) > 0)
			{
				$output .= "<div class=\"updated\">";
				foreach($messages as $msg)
				{
					$output .= "<p>{$msg}</p>";
				}
				$output .= "</div>";
			}
			
			echo $output;
			
			MM_Messages::clear();
		}
		
		public function configurePostMeta()
		{	
			if(is_admin() && class_exists("MM_ProtectedContentView"))
			{
				$protectedContentView = new MM_ProtectedContentView();
				add_meta_box('membermouse_post_access', __('MemberMouse Options'), array($protectedContentView, 'postPublishingBox'), 'post', 'side', 'high');
				add_meta_box('membermouse_post_access', __('MemberMouse Options'), array($protectedContentView, 'postPublishingBox'), 'page', 'side', 'high');
				
				// add meta box to custom post types
				$args = array(
					'public'   => true,
					'_builtin' => false
				);

				$post_types = get_post_types($args, 'names', 'and');
				
				foreach ($post_types as $post_type) 
				{
					add_meta_box('membermouse_post_access', __('MemberMouse Options'), array($protectedContentView, 'postPublishingBox'), $post_type, 'side', 'high');
				}
				
				$commonJSDir = "/resources/js/common/";
				$dependency  = ((isLocalInstall() && file_exists(MM_PLUGIN_ABSPATH."{$commonJSDir}mm-smarttag_library.js")) || !file_exists(MM_PLUGIN_ABSPATH."{$commonJSDir}mm-common-core.js")) ? array('mm-smarttag_library.js') : array('mm-common-core.js');
				$version = self::getPluginVersion(); //use plugin major version to control caching
				wp_enqueue_script('membermouse-postmeta', plugins_url('resources/js/admin/mm-accessrights.js', __FILE__), $dependency,$version);
				wp_enqueue_script('membermouse-corepages', plugins_url('resources/js/admin/mm-corepages.js', __FILE__), $dependency,$version);	
			}
		}
		
		public function install()
		{
			if(class_exists("MM_Install") && !$this->installerRan)
			{
				$installer = new MM_Install();
				$installer->activate();
				$this->installerRan = true;
			}
		}

		public function handleAjaxCallback()
		{
			$response = array();
			$data = stripslashes_deep($_REQUEST);
				
			if(isset($data["module"]))
			{
				if(isset($data["type"]) && $data["type"]=="displayonly")
				{
					$obj = new $data["module"]();
					$ret = $obj->callMethod($data);
					
					if($ret instanceof MM_Response)
					{
						echo $ret->message;
					}
					else 
					{
						echo $ret;
					}
						
					exit();
				}
				else if(class_exists($data["module"]) && class_exists("MM_View") && is_subclass_of($data["module"], "MM_View"))
				{
					$obj = new $data["module"]();
					$response = $obj->callMethod($data);
				}
			}
			
			if (function_exists("wp_json_encode"))
			{
				echo wp_json_encode($response);
			}
			else 
			{
				echo json_encode($response);	
			}
			exit();
		}
		
		public function buildAdminMenu() 
		{
			if (!isset($_GET[MM_Session::$PARAM_COMMAND_DEACTIVATE]))
			{
				global $current_user;

				$crntModule = MM_ModuleUtils::getPage();
				
				if($crntModule == MM_MODULE_GENERAL_SETTINGS)
				{
					add_thickbox();
				}
				
				if (class_exists("MM_Employee"))
				{
					$employee = MM_Employee::findByUserId($current_user->ID);
					$employee->buildMenu();
				}
			}
		}
		
		
		public static function allowJavascriptProtocol($protocols) 
		{
			$protocols[] = "javascript";
			return $protocols;
		}

		
		public static function getPluginVersion()
		{
			return self::$pluginVersion;
		}
	}
	//Allow javascript pseudo-protocol
	add_filter('kses_allowed_protocols', 'MemberMouse::allowJavascriptProtocol');
	$mmplugin = new MemberMouse();
}
?>