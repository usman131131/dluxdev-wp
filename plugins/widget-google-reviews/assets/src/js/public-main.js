function rplg_badge_init(el, name, root_class) {
    var btn = el.querySelector('.wp-' + name + '-badge'),
        form = el.querySelector('.wp-' + name + '-form');

    if (!btn || !form) return;

    var wpac = document.createElement('div');
    wpac.className = root_class + ' wpac';

    if (btn.className.indexOf('-fixed') > -1) {
        wpac.appendChild(btn);
    }
    wpac.appendChild(form);
    document.body.appendChild(wpac);

    btn.onclick = function() {
        form.style.display='block';
    };
}

function rplg_next_reviews(name, pagin) {
    var parent = this.parentNode,
        selector = '.' + name + '-review.' + name + '-hide';
        reviews = parent.querySelectorAll(selector);
    for (var i = 0; i < pagin && i < reviews.length; i++) {
        if (reviews[i]) {
            reviews[i].className = reviews[i].className.replace(name + '-hide', ' ');
        }
    }
    reviews = parent.querySelectorAll(selector);
    if (reviews.length < 1) {
        parent.removeChild(this);
    }
    return false;
}

function rplg_leave_review_window() {
    rpi.Utils.popup(this.getAttribute('href'), 620, 500);
    return false;
}

function grw_popup_open(rootEl, popupEl) {
    popupEl.classList.add('grw-popup-on');
    rpi.Lightbox(popupEl).show(true);
    // A wheel over the backdrop would scroll the page under the popup and leave it elsewhere on close.
    const wrap = popupEl.closest('.rpi-lightbox-wrap');
    if (!wrap.grwWheel) {
        wrap.grwWheel = true;
        wrap.addEventListener('wheel', function(e) { if (!e.target.closest('.grw-popup')) e.preventDefault(); }, {passive: false});
        ['wheel', 'pointerdown', 'keydown'].forEach(ev => wrap.addEventListener(ev, function() { wrap.grwFollow = false; }, {passive: true}));
    }
    grw_popup_focus(rootEl, popupEl);
    return false;
}

const grw_clean = s => s.replace(/\s+/g, ' ').trim();

function grw_badge_phrase(rootEl) {
    const feed = rootEl.querySelector('.rpi-badge-feed'), q = feed && feed.querySelector('q');
    // Only the rated fact (author + one star) names a review; the other facts have no home in the list.
    const b = feed && feed.querySelector('.rpi-stars') ? feed.querySelector('b') : null;
    return {text: q ? grw_clean(q.textContent).replace(/…$/, '') : '', name: b ? grw_clean(b.textContent).split(' ')[0] : ''};
}

// Scrolls to the review the badge phrase (or its author fact) came from; without one the list stays where it was left.
function grw_popup_focus(rootEl, popupEl, phrase) {
    const clean = grw_clean, wrap = popupEl.closest('.rpi-lightbox-wrap');
    const {text, name} = phrase || grw_badge_phrase(rootEl);
    const reviews = popupEl.querySelectorAll('.grw-review, .wp-google-review');
    const hit = Array.prototype.find.call(reviews, r => text ? clean(r.textContent).indexOf(text) > -1 : name && clean((r.querySelector('.wp-google-name') || r).textContent).indexOf(name) === 0);
    popupEl.querySelectorAll('mark.grw-review-mark').forEach(function(m) { m.outerHTML = m.innerHTML; });
    if (hit) {
        if (text) grw_popup_mark(hit.querySelector('.wp-google-text') || hit, text);
        const go = function() { popupEl.scrollTo({top: popupEl.scrollTop + hit.getBoundingClientRect().top - popupEl.getBoundingClientRect().top - 16, behavior: 'smooth'}); };
        // Glides from where it was closed after a beat, so the move to the review reads as a move.
        wrap.grwFollow = true;
        setTimeout(function() {
            go();
            // Avatars above the review arrive after the first scroll and push it down; follow them until the visitor scrolls.
            popupEl.querySelectorAll('img').forEach(function(img) { if (!img.complete) img.addEventListener('load', function() { if (wrap.grwFollow) go(); }, {once: true}); });
            hit.classList.add('grw-review-hl');
            setTimeout(function() { hit.classList.remove('grw-review-hl'); }, 2500);
        }, 500);
    }
}

