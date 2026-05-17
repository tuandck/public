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
					return key.indexOf(query) !== -1 || pinoutDictionary[key].name.toLowerCase().indexOf(query) !== -1;
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
						return '<li>' + note + '</li>';
					}).join('');
					const specs = item.specs.map(function (spec) {
						return '<span class="bhdt-spec-pill">' + spec + '</span>';
					}).join('');

					return '<article class="bhdt-placeholder-card"><div class="bhdt-pinout-figure"><div><h3>' + item.name + '</h3><p>' + item.summary + '</p><div class="bhdt-pinout-specs">' + specs + '</div><ul>' + notes + '</ul></div><div class="bhdt-image-placeholder"><div><span>.webp diagram</span><br><strong>' + item.filename + '</strong></div></div></div></article>';
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
