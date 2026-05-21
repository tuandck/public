<?php
/**
 * Youtube Content System.
 *
 * @package BHDT_Core_System
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Zenclau_V1_Content_Model' ) ) {
	/**
	 * Database model/repository for Youtube Content System.
	 */
	final class Zenclau_V1_Content_Model {
		/**
		 * Products table name.
		 *
		 * @return string
		 */
		public static function products_table() {
			global $wpdb;

			return $wpdb->prefix . 'zenclau_v1_products';
		}

		/**
		 * Posts queue table name.
		 *
		 * @return string
		 */
		public static function posts_queue_table() {
			global $wpdb;

			return $wpdb->prefix . 'zenclau_v1_posts_queue';
		}

		/**
		 * Trusted channels table name.
		 *
		 * @return string
		 */
		public static function trusted_channels_table() {
			global $wpdb;

			return $wpdb->prefix . 'zenclau_v1_trusted_channels';
		}

		/**
		 * Pending channel videos table name.
		 *
		 * @return string
		 */
		public static function channel_videos_table() {
			global $wpdb;

			return $wpdb->prefix . 'zenclau_v1_channel_videos';
		}

		/**
		 * Create/upgrade V1 tables.
		 *
		 * @return void
		 */
		public static function install_tables() {
			global $wpdb;

			require_once ABSPATH . 'wp-admin/includes/upgrade.php';

			$charset_collate = $wpdb->get_charset_collate();
			$products        = self::products_table();
			$posts_queue     = self::posts_queue_table();
			$channels        = self::trusted_channels_table();
			$channel_videos  = self::channel_videos_table();

			dbDelta(
				"CREATE TABLE {$products} (
					id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
					name VARCHAR(255) NOT NULL,
					official_url TEXT NOT NULL,
					pdf_url TEXT NOT NULL,
					search_keywords TEXT NOT NULL,
					created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
					PRIMARY KEY  (id)
				) {$charset_collate};"
			);

			dbDelta(
				"CREATE TABLE {$posts_queue} (
					id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
					product_id BIGINT(20) UNSIGNED NOT NULL,
					youtube_url VARCHAR(255) DEFAULT NULL,
					content_json LONGTEXT NOT NULL,
					status VARCHAR(50) NOT NULL DEFAULT 'pending',
					created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
					PRIMARY KEY  (id),
					KEY product_id (product_id),
					KEY status (status)
				) {$charset_collate};"
			);

			dbDelta(
				"CREATE TABLE {$channels} (
					id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
					channel_name VARCHAR(255) NOT NULL,
					youtube_channel_id VARCHAR(100) NOT NULL,
					channel_url VARCHAR(255) DEFAULT NULL,
					channel_tag VARCHAR(100) DEFAULT 'electronics',
					subscriber_count BIGINT(20) UNSIGNED DEFAULT 0,
					source VARCHAR(20) NOT NULL DEFAULT 'manual',
					is_active TINYINT(1) NOT NULL DEFAULT 1,
					last_checked_at DATETIME DEFAULT NULL,
					created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
					PRIMARY KEY  (id),
					UNIQUE KEY youtube_channel_id (youtube_channel_id)
				) {$charset_collate};"
			);

			dbDelta(
				"CREATE TABLE {$channel_videos} (
					id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
					channel_id BIGINT(20) UNSIGNED NOT NULL,
					youtube_video_id VARCHAR(32) NOT NULL,
					video_url VARCHAR(255) NOT NULL,
					title TEXT NOT NULL,
					published_at DATETIME DEFAULT NULL,
					view_count BIGINT(20) UNSIGNED DEFAULT 0,
					status VARCHAR(30) NOT NULL DEFAULT 'pending',
					created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
					PRIMARY KEY  (id),
					UNIQUE KEY youtube_video_id (youtube_video_id),
					KEY channel_id (channel_id),
					KEY status (status)
				) {$charset_collate};"
			);
		}

		/**
		 * Ensure tables exist during normal admin/REST usage.
		 *
		 * @return void
		 */
		public static function maybe_install_tables() {
			$installed_version = get_option( 'zenclau_v1_content_db_version', '' );
			if ( '1.3.0' === $installed_version ) {
				return;
			}

			self::install_tables();
			update_option( 'zenclau_v1_content_db_version', '1.3.0', false );
		}

		/**
		 * Format product row.
		 *
		 * @param object|array $row Raw row.
		 * @return array
		 */
		private static function format_product( $row ) {
			$row = (array) $row;

			return array(
				'id'              => absint( $row['id'] ?? 0 ),
				'name'            => (string) ( $row['name'] ?? '' ),
				'official_url'    => (string) ( $row['official_url'] ?? '' ),
				'pdf_url'         => (string) ( $row['pdf_url'] ?? '' ),
				'search_keywords' => (string) ( $row['search_keywords'] ?? '' ),
				'created_at'      => (string) ( $row['created_at'] ?? '' ),
			);
		}

		/**
		 * Format queue row.
		 *
		 * @param object|array $row Raw row.
		 * @return array
		 */
		private static function format_queue_item( $row ) {
			$row          = (array) $row;
			$content_json = json_decode( (string) ( $row['content_json'] ?? '{}' ), true );
			if ( ! is_array( $content_json ) ) {
				$content_json = array();
			}

			return array(
				'id'           => absint( $row['id'] ?? 0 ),
				'product_id'   => absint( $row['product_id'] ?? 0 ),
				'youtube_url'  => (string) ( $row['youtube_url'] ?? '' ),
				'content_json' => $content_json,
				'status'       => (string) ( $row['status'] ?? 'pending' ),
				'created_at'   => (string) ( $row['created_at'] ?? '' ),
			);
		}

		/**
		 * Format channel row.
		 *
		 * @param object|array $row Raw row.
		 * @return array
		 */
		private static function format_channel( $row ) {
			$row = (array) $row;

			return array(
				'id'                 => absint( $row['id'] ?? 0 ),
				'channel_name'       => (string) ( $row['channel_name'] ?? '' ),
				'youtube_channel_id' => (string) ( $row['youtube_channel_id'] ?? '' ),
				'channel_url'        => (string) ( $row['channel_url'] ?? '' ),
				'channel_tag'        => (string) ( $row['channel_tag'] ?? 'electronics' ),
				'subscriber_count'   => absint( $row['subscriber_count'] ?? 0 ),
				'source'             => (string) ( $row['source'] ?? 'manual' ),
				'is_active'          => (bool) absint( $row['is_active'] ?? 1 ),
				'last_checked_at'    => (string) ( $row['last_checked_at'] ?? '' ),
				'created_at'         => (string) ( $row['created_at'] ?? '' ),
			);
		}

		/**
		 * Format channel video row.
		 *
		 * @param object|array $row Raw row.
		 * @return array
		 */
		private static function format_channel_video( $row ) {
			$row = (array) $row;

			return array(
				'id'                 => absint( $row['id'] ?? 0 ),
				'channel_id'         => absint( $row['channel_id'] ?? 0 ),
				'channel_name'       => (string) ( $row['channel_name'] ?? '' ),
				'youtube_video_id'   => (string) ( $row['youtube_video_id'] ?? '' ),
				'video_url'          => (string) ( $row['video_url'] ?? '' ),
				'title'              => (string) ( $row['title'] ?? '' ),
				'published_at'       => (string) ( $row['published_at'] ?? '' ),
				'view_count'         => absint( $row['view_count'] ?? 0 ),
				'status'             => (string) ( $row['status'] ?? 'pending' ),
				'created_at'         => (string) ( $row['created_at'] ?? '' ),
			);
		}

		/**
		 * Get all products.
		 *
		 * @return array
		 */
		public static function get_products() {
			global $wpdb;

			self::maybe_install_tables();

			$rows = $wpdb->get_results( "SELECT * FROM " . self::products_table() . ' ORDER BY id DESC' );

			return array_map( array( __CLASS__, 'format_product' ), (array) $rows );
		}

		/**
		 * Get one product.
		 *
		 * @param int $id Product id.
		 * @return array|null
		 */
		public static function get_product( $id ) {
			global $wpdb;

			self::maybe_install_tables();

			$row = $wpdb->get_row(
				$wpdb->prepare( 'SELECT * FROM ' . self::products_table() . ' WHERE id = %d', absint( $id ) )
			);

			return $row ? self::format_product( $row ) : null;
		}

		/**
		 * Create product.
		 *
		 * @param array $data Product payload.
		 * @return array|WP_Error
		 */
		public static function create_product( array $data ) {
			global $wpdb;

			self::maybe_install_tables();

			$name            = sanitize_text_field( $data['name'] ?? '' );
			$official_url    = esc_url_raw( $data['official_url'] ?? '' );
			$pdf_url         = esc_url_raw( $data['pdf_url'] ?? '' );
			$search_keywords = sanitize_textarea_field( $data['search_keywords'] ?? '' );

			if ( '' === $name || '' === $search_keywords ) {
				return new WP_Error( 'zenclau_v1_product_missing_fields', __( 'V1: Product requires name and search_keywords.', 'bhdt-core-system' ), array( 'status' => 400 ) );
			}

			$inserted = $wpdb->insert(
				self::products_table(),
				array(
					'name'            => $name,
					'official_url'    => $official_url,
					'pdf_url'         => $pdf_url,
					'search_keywords' => $search_keywords,
				),
				array( '%s', '%s', '%s', '%s' )
			);

			if ( false === $inserted ) {
				return new WP_Error( 'zenclau_v1_product_insert_failed', __( 'V1: Could not create product.', 'bhdt-core-system' ), array( 'status' => 500 ) );
			}

			return self::get_product( (int) $wpdb->insert_id );
		}

		/**
		 * Update product.
		 *
		 * @param int   $id Product id.
		 * @param array $data Product payload.
		 * @return array|WP_Error
		 */
		public static function update_product( $id, array $data ) {
			global $wpdb;

			self::maybe_install_tables();

			$id      = absint( $id );
			$current = self::get_product( $id );
			if ( ! $current ) {
				return new WP_Error( 'zenclau_v1_product_not_found', __( 'V1: Product not found.', 'bhdt-core-system' ), array( 'status' => 404 ) );
			}

			$payload = array(
				'name'            => sanitize_text_field( $data['name'] ?? $current['name'] ),
				'official_url'    => esc_url_raw( $data['official_url'] ?? $current['official_url'] ),
				'pdf_url'         => esc_url_raw( $data['pdf_url'] ?? $current['pdf_url'] ),
				'search_keywords' => sanitize_textarea_field( $data['search_keywords'] ?? $current['search_keywords'] ),
			);

			$updated = $wpdb->update(
				self::products_table(),
				$payload,
				array( 'id' => $id ),
				array( '%s', '%s', '%s', '%s' ),
				array( '%d' )
			);

			if ( false === $updated ) {
				return new WP_Error( 'zenclau_v1_product_update_failed', __( 'V1: Could not update product.', 'bhdt-core-system' ), array( 'status' => 500 ) );
			}

			return self::get_product( $id );
		}

		/**
		 * Delete product.
		 *
		 * @param int $id Product id.
		 * @return array|WP_Error
		 */
		public static function delete_product( $id ) {
			global $wpdb;

			self::maybe_install_tables();

			$id      = absint( $id );
			$current = self::get_product( $id );
			if ( ! $current ) {
				return new WP_Error( 'zenclau_v1_product_not_found', __( 'V1: Product not found.', 'bhdt-core-system' ), array( 'status' => 404 ) );
			}

			$wpdb->delete( self::posts_queue_table(), array( 'product_id' => $id ), array( '%d' ) );
			$deleted = $wpdb->delete( self::products_table(), array( 'id' => $id ), array( '%d' ) );

			if ( false === $deleted ) {
				return new WP_Error( 'zenclau_v1_product_delete_failed', __( 'V1: Could not delete product.', 'bhdt-core-system' ), array( 'status' => 500 ) );
			}

			return array( 'deleted' => true, 'id' => $id );
		}

		/**
		 * Get trusted channels.
		 *
		 * @param bool $active_only Only active channels.
		 * @return array
		 */
		public static function get_channels( $active_only = false ) {
			global $wpdb;

			self::maybe_install_tables();

			$sql = 'SELECT * FROM ' . self::trusted_channels_table();
			if ( $active_only ) {
				$sql .= ' WHERE is_active = 1';
			}
			$sql .= ' ORDER BY channel_name ASC, id ASC';

			return array_map( array( __CLASS__, 'format_channel' ), (array) $wpdb->get_results( $sql ) );
		}

		/**
		 * Get one channel.
		 *
		 * @param int $id Channel id.
		 * @return array|null
		 */
		public static function get_channel( $id ) {
			global $wpdb;

			self::maybe_install_tables();

			$row = $wpdb->get_row(
				$wpdb->prepare( 'SELECT * FROM ' . self::trusted_channels_table() . ' WHERE id = %d', absint( $id ) )
			);

			return $row ? self::format_channel( $row ) : null;
		}

		/**
		 * Resolve a YouTube channel URL into stored channel metadata.
		 *
		 * @param string $channel_url Channel URL.
		 * @return array|WP_Error
		 */
		private static function resolve_channel_url( $channel_url ) {
			$channel_url = esc_url_raw( $channel_url );
			if ( '' === $channel_url ) {
				return new WP_Error( 'zenclau_v1_channel_url_missing', __( 'V1: Channel URL is required.', 'bhdt-core-system' ), array( 'status' => 400 ) );
			}

			$path = (string) wp_parse_url( $channel_url, PHP_URL_PATH );
			if ( preg_match( '#/channel/(UC[a-zA-Z0-9_-]+)#', $path, $matches ) ) {
				return array(
					'channel_name'       => trim( basename( $path ), '/' ),
					'youtube_channel_id' => $matches[1],
					'channel_url'        => $channel_url,
				);
			}

			$response = wp_remote_get(
				$channel_url,
				array(
					'timeout'     => 15,
					'redirection' => 3,
					'user-agent'  => 'BHDT Youtube Content System/' . ( defined( 'BHDT_CORE_SYSTEM_VERSION' ) ? BHDT_CORE_SYSTEM_VERSION : '1.0' ),
				)
			);
			if ( is_wp_error( $response ) ) {
				return $response;
			}

			$body = wp_remote_retrieve_body( $response );
			if ( '' === trim( $body ) ) {
				return new WP_Error( 'zenclau_v1_channel_resolve_empty', __( 'V1: Could not read YouTube channel page.', 'bhdt-core-system' ), array( 'status' => 400 ) );
			}

			$channel_id = '';
			foreach ( array( '/"externalId"\s*:\s*"(UC[a-zA-Z0-9_-]+)"/', '/"channelId"\s*:\s*"(UC[a-zA-Z0-9_-]+)"/', '#youtube\.com/channel/(UC[a-zA-Z0-9_-]+)#' ) as $pattern ) {
				if ( preg_match( $pattern, $body, $matches ) ) {
					$channel_id = $matches[1];
					break;
				}
			}

			if ( '' === $channel_id ) {
				return new WP_Error( 'zenclau_v1_channel_resolve_failed', __( 'V1: Could not resolve YouTube channel ID from URL.', 'bhdt-core-system' ), array( 'status' => 400 ) );
			}

			$name = '';
			if ( preg_match( '/<meta\s+property="og:title"\s+content="([^"]+)"/i', $body, $matches ) ) {
				$name = html_entity_decode( $matches[1], ENT_QUOTES, 'UTF-8' );
			}
			if ( '' === trim( $name ) ) {
				$name = trim( basename( $path ), '/' );
			}

			return array(
				'channel_name'       => sanitize_text_field( $name ),
				'youtube_channel_id' => sanitize_text_field( $channel_id ),
				'channel_url'        => $channel_url,
			);
		}

		/**
		 * Create channel.
		 *
		 * @param array $data Channel payload.
		 * @return array|WP_Error
		 */
		public static function create_channel( array $data ) {
			global $wpdb;

			self::maybe_install_tables();

			$resolved          = self::resolve_channel_url( $data['channel_url'] ?? '' );
			if ( is_wp_error( $resolved ) ) {
				return $resolved;
			}

			$name              = sanitize_text_field( $data['channel_name'] ?? $resolved['channel_name'] );
			$youtube_channel_id = sanitize_text_field( $data['youtube_channel_id'] ?? $resolved['youtube_channel_id'] );
			$channel_url       = esc_url_raw( $resolved['channel_url'] );
			$tag               = sanitize_key( $data['channel_tag'] ?? 'electronics' );
			$subscriber_count  = absint( $data['subscriber_count'] ?? 0 );
			$source            = sanitize_key( $data['source'] ?? 'manual' );
			$is_active         = isset( $data['is_active'] ) ? ( empty( $data['is_active'] ) ? 0 : 1 ) : 1;

			if ( '' === $name || '' === $youtube_channel_id ) {
				return new WP_Error( 'zenclau_v1_channel_missing_fields', __( 'V1: Could not resolve channel metadata from this URL.', 'bhdt-core-system' ), array( 'status' => 400 ) );
			}

			$existing = $wpdb->get_row(
				$wpdb->prepare( 'SELECT * FROM ' . self::trusted_channels_table() . ' WHERE youtube_channel_id = %s LIMIT 1', $youtube_channel_id )
			);
			if ( $existing ) {
				return self::format_channel( $existing );
			}

			if ( 'auto' === $source && $subscriber_count < 500000 ) {
				return new WP_Error( 'zenclau_v1_channel_subscriber_floor', __( 'V1: Auto-added channels must have at least 500k subscribers.', 'bhdt-core-system' ), array( 'status' => 400 ) );
			}

			$inserted = $wpdb->insert(
				self::trusted_channels_table(),
				array(
					'channel_name'       => $name,
					'youtube_channel_id' => $youtube_channel_id,
					'channel_url'        => $channel_url,
					'channel_tag'        => $tag ? $tag : 'electronics',
					'subscriber_count'   => $subscriber_count,
					'source'             => in_array( $source, array( 'manual', 'auto' ), true ) ? $source : 'manual',
					'is_active'          => $is_active,
				),
				array( '%s', '%s', '%s', '%s', '%d', '%s', '%d' )
			);

			if ( false === $inserted ) {
				return new WP_Error( 'zenclau_v1_channel_insert_failed', __( 'V1: Could not create channel. It may already exist.', 'bhdt-core-system' ), array( 'status' => 500 ) );
			}

			return self::get_channel( (int) $wpdb->insert_id );
		}

		/**
		 * Update channel active status or metadata.
		 *
		 * @param int   $id Channel id.
		 * @param array $data Channel payload.
		 * @return array|WP_Error
		 */
		public static function update_channel( $id, array $data ) {
			global $wpdb;

			self::maybe_install_tables();

			$id      = absint( $id );
			$current = self::get_channel( $id );
			if ( ! $current ) {
				return new WP_Error( 'zenclau_v1_channel_not_found', __( 'V1: Channel not found.', 'bhdt-core-system' ), array( 'status' => 404 ) );
			}

			$payload = array(
				'channel_name'       => sanitize_text_field( $data['channel_name'] ?? $current['channel_name'] ),
				'youtube_channel_id' => sanitize_text_field( $data['youtube_channel_id'] ?? $current['youtube_channel_id'] ),
				'channel_url'        => esc_url_raw( $data['channel_url'] ?? $current['channel_url'] ),
				'channel_tag'        => sanitize_key( $data['channel_tag'] ?? $current['channel_tag'] ),
				'subscriber_count'   => absint( $data['subscriber_count'] ?? $current['subscriber_count'] ),
				'source'             => sanitize_key( $data['source'] ?? $current['source'] ),
				'is_active'          => isset( $data['is_active'] ) ? ( empty( $data['is_active'] ) ? 0 : 1 ) : ( $current['is_active'] ? 1 : 0 ),
			);

			$updated = $wpdb->update(
				self::trusted_channels_table(),
				$payload,
				array( 'id' => $id ),
				array( '%s', '%s', '%s', '%s', '%d', '%s', '%d' ),
				array( '%d' )
			);

			if ( false === $updated ) {
				return new WP_Error( 'zenclau_v1_channel_update_failed', __( 'V1: Could not update channel.', 'bhdt-core-system' ), array( 'status' => 500 ) );
			}

			return self::get_channel( $id );
		}

		/**
		 * Delete channel and queued videos.
		 *
		 * @param int $id Channel id.
		 * @return array|WP_Error
		 */
		public static function delete_channel( $id ) {
			global $wpdb;

			self::maybe_install_tables();

			$id = absint( $id );
			if ( ! self::get_channel( $id ) ) {
				return new WP_Error( 'zenclau_v1_channel_not_found', __( 'V1: Channel not found.', 'bhdt-core-system' ), array( 'status' => 404 ) );
			}

			$wpdb->delete( self::channel_videos_table(), array( 'channel_id' => $id ), array( '%d' ) );
			$deleted = $wpdb->delete( self::trusted_channels_table(), array( 'id' => $id ), array( '%d' ) );

			if ( false === $deleted ) {
				return new WP_Error( 'zenclau_v1_channel_delete_failed', __( 'V1: Could not delete channel.', 'bhdt-core-system' ), array( 'status' => 500 ) );
			}

			return array( 'deleted' => true, 'id' => $id );
		}

		/**
		 * Create or return a channel from YouTube API metadata without resolving a URL.
		 *
		 * @param string $youtube_channel_id YouTube channel ID.
		 * @param string $channel_name Channel name.
		 * @param int    $subscriber_count Subscriber count.
		 * @return array|WP_Error
		 */
		public static function create_or_get_channel_from_api( $youtube_channel_id, $channel_name, $subscriber_count = 0 ) {
			global $wpdb;

			self::maybe_install_tables();

			$youtube_channel_id = sanitize_text_field( $youtube_channel_id );
			$channel_name       = sanitize_text_field( $channel_name );
			if ( '' === $youtube_channel_id ) {
				return new WP_Error( 'zenclau_v1_api_channel_missing_id', __( 'V1: YouTube channel ID is required.', 'bhdt-core-system' ), array( 'status' => 400 ) );
			}

			$existing = $wpdb->get_row(
				$wpdb->prepare( 'SELECT * FROM ' . self::trusted_channels_table() . ' WHERE youtube_channel_id = %s LIMIT 1', $youtube_channel_id )
			);
			if ( $existing ) {
				return self::format_channel( $existing );
			}

			$inserted = $wpdb->insert(
				self::trusted_channels_table(),
				array(
					'channel_name'       => '' !== $channel_name ? $channel_name : $youtube_channel_id,
					'youtube_channel_id' => $youtube_channel_id,
					'channel_url'        => 'https://www.youtube.com/channel/' . rawurlencode( $youtube_channel_id ),
					'channel_tag'        => 'electronics',
					'subscriber_count'   => absint( $subscriber_count ),
					'source'             => 'auto',
					'is_active'          => 1,
				),
				array( '%s', '%s', '%s', '%s', '%d', '%s', '%d' )
			);

			if ( false === $inserted ) {
				return new WP_Error( 'zenclau_v1_api_channel_insert_failed', __( 'V1: Could not create channel for discovered video.', 'bhdt-core-system' ), array( 'status' => 500 ) );
			}

			return self::get_channel( (int) $wpdb->insert_id );
		}

		/**
		 * Store a channel video candidate if it does not exist.
		 *
		 * @param int    $channel_id Channel id.
		 * @param string $video_id YouTube video id.
		 * @param string $title Video title.
		 * @param string $published_at Published datetime.
		 * @param int    $view_count View count.
		 * @return bool
		 */
		public static function upsert_channel_video( $channel_id, $video_id, $title, $published_at = '', $view_count = 0 ) {
			global $wpdb;

			self::maybe_install_tables();

			$channel_id = absint( $channel_id );
			$video_id   = sanitize_text_field( $video_id );
			if ( ! $channel_id || '' === $video_id ) {
				return false;
			}

			$exists = $wpdb->get_var(
				$wpdb->prepare( 'SELECT id FROM ' . self::channel_videos_table() . ' WHERE youtube_video_id = %s', $video_id )
			);
			if ( $exists ) {
				return false;
			}

			$published = '' !== $published_at ? gmdate( 'Y-m-d H:i:s', strtotime( $published_at ) ) : null;
			$inserted  = $wpdb->insert(
				self::channel_videos_table(),
				array(
					'channel_id'       => $channel_id,
					'youtube_video_id' => $video_id,
					'video_url'        => 'https://www.youtube.com/watch?v=' . rawurlencode( $video_id ),
					'title'            => sanitize_text_field( $title ),
					'published_at'     => $published,
					'view_count'       => absint( $view_count ),
					'status'           => 'pending',
				),
				array( '%d', '%s', '%s', '%s', '%s', '%d', '%s' )
			);

			return false !== $inserted;
		}

		/**
		 * Get pending channel videos.
		 *
		 * @return array
		 */
		public static function get_pending_channel_videos() {
			global $wpdb;

			self::maybe_install_tables();

			$rows = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT v.*, c.channel_name FROM ' . self::channel_videos_table() . ' v LEFT JOIN ' . self::trusted_channels_table() . ' c ON c.id = v.channel_id WHERE v.status = %s ORDER BY v.published_at DESC, v.id DESC',
					'pending'
				)
			);

			return array_map( array( __CLASS__, 'format_channel_video' ), (array) $rows );
		}

		/**
		 * Update channel video status.
		 *
		 * @param int    $id Video row id.
		 * @param string $status New status.
		 * @return array|WP_Error
		 */
		public static function update_channel_video_status( $id, $status ) {
			global $wpdb;

			self::maybe_install_tables();

			$id     = absint( $id );
			$status = sanitize_key( $status );
			if ( ! in_array( $status, array( 'pending', 'selected', 'dismissed' ), true ) ) {
				return new WP_Error( 'zenclau_v1_channel_video_bad_status', __( 'V1: Invalid video status.', 'bhdt-core-system' ), array( 'status' => 400 ) );
			}

			$updated = $wpdb->update(
				self::channel_videos_table(),
				array( 'status' => $status ),
				array( 'id' => $id ),
				array( '%s' ),
				array( '%d' )
			);

			if ( false === $updated ) {
				return new WP_Error( 'zenclau_v1_channel_video_update_failed', __( 'V1: Could not update video.', 'bhdt-core-system' ), array( 'status' => 500 ) );
			}

			return array( 'id' => $id, 'status' => $status );
		}

		/**
		 * Mark channel check timestamp.
		 *
		 * @param int $id Channel id.
		 * @return void
		 */
		public static function touch_channel_checked_at( $id ) {
			global $wpdb;

			$wpdb->update(
				self::trusted_channels_table(),
				array( 'last_checked_at' => current_time( 'mysql' ) ),
				array( 'id' => absint( $id ) ),
				array( '%s' ),
				array( '%d' )
			);
		}

		/**
		 * Create queue item.
		 *
		 * @param array $data Queue payload.
		 * @return array|WP_Error
		 */
		public static function create_queue_item( array $data ) {
			global $wpdb;

			self::maybe_install_tables();

			$product_id  = absint( $data['product_id'] ?? 0 );
			$youtube_url = isset( $data['youtube_url'] ) ? esc_url_raw( $data['youtube_url'] ) : '';
			$status      = sanitize_key( $data['status'] ?? 'pending' );
			$status      = $status ? $status : 'pending';
			$content     = $data['content_json'] ?? array();

			if ( ! self::get_product( $product_id ) ) {
				return new WP_Error( 'zenclau_v1_queue_product_missing', __( 'V1: A valid product_id is required.', 'bhdt-core-system' ), array( 'status' => 400 ) );
			}

			if ( is_string( $content ) ) {
				$decoded = json_decode( $content, true );
				$content = is_array( $decoded ) ? $decoded : array();
			}

			if ( ! is_array( $content ) ) {
				return new WP_Error( 'zenclau_v1_queue_invalid_json', __( 'V1: content_json must be a JSON object.', 'bhdt-core-system' ), array( 'status' => 400 ) );
			}

			$content_json = wp_json_encode( $content, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
			if ( false === $content_json ) {
				return new WP_Error( 'zenclau_v1_queue_json_encode_failed', __( 'V1: Could not encode content_json.', 'bhdt-core-system' ), array( 'status' => 500 ) );
			}

			$inserted = $wpdb->insert(
				self::posts_queue_table(),
				array(
					'product_id'   => $product_id,
					'youtube_url'  => $youtube_url,
					'content_json' => $content_json,
					'status'       => $status,
				),
				array( '%d', '%s', '%s', '%s' )
			);

			if ( false === $inserted ) {
				return new WP_Error( 'zenclau_v1_queue_insert_failed', __( 'V1: Could not create queue item.', 'bhdt-core-system' ), array( 'status' => 500 ) );
			}

			return self::get_queue_item( (int) $wpdb->insert_id );
		}

		/**
		 * Get queue item.
		 *
		 * @param int $id Queue id.
		 * @return array|null
		 */
		public static function get_queue_item( $id ) {
			global $wpdb;

			self::maybe_install_tables();

			$row = $wpdb->get_row(
				$wpdb->prepare( 'SELECT * FROM ' . self::posts_queue_table() . ' WHERE id = %d', absint( $id ) )
			);

			return $row ? self::format_queue_item( $row ) : null;
		}

		/**
		 * Get pending queue.
		 *
		 * @return array
		 */
		public static function get_pending_queue() {
			global $wpdb;

			self::maybe_install_tables();

			$rows = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT q.*, p.name AS product_name FROM ' . self::posts_queue_table() . ' q LEFT JOIN ' . self::products_table() . ' p ON p.id = q.product_id WHERE q.status = %s ORDER BY q.created_at ASC, q.id ASC',
					'pending'
				)
			);

			return array_map(
				static function ( $row ) {
					$item                 = self::format_queue_item( $row );
					$item['product_name'] = (string) ( $row->product_name ?? '' );
					return $item;
				},
				(array) $rows
			);
		}

		/**
		 * Get latest queue item for a product and YouTube URL.
		 *
		 * @param int    $product_id Product id.
		 * @param string $youtube_url YouTube URL.
		 * @return array|null
		 */
		public static function get_latest_queue_item_for_run( $product_id, $youtube_url ) {
			global $wpdb;

			self::maybe_install_tables();

			$row = $wpdb->get_row(
				$wpdb->prepare(
					'SELECT * FROM ' . self::posts_queue_table() . ' WHERE product_id = %d AND youtube_url = %s ORDER BY id DESC LIMIT 1',
					absint( $product_id ),
					(string) $youtube_url
				)
			);

			return $row ? self::format_queue_item( $row ) : null;
		}

		/**
		 * Update queue item.
		 *
		 * @param int   $id Queue id.
		 * @param array $data Queue payload.
		 * @return array|WP_Error
		 */
		public static function update_queue_item( $id, array $data ) {
			global $wpdb;

			self::maybe_install_tables();

			$id      = absint( $id );
			$current = self::get_queue_item( $id );
			if ( ! $current ) {
				return new WP_Error( 'zenclau_v1_queue_not_found', __( 'V1: Queue item not found.', 'bhdt-core-system' ), array( 'status' => 404 ) );
			}

			$payload = array();
			$formats = array();

			if ( isset( $data['status'] ) ) {
				$payload['status'] = sanitize_key( $data['status'] );
				$formats[]         = '%s';
			}

			if ( isset( $data['content_json'] ) ) {
				$content = $data['content_json'];
				if ( is_string( $content ) ) {
					$decoded = json_decode( $content, true );
					$content = is_array( $decoded ) ? $decoded : array();
				}
				if ( ! is_array( $content ) ) {
					return new WP_Error( 'zenclau_v1_queue_invalid_json', __( 'V1: content_json must be a JSON object.', 'bhdt-core-system' ), array( 'status' => 400 ) );
				}

				$content_json = wp_json_encode( $content, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
				if ( false === $content_json ) {
					return new WP_Error( 'zenclau_v1_queue_json_encode_failed', __( 'V1: Could not encode content_json.', 'bhdt-core-system' ), array( 'status' => 500 ) );
				}

				$payload['content_json'] = $content_json;
				$formats[]               = '%s';
			}

			if ( empty( $payload ) ) {
				return $current;
			}

			$updated = $wpdb->update(
				self::posts_queue_table(),
				$payload,
				array( 'id' => $id ),
				$formats,
				array( '%d' )
			);

			if ( false === $updated ) {
				return new WP_Error( 'zenclau_v1_queue_update_failed', __( 'V1: Could not update queue item.', 'bhdt-core-system' ), array( 'status' => 500 ) );
			}

			return self::get_queue_item( $id );
		}
	}
}

