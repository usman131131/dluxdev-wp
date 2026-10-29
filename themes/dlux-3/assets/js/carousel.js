/* Beyond the wash, Detailing packages + Our work gallery: carousels (Slick) – infinite loop, 4 visible, 1 slide per arrow click. */
(function ($) {
	'use strict';
	if (!$ || !$.fn.slick) return;

	var MAX_VISIBLE = 4;

	$(function () {
		$('[data-carousel]').each(function () {
			var $track = $(this);
			var $wrap = $track.closest('.services');
			var $cards = $track.children();

			if (!$cards.length) return;

			// Slick only loops when there are more cards than are visible.
			// With 4 or fewer, add copies of the set until there's at least one extra.
			if ($cards.length <= MAX_VISIBLE) {
				while ($track.children().length <= MAX_VISIBLE) {
					$cards.clone().addClass('is-duplicate').appendTo($track);
				}
			}

			// Keep card sections aligned whenever Slick lays out (init, resize, breakpoint).
			$track.on('init setPosition', function () {
				if (window.dluxEqualize) window.dluxEqualize($track[0]);
			});

			$track.slick({
				slidesToShow: MAX_VISIBLE,
				slidesToScroll: 1,
				infinite: true,
				rows: 0,
				speed: 400,
				arrows: true,
				dots: false,
				prevArrow: $wrap.find('[data-carousel-prev]'),
				nextArrow: $wrap.find('[data-carousel-next]'),
				responsive: [
					{ breakpoint: 1181, settings: { slidesToShow: 3 } },
					{ breakpoint: 961, settings: { slidesToShow: 2 } },
					{ breakpoint: 761, settings: { slidesToShow: 1 } }
				]
			});
		});

		// Detailing packages: same behaviour (4 visible, 1 per click, infinite loop).
		$('[data-dpk-carousel]').each(function () {
			var $track = $(this);
			var $wrap = $track.closest('.dpk__slider');
			var $cards = $track.children();
			if ($cards.length < 2) return;

			if ($cards.length <= MAX_VISIBLE) {
				while ($track.children().length <= MAX_VISIBLE) {
					$cards.clone().addClass('is-duplicate').attr('tabindex', '-1').appendTo($track);
				}
			}

			$track.on('init', function () {
				var root = $track.closest('[data-dpk]')[0];
				if (root) root.dispatchEvent(new CustomEvent('dlux:dpk-cloned'));
			});

			$track.slick({
				slidesToShow: MAX_VISIBLE,
				slidesToScroll: 1,
				infinite: true,
				rows: 0,
				speed: 400,
				arrows: true,
				dots: false,
				prevArrow: $wrap.find('[data-dpk-prev]'),
				nextArrow: $wrap.find('[data-dpk-next]'),
				responsive: [
					{ breakpoint: 1181, settings: { slidesToShow: 3 } },
					{ breakpoint: 961, settings: { slidesToShow: 2 } },
					{ breakpoint: 641, settings: { slidesToShow: 1 } }
				]
			});
		});

		// Our work (before / after gallery): 4 visible, 1 per click, infinite loop.
		$('[data-work-carousel]').each(function () {
			var $track = $(this);
			var $wrap = $track.closest('.work__slider');
			var $items = $track.children();
			if ($items.length < 2) return;

			if ($items.length <= MAX_VISIBLE) {
				while ($track.children().length <= MAX_VISIBLE) {
					$items.clone().addClass('is-duplicate').attr('aria-hidden', 'true').appendTo($track);
				}
			}

			$track.slick({
				slidesToShow: MAX_VISIBLE,
				slidesToScroll: 1,
				infinite: true,
				rows: 0,
				speed: 400,
				arrows: true,
				dots: false,
				prevArrow: $wrap.find('[data-work-prev]'),
				nextArrow: $wrap.find('[data-work-next]'),
				responsive: [
					{ breakpoint: 1025, settings: { slidesToShow: 3 } },
					{ breakpoint: 769, settings: { slidesToShow: 2 } },
					{ breakpoint: 481, settings: { slidesToShow: 1 } }
				]
			});
		});
	});
})(window.jQuery);
