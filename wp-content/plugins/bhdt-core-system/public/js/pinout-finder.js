(function () {
	const pinoutDictionary = {
		esp32: {
			name: 'ESP32 DevKit V1',
			filename: 'esp32-pinout-diagram.webp',
			summary: 'WiFi/Bluetooth MCU, logic 3.3V, nhieu GPIO co PWM/ADC/UART/I2C/SPI.',
			notes: ['VIN -> 5V input', '3V3 -> regulated output', 'GPIO21/22 -> I2C default', 'EN -> reset line'],
			specs: ['3.3V logic', '38-pin layout', 'WiFi + BLE']
		},
		lm2596: {
			name: 'LM2596 Buck Module',
			filename: 'lm2596-pinout-diagram.webp',
			summary: 'Module ha ap DC-DC pho bien cho du an nguon DIY va test bench.',
			notes: ['IN+ / IN- -> nguon vao', 'OUT+ / OUT- -> nguon ra', 'Bien tro -> chinh ap', 'Khuyen nghi them tai gia khi tinh chinh'],
			specs: ['4.5V-35V in', 'Adj. output', 'Power module']
		},
		ams1117: {
			name: 'AMS1117 Regulator Board',
			filename: 'ams1117-regulator-diagram.webp',
			summary: 'Mach on ap tuyen tinh cho rail 3.3V hoac 5V voi dau vao DC cao hon.',
			notes: ['VIN -> dau vao', 'GND -> mass chung', 'VOUT -> dien ap on ap', 'Luu y nhiet khi dong tai tang'],
			specs: ['LDO regulator', 'Low-cost board', 'Heat-sensitive']
		},
		arduino: {
			name: 'Arduino Nano',
			filename: 'arduino-nano-pinout-diagram.webp',
			summary: 'Board ATmega328P phu hop nguyen mau nhanh, giao duc va dieu khien co ban.',
			notes: ['D2-D13 -> digital IO', 'A0-A7 -> analog input', '5V -> regulated rail', 'VIN -> unregulated input'],
			specs: ['ATmega328P', 'USB mini', '5V logic']
		},
		relay: {
			name: '1-Channel Relay Module',
			filename: 'relay-module-pinout-diagram.webp',
			summary: 'Module relay 1 kenh dieu khien tai AC/DC bang tin hieu logic.',
			notes: ['VCC/GND -> cap nguon', 'IN -> chan kich', 'COM/NO/NC -> ngo tai', 'Kiem tra jumper high/low trigger'],
			specs: ['Single channel', '5V trigger', 'Isolated switching']
		}
	};

	function escapeHtml(value) {
		return String(value || '').replace(/[&<>"']/g, function (char) {
			return {
				'&': '&amp;',
				'<': '&lt;',
				'>': '&gt;',
				'"': '&quot;',
				"'": '&#039;'
			}[char];
		});
	}

	function normalizePinoutItem(item) {
		const key = String(item.slug || item.key || item.name || '').trim().toLowerCase();
		if (!key || !item.name) {
			return null;
		}

		return {
			name: String(item.name || ''),
			filename: String(item.filename || 'pinout-placeholder.webp'),
			summary: String(item.summary || 'Pinout placeholder duoc tao tu thu vien linh kien. Hay bo sung so do chan, nguon va ghi chu dau noi.'),
			imageUrl: String(item.image_url || ''),
			datasheetUrl: String(item.datasheet_url || ''),
			officialUrl: String(item.official_url || ''),
			notes: Array.isArray(item.notes) ? item.notes.map(String).filter(Boolean) : [],
			specs: Array.isArray(item.specs) ? item.specs.map(String).filter(Boolean) : [],
			aliases: Array.isArray(item.aliases) ? item.aliases.map(function (alias) {
				return String(alias || '').toLowerCase();
			}).filter(Boolean) : []
		};
	}

	if (window.bhdtPinoutLibrary && Array.isArray(window.bhdtPinoutLibrary.items)) {
		window.bhdtPinoutLibrary.items.forEach(function (item) {
			const normalized = normalizePinoutItem(item);
			const key = String(item.slug || item.key || item.name || '').trim().toLowerCase();
			if (normalized && key && !pinoutDictionary[key]) {
				pinoutDictionary[key] = normalized;
			}
		});
	}

	function initPinoutFinder() {
		document.querySelectorAll('[data-bhdt-pinout-finder]').forEach(function (finder) {
			const input = finder.querySelector('[data-bhdt-pinout-input]');
			const results = finder.querySelector('[data-bhdt-pinout-results]');
			const presetButtons = finder.querySelectorAll('[data-bhdt-pinout-preset]');

			if (!input || !results) {
				return;
			}

			function renderResults(query) {
				if (!query) {
					results.innerHTML = '<article class="bhdt-placeholder-card is-empty"><h3>Chua co ket qua</h3><p>Go ten module de hien thi so do chan dang placeholder .webp, rail nguon khuyen nghi va ghi chu mapping I/O.</p></article>';
					presetButtons.forEach(function (button) {
						button.classList.remove('is-active');
					});
					return;
				}

				const matches = Object.keys(pinoutDictionary).filter(function (key) {
					const item = pinoutDictionary[key];
					return key.indexOf(query) !== -1 || item.name.toLowerCase().indexOf(query) !== -1 || (item.aliases || []).some(function (alias) {
						return alias.indexOf(query) !== -1;
					});
				});

				presetButtons.forEach(function (button) {
					button.classList.toggle('is-active', button.getAttribute('data-bhdt-pinout-preset') === query);
				});

				if (!matches.length) {
					results.innerHTML = '<article class="bhdt-placeholder-card"><h3>Khong tim thay mau phu hop</h3><p>Thu lai voi ESP32, Arduino, LM2596, AMS1117 hoac relay.</p></article>';
					return;
				}

				results.innerHTML = matches.map(function (key) {
					const item = pinoutDictionary[key];
					const notes = item.notes.map(function (note) {
						return '<li>' + escapeHtml(note) + '</li>';
					}).join('');
					const specs = item.specs.map(function (spec) {
						return '<span class="bhdt-spec-pill">' + escapeHtml(spec) + '</span>';
					}).join('');
					const sources = [
						item.datasheetUrl ? '<a href="' + escapeHtml(item.datasheetUrl) + '" target="_blank" rel="noopener">Datasheet</a>' : '',
						item.officialUrl ? '<a href="' + escapeHtml(item.officialUrl) + '" target="_blank" rel="noopener">Official docs</a>' : ''
					].filter(Boolean).join(' ');
					const visual = item.imageUrl
						? '<img src="' + escapeHtml(item.imageUrl) + '" alt="' + escapeHtml(item.name) + ' pinout" loading="lazy">'
						: '<div><span>.webp diagram</span><br><strong>' + escapeHtml(item.filename) + '</strong></div>';

					return '<article class="bhdt-placeholder-card"><div class="bhdt-pinout-figure"><div><h3>' + escapeHtml(item.name) + '</h3><p>' + escapeHtml(item.summary) + '</p><div class="bhdt-pinout-specs">' + specs + '</div><ul>' + notes + '</ul><p class="bhdt-pinout-sources">' + sources + '</p></div><div class="bhdt-image-placeholder">' + visual + '</div></div></article>';
				}).join('');
			}

			input.addEventListener('input', function () {
				renderResults(input.value.trim().toLowerCase());
			});

			presetButtons.forEach(function (button) {
				button.addEventListener('click', function () {
					const preset = button.getAttribute('data-bhdt-pinout-preset') || '';
					input.value = preset;
					renderResults(preset.toLowerCase());
				});
			});
		});
	}

	document.addEventListener('DOMContentLoaded', initPinoutFinder);
})();
