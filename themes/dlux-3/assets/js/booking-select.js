/* Pricing & Beyond the wash: pick a package + vehicle size, add extras, then "Book now"
 * opens the booking page with everything preselected (?service&package&size&extras). */
(function () {
	'use strict';

	function money(n) {
		n = Number(n) || 0;
		return '$' + (n % 1 ? n.toFixed(2) : n.toLocaleString('en-AU'));
	}
	function parse(json) { try { return JSON.parse(json || '{}'); } catch (e) { return {}; } }

	function init(section) {
		var bar = section.querySelector('[data-booking-bar]');
		var S = { pkg: null, name: '', size: '', price: 0, extras: [], sizeChosen: false, opt: 0, optText: '' }; // extras: pill elements

		// Does the selected package have "Choose from" options? (one must be picked)
		function needsOption() {
			var c = cards(S.pkg)[0];
			return !!(c && c.querySelector('.package__option'));
		}
		function ready() { return S.sizeChosen && (!needsOption() || S.opt > 0); }

		function cards(id) {
			return Array.prototype.slice.call(section.querySelectorAll(id ? '.package[data-package="' + id + '"]' : '.package[data-package]'));
		}
		function pills() { return Array.prototype.slice.call(section.querySelectorAll('.extra-pill')); }
		function extraIdFor(pill) {
			var ids = parse(pill.getAttribute('data-extra-ids'));
			return ids['*'] || (S.pkg ? ids[S.pkg] : '') || '';
		}
		function available(pill) {
			var ids = parse(pill.getAttribute('data-extra-ids'));
			return !!ids['*'] || !S.pkg || !!ids[S.pkg];
		}

		// After choosing a package, bring the "Book now" bar into view (only if it isn't already).
		function revealBar() {
			if (!bar || bar.hidden) return;
			var r = bar.getBoundingClientRect();
			if (r.top >= 0 && r.bottom <= window.innerHeight) return;
			var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
			bar.scrollIntoView({ behavior: reduce ? 'auto' : 'smooth', block: 'center' });
		}

		function select(card, size, scroll) {
			var rows = Array.prototype.slice.call(card.querySelectorAll('.package__price-row:not([disabled])'));
			if (!rows.length) return;
			var row = null;
			rows.forEach(function (r) { if (r.getAttribute('data-size') === size) row = r; });
			if (!row) {
				// keep the current size when switching package, else the first one
				rows.forEach(function (r) { if (!row && r.getAttribute('data-size') === S.size) row = r; });
				row = row || rows[0];
			}
			var changed = S.pkg !== card.getAttribute('data-package');
			if (changed) { S.opt = 0; S.optText = ''; S.sizeChosen = false; }
			if (scroll) S.sizeChosen = true; // a vehicle size was clicked
			S.pkg = card.getAttribute('data-package');
			S.name = card.getAttribute('data-package-name') || '';
			S.size = row.getAttribute('data-size');
			S.price = Number(row.getAttribute('data-price')) || 0;
			update();
			if (scroll && ready()) revealBar(); // after a size is picked (and an option, if the package has them)
		}

		function update() {
			// cards (including carousel clones)
			cards().forEach(function (c) {
				var on = c.getAttribute('data-package') === S.pkg;
				c.classList.toggle('is-selected', on);
				c.querySelectorAll('.package__option').forEach(function (o) {
					var picked = on && Number(o.getAttribute('data-option')) === S.opt;
					o.classList.toggle('is-active', picked);
					o.setAttribute('aria-checked', picked ? 'true' : 'false');
				});
				c.querySelectorAll('.package__price-row').forEach(function (r) {
					var active = on && r.getAttribute('data-size') === S.size;
					r.classList.toggle('is-active', active);
					r.setAttribute('aria-pressed', active ? 'true' : 'false');
				});
			});

			// extras: drop/disable the ones the chosen package doesn't offer
			pills().forEach(function (p) {
				var ok = available(p);
				p.disabled = !ok;
				p.classList.toggle('is-unavailable', !ok);
				if (!ok && S.extras.indexOf(p) >= 0) S.extras.splice(S.extras.indexOf(p), 1);
				var on = S.extras.indexOf(p) >= 0;
				p.classList.toggle('is-active', on);
				p.setAttribute('aria-pressed', on ? 'true' : 'false');
			});

			if (!bar) return;
			if (!S.pkg) { bar.hidden = true; return; }

			var names = [], ids = [], total = S.price;
			S.extras.forEach(function (p) {
				names.push(p.getAttribute('data-extra-name'));
				total += Number(p.getAttribute('data-extra-price')) || 0;
				var id = extraIdFor(p);
				if (id) ids.push(id);
			});

			var missing = needsOption() && !S.opt;
			bar.querySelector('[data-bb-title]').textContent = S.name + (S.optText ? ' (' + S.optText + ')' : '') + ' \u00b7 ' + S.size;
			bar.querySelector('[data-bb-extras]').textContent = missing ? 'Choose one option on the card to continue.' : (names.length ? '+ ' + names.join(', ') : '');
			bar.classList.toggle('needs-option', missing);
			bar.querySelector('[data-bb-total]').textContent = money(total);

			var url = new URL(section.getAttribute('data-book-url'), location.href);
			url.searchParams.set('service', section.getAttribute('data-service') || '');
			url.searchParams.set('package', S.pkg);
			url.searchParams.set('size', S.size);
			if (ids.length) url.searchParams.set('extras', ids.join(',')); else url.searchParams.delete('extras');
			if (S.opt) url.searchParams.set('option', String(S.opt)); else url.searchParams.delete('option');
			var link = bar.querySelector('[data-bb-link]');
			link.href = url.toString();
			link.classList.toggle('is-disabled', missing);
			link.setAttribute('aria-disabled', missing ? 'true' : 'false');
			bar.hidden = false;
		}

		section.addEventListener('click', function (e) {
			var row = e.target.closest('.package__price-row');
			var card = e.target.closest('.package[data-package]');
			var pill = e.target.closest('.extra-pill');
			var opt = e.target.closest('.package__option');
			var book = e.target.closest('[data-bb-link]');

			if (book && book.classList.contains('is-disabled')) {
				e.preventDefault();
				var c0 = cards(S.pkg)[0];
				if (c0) c0.scrollIntoView({ behavior: 'smooth', block: 'center' });
				return;
			}
			if (opt && card) {
				if (card.getAttribute('data-package') !== S.pkg) select(card, '', false);
				S.opt = Number(opt.getAttribute('data-option')) || 0;
				S.optText = opt.getAttribute('data-option-text') || '';
				update();
				if (ready()) revealBar();
				return;
			}
			if (row && card && !row.disabled) {
				select(card, row.getAttribute('data-size'), true);
			} else if (card && !e.target.closest('a, button')) {
				select(card, '', false);
			} else if (pill && !pill.disabled) {
				var i = S.extras.indexOf(pill);
				if (i >= 0) S.extras.splice(i, 1); else S.extras.push(pill);
				update();
			}
		});

		update();
	}

	document.querySelectorAll('[data-booking-select]').forEach(init);
})();
