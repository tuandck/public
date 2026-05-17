<?php
/**
 * Search form.
 *
 * @package BHDT_Wirecutter_Clone
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<form role="search" method="get" class="bhdt-wire-search" action="<?php echo esc_url( bhdt_wirecutter_current_lang_home_url() ); ?>">
	<label class="screen-reader-text" for="bhdt-search-field"><?php esc_html_e( 'Tìm kiếm:', 'bhdt-wirecutter' ); ?></label>
	<span class="bhdt-wire-search-icon" aria-hidden="true">✦</span>
	<input type="search" id="bhdt-search-field" class="bhdt-wire-search-input" placeholder="<?php echo esc_attr__( 'Tìm kiếm bài viết, hướng dẫn, linh kiện...', 'bhdt-wirecutter' ); ?>" value="<?php echo esc_attr( get_search_query() ); ?>" name="s" />
	<button type="submit" class="bhdt-wire-search-button"><?php esc_html_e( 'Tìm kiếm', 'bhdt-wirecutter' ); ?></button>
</form>
