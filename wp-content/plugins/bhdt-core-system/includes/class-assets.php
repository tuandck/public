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
			add_action( 'enqueue_block_editor_assets', array( __CLASS__, 'enqueue_block_editor_assets' ) );
			add_action( 'rest_api_init', array( __CLASS__, 'register_rest_routes' ) );
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
			wp_localize_script(
				'bhdt-pinout-finder',
				'bhdtPinoutLibrary',
				array(
					'items' => self::get_pinout_library_items(),
				)
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

		/**
		 * Read dynamic pinout items from the theme/admin library.
		 *
		 * @return array
		 */
		private static function get_pinout_library_items() {
			$items = get_option( 'bhdt_pinout_library', array() );
			if ( ! is_array( $items ) ) {
				return array();
			}

			$visible_items = array();
			foreach ( $items as $item ) {
				if ( ! is_array( $item ) || 'hidden' === ( $item['status'] ?? '' ) ) {
					continue;
				}
				$visible_items[] = $item;
			}

			return array_values( $visible_items );
		}

		/**
		 * Enqueue editor helpers.
		 *
		 * @return void
		 */
		public static function enqueue_block_editor_assets() {
			wp_enqueue_script(
				'bhdt-featured-image-editor',
				BHDT_URL . 'public/js/featured-image-editor.js',
				array( 'wp-api-fetch', 'wp-block-editor', 'wp-blocks', 'wp-components', 'wp-compose', 'wp-data', 'wp-element', 'wp-hooks', 'wp-i18n', 'wp-notices' ),
				BHDT_CORE_SYSTEM_VERSION,
				true
			);
		}

		/**
		 * Register editor helper REST routes.
		 *
		 * @return void
		 */
		public static function register_rest_routes() {
			register_rest_route(
				'bhdt/v1',
				'/media/resolve-image',
				array(
					'methods'             => WP_REST_Server::READABLE,
					'permission_callback' => function () {
						return current_user_can( 'edit_posts' );
					},
					'callback'            => array( __CLASS__, 'resolve_image_by_url' ),
					'args'                => array(
						'url' => array(
							'required'          => true,
							'sanitize_callback' => 'esc_url_raw',
						),
					),
				)
			);
		}

		/**
		 * Resolve an image URL from post content to an attachment ID.
		 *
		 * @param WP_REST_Request $request Request.
		 * @return WP_REST_Response|WP_Error
		 */
		public static function resolve_image_by_url( WP_REST_Request $request ) {
			$url = esc_url_raw( $request->get_param( 'url' ) );
			if ( '' === $url ) {
				return new WP_Error( 'bhdt_image_url_missing', __( 'Image URL is required.', 'bhdt-core-system' ), array( 'status' => 400 ) );
			}

			$attachment_id = self::attachment_id_from_url( $url );
			if ( ! $attachment_id ) {
				return new WP_Error( 'bhdt_image_not_found', __( 'Could not find this image in the Media Library.', 'bhdt-core-system' ), array( 'status' => 404 ) );
			}

			return rest_ensure_response(
				array(
					'id'  => $attachment_id,
					'url' => wp_get_attachment_url( $attachment_id ),
				)
			);
		}

		/**
		 * Resolve attachment ID from original or resized image URL.
		 *
		 * @param string $url Image URL.
		 * @return int
		 */
		private static function attachment_id_from_url( $url ) {
			$id = attachment_url_to_postid( $url );
			if ( $id ) {
				return (int) $id;
			}

			$normalized_url = preg_replace( '/-\d+x\d+(?=\.(?:jpe?g|png|gif|webp|avif)$)/i', '', $url );
			if ( $normalized_url && $normalized_url !== $url ) {
				$id = attachment_url_to_postid( $normalized_url );
				if ( $id ) {
					return (int) $id;
				}
			}

			return 0;
		}
	}
}
