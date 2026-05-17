<?php
/**
 * Plugin Name: BHDT Core System
 * Plugin URI:  https://banhangdientu.com
 * Description: Core automation engine for multi-source technical reviews, specifications comparison matrices, and interactive Maker utility tools.
 * Version:     2.0.1
 * Author:      Vo Van Tuan & Copilot
 * Text Domain: bhdt-core-system
 *
 * @package BHDT_Core_System
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BHDT_CORE_SYSTEM_VERSION', '2.0.1' );
define( 'BHDT_PATH', plugin_dir_path( __FILE__ ) );
define( 'BHDT_URL', plugin_dir_url( __FILE__ ) );

require_once BHDT_PATH . 'includes/class-cpt-init.php';
require_once BHDT_PATH . 'includes/class-api-receiver.php';
require_once BHDT_PATH . 'includes/class-assets.php';
require_once BHDT_PATH . 'includes/class-shortcodes.php';
require_once BHDT_PATH . 'includes/class-template-loader.php';

if ( ! class_exists( 'BHDT_Core_System' ) ) {
	/**
	 * Main plugin loader.
	 */
	final class BHDT_Core_System {
		/**
		 * Singleton instance.
		 *
		 * @var BHDT_Core_System|null
		 */
		private static $instance = null;

		/**
		 * Get singleton instance.
		 *
		 * @return BHDT_Core_System
		 */
		public static function instance() {
			if ( null === self::$instance ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Constructor.
		 */
		private function __construct() {
			BHDT_CPT_Init::init();
			BHDT_API_Receiver::init();
			BHDT_Assets::init();
			BHDT_Shortcodes::init();
			BHDT_Template_Loader::init();

			register_activation_hook( __FILE__, array( __CLASS__, 'activate' ) );
			register_deactivation_hook( __FILE__, array( __CLASS__, 'deactivate' ) );
		}

		/**
		 * Activation callback.
		 *
		 * @return void
		 */
		public static function activate() {
			BHDT_CPT_Init::register_post_types();
			flush_rewrite_rules();
		}

		/**
		 * Deactivation callback.
		 *
		 * @return void
		 */
		public static function deactivate() {
			flush_rewrite_rules();
		}
	}
}

BHDT_Core_System::instance();
