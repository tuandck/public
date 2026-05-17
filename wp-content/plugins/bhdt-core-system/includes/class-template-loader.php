<?php
/**
 * Template loader for BHDT CPT single pages.
 *
 * @package BHDT_Core_System
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'BHDT_Template_Loader' ) ) {
	/**
	 * Routes CPT singles to plugin templates.
	 */
	final class BHDT_Template_Loader {
		/**
		 * Boot hooks.
		 *
		 * @return void
		 */
		public static function init() {
			add_filter( 'template_include', array( __CLASS__, 'load_single_templates' ) );
		}

		/**
		 * Provide plugin templates for BHDT CPTs.
		 *
		 * @param string $template Resolved theme template path.
		 * @return string
		 */
		public static function load_single_templates( $template ) {
			if ( is_singular( 'bhdt_review' ) ) {
				$plugin_template = BHDT_PATH . 'templates/single-review.php';
				if ( file_exists( $plugin_template ) ) {
					return $plugin_template;
				}
			}

			if ( is_singular( 'bhdt_project' ) ) {
				$plugin_template = BHDT_PATH . 'templates/single-project.php';
				if ( file_exists( $plugin_template ) ) {
					return $plugin_template;
				}
			}

			return $template;
		}
	}
}
