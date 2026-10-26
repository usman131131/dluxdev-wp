// The badge part of a step page, drawn by the site's rpi.Badge (badge.js), so the preview is the production render.
// data() gives the badge preview data, which the gallery loads next to its own.
function grw_badge_kit(W, data) {
    const state = W.state, h = W.h, esc = W.esc;

    const DEFAULTS = {badge_pos: 'right', badge_tone: 'light', badge_icon: 'medal', badge_click: 'sidebar'};
    const TOP_RATED_NOTE = 'Top rated appears from a 4.5 rating and 10 reviews';
    const PRESETS = [
        {name: 'Classic',        opts: {}},
        {name: 'Square corners', opts: {style_vars: '--badge-radius:3px;', badge_dot: true}},
        {name: 'Pill',           opts: {style_vars: '--badge-radius:999px;'}},
        {name: 'Top rated',      opts: {badge_dot: true, badge_toprated: true}},
        {name: 'Glass',          opts: {badge_tone: 'glass', badge_dot: true}},
        {name: 'Stars first',    opts: {badge_order: 'stars'}},
        {name: 'Compact',        opts: {badge_compact: true, badge_dot: true, badge_width: 'auto'}},
        {name: 'Compact, square corners', opts: {badge_compact: true, badge_width: 'auto', style_vars: '--badge-radius:3px;'}},
        {name: 'One star',       opts: {badge_onestar: true, badge_compact: true, badge_width: 250}},
        {name: 'One star first', opts: {badge_onestar: true, badge_order: 'stars', badge_compact: true, badge_dot: true, badge_width: 250}},
        {name: 'One star first, top rated', opts: {badge_onestar: true, badge_order: 'stars', badge_compact: true, badge_dot: true, badge_toprated: true, badge_width: 'auto'}},
        {name: 'Bar',            opts: {badge_bar: true, badge_pos: 'top', badge_dot: true, badge_toprated: true}},
        {name: 'Bar, plain',     opts: {badge_bar: true, badge_pos: 'top'}},
        {name: 'Bar, one star',  opts: {badge_bar: true, badge_pos: 'top', badge_onestar: true}},
        {name: 'Bar, one star, top rated', opts: {badge_bar: true, badge_pos: 'top', badge_onestar: true, badge_dot: true, badge_toprated: true}}
    ];

    // Each host gets its own root id: badge.js keys its close state by it.
    let seq = 0;
    function draw(host, opts, fixed, click) {
        host.querySelectorAll('.rpi-badge').forEach(b => { if (b.rpiLiveStop) b.rpiLiveStop(); });
        host.innerHTML = '';
        const id = 'grwb' + (++seq);
        Object.keys(sessionStorage).filter(k => k.indexOf('rpi_badge_grwb') === 0).forEach(k => sessionStorage.removeItem(k));
        const r = h('div', {class: 'wp-gr rpi grw-badge-root' + (fixed ? '' : ' grw-badge-static'), 'data-id': id});
        if (opts.style_vars) r.style.cssText = opts.style_vars;
        host.append(r);
        rpi.Badge(r, Object.assign({}, DEFAULTS, opts, {badge_style: 'live', data: data(), onclick: click || function() {}}));
        return r;
    }

    /* ------------------------------------------------------------ step 1: presets */

    // shared: the gallery sets star color, dark and anonymized photos for all tiles at once, so the tiles have no controls of their own.
    function renderPresets(host, head, shared) {
        const wrap = h('div', {class: 'grw-badge-design'});
        const note = h('p', {class: 'description'}, '<span class="dashicons dashicons-info-outline grw-badge-note-ico"></span><b>Sample data.</b> <a href="#" class="grw-badge-find">Find your place</a> above to see the badges with your real rating and reviews.');
        const noteText = () => { note.hidden = !data().sample; };
        noteText();
        // The gallery shows one note for all its sections and passes no head.
        if (head !== null) (head || wrap).append(note);
        const grid = h('div', {class: 'grw-badge-grid'});
        W.colors();
        wrap.append(grid);
        host.append(wrap);
        // Below the label's threshold (badge.js: 4.5 and 10 reviews) the card still previews it, only its button locks.
        const eligible = () => rpi.Badge.Live.topRated(rpi.Badge.Live.biz({data: data()}), {badge_toprated: true});
        const cards = [];
        PRESETS.forEach(preset => {
            const card = h('div', {class: 'grw-badge-card'});
            const head = h('div', {class: 'grw-badge-card-head'}, '<span>' + esc(preset.name) + '</span>');
            const tools = h('div', {class: 'grw-badge-card-tools'});
            const dark = h('label', {class: 'grw-badge-switch', title: 'Dark tone'});
            const cb = h('input', {type: 'checkbox'});
            dark.append(cb, h('i'), document.createTextNode('Dark'));
            const color = h('input', {type: 'color', class: 'grw-badge-color', title: 'Star color', value: '#fbbc04', list: 'grw-badge-colors'});
            const tone = () => cb.checked ? (preset.opts.badge_tone === 'glass' ? 'glass-dark' : 'dark') : (preset.opts.badge_tone || 'light');
            const starColor = () => color.value.toLowerCase() === '#fbbc04' ? '' : color.value;
            const opts = () => {
                const o = Object.assign({}, preset.opts, {badge_tone: tone()});
                if (starColor()) o.style_vars = (o.style_vars || '') + '--badge-star-color:' + starColor() + ';';
                if (shared && shared.anonym) o.anonym_avatar = true;
                return o;
            };
            const chosen = () => state.presetName === preset.name && !!state.options;
            const memo = state.cards[preset.name] || (state.cards[preset.name] = {dark: false, color: '#fbbc04'});
            if (chosen()) {
                memo.dark = /dark/.test(state.options.badge_tone || '');
                memo.color = W.styleVar('--badge-star-color') || '#fbbc04';
            }
            cb.checked = shared ? shared.dark : memo.dark;
            color.value = shared ? shared.color || '#fbbc04' : memo.color;
            const changed = () => {
                memo.dark = cb.checked;
                memo.color = color.value;
                if (chosen()) {
                    [state.options, state.preset].forEach(o => { o.badge_tone = tone(); W.setStyleVar('--badge-star-color', starColor(), o); });
                }
                show();
            };
            color.oninput = changed;
            const stage = h('div', {class: 'grw-badge-stage' + (preset.opts.badge_bar ? ' grw-badge-stage-bar' : '') + (preset.opts.badge_tone === 'glass' ? ' grw-badge-stage-glass' : ''), inert: ''});
            const show = () => draw(stage, Object.assign({preview_toprated: true}, opts()), false);
            cb.onchange = changed;
            const btn = h('button', {type: 'button', class: 'button button-primary'}, chosen() ? 'Selected' : 'Select');
            card.classList.toggle('grw-badge-card-sel', chosen());
            const lock = h('p', {class: 'grw-badge-card-lock', hidden: true}, '<span class="dashicons dashicons-info-outline grw-badge-note-ico"></span>' + TOP_RATED_NOTE);
            const gate = () => {
                const off = !!preset.opts.badge_toprated && !eligible();
                btn.disabled = off;
                btn.title = off ? TOP_RATED_NOTE : '';
                lock.hidden = !off;
            };
            gate();
            // A redraw follows the controls above the gallery but leaves the chosen badge's options alone: step 2 may have changed them.
            cards.push(() => {
                if (shared) { cb.checked = memo.dark = shared.dark; color.value = memo.color = shared.color || '#fbbc04'; }
                show();
                gate();
            });
            btn.onclick = () => {
                if (!chosen()) {
                    // When the saved widget is not a badge the badge is saved as a widget of its own (Plugin_Badge::save): not on the site yet.
                    const saved = W.savedOptions();
                    if (saved && /^(slider|grid|list|rating)$/.test(saved.view_mode || '')) state.global = false;
                    state.options = Object.assign({}, DEFAULTS, opts());
                    state.preset = Object.assign({}, state.options);
                    state.presetName = preset.name;
                }
                W.go(2);
            };
            tools.append(...(shared ? [] : [color, dark]), btn);
            head.append(tools);
            card.append(head, stage, lock);
            grid.append(card);
            show();
        });
        // While a place connects there is no data; the tiles are drawn again once it comes.
        return {refresh() { if (!data()) return; noteText(); cards.forEach(f => f()); }};
    }

    /* ------------------------------------------------------------ step 2: editor */

    // The badge on a stage that stands for the page: in its corner or in the content, as the site shows it.
    function preview(stage) {
        // A place that finishes connecting reloads the data; the next draw follows it.
        if (!data()) return;
        const embed = state.options.badge_pos === 'embed';
        stage.classList.toggle('grw-badge-stage-float', !embed);
        stage.classList.toggle('grw-badge-stage-edge', embed && !!state.options.badge_bar);
        stage.classList.toggle('grw-badge-stage-glass', /^glass/.test(state.options.badge_tone || ''));
        // The lightbox moves the popup out of the root on the first open, so it is captured once here.
        const popup = (state.options.anonym_avatar ? data().popup_anonym : data().popup) || '';
        let popupEl = null;
        const root = draw(stage, state.options, !embed, popup ? () => grw_popup_open(root, popupEl) : null);
        root.insertAdjacentHTML('beforeend', popup);
        popupEl = root.querySelector('.grw-popup');
    }

    function renderEditor(ctx) {
        const o = state.options, ui = ctx.ui, stage = ctx.stage;
        const drawLive = () => preview(stage);

        const POS_PILL = [['right', '↘ Bottom right'], ['left', '↙ Bottom left'], ['top-right', '↗ Top right'], ['top-left', '↖ Top left'], ['embed', '▭ Embed']];
        const POS_BAR = [['top', '▔ Top'], ['bottom', '▁ Bottom'], ['embed', '▬ Embed']];

        /* ---- 1. Position ---- */
        const b1 = ui.card('Position', true);
        const posRows = h('div', {class: 'grw-pl-pos'});
        const posButtons = [];
        const posRow = (title, items, bar) => {
            const g = h('div', {class: 'grw-pl-seg grw-pl-seg-grid'});
            items.forEach(([v, t]) => {
                const b = h('button', {type: 'button'}, esc(t));
                b.onclick = () => { o.badge_pos = v; o.badge_bar = bar; posButtons.forEach(x => x.el.classList.toggle('grw-pl-on', x.bar === bar && x.v === v)); drawLive(); };
                posButtons.push({el: b, v, bar});
                g.append(b);
            });
            posRows.append(h('div', {class: 'grw-pl-sub'}, title), g);
        };
        posRow('Badge', POS_PILL, false);
        posRow('Bar', POS_BAR, true);
        // A bar in a corner is drawn at that edge, so its button lights up the edge it collapses to.
        const curBar = !!o.badge_bar, curPos = o.badge_pos || 'right';
        const curV = curBar && curPos !== 'embed' ? (/^top/.test(curPos) ? 'top' : 'bottom') : curPos;
        posButtons.forEach(x => x.el.classList.toggle('grw-pl-on', x.bar === curBar && x.v === curV));
        b1.append(posRows);
        b1.append(ui.toggle('hide_float_badge', 'Hide on mobile'));

        /* ---- 2. Look ---- */
        const b2 = ui.card('Look', true);
        b2.append(...ui.row('Tone', ui.seg('badge_tone', [['light', 'Light'], ['dark', 'Dark'], ['glass', 'Glass'], ['glass-dark', 'Glass dark']])));
        b2.append(...ui.row('Star color', ui.color(W.styleVar('--badge-star-color') || '#fbbc04', 'Star color', v => { W.setStyleVar('--badge-star-color', v.toLowerCase() === '#fbbc04' ? '' : v); drawLive(); })));
        b2.append(ui.slider('Corners', 0, 30, 1, parseInt(W.styleVar('--badge-radius'), 10) || 14, 'px', v => { W.setStyleVar('--badge-radius', v == 14 ? '' : v + 'px'); drawLive(); }));
        b2.append(ui.slider('Opacity', 40, 100, 5, Math.round((parseFloat(W.styleVar('--badge-opacity')) || 1) * 100), '%', v => { W.setStyleVar('--badge-opacity', v == 100 ? '' : String(v / 100)); drawLive(); }));

        /* ---- 3. Size ---- */
        const b3 = ui.card('Size', false);
        b3.append(ui.slider('Text size', 11, 20, 0.5, o.badge_size || 13, 'px', v => { o.badge_size = v == 13 ? '' : v; drawLive(); }));
        const widthSlider = ui.slider('Width', 200, 480, 5, o.badge_width && o.badge_width !== 'auto' ? o.badge_width : 320, 'px', v => { o.badge_width = v == 320 ? '' : v; drawLive(); });
        widthSlider.hidden = o.badge_width === 'auto';
        b3.append(ui.toggle('badge_width', 'Fit the first line', {get: () => o.badge_width === 'auto', set: v => { o.badge_width = v ? 'auto' : ''; widthSlider.hidden = v; }}));
        b3.append(widthSlider);

        /* ---- 4. Content ---- */
        const b4 = ui.card('Content', false);
        b4.append(ui.toggle('badge_compact', 'Logo on the rating line'));
        b4.append(ui.toggle('badge_onestar', 'One star instead of five'));
        b4.append(ui.toggle('badge_order', 'Stars before the rating', {get: () => o.badge_order === 'stars', set: v => { o.badge_order = v ? 'stars' : ''; }}));
        b4.append(ui.toggle('badge_dot', 'Count after a dot, not in brackets'));
        b4.append(ui.toggle('badge_author_hide', 'Hide review authors'));
        b4.append(ui.toggle('anonym_avatar', 'Anonymize profile photos'));
        const eligible = rpi.Badge.Live.topRated(rpi.Badge.Live.biz({data: data()}), {badge_toprated: true});
        // Shown off below the threshold but left as saved: the badge draws the label by itself once the place qualifies.
        b4.append(ui.toggle('badge_toprated', 'Top rated label', {disabled: !eligible, title: eligible ? '' : TOP_RATED_NOTE, get: () => eligible && !!o.badge_toprated}));
        if (!eligible) b4.append(h('div', {class: 'grw-pl-hint'}, TOP_RATED_NOTE + '.'));
        b4.append(...ui.row('Label icon', ui.select('badge_icon', [['', 'None'], ['medal', 'Medal'], ['check', 'Check'], ['award', 'Award'], ['rosette', 'Rosette']])));

        /* ---- 5. Behaviour ---- */
        const b5 = ui.card('Behaviour', false);
        b5.append(ui.slider('Phrase change', 3, 15, 1, o.badge_interval || 6, 's', v => { o.badge_interval = v == 6 ? '' : v; drawLive(); }));
        b5.append(ui.toggle('badge_close', 'Close button'));

        return drawLive;
    }

    /* ------------------------------------------------------------ step 3: publish */

    function renderPublish(row) {
        const embed = (state.options || {}).badge_pos === 'embed';
        if (!embed) {
            const label = on => on ? 'Displayed on the site' : 'Display on the site';
            const btn = h('button', {type: 'button', class: 'button button-primary button-hero'}, label(state.global));
            btn.disabled = state.global || state.saving;
            const remove = h('a', {href: '#', class: 'grw-badge-uninstall'}, 'Remove from the site');
            remove.hidden = !state.global || state.saving;
            const set = on => {
                const id = state.feedId;
                btn.disabled = true;
                W.save({global: on ? '1' : '0'}, res => {
                    btn.disabled = state.global;
                    if (res.status !== 'success') return W.toast(W.errorOf(res) || 'Could not save.', 'error');
                    const now = !!res.result.global;
                    state.global = now;
                    // A badge deleted or made a slider elsewhere is saved as a new one, and only its own switch puts that one on the site.
                    if (state.step === 3 && (state.feedId !== id || !btn.isConnected)) W.go(3);
                    if (state.feedId !== id) {
                        return W.toast('This badge was deleted or changed elsewhere, so it was saved as a new badge. It is not displayed on the site yet.', 'error');
                    }
                    btn.textContent = label(now);
                    btn.disabled = now;
                    remove.hidden = !now;
                    if (now) W.toast('The badge is now displayed on the site.' + (res.result.replaced ? ' It replaced badge #' + res.result.replaced + '.' : ''), 'success', {label: 'View site', onClick: () => window.open(W.V.home)});
                    else if (!on) W.toast('The badge is no longer displayed on the site.');
                });
            };
            btn.onclick = () => set(true);
            remove.onclick = e => { e.preventDefault(); set(false); };
            row.append(btn, remove);
        }
        return embed ? 'Place the badge in the page content with the shortcode ' : 'Need it on one page only? Put it there with the shortcode ';
    }

    function init() {
        state.global = state.feedId ? !!W.V.global : false;
        if (state.options) {
            // Saved options come back as strings (badge_width "250"), the presets hold numbers.
            const fit = PRESETS.filter(p => Object.keys(p.opts).every(k => String(state.options[k]) === String(p.opts[k])));
            fit.sort((x, y) => Object.keys(y.opts).length - Object.keys(x.opts).length);
            state.presetName = fit.length ? fit[0].name : null;
        }
    }

    return {
        DEFAULTS,
        init,
        payload: () => ({global: state.global ? '1' : '0'}),
        presets: renderPresets,
        editor: renderEditor,
        preview,
        publish: renderPublish
    };
}

function grw_badge_page() {
    const root = document.getElementById('grw-badge');
    if (!root || !window.rpi || !rpi.Badge) return;
    let kit;
    grw_steps(root, {
        noun: 'badge',
        pick: 'Select layout',
        previewTitle: 'Badge preview',
        data: 'grw_badge_data',
        save: 'grw_badge_save',
        defaults: {badge_pos: 'right', badge_tone: 'light', badge_icon: 'medal', badge_click: 'sidebar'},
        init(W) { kit = grw_badge_kit(W, () => W.state.data); kit.init(); },
        payload: () => kit.payload(),
        presets: (host, head) => kit.presets(host, head),
        editor: ctx => kit.editor(ctx),
        publish: row => kit.publish(row)
    });
}

// badge.js (rpi.Badge) is loaded after this file in both bundles, so wait for the DOM.
if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', grw_badge_page);
else grw_badge_page();
