<?php
/**
 * REST receiver and debug meta box module.
 *
 * @package BHDT_Core_System
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'BHDT_API_Receiver' ) ) {
	/**
	 * Handles API routes, payload upserts and debug metabox.
	 */
	final class BHDT_API_Receiver {
		/**
		 * Boot hooks.
		 *
		 * @return void
		 */
		public static function init() {
			$instance = new self();
			add_action( 'rest_api_init', array( $instance, 'register_rest_routes' ) );
			add_action( 'add_meta_boxes', array( $instance, 'register_meta_boxes' ) );
		}

		/**
		 * Register REST routes.
		 *
		 * @return void
		 */
		public function register_rest_routes() {
			register_rest_route(
				'bhdt/v1',
				'/update-product',
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'permission_callback' => array( $this, 'check_product_update_permissions' ),
					'callback'            => array( $this, 'handle_product_update' ),
				)
			);
		}

		/**
		 * Check REST permissions for product updates.
		 *
		 * @param WP_REST_Request $request Request object.
		 * @return true|WP_Error
		 */
		public function check_product_update_permissions( WP_REST_Request $request ) {
			$allow_unsafe_local = (bool) apply_filters( 'bhdt_core_system_allow_open_rest', false, $request );

			if ( $allow_unsafe_local || $this->is_local_environment() ) {
				return true;
			}

			$rate_check = $this->check_rate_limit( $request );
			if ( is_wp_error( $rate_check ) ) {
				return $rate_check;
			}

			if ( ! empty( $request->get_header( 'authorization' ) ) && current_user_can( 'edit_posts' ) ) {
				return true;
			}

			$nonce = $request->get_header( 'x_wp_nonce' );
			if ( empty( $nonce ) ) {
				$nonce = $request->get_header( 'x-wp-nonce' );
			}

			if ( is_user_logged_in() ) {
				if ( empty( $nonce ) ) {
					return new WP_Error(
						'bhdt_rest_nonce_required',
						__( 'A valid X-WP-Nonce header is required outside the local environment.', 'bhdt-core-system' ),
						array( 'status' => 401 )
					);
				}

				if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $nonce ) ), 'wp_rest' ) ) {
					return new WP_Error(
						'bhdt_rest_nonce_invalid',
						__( 'The supplied REST nonce is invalid or expired.', 'bhdt-core-system' ),
						array( 'status' => 403 )
					);
				}

				if ( current_user_can( 'edit_posts' ) ) {
					return true;
				}
			}

			return new WP_Error(
				'bhdt_rest_forbidden',
				__( 'Authentication is required for this endpoint on staging/production.', 'bhdt-core-system' ),
				array( 'status' => 403 )
			);
		}

		/**
		 * Handle REST product upsert requests.
		 *
		 * @param WP_REST_Request $request Request object.
		 * @return WP_REST_Response|WP_Error
		 */
		public function handle_product_update( WP_REST_Request $request ) {
			$params         = $request->get_json_params();
			$model_name     = isset( $params['model_name'] ) ? sanitize_text_field( wp_unslash( $params['model_name'] ) ) : '';
			$ai_summary     = isset( $params['ai_summary'] ) ? wp_kses_post( wp_unslash( $params['ai_summary'] ) ) : '';
			$specifications = isset( $params['specifications'] ) ? $params['specifications'] : array();

			if ( empty( $model_name ) ) {
				return new WP_Error(
					'bhdt_missing_model_name',
					__( 'The model_name field is required.', 'bhdt-core-system' ),
					array( 'status' => 400 )
				);
			}

			if ( ! is_array( $specifications ) && ! is_object( $specifications ) ) {
				return new WP_Error(
					'bhdt_invalid_specifications',
					__( 'The specifications field must be a JSON object or array.', 'bhdt-core-system' ),
					array( 'status' => 400 )
				);
			}

			$existing_post_id = $this->find_review_post_id_by_title( $model_name );
			$postarr          = array(
				'post_type'    => 'bhdt_review',
				'post_status'  => 'publish',
				'post_title'   => $model_name,
				'post_content' => $ai_summary,
			);

			if ( $existing_post_id ) {
				$postarr['ID'] = $existing_post_id;
				$post_id       = wp_update_post( wp_slash( $postarr ), true );
				$action        = 'updated';
			} else {
				$post_id = wp_insert_post( wp_slash( $postarr ), true );
				$action  = 'created';
			}

			if ( is_wp_error( $post_id ) ) {
				return $post_id;
			}

			$specifications_json = wp_json_encode(
				$specifications,
				JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
			);

			if ( false === $specifications_json ) {
				return new WP_Error(
					'bhdt_json_encode_failed',
					__( 'Specifications could not be encoded as JSON.', 'bhdt-core-system' ),
					array( 'status' => 500 )
				);
			}

			update_post_meta( $post_id, 'bhdt_specs', $specifications_json );

			$this->write_import_log(
				array(
					'event'      => 'upsert_ok',
					'action'     => $action,
					'post_id'    => (int) $post_id,
					'model_name' => $model_name,
					'ip'         => $this->get_request_ip(),
				)
			);

			return rest_ensure_response(
				array(
					'success'    => true,
					'action'     => $action,
					'post_id'    => (int) $post_id,
					'model_name' => $model_name,
				)
			);
		}

		/**
		 * Rate limit REST endpoint requests by hashed IP address.
		 *
		 * @param WP_REST_Request $request Request object.
		 * @return true|WP_Error
		 */
		private function check_rate_limit( WP_REST_Request $request ) {
			$defaults = array(
				'max_requests' => 60,
				'window_secs'  => 60,
			);

			$config = (array) apply_filters( 'bhdt_core_system_rate_limit', $defaults, $request );
			$max    = absint( isset( $config['max_requests'] ) ? $config['max_requests'] : $defaults['max_requests'] );
			$window = absint( isset( $config['window_secs'] ) ? $config['window_secs'] : $defaults['window_secs'] );

			$ip      = $this->get_request_ip();
			$key     = 'bhdt_rl_' . hash( 'crc32b', $ip );
			$counter = get_transient( $key );

			if ( false === $counter ) {
				set_transient( $key, 1, $window );
				return true;
			}

			if ( (int) $counter >= $max ) {
				$this->write_import_log(
					array(
						'event'   => 'rate_limited',
						'ip'      => $ip,
						'counter' => (int) $counter,
					)
				);

				return new WP_Error(
					'bhdt_rate_limited',
					sprintf(
						/* translators: 1: max requests, 2: window in seconds */
						__( 'Rate limit exceeded: %1$d requests per %2$d seconds allowed.', 'bhdt-core-system' ),
						$max,
						$window
					),
					array(
						'status'      => 429,
						'retry_after' => $window,
					)
				);
			}

			set_transient( $key, (int) $counter + 1, $window );
			return true;
		}

		/**
		 * Get request IP.
		 *
		 * @return string
		 */
		private function get_request_ip() {
			$candidates = array(
				'HTTP_CF_CONNECTING_IP',
				'HTTP_X_REAL_IP',
				'REMOTE_ADDR',
			);

			foreach ( $candidates as $key ) {
				if ( ! empty( $_SERVER[ $key ] ) ) {
					$raw = sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) );
					$ip  = filter_var( $raw, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE );

					if ( $ip ) {
						return $ip;
					}
				}
			}

			if ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
				return sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
			}

			return '0.0.0.0';
		}

		/**
		 * Write structured import log.
		 *
		 * @param array $context Event payload.
		 * @return void
		 */
		private function write_import_log( array $context ) {
			$log_path = WP_CONTENT_DIR . '/bhdt-import.log';

			$entry = array_merge(
				array(
					'ts'       => gmdate( 'Y-m-d\TH:i:s\Z' ),
					'site_url' => site_url(),
				),
				$context
			);

			$line = wp_json_encode( $entry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
			if ( false === $line ) {
				return;
			}

			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			file_put_contents( $log_path, $line . PHP_EOL, FILE_APPEND | LOCK_EX );
		}

		/**
		 * Find review post by exact title.
		 *
		 * @param string $title Post title.
		 * @return int
		 */
		private function find_review_post_id_by_title( $title ) {
			global $wpdb;

			$post_id = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT ID FROM {$wpdb->posts} WHERE post_type = %s AND post_status NOT IN ('trash', 'auto-draft') AND post_title = %s ORDER BY ID DESC LIMIT 1",
					'bhdt_review',
					$title
				)
			);

			return absint( $post_id );
		}

		/**
		 * Determine local environment mode.
		 *
		 * @return bool
		 */
		private function is_local_environment() {
			$environment_type = function_exists( 'wp_get_environment_type' ) ? wp_get_environment_type() : 'production';
			$host             = isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '';

			if ( 'local' === $environment_type ) {
				return true;
			}

			if ( false !== strpos( $host, '.local' ) || false !== strpos( $host, 'localhost' ) || false !== strpos( $host, '127.0.0.1' ) ) {
				return true;
			}

			return false;
		}

		/**
		 * Register editor meta boxes.
		 *
		 * @return void
		 */
		public function register_meta_boxes() {
			add_meta_box(
				'bhdt_specs_json_box',
				__( 'Thông Số Kỹ Thuật JSON (Web Hãng)', 'bhdt-core-system' ),
				array( $this, 'render_specs_meta_box' ),
				'bhdt_review',
				'normal',
				'default'
			);
		}

		/**
		 * Render JSON debug meta box.
		 *
		 * @param WP_Post $post Post object.
		 * @return void
		 */
		public function render_specs_meta_box( $post ) {
			$stored_value  = get_post_meta( $post->ID, 'bhdt_specs', true );
			$decoded_value = json_decode( (string) $stored_value, true );

			if ( JSON_ERROR_NONE === json_last_error() && null !== $decoded_value ) {
				$display_value = wp_json_encode(
					$decoded_value,
					JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
				);
			} else {
				$display_value = (string) $stored_value;
			}
			?>
			<p>
				<?php esc_html_e( 'Dữ liệu JSON đồng bộ từ pipeline review hoặc script Python sẽ hiển thị tại đây ở chế độ chỉ đọc.', 'bhdt-core-system' ); ?>
			</p>
			<textarea readonly="readonly" style="width:100%;min-height:340px;font-family:Consolas,Monaco,monospace;"><?php echo esc_textarea( $display_value ); ?></textarea>
			<?php
		}
	}
}