function grw_popup_mark(el, text) {
    const re = new RegExp(text.replace(/[.*+?^${}()|[\]\\]/g, '\\$&').replace(/ /g, '\\s+'));
    const walker = document.createTreeWalker(el, NodeFilter.SHOW_TEXT);
    for (let n; (n = walker.nextNode());) {
        const m = re.exec(n.data);
        if (!m) continue;
        const range = document.createRange();
        range.setStart(n, m.index);
        range.setEnd(n, m.index + m[0].length);
        const mark = document.createElement('mark');
        mark.className = 'grw-review-mark';
        range.surroundContents(mark);
        return;
    }
}

// The badge popup is fetched on the first click: the lightbox opens at once on a skeleton, the reviews take its place.
function grw_badge_popup(rootEl, src, common) {
    let popupEl = null, phrase;
    // Another lightbox opened meanwhile takes the node out of the wrap; it must be left alone then.
    const onScreen = el => {
        const wrap = el.closest('.rpi-lightbox-wrap');
        return wrap && wrap.style.display !== 'none';
    };
    return function() {
        // The badge rotates its phrases, so the one clicked is gone by the time the reviews arrive.
        phrase = grw_badge_phrase(rootEl);
        if (!popupEl) {
            const el = popupEl = document.createElement('div');
            el.className = rootEl.className + ' grw-popup';
            el.innerHTML = '<div class="grw-popup-skel">' + '<div><i></i><div><b></b><b></b><b></b></div></div>'.repeat(5) + '</div>';
            fetch(src, {credentials: 'same-origin'})
                .then(r => r.ok ? r.text() : Promise.reject())
                .then(html => {
                    const t = document.createElement('template');
                    t.innerHTML = html;
                    const list = t.content.querySelector('.grw-popup');
                    if (!list) return Promise.reject();
                    el.innerHTML = list.innerHTML;
                    for (const tm of el.getElementsByClassName('wp-google-time')) common.setTime(tm);
                    if (onScreen(el)) grw_popup_focus(rootEl, el, phrase);
                })
                .catch(() => {
                    if (onScreen(el)) rpi.Lightbox(el).hide();
                    popupEl = null;
                });
        }
        return grw_popup_open(rootEl, popupEl);
    };
}

// A card theme cuts the text at a whole line in CSS. A text longer than the theme's lines shows one line less and "read more" takes the
// last line, so every card keeps the same height; once opened, the whole text scrolls in that same height.
function grw_more(fb, options) {
    const open = () => {
        fb.classList.add('grw-open');
        // Inline: "remove unused CSS" optimizers drop the rules of a class the page only gets on a click.
        fb.style.setProperty('-webkit-line-clamp', 'none', 'important');
    };
    // Without ResizeObserver a text shown later ("More reviews", a tab) would stay cut with no link.
    if (!window.ResizeObserver) return open();
    const links = grw_more.links || grw_more_watch();
    const more = rpi.el({tag: 'span', cl: 'wp-more-toggle grw-more', text: rpi.Utils.__('read more', options.trans)});
    rpi.btn(more);
    more.onclick = () => {
        const byKeys = rpi.keyFocus() === more;
        links.delete(fb);
        grw_more.ro.unobserve(fb);
        fb.style.maxHeight = more.getBoundingClientRect().bottom - fb.getBoundingClientRect().top + 'px';
        fb.style.setProperty('overflow-y', 'auto', 'important');
        more.remove();
        open();
        if (byKeys) rpi.focusOnce(fb);
    };
    links.set(fb, more);
    grw_more.ro.observe(fb);
    grw_more.mo.observe(fb, {childList: true, characterData: true, subtree: true});
}

// A text is measured at the theme's full lines, every box before any link moves. A late web font or text swapped in place (WordPress's
// emoji images, a translator) changes the lines without a resize.
function grw_more_watch() {
    const links = grw_more.links = new Map();
    const check = fbs => {
        fbs = fbs.filter(fb => links.has(fb));
        fbs.forEach(fb => { fb.style.removeProperty('-webkit-line-clamp'); fb.style.removeProperty('min-height'); });
        fbs.map(fb => {
            const s = getComputedStyle(fb), lh = parseFloat(s.lineHeight) || parseFloat(s.fontSize) * 1.2;
            // A cut hides at least a line; letters taller than a tight line height stick out by less than half of one.
            return [fb, fb.isConnected && fb.scrollHeight - fb.clientHeight > lh / 2, parseInt(s.getPropertyValue('--card-text-lines'), 10) || 5, lh];
        }).forEach(([fb, cut, lines, lh]) => {
            const more = links.get(fb);
            if (!fb.isConnected) {
                links.delete(fb);
                grw_more.ro.unobserve(fb);
            } else if (!cut) more.remove();
            else {
                fb.style.setProperty('-webkit-line-clamp', Math.max(1, lines - 1), 'important');
                fb.style.setProperty('min-height', Math.max(1, lines - 1) * lh + 'px', 'important');
                if (!more.isConnected) fb.after(more);
            }
        });
    };
    // A frame later: a box resized inside its own observer's callback is a "ResizeObserver loop" error in WebKit.
    grw_more.ro = new ResizeObserver(es => requestAnimationFrame(() => check(es.map(e => e.target))));
    grw_more.mo = new MutationObserver(ms => check([...new Set(ms.map(m => m.target.nodeType === 1 ? m.target : m.target.parentElement).filter(Boolean)
        .map(n => n.closest('.wp-google-feedback')))]));
    if (document.fonts && document.fonts.addEventListener) document.fonts.addEventListener('loadingdone', () => check([...links.keys()]));
    return links;
}

