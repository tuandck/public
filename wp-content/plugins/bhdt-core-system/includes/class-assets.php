<?php
/**
 * Frontend assets module.
 *
 * @package BHDT_Core_System
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'BHDT_Assets' ) ) {
	/**
	 * Handles public CSS/JS registrations.
	 */
	final class BHDT_Assets {
		/**
		 * Boot hooks.
		 *
		 * @return void
		 */
		public static function init() {
			add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_assets' ) );
		}

		/**
		 * Register plugin frontend assets.
		 *
		 * @return void
		 */
		public static function register_assets() {
			wp_register_style(
				'bhdt-core-system-frontend',
				BHDT_URL . 'public/css/bhdt-style.css',
				array(),
				BHDT_CORE_SYSTEM_VERSION
			);

			wp_register_script(
				'bhdt-pinout-finder',
				BHDT_URL . 'public/js/pinout-finder.js',
				array(),
				BHDT_CORE_SYSTEM_VERSION,
				true
			);

			wp_register_script(
				'bhdt-calculators',
				BHDT_URL . 'public/js/calculators.js',
				array(),
				BHDT_CORE_SYSTEM_VERSION,
				true
			);
		}

		/**
		 * Enqueue registered frontend assets.
		 *
		 * @return void
		 */
		public static function enqueue_frontend_assets() {
			wp_enqueue_style( 'bhdt-core-system-frontend' );
			wp_enqueue_script( 'bhdt-pinout-finder' );
			wp_enqueue_script( 'bhdt-calculators' );
		}
	}
}
