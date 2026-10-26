jQuery(document).ready(function($) {

    $('.grw-admin-page a.nav-tab').on('click', function(e)  {
        var $this = $(this), activeId = $this.attr('href');
        $(activeId).show().siblings('.tab-content').hide();
        $this.addClass('nav-tab-active').siblings().removeClass('nav-tab-active');
        e.preventDefault();
    });

    /**
     * Rate us feedback
     */
    var $rateus = $('#grw-rate_us');
    if ($rateus.length) {
        var $rateus_dlg = $('#grw-rate_us-feedback'),
            $rateus_stars = $('.rpi-star', $rateus_dlg);

        if (window.location.href.indexOf('grw_feed_id=') > -1 && !window['grw_rateus']) {
            $rateus.addClass('grw-flash-visible');
        }
        $('.rpi-star i', $rateus).click(function() {
            var rate = $(this).index() + 1;
            if (rate > 3) {
                $.post({
                    url      : ajaxurl,
                    type     : 'POST',
                    dataType : 'json',
                    data     : {
                        rate      : rate,
                        action    : 'grw_rateus_ajax',
                        grw_nonce : $('#grw_nonce').val()
                    }
                });
                var askUrl = /*(Math.random() * 1).toFixed(0) > 0 ?
                    'https://g.page/r/CZac_S7-Dh2NEBM/review' :*/
                    'https://wordpress.org/support/plugin/widget-google-reviews/reviews/?rate=' + rate + '#new-post';
                window.open(askUrl, '_blank');
                grw_rateus_close();
            } else {
                $rateus_stars.attr('data-rating', rate);
                $rateus_dlg.dialog({modal: true, width: '50%', maxWidth: '600px'});
                $('.ui-widget-overlay').bind('click', function() {
                    $rateus_dlg.dialog('close');
                });
            }
        });

        $('i', $rateus_stars).click(function() {
            $rateus_stars.attr('data-rating', $(this).index() + 1);
        });

        $('.grw-rate_us-close').click(grw_rateus_close);

        $('.grw-rate_us-cancel').click(function() {
            $rateus_dlg.dialog('close');
        });

        $('.grw-rate_us-send').click(function() {
            $.post({
                url      : ajaxurl,
                type     : 'POST',
                dataType : 'json',
                data     : {
                    rate      : $rateus_stars.attr('data-rating'),
                    email     : $('input', $rateus_dlg).val(),
                    msg       : $('textarea', $rateus_dlg).val(),
                    action    : 'grw_rateus_ajax_feedback',
                    grw_nonce : $('#grw_nonce').val()
                },
                success  : function(res) {
                    $rateus_dlg.dialog({'title': 'Feedback sent'})
                    $rateus_dlg.html('<b style="color:#4cc74b">Thank you for your feedback!<br>' +
                                     'We received it and will investigate your suggestions.</b>');

                    grw_rateus_close();
                    setTimeout(function() {
                        $rateus_dlg.fadeOut(500, function() { $rateus_dlg.dialog('close'); });
                    }, 1500);
                }
            });
        });

        function grw_rateus_close() {
            setTimeout(function() {
                $rateus.addClass('grw-flash-gout');
                $rateus.removeClass('grw-flash-visible');
                $rateus.removeClass('grw-flash-gout');
                window['grw_rateus'] = 1;
            }, 1000);
        }
    }

    /**
     * Overview page
     */
    var $overviewRating = $('#grw-overview-rating');
    if ($overviewRating.length) {

        var MONTHS = 6,
            $places  = $('#grw-overview-places'),
            $months  = $('#grw-overview-months'),
            $rating  = $('#grw-overview-rating'),
            $reviews = $('#grw-overview-reviews'),
            chart    = null;

        $places.change(function() {
            ajax(this.value);
        });

        $months.change(function() {
            MONTHS = this.value;
            ajax($places.val());
        });

        ajax(0, function(res) {
            /*
             * Places select filled
             */
            $.each(res.places, function(i, place) {
                $places.append($('<option>', {
                    value: place.id,
                    text : place.name
                }));
            });
        });

        function ajax(pid, cb) {
            var data = {
                action    : 'grw_overview_ajax',
                grw_nonce : jQuery('#grw_nonce').val()
            };

            if (pid) {
                data.place_id = pid;
            }
            jQuery.post({
                url      : ajaxurl,
                type     : 'POST',
                dataType : 'json',
                data     : data,
                success  : function(res) {

                    var place = res.places.length > 1 ? res.places.find(x => x.id == pid) : res.places[0];

                    /*
                     * Stats minmax grouping and calculate result
                     */
                    var stats_result = null;

                    if (res.stats_minmax.length) {

                        let minmax = {},
                            mintime = 0,
                            nowtime = (new Date().getTime() / 1000).toFixed(0);

                        for (let i = 0; i < res.stats_minmax.length; i++) {

                            let mm = res.stats_minmax[i],
                                gpid = mm.google_place_id;

                            mintime = !mintime || mm.time < mintime ? mm.time : mintime;

                            if (minmax[gpid]) {

                                minmax[gpid] = {
                                    time         : parseInt(nowtime - minmax[gpid].time),
                                    rating       : parseFloat((mm.rating - minmax[gpid].rating).toFixed(1)),
                                    review_count : parseInt(mm.review_count - minmax[gpid].review_count)
                                };

                                if (stats_result) {
                                    stats_result = {
                                        time         : minmax[gpid].time,
                                        rating       : stats_result.rating + minmax[gpid].rating,
                                        review_count : stats_result.review_count + minmax[gpid].review_count
                                    }
                                } else {
                                    stats_result = minmax[gpid];
                                }

                                delete minmax[gpid];

                            } else {
                                minmax[gpid] = {
                                    time         : mintime,
                                    rating       : mm.rating,
                                    review_count : mm.review_count
                                };
                            }

                        }
                    }

                    let $stats = $('#grw-overview-stats');
                    $stats.html('Not calculated yet');

                    if (stats_result) {
                        let sr = stats_result.rating, src = stats_result.review_count;
                        $stats.html(
                            '<div class="grw-overview-h">While using the plugin</div>' +
                            '<div>' +
                                'Usage time: ' +
                                '<span class="grw-stat-val grw-stat-up">' + grw_s2dmy(stats_result.time) + '</span>' +
                            '</div>' +
                            '<div>' +
                                'Rating up: ' +
                                '<span class="grw-stat-val grw-stat-' + (sr < 0 ? 'down' : (sr > 0 ? 'up' : '')) + '">' + sr + '</span>' +
                            '</div>' +
                            '<div>' +
                                'Reviews up: ' +
                                '<span class="grw-stat-val grw-stat-' + (src < 0 ? 'down' : (src > 0 ? 'up' : '')) + '">' + src + '</span>' +
                            '</div>'
                        );
                    }

                    /*
                     * Render rating
                     */
                    $rating.html(
                        '<div class="grw-overview-h">' + grw_esc(place.name) + '</div>' +
                        '<span class="rpi-stars" style="--rpi-star-size:26px;--rating:' + res.rating + '">' + res.rating + '</span>' +
                        '<div class="wp-google-powered">Based on ' + Number(res.review_count).toLocaleString() + ' reviews</div>' +

                        (place.updated ?
                        '<div class="wp-google-powered">Last updated: ' +
                            '<span class="wp-google-time">' +
                                rpi.Time.getTimeAgo(parseInt(place.updated), rpi.Utils.lang()) +
                            '</span>' +
                        '</div>' : '')
                    );

                    /*
                     * Render reviews
                     */
                    const root = document.createElement('div');
                    const list = document.createElement('div');

                    const common = rpi.Common($reviews[0], {text_size: 50}, {
                        time     : 'wp-google-time',
                        text     : 'wp-google-text',
                        readmore : 'wp-more-toggle'
                    });

                    list.className = 'wp-google-reviews';
                    $.each(res.reviews, function(i, review) {
                        list.appendChild(grw_review(review));
                    });

                    root.className = 'wp-gr wpac';
                    root.appendChild(list);

                    $reviews.html('');
                    $reviews[0].appendChild(root);

                    common.init();

                    $('.wp-review-hide', $reviews).unbind('click').click(function() {
                        grw_review_hide_global($(this));
                        return false;
                    });

                    /*
                     * Render stats
                     */

                    // 1) Grouped by Google place ID
                    var gs = {};
                    for (var s = 0; s < res.stats.length; s++) {
                        var stat = res.stats[s],
                            gpi = stat.google_place_id;

                        gs[gpi] = gs[gpi] || [];
                        gs[gpi].push({
                            time: parseInt(stat.time),
                            rating: parseFloat(stat.rating),
                            review_count: parseInt(stat.review_count)
                        });
                    }

                    // 2) Calculate how many months needs
                    var period = parseInt((res.stats[0].time - res.stats[res.stats.length - 1].time) / (60 * 60 * 24 * 30)),
                        months = period > 4 ? MONTHS : (period || 1);

                    // 2) Calculate stats by months (last six)
                    var ms = {},
                        today = new Date();

                    for (var i = 0; i < months; i++) {
                        var startDay = new Date(today.getFullYear(), today.getMonth() - i, 1),
                            endTime  = new Date(today.getFullYear(), today.getMonth() + 1 - i, 0).getTime(),
                            month    = startDay.toLocaleString('default', {month: 'short'}) + ' ' + startDay.getFullYear().toString().slice(-2);

                        ms[month] = ms[month] || {};

                        for (g in gs) {
                            var j = 0, xx = gs[g];

                            do {
                                var stat = xx[j++],
                                    time = stat.time * 1000;

                                ms[month][g] = ms[month][g] || {};
                                ms[month][g].count = parseInt(stat.review_count);

                            } while(time > endTime && j < xx.length);
                        }
                    }

                    // 3) Summary and normalize
                    var cat = [], data = [], series = []; var ttt = {};
                    for (m in ms) {
                        var count = 0;
                        for (p in ms[m]) {
                            count += ms[m][p].count;

                            // --- TEMP ---
                            var pp = res.places.find(x => x.id == p)
                            ttt[pp.name] = ttt[pp.name] || {};
                            ttt[pp.name].data = ttt[pp.name].data || [];
                            ttt[pp.name].data.unshift(ms[m][p].count);
                            // --- TEMP ---

                        }
                        cat.unshift(m);
                        data.unshift(count);
                    }

                    // --- TEMP ---
                    for (tt in ttt) {
                        series.push({name: tt, data: ttt[tt].data});
                    }
                    // --- TEMP ---

                    // 4) Render chart
                    var options = {
                        series: [{
                            name: 'Reviews',
                            data: data
                        }],
                        chart: {
                            height: 350,
                            type: 'bar',
                            //stacked: true,
                        },
                        plotOptions: {
                            bar: {
                                dataLabels: {
                                    position: 'top', // top, center, bottom
                                },
                            }
                        },
                        dataLabels: {
                            enabled: true,
                            offsetY: -20,
                            style: {
                                fontSize: '12px',
                                colors: ["#304758"]
                            }
                        },
                        tooltip: {
                            enabled: true,
                            intersect: false,
                            custom: function() { return ''; }
                        },
                        xaxis: {
                            categories: cat,
                            axisBorder: {
                                show: false
                            },
                            axisTicks: {
                                show: false
                            },
                            tooltip: {
                                enabled: true
                            }
                        },
                        yaxis: {
                            axisBorder: {
                                show: false
                            },
                            axisTicks: {
                                show: false,
                            }
                        },
                        title: {
                            text: 'Monthly reviews count',
                            align: 'center',
                            style: {
                                color: '#444'
                            }
                        }
                    };

                    if (chart) {
                        chart.updateOptions({series: [{name: 'Reviews', data: data}], xaxis: {categories: cat}});
                    } else {
                        chart = new ApexCharts(document.querySelector('#chart'), options);
                        chart.render();
                    }

                    cb && cb(res);
                }
            });
        }
    }

    /**
     * Deactivation survey (Plugins page)
     */
    var $deactivate = $('#grw-deactivate-dialog');
    if ($deactivate.length) {
        var deactivateUrl = '';

        $('tr[data-plugin="' + $deactivate.data('plugin') + '"] .deactivate a').click(function() {
            deactivateUrl = this.href;
            $deactivate.prop('hidden', false).dialog({modal: true, width: 440, draggable: false, resizable: false});
            return false;
        });

        $('.grw-deactivate-submit, .grw-deactivate-skip', $deactivate).click(function() {
            var $btn = $(this), skip = $btn.hasClass('grw-deactivate-skip'),
                reason = $('input[name="grw_deactivate_reason"]:checked', $deactivate).val() || '',
                msg = $('#grw-deactivate-msg', $deactivate).val();

            $('button', $deactivate).prop('disabled', true);
            if (skip || (!reason && !msg)) {
                window.location.href = deactivateUrl;
                return;
            }
            $.post(ajaxurl, {reason: reason, msg: msg, action: 'grw_deactivate_feedback', grw_nonce: $('#grw_deactivate_nonce').val()})
                .always(function() { window.location.href = deactivateUrl; });
        });
    }

    /**
     * Places page
     */
    var $placesTable = $('#grw-places');
    if ($placesTable.length) {

        var hasKey = $placesTable.data('key') == 1,
            nonce = $('#grw_nonce').val(),
            iframe = null,
            iframeReady = false,
            iframeQueue = [],
            iframeCb = null,
            iframeTimer = null;

        if (!hasKey) {
            var lang = GRW_VARS.lang ? GRW_VARS.lang.toLowerCase().split(/[_-]/)[0] : '';
            iframe = document.createElement('iframe');
            iframe.id = 'gpidc';
            iframe.src = 'https://app.richplugins.com/public/connect?authcode=' + encodeURIComponent($placesTable.data('authcode')) + '&lang=' + encodeURIComponent(lang);
            iframe.style.display = 'none';
            iframe.onload = function() {
                iframeReady = true;
                while (iframeQueue.length) iframe.contentWindow.postMessage(iframeQueue.shift(), '*');
            };
            document.body.appendChild(iframe);

            window.onmessage = function(e) {
                if (e.origin !== 'https://app.richplugins.com' || !e.data) return;
                var gdata = e.data;
                if (gdata.action === 'get_place') {
                    $.post(ajaxurl, {pid: gdata.pid, lang: gdata.lang, token: gdata.token, action: 'grw_get_place', grw_nonce: nonce}, function(res) {
                        if (res.status == 'success') {
                            res.result.place_id = gdata.pid;
                            iframe.contentWindow.postMessage({data: res, action: 'set_place'}, '*');
                        } else {
                            iframeDone({status: 'failed', result: res.result});
                        }
                    });
                } else if (gdata.action === 'connect') {
                    connect(gdata, function(res) {
                        iframe.contentWindow.postMessage({action: 'connect_done'}, '*');
                        iframeDone(res);
                    });
                }
            };
        }

        function iframeSend(msg, cb) {
            iframeCb = cb;
            clearTimeout(iframeTimer);
            iframeTimer = setTimeout(function() {
                iframeDone({status: 'failed', result: {error_message: 'No response from the connection service, please try again later.'}});
            }, 60000);
            if (iframeReady) iframe.contentWindow.postMessage(msg, '*'); else iframeQueue.push(msg);
        }

        function iframeDone(res) {
            clearTimeout(iframeTimer);
            var cb = iframeCb;
            iframeCb = null;
            cb && cb(res);
        }

        function connect(params, cb) {
            $.post(ajaxurl, {
                id          : params.id,
                url         : params.url,
                lang        : params.lang,
                local_img   : params.local_img,
                token       : params.token,
                action      : 'grw_connect_google',
                grw_wpnonce : nonce,
                v           : new Date().getTime()
            }, cb, 'json').fail(function() {
                cb({status: 'failed', result: {error_message: 'Request failed, please try again.'}});
            });
        }

        function updatePlace($row) {
            var conns = $row.data('conns') || [],
                i = 0, credits = -1, last = null, cached = false;

            // The iframe answers one request at a time, so only one row may be in flight.
            if ($('.grw-place-busy', $placesTable).length) return;
            $row.addClass('grw-place-busy');

            function finish(err) {
                $row.removeClass('grw-place-busy');
                if (err) {
                    GRW_TOAST.show({msg: err, type: 'error'});
                    return;
                }
                if (last) {
                    if (last.rating) {
                        $('.grw-col-rating', $row).html('<span class="rpi-stars" style="--rating:' + last.rating + '">' + last.rating + '</span>');
                    }
                    if (last.user_ratings_total) {
                        $('.grw-place-count', $row).text(Number(last.user_ratings_total).toLocaleString());
                    }
                    if (last.photo) {
                        $('.grw-place img', $row).attr('src', last.photo);
                    }
                }
                $('.grw-place-updated', $row).text('just now');
                GRW_TOAST.show({
                    msg: cached ?
                        'Reviews are up to date: without your API key they are refreshed from Google at most once every 3 days, so no attempt was used.' :
                        'Reviews updated successfully. ' + (credits > -1 ? '<br><b>' + credits + ' attempts remaining (without your API key).</b>' : ''),
                    type: 'success'
                });
            }

            function next() {
                if (i >= conns.length) return finish();
                var c = conns[i++],
                    params = {id: $row.data('pid'), url: $row.data('url'), lang: c.lang, local_img: c.local_img, event: 'refresh'};
                if (hasKey) {
                    connect(params, handle);
                } else {
                    iframeSend({params: params, action: 'connect'}, handle);
                }
            }

            function handle(res) {
                if (res && res.status == 'success') {
                    last = res.result;
                    if (res.result.credits > -1) credits = res.result.credits;
                    if (res.result.cached) cached = true;
                    next();
                } else {
                    finish(grw_get_error(res) || 'Update failed, please try again later.');
                }
            }

            next();
        }

        $placesTable.on('click', '.grw-place-update', function() {
            updatePlace($(this).closest('tr'));
            return false;
        });

        // Deleting a place is final and reaches every widget that shows it, so the confirm asks for the word.
        $placesTable.on('click', '.grw-place-delete', function() {
            var $row = $(this).closest('tr');
            if ($row.hasClass('grw-place-busy')) return false;
            var word = prompt('Deleting ' + $row.data('name') + ' also deletes all of its ' + $row.data('reviews') + ' stored reviews.\nWidgets and badges that include this place will stop showing it.\n\nType delete to confirm');
            if (word === null || word.trim().toLowerCase() !== 'delete') return false;
            $row.addClass('grw-place-busy');
            $.post(ajaxurl, {id: $row.data('id'), action: 'grw_delete_place', grw_nonce: nonce}, function(res) {
                if (res.status == 'success') {
                    $row.remove();
                    if (!$('tbody tr', $placesTable).length) window.location.reload();
                } else {
                    $row.removeClass('grw-place-busy');
                    GRW_TOAST.show({msg: grw_get_error(res) || 'Delete failed', type: 'error'});
                }
            }, 'json');
            return false;
        });
    }

});

