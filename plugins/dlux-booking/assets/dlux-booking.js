/* Dlux Booking widgets: [dlux_booking] [dlux_menu] [dlux_confirmation]
 * Talks only to this site's REST API (/wp-json/dlux/v1/). No other dependencies. */
(function () {
  'use strict';
  var C = window.DLUX_CFG || {};

  /* ---------- helpers ---------- */
  function esc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }
  function money(n, cents) { n = Number(n) || 0; return '$' + (cents || n % 1 ? n.toFixed(2) : String(n)); }
  function pad(n) { return String(n).padStart(2, '0'); }
  function ymd(d) { return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()); }
  function addDays(s, n) { var d = new Date(s + 'T12:00:00'); d.setDate(d.getDate() + n); return ymd(d); }
  function fmtDayLong(s) { return new Date(s + 'T12:00:00').toLocaleDateString('en-AU', { weekday: 'long', day: 'numeric', month: 'long' }); }
  function fmtT(t) { var p = t.split(':').map(Number), h = p[0], m = p[1]; var ap = h >= 12 ? 'pm' : 'am'; h = h % 12 || 12; return h + (m ? ':' + pad(m) : '') + ' ' + ap; }
  function qs(k) { return new URLSearchParams(location.search).get(k); }
  function slug(s) { return String(s || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, ''); }
  function withParams(url, params) { var u = new URL(url, location.href); Object.keys(params).forEach(function (k) { u.searchParams.set(k, params[k]); }); return u.toString(); }
  function cleanUrl() { return location.href.split('?')[0]; }
  function callUs() { return C.phone ? ' Please try again, or call us on ' + C.phone + '.' : ' Please try again, or call us.'; }

  function api(path, body) {
    var base = C.api || '/wp-json/dlux/v1/';
    var opts = { method: body ? 'POST' : 'GET', headers: {}, credentials: 'same-origin', cache: 'no-store' };
    if (body) { opts.headers['Content-Type'] = 'application/json'; opts.body = JSON.stringify(body); }
    var url = base.replace(/\/?$/, '/') + path;
    // Sites without pretty permalinks use ?rest_route=, so join the query correctly.
    if (url.indexOf('?') !== url.lastIndexOf('?')) { var i = url.lastIndexOf('?'); url = url.slice(0, i) + '&' + url.slice(i + 1); }
    return fetch(url, opts).then(function (r) {
      return r.json().catch(function () { return {}; }).then(function (d) {
        if (!r.ok) {
          if (window.console) console.error('[Dlux] ' + url + ' -> ' + r.status, d);
          var e = new Error((d && (d.error || d.message)) || ('Something went wrong.' + callUs()));
          e.status = r.status; throw e;
        }
        return d;
      });
    }).catch(function (e) {
      if (e.status) throw e;
      throw new Error('We couldn\u2019t connect. Check your internet and try again.');
    });
  }

  var menuP = null;
  function getMenu() { if (!menuP) menuP = api('menu').catch(function (e) { menuP = null; throw e; }); return menuP; }
  function errBox(msg) { return '<div class="dlux-err" role="alert">' + esc(msg) + '</div>'; }
  function badge(p) { return p.featured ? '<span class="dlux-badge">' + esc((p.badge || 'Most popular').toUpperCase()) + '</span>' : ''; }
  var ICON_CAL = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"></rect><path d="M3 10h18M8 3v4M16 3v4"></path></svg>';

  /* ---------- shared menu helpers ---------- */
  function allPackages(M) { var out = []; M.groups.forEach(function (g) { out = out.concat(g.packages); }); return out; }
  function groupById(M, id) { for (var i = 0; i < M.groups.length; i++) if (M.groups[i].id === id) return M.groups[i]; return null; }
  // Vehicle sizes that at least one package in this group is offered for, in menu order.
  function sizesFor(M, g) { return M.classes.filter(function (c) { return g && g.packages.some(function (p) { return p.prices[c] != null; }); }); }
  function mins(p) { return p.mins ? '<span class="dlux-pkg-d">\u23F1 ' + p.mins + ' mins</span>' : ''; }
  function features(p) { return p.features && p.features.length ? '<ul class="dlux-pkg-f">' + p.features.map(function (x) { return '<li>' + esc(x) + '</li>'; }).join('') + '</ul>' : ''; }
  function groupTabs(M, cur) {
    if (M.groups.length < 2) return '';
    return '<div class="dlux-modes"><div class="dlux-seg" role="tablist">' + M.groups.map(function (g) {
      return '<button type="button" role="tab" data-a="grp" data-v="' + esc(g.id) + '" class="' + (g.id === cur ? 'on' : '') + '" aria-selected="' + (g.id === cur) + '">' + esc(g.label) + '</button>';
    }).join('') + '</div></div>';
  }
  function sizePills(sizes, cur, attr) {
    if (sizes.length < 2) return '';
    return '<div class="dlux-pills">' + sizes.map(function (c) { return '<button type="button" class="dlux-pill' + (c === cur ? ' on' : '') + '" ' + attr + '="' + esc(c) + '" aria-pressed="' + (c === cur) + '">' + esc(c) + '</button>'; }).join('') + '</div>';
  }

  /* ---------- [dlux_booking] ---------- */
  function Booking(el) {
    var M = null;
    var today = ymd(new Date());
    var S = {
      step: 1, grp: '', cls: '', pkg: null, ups: [], day: today, slot: null, slots: null, slotsErr: '',
      opt: '', // chosen "Choose from" option (text)
      lockCls: false, lockPkg: false, // arrived from the website with a package/size chosen → show only that, with "Change"
      busy: false, err: '', notice: '',
      f: { name: '', phone: '', email: '', rego: '', vehicle: '', notes: '', channel: '', consent: true, website: '' },
      pay: C.payCard === false && C.payShop ? 'shop' : 'card' // 'card' (Stripe) or 'shop' (pay at the shop)
    };
    try { var saved = JSON.parse(sessionStorage.getItem('dlux:f') || 'null'); if (saved) { S.f = Object.assign(S.f, saved); S.f.website = ''; } } catch (e) { /* ignore */ }

    // Came back from Stripe without paying: release the time slot.
    if (qs('cancelled')) {
      S.notice = 'Your payment wasn\u2019t completed, so that booking isn\u2019t confirmed. You can book again below.';
      if (qs('booking') && qs('t')) api('booking-cancel', { id: qs('booking'), t: qs('t') }).catch(function () { /* the hourly clean-up will do it */ });
      try { history.replaceState(null, '', cleanUrl()); } catch (e) { /* ignore */ }
    }

    function grp() { return groupById(M, S.grp); }
    function sizes() { return sizesFor(M, grp()); }
    function pkgObj() { var g = grp(); return g ? g.packages.find(function (p) { return p.id === S.pkg; }) : null; }
    function offered(p) { return p.prices[S.cls] != null; }
    function extrasChosen() { var p = pkgObj(); return p ? p.extras.filter(function (x) { return S.ups.indexOf(x.id) >= 0; }) : []; }
    function total() { var p = pkgObj(); return p ? extrasChosen().reduce(function (t, x) { return t + Number(x.price || 0); }, Number(p.prices[S.cls] || 0)) : 0; }

    // Keep size / package / extras valid after any change.
    function settle() {
      var sz = sizes();
      if (sz.indexOf(S.cls) < 0) S.cls = sz[0] || '';
      var p = pkgObj();
      if (p && !offered(p)) { S.pkg = null; p = null; }
      if (!S.pkg) { S.lockPkg = false; S.opt = ''; }
      S.ups = p ? S.ups.filter(function (id) { return p.extras.some(function (x) { return x.id === id; }); }) : [];
    }

    function loadSlots() {
      var day = S.day; S.slots = null; S.slotsErr = ''; render();
      api('availability?day=' + encodeURIComponent(day)).then(function (d) {
        if (day !== S.day) return;
        S.slots = d.slots || [];
        if (S.slot && !S.slots.some(function (s) { return s.time === S.slot && s.free > 0; })) S.slot = null;
        render();
      }, function (e) { S.slotsErr = e.message; S.slots = []; render(); });
    }

    // "9:00 AM – 10:00 AM" from the loaded slots, else the start time.
    function slotLabel() {
      var s = (S.slots || []).find(function (x) { return x.time === S.slot; });
      return s ? s.label : fmtT(S.slot);
    }

    function summary(btn) {
      var p = pkgObj();
      return '<aside class="dlux-sum"><h3>Your booking</h3>' +
        (p ? '<div class="dlux-row"><span><b>' + esc(p.name) + '</b><br><small>' + esc(S.cls) + (M.groups.length > 1 ? ' \u00b7 ' + esc(grp().label) : '') + '</small>' + (S.opt ? '<br><small>\u2713 ' + esc(S.opt) + '</small>' : '') + '</span><b>' + money(p.prices[S.cls]) + '</b></div>' : '<p class="dlux-muted">Choose a package to start.</p>') +
        extrasChosen().map(function (x) { return '<div class="dlux-row dlux-small"><span>' + esc(x.name) + '</span><span>' + money(x.price) + '</span></div>'; }).join('') +
        '<div class="dlux-when">' + ICON_CAL + '<span>' + esc(fmtDayLong(S.day)) + (S.slot ? ', ' + esc(slotLabel()) : ' \u00b7 choose a time') + '</span></div>' +
        '<div class="dlux-total"><span>Total</span><b>' + money(total(), true) + '</b></div>' +
        '<p class="dlux-muted dlux-small">Includes GST. ' + (S.pay === 'shop' ? 'Pay at the shop on the day.' : 'Pay securely online by card.') + '</p>' + btn + '</aside>';
    }

    function choices(p) { return p && Array.isArray(p.choose) ? p.choose : []; }
    function optOk() { var p = pkgObj(); return !choices(p).length || choices(p).indexOf(S.opt) >= 0; }

    function step1() {
      var days = []; var max = Math.min(M.maxDaysAhead || 30, 90);
      for (var i = 0; i <= max; i++) { var d = addDays(today, i), dt = new Date(d + 'T12:00:00'); days.push({ d: d, wk: i === 0 ? 'Today' : dt.toLocaleDateString('en-AU', { weekday: 'short' }), n: dt.getDate(), m: dt.toLocaleDateString('en-AU', { month: 'short' }) }); }
      var slotsHtml = S.slots === null ? '<p class="dlux-muted">Loading times\u2026</p>' :
        S.slotsErr ? errBox(S.slotsErr) :
        !S.slots.length ? '<p class="dlux-muted">No times available on this day. Please try another day.</p>' :
        '<div class="dlux-slots">' + S.slots.map(function (s) {
          var full = s.free < 1;
          return '<button type="button" class="dlux-slot' + (S.slot === s.time ? ' on' : '') + '" data-a="slot" data-v="' + esc(s.time) + '"' + (full ? ' disabled' : '') + ' aria-pressed="' + (S.slot === s.time) + '">' + (full ? 'Full' : esc(s.label)) + '</button>';
        }).join('') + '</div>';
      var g = grp(), p = pkgObj();
      var pkgs = g.packages.filter(offered);
      var sz = sizes();
      var change = function (what) { return ' <button type="button" class="dlux-change" data-a="change" data-v="' + what + '">Change</button>'; };
      if (S.lockPkg && p) pkgs = [p];                          // only the chosen package
      var szShown = S.lockCls && S.cls ? [S.cls] : sz;          // only the chosen size
      return '<div class="dlux-card dlux-flex">' +
        '<div class="dlux-main">' +
        (S.notice ? '<div class="dlux-note">' + esc(S.notice) + '</div>' : '') +
        steps(1) +
        (M.groups.length > 1 ? '<section><h3>What would you like?</h3>' + groupTabs(M, S.grp) + '</section>' : '') +
        (sz.length > 1 ? '<section><h3>Your vehicle' + (S.lockCls ? change('cls') : '') + '</h3>' +
          (S.lockCls ? '<div class="dlux-pills"><button type="button" class="dlux-pill on" aria-pressed="true" data-a="change" data-v="cls">' + esc(S.cls) + '</button></div>' : sizePills(szShown, S.cls, 'data-a="cls" data-v')) + '</section>' : '') +
        '<section><h3>' + (S.lockPkg && p ? 'Your package' + change('pkg') : 'Choose a package') + '</h3>' + (pkgs.length ? '<div class="dlux-pkgs">' + pkgs.map(function (x) {
          var on = x.id === S.pkg;
          return '<button type="button" class="dlux-pkg' + (on ? ' on' : '') + '" data-a="pkg" data-v="' + esc(x.id) + '" aria-pressed="' + on + '">' + badge(x) +
            '<span class="dlux-pkg-n">' + esc(x.name) + '</span><span class="dlux-pkg-p">' + money(x.prices[S.cls]) + '</span>' +
            (x.desc ? '<span class="dlux-pkg-d">' + esc(x.desc) + '</span>' : '') + mins(x) + '</button>';
        }).join('') + '</div>' : '<p class="dlux-muted">Nothing can be booked online for this vehicle size. Please call us.</p>') + '</section>' +
        (p && choices(p).length ? '<section><h3>' + esc(p.choose_title || 'Choose one') + '</h3><div class="dlux-chips">' + choices(p).map(function (c) {
          var on = S.opt === c;
          return '<button type="button" class="dlux-chip' + (on ? ' on' : '') + '" data-a="opt" data-v="' + esc(c) + '" aria-pressed="' + on + '">' + (on ? '\u2713 ' : '') + esc(c) + '</button>';
        }).join('') + '</div></section>' : '') +
        (p && p.extras.length ? '<section><h3>Add extras <small>(optional)</small></h3><div class="dlux-chips">' + p.extras.map(function (x) {
          var on = S.ups.indexOf(x.id) >= 0;
          return '<button type="button" class="dlux-chip' + (on ? ' on' : '') + '" data-a="up" data-v="' + esc(x.id) + '" aria-pressed="' + on + '">' + (on ? '\u2713 ' : '+ ') + esc(x.name) + ' <b>' + money(x.price) + '</b></button>';
        }).join('') + '</div></section>' : '') +
        '<section><h3>Pick a time</h3><div class="dlux-days">' + days.map(function (x) {
          return '<button type="button" class="dlux-day' + (x.d === S.day ? ' on' : '') + '" data-a="day" data-v="' + x.d + '" aria-pressed="' + (x.d === S.day) + '"><small>' + esc(x.wk) + '</small><b>' + x.n + '</b><small>' + esc(x.m) + '</small></button>';
        }).join('') + '</div>' + slotsHtml + '</section>' +
        '</div>' + summary('<button type="button" class="dlux-btn" data-a="next"' + (S.pkg && S.slot && optOk() ? '' : ' disabled') + '>' + (S.pkg && !optOk() ? 'Choose an option' : 'Continue') + '</button>') + '</div>';
    }

    function field(id, label, type, extra) {
      return '<label class="dlux-f" for="dlux-' + id + '"><span>' + label + '</span><input id="dlux-' + id + '" type="' + (type || 'text') + '" data-f="' + id + '" value="' + esc(S.f[id]) + '" ' + (extra || '') + '></label>';
    }
    function payOptions() {
      var opts = [];
      if (C.payCard !== false) opts.push(['card', 'Pay now by card', 'Card, Apple Pay or Google Pay on a secure Stripe page.']);
      if (C.payShop) opts.push(['shop', 'Pay at the shop', 'Pay by card or cash when you pick up your car.']);
      if (opts.length && !opts.some(function (o) { return o[0] === S.pay; })) S.pay = opts[0][0];
      return opts.map(function (o) {
        var on = S.pay === o[0];
        return '<button type="button" class="dlux-radio' + (on ? ' on' : '') + '" data-a="pay" data-v="' + o[0] + '" aria-pressed="' + on + '"><span><b>' + o[1] + '</b><br><small>' + o[2] + '</small></span></button>';
      }).join('');
    }
    function step2() {
      var CH = ['Google search', 'Google Maps', 'Instagram', 'Facebook', 'TikTok', 'Friend or family', 'Drove past', 'Flyer or letterbox', 'Returning customer', 'Other'];
      return '<div class="dlux-card dlux-flex"><div class="dlux-main">' +
        '<button type="button" class="dlux-link" data-a="back">\u2190 Back to package &amp; time</button>' + steps(2) +
        '<section><h3>You and your car</h3>' +
        '<label class="dlux-f" for="dlux-rego"><span>Rego</span><input id="dlux-rego" class="dlux-plate" data-f="rego" value="' + esc(S.f.rego) + '" placeholder="ABC123" maxlength="10" autocomplete="off" required></label>' +
        '<div class="dlux-grid2">' + field('name', 'Name', 'text', 'autocomplete="name" required') + field('phone', 'Mobile', 'tel', 'autocomplete="tel" required') +
        field('email', 'Email <small>(for your confirmation &amp; receipt)</small>', 'email', 'autocomplete="email" required') + field('vehicle', 'Make, model &amp; colour', 'text', 'placeholder="e.g. White Mazda CX-5"') + '</div>' +
        '<label class="dlux-f" for="dlux-notes"><span>Anything we should know? <small>Optional</small></span><textarea id="dlux-notes" data-f="notes" rows="3" maxlength="1000" placeholder="e.g. dog hair, child seats">' + esc(S.f.notes) + '</textarea></label>' +
        '<label class="dlux-f" for="dlux-channel"><span>How did you find us?</span><select id="dlux-channel" data-f="channel"><option value="">Choose one</option>' + CH.map(function (c) { return '<option' + (S.f.channel === c ? ' selected' : '') + '>' + esc(c) + '</option>'; }).join('') + '</select></label>' +
        '<label class="dlux-hp" aria-hidden="true">Website <input type="text" data-f="website" tabindex="-1" autocomplete="off"></label>' +
        '<label class="dlux-check"><input type="checkbox" data-f="consent"' + (S.f.consent ? ' checked' : '') + '> Send me occasional offers by text or email (unsubscribe any time)</label></section>' +
        '<section><h3>Payment</h3>' + payOptions() + '</section>' + (S.err ? errBox(S.err) : '') + '</div>' +
        summary('<button type="button" class="dlux-btn" data-a="submit"' + (S.busy ? ' disabled' : '') + '>' + (S.busy ? 'Please wait\u2026' : (S.pay === 'shop' ? 'Confirm booking' : 'Continue to payment')) + '</button>') + '</div>';
    }
    function steps(n) {
      var L = ['Package &amp; time', 'Your details', 'Confirmed'];
      return '<ol class="dlux-steps">' + L.map(function (l, i) { return '<li class="' + (i + 1 < n ? 'done' : i + 1 === n ? 'on' : '') + '">' + (i + 1 < n ? '\u2713' : i + 1) + ' ' + l + '</li>'; }).join('') + '</ol>';
    }
    function render() { if (!M) return; el.innerHTML = S.step === 1 ? step1() : step2(); }

    function submit() {
      var f = S.f;
      var digits = String(f.phone).replace(/\D/g, '');
      if (String(f.rego).replace(/[^a-z0-9]/gi, '').length < 2) S.err = 'Please enter your rego.';
      else if (!f.name.trim()) S.err = 'Please enter your name.';
      else if (digits.length < 8) S.err = 'Please enter your mobile number.';
      else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(f.email.trim())) S.err = 'Please enter a valid email address.';
      else S.err = '';
      if (S.err) return render();
      S.busy = true; render();
      try { var keep = Object.assign({}, f); delete keep.website; sessionStorage.setItem('dlux:f', JSON.stringify(keep)); } catch (e) { /* ignore */ }
      var p = pkgObj();
      api('booking', {
        day: S.day, time: S.slot, cls: S.cls, package: p ? p.id : '', option: S.opt, extras: S.ups.slice(),
        name: f.name.trim(), phone: f.phone.trim(), email: f.email.trim(), rego: f.rego, vehicle: f.vehicle.trim(), notes: f.notes.trim(),
        channel: f.channel, consent: !!f.consent, website: f.website, pay: S.pay
      }).then(function (d) {
        if (d.checkoutUrl) { location.href = d.checkoutUrl; return; }
        if (d.confirmUrl) { location.href = d.confirmUrl; return; }
        S.busy = false; S.err = 'Something went wrong.' + callUs(); render();
      }, function (e) {
        S.busy = false; S.err = e.message;
        if (e.status === 409) { S.step = 1; S.slot = null; S.notice = e.message; loadSlots(); return; }
        render();
      });
    }

    el.addEventListener('click', function (e) {
      var b = e.target.closest('[data-a]'); if (!b || !el.contains(b) || b.disabled) return;
      var a = b.getAttribute('data-a'), v = b.getAttribute('data-v');
      if (a === 'change') { if (v === 'cls') S.lockCls = false; else S.lockPkg = false; render(); }
      else if (a === 'grp') { if (v !== S.grp) { S.grp = v; S.pkg = null; S.ups = []; S.lockPkg = false; S.lockCls = false; settle(); } render(); }
      else if (a === 'cls') { S.cls = v; settle(); render(); }
      else if (a === 'pkg') { if (v !== S.pkg) { S.pkg = v; S.opt = ''; settle(); } render(); }
      else if (a === 'opt') { S.opt = v; render(); }
      else if (a === 'up') { var i = S.ups.indexOf(v); if (i >= 0) S.ups.splice(i, 1); else S.ups.push(v); render(); }
      else if (a === 'day') { S.day = v; S.slot = null; loadSlots(); }
      else if (a === 'slot') { S.slot = v; render(); }
      else if (a === 'pay') { S.pay = v === 'shop' ? 'shop' : 'card'; render(); }
      else if (a === 'next') { S.step = 2; S.notice = ''; render(); el.scrollIntoView({ behavior: 'smooth', block: 'start' }); }
      else if (a === 'back') { S.step = 1; S.err = ''; render(); }
      else if (a === 'submit') { submit(); }
    });
    el.addEventListener('input', function (e) {
      var k = e.target.getAttribute('data-f'); if (!k) return;
      S.f[k] = e.target.type === 'checkbox' ? e.target.checked : e.target.value;
      if (k === 'rego') e.target.value = e.target.value.toUpperCase();
    });
    el.addEventListener('change', function (e) {
      var k = e.target.getAttribute('data-f'); if (k) S.f[k] = e.target.type === 'checkbox' ? e.target.checked : e.target.value;
    });

    getMenu().then(function (m) {
      M = m;
      if (/^\d{4}-\d{2}-\d{2}$/.test(M.today || '')) { today = M.today; S.day = today; }
      if (!M.groups || !M.groups.length) { el.innerHTML = '<div class="dlux-card"><p>Online booking isn\u2019t available right now.' + (C.phone ? ' Please call us on ' + esc(C.phone) + ' to book.' : ' Please call us to book.') + '</p></div>'; return; }
      // Deep links: ?service=detail  ?package=<id or name>  ?size=<size>  ?extras=<id or name>,<id or name>
      var want = slug(qs('package')), size = slug(qs('size')), svc = slug(qs('service'));
      var wantEx = String(qs('extras') || '').split(',').map(slug).filter(Boolean);
      S.grp = M.groups[0].id;
      M.groups.forEach(function (g) { if (svc && (slug(g.id) === svc || slug(g.label) === svc)) S.grp = g.id; });
      var found = null;
      if (want) allPackages(M).forEach(function (p) { if (!found && (p.id === want || slug(p.name) === want)) found = p; });
      if (found) S.grp = found.group;
      if (size) sizesFor(M, grp()).forEach(function (c) { if (slug(c) === size) S.cls = c; });
      settle();
      if (found && found.prices[S.cls] != null) S.pkg = found.id;
      // Came from the website with a choice made: show just that package / size, with a "Change" link.
      S.lockPkg = !!S.pkg;
      S.lockCls = !!(size && sizesFor(M, grp()).some(function (c) { return slug(c) === size; }));
      var wantOpt = String(qs('option') || '');
      if (S.pkg && wantOpt) {
        var cs = choices(pkgObj());
        S.opt = /^\d+$/.test(wantOpt) ? (cs[Number(wantOpt) - 1] || '') : (cs.filter(function (c) { return slug(c) === slug(wantOpt); })[0] || '');
      }
      if (S.pkg && wantEx.length) {
        var chosen = pkgObj();
        S.ups = chosen ? chosen.extras.filter(function (x) { return wantEx.indexOf(slug(x.id)) >= 0 || wantEx.indexOf(slug(x.name)) >= 0; }).map(function (x) { return x.id; }) : [];
      }
      loadSlots();
    }, function (e) { el.innerHTML = errBox(e.message); });
  }

  /* ---------- [dlux_menu] ---------- */
  function Menu(el) {
    var S = { grp: '', cls: '' };
    var M = null;
    function render() {
      var g = groupById(M, S.grp), sz = sizesFor(M, g);
      if (sz.indexOf(S.cls) < 0) S.cls = sz[0] || '';
      el.innerHTML = '<div class="dlux-card">' + groupTabs(M, S.grp) +
        (sz.length > 1 ? '<div style="margin-bottom:18px">' + sizePills(sz, S.cls, 'data-c') + '</div>' : '') +
        '<div class="dlux-pkgs">' + g.packages.filter(function (p) { return p.prices[S.cls] != null; }).map(function (p) {
          var href = C.bookUrl ? withParams(C.bookUrl, { package: p.id, size: S.cls }) : '';
          var own = g.extras.length ? '' : (p.extras.length ? '<span class="dlux-pkg-d"><b>Extras:</b> ' + p.extras.map(function (x) { return esc(x.name) + ' ' + money(x.price); }).join(', ') + '</span>' : '');
          return '<div class="dlux-pkg dlux-static">' + badge(p) +
            '<span class="dlux-pkg-n">' + esc(p.name) + '</span><span class="dlux-pkg-p">' + money(p.prices[S.cls]) + '</span>' +
            (p.desc ? '<span class="dlux-pkg-d">' + esc(p.desc) + '</span>' : '') + mins(p) + features(p) + own +
            (href ? '<a class="dlux-btn dlux-btn-sm" href="' + esc(href) + '">Book ' + esc(p.name) + '</a>' : '') + '</div>';
        }).join('') + '</div>' +
        (g.extras.length ? '<h3 style="margin-top:28px">Extras</h3><div class="dlux-uplist">' + g.extras.map(function (x) { return '<div><span>' + esc(x.name) + '</span><b>' + money(x.price) + '</b></div>'; }).join('') + '</div>' : '') +
        '<p class="dlux-muted dlux-small" style="margin-top:14px">Prices include GST.</p></div>';
    }
    el.addEventListener('click', function (e) {
      var c = e.target.closest('[data-c]'); if (c) { S.cls = c.getAttribute('data-c'); render(); return; }
      var t = e.target.closest('[data-a="grp"]'); if (t) { S.grp = t.getAttribute('data-v'); render(); }
    });
    getMenu().then(function (m) {
      M = m;
      if (!M.groups || !M.groups.length) { el.innerHTML = '<div class="dlux-card"><p>Prices coming soon.</p></div>'; return; }
      S.grp = M.groups[0].id; render();
    }, function (e) { el.innerHTML = errBox(e.message); });
  }

  /* ---------- [dlux_confirmation] ---------- */
  function Confirm(el) {
    var id = qs('booking'), t = qs('t');
    if (!id || !t) { el.innerHTML = '<div class="dlux-card"><p>No booking to show.' + (C.bookUrl ? ' <a href="' + esc(C.bookUrl) + '">Book a wash</a>' : '') + '</p></div>'; return; }
    try { sessionStorage.removeItem('dlux:f'); } catch (e) { /* ignore */ }
    var tries = 0;
    function ics(b) {
      var s = b.day.replace(/-/g, '') + 'T' + b.time.replace(':', '') + '00';
      var end = new Date(b.day + 'T' + b.time + ':00'); end.setMinutes(end.getMinutes() + (Number(b.mins) || 60));
      var e = ymd(end).replace(/-/g, '') + 'T' + pad(end.getHours()) + pad(end.getMinutes()) + '00';
      var txt = ['BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//Dlux//Booking//EN', 'BEGIN:VEVENT', 'UID:' + id + '@dlux', 'DTSTAMP:' + new Date().toISOString().replace(/[-:]/g, '').slice(0, 15) + 'Z', 'DTSTART:' + s, 'DTEND:' + e,
        'SUMMARY:' + (b.business.name || 'Dlux') + ': ' + b.items.map(function (i) { return i.name; }).join(', '), 'LOCATION:' + (b.business.address || ''), 'END:VEVENT', 'END:VCALENDAR'].join('\r\n');
      return URL.createObjectURL(new Blob([txt], { type: 'text/calendar' }));
    }
    function show(b) {
      if (b.status === 'cancelled' && b.payment !== 'paid') { el.innerHTML = '<div class="dlux-card">' + errBox('This booking isn\u2019t confirmed (the payment wasn\u2019t completed). Please book again, or call us.') + (C.bookUrl ? '<a class="dlux-btn" href="' + esc(C.bookUrl) + '">Book again</a>' : '') + '</div>'; return; }
      var paid = b.payment === 'paid', pending = b.payment === 'pending';
      var addr = b.business.address ? 'https://www.google.com/maps/search/?api=1&query=' + encodeURIComponent(b.business.name + ' ' + b.business.address) : '';
      el.innerHTML = '<div class="dlux-card dlux-done"><div class="dlux-tick">\u2713</div><h2>You\u2019re booked in' + (b.firstName ? ', ' + esc(b.firstName) : '') + '</h2>' +
        '<p>' + (pending ? 'We\u2019re just confirming your payment. ' : 'A confirmation is on its way to your email. ') + 'See you soon!</p>' +
        '<div class="dlux-plate dlux-plate-show">' + esc(b.rego) + '</div>' +
        '<div class="dlux-facts"><div><small>WHEN</small><b>' + esc(b.when) + '</b></div><div><small>PACKAGE</small><b>' + esc(b.items.map(function (i) { return i.name; }).join(' + ')) + '</b></div>' +
        '<div><small>' + (paid ? 'PAID' : pending ? 'PAYMENT' : 'TO PAY AT THE SHOP') + '</small><b>' + money(b.total, true) + (paid ? ' by card' : pending ? ' processing' : '') + '</b></div>' +
        (b.business.address ? '<div><small>WHERE</small><b>' + esc(b.business.address) + '</b></div>' : '') + '</div>' +
        '<div class="dlux-actions"><a class="dlux-btn dlux-btn-dark" href="' + ics(b) + '" download="dlux-booking.ics">Add to calendar</a>' + (addr ? '<a class="dlux-btn dlux-btn-line" href="' + addr + '" target="_blank" rel="noopener">Get directions</a>' : '') + (C.bookUrl ? '<a class="dlux-btn dlux-btn-line" href="' + esc(C.bookUrl) + '">Book another car</a>' : '') + '</div>' +
        (b.business.phone ? '<p class="dlux-muted dlux-small">Need to change it? Call ' + esc(b.business.phone) + '.</p>' : '') + '</div>';
      if (pending && tries < 12) { tries++; setTimeout(load, 2500); }
    }
    function load() { api('booking-status?id=' + encodeURIComponent(id) + '&t=' + encodeURIComponent(t)).then(show, function (e) { el.innerHTML = errBox(e.status === 404 ? 'We couldn\u2019t find that booking. Please call us.' : e.message); }); }
    load();
  }

  function boot() {
    var W = { booking: Booking, menu: Menu, confirmation: Confirm };
    Array.prototype.forEach.call(document.querySelectorAll('.dlux-w[data-dlux]'), function (el) {
      if (el.__dlux) return; el.__dlux = true;
      var f = W[el.getAttribute('data-dlux')]; if (f) f(el);
    });
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot); else boot();
})();
