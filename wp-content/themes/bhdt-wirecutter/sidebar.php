<?php
/**
 * Generic sidebar.
 *
 * @package BHDT_Wirecutter_Clone
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( is_active_sidebar( 'bhdt-default-sidebar' ) ) {
	dynamic_sidebar( 'bhdt-default-sidebar' );
}