if ( ! class_exists( 'Zenclau_V1_REST_API' ) ) {
	/**
	 * REST API for Youtube Content System.
	 */
	final class Zenclau_V1_REST_API {
		/**
		 * Boot hooks.
		 *
		 * @return void
		 */
		public static function init() {
			$instance = new self();
			add_action( 'rest_api_init', array( $instance, 'register_routes' ) );
		}

		/**
		 * Register routes.
		 *
		 * @return void
		 */
		public function register_routes() {
			register_rest_route(
				'zenclau/v1',
				'/products',
				array(
					array(
						'methods'             => WP_REST_Server::READABLE,
						'permission_callback' => array( $this, 'can_manage' ),
						'callback'            => array( $this, 'get_products' ),
					),
					array(
						'methods'             => WP_REST_Server::CREATABLE,
						'permission_callback' => array( $this, 'can_manage' ),
						'callback'            => array( $this, 'create_product' ),
					),
				)
			);

			register_rest_route(
				'zenclau/v1',
				'/products/(?P<id>\d+)',
				array(
					array(
						'methods'             => WP_REST_Server::READABLE,
						'permission_callback' => array( $this, 'can_manage' ),
						'callback'            => array( $this, 'get_product' ),
					),
					array(
						'methods'             => WP_REST_Server::EDITABLE,
						'permission_callback' => array( $this, 'can_manage' ),
						'callback'            => array( $this, 'update_product' ),
					),
					array(
						'methods'             => WP_REST_Server::DELETABLE,
						'permission_callback' => array( $this, 'can_manage' ),
						'callback'            => array( $this, 'delete_product' ),
					),
				)
			);

			register_rest_route(
				'zenclau/v1',
				'/posts-queue',
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'permission_callback' => array( $this, 'can_manage' ),
					'callback'            => array( $this, 'create_queue_item' ),
				)
			);

			register_rest_route(
				'zenclau/v1',
				'/posts-queue/pending',
				array(
					'methods'             => WP_REST_Server::READABLE,
					'permission_callback' => array( $this, 'can_manage' ),
					'callback'            => array( $this, 'get_pending_queue' ),
				)
			);

			register_rest_route(
				'zenclau/v1',
				'/posts-queue/(?P<id>\d+)',
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'permission_callback' => array( $this, 'can_manage' ),
					'callback'            => array( $this, 'update_queue_item' ),
				)
			);

			register_rest_route(
				'zenclau/v1',
				'/posts-queue/(?P<id>\d+)/finish',
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'permission_callback' => array( $this, 'can_manage' ),
					'callback'            => array( $this, 'finish_queue_item' ),
				)
			);

			register_rest_route(
				'zenclau/v1',
				'/scraper/run',
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'permission_callback' => array( $this, 'can_manage' ),
					'callback'            => array( $this, 'run_manual_scraper' ),
				)
			);

			register_rest_route(
				'zenclau/v1',
				'/scraper/transcript',
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'permission_callback' => array( $this, 'can_manage' ),
					'callback'            => array( $this, 'get_youtube_transcript' ),
				)
			);

			register_rest_route(
				'zenclau/v1',
				'/scraper/official-text',
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'permission_callback' => array( $this, 'can_manage' ),
					'callback'            => array( $this, 'get_official_text' ),
				)
			);

			register_rest_route(
				'zenclau/v1',
				'/settings/gemini-api-key',
				array(
					array(
						'methods'             => WP_REST_Server::READABLE,
						'permission_callback' => array( $this, 'can_manage' ),
						'callback'            => array( $this, 'get_gemini_api_key' ),
					),
					array(
						'methods'             => WP_REST_Server::EDITABLE,
						'permission_callback' => array( $this, 'can_manage' ),
						'callback'            => array( $this, 'update_gemini_api_key' ),
					),
				)
			);

			register_rest_route(
				'zenclau/v1',
				'/settings/schema-templates',
				array(
					array(
						'methods'             => WP_REST_Server::READABLE,
						'permission_callback' => array( $this, 'can_manage' ),
						'callback'            => array( $this, 'get_schema_templates' ),
					),
					array(
						'methods'             => WP_REST_Server::EDITABLE,
						'permission_callback' => array( $this, 'can_manage' ),
						'callback'            => array( $this, 'save_schema_template' ),
					),
				)
			);

			register_rest_route(
				'zenclau/v1',
				'/settings/max-output-tokens',
				array(
					array(
						'methods'             => WP_REST_Server::READABLE,
						'permission_callback' => array( $this, 'can_manage' ),
						'callback'            => array( $this, 'get_max_output_tokens' ),
					),
					array(
						'methods'             => WP_REST_Server::EDITABLE,
						'permission_callback' => array( $this, 'can_manage' ),
						'callback'            => array( $this, 'update_max_output_tokens' ),
					),
				)
			);

			register_rest_route(
				'zenclau/v1',
				'/channels',
				array(
					array(
						'methods'             => WP_REST_Server::READABLE,
						'permission_callback' => array( $this, 'can_manage' ),
						'callback'            => array( $this, 'get_channels' ),
					),
					array(
						'methods'             => WP_REST_Server::CREATABLE,
						'permission_callback' => array( $this, 'can_manage' ),
						'callback'            => array( $this, 'create_channel' ),
					),
				)
			);

			register_rest_route(
				'zenclau/v1',
				'/channels/(?P<id>\d+)',
				array(
					array(
						'methods'             => WP_REST_Server::EDITABLE,
						'permission_callback' => array( $this, 'can_manage' ),
						'callback'            => array( $this, 'update_channel' ),
					),
					array(
						'methods'             => WP_REST_Server::DELETABLE,
						'permission_callback' => array( $this, 'can_manage' ),
						'callback'            => array( $this, 'delete_channel' ),
					),
				)
			);

			register_rest_route(
				'zenclau/v1',
				'/channels/check-now',
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'permission_callback' => array( $this, 'can_manage' ),
					'callback'            => array( $this, 'check_channels_now' ),
				)
			);

			register_rest_route(
				'zenclau/v1',
				'/channels/discover',
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'permission_callback' => array( $this, 'can_manage' ),
					'callback'            => array( $this, 'discover_channels' ),
				)
			);

			register_rest_route(
				'zenclau/v1',
				'/suggested-channels',
				array(
					'methods'             => WP_REST_Server::READABLE,
					'permission_callback' => array( $this, 'can_manage' ),
					'callback'            => array( $this, 'get_suggested_channels' ),
				)
			);

			register_rest_route(
				'zenclau/v1',
				'/suggested-channels/seed-trusted',
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'permission_callback' => array( $this, 'can_manage' ),
					'callback'            => array( $this, 'seed_trusted_channels' ),
				)
			);

			register_rest_route(
				'zenclau/v1',
				'/suggested-channels/(?P<id>[A-Za-z0-9_-]+)/approve',
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'permission_callback' => array( $this, 'can_manage' ),
					'callback'            => array( $this, 'approve_suggested_channel' ),
				)
			);

			register_rest_route(
				'zenclau/v1',
				'/suggested-channels/(?P<id>[A-Za-z0-9_-]+)/dismiss',
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'permission_callback' => array( $this, 'can_manage' ),
					'callback'            => array( $this, 'dismiss_suggested_channel' ),
				)
			);

			register_rest_route(
				'zenclau/v1',
				'/videos/discover',
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'permission_callback' => array( $this, 'can_manage' ),
					'callback'            => array( $this, 'discover_videos' ),
				)
			);

			register_rest_route(
				'zenclau/v1',
				'/channel-videos/pending',
				array(
					'methods'             => WP_REST_Server::READABLE,
					'permission_callback' => array( $this, 'can_manage' ),
					'callback'            => array( $this, 'get_pending_channel_videos' ),
				)
			);

			register_rest_route(
				'zenclau/v1',
				'/channel-videos/(?P<id>\d+)',
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'permission_callback' => array( $this, 'can_manage' ),
					'callback'            => array( $this, 'update_channel_video' ),
				)
			);

			register_rest_route(
				'zenclau/v1',
				'/settings/channel-cron',
				array(
					array(
						'methods'             => WP_REST_Server::READABLE,
						'permission_callback' => array( $this, 'can_manage' ),
						'callback'            => array( $this, 'get_channel_cron_settings' ),
					),
					array(
						'methods'             => WP_REST_Server::EDITABLE,
						'permission_callback' => array( $this, 'can_manage' ),
						'callback'            => array( $this, 'update_channel_cron_settings' ),
					),
				)
			);

			register_rest_route(
				'zenclau/v1',
				'/settings/youtube-api-key',
				array(
					array(
						'methods'             => WP_REST_Server::READABLE,
						'permission_callback' => array( $this, 'can_manage' ),
						'callback'            => array( $this, 'get_youtube_api_key' ),
					),
					array(
						'methods'             => WP_REST_Server::EDITABLE,
						'permission_callback' => array( $this, 'can_manage' ),
						'callback'            => array( $this, 'update_youtube_api_key' ),
					),
				)
			);
		}

		/**
		 * Permission callback.
		 *
		 * @return bool|WP_Error
		 */
		public function can_manage() {
			if ( current_user_can( 'manage_options' ) ) {
				return true;
			}

			return new WP_Error( 'zenclau_v1_forbidden', __( 'V1: You do not have permission to manage Zenclau Content System.', 'bhdt-core-system' ), array( 'status' => 403 ) );
		}

		/**
		 * Return the configured Gemini API key for the admin settings panel.
		 *
		 * @return WP_REST_Response|WP_Error
		 */
		public function get_gemini_api_key() {
			$result = $this->read_env_value( 'GEMINI_API_KEY' );
			if ( is_wp_error( $result ) ) {
				return $result;
			}

			return rest_ensure_response(
				array(
					'key'     => $result,
					'env_key' => 'GEMINI_API_KEY',
				)
			);
		}

		/**
		 * Update the Gemini API key in the plugin .env file.
		 *
		 * @param WP_REST_Request $request Request.
		 * @return WP_REST_Response|WP_Error
		 */
		public function update_gemini_api_key( WP_REST_Request $request ) {
			$params  = (array) $request->get_json_params();
			$api_key = isset( $params['key'] ) ? sanitize_text_field( wp_unslash( $params['key'] ) ) : '';

			if ( '' === $api_key ) {
				return new WP_Error( 'zenclau_v1_gemini_key_empty', __( 'V1: Gemini API key cannot be empty.', 'bhdt-core-system' ), array( 'status' => 400 ) );
			}

			$result = $this->write_env_value( 'GEMINI_API_KEY', $api_key );
			if ( is_wp_error( $result ) ) {
				return $result;
			}

			return rest_ensure_response(
				array(
					'success' => true,
					'env_key' => 'GEMINI_API_KEY',
				)
			);
		}

		/**
		 * Return the configured YouTube API key for channel discovery.
		 *
		 * @return WP_REST_Response|WP_Error
		 */
		public function get_youtube_api_key() {
			$result = $this->read_env_value( 'YOUTUBE_API_KEY' );
			if ( is_wp_error( $result ) ) {
				return $result;
			}

			return rest_ensure_response(
				array(
					'key'     => $result,
					'env_key' => 'YOUTUBE_API_KEY',
				)
			);
		}

		/**
		 * Update the YouTube API key in the plugin .env file.
		 *
		 * @param WP_REST_Request $request Request.
		 * @return WP_REST_Response|WP_Error
		 */
		public function update_youtube_api_key( WP_REST_Request $request ) {
			$params  = (array) $request->get_json_params();
			$api_key = isset( $params['key'] ) ? sanitize_text_field( wp_unslash( $params['key'] ) ) : '';

			if ( '' === $api_key ) {
				return new WP_Error( 'zenclau_v1_youtube_key_empty', __( 'V1: YouTube API key cannot be empty.', 'bhdt-core-system' ), array( 'status' => 400 ) );
			}

			$result = $this->write_env_value( 'YOUTUBE_API_KEY', $api_key );
			if ( is_wp_error( $result ) ) {
				return $result;
			}

			return rest_ensure_response(
				array(
					'success' => true,
					'env_key' => 'YOUTUBE_API_KEY',
				)
			);
		}

		/**
		 * Read a single key from the plugin .env file.
		 *
		 * @param string $key Environment key.
		 * @return string|WP_Error
		 */
		private function read_env_value( $key ) {
			$env_file = BHDT_PATH . '.env';

			if ( ! file_exists( $env_file ) ) {
				return new WP_Error( 'zenclau_v1_env_missing', __( 'V1: .env file was not found.', 'bhdt-core-system' ), array( 'status' => 500 ) );
			}

			$contents = file_get_contents( $env_file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			if ( false === $contents ) {
				return new WP_Error( 'zenclau_v1_env_read_failed', __( 'V1: Could not read .env file.', 'bhdt-core-system' ), array( 'status' => 500 ) );
			}

			$lines = preg_split( "/\r\n|\n|\r/", (string) $contents );
			foreach ( $lines as $line ) {
				if ( preg_match( '/^\s*' . preg_quote( $key, '/' ) . '\s*=\s*(.*)\s*$/', $line, $matches ) ) {
					return trim( (string) $matches[1], " \t\n\r\0\x0B\"'" );
				}
			}

			return '';
		}

		/**
		 * Write a single key to the plugin .env file while preserving other values.
		 *
		 * @param string $key Environment key.
		 * @param string $value Environment value.
		 * @return true|WP_Error
		 */
		private function write_env_value( $key, $value ) {
			$env_file = BHDT_PATH . '.env';

			if ( ! file_exists( $env_file ) ) {
				return new WP_Error( 'zenclau_v1_env_missing', __( 'V1: .env file was not found.', 'bhdt-core-system' ), array( 'status' => 500 ) );
			}

			if ( ! is_writable( $env_file ) ) {
				return new WP_Error( 'zenclau_v1_env_not_writable', __( 'V1: .env file is not writable.', 'bhdt-core-system' ), array( 'status' => 500 ) );
			}

			$contents = file_get_contents( $env_file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			if ( false === $contents ) {
				return new WP_Error( 'zenclau_v1_env_read_failed', __( 'V1: Could not read .env file.', 'bhdt-core-system' ), array( 'status' => 500 ) );
			}

			$line    = $key . '=' . $this->format_env_value( $value );
			$pattern = '/^' . preg_quote( $key, '/' ) . '=.*$/m';
			if ( preg_match( $pattern, (string) $contents ) ) {
				$contents = preg_replace( $pattern, $line, (string) $contents );
			} else {
				$contents = rtrim( (string) $contents ) . PHP_EOL . $line . PHP_EOL;
			}

			$written = file_put_contents( $env_file, $contents, LOCK_EX ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			if ( false === $written ) {
				return new WP_Error( 'zenclau_v1_env_write_failed', __( 'V1: Could not write .env file.', 'bhdt-core-system' ), array( 'status' => 500 ) );
			}

			return true;
		}

		/**
		 * Quote an env value only when needed.
		 *
		 * @param string $value Raw value.
		 * @return string
		 */
		private function format_env_value( $value ) {
			$value = trim( (string) $value );
			if ( preg_match( '/[\s#"\']/', $value ) ) {
				return '"' . str_replace( array( '\\', '"' ), array( '\\\\', '\\"' ), $value ) . '"';
			}

			return $value;
		}

		/**
		 * Default Gemini schema templates.
		 *
		 * @return array
		 */
		private function default_schema_templates() {
			return array(
				array(
					'id'           => 'template_1',
					'name'         => 'Mẫu 1 - Review kỹ thuật',
					'prompt_rules' => "- Use Vietnamese.\n- Write a complete, information-rich technical review, not a short summary.\n- Focus on product evaluation, comparison, buying advice, strengths, weaknesses, practical use cases, and who should/should not choose it.\n- Use YouTube transcript only for hands-on observations when a YouTube URL is provided.\n- Use Official URL and especially PDF URL as the primary source for specifications, limits, dimensions, electrical ratings, included features, official claims, warnings, and compatibility.\n- Prefer exact values from the PDF. If the PDF and Official URL disagree, mention the conflict and prefer the PDF unless the source text clearly says otherwise.\n- Do not invent specs, prices, certifications, benchmark numbers, or personal test results.\n- If evidence is missing, write that it needs verification instead of guessing.\n- Make review_content long-form with clear sections and detailed paragraphs.\n- Do not use Markdown heading syntax in generated article fields: no ### headings and no bullet-only outline.\n- Start section headings with plain numbering such as I., II., III. or 1., 2., 3.; bold is allowed only for product names or short key terms.\n- Keep comparison_table to 6-12 rows when the schema includes it.",
					'schema'       => "{\n  \"title\": \"string\",\n  \"summary\": \"string\",\n  \"review_content\": \"string\",\n  \"comparison_table\": [\n    {\n      \"criteria\": \"string\",\n      \"product_value\": \"string\",\n      \"notes\": \"string\"\n    }\n  ]\n}",
				),
				array(
					'id'           => 'template_2',
					'name'         => 'Mẫu 2 - Tổng hợp / giải thích Official PDF',
					'prompt_rules' => "- Use Vietnamese.\n- Write a complete, information-rich explainer and source summary, not a short abstract.\n- Focus on explaining what the product is, how it works, important specifications, official claims, PDF details, usage scenarios, setup notes, limitations, safety notes, and compatibility.\n- Use Official URL and especially PDF URL as the primary source. Stay close to the PDF wording for technical facts, numeric specs, warnings, pinouts, ratings, dimensions, protocols, included parts, and operating conditions.\n- Extract as many useful facts as the sources support, but do not pad with invented information.\n- If the source is unclear, say it is unclear. If a spec is not found, say it is not found in the provided sources.\n- Do not invent prices, certifications, test results, comparisons, or claims that are not present in the Official/PDF text.\n- Make review_content the main public article. It must include the most important details from source_summary, key_specifications, official_claims, pdf_details, recommended_use_cases, and limitations_or_notes in natural prose.\n- Treat arrays such as key_specifications, official_claims, pdf_details, recommended_use_cases, and limitations_or_notes as supporting extraction data, not a replacement for review_content.\n- Make source_summary and review_content detailed with clear sections and practical explanations.\n- Do not use Markdown heading syntax in generated article fields: no ### headings and no bullet-only outline.\n- Start section headings with plain numbering such as I., II., III. or 1., 2., 3.; bold is allowed only for product names or short key terms.\n- In key_specifications, include source labels such as PDF URL or Official URL whenever possible.",
					'schema'       => "{\n  \"title\": \"string\",\n  \"summary\": \"string\",\n  \"source_summary\": \"string\",\n  \"key_specifications\": [\n    {\n      \"name\": \"string\",\n      \"value\": \"string\",\n      \"source\": \"Official URL or PDF URL\"\n    }\n  ],\n  \"official_claims\": [\n    \"string\"\n  ],\n  \"pdf_details\": [\n    \"string\"\n  ],\n  \"recommended_use_cases\": [\n    \"string\"\n  ],\n  \"limitations_or_notes\": [\n    \"string\"\n  ],\n  \"review_content\": \"string\"\n}",
				),
				array(
					'id'           => 'template_3',
					'name'         => 'Mẫu 3 - So sánh 2 sản phẩm',
					'prompt_rules' => "- Use Vietnamese.\n- Write a complete, evidence-based comparison between Product 1 and Product 2.\n- Product 1 comes from selected Product ID. Product 2 comes from the Compare product 2 fields.\n- Use Official URL and PDF URL of both products as the primary sources. Prefer PDF values for technical specs.\n- Compare concrete specs, architecture, performance class, I/O, voltage/current limits, connectivity, software ecosystem, learning curve, project suitability, strengths, weaknesses, and buying recommendation.\n- Do not invent specs, benchmark scores, prices, certifications, or claims. If a value is not found in the provided sources, write that it was not found.\n- If sources conflict, mention the conflict and prefer the PDF unless the source text clearly says otherwise.\n- Make review_content the main public article with detailed sections and practical advice.\n- Do not use Markdown heading syntax in generated article fields: no ### headings and no bullet-only outline.\n- Start section headings with plain numbering such as I., II., III. or 1., 2., 3.; bold is allowed only for product names or short key terms.\n- Make comparison_table detailed with 8-16 rows when evidence supports it.\n- End with clear recommendations: choose Product 1 when..., choose Product 2 when..., and choose neither/verify more when needed.",
					'schema'       => "{\n  \"title\": \"string\",\n  \"summary\": \"string\",\n  \"product_1\": {\n    \"name\": \"string\",\n    \"best_for\": [\"string\"],\n    \"key_specs\": [\n      {\n        \"name\": \"string\",\n        \"value\": \"string\",\n        \"source\": \"Official URL or PDF URL\"\n      }\n    ]\n  },\n  \"product_2\": {\n    \"name\": \"string\",\n    \"best_for\": [\"string\"],\n    \"key_specs\": [\n      {\n        \"name\": \"string\",\n        \"value\": \"string\",\n        \"source\": \"Official URL or PDF URL\"\n      }\n    ]\n  },\n  \"comparison_table\": [\n    {\n      \"criteria\": \"string\",\n      \"product_1_value\": \"string\",\n      \"product_2_value\": \"string\",\n      \"winner_or_note\": \"string\"\n    }\n  ],\n  \"recommendation\": \"string\",\n  \"review_content\": \"string\"\n}",
				),
			);
		}

		/**
		 * Get stored Gemini JSON schema templates.
		 *
		 * @return WP_REST_Response
		 */
		public function get_schema_templates() {
			$templates = get_option( 'zenclau_v1_schema_templates', array() );
			if ( ! is_array( $templates ) || empty( $templates ) ) {
				$templates = $this->default_schema_templates();
				update_option( 'zenclau_v1_schema_templates', $templates, false );
			}
			$templates    = array_values( $templates );
			$existing_ids = array();
			$defaults_by_id = array();
			foreach ( $this->default_schema_templates() as $default_template ) {
				$defaults_by_id[ (string) $default_template['id'] ] = $default_template;
			}
			foreach ( $templates as $template ) {
				if ( is_array( $template ) && ! empty( $template['id'] ) ) {
					$existing_ids[] = (string) $template['id'];
				}
			}
			foreach ( $templates as $index => $template ) {
				if ( ! is_array( $template ) || empty( $template['id'] ) ) {
					continue;
				}
				$template_id = (string) $template['id'];
				if ( isset( $defaults_by_id[ $template_id ] ) ) {
					$templates[ $index ] = array_merge( $defaults_by_id[ $template_id ], $template );
					$templates[ $index ]['name']         = $defaults_by_id[ $template_id ]['name'];
					$templates[ $index ]['prompt_rules'] = $defaults_by_id[ $template_id ]['prompt_rules'];
				}
			}
			foreach ( $this->default_schema_templates() as $default_template ) {
				if ( ! in_array( (string) $default_template['id'], $existing_ids, true ) ) {
					$templates[] = $default_template;
				}
			}
			update_option( 'zenclau_v1_schema_templates', $templates, false );

			return rest_ensure_response(
				array(
					'templates' => $templates,
				)
			);
		}

		/**
		 * Save or update one Gemini JSON schema template.
		 *
		 * @param WP_REST_Request $request Request.
		 * @return WP_REST_Response|WP_Error
		 */
		public function save_schema_template( WP_REST_Request $request ) {
			$params = (array) $request->get_json_params();
			$id     = sanitize_key( $params['id'] ?? '' );
			$name   = sanitize_text_field( wp_unslash( $params['name'] ?? '' ) );
			$schema = isset( $params['schema'] ) ? trim( (string) wp_unslash( $params['schema'] ) ) : '';
			$prompt_rules = isset( $params['prompt_rules'] ) ? sanitize_textarea_field( wp_unslash( $params['prompt_rules'] ) ) : '';

			if ( '' === $name || '' === $schema ) {
				return new WP_Error( 'zenclau_v1_schema_template_missing', __( 'V1: Template name and schema are required.', 'bhdt-core-system' ), array( 'status' => 400 ) );
			}

			$decoded_schema = json_decode( $schema, true );
			if ( ! is_array( $decoded_schema ) ) {
				return new WP_Error( 'zenclau_v1_schema_template_invalid_json', __( 'V1: Schema template must be valid JSON.', 'bhdt-core-system' ), array( 'status' => 400 ) );
			}

			if ( '' === $id || 'new' === $id ) {
				$id = 'template_' . time();
			}

			$templates = get_option( 'zenclau_v1_schema_templates', $this->default_schema_templates() );
			$templates = is_array( $templates ) ? array_values( $templates ) : $this->default_schema_templates();
			$saved     = array(
				'id'           => $id,
				'name'         => $name,
				'prompt_rules' => $prompt_rules,
				'schema'       => wp_json_encode( $decoded_schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ),
			);
			$found     = false;

			foreach ( $templates as $index => $template ) {
				if ( is_array( $template ) && isset( $template['id'] ) && $template['id'] === $id ) {
					$templates[ $index ] = $saved;
					$found               = true;
					break;
				}
			}

			if ( ! $found ) {
				$templates[] = $saved;
			}

			update_option( 'zenclau_v1_schema_templates', $templates, false );

			return rest_ensure_response(
				array(
					'success'   => true,
					'template'  => $saved,
					'templates' => array_values( $templates ),
				)
			);
		}

		/**
		 * Return saved Gemini max output tokens.
		 *
		 * @return WP_REST_Response
		 */
		public function get_max_output_tokens() {
			$value = max( 1024, min( 65536, absint( get_option( 'zenclau_v1_max_output_tokens', 16384 ) ) ) );

			return rest_ensure_response(
				array(
					'max_output_tokens' => $value,
				)
			);
		}

		/**
		 * Save Gemini max output tokens.
		 *
		 * @param WP_REST_Request $request Request.
		 * @return WP_REST_Response
		 */
		public function update_max_output_tokens( WP_REST_Request $request ) {
			$params = (array) $request->get_json_params();
			$value  = isset( $params['max_output_tokens'] ) ? absint( $params['max_output_tokens'] ) : 16384;
			$value  = max( 1024, min( 65536, $value ) );

			update_option( 'zenclau_v1_max_output_tokens', $value, false );

			return rest_ensure_response(
				array(
					'success'           => true,
					'max_output_tokens' => $value,
				)
			);
		}

		/**
		 * Channels list.
		 *
		 * @return WP_REST_Response
		 */
		public function get_channels() {
			return rest_ensure_response( Zenclau_V1_Content_Model::get_channels() );
		}

		/**
		 * Create channel.
		 *
		 * @param WP_REST_Request $request Request.
		 * @return WP_REST_Response|WP_Error
		 */
		public function create_channel( WP_REST_Request $request ) {
			$result = Zenclau_V1_Content_Model::create_channel( (array) $request->get_json_params() );
			return is_wp_error( $result ) ? $result : rest_ensure_response( $result );
		}

		/**
		 * Update channel.
		 *
		 * @param WP_REST_Request $request Request.
		 * @return WP_REST_Response|WP_Error
		 */
		public function update_channel( WP_REST_Request $request ) {
			$result = Zenclau_V1_Content_Model::update_channel( absint( $request['id'] ), (array) $request->get_json_params() );
			return is_wp_error( $result ) ? $result : rest_ensure_response( $result );
		}

		/**
		 * Delete channel.
		 *
		 * @param WP_REST_Request $request Request.
		 * @return WP_REST_Response|WP_Error
		 */
		public function delete_channel( WP_REST_Request $request ) {
			$result = Zenclau_V1_Content_Model::delete_channel( absint( $request['id'] ) );
			return is_wp_error( $result ) ? $result : rest_ensure_response( $result );
		}

		/**
		 * Run channel feed check now.
		 *
		 * @return WP_REST_Response|WP_Error
		 */
		public function check_channels_now() {
			$result = Zenclau_V1_Content_System::run_channel_video_check();
			return is_wp_error( $result ) ? $result : rest_ensure_response( $result );
		}

		/**
		 * Discover suggested channels by keyword through YouTube Data API.
		 *
		 * @param WP_REST_Request $request Request.
		 * @return WP_REST_Response|WP_Error
		 */
		public function discover_channels( WP_REST_Request $request ) {
			$params          = (array) $request->get_json_params();
			$keywords_text   = sanitize_textarea_field( wp_unslash( $params['keywords'] ?? '' ) );
			$min_subscribers = max( 0, absint( $params['min_subscribers'] ?? 500000 ) );
			$api_key         = $this->read_env_value( 'YOUTUBE_API_KEY' );

			if ( is_wp_error( $api_key ) ) {
				return $api_key;
			}
			if ( '' === $api_key ) {
				return new WP_Error( 'zenclau_v1_youtube_key_missing', __( 'V1: YouTube API key is required for channel discovery.', 'bhdt-core-system' ), array( 'status' => 400 ) );
			}

			$keywords = array_values(
				array_filter(
					array_map( 'trim', preg_split( '/[\r\n,]+/', $keywords_text ) ),
					static function ( $keyword ) {
						return '' !== $keyword;
					}
				)
			);
			if ( empty( $keywords ) ) {
				return new WP_Error( 'zenclau_v1_discover_keywords_missing', __( 'V1: Add at least one discovery keyword.', 'bhdt-core-system' ), array( 'status' => 400 ) );
			}

			$channel_ids = array();
			foreach ( array_slice( $keywords, 0, 8 ) as $keyword ) {
				$search = $this->youtube_api_get(
					'search',
					array(
						'part'       => 'snippet',
						'type'       => 'channel',
						'maxResults' => 10,
						'q'          => $keyword,
					),
					$api_key
				);
				if ( is_wp_error( $search ) ) {
					return $search;
				}
				foreach ( (array) ( $search['items'] ?? array() ) as $item ) {
					$id = sanitize_text_field( $item['snippet']['channelId'] ?? '' );
					if ( '' !== $id ) {
						$channel_ids[ $id ] = $id;
					}
				}
			}

			if ( empty( $channel_ids ) ) {
				return rest_ensure_response(
					array(
						'added'       => 0,
						'suggestions' => $this->get_suggested_channels_array(),
					)
				);
			}

			$existing = array();
			foreach ( Zenclau_V1_Content_Model::get_channels() as $channel ) {
				$existing[ $channel['youtube_channel_id'] ] = true;
			}

			$suggestions = $this->get_suggested_channels_array();
			$added       = 0;
			foreach ( array_chunk( array_values( $channel_ids ), 50 ) as $chunk ) {
				$details = $this->youtube_api_get(
					'channels',
					array(
						'part' => 'snippet,statistics',
						'id'   => implode( ',', $chunk ),
					),
					$api_key
				);
				if ( is_wp_error( $details ) ) {
					return $details;
				}

				foreach ( (array) ( $details['items'] ?? array() ) as $item ) {
					$channel_id = sanitize_text_field( $item['id'] ?? '' );
					$subs       = absint( $item['statistics']['subscriberCount'] ?? 0 );
					if ( '' === $channel_id || isset( $existing[ $channel_id ] ) || $subs < $min_subscribers ) {
						continue;
					}

					$suggestions[ $channel_id ] = array(
						'id'               => $channel_id,
						'channel_name'     => sanitize_text_field( $item['snippet']['title'] ?? $channel_id ),
						'channel_url'      => 'https://www.youtube.com/channel/' . rawurlencode( $channel_id ),
						'description'      => sanitize_textarea_field( $item['snippet']['description'] ?? '' ),
						'subscriber_count' => $subs,
						'video_count'      => absint( $item['statistics']['videoCount'] ?? 0 ),
						'view_count'       => absint( $item['statistics']['viewCount'] ?? 0 ),
						'discovered_at'    => current_time( 'mysql' ),
					);
					$added++;
				}
			}

			update_option( 'zenclau_v1_suggested_channels', $suggestions, false );

			return rest_ensure_response(
				array(
					'added'       => $added,
					'suggestions' => array_values( $suggestions ),
				)
			);
		}

		/**
		 * Suggested channels list.
		 *
		 * @return WP_REST_Response
		 */
		public function get_suggested_channels() {
			return rest_ensure_response( array_values( $this->get_suggested_channels_array() ) );
		}

		/**
		 * Seed curated trusted electronics channels into Suggested Channels.
		 *
		 * @return WP_REST_Response
		 */
		public function seed_trusted_channels() {
			$suggestions = $this->get_suggested_channels_array();
			$existing    = array();
			foreach ( Zenclau_V1_Content_Model::get_channels() as $channel ) {
				$existing_url = strtolower( untrailingslashit( (string) $channel['channel_url'] ) );
				if ( '' !== $existing_url ) {
					$existing[ $existing_url ] = true;
				}
			}

			$added = 0;
			foreach ( $this->trusted_channel_seed_list() as $seed ) {
				$id  = sanitize_key( 'seed_' . $seed['slug'] );
				$url = untrailingslashit( $seed['channel_url'] );
				if ( isset( $suggestions[ $id ] ) || isset( $existing[ strtolower( $url ) ] ) ) {
					continue;
				}

				$suggestions[ $id ] = array(
					'id'               => $id,
					'channel_name'     => $seed['channel_name'],
					'channel_url'      => $seed['channel_url'],
					'description'      => 'Curated trusted electronics channel.',
					'subscriber_count' => 500000,
					'video_count'      => 0,
					'view_count'       => 0,
					'discovered_at'    => current_time( 'mysql' ),
				);
				$added++;
			}

			update_option( 'zenclau_v1_suggested_channels', $suggestions, false );

			return rest_ensure_response(
				array(
					'added'       => $added,
					'suggestions' => array_values( $suggestions ),
				)
			);
		}

		/**
		 * Approve a suggested channel into the watchlist.
		 *
		 * @param WP_REST_Request $request Request.
		 * @return WP_REST_Response|WP_Error
		 */
		public function approve_suggested_channel( WP_REST_Request $request ) {
			$id          = sanitize_text_field( $request['id'] );
			$suggestions = $this->get_suggested_channels_array();
			if ( empty( $suggestions[ $id ] ) ) {
				return new WP_Error( 'zenclau_v1_suggestion_not_found', __( 'V1: Suggested channel not found.', 'bhdt-core-system' ), array( 'status' => 404 ) );
			}

			$suggestion = $suggestions[ $id ];
			$result     = Zenclau_V1_Content_Model::create_channel(
				array(
					'channel_url'       => $suggestion['channel_url'],
					'channel_name'      => $suggestion['channel_name'],
					'channel_tag'       => 'electronics',
					'subscriber_count'  => $suggestion['subscriber_count'],
					'source'            => 'auto',
					'is_active'         => 1,
				)
			);
			if ( is_wp_error( $result ) ) {
				return $result;
			}

			unset( $suggestions[ $id ] );
			update_option( 'zenclau_v1_suggested_channels', $suggestions, false );

			return rest_ensure_response(
				array(
					'channel'     => $result,
					'suggestions' => array_values( $suggestions ),
				)
			);
		}

		/**
		 * Dismiss a suggested channel.
		 *
		 * @param WP_REST_Request $request Request.
		 * @return WP_REST_Response
		 */
		public function dismiss_suggested_channel( WP_REST_Request $request ) {
			$id          = sanitize_text_field( $request['id'] );
			$suggestions = $this->get_suggested_channels_array();
			unset( $suggestions[ $id ] );
			update_option( 'zenclau_v1_suggested_channels', $suggestions, false );

			return rest_ensure_response( array_values( $suggestions ) );
		}

		/**
		 * Discover videos by keyword and queue videos above the view threshold.
		 *
		 * @param WP_REST_Request $request Request.
		 * @return WP_REST_Response|WP_Error
		 */
		public function discover_videos( WP_REST_Request $request ) {
			$params    = (array) $request->get_json_params();
			$keywords  = sanitize_textarea_field( wp_unslash( $params['keywords'] ?? '' ) );
			$min_views = max( 0, absint( $params['min_views'] ?? 100000 ) );
			$api_key   = $this->read_env_value( 'YOUTUBE_API_KEY' );

			if ( is_wp_error( $api_key ) ) {
				return $api_key;
			}
			if ( '' === $api_key ) {
				return new WP_Error( 'zenclau_v1_youtube_key_missing', __( 'V1: YouTube API key is required for video discovery.', 'bhdt-core-system' ), array( 'status' => 400 ) );
			}

			$keywords = array_values(
				array_filter(
					array_map( 'trim', preg_split( '/[\r\n,]+/', $keywords ) ),
					static function ( $keyword ) {
						return '' !== $keyword;
					}
				)
			);
			if ( empty( $keywords ) ) {
				return new WP_Error( 'zenclau_v1_discover_video_keywords_missing', __( 'V1: Add at least one video discovery keyword.', 'bhdt-core-system' ), array( 'status' => 400 ) );
			}

			$video_ids = array();
			foreach ( array_slice( $keywords, 0, 8 ) as $keyword ) {
				$search = $this->youtube_api_get(
					'search',
					array(
						'part'       => 'snippet',
						'type'       => 'video',
						'maxResults' => 10,
						'order'      => 'relevance',
						'q'          => $keyword,
					),
					$api_key
				);
				if ( is_wp_error( $search ) ) {
					return $search;
				}
				foreach ( (array) ( $search['items'] ?? array() ) as $item ) {
					$id = sanitize_text_field( $item['id']['videoId'] ?? '' );
					if ( '' !== $id ) {
						$video_ids[ $id ] = $id;
					}
				}
			}

			if ( empty( $video_ids ) ) {
				return rest_ensure_response(
					array(
						'added'  => 0,
						'videos' => Zenclau_V1_Content_Model::get_pending_channel_videos(),
					)
				);
			}

			$added = 0;
			foreach ( array_chunk( array_values( $video_ids ), 50 ) as $chunk ) {
				$details = $this->youtube_api_get(
					'videos',
					array(
						'part' => 'snippet,statistics',
						'id'   => implode( ',', $chunk ),
					),
					$api_key
				);
				if ( is_wp_error( $details ) ) {
					return $details;
				}

				foreach ( (array) ( $details['items'] ?? array() ) as $item ) {
					$video_id   = sanitize_text_field( $item['id'] ?? '' );
					$view_count = absint( $item['statistics']['viewCount'] ?? 0 );
					if ( '' === $video_id || $view_count < $min_views ) {
						continue;
					}

					$snippet    = (array) ( $item['snippet'] ?? array() );
					$channel    = Zenclau_V1_Content_Model::create_or_get_channel_from_api(
						(string) ( $snippet['channelId'] ?? '' ),
						(string) ( $snippet['channelTitle'] ?? '' ),
						0
					);
					if ( is_wp_error( $channel ) ) {
						continue;
					}

					if ( Zenclau_V1_Content_Model::upsert_channel_video( $channel['id'], $video_id, (string) ( $snippet['title'] ?? '' ), (string) ( $snippet['publishedAt'] ?? '' ), $view_count ) ) {
						$added++;
					}
				}
			}

			return rest_ensure_response(
				array(
					'added'  => $added,
					'videos' => Zenclau_V1_Content_Model::get_pending_channel_videos(),
				)
			);
		}

		/**
		 * Get stored suggested channels.
		 *
		 * @return array
		 */
		private function get_suggested_channels_array() {
			$suggestions = get_option( 'zenclau_v1_suggested_channels', array() );
			return is_array( $suggestions ) ? $suggestions : array();
		}

		/**
		 * Curated trusted electronics channel seed list.
		 *
		 * @return array
		 */
		private function trusted_channel_seed_list() {
			return array(
				array(
					'slug'         => 'greatscott',
					'channel_name' => 'GreatScott!',
					'channel_url'  => 'https://www.youtube.com/@greatscottlab',
				),
				array(
					'slug'         => 'electroboom',
					'channel_name' => 'ElectroBOOM',
					'channel_url'  => 'https://www.youtube.com/@ElectroBOOM',
				),
				array(
					'slug'         => 'eevblog',
					'channel_name' => 'EEVblog',
					'channel_url'  => 'https://www.youtube.com/@EEVblog',
				),
				array(
					'slug'         => 'andreas-spiess',
					'channel_name' => 'Andreas Spiess',
					'channel_url'  => 'https://www.youtube.com/@AndreasSpiess',
				),
				array(
					'slug'         => 'dronebot-workshop',
					'channel_name' => 'DroneBot Workshop',
					'channel_url'  => 'https://www.youtube.com/@Dronebotworkshop',
				),
				array(
					'slug'         => 'paul-mcwhorter',
					'channel_name' => 'Paul McWhorter',
					'channel_url'  => 'https://www.youtube.com/@paulmcwhorter',
				),
				array(
					'slug'         => 'ben-eater',
					'channel_name' => 'Ben Eater',
					'channel_url'  => 'https://www.youtube.com/@BenEater',
				),
				array(
					'slug'         => 'adafruit-industries',
					'channel_name' => 'Adafruit Industries',
					'channel_url'  => 'https://www.youtube.com/@adafruit',
				),
				array(
					'slug'         => 'sparkfun-electronics',
					'channel_name' => 'SparkFun Electronics',
					'channel_url'  => 'https://www.youtube.com/@sparkfun',
				),
				array(
					'slug'         => 'digi-key',
					'channel_name' => 'Digi-Key',
					'channel_url'  => 'https://www.youtube.com/@digikey',
				),
				array(
					'slug'         => 'element14-presents',
					'channel_name' => 'element14 presents',
					'channel_url'  => 'https://www.youtube.com/@element14presents',
				),
				array(
					'slug'         => 'the-engineering-mindset',
					'channel_name' => 'The Engineering Mindset',
					'channel_url'  => 'https://www.youtube.com/@EngineeringMindset',
				),
				array(
					'slug'         => 'practical-engineering',
					'channel_name' => 'Practical Engineering',
					'channel_url'  => 'https://www.youtube.com/@PracticalEngineeringChannel',
				),
			);
		}

		/**
		 * Run a YouTube Data API GET request.
		 *
		 * @param string $endpoint API endpoint.
		 * @param array  $args Query args.
		 * @param string $api_key API key.
		 * @return array|WP_Error
		 */
		private function youtube_api_get( $endpoint, array $args, $api_key ) {
			$url      = add_query_arg( array_merge( $args, array( 'key' => $api_key ) ), 'https://www.googleapis.com/youtube/v3/' . ltrim( $endpoint, '/' ) );
			$response = wp_remote_get(
				$url,
				array(
					'timeout'     => 18,
					'redirection' => 2,
				)
			);
			if ( is_wp_error( $response ) ) {
				return $response;
			}

			$code = wp_remote_retrieve_response_code( $response );
			$body = json_decode( wp_remote_retrieve_body( $response ), true );
			if ( 200 !== (int) $code ) {
				$message = is_array( $body ) && ! empty( $body['error']['message'] ) ? $body['error']['message'] : sprintf( 'HTTP %d', (int) $code );
				return new WP_Error( 'zenclau_v1_youtube_api_failed', sprintf( 'V1: YouTube API failed. %s', $message ), array( 'status' => 500 ) );
			}

			return is_array( $body ) ? $body : array();
		}

		/**
		 * Pending channel videos.
		 *
		 * @return WP_REST_Response
		 */
		public function get_pending_channel_videos() {
			return rest_ensure_response( Zenclau_V1_Content_Model::get_pending_channel_videos() );
		}

		/**
		 * Update channel video.
		 *
		 * @param WP_REST_Request $request Request.
		 * @return WP_REST_Response|WP_Error
		 */
		public function update_channel_video( WP_REST_Request $request ) {
			$params = (array) $request->get_json_params();
			$result = Zenclau_V1_Content_Model::update_channel_video_status( absint( $request['id'] ), $params['status'] ?? 'pending' );
			return is_wp_error( $result ) ? $result : rest_ensure_response( $result );
		}

		/**
		 * Get channel cron settings.
		 *
		 * @return WP_REST_Response
		 */
		public function get_channel_cron_settings() {
			return rest_ensure_response( Zenclau_V1_Content_System::get_channel_cron_settings() );
		}

		/**
		 * Update channel cron settings.
		 *
		 * @param WP_REST_Request $request Request.
		 * @return WP_REST_Response
		 */
		public function update_channel_cron_settings( WP_REST_Request $request ) {
			$params = (array) $request->get_json_params();
			return rest_ensure_response( Zenclau_V1_Content_System::update_channel_cron_settings( $params ) );
		}

		/**
		 * Products list.
		 *
		 * @return WP_REST_Response
		 */
		public function get_products() {
			return rest_ensure_response( Zenclau_V1_Content_Model::get_products() );
		}

		/**
		 * Single product.
		 *
		 * @param WP_REST_Request $request Request.
		 * @return WP_REST_Response|WP_Error
		 */
		public function get_product( WP_REST_Request $request ) {
			$product = Zenclau_V1_Content_Model::get_product( absint( $request['id'] ) );
			if ( ! $product ) {
				return new WP_Error( 'zenclau_v1_product_not_found', __( 'V1: Product not found.', 'bhdt-core-system' ), array( 'status' => 404 ) );
			}

			return rest_ensure_response( $product );
		}

		/**
		 * Create product.
		 *
		 * @param WP_REST_Request $request Request.
		 * @return WP_REST_Response|WP_Error
		 */
		public function create_product( WP_REST_Request $request ) {
			$result = Zenclau_V1_Content_Model::create_product( (array) $request->get_json_params() );
			return is_wp_error( $result ) ? $result : rest_ensure_response( $result );
		}

		/**
		 * Update product.
		 *
		 * @param WP_REST_Request $request Request.
		 * @return WP_REST_Response|WP_Error
		 */
		public function update_product( WP_REST_Request $request ) {
			$result = Zenclau_V1_Content_Model::update_product( absint( $request['id'] ), (array) $request->get_json_params() );
			return is_wp_error( $result ) ? $result : rest_ensure_response( $result );
		}

		/**
		 * Delete product.
		 *
		 * @param WP_REST_Request $request Request.
		 * @return WP_REST_Response|WP_Error
		 */
		public function delete_product( WP_REST_Request $request ) {
			$result = Zenclau_V1_Content_Model::delete_product( absint( $request['id'] ) );
			return is_wp_error( $result ) ? $result : rest_ensure_response( $result );
		}

		/**
		 * Create queue item.
		 *
		 * @param WP_REST_Request $request Request.
		 * @return WP_REST_Response|WP_Error
		 */
		public function create_queue_item( WP_REST_Request $request ) {
			$result = Zenclau_V1_Content_Model::create_queue_item( (array) $request->get_json_params() );
			return is_wp_error( $result ) ? $result : rest_ensure_response( $result );
		}

		/**
		 * Pending queue list.
		 *
		 * @return WP_REST_Response
		 */
		public function get_pending_queue() {
			return rest_ensure_response( Zenclau_V1_Content_Model::get_pending_queue() );
		}

		/**
		 * Update queue item.
		 *
		 * @param WP_REST_Request $request Request.
		 * @return WP_REST_Response|WP_Error
		 */
		public function update_queue_item( WP_REST_Request $request ) {
			$result = Zenclau_V1_Content_Model::update_queue_item( absint( $request['id'] ), (array) $request->get_json_params() );
			return is_wp_error( $result ) ? $result : rest_ensure_response( $result );
		}

		/**
		 * Finish a queue item by creating a pending WordPress post for later editing.
		 *
		 * @param WP_REST_Request $request Request.
		 * @return WP_REST_Response|WP_Error
		 */
		public function finish_queue_item( WP_REST_Request $request ) {
			$id      = absint( $request['id'] );
			$params  = (array) $request->get_json_params();
			$current = Zenclau_V1_Content_Model::get_queue_item( $id );

			if ( ! $current ) {
				return new WP_Error( 'zenclau_v1_queue_not_found', __( 'V1: Queue item not found.', 'bhdt-core-system' ), array( 'status' => 404 ) );
			}

			$destination = $this->normalize_finish_destination( $params['destination'] ?? 'post' );
			if ( is_wp_error( $destination ) ) {
				return $destination;
			}

			$content = $current['content_json'];
			if ( isset( $params['content_json'] ) && is_array( $params['content_json'] ) ) {
				$content = array_merge( $content, $params['content_json'] );
			}

			$title          = sanitize_text_field( $content['title'] ?? '' );
			$summary        = $this->clean_generated_article_text( sanitize_textarea_field( $content['summary'] ?? '' ) );
			$review_content = $this->clean_generated_article_text( sanitize_textarea_field( $content['review_content'] ?? ( $content['body'] ?? '' ) ) );

			if ( '' === $title ) {
				return new WP_Error( 'zenclau_v1_finish_missing_title', __( 'V1: Title is required before finishing.', 'bhdt-core-system' ), array( 'status' => 400 ) );
			}

			$content['title']          = $title;
			$content['summary']        = $summary;
			$content['review_content'] = $review_content;

			$postarr = array(
				'post_type'    => $destination['post_type'],
				'post_status'  => 'pending',
				'post_title'   => $title,
				'post_excerpt' => $summary,
				'post_content' => $this->build_finished_post_content( $content, $current ),
			);

			$post_id = wp_insert_post( wp_slash( $postarr ), true );
			if ( is_wp_error( $post_id ) ) {
				return $post_id;
			}

			update_post_meta( $post_id, '_zenclau_v1_queue_id', $id );
			update_post_meta( $post_id, '_zenclau_v1_product_id', absint( $current['product_id'] ) );
			update_post_meta( $post_id, '_zenclau_v1_youtube_url', esc_url_raw( $current['youtube_url'] ) );
			update_post_meta( $post_id, '_zenclau_v1_destination', $destination['key'] );

			if ( ! empty( $content['comparison_table'] ) ) {
				$comparison_json = wp_json_encode( $content['comparison_table'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
				if ( false !== $comparison_json ) {
					update_post_meta( $post_id, 'bhdt_specs', $comparison_json );
					if ( 'bhdt_comparison' === $destination['post_type'] ) {
						update_post_meta( $post_id, '_bhdt_comparison_specs', $comparison_json );
					}
				}
			}

			$content['finished_destination'] = $destination['key'];
			$content['finished_post_type']   = $destination['post_type'];
			$content['finished_post_id']     = (int) $post_id;
			$content['finished_at']          = current_time( 'mysql' );
			$this->cleanup_unused_pdf_images( $content );
			if ( ! empty( $content['pdf_images'] ) && is_array( $content['pdf_images'] ) ) {
				$content['pdf_images'] = array_values( array_slice( $content['pdf_images'], 0, $this->get_pdf_image_limit_for_content( $content ) ) );
			}

			$updated = Zenclau_V1_Content_Model::update_queue_item(
				$id,
				array(
					'status'       => 'finished',
					'content_json' => $content,
				)
			);
			if ( is_wp_error( $updated ) ) {
				return $updated;
			}

			return rest_ensure_response(
				array(
					'success'     => true,
					'queue_item'  => $updated,
					'post_id'     => (int) $post_id,
					'post_type'   => $destination['post_type'],
					'destination' => $destination['key'],
					'edit_url'    => get_edit_post_link( $post_id, 'raw' ),
					'status'      => 'pending',
				)
			);
		}

		/**
		 * Normalize Finish destination into a WordPress post type.
		 *
		 * @param string $destination Destination key.
		 * @return array|WP_Error
		 */
		private function normalize_finish_destination( $destination ) {
			$key = sanitize_key( (string) $destination );
			$map = array(
				'post'       => array(
					'key'       => 'post',
					'post_type' => 'post',
				),
				'review'     => array(
					'key'       => 'review',
					'post_type' => 'bhdt_review',
				),
				'comparison' => array(
					'key'       => 'comparison',
					'post_type' => 'bhdt_comparison',
				),
			);

			if ( ! isset( $map[ $key ] ) ) {
				return new WP_Error( 'zenclau_v1_finish_invalid_destination', __( 'V1: Finish destination must be Post, Review, or So sánh.', 'bhdt-core-system' ), array( 'status' => 400 ) );
			}

			if ( ! post_type_exists( $map[ $key ]['post_type'] ) ) {
				return new WP_Error(
					'zenclau_v1_finish_missing_post_type',
					sprintf( 'V1: Post type %s is not registered yet.', $map[ $key ]['post_type'] ),
					array( 'status' => 500 )
				);
			}

			return $map[ $key ];
		}

		/**
		 * Remove extracted PDF image files that are not inserted into the final post.
		 *
		 * @param array $content Queue content JSON.
		 * @return void
		 */
		private function cleanup_unused_pdf_images( array $content ) {
			if ( empty( $content['pdf_images'] ) || ! is_array( $content['pdf_images'] ) ) {
				return;
			}

			$upload = wp_upload_dir();
			if ( empty( $upload['basedir'] ) || empty( $upload['baseurl'] ) ) {
				return;
			}

			$base_dir = wp_normalize_path( trailingslashit( $upload['basedir'] ) . 'zenclau-pdf-assets' );
			$base_url = trailingslashit( $upload['baseurl'] ) . 'zenclau-pdf-assets';
			$keep     = array();
			$dirs     = array();
			$count    = 0;
			$limit    = $this->get_pdf_image_limit_for_content( $content );

			foreach ( $content['pdf_images'] as $image ) {
				if ( ! is_array( $image ) || empty( $image['url'] ) ) {
					continue;
				}
				$path = $this->pdf_asset_url_to_path( (string) $image['url'], $base_url, $base_dir );
				if ( '' === $path ) {
					continue;
				}
				$dirs[ dirname( $path ) ] = true;
				++$count;
				if ( $count <= $limit ) {
					$keep[ $path ] = true;
				}
			}

			foreach ( array_keys( $dirs ) as $dir ) {
				if ( ! is_dir( $dir ) ) {
					continue;
				}
				foreach ( glob( trailingslashit( $dir ) . '*.{png,jpg,jpeg,webp}', GLOB_BRACE ) as $file ) {
					$file = wp_normalize_path( $file );
					if ( 0 !== strpos( $file, $base_dir ) || isset( $keep[ $file ] ) ) {
						continue;
					}
					wp_delete_file( $file );
				}
			}
		}

		/**
		 * Convert a public PDF asset URL to a local uploads path.
		 *
		 * @param string $url Asset URL.
		 * @param string $base_url Asset base URL.
		 * @param string $base_dir Asset base directory.
		 * @return string
		 */
		private function pdf_asset_url_to_path( $url, $base_url, $base_dir ) {
			if ( 0 !== strpos( $url, $base_url ) ) {
				return '';
			}
			$relative = ltrim( substr( $url, strlen( $base_url ) ), '/\\' );
			$path     = wp_normalize_path( trailingslashit( $base_dir ) . $relative );
			if ( 0 !== strpos( $path, $base_dir ) ) {
				return '';
			}
			return $path;
		}

		/**
		 * Get the PDF image limit for generated content.
		 *
		 * @param array $content Queue content JSON.
		 * @return int
		 */
		private function get_pdf_image_limit_for_content( array $content ) {
			return ( isset( $content['product_1'], $content['product_2'] ) || ! empty( $content['recommendation'] ) ) ? 12 : 6;
		}

		/**
		 * Build editable HTML content for the destination post.
		 *
		 * @param array $content Queue content JSON.
		 * @param array $queue_item Queue item.
		 * @return string
		 */
		private function build_finished_post_content( array $content, array $queue_item ) {
			$summary        = $this->clean_generated_article_text( (string) ( $content['summary'] ?? '' ) );
			$review_content = $this->clean_generated_article_text( (string) ( $content['review_content'] ?? '' ) );
			$youtube_url    = esc_url_raw( $queue_item['youtube_url'] ?? '' );
			$html           = '';

			if ( '' !== trim( $summary ) ) {
				$html .= '<h2>Tóm tắt</h2>' . wp_kses_post( wpautop( $summary ) );
			}

			if ( '' !== trim( $review_content ) ) {
				$html .= '<h2>Nội dung review</h2>' . wp_kses_post( wpautop( $review_content ) );
			}

			if ( ! empty( $content['comparison_table'] ) && is_array( $content['comparison_table'] ) ) {
				$html .= '<h2>Bảng so sánh</h2>';
				$html .= '<table><thead><tr><th>Tiêu chí</th><th>Giá trị</th><th>Ghi chú</th></tr></thead><tbody>';
				foreach ( $content['comparison_table'] as $row ) {
					if ( ! is_array( $row ) ) {
						continue;
					}
					$html .= '<tr>';
					$html .= '<td>' . wp_kses_post( $this->clean_generated_article_text( (string) ( $row['criteria'] ?? '' ) ) ) . '</td>';
					$product_value = $row['product_value'] ?? '';
					if ( '' === $product_value && ( isset( $row['product_1_value'] ) || isset( $row['product_2_value'] ) ) ) {
						$product_value = trim( 'SP1: ' . ( $row['product_1_value'] ?? '' ) . ' | SP2: ' . ( $row['product_2_value'] ?? '' ) );
					}
					$html .= '<td>' . wp_kses_post( $this->clean_generated_article_text( (string) $product_value ) ) . '</td>';
					$html .= '<td>' . wp_kses_post( $this->clean_generated_article_text( (string) ( $row['winner_or_note'] ?? ( $row['notes'] ?? '' ) ) ) ) . '</td>';
					$html .= '</tr>';
				}
				$html .= '</tbody></table>';
			}

			if ( ! empty( $content['pdf_images'] ) && is_array( $content['pdf_images'] ) ) {
				$html .= '<h2>Hình ảnh từ PDF</h2>';
				$count = 0;
				foreach ( $content['pdf_images'] as $image ) {
					if ( ! is_array( $image ) || empty( $image['url'] ) ) {
						continue;
					}
					++$count;
					if ( $count > $this->get_pdf_image_limit_for_content( $content ) ) {
						break;
					}
					$caption = ! empty( $image['caption'] ) ? (string) $image['caption'] : ( ! empty( $image['filename'] ) ? (string) $image['filename'] : sprintf( 'PDF page %s', (string) ( $image['source_page'] ?? '' ) ) );
					$caption = 'Nguồn hình ảnh từ: ' . $caption;
					$html   .= '<figure class="zenclau-v1-pdf-image" style="max-width: 760px; width: 100%; margin: 24px auto;">';
					$html   .= '<img src="' . esc_url( $image['url'] ) . '" alt="' . esc_attr( $caption ) . '" loading="lazy" style="display: block; max-width: 100%; width: 100%; height: auto; object-fit: contain;" />';
					$html   .= '<figcaption style="font-size: 13px; color: #646970; text-align: center; margin-top: 6px;">' . esc_html( $caption ) . '</figcaption>';
					$html   .= '</figure>';
				}
			}

			$known_fields = array(
				'title',
				'summary',
				'review_content',
				'body',
				'comparison_table',
				'product_1',
				'product_2',
				'source_summary',
				'key_specifications',
				'official_claims',
				'pdf_details',
				'recommended_use_cases',
				'limitations_or_notes',
				'pdf_images',
				'_token_usage',
			);
			foreach ( $content as $key => $value ) {
				if ( in_array( $key, $known_fields, true ) || null === $value || '' === $value ) {
					continue;
				}
				$html .= '<h2>' . esc_html( $this->humanize_schema_key( $key ) ) . '</h2>';
				if ( is_array( $value ) || is_object( $value ) ) {
					$json = wp_json_encode( $value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
					$html .= '<pre>' . esc_html( false === $json ? '' : $json ) . '</pre>';
				} else {
					$html .= wp_kses_post( wpautop( $this->clean_generated_article_text( (string) $value ) ) );
				}
			}

			if ( '' !== $youtube_url ) {
				$html .= '<p><strong>YouTube source:</strong> <a href="' . esc_url( $youtube_url ) . '">' . esc_html( $youtube_url ) . '</a></p>';
			}

			return $html;
		}

		/**
		 * Remove unwanted Markdown while preserving intentional bold text.
		 *
		 * @param string $text Generated article text.
		 * @return string
		 */
		private function clean_generated_article_text( $text ) {
			$text = str_replace( array( "\r\n", "\r" ), "\n", (string) $text );
			$text = $this->normalize_generated_headings( $text );
			$text = preg_replace_callback(
				'/\*\*([^*\n][^*\n]*?)\*\*/u',
				function ( $matches ) {
					return '<strong>' . esc_html( trim( $matches[1] ) ) . '</strong>';
				},
				$text
			);
			$text = preg_replace( '/(^|[\s(])\*([^*\n]+?)\*([\s).,:;!?]|$)/u', '$1$2$3', $text );
			$text = preg_replace( '/^[ \t]*[-*+][ \t]+/m', '', $text );
			$text = preg_replace( '/[ \t]+$/m', '', $text );
			$text = preg_replace( "/\n{3,}/", "\n\n", $text );

			return trim( $text );
		}

		/**
		 * Convert Markdown headings into plain numbered headings.
		 *
		 * @param string $text Generated article text.
		 * @return string
		 */
		private function normalize_generated_headings( $text ) {
			$heading_index = 1;
			$lines         = explode( "\n", (string) $text );

			foreach ( $lines as $index => $line ) {
				if ( ! preg_match( '/^[ \t]{0,3}#{1,6}[ \t]+(.+)$/u', $line, $matches ) ) {
					continue;
				}

				$heading = trim( $matches[1] );
				if ( ! preg_match( '/^(?:[IVXLCDM]+\.\s+|\d+[\.)]\s+)/i', $heading ) ) {
					$heading = $this->int_to_roman( $heading_index ) . '. ' . $heading;
				}
				$heading_index++;
				$lines[ $index ] = $heading;
			}

			return implode( "\n", $lines );
		}

		/**
		 * Convert a small positive integer to a Roman numeral.
		 *
		 * @param int $number Number.
		 * @return string
		 */
		private function int_to_roman( $number ) {
			$number = max( 1, min( 20, (int) $number ) );
			$map    = array(
				10 => 'X',
				9  => 'IX',
				5  => 'V',
				4  => 'IV',
				1  => 'I',
			);
			$roman  = '';

			foreach ( $map as $value => $letter ) {
				while ( $number >= $value ) {
					$roman .= $letter;
					$number -= $value;
				}
			}

			return $roman;
		}

		/**
		 * Convert a JSON key into a readable section title.
		 *
		 * @param string $key JSON key.
		 * @return string
		 */
		private function humanize_schema_key( $key ) {
			$key = str_replace( array( '_', '-' ), ' ', (string) $key );
			return ucwords( $key );
		}

		/**
		 * Run the Python V1 scraper.
		 *
		 * @param WP_REST_Request $request Request.
		 * @return WP_REST_Response|WP_Error
		 */
		public function run_manual_scraper( WP_REST_Request $request ) {
			$params      = (array) $request->get_json_params();
			$product_id  = absint( $params['product_id'] ?? 0 );
			$youtube_url = esc_url_raw( $params['youtube_url'] ?? '' );
			$prompt_rules = isset( $params['prompt_rules'] ) ? sanitize_textarea_field( wp_unslash( $params['prompt_rules'] ) ) : '';
			$json_schema = isset( $params['json_schema'] ) ? trim( (string) wp_unslash( $params['json_schema'] ) ) : '';
			$schema_template_id = isset( $params['schema_template_id'] ) ? sanitize_key( $params['schema_template_id'] ) : '';
			$max_output_tokens = isset( $params['max_output_tokens'] ) ? absint( $params['max_output_tokens'] ) : 8192;
			$max_output_tokens = max( 1024, min( 65536, $max_output_tokens ) );
			$compare_product_name = isset( $params['compare_product_name'] ) ? sanitize_text_field( wp_unslash( $params['compare_product_name'] ) ) : '';
			$compare_official_url = isset( $params['compare_official_url'] ) ? esc_url_raw( $params['compare_official_url'] ) : '';
			$compare_pdf_url      = isset( $params['compare_pdf_url'] ) ? esc_url_raw( $params['compare_pdf_url'] ) : '';
			$transcript_override  = isset( $params['transcript_override'] ) ? sanitize_textarea_field( wp_unslash( $params['transcript_override'] ) ) : '';
			$official_text_override = isset( $params['official_text_override'] ) ? sanitize_textarea_field( wp_unslash( $params['official_text_override'] ) ) : '';
			$is_compare_template  = 'template_3' === $schema_template_id || '' !== $compare_pdf_url || false !== strpos( $json_schema, 'product_2_value' );
			$max_pdf_images       = $is_compare_template ? 12 : 6;
			update_option( 'zenclau_v1_max_output_tokens', $max_output_tokens, false );
			$product     = Zenclau_V1_Content_Model::get_product( $product_id );

			if ( ! $product ) {
				return new WP_Error( 'zenclau_v1_scraper_missing_fields', __( 'V1: Product ID is required.', 'bhdt-core-system' ), array( 'status' => 400 ) );
			}
			if ( '' === trim( (string) ( $product['official_url'] ?? '' ) ) && '' === trim( (string) ( $product['pdf_url'] ?? '' ) ) ) {
				return new WP_Error( 'zenclau_v1_scraper_missing_sources', __( 'V1: Product needs Official URL or PDF URL.', 'bhdt-core-system' ), array( 'status' => 400 ) );
			}
			if ( $is_compare_template && '' === trim( $compare_pdf_url ) ) {
				return new WP_Error( 'zenclau_v1_compare_missing_pdf', __( 'V1: Product 2 PDF URL is required for comparison image extraction.', 'bhdt-core-system' ), array( 'status' => 400 ) );
			}

			$result = $this->run_worker_process( $product_id, $youtube_url, $prompt_rules, $json_schema, $max_output_tokens, $compare_product_name, $compare_official_url, $compare_pdf_url, $max_pdf_images, $transcript_override, $official_text_override );
			if ( is_wp_error( $result ) ) {
				return $result;
			}

			$item = Zenclau_V1_Content_Model::get_latest_queue_item_for_run( $product_id, $youtube_url );
			if ( ! $item ) {
				return new WP_Error( 'zenclau_v1_scraper_no_queue_item', __( 'V1: Worker finished but did not create a queue item.', 'bhdt-core-system' ), array( 'status' => 500 ) );
			}

			return rest_ensure_response( $item );
		}

		/**
		 * Resolve Python executable for PHP processes that do not inherit the user PATH.
		 *
		 * @return string
		 */
		private function get_python_bin() {
			$default_candidates = array(
				getenv( 'ZENCLAU_PYTHON_BIN' ),
				'C:\\Program Files\\Python311\\python.exe',
				'C:\\Users\\Tuan\\AppData\\Local\\Programs\\Python\\Python311\\python.exe',
				'python',
			);
			$candidates          = (array) apply_filters( 'zenclau_v1_python_bin_candidates', $default_candidates );

			foreach ( $candidates as $candidate ) {
				$candidate = is_string( $candidate ) ? trim( $candidate ) : '';
				if ( '' === $candidate ) {
					continue;
				}
				if ( 'python' === $candidate || file_exists( $candidate ) ) {
					return (string) apply_filters( 'zenclau_v1_python_bin', $candidate );
				}
			}

			return (string) apply_filters( 'zenclau_v1_python_bin', 'python' );
		}

		/**
		 * Create a temp file for large worker inputs.
		 *
		 * @param string $prefix Filename prefix.
		 * @return string|false
		 */
		private function create_worker_temp_file( $prefix ) {
			if ( ! function_exists( 'wp_tempnam' ) ) {
				require_once ABSPATH . 'wp-admin/includes/file.php';
			}

			if ( function_exists( 'wp_tempnam' ) ) {
				return wp_tempnam( $prefix );
			}

			return tempnam( get_temp_dir(), sanitize_file_name( $prefix ) );
		}

		/**
		 * Fetch and return a YouTube transcript for review before scraping.
		 *
		 * @param WP_REST_Request $request Request.
		 * @return WP_REST_Response|WP_Error
		 */
		public function get_youtube_transcript( WP_REST_Request $request ) {
			$params      = (array) $request->get_json_params();
			$youtube_url = esc_url_raw( $params['youtube_url'] ?? '' );
			$languages   = sanitize_text_field( $params['languages'] ?? 'vi,en' );

			if ( '' === $youtube_url ) {
				return new WP_Error( 'zenclau_v1_transcript_missing_url', __( 'V1: YouTube URL is required.', 'bhdt-core-system' ), array( 'status' => 400 ) );
			}

			$result = $this->run_transcript_process( $youtube_url, $languages );
			if ( is_wp_error( $result ) ) {
				return $result;
			}

			return rest_ensure_response( $result );
		}

		/**
		 * Fetch extracted official_url text for review before scraping.
		 *
		 * @param WP_REST_Request $request Request.
		 * @return WP_REST_Response|WP_Error
		 */
		public function get_official_text( WP_REST_Request $request ) {
			$params     = (array) $request->get_json_params();
			$product_id = absint( $params['product_id'] ?? 0 );
			$schema_template_id = isset( $params['schema_template_id'] ) ? sanitize_key( $params['schema_template_id'] ) : '';
			$json_schema        = isset( $params['json_schema'] ) ? trim( (string) wp_unslash( $params['json_schema'] ) ) : '';
			$compare_product_name = isset( $params['compare_product_name'] ) ? sanitize_text_field( wp_unslash( $params['compare_product_name'] ) ) : '';
			$compare_official_url = isset( $params['compare_official_url'] ) ? esc_url_raw( $params['compare_official_url'] ) : '';
			$compare_pdf_url      = isset( $params['compare_pdf_url'] ) ? esc_url_raw( $params['compare_pdf_url'] ) : '';
			$is_compare_template  = 'template_3' === $schema_template_id || '' !== $compare_pdf_url || false !== strpos( $json_schema, 'product_2_value' );
			$max_pdf_images       = $is_compare_template ? 12 : 6;

			if ( ! Zenclau_V1_Content_Model::get_product( $product_id ) ) {
				return new WP_Error( 'zenclau_v1_official_text_missing_product', __( 'V1: Product ID is required.', 'bhdt-core-system' ), array( 'status' => 400 ) );
			}

			$result = $this->run_official_text_process( $product_id, $compare_product_name, $compare_official_url, $compare_pdf_url, $max_pdf_images );
			if ( is_wp_error( $result ) ) {
				return $result;
			}

			return rest_ensure_response( $result );
		}

		/**
		 * Execute worker script and capture output for diagnostics.
		 *
		 * @param int    $product_id Product id.
		 * @param string $youtube_url Optional YouTube URL.
		 * @param string $prompt_rules Gemini prompt rules.
		 * @param string $json_schema Gemini JSON schema prompt.
		 * @param int    $max_output_tokens Gemini max output tokens.
		 * @param string $compare_product_name Compare product name.
		 * @param string $compare_official_url Compare product official URL.
		 * @param string $compare_pdf_url Compare product PDF URL.
		 * @return true|WP_Error
		 */
		private function run_worker_process( $product_id, $youtube_url, $prompt_rules = '', $json_schema = '', $max_output_tokens = 8192, $compare_product_name = '', $compare_official_url = '', $compare_pdf_url = '', $max_pdf_images = 6, $transcript_override = '', $official_text_override = '' ) {
			$script   = BHDT_PATH . 'zenclau_worker_v1.py';
			$env_file = BHDT_PATH . '.env';

			if ( ! file_exists( $script ) ) {
				return new WP_Error( 'zenclau_v1_worker_missing', __( 'V1: zenclau_worker_v1.py was not found.', 'bhdt-core-system' ), array( 'status' => 500 ) );
			}

			if ( ! file_exists( $env_file ) ) {
				return new WP_Error( 'zenclau_v1_env_missing', __( 'V1: .env file was not found.', 'bhdt-core-system' ), array( 'status' => 500 ) );
			}

			if ( ! function_exists( 'proc_open' ) ) {
				return new WP_Error( 'zenclau_v1_proc_open_disabled', __( 'V1: proc_open is disabled, so WordPress cannot run the Python worker.', 'bhdt-core-system' ), array( 'status' => 500 ) );
			}

			$python = $this->get_python_bin();
			$upload = wp_upload_dir();
			$asset_dir = trailingslashit( $upload['basedir'] ) . 'zenclau-pdf-assets';
			$asset_url = trailingslashit( $upload['baseurl'] ) . 'zenclau-pdf-assets';
			$temp_files = array();
			$cmd    = escapeshellarg( $python )
				. ' ' . escapeshellarg( $script )
				. ' --product-id ' . absint( $product_id )
				. ' --env-file ' . escapeshellarg( $env_file )
				. ' --allow-missing-transcript'
				. ' --max-output-tokens ' . absint( $max_output_tokens )
				. ' --max-pdf-images ' . absint( $max_pdf_images )
				. ' --asset-dir ' . escapeshellarg( $asset_dir )
				. ' --asset-url-base ' . escapeshellarg( $asset_url );
			if ( '' !== trim( $youtube_url ) ) {
				$cmd .= ' --youtube-url ' . escapeshellarg( $youtube_url );
			}
			if ( '' !== trim( $prompt_rules ) ) {
				$cmd .= ' --prompt-rules-b64 ' . escapeshellarg( base64_encode( $prompt_rules ) );
			}
			if ( '' !== trim( $json_schema ) ) {
				$cmd .= ' --json-schema-b64 ' . escapeshellarg( base64_encode( $json_schema ) );
			}
			if ( '' !== trim( $transcript_override ) ) {
				$temp_file = $this->create_worker_temp_file( 'zenclau-transcript-override-' );
				if ( $temp_file && false !== file_put_contents( $temp_file, $transcript_override ) ) {
					$temp_files[] = $temp_file;
					$cmd .= ' --transcript-override-file ' . escapeshellarg( $temp_file );
				}
			}
			if ( '' !== trim( $official_text_override ) ) {
				$temp_file = $this->create_worker_temp_file( 'zenclau-official-text-override-' );
				if ( $temp_file && false !== file_put_contents( $temp_file, $official_text_override ) ) {
					$temp_files[] = $temp_file;
					$cmd .= ' --official-text-override-file ' . escapeshellarg( $temp_file );
				}
			}
			if ( '' !== trim( $compare_product_name ) ) {
				$cmd .= ' --compare-product-name ' . escapeshellarg( $compare_product_name );
			}
			if ( '' !== trim( $compare_official_url ) ) {
				$cmd .= ' --compare-official-url ' . escapeshellarg( $compare_official_url );
			}
			if ( '' !== trim( $compare_pdf_url ) ) {
				$cmd .= ' --compare-pdf-url ' . escapeshellarg( $compare_pdf_url );
			}

			$descriptor_spec = array(
				0 => array( 'pipe', 'r' ),
				1 => array( 'pipe', 'w' ),
				2 => array( 'pipe', 'w' ),
			);

			$process = proc_open( $cmd, $descriptor_spec, $pipes, BHDT_PATH );
			if ( ! is_resource( $process ) ) {
				foreach ( $temp_files as $temp_file ) {
					wp_delete_file( $temp_file );
				}
				return new WP_Error( 'zenclau_v1_worker_start_failed', __( 'V1: Could not start Python worker.', 'bhdt-core-system' ), array( 'status' => 500 ) );
			}

			fclose( $pipes[0] );
			$stdout    = stream_get_contents( $pipes[1] );
			$stderr    = stream_get_contents( $pipes[2] );
			$exit_code = proc_close( $process );
			foreach ( $temp_files as $temp_file ) {
				wp_delete_file( $temp_file );
			}

			if ( 0 !== $exit_code ) {
				$output = trim( (string) $stdout . "\n" . (string) $stderr );
				return new WP_Error(
					'zenclau_v1_worker_failed',
					sprintf( 'V1: Python worker failed with exit code %d. %s', (int) $exit_code, $output ),
					array( 'status' => 500 )
				);
			}

			return true;
		}

		/**
		 * Execute worker transcript-only mode.
		 *
		 * @param string $youtube_url YouTube URL.
		 * @param string $languages Transcript language priority.
		 * @return array|WP_Error
		 */
		private function run_transcript_process( $youtube_url, $languages ) {
			$script = BHDT_PATH . 'zenclau_worker_v1.py';

			if ( ! file_exists( $script ) ) {
				return new WP_Error( 'zenclau_v1_worker_missing', __( 'V1: zenclau_worker_v1.py was not found.', 'bhdt-core-system' ), array( 'status' => 500 ) );
			}

			if ( ! function_exists( 'proc_open' ) ) {
				return new WP_Error( 'zenclau_v1_proc_open_disabled', __( 'V1: proc_open is disabled, so WordPress cannot run the Python worker.', 'bhdt-core-system' ), array( 'status' => 500 ) );
			}

			$python = $this->get_python_bin();
			$cmd    = escapeshellarg( $python )
				. ' ' . escapeshellarg( $script )
				. ' --youtube-url ' . escapeshellarg( $youtube_url )
				. ' --languages ' . escapeshellarg( $languages )
				. ' --transcript-only';

			$descriptor_spec = array(
				0 => array( 'pipe', 'r' ),
				1 => array( 'pipe', 'w' ),
				2 => array( 'pipe', 'w' ),
			);

			$process = proc_open( $cmd, $descriptor_spec, $pipes, BHDT_PATH );
			if ( ! is_resource( $process ) ) {
				return new WP_Error( 'zenclau_v1_worker_start_failed', __( 'V1: Could not start Python worker.', 'bhdt-core-system' ), array( 'status' => 500 ) );
			}

			fclose( $pipes[0] );
			$stdout    = stream_get_contents( $pipes[1] );
			$stderr    = stream_get_contents( $pipes[2] );
			$exit_code = proc_close( $process );
			$payload   = json_decode( trim( (string) $stdout ), true );

			if ( 0 !== $exit_code ) {
				$message = is_array( $payload ) && ! empty( $payload['error'] ) ? $payload['error'] : trim( (string) $stdout . "\n" . (string) $stderr );
				return new WP_Error(
					'zenclau_v1_transcript_failed',
					sprintf( 'V1: Could not fetch transcript. %s', $message ),
					array( 'status' => 500 )
				);
			}

			if ( ! is_array( $payload ) || empty( $payload['transcript'] ) ) {
				return new WP_Error( 'zenclau_v1_transcript_invalid_output', __( 'V1: Transcript worker returned invalid output.', 'bhdt-core-system' ), array( 'status' => 500 ) );
			}

			return $payload;
		}

		/**
		 * Execute worker official-text-only mode.
		 *
		 * @param int $product_id Product id.
		 * @return array|WP_Error
		 */
		private function run_official_text_process( $product_id, $compare_product_name = '', $compare_official_url = '', $compare_pdf_url = '', $max_pdf_images = 6 ) {
			$script   = BHDT_PATH . 'zenclau_worker_v1.py';
			$env_file = BHDT_PATH . '.env';

			if ( ! file_exists( $script ) ) {
				return new WP_Error( 'zenclau_v1_worker_missing', __( 'V1: zenclau_worker_v1.py was not found.', 'bhdt-core-system' ), array( 'status' => 500 ) );
			}

			if ( ! file_exists( $env_file ) ) {
				return new WP_Error( 'zenclau_v1_env_missing', __( 'V1: .env file was not found.', 'bhdt-core-system' ), array( 'status' => 500 ) );
			}

			if ( ! function_exists( 'proc_open' ) ) {
				return new WP_Error( 'zenclau_v1_proc_open_disabled', __( 'V1: proc_open is disabled, so WordPress cannot run the Python worker.', 'bhdt-core-system' ), array( 'status' => 500 ) );
			}

			$python = $this->get_python_bin();
			$upload = wp_upload_dir();
			$asset_dir = trailingslashit( $upload['basedir'] ) . 'zenclau-pdf-assets';
			$asset_url = trailingslashit( $upload['baseurl'] ) . 'zenclau-pdf-assets';
			$cmd    = escapeshellarg( $python )
				. ' ' . escapeshellarg( $script )
				. ' --youtube-url ' . escapeshellarg( 'https://www.youtube.com/watch?v=dummy' )
				. ' --product-id ' . absint( $product_id )
				. ' --env-file ' . escapeshellarg( $env_file )
				. ' --official-text-only'
				. ' --max-pdf-images ' . absint( $max_pdf_images )
				. ' --asset-dir ' . escapeshellarg( $asset_dir )
				. ' --asset-url-base ' . escapeshellarg( $asset_url );
			if ( '' !== trim( $compare_product_name ) ) {
				$cmd .= ' --compare-product-name ' . escapeshellarg( $compare_product_name );
			}
			if ( '' !== trim( $compare_official_url ) ) {
				$cmd .= ' --compare-official-url ' . escapeshellarg( $compare_official_url );
			}
			if ( '' !== trim( $compare_pdf_url ) ) {
				$cmd .= ' --compare-pdf-url ' . escapeshellarg( $compare_pdf_url );
			}

			$descriptor_spec = array(
				0 => array( 'pipe', 'r' ),
				1 => array( 'pipe', 'w' ),
				2 => array( 'pipe', 'w' ),
			);

			$process = proc_open( $cmd, $descriptor_spec, $pipes, BHDT_PATH );
			if ( ! is_resource( $process ) ) {
				return new WP_Error( 'zenclau_v1_worker_start_failed', __( 'V1: Could not start Python worker.', 'bhdt-core-system' ), array( 'status' => 500 ) );
			}

			fclose( $pipes[0] );
			$stdout    = stream_get_contents( $pipes[1] );
			$stderr    = stream_get_contents( $pipes[2] );
			$exit_code = proc_close( $process );
			$payload   = json_decode( trim( (string) $stdout ), true );

			if ( 0 !== $exit_code ) {
				$message = is_array( $payload ) && ! empty( $payload['error'] ) ? $payload['error'] : trim( (string) $stdout . "\n" . (string) $stderr );
				return new WP_Error(
					'zenclau_v1_official_text_failed',
					sprintf( 'V1: Could not extract official text. %s', $message ),
					array( 'status' => 500 )
				);
			}

			if ( ! is_array( $payload ) || ! isset( $payload['text'] ) ) {
				return new WP_Error( 'zenclau_v1_official_text_invalid_output', __( 'V1: Official text worker returned invalid output.', 'bhdt-core-system' ), array( 'status' => 500 ) );
			}

			return $payload;
		}
	}
}

if ( ! class_exists( 'Zenclau_V1_Admin_UI' ) ) {
	/**
	 * Admin UI for Youtube Content System.
	 */
	final class Zenclau_V1_Admin_UI {
		/**
		 * Boot hooks.
		 *
		 * @return void
		 */
		public static function init() {
			$instance = new self();
			add_action( 'admin_menu', array( $instance, 'register_menu' ) );
		}

		/**
		 * Register admin menu.
		 *
		 * @return void
		 */
		public function register_menu() {
			add_menu_page(
				__( 'Youtube Content', 'bhdt-core-system' ),
				__( 'Youtube Content', 'bhdt-core-system' ),
				'manage_options',
				'zenclau-v1-content',
				array( $this, 'render_page' ),
				'dashicons-database-view',
				58
			);
		}

		/**
		 * Render admin page.
		 *
		 * @return void
		 */
		public function render_page() {
			Zenclau_V1_Content_Model::maybe_install_tables();
			$saved_max_output_tokens = max( 1024, min( 65536, absint( get_option( 'zenclau_v1_max_output_tokens', 16384 ) ) ) );
			$default_prompt_rules = "- Use Vietnamese.\n- Write a complete, information-rich product article, not a short summary.\n- Prefer detailed paragraphs, concrete specs, practical notes, and source-backed explanation.\n- If YouTube transcript is available, use it for hands-on observations.\n- If YouTube URL is empty, write from Official URL and PDF URL only.\n- Use Official URL and PDF URL source text for specifications and official claims.\n- Be factual and cautious. If evidence is missing, say it needs verification.\n- Do not invent prices or certifications.\n- Do not use Markdown heading syntax in generated article fields: no ### headings and no bullet-only outline.\n- Start section headings with plain numbering such as I., II., III. or 1., 2., 3.; bold is allowed only for product names or short key terms.\n- Keep comparison_table to 6-12 rows when the schema includes it.";
			$default_json_schema  = "{\n  \"title\": \"string\",\n  \"summary\": \"string\",\n  \"review_content\": \"string\",\n  \"comparison_table\": [\n    {\n      \"criteria\": \"string\",\n      \"product_value\": \"string\",\n      \"notes\": \"string\"\n    }\n  ]\n}";
			?>
			<div class="wrap zenclau-v1-admin" data-zenclau-v1-admin>
				<h1>Youtube Content System</h1>
				<p class="description">Quản lý channel, products, lấy transcript YouTube, chạy scraper thủ công và duyệt Content.</p>

				<nav class="zenclau-v1-tabs" aria-label="Youtube Content tabs">
					<button type="button" class="is-active" data-tab="channels">Channel Tags</button>
					<button type="button" data-tab="products">Products Manager</button>
					<button type="button" data-tab="scraper">Scraper Control</button>
					<button type="button" data-tab="queue">Content</button>
				</nav>

				<section class="zenclau-v1-panel" data-panel="products">
					<div class="zenclau-v1-grid">
						<form class="zenclau-v1-card" data-product-form>
							<h2>Thêm sản phẩm mới</h2>
							<label>
								<span>Name</span>
								<input type="text" name="name" required placeholder="ESP32-S3 WROOM" />
							</label>
							<label>
								<span>Official URL</span>
								<input type="url" name="official_url" placeholder="Trang nhà sản xuất / product page" />
							</label>
							<label>
								<span>PDF URL</span>
								<input type="url" name="pdf_url" placeholder="Datasheet / manual PDF" />
							</label>
							<label>
								<span>Search keywords</span>
								<textarea name="search_keywords" required rows="4" placeholder="esp32 s3, esp32-s3 review, board devkit"></textarea>
							</label>
							<button type="submit" class="button button-primary">Lưu Product</button>
						</form>

						<div class="zenclau-v1-card">
							<h2>Danh sách products</h2>
							<div class="zenclau-v1-list" data-products-list></div>
						</div>
					</div>
				</section>

				<section class="zenclau-v1-panel is-active" data-panel="channels">
					<div class="zenclau-v1-grid zenclau-v1-channel-grid">
						<div class="zenclau-v1-card">
							<h2>Channel watchlist</h2>
							<form class="zenclau-v1-nested-form" data-channel-form>
								<label>
									<span>Channel URL</span>
									<input type="url" name="channel_url" required placeholder="https://www.youtube.com/@Dronebotworkshop" />
								</label>
								<button type="submit" class="button button-primary">Add channel</button>
							</form>

							<form class="zenclau-v1-nested-form" data-youtube-key-form>
								<h3>YouTube API</h3>
								<label>
									<span>YOUTUBE_API_KEY</span>
									<div class="zenclau-v1-inline-control">
										<input type="password" name="key" autocomplete="off" data-youtube-key-input />
										<button type="button" class="button" data-youtube-key-toggle>Show</button>
									</div>
								</label>
								<button type="submit" class="button button-secondary">Save API key</button>
							</form>

							<form class="zenclau-v1-nested-form" data-channel-discover-form>
								<h3>Discover channels</h3>
								<label>
									<span>Keyword list</span>
									<textarea name="keywords" rows="5">electronics
arduino
esp32
pcb design
electronics repair
soldering</textarea>
								</label>
								<label>
									<span>Min subscribers</span>
									<input type="number" min="0" step="10000" name="min_subscribers" value="500000" />
								</label>
								<div class="zenclau-v1-row-actions">
									<button type="submit" class="button button-primary">Discover channels</button>
									<button type="button" class="button button-secondary" data-seed-trusted-channels>Seed trusted list</button>
								</div>
							</form>

							<form class="zenclau-v1-nested-form" data-video-discover-form>
								<h3>Discover videos</h3>
								<label>
									<span>Keyword list</span>
									<textarea name="keywords" rows="5">electronics review
arduino project
esp32 project
pcb design tutorial
electronics repair
soldering tutorial</textarea>
								</label>
								<label>
									<span>Min views</span>
									<input type="number" min="0" step="10000" name="min_views" value="100000" />
								</label>
								<button type="submit" class="button button-primary">Discover videos</button>
							</form>

							<form class="zenclau-v1-nested-form" data-channel-cron-form>
								<h3>Check schedule</h3>
								<label class="zenclau-v1-checkbox">
									<input type="checkbox" name="enabled" value="1" checked />
									<span>Enable cron</span>
								</label>
								<label>
									<span>Interval minutes</span>
									<input type="number" min="5" step="5" name="interval_minutes" value="60" />
								</label>
								<label>
									<span>Lookback days</span>
									<input type="number" min="1" step="1" name="lookback_days" value="30" />
								</label>
								<div class="zenclau-v1-row-actions">
									<button type="submit" class="button">Save schedule</button>
									<button type="button" class="button button-secondary" data-channel-check-now>Check now</button>
								</div>
								<small data-channel-cron-status></small>
							</form>
						</div>

						<div class="zenclau-v1-card">
							<div class="zenclau-v1-subtabs" aria-label="Channel tools">
								<button type="button" class="is-active" data-channel-subtab="suggested">Suggested Channels</button>
								<button type="button" data-channel-subtab="watchlist">Channels</button>
								<button type="button" data-channel-subtab="videos">Pending videos</button>
							</div>
							<div class="zenclau-v1-subpanel is-active" data-channel-subpanel="suggested">
								<h2>Suggested Channels</h2>
								<div class="zenclau-v1-row-actions">
									<button type="button" class="button button-secondary" data-approve-all-suggested>Approve all suggested</button>
								</div>
								<div class="zenclau-v1-list" data-suggested-channels-list></div>
							</div>
							<div class="zenclau-v1-subpanel" data-channel-subpanel="watchlist">
								<h2>Channels</h2>
								<div class="zenclau-v1-row-actions">
									<button type="button" class="button button-secondary" data-sort-channels-subs>Sort subscribers desc</button>
								</div>
								<div class="zenclau-v1-list" data-channels-list></div>
							</div>
							<div class="zenclau-v1-subpanel" data-channel-subpanel="videos">
								<h2>Pending videos</h2>
								<div class="zenclau-v1-row-actions">
									<button type="button" class="button button-secondary" data-sort-videos-views>Sort views desc</button>
									<button type="button" class="button button-secondary" data-sort-videos-newest>Sort newest desc</button>
								</div>
								<div class="zenclau-v1-list" data-channel-videos-list></div>
							</div>
						</div>
					</div>
				</section>

				<section class="zenclau-v1-panel" data-panel="scraper">
					<form class="zenclau-v1-card zenclau-v1-compact zenclau-v1-settings-card" data-gemini-key-form>
						<h2>Gemini API key</h2>
						<label>
							<span>GEMINI_API_KEY</span>
							<div class="zenclau-v1-inline-control">
								<input type="password" name="key" autocomplete="off" data-gemini-key-input />
								<button type="button" class="button" data-gemini-key-toggle>Show</button>
							</div>
						</label>
						<button type="submit" class="button button-secondary">Save API key</button>
					</form>

					<form class="zenclau-v1-card zenclau-v1-compact" data-scraper-form>
						<h2>Scraper Control</h2>
						<label>
							<span>YouTube URL optional</span>
							<input type="url" name="youtube_url" placeholder="https://www.youtube.com/watch?v=..." />
						</label>
						<label>
							<span>Transcript languages</span>
							<input type="text" name="languages" value="vi,en" />
						</label>
						<label>
							<span>Product ID</span>
							<select name="product_id" required data-products-select></select>
						</label>
						<label>
							<span>Max output tokens</span>
							<input type="number" name="max_output_tokens" min="1024" max="65536" step="1" value="<?php echo esc_attr( $saved_max_output_tokens ); ?>" />
						</label>
						<div class="zenclau-v1-schema-tools">
							<label>
								<span>JSON schema template</span>
								<select name="schema_template_id" data-schema-template-select></select>
							</label>
							<label>
								<span>Template name</span>
								<input type="text" name="schema_template_name" value="Mẫu 1 - Review kỹ thuật" data-schema-template-name />
							</label>
							<label>
								<span>Gemini JSON schema prompt</span>
								<textarea name="json_schema" rows="12" data-json-schema-input><?php echo esc_textarea( $default_json_schema ); ?></textarea>
							</label>
							<div class="zenclau-v1-row-actions">
								<button type="button" class="button" data-schema-template-new>New template</button>
								<button type="button" class="button" data-schema-template-save>Save template</button>
							</div>
						</div>
						<div class="zenclau-v1-nested-form" data-compare-product-fields hidden>
							<h3>Compare product 2</h3>
							<label>
								<span>Product 2 name</span>
								<input type="text" name="compare_product_name" placeholder="ESP32 DevKit / Arduino Uno R3..." disabled />
							</label>
							<label>
								<span>Product 2 Official URL</span>
								<input type="url" name="compare_official_url" placeholder="Trang nhà sản xuất / product page" disabled />
							</label>
							<label>
								<span>Product 2 PDF URL</span>
								<input type="url" name="compare_pdf_url" placeholder="Datasheet / manual PDF" disabled />
							</label>
						</div>
						<label>
							<span>Gemini prompt rules</span>
							<textarea name="prompt_rules" rows="9"><?php echo esc_textarea( $default_prompt_rules ); ?></textarea>
						</label>
						<button type="button" class="button" data-transcript-check>Check Transcript</button>
						<button type="button" class="button" data-official-text-check>Check Official + PDF Text</button>
						<button type="submit" class="button button-primary">Run Scraper</button>
						<div class="zenclau-v1-token-grid">
							<label>
								<span>Input tokens</span>
								<input type="text" name="input_tokens" readonly data-token-input value="0" />
							</label>
							<label>
								<span>Output tokens</span>
								<input type="text" name="output_tokens" readonly data-token-output value="0" />
							</label>
							<label>
								<span>Total tokens</span>
								<input type="text" name="total_tokens" readonly data-token-total value="0" />
							</label>
						</div>
						<small data-token-note></small>
						<label>
							<span>Transcript preview</span>
							<textarea name="transcript_override" rows="12" data-transcript-output></textarea>
						</label>
						<label>
							<span>Official + PDF text preview</span>
							<textarea name="official_text_override" rows="12" data-official-text-output></textarea>
						</label>
						<div class="zenclau-v1-pdf-preview">
							<h3>PDF images preview</h3>
							<div class="zenclau-v1-pdf-images" data-pdf-images-preview></div>
						</div>
					</form>
				</section>

				<section class="zenclau-v1-panel" data-panel="queue">
					<div class="zenclau-v1-grid zenclau-v1-editor-grid">
						<div class="zenclau-v1-card">
							<h2>Pending posts</h2>
							<div class="zenclau-v1-list" data-queue-list></div>
						</div>
						<form class="zenclau-v1-card" data-editor-form>
							<h2>Editor</h2>
							<input type="hidden" name="id" />
							<label>
								<span>Title</span>
								<input type="text" name="title" required />
							</label>
							<label>
								<span>Summary</span>
								<textarea name="summary" rows="4"></textarea>
							</label>
							<label>
								<span>Body</span>
								<textarea name="body" rows="12"></textarea>
							</label>
							<label>
								<span>Finish to</span>
								<select name="destination">
									<option value="post">Posts</option>
									<option value="review">Review</option>
									<option value="comparison">So sánh</option>
								</select>
							</label>
							<button type="submit" class="button button-primary">Finish</button>
						</form>
					</div>
				</section>

				<div class="zenclau-v1-toast" data-toast hidden></div>
			</div>

			<style>
				.zenclau-v1-admin {
					max-width: min(100%, 1420px);
					margin-right: 28px;
					color: #1d2327;
				}

				.zenclau-v1-admin h1 {
					margin: 26px 0 6px;
					font-size: 28px;
					font-weight: 700;
					letter-spacing: 0;
				}

				.zenclau-v1-admin > .description {
					margin: 0 0 20px;
					color: #646970;
					font-size: 14px;
				}

				.zenclau-v1-tabs {
					display: inline-flex;
					flex-wrap: wrap;
					gap: 6px;
					margin: 0 0 18px;
					padding: 6px;
					border: 1px solid #dcdcde;
					border-radius: 8px;
					background: #ffffff;
					box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
				}

				.zenclau-v1-tabs button {
					appearance: none;
					border: 0;
					border-radius: 6px;
					background: transparent;
					color: #50575e;
					padding: 10px 16px;
					font-weight: 700;
					line-height: 1.2;
					cursor: pointer;
				}

				.zenclau-v1-tabs button:hover,
				.zenclau-v1-tabs button:focus-visible {
					background: #f0f6fc;
					color: #0a4b78;
				}

				.zenclau-v1-tabs button.is-active {
					background: #135e96;
					color: #ffffff;
					box-shadow: 0 6px 16px rgba(19, 94, 150, 0.18);
				}

				.zenclau-v1-subtabs {
					display: flex;
					flex-wrap: wrap;
					gap: 6px;
					padding-bottom: 4px;
				}

				.zenclau-v1-subtabs button {
					border: 1px solid #c3c4c7;
					border-radius: 6px;
					background: #ffffff;
					padding: 8px 12px;
					color: #2c3338;
					font-weight: 700;
					cursor: pointer;
				}

				.zenclau-v1-subtabs button.is-active {
					border-color: #135e96;
					background: #135e96;
					color: #ffffff;
				}

				.zenclau-v1-subpanel {
					display: none;
				}

				.zenclau-v1-subpanel.is-active {
					display: grid;
					gap: 12px;
				}

				.zenclau-v1-panel {
					display: none;
				}

				.zenclau-v1-panel.is-active {
					display: block;
				}

				.zenclau-v1-grid {
					display: grid;
					grid-template-columns: minmax(340px, 0.8fr) minmax(520px, 1.2fr);
					align-items: stretch;
					gap: 22px;
				}

				.zenclau-v1-editor-grid {
					grid-template-columns: minmax(360px, 0.72fr) minmax(620px, 1.28fr);
				}

				.zenclau-v1-channel-grid {
					grid-template-columns: minmax(360px, 0.7fr) minmax(620px, 1.3fr);
				}

				.zenclau-v1-card {
					display: grid;
					align-content: start;
					gap: 16px;
					min-height: 360px;
					padding: 24px;
					border: 1px solid #dcdcde;
					border-radius: 8px;
					background: #ffffff;
					box-shadow: 0 10px 28px rgba(0, 0, 0, 0.045);
				}

				.zenclau-v1-compact {
					max-width: 860px;
					min-height: 0;
				}

				.zenclau-v1-settings-card {
					margin-bottom: 18px;
				}

				.zenclau-v1-inline-control {
					display: grid;
					grid-template-columns: minmax(0, 1fr) auto;
					gap: 8px;
					align-items: center;
				}

				.zenclau-v1-schema-tools {
					display: grid;
					gap: 12px;
					padding: 14px;
					border: 1px solid #dcdcde;
					border-radius: 8px;
					background: #f8fafc;
				}

				.zenclau-v1-nested-form {
					display: grid;
					gap: 12px;
					padding: 14px;
					border: 1px solid #dcdcde;
					border-radius: 8px;
					background: #f8fafc;
				}

				.zenclau-v1-nested-form h3 {
					margin: 0;
					font-size: 15px;
				}

				.zenclau-v1-token-grid {
					display: grid;
					grid-template-columns: repeat(3, minmax(0, 1fr));
					gap: 10px;
					padding: 14px;
					border: 1px solid #dcdcde;
					border-radius: 8px;
					background: #f8fafc;
				}

				.zenclau-v1-pdf-preview {
					display: grid;
					gap: 12px;
					padding: 14px;
					border: 1px solid #dcdcde;
					border-radius: 8px;
					background: #f8fafc;
				}

				.zenclau-v1-pdf-preview h3 {
					margin: 0;
					font-size: 15px;
				}

				.zenclau-v1-pdf-images {
					display: grid;
					grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
					gap: 12px;
				}

				.zenclau-v1-pdf-image-card {
					display: grid;
					gap: 8px;
					padding: 10px;
					border: 1px solid #dcdcde;
					border-radius: 8px;
					background: #fff;
				}

				.zenclau-v1-pdf-image-card img {
					width: 100%;
					aspect-ratio: 4 / 3;
					object-fit: contain;
					background: #f1f5f9;
					border-radius: 6px;
				}

				.zenclau-v1-pdf-image-card small {
					color: #646970;
					line-height: 1.35;
				}

				.zenclau-v1-card h2 {
					margin: 0;
					font-size: 18px;
					font-weight: 700;
					line-height: 1.3;
				}

				.zenclau-v1-card label {
					display: grid;
					gap: 8px;
					color: #1d2327;
					font-weight: 700;
				}

				.zenclau-v1-card .zenclau-v1-checkbox {
					display: flex;
					align-items: center;
					gap: 8px;
				}

				.zenclau-v1-card .zenclau-v1-checkbox input {
					width: auto;
					min-height: 0;
				}

				.zenclau-v1-card input,
				.zenclau-v1-card textarea,
				.zenclau-v1-card select {
					width: 100%;
					max-width: none;
					min-height: 42px;
					border-color: #c3c4c7;
					border-radius: 6px;
					padding: 9px 12px;
					font-size: 14px;
					line-height: 1.45;
					box-shadow: none;
				}

				.zenclau-v1-card textarea {
					min-height: 128px;
					resize: vertical;
				}

				.zenclau-v1-card input:focus,
				.zenclau-v1-card textarea:focus,
				.zenclau-v1-card select:focus {
					border-color: #2271b1;
					box-shadow: 0 0 0 2px rgba(34, 113, 177, 0.16);
				}

				.zenclau-v1-card .button-primary {
					min-height: 42px;
					border-radius: 6px;
					font-weight: 700;
				}

				.zenclau-v1-list {
					display: grid;
					align-content: start;
					gap: 10px;
					min-height: 220px;
				}

				.zenclau-v1-row {
					display: grid;
					gap: 7px;
					padding: 14px;
					border: 1px solid #dcdcde;
					border-radius: 8px;
					background: #f8fafc;
					color: #1d2327;
					text-align: left;
				}

				button.zenclau-v1-row {
					cursor: pointer;
				}

				button.zenclau-v1-row:hover,
				button.zenclau-v1-row:focus-visible {
					border-color: #2271b1;
					background: #f0f6fc;
					box-shadow: 0 0 0 2px rgba(34, 113, 177, 0.1);
				}

				.zenclau-v1-row strong {
					font-size: 14px;
				}

				.zenclau-v1-row span,
				.zenclau-v1-row small {
					color: #646970;
				}

				.zenclau-v1-row-actions {
					display: flex;
					flex-wrap: wrap;
					gap: 8px;
					margin-top: 4px;
				}

				.zenclau-v1-admin .button.is-busy {
					display: inline-flex;
					align-items: center;
					justify-content: center;
					gap: 8px;
					pointer-events: none;
					opacity: 0.82;
				}

				.zenclau-v1-spinner {
					width: 14px;
					height: 14px;
					border: 2px solid currentColor;
					border-right-color: transparent;
					border-radius: 50%;
					animation: zenclau-v1-spin 0.72s linear infinite;
				}

				@keyframes zenclau-v1-spin {
					to {
						transform: rotate(360deg);
					}
				}

				.zenclau-v1-toast {
					position: fixed;
					right: 24px;
					bottom: 24px;
					z-index: 100000;
					padding: 12px 16px;
					border-radius: 8px;
					background: #1d2327;
					box-shadow: 0 10px 28px rgba(0, 0, 0, 0.22);
					color: #ffffff;
				}

				@media (max-width: 1100px) {
					.zenclau-v1-grid,
					.zenclau-v1-editor-grid,
					.zenclau-v1-channel-grid {
						grid-template-columns: 1fr;
					}

					.zenclau-v1-card {
						min-height: 0;
					}
				}

				@media (max-width: 782px) {
					.zenclau-v1-admin {
						margin-right: 12px;
					}

					.zenclau-v1-tabs {
						display: grid;
					}
				}
			</style>

			<script>
			(() => {
				const root = document.querySelector('[data-zenclau-v1-admin]');
				if (!root) return;

				const apiBase = <?php echo wp_json_encode( esc_url_raw( rest_url( 'zenclau/v1' ) ) ); ?>;
				const nonce = <?php echo wp_json_encode( wp_create_nonce( 'wp_rest' ) ); ?>;
				let products = [];
				let pendingPosts = [];
				let schemaTemplates = [];
				let channels = [];
				let pendingChannelVideos = [];
				let suggestedChannels = [];

				const request = async (path, options = {}) => {
					const response = await fetch(`${apiBase}${path}`, {
						...options,
						headers: {
							'Content-Type': 'application/json',
							'X-WP-Nonce': nonce,
							...(options.headers || {}),
						},
					});
					const data = await response.json().catch(() => ({}));
					if (!response.ok) {
						throw new Error(data.message || 'Youtube Content API error');
					}
					return data;
				};

				const toast = (message) => {
					const el = root.querySelector('[data-toast]');
					el.textContent = message;
					el.hidden = false;
					window.setTimeout(() => {
						el.hidden = true;
					}, 2600);
				};

				const setButtonBusy = (button, busy, label = 'Processing...') => {
					if (!button) return;
					if (busy) {
						button.dataset.idleText = button.textContent.trim();
						button.disabled = true;
						button.classList.add('is-busy');
						button.innerHTML = `<span class="zenclau-v1-spinner" aria-hidden="true"></span><span>${escapeHtml(label)}</span>`;
						return;
					}
					button.disabled = false;
					button.classList.remove('is-busy');
					button.textContent = button.dataset.idleText || button.textContent;
					delete button.dataset.idleText;
				};

				const withBusy = async (button, label, task) => {
					if (button?.disabled) return;
					setButtonBusy(button, true, label);
					try {
						return await task();
					} catch (error) {
						toast(error.message || 'Request failed.');
						return undefined;
					} finally {
						setButtonBusy(button, false);
					}
				};

				const escapeHtml = (value) => String(value || '').replace(/[&<>"']/g, (char) => ({
					'&': '&amp;',
					'<': '&lt;',
					'>': '&gt;',
					'"': '&quot;',
					"'": '&#039;',
				}[char]));

				const setTokenUsage = (usage = {}) => {
					const input = root.querySelector('[data-token-input]');
					const output = root.querySelector('[data-token-output]');
					const total = root.querySelector('[data-token-total]');
					const note = root.querySelector('[data-token-note]');
					if (input) input.value = Number(usage.input_tokens || 0).toLocaleString();
					if (output) output.value = Number(usage.output_tokens || 0).toLocaleString();
					if (total) total.value = Number(usage.total_tokens || 0).toLocaleString();
					if (note) note.textContent = usage.estimated ? 'Token usage is estimated because model usage metadata was unavailable.' : '';
				};

				const renderPdfImagesPreview = (images = [], error = '') => {
					const target = root.querySelector('[data-pdf-images-preview]');
					if (!target) return;
					if (error) {
						target.innerHTML = `<p>${escapeHtml(error)}</p>`;
						return;
					}
					if (!images.length) {
						target.innerHTML = '<p>No PDF images extracted.</p>';
						return;
					}
					target.innerHTML = images.map((image) => `
						<div class="zenclau-v1-pdf-image-card">
							<img src="${escapeHtml(image.url)}" alt="${escapeHtml(image.caption || 'PDF image')}" loading="lazy" />
							<small>Page ${escapeHtml(image.source_page || '')} - ${Number(image.width || 0)} x ${Number(image.height || 0)}</small>
						</div>
					`).join('');
				};

				const renderProducts = () => {
					const list = root.querySelector('[data-products-list]');
					const select = root.querySelector('[data-products-select]');
					list.innerHTML = products.length ? products.map((product) => `
						<div class="zenclau-v1-row">
							<strong>#${product.id} - ${escapeHtml(product.name)}</strong>
							<span>Official: ${product.official_url ? escapeHtml(product.official_url) : 'Chưa có'}</span>
							<span>PDF: ${product.pdf_url ? escapeHtml(product.pdf_url) : 'Chưa có'}</span>
							<small>${escapeHtml(product.search_keywords)}</small>
							<div class="zenclau-v1-row-actions">
								<button type="button" class="button" data-delete-product="${product.id}">Xóa</button>
							</div>
						</div>
					`).join('') : '<p>Chưa có product nào.</p>';

					select.innerHTML = products.length ? products.map((product) => (
						`<option value="${product.id}">#${product.id} - ${escapeHtml(product.name)}</option>`
					)).join('') : '<option value="">Thêm product trước</option>';
				};

				const renderQueue = () => {
					const list = root.querySelector('[data-queue-list]');
					list.innerHTML = pendingPosts.length ? pendingPosts.map((item) => `
						<button type="button" class="zenclau-v1-row" data-open-post="${item.id}">
							<strong>#${item.id} - ${escapeHtml(item.content_json?.title || 'Untitled draft')}</strong>
							<span>${escapeHtml(item.product_name || `Product #${item.product_id}`)}</span>
							<small>${escapeHtml(item.youtube_url || '')}${item.content_json?._token_usage ? ` - ${Number(item.content_json._token_usage.total_tokens || 0).toLocaleString()} tokens` : ''}</small>
						</button>
					`).join('') : '<p>Không có post pending.</p>';
				};

				const renderSuggestedChannels = () => {
					const list = root.querySelector('[data-suggested-channels-list]');
					if (!list) return;
					list.innerHTML = suggestedChannels.length ? suggestedChannels.map((channel) => `
						<div class="zenclau-v1-row">
							<strong>${escapeHtml(channel.channel_name || channel.id)}</strong>
							<span>${escapeHtml(channel.channel_url || '')}</span>
							<small>${Number(channel.subscriber_count || 0).toLocaleString()} subs - ${Number(channel.video_count || 0).toLocaleString()} videos</small>
							<div class="zenclau-v1-row-actions">
								<button type="button" class="button button-primary" data-approve-suggested="${escapeHtml(channel.id)}">Approve</button>
								<button type="button" class="button" data-dismiss-suggested="${escapeHtml(channel.id)}">Dismiss</button>
							</div>
						</div>
					`).join('') : '<p>No suggested channels yet.</p>';
				};

				const renderChannels = () => {
					const list = root.querySelector('[data-channels-list]');
					if (!list) return;
					list.innerHTML = channels.length ? channels.map((channel) => `
						<div class="zenclau-v1-row">
							<strong>#${channel.id} - ${escapeHtml(channel.channel_name)}</strong>
							<span>${escapeHtml(channel.channel_url || '')}</span>
							<small>${Number(channel.subscriber_count || 0).toLocaleString()} subs - ${channel.is_active ? 'active' : 'paused'}${channel.last_checked_at ? ` - checked ${escapeHtml(channel.last_checked_at)}` : ''}</small>
							<div class="zenclau-v1-row-actions">
								<button type="button" class="button" data-toggle-channel="${channel.id}" data-next-active="${channel.is_active ? '0' : '1'}">${channel.is_active ? 'Pause' : 'Activate'}</button>
								<button type="button" class="button" data-delete-channel="${channel.id}">Delete</button>
							</div>
						</div>
					`).join('') : '<p>No channels yet.</p>';
				};

				const renderChannelVideos = () => {
					const list = root.querySelector('[data-channel-videos-list]');
					if (!list) return;
					list.innerHTML = pendingChannelVideos.length ? pendingChannelVideos.map((video) => `
						<div class="zenclau-v1-row">
							<strong>#${video.id} - ${escapeHtml(video.title || 'Untitled video')}</strong>
							<span>${escapeHtml(video.channel_name || `Channel #${video.channel_id}`)}</span>
							<small>${Number(video.view_count || 0).toLocaleString()} views - ${escapeHtml(video.video_url)}${video.published_at ? ` - ${escapeHtml(video.published_at)}` : ''}</small>
							<div class="zenclau-v1-row-actions">
								<button type="button" class="button button-primary" data-use-video="${video.id}">Use in scraper</button>
								<button type="button" class="button" data-dismiss-video="${video.id}">Dismiss</button>
							</div>
						</div>
					`).join('') : '<p>No pending videos.</p>';
				};

				const renderChannelCron = (settings) => {
					const form = root.querySelector('[data-channel-cron-form]');
					const status = root.querySelector('[data-channel-cron-status]');
					if (!form) return;
					form.elements.enabled.checked = !!settings.enabled;
					form.elements.interval_minutes.value = settings.interval_minutes || 60;
					form.elements.lookback_days.value = settings.lookback_days || 30;
					if (status) {
						status.textContent = settings.next_run ? `Next run timestamp: ${settings.next_run}` : 'No cron scheduled.';
					}
				};

				const loadProducts = async () => {
					products = await request('/products');
					renderProducts();
				};

				const loadQueue = async () => {
					pendingPosts = await request('/posts-queue/pending');
					renderQueue();
				};

				const loadChannels = async () => {
					channels = await request('/channels');
					renderChannels();
				};

				const loadSuggestedChannels = async () => {
					suggestedChannels = await request('/suggested-channels');
					renderSuggestedChannels();
				};

				const loadChannelVideos = async () => {
					pendingChannelVideos = await request('/channel-videos/pending');
					renderChannelVideos();
				};

				const loadChannelCron = async () => {
					const settings = await request('/settings/channel-cron');
					renderChannelCron(settings);
				};

				const loadYouTubeKey = async () => {
					const form = root.querySelector('[data-youtube-key-form]');
					if (!form) return;
					const result = await request('/settings/youtube-api-key');
					form.elements.key.value = result.key || '';
				};

				const loadGeminiKey = async () => {
					const form = root.querySelector('[data-gemini-key-form]');
					if (!form) return;
					const result = await request('/settings/gemini-api-key');
					form.elements.key.value = result.key || '';
				};

				const loadMaxOutputTokens = async () => {
					const form = root.querySelector('[data-scraper-form]');
					if (!form || !form.elements.max_output_tokens) return;
					const result = await request('/settings/max-output-tokens');
					form.elements.max_output_tokens.value = result.max_output_tokens || 16384;
					form.elements.max_output_tokens.defaultValue = form.elements.max_output_tokens.value;
				};

				const saveMaxOutputTokens = async () => {
					const form = root.querySelector('[data-scraper-form]');
					if (!form || !form.elements.max_output_tokens) return;
					const value = Number(form.elements.max_output_tokens.value || 16384);
					const result = await request('/settings/max-output-tokens', {
						method: 'PATCH',
						body: JSON.stringify({ max_output_tokens: value }),
					});
					form.elements.max_output_tokens.value = result.max_output_tokens || value;
					form.elements.max_output_tokens.defaultValue = form.elements.max_output_tokens.value;
				};

				const applySchemaTemplate = (template) => {
					const form = root.querySelector('[data-scraper-form]');
					if (!form || !template) return;
					form.elements.schema_template_id.value = template.id || '';
					form.elements.schema_template_name.value = template.name || '';
					if (template.prompt_rules) {
						form.elements.prompt_rules.value = template.prompt_rules;
					}
					form.elements.json_schema.value = template.schema || '';
					toggleCompareProductFields(template.id === 'template_3');
				};

				const toggleCompareProductFields = (enabled) => {
					const block = root.querySelector('[data-compare-product-fields]');
					if (!block) return;
					block.hidden = !enabled;
					block.querySelectorAll('input, textarea, select').forEach((input) => {
						input.disabled = !enabled;
						if (!enabled) input.value = '';
					});
				};

				const renderSchemaTemplates = () => {
					const select = root.querySelector('[data-schema-template-select]');
					if (!select) return;
					select.innerHTML = schemaTemplates.map((template) => (
						`<option value="${escapeHtml(template.id)}">${escapeHtml(template.name)}</option>`
					)).join('');
					applySchemaTemplate(schemaTemplates[0]);
				};

				const loadSchemaTemplates = async () => {
					const result = await request('/settings/schema-templates');
					schemaTemplates = result.templates || [];
					renderSchemaTemplates();
				};

				root.querySelectorAll('[data-tab]').forEach((button) => {
					button.addEventListener('click', () => {
						root.querySelectorAll('[data-tab]').forEach((item) => item.classList.toggle('is-active', item === button));
						root.querySelectorAll('[data-panel]').forEach((panel) => panel.classList.toggle('is-active', panel.dataset.panel === button.dataset.tab));
						if (button.dataset.tab === 'queue') loadQueue();
						if (button.dataset.tab === 'channels') Promise.all([loadChannels(), loadSuggestedChannels(), loadChannelVideos(), loadChannelCron(), loadYouTubeKey()]).catch((error) => toast(error.message));
						if (button.dataset.tab === 'scraper') loadGeminiKey().catch((error) => toast(error.message));
						if (button.dataset.tab === 'scraper') loadMaxOutputTokens().catch((error) => toast(error.message));
						if (button.dataset.tab === 'scraper') loadSchemaTemplates().catch((error) => toast(error.message));
					});
				});

				root.querySelectorAll('[data-channel-subtab]').forEach((button) => {
					button.addEventListener('click', () => {
						root.querySelectorAll('[data-channel-subtab]').forEach((item) => item.classList.toggle('is-active', item === button));
						root.querySelectorAll('[data-channel-subpanel]').forEach((panel) => panel.classList.toggle('is-active', panel.dataset.channelSubpanel === button.dataset.channelSubtab));
					});
				});

				root.querySelector('[data-sort-channels-subs]').addEventListener('click', () => {
					channels = [...channels].sort((a, b) => Number(b.subscriber_count || 0) - Number(a.subscriber_count || 0));
					renderChannels();
					toast('Sorted channels by subscribers.');
				});

				root.querySelector('[data-sort-videos-views]').addEventListener('click', () => {
					pendingChannelVideos = [...pendingChannelVideos].sort((a, b) => Number(b.view_count || 0) - Number(a.view_count || 0));
					renderChannelVideos();
					toast('Sorted videos by views.');
				});

				root.querySelector('[data-sort-videos-newest]').addEventListener('click', () => {
					pendingChannelVideos = [...pendingChannelVideos].sort((a, b) => new Date(b.published_at || 0).getTime() - new Date(a.published_at || 0).getTime());
					renderChannelVideos();
					toast('Sorted videos by newest date.');
				});

				root.querySelector('[data-schema-template-select]').addEventListener('change', (event) => {
					const template = schemaTemplates.find((item) => String(item.id) === String(event.currentTarget.value));
					applySchemaTemplate(template);
				});

				root.querySelector('[name="max_output_tokens"]').addEventListener('change', async () => {
					try {
						await saveMaxOutputTokens();
						toast('Saved max output tokens.');
					} catch (error) {
						toast(error.message);
					}
				});

				root.querySelector('[data-schema-template-new]').addEventListener('click', () => {
					const form = root.querySelector('[data-scraper-form]');
					form.elements.schema_template_id.value = 'new';
					form.elements.schema_template_name.value = `Mẫu ${schemaTemplates.length + 1}`;
					form.elements.prompt_rules.value = '- Use Vietnamese.\\n- Write with useful detail.\\n- Stay close to the provided sources and do not invent unsupported facts.';
					form.elements.json_schema.value = '{\n  "title": "string",\n  "summary": "string",\n  "review_content": "string"\n}';
					toggleCompareProductFields(false);
					toast('Đang tạo mẫu schema mới.');
				});

				root.querySelector('[data-schema-template-save]').addEventListener('click', async (event) => {
					const form = root.querySelector('[data-scraper-form]');
					let parsed;
					try {
						parsed = JSON.parse(form.elements.json_schema.value);
					} catch (error) {
						toast('JSON schema chưa hợp lệ.');
						return;
					}
					if (!parsed.title) {
						toast('Schema cần có field title.');
						return;
					}
					await withBusy(event.currentTarget, 'Saving...', async () => {
						const result = await request('/settings/schema-templates', {
							method: 'PATCH',
							body: JSON.stringify({
								id: form.elements.schema_template_id.value,
								name: form.elements.schema_template_name.value,
								prompt_rules: form.elements.prompt_rules.value,
								schema: JSON.stringify(parsed, null, 2),
							}),
						});
						schemaTemplates = result.templates || [];
						renderSchemaTemplates();
						applySchemaTemplate(result.template);
						toast('Đã lưu mẫu JSON schema.');
					});
				});

				root.querySelector('[data-gemini-key-toggle]').addEventListener('click', (event) => {
					const input = root.querySelector('[data-gemini-key-input]');
					const isHidden = input.type === 'password';
					input.type = isHidden ? 'text' : 'password';
					event.currentTarget.textContent = isHidden ? 'Hide' : 'Show';
				});

				root.querySelector('[data-gemini-key-form]').addEventListener('submit', async (event) => {
					event.preventDefault();
					const form = event.currentTarget;
					const button = form.querySelector('button[type="submit"]');
					await withBusy(button, 'Saving...', async () => {
						const payload = Object.fromEntries(new FormData(form).entries());
						await request('/settings/gemini-api-key', { method: 'PATCH', body: JSON.stringify(payload) });
						toast('Đã lưu Gemini API key.');
					});
				});

				root.querySelector('[data-product-form]').addEventListener('submit', async (event) => {
					event.preventDefault();
					const form = event.currentTarget;
					const button = form.querySelector('button[type="submit"]');
					await withBusy(button, 'Saving...', async () => {
						const payload = Object.fromEntries(new FormData(form).entries());
						await request('/products', { method: 'POST', body: JSON.stringify(payload) });
						form.reset();
						await loadProducts();
						toast('Đã thêm product.');
					});
				});

				root.querySelector('[data-products-list]').addEventListener('click', async (event) => {
					const button = event.target.closest('[data-delete-product]');
					if (!button) return;
					await withBusy(button, 'Deleting...', async () => {
						await request(`/products/${button.dataset.deleteProduct}`, { method: 'DELETE' });
						await loadProducts();
						await loadQueue();
						toast('Đã xóa product.');
					});
				});

				root.querySelector('[data-channel-form]').addEventListener('submit', async (event) => {
					event.preventDefault();
					const form = event.currentTarget;
					const button = form.querySelector('button[type="submit"]');
					await withBusy(button, 'Saving...', async () => {
						const payload = Object.fromEntries(new FormData(form).entries());
						await request('/channels', { method: 'POST', body: JSON.stringify(payload) });
						form.reset();
						await loadChannels();
						toast('Added channel.');
					});
				});

				root.querySelector('[data-youtube-key-toggle]').addEventListener('click', (event) => {
					const input = root.querySelector('[data-youtube-key-input]');
					const isHidden = input.type === 'password';
					input.type = isHidden ? 'text' : 'password';
					event.currentTarget.textContent = isHidden ? 'Hide' : 'Show';
				});

				root.querySelector('[data-youtube-key-form]').addEventListener('submit', async (event) => {
					event.preventDefault();
					const form = event.currentTarget;
					const button = form.querySelector('button[type="submit"]');
					await withBusy(button, 'Saving...', async () => {
						const payload = Object.fromEntries(new FormData(form).entries());
						await request('/settings/youtube-api-key', { method: 'PATCH', body: JSON.stringify(payload) });
						toast('Saved YouTube API key.');
					});
				});

				root.querySelector('[data-channel-discover-form]').addEventListener('submit', async (event) => {
					event.preventDefault();
					const form = event.currentTarget;
					const button = form.querySelector('button[type="submit"]');
					await withBusy(button, 'Discovering...', async () => {
						const payload = Object.fromEntries(new FormData(form).entries());
						const result = await request('/channels/discover', { method: 'POST', body: JSON.stringify(payload) });
						suggestedChannels = result.suggestions || [];
						renderSuggestedChannels();
						toast(`Found ${result.added || 0} suggested channels.`);
					});
				});

				root.querySelector('[data-seed-trusted-channels]').addEventListener('click', async (event) => {
					await withBusy(event.currentTarget, 'Seeding...', async () => {
						const result = await request('/suggested-channels/seed-trusted', { method: 'POST', body: JSON.stringify({}) });
						suggestedChannels = result.suggestions || [];
						renderSuggestedChannels();
						toast(`Seeded ${result.added || 0} trusted channels.`);
					});
				});

				root.querySelector('[data-video-discover-form]').addEventListener('submit', async (event) => {
					event.preventDefault();
					const form = event.currentTarget;
					const button = form.querySelector('button[type="submit"]');
					await withBusy(button, 'Discovering...', async () => {
						const payload = Object.fromEntries(new FormData(form).entries());
						const result = await request('/videos/discover', { method: 'POST', body: JSON.stringify(payload) });
						pendingChannelVideos = result.videos || [];
						renderChannelVideos();
						root.querySelector('[data-channel-subtab="videos"]').click();
						await loadChannels();
						toast(`Added ${result.added || 0} videos to pending.`);
					});
				});

				root.querySelector('[data-approve-all-suggested]').addEventListener('click', async (event) => {
					await withBusy(event.currentTarget, 'Approving...', async () => {
						const ids = suggestedChannels.map((channel) => channel.id).filter(Boolean);
						let approved = 0;
						for (const id of ids) {
							try {
								const result = await request(`/suggested-channels/${id}/approve`, { method: 'POST', body: JSON.stringify({}) });
								suggestedChannels = result.suggestions || [];
								approved++;
							} catch (error) {
								toast(error.message || 'Could not approve one suggestion.');
							}
						}
						renderSuggestedChannels();
						await loadChannels();
						toast(`Approved ${approved} channels.`);
					});
				});

				root.querySelector('[data-suggested-channels-list]').addEventListener('click', async (event) => {
					const approve = event.target.closest('[data-approve-suggested]');
					const dismiss = event.target.closest('[data-dismiss-suggested]');
					if (approve) {
						await withBusy(approve, 'Approving...', async () => {
							const result = await request(`/suggested-channels/${approve.dataset.approveSuggested}/approve`, { method: 'POST', body: JSON.stringify({}) });
							suggestedChannels = result.suggestions || [];
							renderSuggestedChannels();
							await loadChannels();
							toast('Approved channel.');
						});
					}
					if (dismiss) {
						await withBusy(dismiss, 'Dismissing...', async () => {
							suggestedChannels = await request(`/suggested-channels/${dismiss.dataset.dismissSuggested}/dismiss`, { method: 'POST', body: JSON.stringify({}) });
							renderSuggestedChannels();
							toast('Dismissed suggestion.');
						});
					}
				});

				root.querySelector('[data-channel-cron-form]').addEventListener('submit', async (event) => {
					event.preventDefault();
					const form = event.currentTarget;
					const button = form.querySelector('button[type="submit"]');
					await withBusy(button, 'Saving...', async () => {
						const payload = Object.fromEntries(new FormData(form).entries());
						payload.enabled = form.elements.enabled.checked ? 1 : 0;
						const settings = await request('/settings/channel-cron', { method: 'PATCH', body: JSON.stringify(payload) });
						renderChannelCron(settings);
						toast('Saved channel cron.');
					});
				});

				root.querySelector('[data-channel-check-now]').addEventListener('click', async (event) => {
					await withBusy(event.currentTarget, 'Checking...', async () => {
						const result = await request('/channels/check-now', { method: 'POST', body: JSON.stringify({}) });
						await Promise.all([loadChannels(), loadChannelVideos(), loadChannelCron()]);
						toast(`Checked ${result.checked || 0} channels, added ${result.added || 0} videos.`);
					});
				});

				root.querySelector('[data-channels-list]').addEventListener('click', async (event) => {
					const toggle = event.target.closest('[data-toggle-channel]');
					const remove = event.target.closest('[data-delete-channel]');
					if (toggle) {
						const channel = channels.find((item) => String(item.id) === String(toggle.dataset.toggleChannel));
						if (!channel) return;
						await withBusy(toggle, 'Saving...', async () => {
							await request(`/channels/${toggle.dataset.toggleChannel}`, {
								method: 'PATCH',
								body: JSON.stringify({ ...channel, is_active: Number(toggle.dataset.nextActive) }),
							});
							await loadChannels();
							toast('Updated channel.');
						});
					}
					if (remove) {
						await withBusy(remove, 'Deleting...', async () => {
							await request(`/channels/${remove.dataset.deleteChannel}`, { method: 'DELETE' });
							await Promise.all([loadChannels(), loadChannelVideos()]);
							toast('Deleted channel.');
						});
					}
				});

				root.querySelector('[data-channel-videos-list]').addEventListener('click', async (event) => {
					const useButton = event.target.closest('[data-use-video]');
					const dismissButton = event.target.closest('[data-dismiss-video]');
					if (useButton) {
						const video = pendingChannelVideos.find((item) => String(item.id) === String(useButton.dataset.useVideo));
						const form = root.querySelector('[data-scraper-form]');
						if (!video || !form) return;
						form.elements.youtube_url.value = video.video_url || '';
						await request(`/channel-videos/${video.id}`, { method: 'PATCH', body: JSON.stringify({ status: 'selected' }) });
						await loadChannelVideos();
						root.querySelector('[data-tab="scraper"]').click();
						toast('Video URL copied to Scraper Control.');
					}
					if (dismissButton) {
						await withBusy(dismissButton, 'Dismissing...', async () => {
							await request(`/channel-videos/${dismissButton.dataset.dismissVideo}`, { method: 'PATCH', body: JSON.stringify({ status: 'dismissed' }) });
							await loadChannelVideos();
							toast('Dismissed video.');
						});
					}
				});

				root.querySelector('[data-transcript-check]').addEventListener('click', async (event) => {
					const form = root.querySelector('[data-scraper-form]');
					const output = root.querySelector('[data-transcript-output]');
					const payload = Object.fromEntries(new FormData(form).entries());
					if (!payload.youtube_url) {
						toast('Nhập YouTube URL trước.');
						return;
					}
					await withBusy(event.currentTarget, 'Checking...', async () => {
						output.value = 'Đang lấy transcript...';
						try {
							const result = await request('/scraper/transcript', { method: 'POST', body: JSON.stringify(payload) });
							output.value = result.transcript || '';
							toast(`Đã lấy transcript (${result.chars || output.value.length} ký tự).`);
						} catch (error) {
							output.value = error.message;
							toast(error.message);
						}
					});
				});

				root.querySelector('[data-official-text-check]').addEventListener('click', async (event) => {
					const form = root.querySelector('[data-scraper-form]');
					const output = root.querySelector('[data-official-text-output]');
					const payload = Object.fromEntries(new FormData(form).entries());
					if (!payload.product_id) {
						toast('Chọn product trước.');
						return;
					}
					await withBusy(event.currentTarget, 'Checking...', async () => {
						output.value = 'Đang extract Official URL + PDF URL text...';
						try {
							const result = await request('/scraper/official-text', { method: 'POST', body: JSON.stringify(payload) });
							output.value = result.text || '';
							renderPdfImagesPreview(result.pdf_images || [], result.pdf_image_error || '');
							toast(`Đã extract Official + PDF text (${result.chars || output.value.length} ký tự).`);
						} catch (error) {
							output.value = error.message;
							toast(error.message);
						}
					});
				});

				root.querySelector('[data-scraper-form]').addEventListener('submit', async (event) => {
					event.preventDefault();
					const form = event.currentTarget;
					const button = form.querySelector('button[type="submit"]');
					await withBusy(button, 'Running...', async () => {
						const isCompareTemplate = form.elements.schema_template_id.value === 'template_3';
						if (isCompareTemplate) {
							toggleCompareProductFields(true);
							if (!form.elements.compare_pdf_url.value.trim()) {
								toast('Nhập Product 2 PDF URL cho Mẫu 3 trước.');
								return;
							}
						}
						const payload = Object.fromEntries(new FormData(form).entries());
						const item = await request('/scraper/run', { method: 'POST', body: JSON.stringify(payload) });
						const usage = item.content_json?._token_usage || {};
						form.elements.max_output_tokens.defaultValue = payload.max_output_tokens || form.elements.max_output_tokens.defaultValue;
						form.reset();
						setTokenUsage(usage);
						await loadQueue();
						await loadSchemaTemplates();
						toast('Đã tạo bản tóm tắt/review và đưa vào Content.');
					});
				});

				root.querySelector('[data-queue-list]').addEventListener('click', (event) => {
					const button = event.target.closest('[data-open-post]');
					if (!button) return;
					const item = pendingPosts.find((post) => String(post.id) === String(button.dataset.openPost));
					if (!item) return;
					const form = root.querySelector('[data-editor-form]');
					form.elements.id.value = item.id;
					form.elements.title.value = item.content_json?.title || '';
					form.elements.summary.value = item.content_json?.summary || '';
					form.elements.body.value = item.content_json?.review_content || item.content_json?.body || '';
					form.elements.destination.value = item.content_json?.finished_destination || 'post';
					setTokenUsage(item.content_json?._token_usage || {});
				});

				root.querySelector('[data-editor-form]').addEventListener('submit', async (event) => {
					event.preventDefault();
					const form = event.currentTarget;
					const id = form.elements.id.value;
					if (!id) {
						toast('Hãy chọn một post pending trước.');
						return;
					}
					const button = form.querySelector('button[type="submit"]');
					await withBusy(button, 'Finishing...', async () => {
						const item = pendingPosts.find((post) => String(post.id) === String(id));
						const result = await request(`/posts-queue/${id}/finish`, {
							method: 'POST',
							body: JSON.stringify({
								destination: form.elements.destination.value,
								content_json: {
									...(item?.content_json || {}),
									title: form.elements.title.value,
									summary: form.elements.summary.value,
									review_content: form.elements.body.value,
								},
							}),
						});
						form.reset();
						await loadQueue();
						toast(`Đã tạo bài pending #${result.post_id} trong ${result.post_type}.`);
					});
				});

				Promise.all([loadProducts(), loadQueue(), loadGeminiKey(), loadSchemaTemplates(), loadChannels(), loadSuggestedChannels(), loadChannelVideos(), loadChannelCron(), loadYouTubeKey()]).catch((error) => toast(error.message));
			})();
			</script>
			<?php
		}
	}
}

if ( ! class_exists( 'Zenclau_V1_Content_System' ) ) {
	/**
	 * Loader for Youtube Content System.
	 */
	final class Zenclau_V1_Content_System {
		const CHANNEL_CRON_HOOK = 'zenclau_v1_check_youtube_channels';

		/**
		 * Boot hooks.
		 *
		 * @return void
		 */
		public static function init() {
			add_action( 'init', array( 'Zenclau_V1_Content_Model', 'maybe_install_tables' ), 5 );
			add_filter( 'cron_schedules', array( __CLASS__, 'add_cron_schedules' ) );
			add_action( self::CHANNEL_CRON_HOOK, array( __CLASS__, 'run_channel_video_check' ) );
			add_action( 'init', array( __CLASS__, 'ensure_channel_cron_scheduled' ), 20 );
			Zenclau_V1_REST_API::init();
			Zenclau_V1_Admin_UI::init();
		}

		/**
		 * Add configurable channel check schedule.
		 *
		 * @param array $schedules Cron schedules.
		 * @return array
		 */
		public static function add_cron_schedules( $schedules ) {
			$settings = self::get_channel_cron_settings();
			$minutes  = max( 5, absint( $settings['interval_minutes'] ?? 60 ) );

			$schedules['zenclau_v1_channel_interval'] = array(
				'interval' => $minutes * MINUTE_IN_SECONDS,
				'display'  => sprintf( 'Zenclau channel check every %d minutes', $minutes ),
			);

			return $schedules;
		}

		/**
		 * Get channel cron settings.
		 *
		 * @return array
		 */
		public static function get_channel_cron_settings() {
			$settings = get_option(
				'zenclau_v1_channel_cron_settings',
				array(
					'enabled'          => true,
					'interval_minutes' => 60,
				)
			);
			$settings = is_array( $settings ) ? $settings : array();

			return array(
				'enabled'          => ! empty( $settings['enabled'] ),
				'interval_minutes' => max( 5, absint( $settings['interval_minutes'] ?? 60 ) ),
				'lookback_days'    => max( 1, absint( $settings['lookback_days'] ?? 30 ) ),
				'next_run'         => wp_next_scheduled( self::CHANNEL_CRON_HOOK ),
			);
		}

		/**
		 * Update channel cron settings and reschedule.
		 *
		 * @param array $data Settings payload.
		 * @return array
		 */
		public static function update_channel_cron_settings( array $data ) {
			$settings = array(
				'enabled'          => ! empty( $data['enabled'] ),
				'interval_minutes' => max( 5, absint( $data['interval_minutes'] ?? 60 ) ),
				'lookback_days'    => max( 1, absint( $data['lookback_days'] ?? 30 ) ),
			);

			update_option( 'zenclau_v1_channel_cron_settings', $settings, false );
			self::clear_channel_cron();
			self::ensure_channel_cron_scheduled();

			return self::get_channel_cron_settings();
		}

		/**
		 * Ensure channel cron is scheduled when enabled.
		 *
		 * @return void
		 */
		public static function ensure_channel_cron_scheduled() {
			$settings = self::get_channel_cron_settings();
			if ( empty( $settings['enabled'] ) ) {
				self::clear_channel_cron();
				return;
			}

			if ( ! wp_next_scheduled( self::CHANNEL_CRON_HOOK ) ) {
				wp_schedule_event( time() + MINUTE_IN_SECONDS, 'zenclau_v1_channel_interval', self::CHANNEL_CRON_HOOK );
			}
		}

		/**
		 * Clear channel cron events.
		 *
		 * @return void
		 */
		public static function clear_channel_cron() {
			while ( $timestamp = wp_next_scheduled( self::CHANNEL_CRON_HOOK ) ) {
				if ( false === wp_unschedule_event( $timestamp, self::CHANNEL_CRON_HOOK ) ) {
					break;
				}
			}
		}

		/**
		 * Check all active YouTube channel RSS feeds and queue new videos.
		 *
		 * @return array|WP_Error
		 */
		public static function run_channel_video_check() {
			Zenclau_V1_Content_Model::maybe_install_tables();

			$channels = Zenclau_V1_Content_Model::get_channels( true );
			$settings = self::get_channel_cron_settings();
			$min_time = time() - ( max( 1, absint( $settings['lookback_days'] ?? 30 ) ) * DAY_IN_SECONDS );
			$checked  = 0;
			$added    = 0;
			$errors   = array();

			foreach ( $channels as $channel ) {
				$checked++;
				$result = self::check_one_channel_feed( $channel, $min_time );
				Zenclau_V1_Content_Model::touch_channel_checked_at( $channel['id'] );

				if ( is_wp_error( $result ) ) {
					$errors[] = array(
						'channel_id' => $channel['id'],
						'message'    => $result->get_error_message(),
					);
					continue;
				}

				$added += absint( $result['added'] ?? 0 );
			}

			update_option( 'zenclau_v1_channel_last_check', current_time( 'mysql' ), false );

			return array(
				'checked' => $checked,
				'added'   => $added,
				'errors'  => $errors,
			);
		}

		/**
		 * Check one channel RSS feed.
		 *
		 * @param array $channel Channel row.
		 * @return array|WP_Error
		 */
		private static function check_one_channel_feed( array $channel, $min_time = 0 ) {
			$channel_id = sanitize_text_field( $channel['youtube_channel_id'] ?? '' );
			if ( '' === $channel_id ) {
				return new WP_Error( 'zenclau_v1_channel_feed_missing_id', __( 'V1: Channel ID is empty.', 'bhdt-core-system' ) );
			}

			$url      = add_query_arg( 'channel_id', $channel_id, 'https://www.youtube.com/feeds/videos.xml' );
			$response = wp_remote_get(
				$url,
				array(
					'timeout'     => 15,
					'redirection' => 3,
					'user-agent'  => 'BHDT Youtube Content System/' . ( defined( 'BHDT_CORE_SYSTEM_VERSION' ) ? BHDT_CORE_SYSTEM_VERSION : '1.0' ),
				)
			);

			if ( is_wp_error( $response ) ) {
				return $response;
			}

			$code = wp_remote_retrieve_response_code( $response );
			$body = wp_remote_retrieve_body( $response );
			if ( 200 !== (int) $code || '' === trim( $body ) ) {
				return new WP_Error( 'zenclau_v1_channel_feed_failed', sprintf( 'V1: YouTube feed returned HTTP %d.', (int) $code ) );
			}

			$xml = simplexml_load_string( $body );
			if ( false === $xml || empty( $xml->entry ) ) {
				return array( 'added' => 0 );
			}

			$added = 0;
			foreach ( $xml->entry as $entry ) {
				$yt       = $entry->children( 'http://www.youtube.com/xml/schemas/2015' );
				$video_id = isset( $yt->videoId ) ? (string) $yt->videoId : '';
				$title    = isset( $entry->title ) ? (string) $entry->title : '';
				$date     = isset( $entry->published ) ? (string) $entry->published : '';
				$time     = '' !== $date ? strtotime( $date ) : 0;

				if ( $min_time && $time && $time < $min_time ) {
					continue;
				}

				if ( Zenclau_V1_Content_Model::upsert_channel_video( $channel['id'], $video_id, $title, $date ) ) {
					$added++;
				}
			}

			return array( 'added' => $added );
		}
	}
}


