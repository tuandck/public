<?php
/**
 * Plugin Name: BHDT Polylang Setup
 * Plugin URI: https://banhangdientu.local
 * Description: Setup English translations for BHDT theme via Polylang
 * Version: 1.0.1
 * Author: Vo Van Tuan & Copilot
 * Text Domain: bhdt-polylang-setup
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Check if Polylang is active and properly setup
 */
function bhdt_polylang_is_available() {
	return defined( 'POLYLANG_VERSION' ) || function_exists( 'pll_current_language' ) || function_exists( 'pll_get_languages' );
}

function bhdt_polylang_check_setup() {
	if ( ! is_admin() || bhdt_polylang_is_available() ) {
		return true;
	}

	add_action( 'admin_notices', function() {
		echo '<div class="notice notice-error"><p>';
		echo esc_html__( 'BHDT Polylang Setup: Polylang plugin is not active. Please activate Polylang first.', 'bhdt-polylang-setup' );
		echo '</p></div>';
	} );

	return false;
}

add_action( 'admin_init', 'bhdt_polylang_check_setup' );

/**
 * Create English post translations automatically
 */
function bhdt_create_english_translations() {
	// Security check
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( 'Permission denied' );
		return;
	}
	
	// Nonce check
	check_ajax_referer( 'bhdt_create_translations' );
	
	if ( ! function_exists( 'pll_get_post_translations' ) ) {
		wp_send_json_error( 'Polylang not active or properly installed' );
		return;
	}
	
	// Get all Vietnamese posts
	$args = array(
		'post_type'      => array( 'post', 'page', 'bhdt_review', 'bhdt_project' ),
		'post_status'    => 'publish',
		'posts_per_page' => -1,
	);
	
	$vi_posts = get_posts( $args );
	
	$created = 0;
	$already_linked = 0;
	$errors = 0;
	
	foreach ( $vi_posts as $post ) {
		// Get existing translations
		$translations = pll_get_post_translations( $post->ID );
		
		if ( isset( $translations['en'] ) && ! empty( $translations['en'] ) ) {
			$already_linked++;
			continue;
		}
		
		// Create English version as draft
		$en_post_args = array(
			'post_type'    => $post->post_type,
			'post_title'   => $post->post_title,
			'post_content' => $post->post_content,
			'post_excerpt' => $post->post_excerpt,
			'post_status'  => 'draft',
			'post_author'  => $post->post_author,
		);
		
		$en_post_id = wp_insert_post( $en_post_args );
		
		if ( is_wp_error( $en_post_id ) ) {
			$errors++;
			continue;
		}
		
		// Copy post meta
		$post_meta = get_post_meta( $post->ID );
		foreach ( $post_meta as $meta_key => $meta_values ) {
			if ( ! in_array( $meta_key, array( '_wp_old_slug', '_encloseme', '_thumbnail_id' ), true ) ) {
				foreach ( $meta_values as $meta_value ) {
					add_post_meta( $en_post_id, $meta_key, $meta_value );
				}
			}
		}
		
		// Copy featured image
		if ( has_post_thumbnail( $post->ID ) ) {
			set_post_thumbnail( $en_post_id, get_post_thumbnail_id( $post->ID ) );
		}
		
		// Set post language
		if ( function_exists( 'pll_set_post_language' ) ) {
			pll_set_post_language( $en_post_id, 'en' );
		}
		
		// Link translations
		if ( function_exists( 'pll_save_post_translations' ) ) {
			$post_translations = array(
				'vi' => $post->ID,
				'en' => $en_post_id,
			);
			pll_save_post_translations( $post_translations );
		}
		
		$created++;
	}
	
	// Flush rewrite rules
	flush_rewrite_rules();
	
	wp_send_json_success( array(
		'created'        => $created,
		'already_linked' => $already_linked,
		'errors'         => $errors,
		'total'          => count( $vi_posts ),
		'message'        => sprintf(
			'Created %d translations, already linked %d, errors %d',
			$created,
			$already_linked,
			$errors
		),
	) );
}

add_action( 'wp_ajax_bhdt_create_en_translations', 'bhdt_create_english_translations' );

/**
 * Register admin menu
 */
function bhdt_register_polylang_menu() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	
	add_submenu_page(
		'tools.php',
		'BHDT Polylang Setup',
		'BHDT Polylang Setup',
		'manage_options',
		'bhdt-polylang-setup',
		'bhdt_render_polylang_page'
	);
}

add_action( 'admin_menu', 'bhdt_register_polylang_menu' );

/**
 * Render setup page
 */
function bhdt_render_polylang_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Access denied' );
	}
	
	$polylang_active = function_exists( 'pll_get_languages' );
	$languages = $polylang_active ? pll_get_languages( array( 'fields' => 'all' ) ) : array();
	
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'BHDT Polylang Setup', 'bhdt-polylang-setup' ); ?></h1>
		
		<?php if ( ! $polylang_active ) : ?>
			<div class="notice notice-error">
				<p><?php esc_html_e( 'Polylang plugin is not active. Please activate it first.', 'bhdt-polylang-setup' ); ?></p>
			</div>
		<?php else : ?>
			<div class="card">
				<h2><?php esc_html_e( 'Language Configuration', 'bhdt-polylang-setup' ); ?></h2>
				<p><?php esc_html_e( 'Active Languages:', 'bhdt-polylang-setup' ); ?></p>
				<ul>
					<?php foreach ( $languages as $lang ) : ?>
						<li><?php echo esc_html( $lang->name ); ?> (<?php echo esc_html( $lang->slug ); ?>)</li>
					<?php endforeach; ?>
				</ul>
			</div>
			
			<div class="card">
				<h2><?php esc_html_e( 'Create English Post Translations', 'bhdt-polylang-setup' ); ?></h2>
				<p><?php esc_html_e( 'This will create English draft versions of all Vietnamese posts and link them via Polylang.', 'bhdt-polylang-setup' ); ?></p>
				
				<button type="button" id="bhdt-create-translations-btn" class="button button-primary button-large">
					<?php esc_html_e( 'Create English Translations', 'bhdt-polylang-setup' ); ?>
				</button>
				
				<div id="bhdt-translation-result" style="margin-top: 20px; display: none;"></div>
			</div>
		<?php endif; ?>
	</div>
	
	<script type="text/javascript">
	(function($) {
		$('#bhdt-create-translations-btn').on('click', function() {
			const btn = $(this);
			const result = $('#bhdt-translation-result');
			const originalText = btn.text();
			
			btn.prop('disabled', true).text('Processing...');
			result.show().html('<p>Creating translations, please wait...</p>');
			
			$.ajax({
				url: ajaxurl,
				type: 'POST',
				data: {
					action: 'bhdt_create_en_translations',
					nonce: '<?php echo wp_create_nonce( 'bhdt_create_translations' ); ?>'
				},
				success: function(response) {
					if (response.success) {
						const data = response.data;
						result.html(`
							<div class="notice notice-success"><p>
								<strong>Success!</strong><br>
								Created: ${data.created} translations<br>
								Already linked: ${data.already_linked}<br>
								Errors: ${data.errors}<br>
								Total: ${data.total}
							</p></div>
						`);
					} else {
						result.html(`<div class="notice notice-error"><p>${response.data}</p></div>`);
					}
				},
				error: function(xhr, status, error) {
					result.html(`<div class="notice notice-error"><p>Error: ${error}</p></div>`);
				},
				complete: function() {
					btn.prop('disabled', false).text(originalText);
				}
			});
		});
	})(jQuery);
	</script>
	<?php
}
