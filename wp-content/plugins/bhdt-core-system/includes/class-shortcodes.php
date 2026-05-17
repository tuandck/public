<?php
/**
 * Shortcode rendering module.
 *
 * @package BHDT_Core_System
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'BHDT_Shortcodes' ) ) {
	/**
	 * Registers and renders all frontend shortcodes.
	 */
	final class BHDT_Shortcodes {
		/**
		 * Boot hooks.
		 *
		 * @return void
		 */
		public static function init() {
			$instance = new self();
			add_action( 'init', array( $instance, 'register_shortcodes' ) );
		}

		/**
		 * Register shortcodes.
		 *
		 * @return void
		 */
		public function register_shortcodes() {
			add_shortcode( 'bhdt_pinout_finder', array( $this, 'render_pinout_finder_shortcode' ) );
			add_shortcode( 'bhdt_resistor_calculator', array( $this, 'render_resistor_calculator_shortcode' ) );
			add_shortcode( 'bhdt_voltage_divider', array( $this, 'render_voltage_divider_shortcode' ) );
			add_shortcode( 'bhdt_project_hub', array( $this, 'render_project_hub_shortcode' ) );
			add_shortcode( 'bhdt_pcb_affiliate', array( $this, 'render_pcb_affiliate_shortcode' ) );
			add_shortcode( 'bhdt_wirecutter_pick', array( $this, 'render_wirecutter_pick_shortcode' ) );
		}

		/**
		 * Render pinout finder shortcode.
		 *
		 * @return string
		 */
		public function render_pinout_finder_shortcode() {
			BHDT_Assets::enqueue_frontend_assets();

			ob_start();
			?>
			<section class="bhdt-tool-card bhdt-pinout-finder" data-bhdt-pinout-finder>
				<header class="bhdt-tool-header">
					<p class="bhdt-eyebrow"><?php esc_html_e( 'Maker Utility', 'bhdt-core-system' ); ?></p>
					<h2><?php esc_html_e( 'Pinout Finder', 'bhdt-core-system' ); ?></h2>
					<p><?php esc_html_e( 'Tra cứu nhanh sơ đồ chân theo phong cách card sản phẩm kỹ thuật: rõ module, rõ rail nguồn, rõ nhóm tín hiệu cần đấu dây.', 'bhdt-core-system' ); ?></p>
				</header>

				<div class="bhdt-market-strip">
					<div class="bhdt-market-chip">
						<strong><?php esc_html_e( 'Popular', 'bhdt-core-system' ); ?></strong>
						<span><?php esc_html_e( 'ESP32, LM2596, relay', 'bhdt-core-system' ); ?></span>
					</div>
					<div class="bhdt-market-chip">
						<strong><?php esc_html_e( 'Output', 'bhdt-core-system' ); ?></strong>
						<span><?php esc_html_e( 'Placeholder .webp + chân tín hiệu', 'bhdt-core-system' ); ?></span>
					</div>
					<div class="bhdt-market-chip">
						<strong><?php esc_html_e( 'Use case', 'bhdt-core-system' ); ?></strong>
						<span><?php esc_html_e( 'Review linh kiện và landing page DIY', 'bhdt-core-system' ); ?></span>
					</div>
				</div>

				<div class="bhdt-search-shell">
					<label class="bhdt-label" for="bhdt-pinout-query"><?php esc_html_e( 'Nhập từ khóa linh kiện', 'bhdt-core-system' ); ?></label>
					<div class="bhdt-input-wrap">
						<span class="bhdt-input-icon" aria-hidden="true">+</span>
						<input id="bhdt-pinout-query" class="bhdt-input" type="search" placeholder="ESP32, LM2596, AMS1117..." autocomplete="off" data-bhdt-pinout-input />
					</div>
					<div class="bhdt-quick-picks" aria-label="<?php esc_attr_e( 'Gợi ý tra cứu nhanh', 'bhdt-core-system' ); ?>">
						<button type="button" class="bhdt-pill-button" data-bhdt-pinout-preset="esp32">ESP32</button>
						<button type="button" class="bhdt-pill-button" data-bhdt-pinout-preset="lm2596">LM2596</button>
						<button type="button" class="bhdt-pill-button" data-bhdt-pinout-preset="arduino">Arduino Nano</button>
						<button type="button" class="bhdt-pill-button" data-bhdt-pinout-preset="relay">Relay</button>
					</div>
				</div>
				<div class="bhdt-pinout-results" data-bhdt-pinout-results>
					<article class="bhdt-placeholder-card is-empty">
						<h3><?php esc_html_e( 'Chưa có kết quả', 'bhdt-core-system' ); ?></h3>
						<p><?php esc_html_e( 'Gõ tên module để hiển thị sơ đồ chân dạng placeholder .webp, rail nguồn khuyến nghị và ghi chú mapping I/O.', 'bhdt-core-system' ); ?></p>
					</article>
				</div>
			</section>
			<?php

			return ob_get_clean();
		}

		/**
		 * Render resistor calculator shortcode.
		 *
		 * @return string
		 */
		public function render_resistor_calculator_shortcode() {
			BHDT_Assets::enqueue_frontend_assets();

			ob_start();
			?>
			<section class="bhdt-tool-card bhdt-resistor-calculator" data-bhdt-resistor-tool>
				<header class="bhdt-tool-header">
					<p class="bhdt-eyebrow"><?php esc_html_e( 'Electronics Utility', 'bhdt-core-system' ); ?></p>
					<h2><?php esc_html_e( 'Resistor Calculator', 'bhdt-core-system' ); ?></h2>
					<p><?php esc_html_e( 'Widget tra cứu nhanh cho layout review và catalogue linh kiện: đọc màu, suy ra Ohm, và đối chiếu mã tụ chỉ trong một khối giao diện.', 'bhdt-core-system' ); ?></p>
				</header>

				<div class="bhdt-market-strip bhdt-market-strip-compact">
					<div class="bhdt-market-chip">
						<strong><?php esc_html_e( 'Modes', 'bhdt-core-system' ); ?></strong>
						<span><?php esc_html_e( '4-band / 5-band', 'bhdt-core-system' ); ?></span>
					</div>
					<div class="bhdt-market-chip">
						<strong><?php esc_html_e( 'Lookup', 'bhdt-core-system' ); ?></strong>
						<span><?php esc_html_e( 'Ohm to color + capacitor code', 'bhdt-core-system' ); ?></span>
					</div>
				</div>

				<div class="bhdt-toggle-row" role="tablist" aria-label="<?php esc_attr_e( 'Chọn chế độ điện trở', 'bhdt-core-system' ); ?>">
					<button type="button" class="bhdt-toggle-button is-active" data-bhdt-band-mode="4"><?php esc_html_e( '4 Vòng', 'bhdt-core-system' ); ?></button>
					<button type="button" class="bhdt-toggle-button" data-bhdt-band-mode="5"><?php esc_html_e( '5 Vòng', 'bhdt-core-system' ); ?></button>
				</div>

				<div class="bhdt-band-legend" data-bhdt-band-legend>
					<span class="bhdt-band-legend-item"><?php esc_html_e( 'Band 1', 'bhdt-core-system' ); ?></span>
					<span class="bhdt-band-legend-item"><?php esc_html_e( 'Band 2', 'bhdt-core-system' ); ?></span>
					<span class="bhdt-band-legend-item"><?php esc_html_e( 'Multiplier', 'bhdt-core-system' ); ?></span>
					<span class="bhdt-band-legend-item"><?php esc_html_e( 'Tolerance', 'bhdt-core-system' ); ?></span>
				</div>

				<div class="bhdt-resistor-body" aria-live="polite" data-bhdt-resistor-body>
					<button type="button" class="bhdt-band-button" data-bhdt-band-index="0"><span><?php esc_html_e( 'Nâu', 'bhdt-core-system' ); ?></span></button>
					<button type="button" class="bhdt-band-button" data-bhdt-band-index="1"><span><?php esc_html_e( 'Đen', 'bhdt-core-system' ); ?></span></button>
					<button type="button" class="bhdt-band-button" data-bhdt-band-index="2"><span><?php esc_html_e( 'Đỏ', 'bhdt-core-system' ); ?></span></button>
					<button type="button" class="bhdt-band-button" data-bhdt-band-index="3"><span><?php esc_html_e( 'Vàng', 'bhdt-core-system' ); ?></span></button>
					<button type="button" class="bhdt-band-button is-hidden" data-bhdt-band-index="4"><span><?php esc_html_e( 'Nâu', 'bhdt-core-system' ); ?></span></button>
				</div>

				<div class="bhdt-status-line">
					<span><?php esc_html_e( 'Click vào từng band để đổi màu.', 'bhdt-core-system' ); ?></span>
					<span><?php esc_html_e( 'Hoặc nhập trực tiếp giá trị Ohm để auto-map.', 'bhdt-core-system' ); ?></span>
				</div>

				<div class="bhdt-grid-2col">
					<div>
						<label class="bhdt-label" for="bhdt-ohm-value"><?php esc_html_e( 'Nhập giá trị Ohm', 'bhdt-core-system' ); ?></label>
						<input id="bhdt-ohm-value" class="bhdt-input" type="number" min="0" step="0.01" placeholder="4700" data-bhdt-ohm-input />
						<p class="bhdt-helper"><?php esc_html_e( 'Ví dụ: 220, 4700, 100000 hoặc 0.47.', 'bhdt-core-system' ); ?></p>
					</div>
					<div>
						<label class="bhdt-label" for="bhdt-cap-code"><?php esc_html_e( 'Tra cứu mã tụ', 'bhdt-core-system' ); ?></label>
						<input id="bhdt-cap-code" class="bhdt-input" type="text" inputmode="numeric" placeholder="104" data-bhdt-cap-input />
						<p class="bhdt-helper" data-bhdt-cap-output><?php esc_html_e( 'Ví dụ: 104 = 100nF.', 'bhdt-core-system' ); ?></p>
					</div>
				</div>

				<output class="bhdt-output-panel" data-bhdt-resistor-output>
					<strong><?php esc_html_e( 'Giá trị hiện tại:', 'bhdt-core-system' ); ?></strong>
					<span><?php esc_html_e( '100 Ohm ±5%', 'bhdt-core-system' ); ?></span>
				</output>
			</section>
			<?php

			return ob_get_clean();
		}

		/**
		 * Render voltage divider shortcode.
		 *
		 * @return string
		 */
		public function render_voltage_divider_shortcode() {
			BHDT_Assets::enqueue_frontend_assets();

			ob_start();
			?>
			<section class="bhdt-tool-card bhdt-voltage-divider" data-bhdt-voltage-divider>
				<header class="bhdt-tool-header">
					<p class="bhdt-eyebrow"><?php esc_html_e( 'Electronics Utility', 'bhdt-core-system' ); ?></p>
					<h2><?php esc_html_e( 'Voltage Divider Calculator', 'bhdt-core-system' ); ?></h2>
					<p><?php esc_html_e( 'Tính nhanh điện áp ra, dòng qua cầu chia áp và công suất trên từng điện trở trước khi đưa tín hiệu vào ADC hoặc mạch đo.', 'bhdt-core-system' ); ?></p>
				</header>

				<div class="bhdt-market-strip bhdt-market-strip-compact">
					<div class="bhdt-market-chip">
						<strong><?php esc_html_e( 'Formula', 'bhdt-core-system' ); ?></strong>
						<span>Vout = Vin × R2 / (R1 + R2)</span>
					</div>
					<div class="bhdt-market-chip">
						<strong><?php esc_html_e( 'Use case', 'bhdt-core-system' ); ?></strong>
						<span><?php esc_html_e( 'ADC, sensor scaling, battery monitor', 'bhdt-core-system' ); ?></span>
					</div>
				</div>

				<div class="bhdt-voltage-layout">
					<div class="bhdt-grid-2col">
						<div>
							<label class="bhdt-label" for="bhdt-vin-value"><?php esc_html_e( 'Vin (V)', 'bhdt-core-system' ); ?></label>
							<input id="bhdt-vin-value" class="bhdt-input" type="number" min="0" step="0.01" value="12" data-bhdt-vin-input />
						</div>
						<div>
							<label class="bhdt-label" for="bhdt-r1-value"><?php esc_html_e( 'R1 phía trên (Ω)', 'bhdt-core-system' ); ?></label>
							<input id="bhdt-r1-value" class="bhdt-input" type="number" min="0" step="1" value="10000" data-bhdt-r1-input />
						</div>
						<div>
							<label class="bhdt-label" for="bhdt-r2-value"><?php esc_html_e( 'R2 xuống GND (Ω)', 'bhdt-core-system' ); ?></label>
							<input id="bhdt-r2-value" class="bhdt-input" type="number" min="0" step="1" value="4700" data-bhdt-r2-input />
						</div>
						<div>
							<label class="bhdt-label" for="bhdt-target-value"><?php esc_html_e( 'Vout mục tiêu (tùy chọn)', 'bhdt-core-system' ); ?></label>
							<input id="bhdt-target-value" class="bhdt-input" type="number" min="0" step="0.01" placeholder="3.3" data-bhdt-target-input />
							<p class="bhdt-helper"><?php esc_html_e( 'Nhập để gợi ý tỉ lệ R1/R2 gần đúng.', 'bhdt-core-system' ); ?></p>
						</div>
					</div>

					<div class="bhdt-voltage-schematic" aria-hidden="true">
						<span class="bhdt-node bhdt-node-vin">Vin</span>
						<span class="bhdt-resistor-symbol">R1</span>
						<span class="bhdt-node bhdt-node-vout">Vout</span>
						<span class="bhdt-resistor-symbol">R2</span>
						<span class="bhdt-node bhdt-node-gnd">GND</span>
					</div>
				</div>

				<output class="bhdt-output-panel bhdt-voltage-output" data-bhdt-voltage-output>
					<span><strong><?php esc_html_e( 'Vout:', 'bhdt-core-system' ); ?></strong> <b data-bhdt-vout>0 V</b></span>
					<span><strong><?php esc_html_e( 'Dòng:', 'bhdt-core-system' ); ?></strong> <b data-bhdt-current>0 mA</b></span>
					<span><strong><?php esc_html_e( 'Công suất:', 'bhdt-core-system' ); ?></strong> <b data-bhdt-power>0 mW</b></span>
				</output>
				<p class="bhdt-helper" data-bhdt-divider-hint><?php esc_html_e( 'Giữ tổng trở đủ cao để tiết kiệm pin, nhưng đừng quá cao nếu ADC cần nguồn trở thấp.', 'bhdt-core-system' ); ?></p>
			</section>
			<?php

			return ob_get_clean();
		}

		/**
		 * Render project hub shortcode.
		 *
		 * @return string
		 */
		public function render_project_hub_shortcode() {
			BHDT_Assets::enqueue_frontend_assets();

			$steps = array(
				array(
					'title'       => __( 'Bước 1: Tách script video thành checklist', 'bhdt-core-system' ),
					'description' => __( 'Chuẩn hóa lời thoại thành danh sách linh kiện, dụng cụ và trình tự thao tác để hạn chế bỏ sót khi dựng bài.', 'bhdt-core-system' ),
				),
				array(
					'title'       => __( 'Bước 2: Chia cụm hàn và test nhanh', 'bhdt-core-system' ),
					'description' => __( 'Nhóm từng công đoạn: nguồn, điều khiển, hiển thị, enclosure để quay B-roll và kiểm tra từng module độc lập.', 'bhdt-core-system' ),
				),
				array(
					'title'       => __( 'Bước 3: Tạo BOM mua nhanh', 'bhdt-core-system' ),
					'description' => __( 'Xuất danh sách BOM có số lượng, link mua, và mã thay thế tương đương để chèn vào landing page dự án.', 'bhdt-core-system' ),
				),
				array(
					'title'       => __( 'Bước 4: Chèn checkpoint hiệu chỉnh', 'bhdt-core-system' ),
					'description' => __( 'Đánh dấu các điểm cần đo điện áp, dòng tiêu thụ và tín hiệu logic trước khi đóng gói phiên bản hoàn thiện.', 'bhdt-core-system' ),
				),
			);

			ob_start();
			?>
			<section class="bhdt-tool-card bhdt-project-hub">
				<header class="bhdt-tool-header">
					<p class="bhdt-eyebrow"><?php esc_html_e( 'Project Hub', 'bhdt-core-system' ); ?></p>
					<h2><?php esc_html_e( 'DIY Assembly Workflow', 'bhdt-core-system' ); ?></h2>
					<p><?php esc_html_e( 'Template giao diện hub để đóng gói hướng dẫn lắp ráp từng bước từ video script, sẵn sàng gắn CTA bán BOM hoàn chỉnh.', 'bhdt-core-system' ); ?></p>
				</header>

				<div class="bhdt-card-grid">
					<?php foreach ( $steps as $index => $step ) : ?>
						<article class="bhdt-step-card">
							<p class="bhdt-step-index"><?php echo esc_html( sprintf( '%02d', $index + 1 ) ); ?></p>
							<h3><?php echo esc_html( $step['title'] ); ?></h3>
							<p><?php echo esc_html( $step['description'] ); ?></p>
						</article>
					<?php endforeach; ?>
				</div>

				<div class="bhdt-affiliate-panel">
					<div>
						<h3><?php esc_html_e( 'Buy Complete BOM List on Shopee', 'bhdt-core-system' ); ?></h3>
						<p><?php esc_html_e( 'Khu vực CTA nổi bật để điều hướng người dùng sang bộ linh kiện đầy đủ, giảm ma sát khi follow dự án.', 'bhdt-core-system' ); ?></p>
					</div>
					<a class="bhdt-button-primary" href="https://shopee.vn" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Mở trang BOM affiliate', 'bhdt-core-system' ); ?></a>
				</div>
			</section>
			<?php

			return ob_get_clean();
		}

		/**
		 * Render PCB affiliate shortcode.
		 *
		 * @return string
		 */
		public function render_pcb_affiliate_shortcode() {
			BHDT_Assets::enqueue_frontend_assets();

			ob_start();
			?>
			<section class="bhdt-tool-card bhdt-pcb-affiliate">
				<header class="bhdt-tool-header">
					<p class="bhdt-eyebrow"><?php esc_html_e( 'PCB Fulfillment', 'bhdt-core-system' ); ?></p>
					<h2><?php esc_html_e( 'Gerber Download & Fabrication Links', 'bhdt-core-system' ); ?></h2>
					<p><?php esc_html_e( 'Khối placeholder cho file Gerber, tài liệu assembly, và điều hướng sang các nhà sản xuất PCB bên ngoài.', 'bhdt-core-system' ); ?></p>
				</header>

				<div class="bhdt-download-shell">
					<div class="bhdt-download-card">
						<h3><?php esc_html_e( 'Gerber Package', 'bhdt-core-system' ); ?></h3>
						<p><?php esc_html_e( 'Tải bộ Gerber zip, sơ đồ nguyên lý PDF và placement guide cho xưởng lắp ráp.', 'bhdt-core-system' ); ?></p>
						<div class="bhdt-button-row">
							<a class="bhdt-button-secondary" href="#gerber-download-template"><?php esc_html_e( 'Download Gerber', 'bhdt-core-system' ); ?></a>
							<a class="bhdt-button-secondary" href="#bom-download-template"><?php esc_html_e( 'Download BOM CSV', 'bhdt-core-system' ); ?></a>
						</div>
					</div>

					<div class="bhdt-download-card">
						<h3><?php esc_html_e( 'Order PCB Online', 'bhdt-core-system' ); ?></h3>
						<p><?php esc_html_e( 'Điểm chèn link affiliate routing sang xưởng PCB cho nguyên mẫu và sản xuất lô nhỏ.', 'bhdt-core-system' ); ?></p>
						<div class="bhdt-button-row">
							<a class="bhdt-button-primary" href="https://jlcpcb.com" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Order via JLCPCB', 'bhdt-core-system' ); ?></a>
							<a class="bhdt-button-primary" href="https://www.pcbway.com" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Order via PCBWay', 'bhdt-core-system' ); ?></a>
						</div>
					</div>
				</div>
			</section>
			<?php

			return ob_get_clean();
		}

		/**
		 * Render Wirecutter-style pick box.
		 *
		 * @param array $atts Shortcode attrs.
		 * @return string
		 */
		public function render_wirecutter_pick_shortcode( $atts ) {
			BHDT_Assets::enqueue_frontend_assets();

			$atts = shortcode_atts(
				array(
					'badge' => '',
					'title' => '',
					'price' => '',
					'link'  => '',
					'pros'  => '',
					'cons'  => '',
				),
				$atts,
				'bhdt_wirecutter_pick'
			);

			$pros = array_filter( array_map( 'trim', explode( '|', $atts['pros'] ) ) );
			$cons = array_filter( array_map( 'trim', explode( '|', $atts['cons'] ) ) );

			ob_start();
			?>
			<div class="bhdt-wirecutter-pick">
				<div class="bhdt-wirecutter-head">
					<div class="bhdt-wirecutter-badge"><?php echo esc_html( strtoupper( $atts['badge'] ) ); ?></div>
					<h3><?php echo esc_html( $atts['title'] ); ?></h3>
					<p class="bhdt-wirecutter-price"><?php echo esc_html( $atts['price'] ); ?></p>
				</div>
				<div class="bhdt-wirecutter-body">
					<div>
						<h4><?php esc_html_e( 'Ưu điểm', 'bhdt-core-system' ); ?></h4>
						<ul class="bhdt-wirecutter-list bhdt-wirecutter-pros">
							<?php foreach ( $pros as $pro ) : ?>
								<li><?php echo esc_html( $pro ); ?></li>
							<?php endforeach; ?>
						</ul>
					</div>
					<div>
						<h4><?php esc_html_e( 'Nhược điểm', 'bhdt-core-system' ); ?></h4>
						<ul class="bhdt-wirecutter-list bhdt-wirecutter-cons">
							<?php foreach ( $cons as $con ) : ?>
								<li><?php echo esc_html( $con ); ?></li>
							<?php endforeach; ?>
						</ul>
					</div>
				</div>
				<a class="bhdt-wirecutter-cta" href="<?php echo esc_url( $atts['link'] ); ?>" target="_blank" rel="noopener noreferrer">
					<?php esc_html_e( 'Xem ưu đãi', 'bhdt-core-system' ); ?>
				</a>
			</div>
			<?php

			return ob_get_clean();
		}
	}
}
