<?php
/**
 * Script to create English translations for the BHDT Wirecutter theme
 * Run this via: wp eval-file create-translations.php
 * Or manually: php -r "include 'create-translations.php';"
 */

if ( ! defined( 'ABSPATH' ) ) {
	// If run standalone, load WordPress
	require_once( dirname( __DIR__, 3 ) . '/wp-load.php' );
}

// English translations mapping for all theme strings
$translations = array(
	'Menu chính' => 'Primary Menu',
	'Cột trái trang chủ' => 'Home Left Column',
	'Thanh bên trái trên trang chủ và các cột biên tập.' => 'Left sidebar on homepage and editorial columns.',
	'Cột phải trang chủ' => 'Home Right Column',
	'Thanh bên phải trên trang chủ cho các khuyến mãi và liên kết.' => 'Right sidebar on homepage for deals and links.',
	'Thanh bên mặc định' => 'Default Sidebar',
	'Thanh bên dự phòng cho bài viết và trang.' => 'Fallback sidebar for posts and pages.',
	'Cột chân trang 1' => 'Footer Column 1',
	'Cột chân trang 2' => 'Footer Column 2',
	'Cột chân trang 3' => 'Footer Column 3',
	'Chủ đề hàng đầu BHDT' => 'BHDT Top Topics',
	'Ưu đãi hàng ngày BHDT' => 'BHDT Daily Deals',
	'Tìm kiếm phổ biến BHDT' => 'BHDT Popular Searches',
	'Một mục mỗi dòng theo định dạng: Nhãn|URL|Ghi chú' => 'One item per line in the format: Label|URL|Short note',
	'Tìm bài viết, hướng dẫn, linh kiện...' => 'Search posts, guides, electronics...',
	'Tìm kiếm' => 'Search',
	'Công cụ chuyển ngôn ngữ' => 'Language Switcher',
	'Đăng nhập' => 'Log in',
	'Đăng ký' => 'Subscribe',
	'Dự án' => 'Projects',
	'Linh kiện điện tử' => 'Electronics',
	'Công cụ' => 'Tools',
	'Ưu đãi' => 'Deals',
	'Menu chính' => 'Main Menu',
	'Bố cục biên tập gọn gàng, lấy cảm hứng từ Wirecutter' => 'Clean editorial layout inspired by Wirecutter',
	'Hướng dẫn thực tế, đánh giá linh kiện và ghi chú dự án.' => 'Practical guides, electronic reviews, and project notes.',
	'Mới nhất' => 'The Latest',
	'Bài đánh giá' => 'Đánh giá',
	'Lưu trữ đánh giá' => 'Lưu trữ đánh giá',
	'Đọc bài đánh giá' => 'Đọc đánh giá',
	'%d bài đánh giá được xuất bản' => '%d bài đánh giá đã xuất bản',
	'Đánh giá' => 'Đánh giá',
	'Lưu trữ đánh giá' => 'Review archive',
	'Chỉ mục đánh giá biên tập với ghi chú có cấu trúc, thông số kỹ thuật chuẩn và luồng đọc rõ ràng.' => 'Editorial review index with structured notes, key specs, and clean reading flow',
	'%d bài đánh giá được xuất bản' => '%d reviews published',
	'Đánh giá' => 'Review',
	'Đọc bài đánh giá' => 'Read review',
	'Không có hình ảnh' => 'No image',
	'Lưu trữ dự án' => 'Project archive',
	'Quy trình dự án, ý tưởng BOM và ghi chú xây dựng được hiển thị trong lưới biên tập rõ ràng.' => 'Project workflows, BOM ideas, and build notes displayed in a clean editorial grid.',
	'Dự án' => 'Project',
	'Đọc dự án' => 'Read project',
	'Lưu trữ danh mục' => 'Category archive',
	'Bài viết danh mục' => 'Category post',
	'Đọc thêm' => 'Read more',
	'Lưu trữ phân loại' => 'Taxonomy archive',
	'ID thuật ngữ: %d' => 'Term ID: %d',
	'Bài viết' => 'Post',
	'Đánh giá Linh Kiện' => 'Electronics Review',
	'Kết luận' => 'Verdict',
	'Ưu điểm & Nhược điểm' => 'Strengths & Weaknesses',
	'Ưu điểm' => 'Strengths',
	'Nhược điểm' => 'Weaknesses',
	'Ghi chú Người đánh giá / Thông số' => 'Reviewer Notes / Specs',
	'JSON Kỹ thuật' => 'Technical JSON',
	'Xếp hạng / Kết luận' => 'Rating / Verdict',
	'Thông số kỹ thuật' => 'Technical Specs',
	'Dự án DIY' => 'DIY Project',
	'BOM / Danh sách các phần' => 'BOM / Parts List',
	'Thông số Dự án / Ghi chú' => 'Project Specs / Notes',
	'Sử dụng một mục mỗi dòng. Mẫu dự án sẽ nổi bật các trường này.' => 'Use one item per line. Project template will highlight these fields.',
	'Sử dụng khu vực này cho BOM, công cụ, điểm kiểm tra, ghi chú dây điện và liên kết khởi chạy. Bố cục có ý đọc trước vì vậy thêm thông tin dự án có thể được phân lớp mà không bị lộn xộn.' => 'Use this area for BOM, tools, checkpoints, wire notes and startup links. Layout has reading-first intent so additional project info can be layered without clutter.',
	'Ghi chú dự án' => 'Project notes',
	'Chủ đề linh kiện' => 'Electronics topics',
	'Hướng dẫn nổi bật' => 'Featured guide',
	'Cập nhật %s' => 'Updated %s',
	'Ưu đãi hàng ngày' => 'Daily Deals',
	'%d%% GIẢM' => '%d%% OFF',
	'Ưu đãi hàng ngày sẽ hiển thị khi WooCommerce và giá khuyến mãi sẵn sàng.' => 'Daily Deals will display when WooCommerce and sale prices are available.',
	'Công cụ chuyển ngôn ngữ' => 'Language switcher',
	'Menu danh mục kiểu Wirecutter' => 'Wirecutter-style category menu',
	'Đồng hồ vạn năng tốt nhất' => 'Best Multimeters',
	'Hướng dẫn ESP32' => 'ESP32 Guides',
	'Mô-đun công suất' => 'Power Modules',
	'Ưu đãi linh kiện hàng ngày' => 'Daily Electronics Deals',
	'Bộ khởi đầu' => 'Starter Kits',
	'Lựa chọn của biên tập viên' => 'Editor\'s Picks',
	'Trang chủ' => 'Home',
	'Tốt nhất' => 'Best Of',
);

