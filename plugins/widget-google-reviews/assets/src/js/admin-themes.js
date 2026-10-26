// Create Widget: every card preset as a slider and as a grid, a list, a rating, then the badges (grw_badge_kit), on one step page.
// Slider and grid previews are rendered by PHP's View, so they are the production markup; the card skin is painted over them live.
function grw_themes_page() {
    const root = document.getElementById('grw-themes');
    if (!root || !window.rpi || !rpi.Badge) return;

    const V0 = JSON.parse(root.dataset.vars), C = V0.cards;
    const LAYOUTS = [['slider', 'Slider'], ['grid', 'Grid']];
    // Layouts without cards: one look each.
    const PLAIN = [['list', 'List'], ['rating', 'Rating']];
    const KINDS = LAYOUTS.concat(PLAIN);
    const hasCards = mode => LAYOUTS.some(([k]) => k === mode);
    const THEME_DEFAULTS = {view_mode: 'slider', card_style: 'c1'};
    const BADGE_DEFAULTS = {badge_pos: 'right', badge_tone: 'light', badge_icon: 'medal', badge_click: 'sidebar'};
    // Each preset comes with a header: the widget's own header options and header variables, so step 2 and the builder edit it as usual.
    const RATING = {header_hide_photo: true, header_hide_name: true, header_show_scale: true, header_platform: true, header_bold_count: true};
    const COLUMN = Object.assign({header_center: true, header_hide_grade: true, hide_writereview: true, header_order: 'scale,stars,based,platform'}, RATING);
    const COLUMN_VARS = '--head-scale-fs:24px;--head-star-size:36px;--head-logo-size:40px;';
    const LINE = Object.assign({header_center: true, hide_writereview: true, header_order: 'platform+stars+based'}, RATING, {header_show_scale: false});
    const LINE_VARS = '--head-star-size:18px;--head-logo-size:20px;';
    const HEADERS = {
        bar:      {opts: Object.assign({slider_head_pos: '1', header_short_count: true, header_order: 'platform+scale+stars+based'}, RATING),
                   vars: '--slider-head-card-bg:#ffffff;--slider-head-card-shadow:0 0 0 1px rgba(0, 0, 0, .08), 0 1px 2px rgba(0, 0, 0, .04);--slider-head-card-radius:12px;--head-scale-fs:15px;--head-star-size:21px;--head-logo-size:28px;--btn-radius:5px;'},
        column:   {opts: Object.assign({slider_head_pos: '2'}, COLUMN), vars: COLUMN_VARS},
        top:      {opts: Object.assign({slider_head_pos: '1'}, COLUMN), vars: COLUMN_VARS},
        line:     {opts: Object.assign({slider_head_pos: '3'}, LINE), vars: LINE_VARS},
        lineUp:   {opts: Object.assign({slider_head_pos: '1'}, LINE), vars: LINE_VARS},
        classic:  {opts: {}, vars: ''},
        noname:   {opts: {header_hide_name: true}, vars: ''},
        nonameUp: {opts: {header_hide_name: true, slider_head_pos: '1'}, vars: ''},
        above:    {opts: {slider_head_pos: '1'}, vars: ''},
        none:     {opts: {header_hide_social: true}, vars: ''}
    };
    // A header that contrasts with the card: no gray plate over gray cards, nothing above a photo on the card's top border.
    const PAIRS = {
        c1: ['column', 'noname'],   r1: ['bar', 'top'],       r9: ['classic', 'nonameUp'],  r6: ['line', 'lineUp'],
        r11: ['nonameUp', 'bar'],   r12: ['lineUp', 'line'],  r15: ['bar', 'classic'],      c4: ['above', 'none'],
        c3: ['column', 'top'],      c5: ['line', 'none'],     r3: ['top', 'column'],        r7: ['classic', 'line'],
        r5: ['none', 'above'],      r8: ['top', 'bar'],       c2: ['nonameUp', 'classic'],  r10: ['bar', 'column'],
        r13: ['above', 'nonameUp'], r2: ['noname', 'line'],   r14: ['column', 'noname'],    r4: ['none', 'classic']
    };
    const THEME_OPTS = {'slider:c5': {slider_hide_dots: true}, 'slider:r6': {slider_hide_dots: true}};
    // The gallery's own order; the builder lists the presets as Card_Presets defines them.
    const ORDER = ['c1', 'r10', 'r7', 'c3', 'r2', 'r8', 'r5', 'r1', 'c5', 'r3', 'c4', 'r12', 'r15', 'r4', 'c2', 'r14', 'r9', 'r13', 'r6', 'r11'];
    const headerOf = (id, layout) => {
        const hd = HEADERS[(PAIRS[id] || ['bar', 'bar'])[layout === 'grid' ? 1 : 0]];
        return {vars: hd.vars, opts: Object.assign({}, hd.opts, THEME_OPTS[layout + ':' + id])};
    };
    // The bar's plate goes dark with the cards: its text turns light under "Dark background".
    const PLATE = '#ffffff';
    const plate = (sv, dark) => sv.replace(/--slider-head-card-bg:[^;]+/, '--slider-head-card-bg:' + (dark ? DARK_CARD : PLATE));
    // Header variables and anything else in style_vars that is not a card variable: kept when the card skin is written again.
    const extraVars = sv => (sv || '').split(';').map(s => s.trim()).filter(s => s && C.vars.indexOf(s.split(':')[0].trim()) < 0).map(s => s + ';').join('');

    const asked = (V0.feedId && V0.options.view_mode) || new URLSearchParams(location.search).get('kind');
    let W, state, h, esc, kit, filter = KINDS.concat([['badge']]).some(([k]) => k === asked) ? asked : 'all';

    const isTheme = o => !!o && KINDS.some(([k]) => k === o.view_mode);
    const theme = () => isTheme(state ? state.options : V0.feedId ? V0.options : null);

    /* ------------------------------------------------------------ card skin */

    const base = id => { const p = C.presets[id] || C.presets.c1; return Object.assign({}, C.skin, p.ink ? C.ink : {}, p.skin || {}); };
    // The skin of the options: the preset's, with the changes style_vars keeps. The name colour is kept there as the builder's shared control keeps it.
    const skinOf = o => {
        const s = grw_card_read(o.style_vars || '', base(o.card_style), C.shadows), name = grw_card_pairs(o.style_vars || '')['--name-color'];
        if (name) s.name = name;
        return s;
    };
    // style_vars as the builder saves it: only the card variables that differ from the preset.
    const styleVars = (id, s) => {
        const b = grw_card_vars(base(id), C.shadows), v = grw_card_vars(s, C.shadows);
        return Object.keys(v).filter(n => v[n] !== b[n]).map(n => n + ':' + v[n] + ';').join('');
    };
    // The CSS variables field reaches the root as !important declarations rendered by PHP, and they must stay.
    const setVar = (r, n, v) => { if (!r.style.getPropertyPriority(n)) v ? r.style.setProperty(n, v) : r.style.removeProperty(n); };
    const paint = (host, s) => {
        const r = host.querySelector('.wp-gr');
        if (!r) return;
        const v = grw_card_vars(s, C.shadows);
        Object.keys(v).forEach(n => setVar(r, n, v[n]));
    };

    // innerHTML does not run the widget's inline script (it sizes the row), so it is put in anew; then the site script starts the widget.
    function mount(host, html, ready) {
        host.innerHTML = html;
        if (ready) ready();
        host.querySelectorAll('script').forEach(s => { const n = document.createElement('script'); n.textContent = s.textContent; s.replaceWith(n); });
        host.querySelectorAll('.wp-gr[data-exec="false"]').forEach(r => grw_init(r, r.dataset.layout));
    }

    /* ------------------------------------------------------------ step 1 */

    // "Dark background" makes the widget's text light (public-main.css .wp-dark), so a light card goes dark with it: a solid
    // dark fill that holds on any site, light lines. A card that is dark already stays.
    const DARK_CARD = '#383e45';
    const DARK = {borderColor: '#4c535b', band: '#41474e', divider: '#4c535b', name: '#eeeeee', text: '#ececec', date: '#bbbbbb'};
    const lum = hex => { const n = parseInt(String(hex).slice(1, 7), 16); return ((n >> 16) * 299 + (n >> 8 & 255) * 587 + (n & 255) * 114) / 255000; };
    // Only the preset's own near-black stars turn light: a colour the user chose stays.
    const darkOf = (s, id) => s.bg === 'color' && lum(s.bg1) < .3 ? s : Object.assign({}, s, DARK, s.bg === 'none' ? {} : {bg: 'color', bg1: DARK_CARD}, s.stars === base(id).stars && lum(s.stars) < .3 ? {stars: DARK.name} : {});
    // Back from dark: the fill and lines of the preset, the rest as changed.
    const lightOf = (s, id) => { const b = base(id); return Object.assign({}, s, ...['bg', 'bg1', 'bg2'].concat(Object.keys(DARK), s.stars === DARK.name && lum(b.stars) < .3 ? ['stars'] : []).map(k => ({[k]: b[k]}))); };

    const gallerySkin = id => {
        const s = state.shared.dark ? darkOf(base(id), id) : base(id);
        if (state.shared.color) s.stars = state.shared.color;
        return s;
    };

    // The look over the server's render: star colour and dark come from the controls above the gallery.
    function look(stage) {
        const r = stage.querySelector('.wp-gr'), sh = state.shared;
        stage.classList.toggle('grw-themes-dark', sh.dark);
        if (!r) return;
        r.classList.toggle('wp-dark', sh.dark);
        if (stage.grwId) paint(stage, gallerySkin(stage.grwId));
        setVar(r, '--star-color', sh.color);
        if (r.style.getPropertyValue('--slider-head-card-bg')) setVar(r, '--slider-head-card-bg', sh.dark ? DARK_CARD : PLATE);
    }

    // A grid tile shows whole rows: two, then two more on each "More reviews", as many cards a row as its width holds.
    function twoRows(stage) {
        const cards = [...stage.querySelectorAll('.grw-review')], more = stage.querySelector('.grw-content > .wp-google-url');
        if (!cards.length || !more) return;
        let rows = 2;
        const fit = () => {
            const n = rows * (parseInt(getComputedStyle(cards[0]).getPropertyValue('--col'), 10) || 1);
            cards.forEach((c, i) => c.classList.toggle('grw-hide', i >= n));
            more.style.display = n >= cards.length ? 'none' : '';
        };
        more.onclick = () => { rows += 2; fit(); return false; };
        // --col follows the width class the site script puts on the row a frame or more after a resize.
        new MutationObserver(fit).observe(stage.querySelector('.grw-row'), {attributeFilter: ['class']});
        fit();
    }

    function renderPresets(host, head) {
        const sh = state.shared;
        const filters = h('div', {class: 'grw-pl-seg grw-themes-filter', role: 'group', 'aria-label': 'Layout'});
        const tools = h('div', {class: 'grw-themes-tools'});
        // The first colour of the palette stands for each theme's own stars, as on the badges.
        const stars = h('span', {class: 'grw-themes-stars'});
        const color = h('input', {type: 'color', class: 'grw-badge-color', list: 'grw-badge-colors', 'aria-label': 'Stars color', title: 'Stars color'});
        color.value = sh.color || '#fbbc04';
        // Dragging in the picker fires "input" many times a frame: meanwhile only the star variables move, once a frame;
        // "change" (the picker released or closed) redraws the badges and the rest.
        let frame = 0;
        color.oninput = () => {
            sh.color = color.value.toLowerCase() === '#fbbc04' ? null : color.value;
            if (frame) return;
            frame = requestAnimationFrame(() => {
                frame = 0;
                const c = sh.color || '';
                stages.forEach(st => {
                    const r = st.querySelector('.wp-gr');
                    if (!r) return;
                    if (st.grwId) setVar(r, '--card-star-color', gallerySkin(st.grwId).stars);
                    setVar(r, '--star-color', c);
                });
                host.querySelectorAll('.grw-badge-root').forEach(r => c ? r.style.setProperty('--badge-star-color', c) : r.style.removeProperty('--badge-star-color'));
            });
        };
        color.onchange = () => changed('color');
        stars.append(document.createTextNode('Stars'), color);
        const switcher = (key, label) => {
            const l = h('label', {class: 'grw-badge-switch'});
            const c = h('input', {type: 'checkbox'});
            c.checked = sh[key];
            c.onchange = () => { sh[key] = c.checked; changed(key); };
            l.append(c, h('i'), document.createTextNode(label));
            return l;
        };
        tools.append(stars, switcher('dark', 'Dark site background'), switcher('anonym', 'Anonymize profile photos'));
        const note = h('p', {class: 'description'}, '<span class="dashicons dashicons-info-outline grw-badge-note-ico"></span><b>Sample data.</b> <a href="#" class="grw-badge-find">Find your place</a> above to see the designs with your real rating and reviews.');
        head.append(filters, tools, note);
        const count = h('span');
        head.querySelector('h2').append(' ', count);
        W.colors();

        // 38 live widgets at once make the page heavy: each is drawn as it comes near the screen.
        const lazy = new IntersectionObserver(es => es.forEach(e => {
            if (!e.isIntersecting) return;
            lazy.unobserve(e.target);
            e.target.grwDraw();
        }), {rootMargin: '600px 0px'});

        const sections = [];
        const section = (key, title, parent) => {
            const sec = h('section', {class: 'grw-themes-section', 'data-kind': key});
            sec.append(h('h3', null, esc(title)));
            (parent || host).append(sec);
            return sec;
        };
        // The list and the rating, one tile each, share a row.
        const pair = h('div', {class: 'grw-themes-pair'});

        const stages = [];
        // The server renders the previews in the presets' order, so i is the place of a preset there.
        const ids = Object.keys(C.presets);
        const order = ORDER.filter(id => C.presets[id]).concat(ids.filter(id => ORDER.indexOf(id) < 0));
        // A design without a header takes half a row.
        const half = (id, layout) => (PAIRS[id] || [])[layout === 'grid' ? 1 : 0] === 'none';
        // A half tile left alone between whole ones takes the next half tile up beside it in the page itself, so Tab goes in the order seen.
        const arranged = layout => {
            const out = [];
            let alone = -1;
            order.forEach(id => {
                if (!half(id, layout)) out.push(id);
                else if (alone < 0) alone = out.push(id) - 1;
                else { out.splice(alone + 1, 0, id); alone = -1; }
            });
            return out;
        };
        // id is the card preset, none on a list or rating tile.
        const tile = (grid, layout, id, i, name) => {
            const card = h('div', {class: 'grw-badge-card' + (id && half(id, layout) ? ' grw-themes-half' : '')});
            const top = h('div', {class: 'grw-badge-card-head'}, '<span>' + esc(name) + '</span>');
            const chosen = () => theme() && state.options.view_mode === layout && (!id || state.options.card_style === id);
            const stage = h('div', {class: 'grw-badge-stage grw-themes-stage'});
            stage.grwId = id;
            stages.push(stage);
            const btn = h('button', {type: 'button', class: 'button button-primary', 'aria-label': (chosen() ? 'Selected: ' : 'Select ') + name + ' ' + layout}, chosen() ? 'Selected' : 'Select');
            card.classList.toggle('grw-badge-card-sel', chosen());
            btn.onclick = () => {
                if (!chosen()) {
                    state.options = Object.assign({}, THEME_DEFAULTS, {view_mode: layout}, id ? {card_style: id} : {});
                    state.options.style_vars = (id ? styleVars(id, gallerySkin(id)) : '') + (sh.color ? '--star-color:' + sh.color + ';' : '') + (id ? plate(headerOf(id, layout).vars, sh.dark) : '');
                    if (sh.dark) state.options.dark_theme = true;
                    if (sh.anonym) state.options.anonym_avatar = true;
                    if (id) Object.assign(state.options, headerOf(id, layout).opts);
                    state.preset = Object.assign({}, state.options);
                    state.presetName = null;
                }
                W.go(2);
            };
            top.append(btn);
            card.append(top, stage);
            grid.append(card);
            // While a place connects there is no data; the gallery is drawn again once it comes.
            // Shaped before the widget lays itself out: a first layout with the server's two reviews makes scroll anchoring jump a row.
            return () => { stage.grwDraw = () => { if (!state.data) return; mount(stage, state.data[layout][i], () => { look(stage); if (layout === 'grid') twoRows(stage); }); }; lazy.observe(stage); };
        };
        KINDS.forEach(([layout, title]) => {
            const sec = section(layout, title, hasCards(layout) ? host : pair);
            const grid = h('div', {class: 'grw-badge-grid grw-themes-grid'});
            sec.append(grid);
            const cards = hasCards(layout) ? arranged(layout).map(id => tile(grid, layout, id, ids.indexOf(id), C.presets[id].name)) : [tile(grid, layout, null, 0, 'Original')];
            sections.push({sec, key: layout, draw: () => cards.forEach(f => f())});
        });
        host.append(pair);

        const badgeSec = section('badge', 'Badge');
        const badges = kit.presets(badgeSec, null, sh);
        sections.push({sec: badgeSec, key: 'badge', draw: () => badges.refresh(), drawn: true});

        // Colour and dark repaint what is drawn; the photos are the server's, so they need the data again.
        function changed(key) {
            if (key === 'anonym') return W.loadData(refresh, host);
            stages.forEach(look);
            badges.refresh();
        }

        // A hidden section is drawn when it is shown: a slider started at zero width would keep the narrowest columns.
        const show = () => {
            sections.forEach(s => {
                s.sec.hidden = filter !== 'all' && filter !== s.key;
                if (!s.sec.hidden && !s.drawn) { s.drawn = true; s.draw(); }
            });
            pair.hidden = [...pair.children].every(s => s.hidden);
            count.textContent = '(' + host.querySelectorAll('.grw-themes-section:not([hidden]) .grw-badge-card').length + ')';
        };
        [['all', 'All']].concat(KINDS, [['badge', 'Badge']]).forEach(([v, t]) => {
            const b = h('button', {type: 'button', class: v === filter ? 'grw-pl-on' : '', 'aria-pressed': String(v === filter)}, esc(t));
            b.onclick = () => {
                filter = v;
                [...filters.children].forEach(x => { x.classList.toggle('grw-pl-on', x === b); x.setAttribute('aria-pressed', String(x === b)); });
                show();
                // Scrolled into the list, the new one starts under the stuck bar; on phones only the filters stick.
                const past = host.getBoundingClientRect().top - Math.max(head.getBoundingClientRect().bottom, filters.getBoundingClientRect().bottom);
                if (past < 0) scrollBy(0, past);
            };
            filters.append(b);
        });
        function refresh() { note.hidden = !state.data.sample; sections.forEach(s => { s.drawn = false; }); show(); }
        refresh();
        return {refresh};
    }

    /* ------------------------------------------------------------ step 2 */

    function renderEditor(ctx) {
        if (!theme()) return kit.editor(ctx);
        const o = state.options, ui = ctx.ui, stage = ctx.stage;
        const skin = hasCards(o.view_mode) ? skinOf(o) : null;
        stage.classList.add('grw-themes-stage');
        stage.classList.toggle('grw-themes-dark', !!o.dark_theme);

        // The page the widget will sit on, for the preview only: nothing of it is saved.
        const PAGES = [['#ffffff', 'White'], ['#f0f0f1', 'Gray'], ['#1d2327', 'Dark']];
        const pageBg = h('div', {class: 'grw-themes-page-bg'});
        const pages = h('div', {class: 'grw-pl-seg', role: 'group', 'aria-label': 'Page background'});
        const custom = h('input', {type: 'color', class: 'grw-badge-color', list: 'grw-badge-colors', 'aria-label': 'Page background color', title: 'Your page color'});
        const setPage = (c, b) => {
            stage.style.background = c;
            [...pages.children].forEach(x => { x.classList.toggle('grw-pl-on', x === b); x.setAttribute('aria-pressed', String(x === b)); });
        };
        PAGES.forEach(([c, t]) => { const b = h('button', {type: 'button'}, esc(t)); b.onclick = () => { setPage(c, b); custom.value = c; }; pages.append(b); });
        custom.oninput = () => setPage(custom.value, null);
        pageBg.append(h('span', null, 'Page'), pages, custom);
        stage.closest('.grw-badge-cols').querySelector('.grw-badge-devices').before(pageBg);
        const pageFor = dark => { const i = dark ? 2 : 0; custom.value = PAGES[i][0]; setPage(PAGES[i][0], pages.children[i]); };
        pageFor(o.dark_theme);
        // Dark at once on what is drawn; the render that follows confirms it.
        const darken = v => {
            stage.classList.toggle('grw-themes-dark', v);
            pageFor(v);
            const r = stage.querySelector('.wp-gr');
            if (r) r.classList.toggle('wp-dark', v);
            return r;
        };

        let seq = 0, timer = 0;
        const load = () => {
            clearTimeout(timer);
            timer = setTimeout(() => {
                const mine = ++seq;
                W.post('grw_theme_preview', {pids: state.selected, langs: W.langs(), post_id: state.feedId || '', options: JSON.stringify(o)}, res => {
                    if (mine !== seq) return;
                    if (res.status !== 'success') return W.toast(W.errorOf(res) || 'Could not load the preview.', 'error');
                    mount(stage, res.result.html);
                    if (skin) paint(stage, skin);
                });
            }, 250);
        };

        // Reviews before "More reviews": a widget from the builder may hold any number, and an empty one shows all.
        const perPage = () => {
            const v = o.pagination === undefined ? '10' : String(o.pagination) === '' ? '0' : String(o.pagination);
            const s = ui.select('pagination', [...new Set(['3', '4', '5', '6', '8', '9', '10', '12', '15', '20', '30', v])].filter(n => n !== '0').sort((a, b) => a - b).map(n => [n, n]).concat([['0', 'All']]));
            s.value = v;
            return s;
        };

        // A list or a rating: only what acts on it, the rest is in the builder.
        if (!skin) {
            const list = o.view_mode === 'list';
            const b = ui.card(list ? 'List' : 'Rating', true);
            if (list) b.append(ui.toggle('header_hide_social', 'Show the business header', {get: () => !o.header_hide_social, set: v => { o.header_hide_social = !v; }}));
            if (list) b.append(...ui.row('Reviews per page', perPage()));
            b.append(ui.toggle('dark_theme', 'Dark site background', {set: v => { o.dark_theme = v; darken(v); }}));
            b.append(...ui.row('Stars color', ui.color(W.styleVar('--star-color') || '#fb8e28', 'Stars color', v => { W.setStyleVar('--star-color', v.toLowerCase() === '#fb8e28' ? '' : v); load(); })));
            b.append(...(list ? [ui.toggle('anonym_avatar', 'Anonymize profile photos'), ui.toggle('short_last_name', 'Shorten last name (GDPR)')] : [ui.toggle('rating_popup', 'Open reviews in a popup on click')]));
            return key => { if (key !== 'device') load(); };
        }

        const skinChanged = () => { o.style_vars = styleVars(o.card_style, skin) + extraVars(o.style_vars); paint(stage, skin); };
        const sk = W.controls(ctx.side, skin, () => { skinChanged(); sync(); });
        const show = (el, on) => { (el.parentNode && el.parentNode.classList.contains('grw-pl-row') ? el.parentNode : el).hidden = !on; };
        // The controls that show a skin value Dark changes.
        const shown = [];
        const colorOf = (key, label) => {
            const hex = h('code', {class: 'grw-themes-hex'}, esc(skin[key]));
            const pick = h('span', {class: 'grw-themes-pick'});
            const input = sk.color(skin[key], label, v => { skin[key] = v; hex.textContent = v; skinChanged(); });
            pick.append(hex, input);
            shown.push(() => { hex.textContent = skin[key]; input.value = skin[key]; });
            return ui.row(label, pick);
        };

        /* ---- Layout ---- */
        const b1 = ui.card('Layout', true);
        b1.append(...ui.row('Layout', ui.seg('view_mode', LAYOUTS, () => { sync(); load(); })));
        const per = perPage();
        b1.append(...ui.row('Reviews per page', per));
        b1.append(ui.toggle('header_hide_social', 'Show the business header', {get: () => !o.header_hide_social, set: v => { o.header_hide_social = !v; sync(); }}));
        // The card follows the background, and the color controls show its new values.
        b1.append(ui.toggle('dark_theme', 'Dark site background', {set: v => {
            o.dark_theme = v;
            Object.assign(skin, v ? darkOf(skin, o.card_style) : lightOf(skin, o.card_style));
            o.style_vars = styleVars(o.card_style, skin) + plate(extraVars(o.style_vars), v);
            shown.forEach(f => f());
            paint(stage, skin);
            const r = darken(v);
            if (r && r.style.getPropertyValue('--slider-head-card-bg')) setVar(r, '--slider-head-card-bg', v ? DARK_CARD : PLATE);
        }}));
        const headPos = ui.select('slider_head_pos', [['1', 'Above the reviews'], ['2', 'Beside the reviews'], ['3', 'Below the reviews']]);
        headPos.value = o.slider_head_pos || '2';
        b1.append(...ui.row('Header', headPos));

        /* ---- Card ---- */
        const b2 = sk.card('Card', true);
        const BGS = [['color', 'Color'], ['gradient', 'Gradient'], ['none', 'None']], bg = sk.seg('bg', BGS);
        shown.push(() => [...bg.children].forEach((b, i) => { b.classList.toggle('grw-pl-on', BGS[i][0] === skin.bg); b.setAttribute('aria-pressed', String(BGS[i][0] === skin.bg)); }));
        b2.append(...sk.row('Background', bg));
        const bg1 = colorOf('bg1', 'Background color'), bg2 = colorOf('bg2', 'Gradient end color');
        b2.append(...bg1, ...bg2);
        b2.append(sk.toggle('border', 'Border'));
        const border = colorOf('borderColor', 'Border color');
        b2.append(...border);
        b2.append(...sk.row('Shadow', sk.select('shadow', [['none', 'None'], ['light', 'Light'], ['soft', 'Soft'], ['grw', 'Classic']])));
        b2.append(sk.slider('Corner radius', 0, 24, 1, skin.radius, 'px', v => { skin.radius = +v; skinChanged(); }));
        // The site script cuts the text when a card is drawn, so a new number of lines needs a new render.
        b2.append(...sk.row('Text lines', sk.select('lines', [['2', '2'], ['3', '3'], ['4', '4'], ['5', '5'], ['6', '6'], ['7', '7'], ['8', '8'], ['none', 'All']], () => { skinChanged(); load(); })));

        /* ---- Colours ---- */
        const b3 = sk.card('Colors', false);
        b3.append(...colorOf('stars', 'Stars'));
        b3.append(sk.slider('Stars size', 12, 28, 1, skin.starSize, 'px', v => { skin.starSize = +v; skinChanged(); }));
        b3.append(...colorOf('name', 'Name'), ...colorOf('text', 'Text'), ...colorOf('date', 'Date'));

        /* ---- Photos and names ---- */
        const b4 = sk.card('Photos and names', false);
        b4.append(sk.slider('Photo size', 32, 64, 2, skin.photo, 'px', v => { skin.photo = +v; skinChanged(); }));
        b4.append(sk.toggle('gray', 'Gray photos'));
        b4.append(ui.toggle('anonym_avatar', 'Anonymize profile photos'));
        b4.append(ui.toggle('short_last_name', 'Shorten last name (GDPR)'));

        /* ---- Slider ---- */
        const b5 = ui.card('Slider', false);
        const auto = ui.toggle('slider_autoplay', 'Autoplay', {get: () => o.slider_autoplay !== false, set: v => { o.slider_autoplay = v; sync(); }});
        const speed = ui.slider('Speed', 1, 10, 1, o.slider_speed || 3, 's', v => { o.slider_speed = v == 3 ? '' : v; load(); });
        const pause = ui.toggle('slider_mousestop', 'Pause on hover', {get: () => o.slider_mousestop !== false, set: v => { o.slider_mousestop = v; }});
        b5.append(auto, speed, pause);
        b5.append(ui.toggle('slider_hide_prevnext', 'Arrows', {get: () => !o.slider_hide_prevnext, set: v => { o.slider_hide_prevnext = !v; }}));
        b5.append(ui.toggle('slider_hide_dots', 'Dots', {get: () => !o.slider_hide_dots, set: v => { o.slider_hide_dots = !v; }}));

        // Shows only the controls that act on what is chosen.
        function sync() {
            const slider = o.view_mode === 'slider';
            b5.parentNode.hidden = !slider;
            show(per, !slider);
            show(headPos, slider && !o.header_hide_social);
            bg1.forEach(el => { el.hidden = skin.bg === 'none'; });
            bg2.forEach(el => { el.hidden = skin.bg !== 'gradient'; });
            border.forEach(el => { el.hidden = !skin.border; });
            speed.hidden = pause.hidden = o.slider_autoplay === false;
        }
        sync();

        // Skin controls paint, the rest is a render; the device buttons only change the stage width.
        return key => {
            if (key === 'device') return;
            sync();
            load();
        };
    }

    /* ------------------------------------------------------------ step 3 */

    function renderPublish(row) {
        return kit.publish(row);
    }

    const PLACES = {
        slider: 'A slider fits the home page, between two sections, where visitors decide whether to trust you.',
        grid: 'A grid suits a page of its own, such as Reviews or Testimonials, or the end of a service page.'
    };

    // The result: the saved widget beside the ways to put it on a page. Nothing reaches the site by itself,
    // except a floating badge, which its own button shows on every page.
    function renderResult(wrap) {
        // A second save may follow the first: the screen waits for it rather than show the widget as it was.
        if (!state.feedId || state.saving) {
            wrap.append(h('h2', null, 'Saving…'));
            return true;
        }
        const badge = !theme(), floating = badge && state.options.badge_pos !== 'embed';
        const sc = '[grw id=' + state.feedId + ']';
        const head = h('div', {class: 'grw-themes-done'});
        head.append(h('h2', null, 'Your ' + esc(badge ? 'badge' : state.options.view_mode) + ' is ready'),
            h('p', {class: 'description'}, 'Saved as <b>' + esc(state.title) + '</b>. You find it later under Widgets.'));
        const cols = h('div', {class: 'grw-badge-cols grw-themes-result'});
        const main = h('div', {class: 'grw-badge-main-col'});
        const side = h('aside', {class: 'grw-pl-side'});
        // The ways to add it come first for the keyboard too; the grid still shows them on the right.
        cols.append(side, main);
        wrap.append(head, cols);

        if (badge) {
            const stage = h('div', {class: 'grw-badge-stage grw-badge-embed-stage'});
            main.append(stage);
            // Step 3 opened by its address comes without the preview data.
            W.loadData(() => kit.preview(stage), stage);
        } else {
            const stage = h('div', {class: 'grw-badge-stage grw-themes-stage grw-themes-result-stage' + (state.options.dark_theme ? ' grw-themes-dark' : '')});
            main.append(stage);
            W.post('grw_theme_preview', {pids: state.selected, langs: W.langs(), post_id: state.feedId, options: JSON.stringify(state.options)}, res => {
                if (res.status === 'success') mount(stage, res.result.html);
            });
        }

        const put = h('section', {class: 'grw-pl-card grw-themes-put'});
        put.append(h('h3', null, 'Add it to your site'));
        // A floating badge goes on the site by its own button, and the shortcode is for one page only.
        const byHand = floating ? h('div', {class: 'grw-themes-add'}) : put;
        if (floating) {
            const show = h('div', {class: 'grw-themes-show'});
            kit.publish(show);
            put.append(show, h('p', {class: 'description'}, 'It appears on every page of your site.'), byHand);
            byHand.append(h('p', {class: 'grw-themes-or'}, 'Need it on one page only?'));
        }

        const WAYS = [
            ['Block editor', 'Edit a page or post, click <b>+</b>, search for <b>Google Reviews</b> and pick <b>' + esc(state.title) + '</b>.'],
            ['Elementor and other builders', 'Add the builder\'s <b>Shortcode</b> element and paste the shortcode into it.'],
            ['Widget area', 'In <b>Appearance → Widgets</b> add the <b>Google Reviews</b> widget to a sidebar or footer and choose <b>' + esc(state.title) + '</b>.']
        ];
        const field = h('input', {type: 'text', class: 'grw-themes-sc', readonly: true, value: sc, 'aria-label': 'Shortcode'});
        const copy = h('button', {type: 'button', class: floating ? 'button' : 'button button-primary'}, 'Copy');
        copy.onclick = () => {
            field.select();
            (navigator.clipboard ? navigator.clipboard.writeText(sc) : Promise.reject()).catch(() => document.execCommand('copy'));
            copy.textContent = 'Copied';
            setTimeout(() => { copy.textContent = 'Copy'; }, 2000);
        };
        const line = h('div', {class: 'grw-themes-sc-row'});
        line.append(field, copy);
        byHand.append(line, h('p', {class: 'description'}, 'Paste the shortcode into any page, post or text widget.'));
        WAYS.forEach(([t, text]) => {
            const way = h('details', {class: 'grw-themes-way'});
            way.append(h('summary', null, esc(t)), h('p', {class: 'description'}, text));
            byHand.append(way);
        });
        if (!floating) {
            const add = h('div', {class: 'grw-themes-add'});
            const make = h('button', {type: 'button', class: 'button'}, 'Create a draft page');
            const made = h('p', {class: 'description grw-themes-made', 'aria-live': 'polite'}, 'A new draft page "Reviews" with only this widget in it. Nothing changes on your site until you publish that page.');
            const ready = page => {
                make.disabled = true;
                made.innerHTML = 'The draft page is ready: <a href="' + esc(page.view) + '" target="_blank" rel="noopener">view it</a> or <a href="' + esc(page.edit) + '">edit and publish it</a>.';
            };
            // Every click would make another draft, so the page made for this widget is kept for later visits of this step.
            state.pages = state.pages || {};
            if (state.pages[state.feedId]) ready(state.pages[state.feedId]);
            make.onclick = () => {
                make.disabled = true;
                W.post('grw_theme_page', {post_id: state.feedId}, res => {
                    if (res.status !== 'success') { make.disabled = false; return W.toast(W.errorOf(res) || 'Could not create the page.', 'error'); }
                    state.pages[state.feedId] = res.result;
                    ready(res.result);
                    W.toast('The draft page is created.');
                });
            };
            add.append(h('p', {class: 'grw-themes-or'}, 'No page for it yet?'), make, made);
            put.append(add);
        }
        if (PLACES[state.options.view_mode]) put.append(h('p', {class: 'grw-themes-tip'}, '<span class="dashicons dashicons-lightbulb"></span>' + esc(PLACES[state.options.view_mode])));

        // The builder sends a badge back here (Admin_Feed_Columns::keep_editor), so a badge has no More options.
        const links = h('p', {class: 'grw-pl-links'}, (badge ? '' : '<a href="' + esc(V0.builder + state.feedId) + '">More options</a> · ') + '<a href="#" class="grw-themes-back">Back to setup</a> · <a href="' + esc(location.pathname + '?page=grw-widget') + '">Create another</a>');
        links.querySelector('.grw-themes-back').onclick = e => { e.preventDefault(); W.go(2); };
        side.append(put, links);
        return true;
    }


    grw_steps(root, {
        noun: () => theme() ? state.options.view_mode : 'badge',
        pick: 'Select a design',
        previewTitle: () => theme() ? 'Widget preview' : 'Badge preview',
        data: 'grw_theme_data',
        dataKey: () => state.shared.anonym ? 'anonym' : '',
        dataFields: () => ({anonym: state.shared.anonym ? '1' : '', headers: JSON.stringify(LAYOUTS.reduce((m, [layout]) => { Object.keys(C.presets).forEach(id => { const hd = headerOf(id, layout); m[layout + ':' + id] = Object.assign({style_vars: hd.vars}, hd.opts); }); return m; }, {}))}),
        save: () => theme() ? 'grw_theme_save' : 'grw_badge_save',
        defaults: () => theme() ? THEME_DEFAULTS : BADGE_DEFAULTS,
        init(w) {
            W = w; state = w.state; h = w.h; esc = w.esc;
            state.shared = {color: null, dark: false, anonym: false};
            kit = grw_badge_kit(W, () => state.data && state.data.badge);
            if (theme()) state.global = false;
            else kit.init();
        },
        payload: () => theme() ? {} : kit.payload(),
        presets: renderPresets,
        editor: renderEditor,
        publish: renderPublish,
        result: renderResult
    });
}

// public-main.js (grw_init) and badge.js are loaded after this file, so wait for the DOM.
if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', grw_themes_page);
else grw_themes_page();