function grw_review(review) {
    const slg = 'wp-google';

    const root = document.createElement('div');
    root.className = slg + '-review' + (review.hide === '' ? '' : ' wp-review-hidden');
    root.dataset.rev = String(review.provider || '');

    const right = document.createElement('div');
    right.className = slg + '-right';
    root.appendChild(right);

    const lnk = document.createElement('a');
    lnk.className = slg + '-name';
    lnk.target = '_blank';
    lnk.rel = 'nofollow noopener';
    lnk.href = grw_safeUrl(review.author_url);
    lnk.textContent = review.author_name || '';
    right.appendChild(lnk);

    const time = document.createElement('div');
    time.className = slg + '-time';
    time.dataset.time = String(review.time || '');
    right.appendChild(time);

    const feedback = document.createElement('div');
    feedback.className = slg + '-feedback';
    right.appendChild(feedback);

    const stars = document.createElement('span');
    stars.className = 'rpi-stars';
    stars.style.setProperty('--rpi-star-size', '16px');
    stars.style.setProperty('--rating', review.rating);
    feedback.appendChild(stars);

    const text = document.createElement('span');
    text.className = slg + '-text';
    text.textContent = review.text;
    text.innerHTML = text.innerHTML.replace(/\n/g, '<br>');
    feedback.appendChild(text);

    const toggle = document.createElement('a');
    toggle.href = '#';
    toggle.className = 'wp-review-hide';
    toggle.dataset.id = String(review.id || '');
    toggle.textContent = (review.hide === '' ? 'Hide' : 'Show') + ' review';
    right.appendChild(toggle);

    return root;
}

function grw_safeUrl(url) {
    if (!url) return '#';
    url = String(url).trim();
    if (/^https?:\/\//i.test(url)) {
        return url;
    }
    return '#';
}

function grw_esc(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, c => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[c]));
}

function grw_s2dmy(s) {
    let d = (s / (60 * 60 * 24)).toFixed(0);
    if (d > 30) {
        if (d > 365) {
            return Math.round(d / 365) + ' years';
        }
        return Math.round(d / 30) + ' months';
    }
    return d + ' days';
}

const GRW_TOAST = rpi.Toast({timeout: 25});

function grw_get_error(res) {
    return res?.result?.error_message?.message || res?.result?.error_message || null;
}
