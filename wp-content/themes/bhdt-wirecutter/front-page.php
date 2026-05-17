<?php
/**
 * Front page template.
 *
 * Delegates rendering to index.php so the homepage uses one shared layout.
 *
 * @package BHDT_Wirecutter_Clone
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require get_template_directory() . '/index.php';
