(function () {
	'use strict';

	/* Header: scrolled state */
	var header = document.querySelector('[data-header]');
	if (header) {
		var onScroll = function () { header.classList.toggle('is-scrolled', window.scrollY > 10); };
		onScroll();
		window.addEventListener('scroll', onScroll, { passive: true });
	}

	/* Mobile navigation */
	var toggle = document.querySelector('[data-nav-toggle]');
	var nav = document.querySelector('[data-nav]');
	if (toggle && nav) {
		var setOpen = function (open) {
			toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
			nav.classList.toggle('is-open', open);
			document.body.classList.toggle('nav-open', open);
		};
		toggle.addEventListener('click', function () {
			setOpen(toggle.getAttribute('aria-expanded') !== 'true');
		});
		nav.addEventListener('click', function (e) {
			if (e.target.closest('a')) setOpen(false);
		});
		document.addEventListener('keydown', function (e) {
			if (e.key === 'Escape' && nav.classList.contains('is-open')) { setOpen(false); toggle.focus(); }
		});
		window.addEventListener('resize', function () {
			if (window.innerWidth > 1024) setOpen(false);
		});
	}

	/* Pricing: vehicle size switcher (tabs -> panels) */
	document.querySelectorAll('[data-pricing]').forEach(function (section) {
		var buttons = Array.prototype.slice.call(section.querySelectorAll('.size-switch__btn'));
		var select = function (btn) {
			buttons.forEach(function (b) {
				var on = b === btn;
				b.setAttribute('aria-selected', on ? 'true' : 'false');
				b.tabIndex = on ? 0 : -1;
				var panel = document.getElementById(b.getAttribute('aria-controls'));
				if (panel) {
					panel.hidden = !on;
					if (on) panel.scrollLeft = 0;
				}
			});
		};
		buttons.forEach(function (btn, i) {
			btn.tabIndex = btn.getAttribute('aria-selected') === 'true' ? 0 : -1;
			btn.addEventListener('click', function () { select(btn); });
			btn.addEventListener('keydown', function (e) {
				var dir = e.key === 'ArrowRight' ? 1 : e.key === 'ArrowLeft' ? -1 : 0;
				if (!dir) return;
				e.preventDefault();
				var next = buttons[(i + dir + buttons.length) % buttons.length];
				select(next);
				next.focus();
			});
		});
	});

	/* Package cards: align headings, price lists and features across cards in a row */
	var EQ_PARTS = ['head', 'prices', 'features'];
	function equalize(container) {
		var cards = Array.prototype.slice.call(container.querySelectorAll('.package'));
		if (!cards.length) return;
		EQ_PARTS.forEach(function (part) {
			cards.forEach(function (c) { var el = c.querySelector('[data-eq="' + part + '"]'); if (el) el.style.minHeight = ''; });
		});
		// Carousel: every slide is one row. Grid: group by vertical position.
		var isCarousel = container.classList.contains('slick-initialized');
		var rows = {};
		cards.forEach(function (c) {
			var key = isCarousel ? 0 : Math.round(c.getBoundingClientRect().top + window.scrollY);
			(rows[key] = rows[key] || []).push(c);
		});
		Object.keys(rows).forEach(function (k) {
			var row = rows[k];
			if (row.length < 2) return;
			EQ_PARTS.forEach(function (part) {
				var els = row.map(function (c) { return c.querySelector('[data-eq="' + part + '"]'); }).filter(Boolean);
				var max = 0;
				els.forEach(function (el) { max = Math.max(max, el.offsetHeight); });
				els.forEach(function (el) { el.style.minHeight = max + 'px'; });
			});
		});
	}
	function equalizeAll() {
		document.querySelectorAll('[data-equalize]').forEach(equalize);
	}
	window.dluxEqualize = equalize;
	var eqTimer;
	window.addEventListener('resize', function () { clearTimeout(eqTimer); eqTimer = setTimeout(equalizeAll, 120); });
	window.addEventListener('load', equalizeAll);
	if (document.fonts && document.fonts.ready) document.fonts.ready.then(equalizeAll);
	equalizeAll();
})();
