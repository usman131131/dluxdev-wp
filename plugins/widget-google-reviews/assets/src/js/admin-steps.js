// The three-step Create Widget page: places on step 1, an editor on step 2, the shortcode on step 3.
// The page brings its presets, its editor and its publish extras through cfg.
function grw_steps(root, cfg) {
    const V = JSON.parse(root.dataset.vars);
    const body = root.querySelector('.grw-badge-body');
    const steps = [...root.querySelectorAll('.grw-badge-steps li')];
    const CONNECT_URL = V.connect || 'https://app.richplugins.com/public/connect';
    const CONNECT_ORIGIN = new URL(CONNECT_URL).origin;
    // A field of cfg may be a function: in the gallery it depends on what was picked.
    const val = k => typeof cfg[k] === 'function' ? cfg[k]() : cfg[k];

    const state = {
        step: V.feedId ? Math.min(3, Math.max(1, parseInt(new URLSearchParams(location.search).get('step'), 10) || 2)) : 1,
        // In the widget's own order: the first place is the one its header shows.
        tiles: V.feedId ? V.selected.map(id => V.places.find(p => p.id === id)).filter(Boolean).map(p => Object.assign({connected: true}, p, V.langs && V.langs[p.id] ? {lang: V.langs[p.id]} : {})) : [],
        selected: V.feedId ? V.selected.filter(id => V.places.some(p => p.id === id)) : [],
        options: V.feedId ? Object.assign({}, val('defaults'), V.options) : null,
        preset: V.feedId ? Object.assign({}, val('defaults'), V.options) : null,
        data: null,
        feedId: V.feedId,
        title: V.title,
        done: V.feedId ? 3 : 1,
        presetName: null,
        cards: {},
        device: 'desktop',
        credits: -1
    };

    const toast = (msg, type, action) => GRW_TOAST.show({msg, type: type || 'success', action});
    const esc = grw_esc;
    const h = (tag, atts, html) => {
        const e = document.createElement(tag);
        for (const k in atts || {}) {
            if (k === 'class') e.className = atts[k];
            else if (k === 'on') for (const ev in atts[k]) e.addEventListener(ev, atts[k][ev]);
            else if (atts[k] != null && atts[k] !== false) e.setAttribute(k, atts[k]);
        }
        if (html != null) e.innerHTML = html;
        return e;
    };
    const post = (action, fields, cb) => {
        const fd = new FormData();
        fd.append('action', action);
        fd.append('grw_nonce', V.nonce);
        fd.append('grw_wpnonce', V.nonce);
        for (const k in fields) {
            if (Array.isArray(fields[k])) fields[k].forEach(v => fd.append(k + '[]', v));
            else fd.append(k, fields[k]);
        }
        // The failure handler sits beside cb, not after it: an error thrown inside cb must not call cb again as a failed request.
        fetch(ajaxurl, {method: 'POST', credentials: 'same-origin', body: fd})
            .then(r => r.json())
            .then(cb, () => cb({status: 'failed', result: {error_message: 'Request failed, please try again.'}}));
    };
    const errorOf = grw_get_error;

    const W = {V, state, h, esc, post, toast, errorOf, go, publish, loadData, pids, langs, controls, styleVar, setStyleVar};

    /* ------------------------------------------------------------ steps */

    // Counts the visits to steps: a reply asked for by an earlier visit draws nothing in a later one.
    let visit = 0;
    let redrawStart = () => {};
    let redrawTiles = () => {};
    let foldSearch = () => {};
    // Search suggestions lie over the page: a click, a focus or a wheel elsewhere folds them.
    ['pointerdown', 'focusin', 'wheel'].forEach(t => document.addEventListener(t, e => {
        if (!(e.target.closest && e.target.closest('.grw-badge-search'))) foldSearch();
    }, {passive: true}));
    // Google has no page for the sample place.
    document.addEventListener('click', e => {
        if (e.target.closest && e.target.closest('a[href*="placeid=sample"]')) e.preventDefault();
    });

    function go(step) {
        visit++;
        state.step = step;
        state.done = Math.max(state.done, step);
        if (state.feedId) {
            const url = new URL(location.href);
            url.searchParams.set('step', step);
            history.replaceState(null, '', url);
        }
        steps.forEach(li => {
            const n = +li.dataset.step, open = reached(n) && n !== step;
            li.classList.toggle('grw-badge-step-on', reached(n));
            li.classList.toggle('grw-badge-step-cur', n === step);
            if (n === step) li.setAttribute('aria-current', 'step'); else li.removeAttribute('aria-current');
            if (open) { li.tabIndex = 0; li.setAttribute('role', 'button'); } else { li.removeAttribute('tabindex'); li.removeAttribute('role'); }
        });
        body.innerHTML = '';
        if (step === 1) renderStart();
        if (step === 2) loadData(renderEditor);
        if (step === 3) renderPublish();
        window.scrollTo(0, 0);
    }
    // While the first save is in flight the page already sits on step 3 without an id.
    const reached = step => step === 3 ? (!!state.feedId || state.step === 3) : step <= state.done;
    steps.forEach(li => {
        li.addEventListener('click', () => {
            const to = +li.dataset.step;
            if (!reached(to) || to === state.step) return;
            // "Publish" with changes not saved yet publishes them, as the button on step 2 does: step 3 says the widget is saved.
            // A place still connecting is a change the snapshot cannot see yet; publish() asks to wait for it.
            if (to === 3 && state.feedId && !state.saving && (snapshot() !== state.saved || state.tiles.some(t => !t.connected))) return publish();
            go(to);
        });
        li.addEventListener('keydown', e => {
            if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); li.click(); }
        });
    });

    // go(1) needs no focus call: step 1 focuses its search as it draws it.
    body.addEventListener('click', e => {
        if (!e.target.closest('.grw-badge-find')) return;
        e.preventDefault();
        if (state.step !== 1) return go(1);
        const search = body.querySelector('.grw-badge-search');
        search.scrollIntoView({block: 'center'});
        (search.querySelector('iframe, input') || search).focus();
    });

    function langs() { const m = {}; state.tiles.filter(t => t.connected).forEach(t => { m[t.id] = t.lang || ''; }); return JSON.stringify(m); }
    const payload = extra => Object.assign({post_id: state.feedId || '', title: state.title || '', pids: state.selected, langs: langs(), options: JSON.stringify(state.options || val('defaults'))}, cfg.payload ? cfg.payload() : {}, extra || {});
    // What a save sends, without the id and title it gets back: state.saved holds it for the widget as last saved.
    const snapshot = extra => JSON.stringify([state.selected, langs(), state.options || val('defaults'), Object.assign({}, cfg.payload ? cfg.payload() : {}, extra || {})]);
    W.savedOptions = () => state.saved ? JSON.parse(state.saved)[2] : null;

    // Why a publish cannot go now, or '' when it can. A place still connecting is left out of what a save would send.
    const blocked = () => {
        if (!state.selected.length) return 'The ' + val('noun') + ' is on sample data. Find your place on step 1 to publish it.';
        const t = state.tiles.find(x => !x.connected);
        return t ? 'Wait until <b>' + esc(t.name) + '</b> is connected, then publish.' : '';
    };

    function publish() {
        const why = blocked();
        if (why) return toast(why, 'error');
        // A second click before the first save answers would post without the id and make a second widget.
        if (state.saving) return go(3);
        state.saving = true;
        const sent = snapshot();
        go(3);
        post(val('save'), payload(), res => {
            state.saving = false;
            if (res.status !== 'success') { go(2); return toast(errorOf(res) || 'Could not save.', 'error'); }
            // Read before adopt(): the answer may correct what was sent (an embed badge is never on the site).
            const meanwhile = snapshot() !== sent || state.tiles.some(t => !t.connected);
            adopt(res);
            state.saved = meanwhile ? sent : snapshot();
            // Changes made while it saved and published again meanwhile: they are saved now, or back on step 2 while a place connects.
            if (state.step === 3 && meanwhile) {
                const why = blocked();
                if (!why) return publish();
                go(2);
                return toast(why, 'error');
            }
            if (state.step === 3) { body.innerHTML = ''; renderPublish(); }
        });
    }

    // The id goes into the URL, so a reload or a second save edits this one instead of creating another.
    function adopt(res) {
        state.feedId = res.result.id;
        if (res.result.title) state.title = res.result.title;
        // A badge save answers whether the badge is on the site: an embed badge never is.
        if ('global' in res.result) state.global = !!res.result.global;
        const url = new URL(location.href);
        url.searchParams.delete('new');
        url.searchParams.set('grw_feed_id', state.feedId);
        url.searchParams.set('step', state.step);
        history.replaceState(null, '', url);
    }

    // A save from step 3 (the badge's site switch) may come back as a new widget as well.
    W.save = (extra, cb) => {
        const sent = snapshot(extra);
        post(val('save'), payload(extra), res => {
            const meanwhile = snapshot(extra) !== sent;
            if (res.status === 'success' && res.result && res.result.id) adopt(res);
            cb(res);
            if (res.status === 'success') state.saved = meanwhile ? sent : snapshot();
        });
    };

    /* ------------------------------------------------------------ step 1: search + presets */

    function renderStart() {
        const wrap = h('div', {class: 'grw-badge-section'});

        const card = h('div', {class: 'grw-badge-card-block grw-badge-connect'});
        const search = h('div', {class: 'grw-badge-search'});
        const addLabel = () => 'Use a connected business (' + V.places.length + ')';
        const add = h('button', {type: 'button', class: 'button grw-badge-tiles-add'}, addLabel());
        const tiles = h('div', {class: 'grw-badge-tiles'});
        card.append(search, ...(V.places.length ? [add] : []), tiles);
        const rememberPlace = p => {
            if (V.places.some(x => x.id === p.id)) return;
            V.places.push(p);
            add.textContent = addLabel();
            if (!add.isConnected) search.after(add);
        };
        wrap.append(h('h2', null, 'Connect Google'), card);

        const grid = h('div', {class: 'grw-badge-presets'});
        const head = h('div', {class: 'grw-badge-head-inline'});
        head.append(h('h2', null, cfg.pick));
        wrap.append(head, grid);
        body.append(wrap);

        let presets = null;
        const drawGrid = () => loadData(() => {
            if (presets) presets.refresh();
            else { grid.innerHTML = ''; presets = cfg.presets(grid, head, W); }
        }, grid);
        const drawTiles = () => {
            tiles.innerHTML = '';
            state.tiles.forEach(t => tiles.append(tile(t, () => { state.tiles = state.tiles.filter(x => x !== t); drawTiles(); drawGrid(); }, lang => relang(t, lang))));
        };
        add.onclick = () => picker(picked => { picked.forEach(p => addTile(Object.assign({connected: true}, p))); drawTiles(); drawGrid(); });
        drawTiles();
        drawGrid();
        redrawStart = () => { drawTiles(); drawGrid(); };
        redrawTiles = drawTiles;

        const relang = (t, lang) => {
            const was = t.lang;
            t.lang = lang;
            t.connected = false;
            drawTiles();
            const done = (err, result) => {
                // A place removed meanwhile stays removed.
                if (!state.tiles.includes(t)) return;
                if (err) { t.lang = was; t.connected = true; redrawStart(); return toast(err, 'error'); }
                addTile({id: t.id, name: result.name, photo: result.photo, rating: result.rating, total: result.user_ratings_total, address: t.address, lang, connected: true});
                if (result.credits > -1) state.credits = result.credits;
                redrawStart();
            };
            if (V.hasKey) connect({id: t.id, lang}, done);
            else frameConnect({id: t.id, lang, local_img: true}, done);
        };

        // A pick answered after step 1 was drawn again belongs on the step as it is now.
        const onPick = place => {
            if (state.tiles.some(t => t.id === place.id)) return;
            addTile(place);
            redrawTiles();
            const done = (err, result) => {
                if (!err) rememberPlace({id: place.id, name: result.name || place.name, address: place.address || '', photo: result.photo || '', rating: result.rating ? Number(result.rating).toFixed(1) : '', total: result.user_ratings_total || 0, lang: place.lang});
                // A place removed while it connected stays removed.
                if (!state.tiles.some(t => t.id === place.id)) return;
                if (err) { state.tiles = state.tiles.filter(t => t.id !== place.id); redrawStart(); return toast(err, 'error'); }
                addTile({id: place.id, name: result.name, photo: result.photo, rating: result.rating, total: result.user_ratings_total, address: place.address, lang: place.lang, connected: true});
                if (result.credits > -1) state.credits = result.credits;
                toast('<b>' + esc(result.name || place.name) + '</b> connected.' + (state.credits > -1 ? '<br>' + state.credits + ' attempts remaining (without your API key).' : ''));
                redrawStart();
            };
            if (V.hasKey) connect({id: place.id, lang: place.lang}, done);
            else frameConnect({id: place.id, lang: place.lang, local_img: true}, done);
        };
        if (V.hasKey) localSearch(search, onPick);
        else frameSearch(search, onPick, () => { drawTiles(); drawGrid(); });
    }

    function tile(t, onRemove, onLang) {
        const stars = t.rating ? '<span class="rpi-stars" style="--rating:' + Number(t.rating) + '">' + Number(t.rating).toFixed(1) + '</span>' : '';
        const total = t.total ? '(' + Number(t.total).toLocaleString() + ')' : (t.connected ? '' : 'Connecting…');
        const langList = (rpi.Langs && rpi.Langs.google) || [];
        // Closed it reads as the code, open it lists full names: a code label with a transparent select laid over it.
        const langOpts = langList.map(l => '<option value="' + esc(l.v) + '"' + (l.v === t.lang ? ' selected' : '') + '>' + esc(l.t) + '</option>').join('');
        const langName = (langList.find(l => l.v === t.lang) || {}).t || '';
        // Google still calls Hebrew "iw".
        const langCode = t.lang === 'iw' ? 'he' : String(t.lang || '');
        const el = h('div', {class: 'grw-badge-tile' + (t.connected ? '' : ' grw-badge-tile-pending')},
            '<img class="grw-badge-tile-photo" alt="" width="32" height="32" src="' + esc(t.photo || V.blank) + '" onerror="this.src=\'' + esc(V.blank) + '\'">' +
            '<div class="grw-badge-tile-facts">' +
                '<div class="grw-badge-tile-line" title="' + esc(t.address) + '"><b>' + esc(t.name) + '</b>' +
                    (langOpts ? '<span class="grw-badge-tile-dot">·</span><span class="grw-badge-tile-lang" title="' + esc(langName ? 'Review language: ' + langName : 'Review language') + '">' +
                        '<span class="grw-badge-tile-lang-code">' + esc(langCode.toUpperCase()) + '</span>' +
                        '<select aria-label="Review language"' + (t.connected ? '' : ' disabled') + '>' + langOpts + '</select></span>' : '') +
                '</div>' +
                '<div class="grw-badge-tile-score">' + stars + '<span>' + esc(total) + '</span></div>' +
            '</div>' +
            '<button type="button" class="grw-badge-tile-x" aria-label="Remove ' + esc(t.name) + '"></button>');
        el.querySelector('.grw-badge-tile-x').onclick = onRemove;
        const sel = el.querySelector('.grw-badge-tile-lang select');
        if (sel) sel.onchange = () => onLang(sel.value);
        return el;
    }

    function picker(onAdd) {
        const places = V.places;
        const added = new Set(state.tiles.map(t => t.id));
        const modal = h('div', {class: 'grw-badge-modal'});
        const box = h('div', {class: 'grw-badge-modal-box', role: 'dialog', 'aria-modal': 'true', 'aria-label': 'Use a connected business'});
        const head = h('div', {class: 'grw-badge-modal-head'}, '<h2>Use a connected business</h2>');
        const x = h('button', {type: 'button', class: 'grw-badge-modal-x', 'aria-label': 'Close'}, '<span class="dashicons dashicons-no-alt"></span>');
        const search = h('div', {class: 'grw-badge-modal-body'});
        const input = h('input', {type: 'search', class: 'grw-badge-input', placeholder: 'Search by name or address', autocomplete: 'off'});
        const list = h('div', {class: 'grw-badge-modal-list'});
        const empty = h('div', {class: 'grw-badge-modal-empty', hidden: true}, 'No place matches.');
        const foot = h('div', {class: 'grw-badge-modal-foot'});
        const btn = h('button', {type: 'button', class: 'button button-primary', disabled: true}, 'Use this business');
        const rows = places.map(p => h('label', {class: 'grw-badge-row grw-badge-pick'},
            '<input type="checkbox"' + (added.has(p.id) ? ' checked disabled' : '') + '>' +
            '<img class="grw-badge-tile-photo" alt="" width="32" height="32" src="' + esc(p.photo || V.blank) + '" onerror="this.src=\'' + esc(V.blank) + '\'">' +
            '<span><b>' + esc(p.name) + '</b><small>' + esc(p.address) + '</small></span>'));
        const picked = () => places.filter((p, i) => rows[i].firstElementChild.checked && !rows[i].firstElementChild.disabled);
        const close = () => { modal.remove(); document.body.classList.remove('grw-badge-modal-open'); };
        list.onchange = () => {
            const n = picked().length;
            btn.disabled = !n;
            btn.textContent = n > 1 ? 'Use ' + n + ' businesses' : 'Use this business';
        };
        input.oninput = () => {
            const q = input.value.trim().toLowerCase();
            rows.forEach((r, i) => { r.hidden = !!q && (places[i].name + ' ' + places[i].address).toLowerCase().indexOf(q) < 0; });
            empty.hidden = rows.some(r => !r.hidden);
        };
        btn.onclick = () => { const add = picked(); close(); onAdd(add); };
        x.onclick = close;
        modal.onclick = e => { if (e.target === modal) close(); };
        modal.onkeydown = e => { if (e.key === 'Escape') close(); };
        head.append(x);
        search.append(input);
        list.append(...rows, empty);
        foot.append(btn);
        box.append(head, search, list, foot);
        modal.append(box);
        document.body.append(modal);
        document.body.classList.add('grw-badge-modal-open');
        input.focus();
    }

    function addTile(place) {
        const t = state.tiles.find(x => x.id === place.id);
        const next = {
            id: place.id,
            name: place.name || '',
            address: place.address || '',
            photo: place.photo || '',
            rating: place.rating ? Number(place.rating).toFixed(1) : '',
            total: place.total || place.count || place.user_ratings_total || 0,
            lang: place.lang || V.lang,
            connected: !!place.connected
        };
        if (t) Object.assign(t, next, {connected: t.connected || next.connected}); else state.tiles.push(next);
    }

    function connect(params, cb) {
        post('grw_connect_google', {id: params.id, url: params.url || '', lang: params.lang || '', local_img: 'true', token: params.token || '', feed_id: '', v: Date.now()}, res => {
            if (res.status === 'success') cb(null, res.result);
            else cb(errorOf(res) || 'Connection failed, please try again.');
        });
    }

    /* Without an API key the search runs on app.richplugins.com (reCAPTCHA lives there), framed
       inline. Messages: place (a suggestion was picked), connect (a signed connect request),
       resize (content height). The parent asks for a signature with {action:'connect', params}. */
    let frame = null, frameCb = null, frameTimer = null, frameOn = null, listening = false;
    function frameSearch(host, onPick, redraw) {
        // The frame being replaced can no longer sign what it was asked to.
        if (frameCb) {
            const cb = frameCb;
            frameCb = null;
            clearTimeout(frameTimer);
            cb('The connection was interrupted, please pick the place again.');
        }
        frame = h('iframe', {class: 'grw-badge-frame', src: CONNECT_URL + '?inline=1&authcode=' + encodeURIComponent(V.authcode) + '&lang=' + encodeURIComponent(V.lang), title: 'Find your business'});
        host.append(frame);
        // The field alone: the frame keeps its list open inside, its 68px box would show the top of it; the next keystroke resizes the frame again.
        foldSearch = () => { if (frame.offsetHeight > host.offsetHeight) frame.style.height = '40px'; };
        frame.addEventListener('load', () => frame.focus());
        // Step 1 is rebuilt on every visit: one listener for the page, dispatching to the current handlers.
        frameOn = {pick: onPick, redraw};
        if (listening) return;
        listening = true;
        window.addEventListener('message', function onMsg(e) {
            if (e.origin !== CONNECT_ORIGIN || !e.data || !frame || !frame.isConnected || e.source !== frame.contentWindow) return;
            const d = e.data;
            if (d.action === 'resize' && d.height) frame.style.height = Math.min(600, Math.max(40, d.height)) + 'px';
            if (d.action === 'place' && d.place) frameOn.pick(d.place);
            if (d.action === 'connect') {
                // Either the answer to our signature request or, on the non-inline page, its own Connect button.
                const cb = frameCb;
                frameCb = null;
                clearTimeout(frameTimer);
                connect(d, (err, result) => {
                    // The reply may land after step 1 was left and the iframe is gone.
                    if (frame && frame.contentWindow) frame.contentWindow.postMessage({action: 'connect_done'}, '*');
                    if (cb) return cb(err, result);
                    if (err) return toast(err, 'error');
                    addTile({id: d.id, name: result.name, photo: result.photo, rating: result.rating, total: result.user_ratings_total, lang: d.lang, connected: true});
                    if (result.credits > -1) state.credits = result.credits;
                    toast('<b>' + esc(result.name) + '</b> connected.');
                    frameOn.redraw();
                });
            }
        });
    }
    function frameConnect(params, cb) {
        frameCb = cb;
        clearTimeout(frameTimer);
        frameTimer = setTimeout(() => { frameCb = null; cb('No response from the connection service, please try again later.'); }, 60000);
        frame.contentWindow.postMessage({action: 'connect', params}, '*');
    }

    function localSearch(host, onPick) {
        const input = h('input', {type: 'text', class: 'grw-badge-input', placeholder: 'Start typing your business name and address', autocomplete: 'off'});
        const list = h('div', {class: 'grw-badge-list', hidden: true});
        host.append(input, list);
        foldSearch = () => { list.hidden = true; };
        input.addEventListener('keydown', e => { if (e.key === 'Escape') list.hidden = true; });
        input.focus();
        let timer = 0, last = '';
        input.addEventListener('input', () => {
            clearTimeout(timer);
            const v = input.value.trim();
            list.hidden = true;
            if (v.length < 3) return;
            if (/^ChIJ\S+$/.test(v)) return pick(v);
            timer = setTimeout(() => {
                if (v === last) { list.hidden = !list.children.length; return; }
                last = v;
                post('grw_place_autocomplete', {input: v}, res => {
                    list.innerHTML = '';
                    const preds = (res && res.predictions) || [];
                    if (!preds.length) return;
                    preds.slice(0, 8).forEach(p => {
                        const f = p.structured_formatting || {};
                        const row = h('a', {href: '#', class: 'grw-badge-row'}, '<b>' + esc(f.main_text || p.description) + '</b><small>' + esc(f.secondary_text || '') + '</small>');
                        row.onclick = ev => { ev.preventDefault(); pick(p.place_id, f.secondary_text || '', f.main_text || ''); };
                        list.append(row);
                    });
                    list.hidden = false;
                });
            }, 700);
        });
        function pick(pid, address, name) {
            list.hidden = true;
            input.value = '';
            last = '';
            post('grw_get_place', {pid, lang: V.lang}, res => {
                if (res.status !== 'success') return toast(errorOf(res) || 'Could not read the place.', 'error');
                const r = res.result;
                onPick({id: pid, name: r.name || name, photo: r.photo, rating: r.rating, total: r.user_ratings_total, address: address || r.address || '', lang: r.country && rpi.Langs ? rpi.Langs.langForCountry(r.country) || V.lang : V.lang});
            });
        }
    }

    /* ------------------------------------------------------------ data */

    function pids() { return state.tiles.filter(t => t.connected).map(t => t.id); }

    // cfg.dataKey adds what else the page's data depends on (the gallery: anonymized photos).
    function loadData(cb, host) {
        state.selected = pids();
        const key = () => pids().join(',') + '|' + langs() + (cfg.dataKey ? '|' + cfg.dataKey() : '');
        const sent = key();
        if (state.data && state.dataPids === sent) return cb();
        const loading = h('p', {class: 'grw-badge-loading'}, 'Loading…');
        (host || body).append(loading);
        const at = visit;
        post(cfg.data, Object.assign({pids: state.selected, langs: langs(), post_id: state.feedId || ''}, cfg.dataFields ? cfg.dataFields() : {}), res => {
            loading.remove();
            // A reply for a set of places that is no longer the one on screen is dropped; the same visit asks again for the new set.
            if (sent !== key()) { if (visit === at) loadData(cb, host); return; }
            if (res.status !== 'success') return toast(errorOf(res) || 'Could not load the preview.', 'error');
            state.data = res.result;
            state.dataPids = sent;
            // After a move to another step the data is kept, but the visit it was asked for is not drawn over the new one.
            if (visit === at) cb();
        });
    }

    function styleVar(name, target) {
        const m = new RegExp('(?:^|;)\\s*' + name + '\\s*:\\s*([^;]+)').exec((target || state.options).style_vars || '');
        return m ? m[1].trim() : '';
    }
    function setStyleVar(name, value, target) {
        target = target || state.options;
        const vars = (target.style_vars || '').split(';').map(s => s.trim()).filter(s => s && s.indexOf(name + ':') !== 0);
        if (value !== '') vars.push(name + ':' + value);
        target.style_vars = vars.length ? vars.join(';') + ';' : '';
    }

    /* ------------------------------------------------------------ step 2: editor */

    // Polaris-like controls over the object o; each change calls redraw.
    function controls(side, o, redraw) {
        const card = (title, open) => {
            const c = h('section', {class: 'grw-pl-card' + (open ? '' : ' grw-pl-closed')});
            const head = h('button', {type: 'button', class: 'grw-pl-head', 'aria-expanded': open ? 'true' : 'false'}, '<span>' + esc(title) + '</span><i></i>');
            const bodyEl = h('div', {class: 'grw-pl-body'});
            head.onclick = () => { const closed = c.classList.toggle('grw-pl-closed'); head.setAttribute('aria-expanded', closed ? 'false' : 'true'); };
            c.append(head, bodyEl);
            side.append(c);
            return bodyEl;
        };
        const row = (label, control, hint) => {
            if (control.classList.contains('grw-pl-seg')) { control.setAttribute('role', 'group'); control.setAttribute('aria-label', label); }
            else if (/^(SELECT|INPUT)$/.test(control.tagName) && !control.getAttribute('aria-label')) control.setAttribute('aria-label', label);
            const r = h('div', {class: 'grw-pl-row'});
            r.append(h('span', {class: 'grw-pl-label'}, esc(label)), control);
            const out = [r];
            if (hint) out.push(h('div', {class: 'grw-pl-hint'}, esc(hint)));
            return out;
        };
        const toggle = (key, label, opts) => {
            opts = opts || {};
            const l = h('label', {class: 'grw-pl-toggle'});
            const c = h('input', {type: 'checkbox'});
            c.checked = opts.get ? opts.get() : !!o[key];
            c.disabled = !!opts.disabled;
            c.onchange = () => { if (opts.set) opts.set(c.checked); else o[key] = c.checked; redraw(key); };
            l.append(c, h('i'), h('span', null, esc(label)));
            if (opts.title) l.title = opts.title;
            return l;
        };
        const select = (key, items, onchange) => {
            const s = h('select', {class: 'grw-pl-select'}, items.map(([v, t]) => '<option value="' + esc(v) + '">' + esc(t) + '</option>').join(''));
            s.value = o[key] || '';
            s.onchange = () => { o[key] = s.value; (onchange || redraw)(key); };
            return s;
        };
        const seg = (key, items, onchange) => {
            const g = h('div', {class: 'grw-pl-seg'});
            items.forEach(([v, t]) => {
                const b = h('button', {type: 'button', class: (o[key] || '') === v ? 'grw-pl-on' : '', 'aria-pressed': String((o[key] || '') === v)}, esc(t));
                b.onclick = () => { o[key] = v; [...g.children].forEach(x => { x.classList.toggle('grw-pl-on', x === b); x.setAttribute('aria-pressed', String(x === b)); }); (onchange || redraw)(key); };
                g.append(b);
            });
            return g;
        };
        const slider = (label, min, max, step, value, unit, onchange) => {
            const box = h('div', {class: 'grw-pl-opt'});
            const top = h('div', {class: 'grw-pl-row'});
            const out = h('span', {class: 'grw-pl-value'}, value + unit);
            top.append(h('span', {class: 'grw-pl-label'}, esc(label)), out);
            const r = h('input', {type: 'range', class: 'grw-pl-slider', min, max, step, 'aria-label': label});
            r.value = value;
            const fill = () => r.style.setProperty('--fill', ((r.value - min) / (max - min) * 100) + '%');
            fill();
            r.oninput = () => { out.textContent = r.value + unit; fill(); onchange(r.value); };
            box.append(top, r);
            return box;
        };
        const color = (value, title, onchange) => {
            colors();
            const c = h('input', {type: 'color', class: 'grw-badge-color', list: 'grw-badge-colors', title});
            c.value = value;
            c.oninput = () => onchange(c.value);
            return c;
        };
        return {card, row, toggle, select, seg, slider, color};
    }

    function colors() {
        if (document.getElementById('grw-badge-colors')) return;
        document.body.append(h('datalist', {id: 'grw-badge-colors'}, ['#fbbc04', '#fb8e28', '#f4b400', '#ea4335', '#34a853', '#1a73e8', '#673ab7', '#e91e63', '#00acc1', '#202124', '#5f6368', '#ffffff'].map(c => '<option value="' + c + '"></option>').join('')));
    }
    W.colors = colors;

    const ICONS = {
        desktop: '<path fill-rule="evenodd" d="M3.5 6.25a2.75 2.75 0 0 1 2.75-2.75h7.5a2.75 2.75 0 0 1 2.75 2.75v4.5a2.75 2.75 0 0 1-2.75 2.75h-1.25v1.5h.75a.75.75 0 0 1 0 1.5h-6.5a.75.75 0 0 1 0-1.5h.75v-1.5h-1.25a2.75 2.75 0 0 1-2.75-2.75v-4.5Zm5.5 7.25h2v1.5h-2v-1.5Zm-2.75-8.5c-.69 0-1.25.56-1.25 1.25v3.25h10v-3.25c0-.69-.56-1.25-1.25-1.25h-7.5Zm8.725 6c-.116.57-.62 1-1.225 1h-7.5a1.25 1.25 0 0 1-1.225-1h9.95Z"/>',
        tablet: '<path d="M8.5 13a.75.75 0 0 0 0 1.5h3a.75.75 0 0 0 0-1.5h-3Z"/><path fill-rule="evenodd" d="M6.75 3a2.75 2.75 0 0 0-2.75 2.75v8.5a2.75 2.75 0 0 0 2.75 2.75h6.5a2.75 2.75 0 0 0 2.75-2.75v-8.5a2.75 2.75 0 0 0-2.75-2.75h-6.5Zm-1.25 2.75c0-.69.56-1.25 1.25-1.25h6.5c.69 0 1.25.56 1.25 1.25v8.5c0 .69-.56 1.25-1.25 1.25h-6.5c-.69 0-1.25-.56-1.25-1.25v-8.5Z"/>',
        mobile: '<path d="M7.75 13.75a.75.75 0 0 1 .75-.75h3a.75.75 0 0 1 0 1.5h-3a.75.75 0 0 1-.75-.75Z"/><path fill-rule="evenodd" d="M4.75 5.75a2.75 2.75 0 0 1 2.75-2.75h5a2.75 2.75 0 0 1 2.75 2.75v8.5a2.75 2.75 0 0 1-2.75 2.75h-5a2.75 2.75 0 0 1-2.75-2.75v-8.5Zm2.75-1.25c-.69 0-1.25.56-1.25 1.25v8.5c0 .69.56 1.25 1.25 1.25h5c.69 0 1.25-.56 1.25-1.25v-8.5c0-.69-.56-1.25-1.25-1.25h-.531a1 1 0 0 1-.969.75h-2a1 1 0 0 1-.969-.75h-.531Z"/>'
    };

    // cfg.editor(ctx) fills the settings column and returns the function that draws the preview into ctx.stage.
    function renderEditor() {
        const wrap = h('div', {class: 'grw-badge-editor grw-badge-section'});
        const cols = h('div', {class: 'grw-badge-cols'});
        const main = h('div', {class: 'grw-badge-main-col'});
        const side = h('aside', {class: 'grw-pl-side'});
        cols.append(main, side);
        wrap.append(cols);

        let draw = () => {};
        const devices = h('div', {class: 'grw-badge-devices', role: 'group', 'aria-label': 'Preview width'});
        const stage = h('div', {class: 'grw-badge-stage grw-badge-embed-stage' + (cfg.stageClass ? ' ' + cfg.stageClass : ''), 'data-device': state.device});
        Object.keys(ICONS).forEach(d => {
            const b = h('button', {type: 'button', class: d === state.device ? 'grw-on' : '', 'aria-label': d.charAt(0).toUpperCase() + d.slice(1) + ' preview'}, '<svg viewBox="0 0 20 20" width="20" height="20" fill="currentColor">' + ICONS[d] + '</svg>');
            b.onclick = () => { state.device = d; [...devices.children].forEach(x => x.classList.toggle('grw-on', x === b)); stage.dataset.device = d; draw('device'); };
            devices.append(b);
        });
        const stageHead = h('div', {class: 'grw-badge-stage-head'});
        stageHead.append(h('h2', null, val('previewTitle')), devices);
        cols.prepend(stageHead);
        if (!state.selected.length) main.append(h('p', {class: 'description grw-badge-sample'}, '<span class="dashicons dashicons-info-outline grw-badge-note-ico"></span><b>Sample data.</b> <a href="#" class="grw-badge-find">Find your place</a> to see the ' + esc(val('noun')) + ' with your real rating and reviews.'));
        main.append(stage);

        draw = cfg.editor({stage, side, ui: controls(side, state.options, k => draw(k))}, W);

        const next = h('button', {type: 'button', class: 'button button-primary button-hero grw-pl-next'}, state.feedId ? 'Save and continue' : 'Continue to publish');
        next.onclick = publish;
        const links = h('p', {class: 'grw-pl-links'}, '<a href="#" class="grw-badge-back">Another preset</a> · <a href="#" class="grw-badge-reset">Reset changes</a>');
        links.querySelector('.grw-badge-reset').onclick = e => { e.preventDefault(); if (!confirm('Discard all your changes to this design?')) return; state.options =Object.assign({}, state.preset || val('defaults')); if (cfg.reset) cfg.reset(); body.innerHTML = ''; renderEditor(); };
        // Only navigates: steps 2 and 3 stay reachable from the indicator with these options.
        links.querySelector('.grw-badge-back').onclick = e => { e.preventDefault(); go(1); };
        const foot = h('div', {class: 'grw-pl-foot'});
        foot.append(next, links);
        side.append(foot);

        body.append(wrap);
        draw();
    }

    /* ------------------------------------------------------------ step 3: publish */

    function renderPublish() {
        const wrap = h('div', {class: 'grw-badge-publish grw-badge-section'});
        // A page with its own result screen draws it whole; cfg.result returns false to leave it to this one. A saved widget keeps
        // its result screen when its places were removed meanwhile.
        if ((state.selected.length || state.feedId) && cfg.result && cfg.result(wrap, W)) {
            body.append(wrap);
            return;
        }
        wrap.append(h('h2', null, 'Publish'));
        if (!state.feedId && !state.saving) {
            wrap.append(h('div', {class: 'notice notice-warning inline'}, '<p>The ' + esc(val('noun')) + ' is on sample data. <a href="#" class="grw-badge-to1">Find your place</a> to publish it.</p>'));
            wrap.querySelector('.grw-badge-to1').onclick = e => { e.preventDefault(); go(1); };
            body.append(wrap);
            return;
        }
        const row = h('div', {class: 'grw-badge-install grw-badge-card-block'});
        const sc = state.feedId && !state.saving ? '[grw id=' + state.feedId + ']' : 'saving…';
        const text = h('p', {class: 'grw-badge-install-or'}, cfg.publish(row, W)
            + '<input type="text" class="grw-badge-sc" readonly value="' + esc(sc) + '"> or the Google Reviews block.');
        text.querySelector('.grw-badge-sc').onclick = function() { this.select(); document.execCommand('copy'); toast('Shortcode copied.'); };
        row.append(text);
        if (cfg.publishAfter) cfg.publishAfter(row, W);
        wrap.append(row);
        body.append(wrap);
    }

    if (cfg.init) cfg.init(W);
    if (state.feedId) state.saved = snapshot();
    go(state.step);
    return W;
}
