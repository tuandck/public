<?php
/**
 * CPT registration module.
 *
 * @package BHDT_Core_System
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'BHDT_CPT_Init' ) ) {
	/**
	 * Registers plugin custom post types.
	 */
	final class BHDT_CPT_Init {
		/**
		 * Boot hooks.
		 *
		 * @return void
		 */
		public static function init() {
			add_action( 'init', array( __CLASS__, 'register_post_types' ) );
		}

		/**
		 * Register custom post types.
		 *
		 * @return void
		 */
		public static function register_post_types() {
			register_post_type(
				'bhdt_review',
				array(
					'labels'             => array(
						'name'               => __( 'Review', 'bhdt-core-system' ),
						'singular_name'      => __( 'Review', 'bhdt-core-system' ),
						'menu_name'          => __( 'Review', 'bhdt-core-system' ),
						'all_items'          => __( 'Review', 'bhdt-core-system' ),
						'add_new'            => __( 'Thêm bài review', 'bhdt-core-system' ),
						'add_new_item'       => __( 'Thêm bài review', 'bhdt-core-system' ),
						'name_admin_bar'     => __( 'Review', 'bhdt-core-system' ),
						'edit_item'          => __( 'Sửa Review Linh Kiện', 'bhdt-core-system' ),
						'new_item'           => __( 'Review Mới', 'bhdt-core-system' ),
						'view_item'          => __( 'Xem Review', 'bhdt-core-system' ),
						'search_items'       => __( 'Tìm Reviews', 'bhdt-core-system' ),
						'not_found'          => __( 'Chưa có review nào.', 'bhdt-core-system' ),
						'not_found_in_trash' => __( 'Không có review trong thùng rác.', 'bhdt-core-system' ),
					),
					'public'             => true,
					'show_in_rest'       => true,
					'menu_icon'          => 'dashicons-analytics',
					'has_archive'        => true,
					'rewrite'            => array( 'slug' => 'reviews-linh-kien' ),
					'supports'           => array( 'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields' ),
					'publicly_queryable' => true,
					'show_ui'            => true,
					'show_in_menu'       => true,
				)
			);

			register_post_type(
				'bhdt_project',
				array(
					'labels'             => array(
						'name'               => __( 'Dự Án DIY', 'bhdt-core-system' ),
						'singular_name'      => __( 'Dự Án DIY', 'bhdt-core-system' ),
						'menu_name'          => __( 'Dự Án DIY', 'bhdt-core-system' ),
						'add_new'            => __( 'Thêm Dự Án', 'bhdt-core-system' ),
						'add_new_item'       => __( 'Thêm Dự Án DIY', 'bhdt-core-system' ),
						'edit_item'          => __( 'Sửa Dự Án DIY', 'bhdt-core-system' ),
						'new_item'           => __( 'Dự Án Mới', 'bhdt-core-system' ),
						'view_item'          => __( 'Xem Dự Án', 'bhdt-core-system' ),
						'search_items'       => __( 'Tìm Dự Án', 'bhdt-core-system' ),
						'not_found'          => __( 'Chưa có dự án nào.', 'bhdt-core-system' ),
						'not_found_in_trash' => __( 'Không có dự án trong thùng rác.', 'bhdt-core-system' ),
					),
					'public'             => true,
					'show_in_rest'       => true,
					'menu_icon'          => 'dashicons-hammer',
					'has_archive'        => true,
					'rewrite'            => array( 'slug' => 'du-an-diy' ),
					'supports'           => array( 'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields' ),
					'publicly_queryable' => true,
					'show_ui'            => true,
					'show_in_menu'       => true,
				)
			);
		}
	}
}
