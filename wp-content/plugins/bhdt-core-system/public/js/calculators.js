(function () {
	const resistorColorData = {
		digits: [
			{ name: 'Den', value: 0, color: '#111111', text: '#ffffff' },
			{ name: 'Nau', value: 1, color: '#6f4e37', text: '#ffffff' },
			{ name: 'Do', value: 2, color: '#c0392b', text: '#ffffff' },
			{ name: 'Cam', value: 3, color: '#e67e22', text: '#ffffff' },
			{ name: 'Vang', value: 4, color: '#f1c40f', text: '#1a1a1a' },
			{ name: 'Luc', value: 5, color: '#27ae60', text: '#ffffff' },
			{ name: 'Lam', value: 6, color: '#2980b9', text: '#ffffff' },
			{ name: 'Tim', value: 7, color: '#8e44ad', text: '#ffffff' },
			{ name: 'Xam', value: 8, color: '#7f8c8d', text: '#ffffff' },
			{ name: 'Trang', value: 9, color: '#ecf0f1', text: '#1a1a1a' }
		],
		multipliers: [
			{ name: 'Bac', value: -2, color: '#bdc3c7', text: '#1a1a1a' },
			{ name: 'Vang', value: -1, color: '#d4ac0d', text: '#1a1a1a' },
			{ name: 'Den', value: 0, color: '#111111', text: '#ffffff' },
			{ name: 'Nau', value: 1, color: '#6f4e37', text: '#ffffff' },
			{ name: 'Do', value: 2, color: '#c0392b', text: '#ffffff' },
			{ name: 'Cam', value: 3, color: '#e67e22', text: '#ffffff' },
			{ name: 'Vang', value: 4, color: '#f1c40f', text: '#1a1a1a' },
			{ name: 'Luc', value: 5, color: '#27ae60', text: '#ffffff' },
			{ name: 'Lam', value: 6, color: '#2980b9', text: '#ffffff' },
			{ name: 'Tim', value: 7, color: '#8e44ad', text: '#ffffff' },
			{ name: 'Xam', value: 8, color: '#7f8c8d', text: '#ffffff' },
			{ name: 'Trang', value: 9, color: '#ecf0f1', text: '#1a1a1a' }
		],
		tolerances: [
			{ name: 'Nau', value: '±1%', color: '#6f4e37', text: '#ffffff' },
			{ name: 'Do', value: '±2%', color: '#c0392b', text: '#ffffff' },
			{ name: 'Luc', value: '±0.5%', color: '#27ae60', text: '#ffffff' },
			{ name: 'Lam', value: '±0.25%', color: '#2980b9', text: '#ffffff' },
			{ name: 'Tim', value: '±0.1%', color: '#8e44ad', text: '#ffffff' },
			{ name: 'Xam', value: '±0.05%', color: '#7f8c8d', text: '#ffffff' },
			{ name: 'Vang', value: '±5%', color: '#d4ac0d', text: '#1a1a1a' },
			{ name: 'Bac', value: '±10%', color: '#bdc3c7', text: '#1a1a1a' }
		]
	};

	function formatOhms(value) {
		if (!isFinite(value) || value <= 0) {
			return 'Khong hop le';
		}

		if (value >= 1000000) {
			return (value / 1000000).toFixed(value % 1000000 === 0 ? 0 : 2).replace(/\.00$/, '') + ' MOhm';
		}

		if (value >= 1000) {
			return (value / 1000).toFixed(value % 1000 === 0 ? 0 : 2).replace(/\.00$/, '') + ' kOhm';
		}

		if (value < 1) {
			return value.toFixed(2).replace(/0+$/, '').replace(/\.$/, '') + ' Ohm';
		}

		return value.toFixed(value % 1 === 0 ? 0 : 2).replace(/\.00$/, '') + ' Ohm';
	}

	function findBandByName(collection, name) {
		return collection.find(function (item) {
			return item.name === name;
		}) || collection[0];
	}

	function cloneBand(item) {
		return { name: item.name, value: item.value, color: item.color, text: item.text };
	}

	function initResistorCalculator() {
		document.querySelectorAll('[data-bhdt-resistor-tool]').forEach(function (tool) {
			const modeButtons = tool.querySelectorAll('[data-bhdt-band-mode]');
			const bandButtons = tool.querySelectorAll('[data-bhdt-band-index]');
			const bandLegendItems = tool.querySelectorAll('.bhdt-band-legend-item');
			const output = tool.querySelector('[data-bhdt-resistor-output] span');
			const ohmInput = tool.querySelector('[data-bhdt-ohm-input]');
			const capInput = tool.querySelector('[data-bhdt-cap-input]');
			const capOutput = tool.querySelector('[data-bhdt-cap-output]');

			let mode = 4;
			let state = [
				cloneBand(findBandByName(resistorColorData.digits, 'Nau')),
				cloneBand(findBandByName(resistorColorData.digits, 'Den')),
				cloneBand(findBandByName(resistorColorData.multipliers, 'Do')),
				cloneBand(findBandByName(resistorColorData.tolerances, 'Vang')),
				cloneBand(findBandByName(resistorColorData.tolerances, 'Nau'))
			];

			function getBandCollection(index) {
				if (mode === 4) {
					if (index <= 1) {
						return resistorColorData.digits;
					}
					if (index === 2) {
						return resistorColorData.multipliers;
					}
					return resistorColorData.tolerances;
				}

				if (index <= 2) {
					return resistorColorData.digits;
				}

				if (index === 3) {
					return resistorColorData.multipliers;
				}

				return resistorColorData.tolerances;
			}

			function updateBandUI() {
				if (bandLegendItems.length >= 4) {
					if (mode === 5) {
						bandLegendItems[2].textContent = 'Band 3';
						bandLegendItems[3].textContent = 'Multiplier / Tolerance';
					} else {
						bandLegendItems[2].textContent = 'Multiplier';
						bandLegendItems[3].textContent = 'Tolerance';
					}
				}

				bandButtons.forEach(function (button, index) {
					const isVisible = mode === 5 || index < 4;
					button.classList.toggle('is-hidden', !isVisible);

					if (!isVisible) {
						return;
					}

					const band = state[index];
					button.style.background = band.color;
					button.style.color = band.text;
					button.querySelector('span').textContent = band.name;
				});
			}

			function calculateValue() {
				let resistance;
				let tolerance;

				if (mode === 4) {
					const digits = '' + state[0].value + state[1].value;
					resistance = parseInt(digits, 10) * Math.pow(10, state[2].value);
					tolerance = state[3].value;
				} else {
					const digits = '' + state[0].value + state[1].value + state[2].value;
					resistance = parseInt(digits, 10) * Math.pow(10, state[3].value);
					tolerance = state[4].value;
				}

				output.textContent = formatOhms(resistance) + ' ' + tolerance;
				if (ohmInput) {
					ohmInput.value = Number.isInteger(resistance) ? String(resistance) : resistance.toFixed(2);
				}
			}

			function applyValueToBands(value) {
				if (!isFinite(value) || value <= 0) {
					return false;
				}

				const digitsNeeded = mode === 4 ? 2 : 3;
				const min = mode === 4 ? 10 : 100;
				const max = mode === 4 ? 99 : 999;
				const multipliers = resistorColorData.multipliers;
				let matched = null;

				multipliers.forEach(function (multiplier) {
					if (matched) {
						return;
					}

					const significant = value / Math.pow(10, multiplier.value);
					const rounded = Math.round(significant);

					if (Math.abs(significant - rounded) < 0.000001 && rounded >= min && rounded <= max) {
						matched = {
							significant: String(rounded).padStart(digitsNeeded, '0'),
							multiplier: multiplier
						};
					}
				});

				if (!matched) {
					return false;
				}

				state[0] = cloneBand(resistorColorData.digits[parseInt(matched.significant.charAt(0), 10)]);
				state[1] = cloneBand(resistorColorData.digits[parseInt(matched.significant.charAt(1), 10)]);
				if (mode === 5) {
					state[2] = cloneBand(resistorColorData.digits[parseInt(matched.significant.charAt(2), 10)]);
					state[3] = cloneBand(matched.multiplier);
					state[4] = cloneBand(findBandByName(resistorColorData.tolerances, 'Nau'));
				} else {
					state[2] = cloneBand(matched.multiplier);
					state[3] = cloneBand(findBandByName(resistorColorData.tolerances, 'Vang'));
				}

				updateBandUI();
				calculateValue();
				return true;
			}

			function cycleBand(index) {
				const collection = getBandCollection(index);
				const currentIndex = collection.findIndex(function (item) {
					return item.name === state[index].name;
				});
				const nextItem = collection[(currentIndex + 1) % collection.length];
				state[index] = cloneBand(nextItem);
				updateBandUI();
				calculateValue();
			}

			modeButtons.forEach(function (button) {
				button.addEventListener('click', function () {
					mode = parseInt(button.getAttribute('data-bhdt-band-mode'), 10);
					modeButtons.forEach(function (toggle) {
						toggle.classList.toggle('is-active', toggle === button);
					});

					if (mode === 4) {
						state[0] = cloneBand(findBandByName(resistorColorData.digits, 'Nau'));
						state[1] = cloneBand(findBandByName(resistorColorData.digits, 'Den'));
						state[2] = cloneBand(findBandByName(resistorColorData.multipliers, 'Do'));
						state[3] = cloneBand(findBandByName(resistorColorData.tolerances, 'Vang'));
					} else {
						state[0] = cloneBand(findBandByName(resistorColorData.digits, 'Nau'));
						state[1] = cloneBand(findBandByName(resistorColorData.digits, 'Den'));
						state[2] = cloneBand(findBandByName(resistorColorData.digits, 'Den'));
						state[3] = cloneBand(findBandByName(resistorColorData.multipliers, 'Nau'));
						state[4] = cloneBand(findBandByName(resistorColorData.tolerances, 'Nau'));
					}

					updateBandUI();
					calculateValue();
				});
			});

			bandButtons.forEach(function (button) {
				button.addEventListener('click', function () {
					const index = parseInt(button.getAttribute('data-bhdt-band-index'), 10);
					if (mode === 4 && index === 4) {
						return;
					}

					cycleBand(index);
				});
			});

			if (ohmInput) {
				ohmInput.addEventListener('change', function () {
					const applied = applyValueToBands(parseFloat(ohmInput.value));
					if (!applied) {
						output.textContent = 'Khong the quy doi chinh xac sang ma mau.';
					}
				});
			}

			if (capInput && capOutput) {
				capInput.addEventListener('input', function () {
					const code = capInput.value.trim();
					if (!/^\d{3}$/.test(code)) {
						capOutput.textContent = 'Nhap ma 3 chu so, vi du 104 = 100nF.';
						return;
					}

					const base = parseInt(code.slice(0, 2), 10);
					const multiplier = parseInt(code.slice(2), 10);
					const valuePf = base * Math.pow(10, multiplier);
					let readable = valuePf + 'pF';

					if (valuePf >= 1000000) {
						readable = (valuePf / 1000000).toFixed(2).replace(/\.00$/, '') + 'uF';
					} else if (valuePf >= 1000) {
						readable = (valuePf / 1000).toFixed(2).replace(/\.00$/, '') + 'nF';
					}

					capOutput.textContent = code + ' = ' + readable;
				});
			}

			updateBandUI();
			calculateValue();
		});
	}

	function formatVoltage(value) {
		if (!isFinite(value)) {
			return '0 V';
		}

		return value.toFixed(value >= 10 ? 2 : 3).replace(/0+$/, '').replace(/\.$/, '') + ' V';
	}

	function formatCurrent(value) {
		if (!isFinite(value)) {
			return '0 mA';
		}

		const milliAmp = value * 1000;
		if (Math.abs(milliAmp) < 1) {
			return (value * 1000000).toFixed(2).replace(/\.00$/, '') + ' uA';
		}

		return milliAmp.toFixed(3).replace(/0+$/, '').replace(/\.$/, '') + ' mA';
	}

	function formatPower(value) {
		if (!isFinite(value)) {
			return '0 mW';
		}

		return (value * 1000).toFixed(3).replace(/0+$/, '').replace(/\.$/, '') + ' mW';
	}

	function initVoltageDivider() {
		document.querySelectorAll('[data-bhdt-voltage-divider]').forEach(function (tool) {
			const vinInput = tool.querySelector('[data-bhdt-vin-input]');
			const r1Input = tool.querySelector('[data-bhdt-r1-input]');
			const r2Input = tool.querySelector('[data-bhdt-r2-input]');
			const targetInput = tool.querySelector('[data-bhdt-target-input]');
			const voutOutput = tool.querySelector('[data-bhdt-vout]');
			const currentOutput = tool.querySelector('[data-bhdt-current]');
			const powerOutput = tool.querySelector('[data-bhdt-power]');
			const hint = tool.querySelector('[data-bhdt-divider-hint]');

			function numericValue(input) {
				return input ? parseFloat(input.value) : NaN;
			}

			function updateDivider() {
				const vin = numericValue(vinInput);
				const r1 = numericValue(r1Input);
				const r2 = numericValue(r2Input);
				const target = numericValue(targetInput);

				if (!isFinite(vin) || !isFinite(r1) || !isFinite(r2) || vin < 0 || r1 <= 0 || r2 <= 0) {
					if (voutOutput) voutOutput.textContent = 'Khong hop le';
					if (currentOutput) currentOutput.textContent = 'Khong hop le';
					if (powerOutput) powerOutput.textContent = 'Khong hop le';
					if (hint) hint.textContent = 'Vin phai >= 0, R1 va R2 phai lon hon 0 Ohm.';
					return;
				}

				const totalResistance = r1 + r2;
				const current = vin / totalResistance;
				const vout = vin * r2 / totalResistance;
				const p1 = current * current * r1;
				const p2 = current * current * r2;

				if (voutOutput) voutOutput.textContent = formatVoltage(vout);
				if (currentOutput) currentOutput.textContent = formatCurrent(current);
				if (powerOutput) powerOutput.textContent = 'R1 ' + formatPower(p1) + ' / R2 ' + formatPower(p2);

				if (hint) {
					if (isFinite(target) && target > 0 && target < vin) {
						const ratio = (vin / target) - 1;
						hint.textContent = 'De dat ' + formatVoltage(target) + ', chon ti le gan dung R1 = ' + ratio.toFixed(2).replace(/\.00$/, '') + ' x R2.';
					} else if (isFinite(target) && target >= vin) {
						hint.textContent = 'Vout muc tieu phai nho hon Vin voi cau chia ap thu dong.';
					} else {
						hint.textContent = 'Tong tro hien tai: ' + formatOhms(totalResistance) + '. Dong qua cau chia ap: ' + formatCurrent(current) + '.';
					}
				}
			}

			[vinInput, r1Input, r2Input, targetInput].forEach(function (input) {
				if (input) {
					input.addEventListener('input', updateDivider);
				}
			});

			updateDivider();
		});
	}

	document.addEventListener('DOMContentLoaded', function () {
		initResistorCalculator();
		initVoltageDivider();
	});
})();