function grw_init(el, layout) {
    const rootEl = rpi.Utils.getParent(el, 'wp-gr');

    if (rootEl.getAttribute('data-exec') == 'true') return;
    else rootEl.setAttribute('data-exec', 'true');

    // A host's wpautop wraps card lines in <p>, and the card's CSS addresses each line as a child of its row or box.
    rootEl.querySelectorAll('.rpi-card p').forEach(p => p.replaceWith(...p.childNodes));

    const options = JSON.parse(rootEl.getAttribute('data-options'));

    const common = rpi.Common(rootEl, options, {
        time     : 'wp-google-time',
        text     : 'wp-google-text',
        readmore : 'wp-more-toggle'
    });
    common.init();
    rootEl.querySelectorAll('.rpi-card .wp-google-feedback').forEach(fb => grw_more(fb, options));

    const media = rpi.Media(rootEl, {}, {
        root : 'wp-gr',
        card : 'grw-review'
    }).init();

    if (layout == 'badge' && rpi.Badge) {
        const src = rootEl.dataset.reviews;
        rpi.Badge(rootEl, {onclick: src && rpi.Lightbox && rootEl.querySelector('.rpi-badge-clickable') ? grw_badge_popup(rootEl, src, common) : null});
        return;
    }

    const popupEl = rootEl.querySelector('.grw-popup');
    const open = () => grw_popup_open(rootEl, popupEl);

    if (popupEl && rpi.Lightbox) {

        const headerEls = rootEl.getElementsByClassName('grw-header-inner');
        for (let i = 0; i < headerEls.length; i++) {
            headerEls[i].setAttribute('role', 'button');
            headerEls[i].setAttribute('tabindex', '0');
            headerEls[i].onclick = function(e) {
                if (e.target.closest('a')) return;
                open();
            };
            headerEls[i].onkeydown = function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    open();
                }
            };
        }
    }

    if (rootEl.getElementsByClassName('grw-review')[0] && (layout == 'slider' || layout == 'grid')) {
        // Init Slider or Grid
        const row = rootEl.getElementsByClassName('grw-row')[0];
        const opt = JSON.parse(row.getAttribute('data-options'));
        const column = rpi.Column(rootEl, opt, {
            cnt      : 'grw-row',
            col      : 'grw-row',
            card     : 'grw-review'
        });
        const slider = rpi.Slider(rootEl, opt, {
            cnt      : 'grw-row',
            col      : 'grw-row',
            content  : 'grw-content',
            cards    : 'grw-reviews',
            card     : 'grw-review',
            text     : 'wp-google-text',
            btnPrev  : 'grw-prev',
            btnNext  : 'grw-next',
            dotsWrap : 'rpi-dots-wrap',
            dots     : 'rpi-dots',
            dot      : 'rpi-dot'
        }, {
            column: column
        });
        slider.init();
    }
}

function grw_boot() {
    const els = document.querySelectorAll('.wp-gr[data-exec="false"]');
    for (let i = 0; i < els.length; i++) {
        (function(elem) {
            // A root inside another widget came from review content, not from the plugin.
            if (elem.parentElement && elem.parentElement.closest('.wp-gr')) return;
            grw_init(elem, elem.getAttribute('data-layout'));
        })(els[i]);
    }
}

if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', grw_boot);
else grw_boot();
// Page builders' editors (a framed preview, or Beaver Builder's in the page itself) put a widget in after the page has loaded.
if (window.self !== window.top || /[?&]fl_builder\b/.test(location.search)) new MutationObserver(grw_boot).observe(document.documentElement, {childList: true, subtree: true});