// Generate .pot template file
function bhdt_generate_pot_file() {
	global $translations;
	
	$pot_content = <<<'POT'
# Translation file for BHDT Wirecutter Theme
# Copyright (C) 2024 BHDT
msgid ""
msgstr ""
"Content-Type: text/plain; charset=UTF-8\n"
"Language: en\n"
"Plural-Forms: nplurals=2; plural=(n != 1);\n"

POT;

	foreach ( $translations as $vi => $en ) {
		$pot_content .= sprintf(
			"\nmsgid \"%s\"\nmsgstr \"%s\"\n",
			addslashes( $vi ),
			addslashes( $en )
		);
	}

	return $pot_content;
}

// Get theme directory
$theme_dir = dirname( __FILE__ );
$lang_dir = $theme_dir . '/languages';

// Create .pot file
$pot_content = bhdt_generate_pot_file();
file_put_contents( $lang_dir . '/bhdt-wirecutter.pot', $pot_content );
echo "✓ Created bhdt-wirecutter.pot\n";

// Create English .po file
$po_content = str_replace(
	'msgid ""',
	'msgid ""\nmsgstr "Project-Id-Version: BHDT Wirecutter\\nReport-Msgid-Bugs-To: support@banhangdientu.local\\nPOT-Creation-Date: 2024-01-01 00:00:00+0000\\nPO-Revision-Date: 2024-01-01 00:00:00+0000\\nLanguage: en_US\\nMIME-Version: 1.0\\nContent-Type: text/plain; charset=UTF-8\\nContent-Transfer-Encoding: 8bit\\n"',
	$pot_content
);

file_put_contents( $lang_dir . '/bhdt-wirecutter-en_US.po', $po_content );
echo "✓ Created bhdt-wirecutter-en_US.po\n";

// Try to compile with msgfmt if available
$mo_file = $lang_dir . '/bhdt-wirecutter-en_US.mo';
if ( function_exists( 'exec' ) ) {
	@exec( 'msgfmt -o ' . escapeshellarg( $mo_file ) . ' ' . escapeshellarg( $lang_dir . '/bhdt-wirecutter-en_US.po' ) );
	if ( file_exists( $mo_file ) ) {
		echo "✓ Created bhdt-wirecutter-en_US.mo\n";
	} else {
		echo "⚠ msgfmt not available - .mo file not created (WordPress will use .po)\n";
	}
}

echo "\n✓ Translation files created successfully!\n";
?>
