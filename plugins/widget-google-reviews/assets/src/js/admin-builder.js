const GRW_AUTOSAVE_KEYUP_TIMEOUT = 1500;
var GRW_AUTOSAVE_TIMEOUT = null;

// A save waiting for the typing to stop; leaving the page warns while one is waiting.
function grw_autosave() {
    clearTimeout(GRW_AUTOSAVE_TIMEOUT);
    GRW_AUTOSAVE_TIMEOUT = setTimeout(function() {
        GRW_AUTOSAVE_TIMEOUT = null;
        grw_serialize_connections();
    }, GRW_AUTOSAVE_KEYUP_TIMEOUT);
}

const GRW_HTML_CONTENT =

    '<div class="grw-builder-platforms grw-builder-inside">' +
        '<div class="grw-builder-connect grw-connect-google">Connect Google</div>' +
        '<div id="grw-connect-wizard" title="Connect Google Reviews" style="display:none;">{{wizard}}</div>' +
        '<div class="grw-connections"></div>' +
    '</div>' +

    '<div class="grw-connect-options">' +

        '<div class="grw-builder-inside">' +
            '<div class="grw-builder-option">' +
                'Layout' +
                '<select id="view_mode" name="view_mode">' +
                    '<option value="slider" selected="selected">Slider</option>' +
                    '<option value="grid">Grid</option>' +
                    '<option value="list">List</option>' +
                    '<option value="rating">Rating</option>' +
                '</select>' +
            '</div>' +
        '</div>' +

        /* Header Options */
        '<div class="grw-builder-top grw-toggle">Header Options</div>' +
        '<div class="grw-builder-inside" style="display:none">' +
            '<div class="grw-builder-option" data-if="cards header">' +
                'Header position' +
                '<select name="slider_head_pos">' +
                    '<option value="2">Left</option>' +
                    '<option value="1">Top</option>' +
                    '<option value="3">Bottom</option>' +
                '</select>' +
            '</div>' +
            '<div class="grw-builder-option">' +
                '<label>' +
                    '<input type="checkbox" name="header_hide_social" value="">' +
                    'Hide rating header, leave only reviews' +
                '</label>' +
            '</div>' +
            '<div class="grw-builder-option" data-if="header">' +
                '<label>' +
                    '<input type="checkbox" name="header_center" value="">' +
                    'Center the header' +
                '</label>' +
            '</div>' +
            '<div class="grw-builder-option" data-if="header">' +
                '<label>' +
                    '<input type="checkbox" name="header_hide_photo" value="">' +
                    'Hide business photo' +
                '</label>' +
            '</div>' +
            '<div class="grw-builder-option" data-if="header">' +
                '<label>' +
                    '<input type="checkbox" name="disable_biz_link">' +
                    'Disable link on business name' +
                '</label>' +
                '<span class="grw-quest grw-quest-top grw-toggle" title="Click to help">?</span>' +
                '<div class="grw-quest-help" style="display:none;">Show the business name as plain text instead of a link to the Google Local Business Page.</div>' +
            '</div>' +
            '<div class="grw-builder-option" data-if="header">' +
                '<label>' +
                    '<input type="checkbox" name="header_hide_grade" value="">' +
                    'Hide rating number' +
                '</label>' +
            '</div>' +
            '<div class="grw-builder-option" data-if="header">' +
                '<label>' +
                    '<input type="checkbox" name="header_bold_count" value="">' +
                    'Bold review count' +
                '</label>' +
            '</div>' +
            '<div class="grw-builder-option" data-if="header">' +
                '<label>' +
                    '<input type="checkbox" name="header_short_count" value="">' +
                    'Use short review count' +
                '</label>' +
                '<span class="grw-quest grw-quest-top grw-toggle" title="Click to help">?</span>' +
                '<div class="grw-quest-help" style="display:none;">Shows \'256 reviews\' instead of \'Based on 256 reviews\'.</div>' +
            '</div>' +
            '<div class="grw-builder-option" data-if="header">' +
                '<label>' +
                    '<input type="checkbox" name="hide_writereview" value="">' +
                    'Hide \'review us on G\' button' +
                '</label>' +
            '</div>' +
            '<div class="grw-builder-option" data-if="header headcard">' +
                '<label>' +
                    '<input type="checkbox" name="--slider-head-card-bg" value="var(--slider-head-card-bg-color)" data-on="var(--slider-head-card-bg-color)" data-off="transparent">' +
                    'Show header background' +
                '</label>' +
            '</div>' +
            '<div class="grw-builder-option" data-if="header headcard headbg">' +
                '<input type="color" name="--slider-head-card-bg-color" value="#f4f4f4" data-val="#f4f4f4" data-defval="#f4f4f4"/>' +
                '<input type="text" value="#f4f4f4"/>' +
                'Header background color' +
            '</div>' +
            '<div class="grw-builder-option" data-if="header headcard">' +
                '<label>' +
                    '<input type="checkbox" name="--slider-head-card-radius" value="8px" data-on="8px" data-off="0" checked>' +
                    'Rounded header corners' +
                '</label>' +
            '</div>' +
            '<div class="grw-builder-option" data-if="header headcard">' +
                '<label>' +
                    '<input type="checkbox" name="--slider-head-card-shadow" value="var(--rev-shadow)" data-on="var(--rev-shadow)" data-off="none">' +
                    'Show header shadow' +
                '</label>' +
            '</div>' +
            '<div class="grw-builder-option grw-header-order" data-if="header">' +
                'Header lines' +
                '<input type="hidden" name="header_order" value="">' +
                '<div hidden>' +
                    '<input type="checkbox" name="header_show_scale" value="">' +
                    '<input type="checkbox" name="header_hide_name" value="">' +
                    '<input type="checkbox" name="hide_based_on" value="">' +
                    '<input type="checkbox" name="header_platform" value="">' +
                '</div>' +
            '</div>' +
        '</div>' +

        /* Common Options */
        '<div class="grw-builder-top grw-toggle">Common Options</div>' +
        '<div class="grw-builder-inside" style="display:none">' +
            '<div class="grw-builder-option" data-if="paged">' +
                'Reviews shown before \'More reviews\'' +
                '<input type="text" name="pagination" value="">' +
            '</div>' +
            '<div class="grw-builder-option" data-if="reviews !engine">' +
                'Maximum characters before \'read more\' link' +
                '<input type="text" name="text_size" value="">' +
            '</div>' +
            '<div class="grw-builder-option" data-if="cards !engine">' +
                'Text height' +
                '<input type="text" name="slider_text_height" value="" placeholder="Default: 100px">' +
            '</div>' +
            '<div class="grw-builder-option" data-if="reviews">' +
                '<label>' +
                    '<input type="checkbox" name="short_last_name" value="">' +
                    'Shorten last name (GDPR)' +
                '</label>' +
                '<span class="grw-quest grw-quest-top grw-toggle" title="Click to help">?</span>' +
                '<div class="grw-quest-help" style="display:none;">Show only first name and first letter of last name</div>' +
            '</div>' +
            '<div class="grw-builder-option" data-if="reviews">' +
                '<label>' +
                    '<input type="checkbox" name="anonym_avatar" value="">' +
                    'Anonymize profile photos' +
                '</label>' +
                '<span class="grw-quest grw-quest-top grw-toggle" title="Click to help">?</span>' +
                '<div class="grw-quest-help" style="display:none;">Shows the first letter of the reviewer\'s name on a colored background instead of their photo. The photo URL never reaches the visitor\'s browser. The color comes from the name, so a reviewer always gets the same one.</div>' +
            '</div>' +
            '<div class="grw-builder-option" data-if="reviews">' +
                '<label>' +
                    '<input type="checkbox" name="min_letter" value="">' +
                    'Hide reviews without text' +
                '</label>' +
            '</div>' +
            '<div class="grw-builder-option" data-if="rating">' +
                '<label>' +
                    '<input type="checkbox" name="rating_popup" value="" checked>' +
                    'Open reviews in a popup on click' +
                '</label>' +
                '<span class="grw-quest grw-quest-top grw-toggle" title="Click to help">?</span>' +
                '<div class="grw-quest-help" style="display:none;">The rating block becomes clickable and opens the reviews in a popup, so the compact badge stays compact.</div>' +
            '</div>' +
            /*'<div class="grw-builder-option">' +
                '<label>' +
                    '<input type="checkbox" name="hide_reviews" value="">' +
                    'Hide reviews, leave only rating header' +
                '</label>' +
            '</div>' +*/
        '</div>' +

        /* Slider Options */
        '<div class="grw-builder-top grw-toggle" data-if="slider">Slider Options</div>' +
        '<div class="grw-builder-inside" style="display:none" data-if="slider">' +
            '<div class="grw-builder-option">' +
                '<label>' +
                    '<input type="checkbox" name="slider_autoplay" value="" checked>' +
                    'Auto-play' +
                '</label>' +
            '</div>' +
            '<div class="grw-builder-option" data-if="autoplay">' +
                'Delay between slides (sec)' +
                '<input type="text" name="slider_speed" value="" placeholder="Default: 3">' +
            '</div>' +
            '<div class="grw-builder-option" data-if="autoplay">' +
                '<label>' +
                    '<input type="checkbox" name="slider_mousestop" value="" checked>' +
                    'Stop auto play on mouse over' +
                '</label>' +
            '</div>' +
            '<div class="grw-builder-option">' +
                '<label>' +
                    '<input type="checkbox" name="slider_hide_prevnext" value="">' +
                    'Hide prev & next buttons' +
                '</label>' +
            '</div>' +
            '<div class="grw-builder-option">' +
                '<label>' +
                    '<input type="checkbox" name="slider_hide_dots" value="">' +
                    'Hide dots' +
                '</label>' +
            '</div>' +
        '</div>' +

        /* Breakpoints */
        '<div class="grw-builder-top grw-toggle" data-if="cards">Column Options</div>' +
        '<div class="grw-builder-inside" style="display:none" data-if="cards">' +
            '<div class="grw-builder-option">' +
                '<input type="hidden" name="slider_breakpoints">' +
                '<label>' +
                    'How many columns to show' +
                '</label>' +
                '<div class="grw-slider-br">' +
                    '<label>' +
                        '<select>' +
                            '<option value="off">Auto</option>' +
                            '<option value="3000">Large Desktop & TV</option>' +
                            '<option value="1200">Desktop</option>' +
                            '<option value="1024">Laptop</option>' +
                            '<option value="768">Tablet</option>' +
                            '<option value="480">Mobile</option>' +
                            '<option value="">Custom</option>' +
                        '</select>' +
                    '</label>' +
                    '<label>' +
                        '<input type="range" name="" value="3" min="1" max="12" step="1" oninput="this.nextSibling.innerHTML=this.value"/><span>3</span>' +
                    '</label>' +
                    '<span class="grw-quest" title="Click to add new breakpoints">+</span>' +
                '</div>' +
            '</div>' +
        '</div>' +

        /* Card Options */
        '<div class="grw-builder-top grw-toggle" data-if="cards">Card Options</div>' +
        '<div class="grw-builder-inside grw-card-ui" style="display:none" data-if="cards"></div>' +

        /* Style Options */
        '<div class="grw-builder-top grw-toggle">Style Options</div>' +
        '<div class="grw-builder-inside" style="display:none">' +
            '<div class="grw-builder-option">' +
                '<label>' +
                    '<input type="checkbox" name="dark_theme">' +
                    'Dark background' +
                '</label>' +
            '</div>' +
            /*'<div class="grw-builder-option">' +
                '<label>' +
                    '<input type="checkbox" name="hide_backgnd" value="">' +
                    'Hide reviews background' +
                '</label>' +
            '</div>' +*/
            '<div class="grw-builder-option" data-if="cards !engine">' +
                '<label>' +
                    '<input type="checkbox" name="--rev-bg" value="transparent" data-on="transparent" data-off="var(--rev-color)">' +
                    'Hide reviews background' +
                '</label>' +
            '</div>' +
            '<div class="grw-builder-option" data-if="cards revbg !engine">' +
                '<input type="color" name="--rev-color" value="#f4f4f4" data-val="#f4f4f4" data-defval="#f4f4f4"/>' +
                '<input type="text" value="#f4f4f4"/>' +
                'Reviews color' +
            '</div>' +
            '<div class="grw-builder-option" data-if="cards !engine">' +
                '<label>' +
                    '<input type="checkbox" name="show_round" value="" checked>' +
                    'Rounded review corners' +
                '</label>' +
            '</div>' +
            '<div class="grw-builder-option" data-if="cards !engine">' +
                '<label>' +
                    '<input type="checkbox" name="show_shadow" value="">' +
                    'Show reviews shadow' +
                '</label>' +
            '</div>' +
            '<div class="grw-builder-option" data-if="header">' +
                '<label>' +
                    '<input type="checkbox" name="--head-scale-case" value="uppercase" data-on="uppercase" data-off="none" checked>' +
                    'Uppercase rating label' +
                '</label>' +
            '</div>' +
            '<div class="grw-builder-option">' +
                '<input type="color" name="--star-color" value="#fb8e28" data-val="#fb8e28" data-defval="#fb8e28"/>' +
                '<input type="text" value="#fb8e28"/>' +
                '<span data-if="!engine">Stars color</span><span data-if="engine">Rating stars color</span>' +
            '</div>' +
            '<div class="grw-builder-option" data-if="header">' +
                '<input type="color" name="--head-name-color" value="#333333" data-val="#333333" data-defval="#333333"/>' +
                '<input type="text" value="#333333"/>' +
                'Business name color' +
            '</div>' +
            '<div class="grw-builder-option" data-if="header">' +
                '<input type="color" name="--head-scale-color" value="#333333" data-val="#333333" data-defval="#333333"/>' +
                '<input type="text" value="#333333"/>' +
                'Rating label color' +
            '</div>' +
            '<div class="grw-builder-option" data-if="header">' +
                '<input type="color" name="--head-based-color" value="#555555" data-val="#555555" data-defval="#555555"/>' +
                '<input type="text" value="#555555"/>' +
                'Based on color' +
            '</div>' +
            '<div class="grw-builder-option" data-if="header">' +
                '<input type="color" name="--powered-color" value="#5e5e5e" data-val="#5e5e5e" data-defval="#5e5e5e"/>' +
                '<input type="text" value="#5e5e5e"/>' +
                'Powered color' +
            '</div>' +
            '<div class="grw-builder-option" data-if="header">' +
                '<input type="color" name="--btn-color" value="#ffffff" data-val="#ffffff" data-defval="#ffffff"/>' +
                '<input type="text" value="#ffffff"/>' +
                '\'Review us\' button color' +
            '</div>' +
            '<div class="grw-builder-option" data-if="header">' +
                '<input type="color" name="--btn-txt-color" value="#1f1f1f" data-val="#1f1f1f" data-defval="#1f1f1f" data-vars="--btn-txt-shadow:none"/>' +
                '<input type="text" value="#1f1f1f"/>' +
                '\'Review us\' button text color' +
            '</div>' +
            '<div class="grw-builder-option" data-if="reviews">' +
                '<input type="color" name="--name-color" value="#154fc1" data-val="#154fc1" data-defval="#154fc1"/>' +
                '<input type="text" value="#154fc1"/>' +
                'Review author name color' +
            '</div>' +
            '<div class="grw-builder-option" data-if="reviews !engine">' +
                '<input type="color" name="--text-color" value="#222222" data-val="#222222" data-defval="#222222"/>' +
                '<input type="text" value="#222222"/>' +
                'Reviews text color' +
            '</div>' +
            '<div class="grw-builder-option" data-if="header">' +
                'Rating label size' +
                '<div class="grw-range">' +
                    '<input type="range" name="--head-scale-fs" value="24" min="12" max="48" step="1" data-val="24px" data-defval="24" data-postfix="px"/>' +
                    '<span class="grw-range-val"></span>' +
                '</div>' +
            '</div>' +
            '<div class="grw-builder-option" data-if="header">' +
                'Rating label weight' +
                '<div class="grw-range">' +
                    '<input type="range" name="--head-scale-weight" value="700" min="100" max="900" step="100" data-val="700" data-defval="700"/>' +
                    '<span class="grw-range-val"></span>' +
                '</div>' +
            '</div>' +
            '<div class="grw-builder-option" data-if="header">' +
                'Rating stars size' +
                '<div class="grw-range">' +
                    '<input type="range" name="--head-star-size" value="20" min="12" max="48" step="1" data-val="20px" data-defval="20" data-postfix="px"/>' +
                    '<span class="grw-range-val"></span>' +
                '</div>' +
            '</div>' +
            '<div class="grw-builder-option" data-if="header">' +
                'Google logo size' +
                '<div class="grw-range">' +
                    '<input type="range" name="--head-logo-size" value="14" min="10" max="48" step="1" data-val="14px" data-defval="14" data-postfix="px"/>' +
                    '<span class="grw-range-val"></span>' +
                '</div>' +
            '</div>' +
            '<div class="grw-builder-option">' +
                '<a href="#" class="grw-style-reset">Reset to default style</a>' +
            '</div>' +
            '<input id="style_vars" name="style_vars" type="hidden"/>' +
        '</div>' +

        /* Advanced Options */
        '<div class="grw-builder-top grw-toggle">Advanced Options</div>' +
        '<div class="grw-builder-inside" style="display:none">' +
            '<div class="grw-builder-option grw-card-ui grw-card-code" data-if="engine"></div>' +
            '<div class="grw-builder-option">' +
                'CSS variables' +
                '<textarea name="css_vars" class="large-text code" rows="5" maxlength="20000" aria-label="CSS variables" spellcheck="false" autocomplete="off" placeholder="--card-bg: #ffffff;&#10;--card-text-size: 14px;&#10;--head-star-size: 18px;"></textarea>' +
                '<span class="grw-quest grw-quest-top grw-toggle" title="Click to help">?</span>' +
                '<div class="grw-quest-help" style="display:none;">For those who know CSS: variables for this widget, one per line. --card-… change the review cards, --head-… the rating header. They override the theme and the color and size settings.</div>' +
            '</div>' +
            '<div class="grw-builder-option" data-if="reviews">' +
                'Reviewer photo resolution' +
                '<select name="reviewer_avatar_size">' +
                    '<option value="56" selected="selected">56 px (standard)</option>' +
                    '<option value="128">128 px (Retina)</option>' +
                    '<option value="256">256 px (max)</option>' +
                '</select>' +
                '<span class="grw-quest grw-quest-top grw-toggle" title="Click to help">?</span>' +
                '<div class="grw-quest-help" style="display:none;">Photos are shown at 44px; a higher resolution is sharper on Retina screens but loads more data.</div>' +
            '</div>' +
            '<div class="grw-builder-option">' +
                'Cache data' +
                '<select name="cache">' +
                    '<option value="1">1 Hour</option>' +
                    '<option value="3">3 Hours</option>' +
                    '<option value="6">6 Hours</option>' +
                    '<option value="12" selected="selected">12 Hours</option>' +
                    '<option value="24">1 Day</option>' +
                    '<option value="48">2 Days</option>' +
                    '<option value="168">1 Week</option>' +
                    '<option value="">Disable (NOT recommended)</option>' +
                '</select>' +
            '</div>' +
            '<div class="grw-builder-option" data-if="reviews">' +
                'Reviews limit' +
                '<input type="text" name="reviews_limit" value="">' +
            '</div>' +
            '<div class="grw-builder-option">' +
                '<label>' +
                    '<input type="checkbox" name="lazy_load_img" checked>' +
                    'Lazy load images' +
                '</label>' +
            '</div>' +
            '<div class="grw-builder-option">' +
                '<label>' +
                    '<input type="checkbox" name="nofollow_link" checked>' +
                    'Use nofollow links' +
                '</label>' +
            '</div>' +
            '<div class="grw-builder-option">' +
                '<label>' +
                    '<input type="checkbox" name="open_link" checked>' +
                    'Open links in a new tab' +
                '</label>' +
            '</div>' +
            '<div class="grw-builder-option">' +
                '<label>' +
                    '<input type="checkbox" name="aria_label">' +
                    'Enable ARIA label for screen readers' +
                '</label>' +
            '</div>' +
            '<div class="grw-builder-option" data-if="reviews">' +
                '<label>' +
                    '<input type="checkbox" name="media" value="" checked>' +
                    'Show review images' +
                '</label>' +
            '</div>' +
            '<div class="grw-builder-option" data-if="reviews">' +
                '<label>' +
                    '<input type="checkbox" name="reply" value="" checked>' +
                    'Show owner responses' +
                '</label>' +
            '</div>' +
            '<div class="grw-builder-option" data-if="seeall">' +
                '<label>' +
                    '<input type="checkbox" name="google_def_rev_link">' +
                    'Use default Google reviews link' +
                '</label>' +
                '<span class="grw-quest grw-quest-top grw-toggle" title="Click to help">?</span>' +
                '<div class="grw-quest-help" style="display:none;">If the direct link to all reviews <b>https://search.google.com/local/reviews?placeid=&lt;PLACE_ID&gt;</b> does not work with your Google place (leads to 404), please use this option to use the default reviews link to Google map.</div>' +
            '</div>' +
            '<input type="hidden" id="hidden_ids" name="hidden" value="">' +
        '</div>' +

    '</div>';

const GRW_WIZARD =
    '<iframe id="gpidc" src="https://app.richplugins.com/public/connect?authcode={{authcode}}&lang={{lang}}" style="width:100%;height:99%"></iframe>' +
    '<small class="grw-connect-error"></small>';

const GRW_WIZARD2 =
    '<div id="gpidc" style="height:400px;background:#ffffff;padding:20px">' +
        '<h3>Connection Wizard</h3>' +
        '<p>Please start typing your business name or address in the search field below.<br>Alternatively, you can paste your Google Place ID if you know it.</p>' +
        '<div style="position:relative">' +
          '<input id="grw_place_autocomplete" type="text" placeholder="Start typing here your business place address and name" autofocus/>' +
          '<svg id="grw_place_spin_svg" style="display:none" viewBox="0 0 16 16"><path fill="#999999" d="M12.9 3.1c1.3 1.2 2.1 3 2.1 4.9 0 3.9-3.1 7-7 7s-7-3.1-7-7c0-1.9 0.8-3.7 2.1-4.9l-0.8-0.8c-1.4 1.5-2.3 3.5-2.3 5.7 0 4.4 3.6 8 8 8s8-3.6 8-8c0-2.2-0.9-4.2-2.3-5.7l-0.8 0.8z"/></svg>' +
          '<ul id="grw_place_list" role="listbox"></ul>' +
        '</div>' +
        '<div id="grw_place" style="display:none;margin-top:12px">' +
            '<div class="wp-google-place" style="display:flex;align-items:center;gap:16px;flex-wrap:wrap">' +
                '<div class="wp-google-left">' +
                    '<img src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" alt="g business img" width="50" height="50" title="g business img" style="border-radius:50%">' +
                '</div>' +
                '<div class="wp-google-right">' +
                    '<b class="wp-google-name"></b>' +
                    '<div style="display:flex;align-items:center;gap:2px">' +
                        '<span class="wp-google-rating"></span>' +
                        '<span class="rpi-stars" style="--rpi-star-size:17px"></span>' +
                    '</div>' +
                    '<small class="wp-google-powered">Based on <span class="wp-google-based"></span> reviews</small>' +
                '</div>' +
                '<select id="grw_place_lang"></select>' +
                '<button id="grw_place_btn">Connect</button>' +
            '</div>' +
        '</div>' +
        '<p id="grw_place_error" class="grw-connect-error"></p>' +
    '</div>';

var GRW_LIGHTBOX;

function grw_stylechange2(target) {
    let rp = document.getElementsByClassName('wp-gr')[0];
    if (!rp) return;

    let vars = target.getAttribute('data-vars');
    if (vars) {
        let on = target.type == 'color' || target.checked;
        vars.split(';').forEach(function(pair) {
            pair = pair.split(':');
            if (pair.length > 1) {
                if (on) {
                    rp.style.setProperty(pair[0].trim(), pair[1].trim());
                } else {
                    rp.style.removeProperty(pair[0].trim());
                }
            }
        });
    }

    if (target.type == 'range' || target.type == 'color') {
        let val = target.value + (target.getAttribute('data-postfix') || '');
        target.setAttribute('data-val', val);
        rp.style.setProperty(target.name, val);

        // if color input put color to the next text input
        if (target.type == 'color') {
            target.nextSibling.value = val;
        }
    } else if (target.type == 'checkbox' || target.type == 'radio') {
        if (target.checked) {
            rp.style.setProperty(target.name, target.getAttribute('data-on'));
        } else if (target.getAttribute('data-off')) {
            rp.style.setProperty(target.name, target.getAttribute('data-off'));
        } else {
            rp.style.removeProperty(target.name);
        }
    }

    window.style_vars.value = grw_style_vars(rp);
}

// The variables the preview root carries (with no place connected there is no preview: the ones kept so far); for a card preset its
// own ones only where they differ from the preset. The CSS variables field reaches the root as !important declarations, kept in css_vars.
function grw_style_vars(rp) {
    const list = grw_declarations(rp ? rp.getAttribute('style') || '' : window.style_vars.value)
        .filter(d => /^--[\w-]+:./.test(d) && !/!\s*important$/i.test(d));
    return (GRW_CARD ? GRW_CARD.vars(list) : list).map(v => v + ';').join('');
}

// "name:value" per declaration; a semicolon inside brackets or quotes, as in url(data:…;…), does not end one.
function grw_declarations(text) {
    const list = [];
    let depth = 0, quote = '', from = 0;
    const push = to => {
        const d = text.slice(from, to), i = d.indexOf(':');
        if (i > 0) list.push(d.slice(0, i).trim() + ':' + d.slice(i + 1).trim());
        from = to + 1;
    };
    for (let i = 0; i < text.length; i++) {
        const c = text[i];
        if (quote) {
            if (c === '\\') i++;
            else if (c === quote) quote = '';
        } else if (c === '"' || c === "'") {
            quote = c;
        } else if (c === '(') {
            depth++;
        } else if (c === ')') {
            depth = Math.max(0, depth - 1);
        } else if (c === ';' && !depth) {
            push(i);
        }
    }
    if (!depth && !quote) push(text.length);
    return list;
}

function stylereset() {
    const rp = document.querySelector('.wp-gr');
    if (rp) {
        // The CSS variables field's values are the root's only !important ones; a reset keeps them, as it keeps the field.
        [...rp.style].filter(prop => prop.indexOf('--') === 0 && !rp.style.getPropertyPriority(prop)).forEach(prop => rp.style.removeProperty(prop));
    }
    document.querySelectorAll('.grw-connect-options input[name^="--"]').forEach(function(input) {
        input.value = input.defaultValue;
        input.checked = input.defaultChecked;
        input.setAttribute('data-val', input.defaultValue + (input.getAttribute('data-postfix') || ''));
        if (input.type == 'color') {
            input.nextSibling.value = input.defaultValue;
        }
    });
    window.style_vars.value = '';
    if (GRW_CARD) GRW_CARD.resetSkin();
    grw_builder_refresh();
}

function grw_builder_refresh() {
    const panel = document.querySelector('.grw-connect-options'),
        q = name => panel.querySelector('[name="' + name + '"]'),
        on = name => q(name).checked,
        mode = q('view_mode').value,
        rating = mode == 'rating';

    // Until the user sets it, the card background follows the theme: public CSS turns it off in the dark one.
    if (!/--rev-bg:/.test(window.style_vars.value)) {
        q('--rev-bg').checked = on('dark_theme');
    }

    const flags = {
        slider   : mode == 'slider',
        rating   : rating,
        cards    : mode == 'slider' || mode == 'grid',
        paged    : mode == 'grid' || mode == 'list',
        header   : !on('header_hide_social'),
        headcard : mode == 'grid' || mode == 'slider' && q('slider_head_pos').value != '2',
        headbg   : on('--slider-head-card-bg'),
        revbg    : !on('--rev-bg'),
        autoplay : on('slider_autoplay'),
        reviews  : !rating || on('rating_popup'),
        seeall   : mode == 'list' || rating && on('rating_popup')
    };
    Object.assign(flags, GRW_CARD ? GRW_CARD.flags(flags.cards) : {});
    // A grid has no header beside its reviews: "Left" is not offered, and the grid draws it on top as it always has.
    const headPos = q('slider_head_pos');
    headPos.querySelector('option[value="2"]').hidden = mode == 'grid';
    if (mode == 'grid' && headPos.value == '2') headPos.value = '1';
    panel.querySelectorAll('[data-if]').forEach(function(el) {
        el.hidden = !el.getAttribute('data-if').split(' ').every(function(flag) {
            return flag[0] == '!' ? !flags[flag.slice(1)] : flags[flag];
        });
    });
    panel.querySelectorAll('.grw-range-val').forEach(function(val) {
        const range = val.previousElementSibling;
        val.textContent = range.value + (range.getAttribute('data-postfix') || '');
    });
}

var GRW_CARD = null;

// The same variables as Card_Presets::vars() in PHP.
function grw_card_vars(s, shadows) {
    const bg = s.bg === 'none' ? 'transparent' : s.bg === 'gradient' ? 'linear-gradient(135deg, ' + s.bg1 + ', ' + s.bg2 + ')' : s.bg1;
    return {
        '--card-bg': bg, '--card-tail': s.bg === 'none' && s.border ? s.borderColor : bg, '--card-br': s.border ? '1px solid' : 'none',
        '--card-br-color': s.borderColor, '--card-shadow': shadows[s.shadow] || 'none', '--card-radius': s.radius + 'px',
        '--card-band-bg': s.band, '--card-sep-color': s.divider, '--card-star-color': s.stars, '--card-star-size': s.starSize + 'px',
        '--name-color': s.name, '--card-color': s.text, '--time-color': s.date, '--card-img-size': s.photo + 'px', '--img-filter': s.gray ? 'grayscale(1)' : 'none',
        '--card-text-lines': s.lines
    };
}

function grw_card_pairs(str) {
    return str.split(';').reduce((o, p) => {
        const i = p.indexOf(':');
        if (i > 0) o[p.slice(0, i).trim()] = p.slice(i + 1).trim();
        return o;
    }, {});
}

// The skin back from the variables a widget saved, over the preset's skin s.
function grw_card_read(sv, s, shadows) {
    const v = grw_card_pairs(sv), px = k => parseInt(v[k], 10), g = /^linear-gradient\(135deg, (#[0-9a-f]{6}), (#[0-9a-f]{6})\)$/i.exec(v['--card-bg'] || '');
    if (g) Object.assign(s, {bg: 'gradient', bg1: g[1], bg2: g[2]});
    else if (v['--card-bg']) Object.assign(s, v['--card-bg'] === 'transparent' ? {bg: 'none'} : {bg: 'color', bg1: v['--card-bg']});
    if (v['--card-br']) s.border = v['--card-br'] === '1px solid';
    if (v['--card-shadow']) s.shadow = Object.keys(shadows).find(k => shadows[k] === v['--card-shadow']) || s.shadow;
    if (v['--img-filter']) s.gray = v['--img-filter'] !== 'none';
    if (v['--card-text-lines']) s.lines = v['--card-text-lines'];
    [['borderColor', '--card-br-color'], ['band', '--card-band-bg'], ['divider', '--card-sep-color'], ['stars', '--card-star-color'],
        ['text', '--card-color'], ['date', '--time-color']].forEach(([k, n]) => { if (v[n]) s[k] = v[n]; });
    [['radius', '--card-radius'], ['starSize', '--card-star-size'], ['photo', '--card-img-size']].forEach(([k, n]) => { if (v[n]) s[k] = px(n); });
    return s;
}

// Card Options: a card preset (Card_Presets in PHP), its layout in rpi.CardEditor's rows and its skin, kept as the values of its
// controls; the preview root carries the skin as variables, and style_vars only what differs from the preset.
function grw_card_init(el, cfg, saved) {
    const E = rpi.Card, Ed = rpi.CardEditor,
        box = el.querySelector('.grw-card-ui'),
        esc = s => String(s).replace(/[&<>"']/g, c => '&#' + c.charCodeAt(0) + ';'),
        option = (html, cond) => '<div class="grw-builder-option"' + (cond ? ' data-if="' + cond + '"' : '') + '>' + html + '</div>',
        select = (cls, label, opts) => label + '<select class="' + cls + '" aria-label="' + label + '">' + opts.map(o => '<option value="' + o[0] + '">' + o[1] + '</option>').join('') + '</select>',
        color = (key, label) => '<input type="color" data-skin="' + key + '" aria-label="' + label + '"><input type="text" maxlength="7" aria-label="' + label + ' hex">' + label,
        range = (key, label, min, max, step) => label + '<div class="grw-range"><input type="range" data-skin="' + key + '" aria-label="' + label + '" min="' + min + '" max="' + max +
            '" step="' + step + '" data-postfix="px"><span class="grw-range-val"></span></div>',
        check = (key, label) => '<label><input type="checkbox" data-skin="' + key + '">' + label + '</label>',
        help = text => '<span class="grw-quest grw-quest-top grw-toggle" title="Click to help">?</span><div class="grw-quest-help" style="display:none;">' + text + '</div>';

    box.innerHTML =
        option('Card style<select name="card_style" aria-label="Card style">' +
            Object.keys(cfg.presets).map(id => '<option value="' + id + '">' + esc(cfg.presets[id].name) + '</option>').join('') + '</select>' +
            '<input type="hidden" name="card_layout" value="">') +
        option('<span class="grw-card-changes"></span> <a href="#" class="grw-card-reset">Reset to preset</a>', 'engine') +
        option('Card lines' + help('Drag rows by ⋮⋮, also between sections. The two squares put a row on the line above; the arrow to a bar pushes it to the right edge.') +
            '<div class="grw-card-lines"></div>', 'engine') +
        option(select('grw-card-photo', 'Photo', [['beside', 'Beside the rows below it'], ['above', 'On its own line'], ['top', 'On the top border']]) +
            '<div class="grw-card-hint" data-if="cardtop">Drag Photo to the very top of Section 1 to put it on the border.</div>', 'engine') +
        option(select('grw-card-logo', 'Google logo', [['corner', 'Corner'], ['photo', 'On the photo'], ['name', 'Next to the name'],
            ['stars', 'Next to the stars'], ['layout', 'In the rows above'], ['hidden', 'Hidden']]), 'engine') +
        option(select('grw-card-bg', 'Background', [['color', 'Color'], ['gradient', 'Gradient'], ['none', 'None']]), 'engine') +
        option(color('bg1', 'Background color'), 'engine cardbg') +
        option(color('bg2', 'Gradient end color'), 'engine cardgrad') +
        option(check('border', 'Border'), 'engine') +
        option(color('borderColor', 'Border color'), 'engine cardborder') +
        option(select('grw-card-shadow', 'Shadow', [['none', 'None'], ['light', 'Light'], ['soft', 'Soft'], ['grw', 'Classic']]), 'engine') +
        option(range('radius', 'Corner radius', 0, 24, 1), 'engine') +
        option(color('band', 'Band color'), 'engine cardband') +
        option(color('divider', 'Line color'), 'engine cardsep') +
        option(color('stars', 'Stars color'), 'engine') +
        option(range('starSize', 'Stars size', 12, 28, 1), 'engine') +
        option(color('text', 'Text color'), 'engine') +
        option(select('grw-card-textlines', 'Text lines', [['2', '2'], ['3', '3'], ['4', '4'], ['5', '5'], ['6', '6'], ['7', '7'], ['8', '8'], ['none', 'All']]) +
            help('Longer reviews end with "read more", which opens the rest in the card.'), 'engine') +
        option(color('date', 'Date color'), 'engine') +
        option(range('photo', 'Photo size', 32, 64, 2), 'engine') +
        option(check('gray', 'Gray photos'), 'engine');
    el.querySelector('.grw-card-code').innerHTML = 'Layout code' +
        help('The card lines as one line: sections "|", lines ",", side by side "+", groups "( )", hidden "-", push to the edge "~". ' +
            'The logo: "photo{logo}", "name{logo}", "stars{logo}", a row of its own, or ";logo" for the corner. Press Enter to apply.') +
        '<input type="text" class="large-text code grw-card-input" aria-label="Layout code" spellcheck="false" autocomplete="off">' +
        '<div class="grw-card-hint grw-card-note"></div><div class="grw-card-error" hidden></div>';

    const q = s => el.querySelector(s),
        card = q('[name="card_style"]'), layoutIn = q('[name="card_layout"]'), code = q('.grw-card-input'), photo = q('.grw-card-photo'), logo = q('.grw-card-logo'),
        root = () => document.getElementsByClassName('wp-gr')[0],
        preset = () => cfg.presets[card.value] || null,
        base = (p = preset()) => Object.assign({}, cfg.skin, p && p.ink ? cfg.ink : {}, p && p.skin || {}),
        // Classic under "Dark background" takes the colours the Standard card had there (public-main.css, .wp-dark). Only the builder
        // knows them: PHP renders a preset from base(), so style_vars must keep them as changes.
        DARK = {bg: 'none', text: '#ececec', date: '#bbbbbb'},
        // The Standard card's own dark background, when its owner had not hidden it.
        GLASS = '#ffffff14',
        dark = () => el.querySelector('input[name="dark_theme"]').checked,
        look = (p = preset()) => Object.assign(base(p), p === cfg.presets.c1 && dark() ? DARK : {}),
        layout = () => layoutIn.value || preset().layout;

    const vars = s => grw_card_vars(s, cfg.shadows);
    const pairs = grw_card_pairs;
    const read = sv => grw_card_read(sv, base(), cfg.shadows);

    // A widget saved before card styles had the Standard card: it opens as Classic with that card's look, which its first save keeps.
    const legacy = !cfg.presets[saved.card_style] && 'view_mode' in saved;
    card.value = cfg.presets[saved.card_style] ? saved.card_style : 'c1';
    layoutIn.value = card.value && saved.card_layout || '';
    // A widget without a place skips grw_deserialize_connections(), which fills style_vars for the others.
    if (!window.style_vars.value && saved.style_vars) window.style_vars.value = saved.style_vars;

    // The Standard card's look in Classic's controls (public-main.css: --rev-color, --rev-bg, --text-color, --star-color, grw-round,
    // grw-shadow, wp-dark; the options missing from old widgets are Core's defaults). The name colour is shared already.
    const legacySkin = () => {
        const v = pairs(window.style_vars.value), s = Object.assign(base(), saved.dark_theme ? DARK : {});
        if (saved.hide_backgnd || (v['--rev-bg'] || (saved.dark_theme ? 'transparent' : '')) === 'transparent') s.bg = 'none';
        else Object.assign(s, {bg: 'color', bg1: v['--rev-color'] || (saved.dark_theme ? GLASS : s.bg1)});
        s.radius = saved.show_round ? 8 : 0;
        s.shadow = saved.show_shadow ? 'grw' : 'none';
        if (v['--text-color']) s.text = v['--text-color'];
        if (v['--star-color']) s.stars = v['--star-color'];
        // Its fixed text height in lines of the Standard card's 24px, plus the line "read more" takes in a cut text: the 100px default is
        // five. A height such as auto or 100% showed the whole text; one the browser ignored left the default.
        const h = String(saved.slider_text_height || '').trim().toLowerCase(), n = /^(\d*\.?\d+)(px|r?em)?$/.exec(h);
        if (n && +n[1] > 0) s.lines = String(Math.min(8, Math.max(2, Math.round(n[1] * (n[2] && n[2] !== 'px' ? 16 : 1) / 24) + 1)));
        else if (/^(auto|unset|initial|inherit|revert|(min-|max-|fit-)content|\d*\.?\d+%)$/.test(h)) s.lines = 'none';
        return s;
    };
    let skin = legacy ? legacySkin() : read(window.style_vars.value), current = card.value;
    const changes = (p = preset()) => (layoutIn.value ? 1 : 0) + Object.keys(skin).filter(k => k !== 'name' && skin[k] !== look(p)[k]).length;
    const nameOver = () => pairs(window.style_vars.value)['--name-color'];

    // The skin on the preview root: the name colour is the shared control's when set. The CSS variables field reaches the root as
    // !important declarations rendered by PHP, and they must stay.
    function paint() {
        const rp = root();
        if (rp) {
            const v = preset() ? Object.assign(vars(skin), preset().vars) : {};
            cfg.vars.forEach(n => {
                if (rp.style.getPropertyPriority(n)) return;
                const val = n === '--name-color' ? nameOver() || v[n] : v[n];
                if (val) rp.style.setProperty(n, val);
                else rp.style.removeProperty(n);
            });
        }
        window.style_vars.value = grw_style_vars(rp);
    }

    // The shared name colour control shows the colour in use.
    function syncName() {
        const ctl = el.querySelector('input[name="--name-color"]');
        const v = nameOver() || (preset() ? skin.name : ctl.defaultValue);
        ctl.value = v;
        ctl.setAttribute('data-val', v);
        ctl.nextElementSibling.value = v;
    }

    function syncSkin() {
        q('.grw-card-bg').value = skin.bg;
        q('.grw-card-shadow').value = skin.shadow;
        q('.grw-card-textlines').value = skin.lines;
        box.querySelectorAll('[data-skin]').forEach(input => {
            if (input.type === 'checkbox') input.checked = skin[input.dataset.skin];
            else input.value = skin[input.dataset.skin];
            if (input.type === 'color') input.nextElementSibling.value = input.value;
        });
    }

    // The photo on the top border is not offered where the card would not draw it; the option's title says why.
    function syncSelects(view) {
        photo.value = view.photo;
        logo.value = view.logo;
        const top = photo.querySelector('option[value="top"]');
        top.title = view.sections.some(sec => sec.style === 'bubble') ? 'Not shown with a speech bubble'
            : view.sections[0].style === 'band' ? 'Not shown with a band on Section 1' : '';
        top.disabled = !!top.title;
    }

    // The rows show a layout as written when the layout they would save draws the same card.
    const stub = {slot: '', frame: ''};
    E.ELEMENTS.forEach(k => { stub[k] = (r, o) => '<' + k + ' ' + o.cls + (o.logo ? ' logo' : '') + '>'; });
    const drawn = s => E.render({}, E.parse(s), stub);
    const note = s => {
        q('.grw-card-note').textContent = drawn(Ed.canonical(s)) === drawn(s) ? ''
            : 'This layout has more nesting than the rows above can show. It renders as written; editing the rows will simplify it.';
    };

    function syncLayout() {
        const view = Ed.project(E.parse(layout()));
        lines.set(view);
        syncSelects(view);
        code.value = layout();
        q('.grw-card-error').hidden = true;
        note(layout());
    }

    function state() {
        const n = changes();
        q('.grw-card-changes').textContent = (n ? n + (n === 1 ? ' change' : ' changes') : 'No changes') + (preset() ? ' to ' + preset().name : '');
        q('.grw-card-reset').hidden = !n;
    }

    function setLayout(str) {
        layoutIn.value = str === preset().layout ? '' : str;
        state();
        grw_serialize_connections();
    }

    function setSkin(key, val) {
        skin[key] = val;
        paint();
        state();
        grw_autosave();
    }

    // A new preset, or the same one without its changes; the shared name colour stays.
    function reset(save) {
        current = card.value;
        layoutIn.value = '';
        if (preset()) syncLayout();
        resetSkin();
        grw_builder_refresh();
        if (save) grw_serialize_connections();
    }

    function resetSkin() {
        skin = look();
        if (preset()) {
            syncSkin();
            state();
        }
        paint();
        syncName();
    }

    const lines = Ed.Lines(q('.grw-card-lines'), (str, view) => {
        syncSelects(view);
        code.value = str;
        q('.grw-card-error').hidden = true;
        note(str);
        setLayout(str);
    }, {logo: 'Google logo'});

    card.addEventListener('change', () => {
        // The select already shows the new preset: the changes counted are the ones made to the preset left.
        if (cfg.presets[current] && changes(cfg.presets[current]) && !confirm('Discard your changes to ' + cfg.presets[current].name + '?')) {
            card.value = current;
            return;
        }
        reset(true);
    });
    q('.grw-card-reset').addEventListener('click', e => {
        e.preventDefault();
        reset(true);
        // The link hides itself once nothing is changed, which would leave the keyboard nowhere.
        card.focus();
    });
    photo.addEventListener('change', () => lines.edit(v => Ed.photoMode(v, photo.value)));
    logo.addEventListener('change', () => lines.edit(v => { v.logo = logo.value; }));
    q('.grw-card-bg').addEventListener('change', e => setSkin('bg', e.target.value));
    q('.grw-card-shadow').addEventListener('change', e => setSkin('shadow', e.target.value));
    q('.grw-card-textlines').addEventListener('change', e => setSkin('lines', e.target.value));
    box.querySelectorAll('[data-skin]').forEach(input => {
        const key = input.dataset.skin, hex = input.nextElementSibling;
        if (input.type === 'checkbox') return input.addEventListener('change', () => setSkin(key, input.checked));
        if (input.type === 'range') return input.addEventListener('input', () => setSkin(key, parseInt(input.value, 10)));
        input.addEventListener('input', () => {
            hex.value = input.value;
            setSkin(key, input.value);
        });
        hex.addEventListener('input', () => {
            if (/^#[0-9a-f]{6}$/i.test(hex.value)) setSkin(key, input.value = hex.value.toLowerCase());
        });
        // "#abc" is taken when the field is left; anything unreadable is put back to the colour in use.
        hex.addEventListener('change', () => {
            const m = /^#?([0-9a-f]{3}|[0-9a-f]{6})$/i.exec(hex.value.trim());
            if (m) setSkin(key, input.value = '#' + (m[1].length === 3 ? m[1].replace(/./g, c => c + c) : m[1]).toLowerCase());
            hex.value = input.value;
        });
    });
    const apply = () => {
        const err = q('.grw-card-error');
        let str;
        try {
            str = E.serialize(E.parse(code.value));
            Ed.canonical(str);
        } catch (e) {
            err.textContent = e.message;
            err.hidden = false;
            return;
        }
        err.hidden = true;
        code.value = str;
        if (str === layout()) return;
        layoutIn.value = str === preset().layout ? '' : str;
        syncLayout();
        state();
        grw_serialize_connections();
    };
    code.addEventListener('keydown', e => {
        if (e.key === 'Enter') {
            e.preventDefault();
            apply();
        }
    });
    code.addEventListener('change', apply);
    // "Dark background" swaps Classic's light and dark colours, in the capture phase: before the checkbox's own handler saves the
    // widget. Colours picked by hand stay; a card background picked by hand also keeps the text and date that go with it.
    el.addEventListener('change', e => {
        if (e.target.name !== 'dark_theme' || preset() !== cfg.presets.c1) return;
        const light = base(), dk = Object.assign(base(), DARK), [from, to] = e.target.checked ? [light, dk] : [dk, light];
        const glass = !e.target.checked && skin.bg === 'color' && skin.bg1 === GLASS;
        if (!glass && ['bg', 'bg1', 'bg2'].some(x => skin[x] !== from[x])) return;
        if (glass) Object.assign(skin, {bg1: light.bg1});
        Object.keys(DARK).forEach(k => { if (skin[k] === from[k]) skin[k] = to[k]; });
        syncSkin();
        paint();
        state();
    }, true);

    if (preset()) {
        syncLayout();
        syncSkin();
        state();
    }
    syncName();

    return {
        resetSkin,
        repaint() {
            if (preset() && root() && root().querySelector('.rpi-card')) paint();
            // A list, or no place yet: no card to paint, but the old look must reach style_vars before the first save.
            else if (legacy) window.style_vars.value = grw_style_vars(root());
        },
        // style_vars from the preview root's list: card variables from the skin; the name colour only the user's, its last declaration (the root has the preset's first).
        vars(list) {
            const b = preset() ? vars(base()) : {}, v = preset() ? vars(skin) : {},
                name = list.map(p => p.split(':')[0].trim()).lastIndexOf('--name-color');
            return list.filter((p, i) => {
                const n = p.split(':')[0].trim();
                return cfg.vars.indexOf(n) < 0 || i === name && (nameOver() || p.slice(p.indexOf(':') + 1).trim() !== b[n]);
            }).concat(Object.keys(v).filter(n => n !== '--name-color' && v[n] !== b[n]).map(n => n + ':' + v[n]));
        },
        flags(cards) {
            const engine = cards && !!preset(), rp = root(), m = engine && E.parse(layout());
            const lead = m && m.sections[0] && m.sections[0].items[0];
            return {
                engine,
                cardbg: engine && skin.bg !== 'none',
                cardgrad: engine && skin.bg === 'gradient',
                cardborder: engine && skin.border,
                cardband: engine && !!rp && !!rp.querySelector('.rpi-band'),
                cardsep: engine && !!rp && !!rp.querySelector('.rpi-sep'),
                cardtop: engine && photo.value === 'top' && !(lead && (lead.el === 'photo' || lead.dir === 'row' && lead.items[0].el === 'photo'))
            };
        }
    };
}

function grw_builder_init($, data) {

    var el = document.querySelector(data.el);
    if (!el) return;

    const lang = GRW_VARS.lang ? GRW_VARS.lang.toLowerCase().split(/[_-]/)[0] : '';

    el.innerHTML = GRW_HTML_CONTENT.replace('{{wizard}}',
        data.key ? GRW_WIZARD2 : GRW_WIZARD.replace('{{authcode}}', data.authcode).replace('{{lang}}', lang));

    //var $connect_wizard_el = $('#grw-connect-wizard');
    GRW_LIGHTBOX = rpi.Lightbox(window.gpidc);

    if (data.conns && data.conns.connections && data.conns.connections.length) {
        grw_deserialize_connections($, el, data);
    } else {
        //$('.grw-connect-google').hide();
        GRW_LIGHTBOX.show(true);
        window.gpidc.focus();
        /*$connect_wizard_el.dialog({
            modal: false,
            width: '50%',
            maxWidth: '600px',
            closeOnEscape: false,
            open: function() { $(".ui-dialog-titlebar-close").hide() }
        });*/
    }

    // GPIDC
    window.onmessage = function(e) {
        if (e.origin !== 'https://app.richplugins.com') return;
        if (e.data) {
            let gdata = e.data;
            switch (gdata.action) {
                case 'get_place':
                    $.post(ajaxurl, {
                        pid       : gdata.pid,
                        lang      : gdata.lang,
                        token     : gdata.token,
                        action    : 'grw_get_place',
                        grw_nonce : jQuery('#grw_nonce').val()
                    }, function(res) {
                        if (res.status == 'success') {
                            res.result.place_id = gdata.pid;
                            window.gpidc.contentWindow.postMessage({data: res, action: 'set_place'}, '*');
                        } else {
                            grw_connect_error($, grw_get_error(res));
                        }
                    });
                    break;
                case 'connect':
                    grw_connect_ajax($, el, gdata, data.authcode, 1, function() {
                        window.gpidc.contentWindow.postMessage({action: 'connect_done'}, '*');
                    });
                    break;
            }
        }
    };

    // Init slider breakpoints
    grw_sbs_init();

    const order = el.querySelector('input[name="header_order"]'),
        box = name => el.querySelector('input[name="' + name + '"]');
    order.parentNode.append(rpi.Order(order, [
        {key: 'scale',    label: 'Rating label (Top rated, Excellent...)', input: box('header_show_scale')},
        {key: 'name',     label: 'Business name',                          input: box('header_hide_name'), invert: true},
        {key: 'stars',    label: 'Stars'},
        {key: 'based',    label: '\'Based on ... reviews\'',                input: box('hide_based_on'), invert: true},
        {key: 'platform', label: 'Use short Google logo',                  input: box('header_platform')}
    ]));
    order.addEventListener('change', grw_serialize_connections);

    $('.grw-connect-options input[type="text"]:not([name^="--"]),.grw-connect-options textarea').keyup(function() {
        grw_autosave();
    });
    // Pasting or dropping text with the mouse fires no keyup.
    const cssVars = document.querySelector('.grw-connect-options textarea[name="css_vars"]');
    if (cssVars) cssVars.addEventListener('input', grw_autosave);
    $('.grw-connect-options input[type="checkbox"][name]:not([name^="--"]),.grw-connect-options select').change(function() {
        grw_serialize_connections();
    });
    $('.grw-connect-options input[name^="--"]').on('input', function() {
        grw_stylechange2(this);
        grw_autosave();
    });
    $('.grw-connect-options input[type="color"][name^="--"] + input[type="text"]').keyup(function() {
        if (this.value.indexOf('#') == 0 && this.value.length == 7) {
            this.previousElementSibling.value = this.value;
            this.previousElementSibling.dispatchEvent(new Event('input'));
        }
    });

    // After the handlers above, which would save the card's own controls as options; before the help toggles, which it uses.
    GRW_CARD = data.cards ? grw_card_init(el, data.cards, data.conns && data.conns.options || {}) : null;
    // Loading a widget without style variables of its own has just cleared the preview root, the preset's skin with it.
    if (GRW_CARD) GRW_CARD.repaint();

    $('.grw-toggle', el).unbind('click').click(function () {
        $(this).toggleClass('toggled');
        $(this).next().slideToggle();
    });

    const panel = el.querySelector('.grw-connect-options');
    panel.addEventListener('change', grw_builder_refresh);
    panel.addEventListener('input', grw_builder_refresh);
    panel.querySelector('.grw-style-reset').onclick = function() {
        if (confirm('Reset all colors and sizes to their defaults?')) {
            stylereset();
            grw_serialize_connections();
        }
        return false;
    };
    grw_builder_refresh();

    $('.grw-builder-connect.grw-connect-google').click(function () {
        GRW_LIGHTBOX.show(true);
        //$connect_wizard_el.dialog({modal: true, width: '50%', maxWidth: '600px'});
    });

    if ($('.grw-connections').sortable) {
        $('.grw-connections').sortable({
            stop: function(event, ui) {
                grw_serialize_connections();
            }
        });
        $('.grw-connections').disableSelection();
    }

    $('.wp-review-hide').click(function() {
        grw_review_hide($(this));
        return false;
    });

    $('#grw_save').click(function() {
        const url = new URL(window.location.href);
        if (url.searchParams.has('grw_feed_id')) {
            grw_serialize_connections();
            return false;
        } else {
            return true;
        }
    });

    let _isSubmit = false;
    const form = document.querySelector('.grw-builder form');
    form.addEventListener('submit', function () {
        _isSubmit = true;
    });

    // Confirmation alert before close the page if unautosave
    window.addEventListener('beforeunload', function(e) {
        const url = new URL(window.location.href);
        if (window.grw_post_id.value && !_isSubmit && (!url.searchParams.has('grw_feed_id') || GRW_AUTOSAVE_TIMEOUT || GRW_SAVE_AGAIN || GRW_SAVE_FAILED)) {
            e.preventDefault();
            e.returnValue = '';
        }
    });

    if (data.key) {
        window.grw_place_lang.innerHTML = '<option value="" selected="selected">Choose language if needed</option>' + grw_lang_options();
        window.grw_place_autocomplete.focus();
        window.grw_place_autocomplete.addEventListener('keyup', grw_input_keyup);
    }
}

function grw_input_keyup(e) {
    clearTimeout(window.keyupTimeout);

    if (e.keyCode === 13) return;

    let val = this.value.trim();
    if (val && /^(\s+|\S+)+$/giu.test(val)) {
        grw_place_spin(1);
        if (/^ChIJ\S+$/giu.test(val)) {
            grw_get_place(val);
        } else {
            keyupTimeout = setTimeout(grw_on_keyup, 1000, e, this.value.trim());
        }
    } else {
        grw_place_spin();
    }
}

function grw_on_keyup(e, val) {
    if (val && val != window.grw_keyup_val) {
        grw_keyup_val = val;
        e.preventDefault();

        jQuery.post(ajaxurl, {
            input: val,
            action: 'grw_place_autocomplete',
            grw_nonce: jQuery('#grw_nonce').val()
        }, function(res) {
            grw_place_spin();
            window.grw_place_list.innerHTML = '';
            if (res && res.error_message) {
                window.grw_place_error.innerHTML = res.error_message;
                return;
            }

            for (let i = 0; i < res.predictions.length; i++) {
                (function(pred) {
                    let a       = document.createElement('a');
                    a.href      = '#';
                    a.className = 'dropdown-item';
                    a.role      = 'option';
                    a.innerHTML = '<div class="location-name">' + grw_esc(pred.structured_formatting.main_text) + '</div>';
                    if (pred.structured_formatting.secondary_text) {
                        a.innerHTML += '<small class="location-address">' + grw_esc(pred.structured_formatting.secondary_text) + '</small>';
                    }
                    a.onclick = function(e) {
                        e.preventDefault();
                        grw_get_place(pred.place_id);
                        grw_place_spin(1);
                    };

                    let li = document.createElement('li');
                    li.appendChild(a);
                    window.grw_place_list.appendChild(li);
                })(res.predictions[i]);
            }
            window.grw_place_list.style.display = 'block';
        });
    } else {
        grw_place_spin();
    }
}

function grw_get_place(pid) {
    jQuery.post(ajaxurl, {
        pid       : pid,
        action    : 'grw_get_place',
        grw_nonce : jQuery('#grw_nonce').val()
    }, function(res) {
        grw_place_spin();
        window.grw_place_list.innerHTML = '';
        const err = grw_get_error(res);
        if (err) {
            window.grw_place_error.innerHTML = err;
            return;
        }
        grw_set_place(pid, res.result);
    });
}

function grw_set_place(pid, place) {
    let img    = window.grw_place.getElementsByTagName('img')[0],
        name   = window.grw_place.getElementsByClassName('wp-google-name')[0],
        rating = window.grw_place.getElementsByClassName('wp-google-rating')[0],
        stars  = window.grw_place.getElementsByClassName('rpi-stars')[0],
        based  = window.grw_place.getElementsByClassName('wp-google-based')[0];

    img.src          = place.photo || place.icon;
    name.textContent   = place.name;
    rating.textContent = place.rating;
    stars.style.setProperty('--rating', place.rating || 0);
    based.textContent  = place.user_ratings_total;

    window.grw_place_list.style.display = 'none';
    window.grw_place.style.display = 'block';

    // Default review language from the place's own country (rpi.Langs ships
    // in this bundle from the CDN), site locale as the fallback. Only while
    // the select is untouched — a manual pick always wins.
    if (window.grw_place_lang && !window.grw_place_lang.value) {
        const site = GRW_VARS.lang ? GRW_VARS.lang.toLowerCase().split(/[_-]/)[0] : '';
        const lang = rpi.Langs.langForCountry(place.country) || site;
        if (lang && rpi.Langs.google.some(l => l.v === lang)) {
            window.grw_place_lang.value = lang;
        }
    }

    window.grw_place_btn.onclick = function(e) {
        e.preventDefault();

        let btn = e.target;
        btn.disabled = true;

        const params = {
            id: pid,
            lang: window.grw_place_lang.value
        };
        grw_connect_ajax(jQuery, document, params, null, 1, function() {
            btn.disabled = false;
            grw_place_spin();
            grw_wizard_close();
        });
        grw_place_spin(1);
    }
}

function grw_place_spin(s) {
    window.grw_place_spin_svg.style.display = s ? 'block' : 'none';
}

// Saves go one at a time: changes made while one runs are saved together, as they are then, once it returns.
var GRW_SAVING = false,
    GRW_SAVE_AGAIN = false,
    GRW_SAVE_FAILED = false;

function grw_feed_save_ajax() {
    if (!window.grw_title.value) {
        window.grw_title.focus();
        return false;
    }
    if (GRW_SAVING) {
        GRW_SAVE_AGAIN = true;
        return;
    }
    GRW_SAVING = true;

    window.grw_save.innerText = 'Auto save, wait';
    window.grw_save.disabled = true;

    jQuery.post(ajaxurl, {

        post_id   : window.grw_post_id.value,
        title     : window.grw_title.value,
        content   : document.getElementById('grw-builder-connection').value,
        action    : 'grw_feed_save_ajax',
        grw_nonce : jQuery('#grw_nonce').val()

    }, function(res) {

        GRW_SAVE_FAILED = false;
        if (!window.grw_post_id.value) {
            const tpl = document.createElement('template');
            tpl.innerHTML = res;
            const root = tpl.content.querySelector('.wp-gr');
            if (root) {
                const post_id = root.getAttribute('data-id');
                const toolbar_el = document.querySelector('.grw-toolbar-control');
                const label = document.createElement('label');
                label.innerHTML = '<span id="grw_sc_msg">Copy Shortcode </span> <input id="grw_sc" type="text" value="[grw id=' + post_id + ']" data-grw-shortcode="[grw id=' + post_id + ']" onclick="this.select(); document.execCommand(\'copy\'); window.grw_sc_msg.innerHTML = \'Shortcode Copied! Paste on page. \';" readonly="">';
                toolbar_el.insertBefore(label, window.grw_save);
                window.grw_post_id.value = post_id;
                //window.location.href = GRW_VARS.builderUrl + '&grw_feed_id=' + post_id + '&grw_feed_new=1';
            }
        }

        // A newer state is waiting to be saved: this preview would undo the edits made since.
        if (GRW_SAVE_AGAIN || GRW_AUTOSAVE_TIMEOUT) return;

        rpi.Utils.clear();

        var wpgr = document.querySelectorAll('.wp-gr');
        for (var i = 0; i < wpgr.length; i++) {
            wpgr[i].parentNode.removeChild(wpgr[i]);
        }

        window.grw_collection_preview.innerHTML = res;

        window.grw_boot && window.grw_boot();
        grw_builder_refresh();

        jQuery('.wp-review-hide').unbind('click').click(function() {
            grw_review_hide(jQuery(this));
            return false;
        });

        var $rateus = jQuery('#grw-rate_us');
        if ($rateus.length && !$rateus.hasClass('grw-flash-visible') && !window['grw_rateus']) {
            $rateus.addClass('grw-flash-visible');
        }

    }).fail(function() {
        GRW_SAVE_FAILED = true;
        GRW_TOAST.show({msg: 'The widget was not saved. Click Save & Update to try again.', type: 'error'});
    }).always(function() {
        GRW_SAVING = false;
        if (GRW_SAVE_AGAIN) {
            GRW_SAVE_AGAIN = false;
            grw_feed_save_ajax();
        } else {
            window.grw_save.innerText = 'Save & Update';
            window.grw_save.disabled = false;
        }
    });
}

function grw_review_hide($this) {
    let id = $this.attr('data-id'),
        hids = window.hidden_ids.value;
    if (hids) {
        hids = hids.split(',');
        let idx = hids.indexOf(id);
        if (idx < 0) {
            hids.push(id);
        } else {
            hids.splice(idx, 1);
        }
    } else {
        hids = [id];
    }
    window.hidden_ids.value = hids.join(',');
    grw_serialize_connections();
}

function grw_review_hide_global($this) {
    jQuery.post(ajaxurl, {

        id          : $this.attr('data-id'),
        feed_id     : jQuery('input[name="grw_feed[post_id]"]').val(),
        grw_wpnonce : jQuery('#grw_nonce').val(),
        action      : 'grw_hide_review'

    }, function(res) {
        var parent = $this.parent().parent();
        if (res.hide) {
            $this.text('show review');
            parent.addClass('wp-review-hidden');
        } else {
            $this.text('hide review');
            parent.removeClass('wp-review-hidden');
        }
    }, 'json');
}

function grw_connect_ajax($, el, params, authcode, attempt, cb) {

    var platform = 'google',
        connect_btn = el.querySelector('.grw-connect-btn'),
        local_img = params.local_img !== undefined ? params.local_img : true;

    window.grw_save.innerText = 'Auto save, wait';
    window.grw_save.disabled = true;

    const args = {
        id          : decodeURIComponent(params.id),
        url         : params.url,
        lang        : params.lang,
        local_img   : local_img,
        token       : params.token,
        feed_id     : $('input[name="grw_feed[post_id]"]').val(),
        grw_wpnonce : $('#grw_nonce').val(),
        action      : 'grw_connect_google',
        v           : new Date().getTime()
    };

    $.post(ajaxurl, args, function(res) {

        if (res.status == 'success') {

            let error_els = document.querySelectorAll('.grw-connect-error');
            for (let i = 0; i < error_els.length; i++) {
                error_els[i].innerHTML = '';
            }

            grw_wizard_close();

            let props = {
                default_photo : res.result.photo
            };
            if (res.result.map_url) {
                props.map_url = res.result.map_url;
            }

            let connection_params = {
                id        : res.result.id,
                lang      : params.lang,
                name      : res.result.name,
                photo     : res.result.photo,
                refresh   : true,
                local_img : local_img,
                platform  : platform,
                props     : props
            };

            grw_connection_add($, el, connection_params);
            grw_serialize_connections();

            GRW_TOAST.show({
                msg: (params.event === 'refresh' ? 'Reviews updated' : 'Widget saved') + ' successfully. ' +
                     (res.result.credits > -1 ? '<br><b>' + res.result.credits + ' attempts remaining (without your API key).</b>' : ''),
                type: 'success'
            });

        } else {
            const err = grw_get_error(res);
            GRW_TOAST.show({msg: err, type: 'error'});
            window.grw_save.innerText = 'Save & Update';
            window.grw_save.disabled = false;
            /*if (params.event === 'refresh') {
                GRW_TOAST.show({msg: err, type: 'error'});
            } else {
                grw_connect_error($, err, function() {
                    if (attempt > 1) return;
                    if (window.gpidc) {
                        grw_popup('https://app.richplugins.com/gpaw/botcheck?authcode=' + authcode, 640, 480, function() {
                            window.gpidc.contentWindow.postMessage({params: params, action: 'connect'}, '*');
                        });
                    }
                });
            }*/
        }

        cb && cb(res);

    }, 'json');
}

function grw_wizard_close() {
    GRW_LIGHTBOX.hide();
    /*try {
        jQuery('#grw-connect-wizard').dialog('close');
    } catch (e) {}*/
}

function grw_connect_error($, error_message, cb) {

    let error_els = document.querySelectorAll('.grw-connect-error');
    for (let i = 0; i < error_els.length; i++) {
        error_els[i].innerHTML = '';
    }

    switch (error_message) {

        case 'usage_limit':
            $('#dialog').dialog({width: '50%', maxWidth: '600px'});
            break;

        case 'bot_check':
            cb && cb();
            break;

        default:
            for (let i = 0; i < error_els.length; i++) {
                error_els[i].innerHTML = '<b>Error</b>: ' + error_message;
            }
    }

    window.grw_save.innerText = 'Save & Update';
    window.grw_save.disabled = false;
}

function grw_connection_add($, el, conn, checked, append) {

    var connected_id = grw_connection_id(conn),
        connected_el = $('#' + connected_id);

    if (connected_el.length) {
        if (conn.photo) {
            grw_change_photo(connected_el, conn.photo);
        }
        if (conn.props && conn.props.map_url) {
            let propsEl = connected_el.children().eq(1);
            let mapUrlEl = $('input[name="map_url"]', propsEl);
            if (mapUrlEl.length) {
                mapUrlEl.remove();
            }
            propsEl.prepend('<input type="hidden" name="map_url" value="' + grw_esc(conn.props.map_url) + '" class="grw-connect-prop" readonly="">');
        }
    } else if (!connected_el.length) {
        connected_el = $('<div class="grw-connection"></div>')[0];
        connected_el.id = connected_id;
        if (conn.lang != undefined) {
            connected_el.setAttribute('data-lang', conn.lang);
        }
        connected_el.setAttribute('data-platform', conn.platform);
        connected_el.innerHTML = grw_connection_render(conn, checked);

        var connections_el = $('.grw-connections')[0];
        if (append) {
            connections_el.appendChild(connected_el);
        } else {
            connections_el.prepend(connected_el);
        }

        jQuery('.grw-toggle', connected_el).unbind('click').click(function () {
            jQuery(this).toggleClass('toggled');
            jQuery(this).next().slideToggle();
        });

        var file_frame;
        jQuery('.grw-connect-photo-change', connected_el).on('click', function(e) {
            e.preventDefault();
            grw_upload_photo(connected_el, file_frame, function() {
                grw_serialize_connections();
            });
            return false;
        });

        jQuery('.grw-connect-photo-default', connected_el).on('click', function(e) {
            grw_change_photo(connected_el, conn.props.default_photo);
            grw_serialize_connections();
            return false;
        });

        $('input[type="text"]', connected_el).keyup(function() {
            grw_autosave();
        });

        $('input[type="checkbox"]', connected_el).click(function() {
            grw_serialize_connections();
        });

        $('select.grw-connect-lang', connected_el).change(function() {
            conn.lang = this.value;
            connected_el.id = grw_connection_id(conn);
            connected_el.setAttribute('data-lang', this.value);
            grw_reconnect($, el, conn);
            return false;
        });

        $('input[name="local_img"]', connected_el).unbind('click').click(function() {
            conn.local_img = this.checked;
            grw_reconnect($, el, conn);
        });

        $('.grw-connect-reconnect', connected_el).click(function() {
            grw_reconnect($, el, conn);
            return false;
        });

        $('.grw-connect-delete', connected_el).click(function() {
            if (confirm('Are you sure to delete this business?')) {
                $(connected_el).remove();
                grw_serialize_connections();
            }
            return false;
        });
    }
}

function grw_reconnect($, el, conn) {
    if (window.gpidc instanceof HTMLIFrameElement) {
        /*if (conn.props && !conn.props.map_url) {
            grw_show_wizard($, '', conn.lang);
        } else {*/
            conn.event = 'refresh';
            conn.url = conn.props.map_url;
            window.grw_save.disabled = true;
            window.grw_save.innerText = 'Updating...';
            window.gpidc.contentWindow.postMessage({params: conn, action: 'connect'}, '*');
        //}
    } else {
        grw_connect_ajax($, el, conn, null, 1);
    }
}

/*function grw_show_wizard($, title, lang) {
    if (lang) {
        window.gpidc.src = window.gpidc.src.replace(/&lang=.+/, '&lang=' + lang);
    }
    $('#grw-connect-wizard').dialog({
        title: title || 'Please copy & paste Google map URL for this location in the field below and click Connect button to refresh reviews',
        modal: true,
        width: '50%',
        maxWidth: '600px'
    });
}*/

function grw_connection_id(conn) {
    var id = 'grw-' + conn.platform + '-' + conn.id.replace(/\//g, '');
    if (conn.lang != null) {
        id += conn.lang;
    }
    return id;
}

function grw_connection_render(conn, checked) {
    var name = conn.name;
    if (conn.lang) {
        name += ' (' + conn.lang + ')';
    }

    conn.photo = conn.photo || 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';

    var option = document.createElement('option');
    if (conn.platform == 'google' && conn.props && conn.props.place_id) {
        option.value = conn.props.place_id;
    } else {
        option.value = conn.id;
    }
    option.text = grw_capitalize(conn.platform) + ': ' + conn.name;

    return '' +
        '<div class="grw-toggle grw-builder-connect grw-connect-business">' +
            '<input type="checkbox" class="grw-connect-select" onclick="event.stopPropagation();" ' + (checked?'checked':'') + ' /> ' +
            grw_esc(name) + (conn.address ? ' (' + grw_esc(conn.address) + ')' : '') +
        '</div>' +
        '<div style="display:none">' +
            (function(props) {
                var result = '';
                for (prop in props) {
                    if (prop != 'platform' && Object.prototype.hasOwnProperty.call(props, prop)) {
                        result += '<input type="hidden" name="' + grw_esc(prop) + '" value="' + grw_esc(props[prop]) + '" class="grw-connect-prop" readonly />';
                    }
                }
                return result;
            })(conn.props) +
            '<input type="hidden" name="id" value="' + grw_esc(conn.id) + '" readonly />' +
            (conn.address ? '<input type="hidden" name="address" value="' + grw_esc(conn.address) + '" readonly />' : '') +
            (conn.access_token ? '<input type="hidden" name="access_token" value="' + grw_esc(conn.access_token) + '" readonly />' : '') +
            '<div class="grw-builder-option">' +
                '<img src="' + grw_esc(conn.photo) + '" alt="' + grw_esc(conn.name) + '" class="grw-connect-photo">' +
                '<a href="#" class="grw-connect-photo-change">Change</a>' +
                '<a href="#" class="grw-connect-photo-default">Default</a>' +
                '<input type="hidden" name="photo" class="grw-connect-photo-hidden" value="' + grw_esc(conn.photo) + '" tabindex="2"/>' +
            '</div>' +
            '<div class="grw-builder-option">' +
                '<input type="text" name="name" value="' + grw_esc(conn.name) + '" />' +
            '</div>' +
            (conn.website != undefined ?
            '<div class="grw-builder-option">' +
                '<input type="text" name="website" value="' + grw_esc(conn.website) + '" />' +
            '</div>'
            : '' ) +
            (conn.lang != undefined ?
            '<div class="grw-builder-option">' +
                //'<input type="text" name="lang" value="' + conn.lang + '" placeholder="Default language (English)" />' +
                grw_lang('Show all', conn.lang) +
            '</div>'
            : '' ) +
            (conn.review_count != undefined ?
            '<div class="grw-builder-option">' +
                '<input type="text" name="review_count" value="' + conn.review_count + '" placeholder="Total number of reviews" />' +
                '<span class="grw-quest grw-toggle" title="Click to help">?</span>' +
                '<div class="grw-quest-help">Google return only 5 most helpful reviews and does not return information about total number of reviews and you can type here it manually.</div>' +
            '</div>'
            : '' ) +
            (conn.refresh != undefined ?
            '<div class="grw-builder-option">' +
                '<label>' +
                    '<input type="checkbox" name="refresh" ' + (conn.refresh ? 'checked' : '') + '> Include in auto-update<br>' +
                    '<small>Works only with your own Google API key</small>' +
                '</label>' +
                /*'<span class="grw-quest grw-quest-top grw-toggle" title="Click to help">?</span>' +
                '<div class="grw-quest-help">' +
                    (conn.platform == 'google' ? 'The plugin uses the Google Places API to get your reviews. <b>The API only returns the 5 most helpful reviews (it\'s a limitation of Google, not the plugin)</b>. This option calls the Places API once in 24 hours (to keep the plugin\'s free and avoid a Google Billing) to check for a new reviews and if there are, adds to the plugin. Thus slowly building up a database of reviews.<br><br>Also if you see the new reviews on Google map, but after some time it\'s not added to the plugin, it means that Google does not include these reviews to the API and the plugin can\'t get this.<br><br>If you need to show <b>all reviews</b>, please use <a href="https://richplugins.com/business-reviews-bundle-wordpress-plugin?code=SUMR26#pricing" target="_blank">Business plugin</a> which uses a Google My Business API without API key and billing.' : '') +
                    (conn.platform == 'yelp' ? 'The plugin uses the Yelp API to get your reviews. <b>The API only returns the 3 most helpful reviews without sorting possibility.</b> When Yelp changes the 3 most helpful the plugin will automatically add the new one to your database. Thus slowly building up a database of reviews.' : '') +
                '</div>' +*/
            '</div>'
            : '' ) +
            '<div class="grw-builder-option">' +
                '<label>' +
                    '<input type="checkbox" name="local_img" ' + (conn.local_img ? 'checked' : '') + '>' +
                    'Save images locally (GDPR)' +
                '</label>' +
            '</div>' +
            '<div class="grw-builder-option">' +
                '<button class="grw-connect-reconnect">Update reviews</button>' +
            '</div>' +
            '<div class="grw-builder-option">' +
                '<button class="grw-connect-delete">Delete connection</button>' +
            '</div>' +
            '<div class="grw-builder-option">' +
                '<small class="grw-connect-error"></small>' +
            '</div>' +
        '</div>';
}

function grw_serialize_connections() {

    var connections = [],
        connections_el = document.querySelectorAll('.grw-connection');

    for (var i in connections_el) {
        if (Object.prototype.hasOwnProperty.call(connections_el, i)) {

            var select_el = connections_el[i].querySelector('.grw-connect-select');
            if (select_el && !grw_is_hidden(select_el) && !select_el.checked) {
                continue;
            }

            var connection = {},
                lang       = connections_el[i].getAttribute('data-lang'),
                platform   = connections_el[i].getAttribute('data-platform'),
                inputs     = connections_el[i].querySelectorAll('input');

            //connections[platform] = connections[platform] || [];

            if (lang != undefined) {
                connection.lang = lang;
            }

            for (var j in inputs) {
                if (Object.prototype.hasOwnProperty.call(inputs, j)) {
                    var input = inputs[j],
                        name = input.getAttribute('name');

                    if (!name) continue;

                    if (input.className == 'grw-connect-prop') {
                        connection.props = connection.props || {};
                        connection.props[name] = input.value;
                    } else {
                        connection[name] = (input.type == 'checkbox' ? input.checked : input.value);
                    }
                }
            }
            connection.platform = platform;
            connections.push(connection);
        }
    }

    var options = {},
        options_el = document.querySelector('.grw-connect-options').querySelectorAll('input[name],select,textarea');

    for (var o in options_el) {
        if (Object.prototype.hasOwnProperty.call(options_el, o)) {
            var input = options_el[o],
                name  = input.getAttribute('name');

            if (!name && input.closest('.grw-card-ui')) continue;
            if (input.type == 'checkbox') {
                options[name] = input.checked;
            } else if (input.value != undefined) {
                // css_vars goes as plain text: the server's sanitize_textarea_field() strips %-encoded characters.
                options[name] = (
                                    input.type == 'textarea' && name != 'css_vars' ||
                                    name       == 'word_filter'  ||
                                    name       == 'word_exclude' ?
                                    encodeURIComponent(input.value) : input.value
                                );
            }
        }
    }

    document.getElementById('grw-builder-connection').value = JSON.stringify({connections: connections, options: options});

    if (connections.length) {
        var first = connections[0],
            title = window.grw_title.value;

        if (!title) {
            window.grw_title.value = first.name;
        }
        grw_feed_save_ajax();
    } else {
        /*var connect_google_el = document.querySelector('.grw-connect-google-inside'),
            google_pid_el = document.querySelector('.grw-connect-id');

        connect_google_el.style = '';
        google_pid_el.focus();*/
    }
}

function grw_deserialize_connections($, el, data) {
    var connections = data.conns,
        options = connections.options;

    if (Array.isArray(connections.connections)) {
        connections = connections.connections;
    } else {
        var temp_conns = [];
        if (Array.isArray(connections.google)) {
            for (var c = 0; c < connections.google.length; c++) {
                connections.google[c].platform = 'google';
            }
            temp_conns = temp_conns.concat(connections.google);
        }
        if (Array.isArray(connections.facebook)) {
            for (var c = 0; c < connections.facebook.length; c++) {
                connections.facebook[c].platform = 'facebook';
            }
            temp_conns = temp_conns.concat(connections.facebook);
        }
        if (Array.isArray(connections.yelp)) {
            for (var c = 0; c < connections.yelp.length; c++) {
                connections.yelp[c].platform = 'yelp';
            }
            temp_conns = temp_conns.concat(connections.yelp);
        }
        connections = temp_conns;
    }

    let $bp = el.querySelector('.grw-builder-platforms');
    for (var i = 0; i < connections.length; i++) {
        grw_connection_add($, $bp, connections[i], true, true);
    }

    for (var opt in options) {
        if (Object.prototype.hasOwnProperty.call(options, opt) && opt) {
            var control = el.querySelector('input[name="' + opt + '"],select[name="' + opt + '"],textarea[name="' + opt + '"]');
            if (control) {
                var name = control.getAttribute('name');
                if (typeof(options[opt]) === 'boolean') {
                    control.checked = options[opt];
                } else {
                    control.value = (
                                        control.type == 'textarea' && name != 'css_vars' ||
                                        name         == 'word_filter'  ||
                                        name         == 'word_exclude' ?
                                        decodeURIComponent(options[opt]) : options[opt]
                                    );
                    if (opt.indexOf('_photo') > -1 && control.value) {
                        control.parentNode.querySelector('img').src = control.value;
                    }
                    if (opt == 'style_vars') {
                        rplg_sv_parse(el, control.value);
                    }
                    if (opt == 'slider_breakpoints') {
                        grw_sbs_parse(control.value);
                    }
                }
            }
        }
    }
}

function rplg_sv_parse(el, val) {
    if (val) {
        // Widgets saved before the card background checkbox hid the background by making the card color itself transparent.
        const legacy = /--rev-color:\s*transparent/;
        if (legacy.test(val)) {
            val = window.style_vars.value = val.replace(legacy, '--rev-bg: transparent');
            const rp = document.querySelector('.wp-gr');
            if (rp) {
                rp.style.removeProperty('--rev-color');
                rp.style.setProperty('--rev-bg', 'transparent');
            }
        }
        let sv = val.split(';');
        for (let i = 0; i < sv.length; i++) {
            if (sv[i]) {
                let svp = sv[i].split(':'),
                    svcs = el.querySelectorAll('input[name="' + svp[0].trim() + '"]');
               for (let j = 0; j < svcs.length; j++) {
                   let svc = svcs[j];
                    if (svc.type == 'checkbox') {
                        let off = svc.getAttribute('data-off'),
                            on = svc.getAttribute('data-on');
                        svc.checked = svp[1].trim() != off ? (svp[1].trim() == on) : false;
                    } else {
                        let da = svc.getAttribute('data-postfix');
                        svc.setAttribute('data-val', svp[1].trim());
                        svc.value = svp[1].trim().replace(da, '');

                        // if color input put color to the next text input
                        if (svc.type == 'color') {
                            svc.nextSibling.value = svp[1].trim();
                        }
                    }
                }
            }
        }
    } else {
        stylereset();
    }
}

function grw_upload_photo(el, file_frame, cb) {
    if (file_frame) {
        file_frame.open();
        return;
    }

    file_frame = wp.media.frames.file_frame = wp.media({
        title: jQuery(this).data('uploader_title'),
        button: {text: jQuery(this).data('uploader_button_text')},
        multiple: false
    });

    file_frame.on('select', function() {
        var attachment = file_frame.state().get('selection').first().toJSON();
        grw_change_photo(el, attachment.url);
        cb && cb(attachment.url);
    });
    file_frame.open();
}

function grw_change_photo(el, photo_url) {
    var place_photo_hidden = jQuery('.grw-connect-photo-hidden', el),
        place_photo_img = jQuery('.grw-connect-photo', el);

    place_photo_hidden.val(photo_url);
    place_photo_img.attr('src', photo_url);
    place_photo_img.show();
}

function grw_popup(url, width, height, cb) {
    var top = top || (screen.height/2)-(height/2),
        left = left || (screen.width/2)-(width/2),
        win = window.open(url, '', 'location=1,status=1,resizable=yes,width='+width+',height='+height+',top='+top+',left='+left);
    function check() {
        if (!win || win.closed != false) {
            cb();
        } else {
            setTimeout(check, 100);
        }
    }
    setTimeout(check, 100);
}

function grw_randstr(len) {
   var result = '',
       chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789',
       charsLen = chars.length;
   for ( var i = 0; i < len; i++ ) {
      result += chars.charAt(Math.floor(Math.random() * charsLen));
   }
   return result;
}

function grw_is_hidden(el) {
    return el.offsetParent === null;
}

function grw_capitalize(str) {
    return str.charAt(0).toUpperCase() + str.slice(1);
}

function grw_lang_options(lang) {
    return rpi.Langs.google.map(l => '<option value="' + l.v + '"' + (lang == l.v ? ' selected="selected"' : '') + '>' + l.t + '</option>').join('');
}

function grw_lang(defname, lang) {
    return '<select class="grw-connect-lang" name="lang">' +
               '<option value=""' + (lang ? '' : ' selected="selected"') + '>' + defname + '</option>' +
               grw_lang_options(lang) +
           '</select>';
}

/******************************** Breakpoints copy & paste ********************************/
function grw_sbs_init() {
    var select = document.querySelector('.grw-slider-br select'),
        range = document.querySelector('.grw-slider-br input[type="range"]'),
        span = document.querySelector('.grw-slider-br .grw-quest');

    select.addEventListener('change', grw_sbs_keyup);
    range.addEventListener('input', grw_sbs_keyup);

    span.onclick = function() {
        grw_sbs_clone(document.querySelector('.grw-slider-br:last-child'));
        grw_sbs_keyup();
    };
}

function grw_sbs_parse(sbs) {
    var brs,
        select = document.querySelector('.grw-slider-br select'),
        range = document.querySelector('.grw-slider-br input[type="range"]'),
        span = document.querySelector('.grw-slider-br input[type="range"] + span');

    if (sbs && (brs = sbs.split(',')).length) {
        var br_first = brs.shift(),
            br_vals = br_first.split(':');
        select.value = br_vals[0].replace('px', '').trim();
        range.value = parseInt(br_vals[1]);
        span.innerHTML = br_vals[1];

        while (brs.length) {
            grw_sbs_clone(document.querySelector('.grw-slider-br:last-child'), brs.shift().split(':'));
        }
    }
}

function grw_sbs_clone(br, vals) {
    let clone = br.cloneNode(true),
        prevSelect = br.querySelector('select'),
        quest = clone.querySelector('.grw-quest'),
        select = clone.querySelector('select'),
        input = clone.querySelector('input[type="text"]')
        range = clone.querySelector('input[type="range"]'),
        span = clone.querySelector('input[type="range"] + span');

    clearTimeout(window.grw_sbt);

    if (select.options.length > prevSelect.selectedIndex + 1) {
        select.selectedIndex = prevSelect.selectedIndex + 1;
    }
    if (select.selectedIndex == 0) {
        select.selectedIndex = 1;
    }

    quest.innerHTML = '-';
    br.parentNode.appendChild(clone);

    if (vals) {
        select.value = vals[0];
        if (!select.value) {
            select.value = '';
            if (!input) {
                input = document.createElement('input');
                input.type = 'text';
                select.after(input);
            }
            input.value = vals[0];
            input.addEventListener('keyup', grw_sbs_keyup);
        } else if (input) {
            input.parentNode.removeChild(input);
        }
        range.value = vals[1];
        span.innerHTML = vals[1];
    }

    select.addEventListener('change', grw_sbs_keyup);
    range.addEventListener('input', grw_sbs_keyup);

    quest.onclick = function() {
        clone.parentNode.removeChild(clone);
        grw_sbs_keyup();
    };
}

function grw_sbs_keyup() {
    clearTimeout(window.grw_sbt);
    let vals = [], brs = document.querySelectorAll('.grw-slider-br');
    for (let i = 0; i < brs.length; i++) {
        let select = brs[i].querySelector('select'),
            input = brs[i].querySelector('input[type="text"]'),
            range = brs[i].querySelector('input[type="range"]');
            //inputs = brs[i].querySelectorAll('input,select');

        if (select.value == 'off') {
            rpi.Utils.rm(input);
            continue;
        } else if ((select.value || (input && input.value)) && range.value) {
            let reslt;
            if (select.value) {
                reslt = select.value;
                rpi.Utils.rm(input);
            } else {
                reslt = input.value;
            }
            vals.push([reslt, range.value].join(':'));
        } else if (!input) {
            let custom = document.createElement('input');
            custom.type = 'text';
            custom.addEventListener('keyup', grw_sbs_keyup);
            select.after(custom);
            custom.focus();
        }
    }
    const bp = vals.join(',');
    rpi.Instances[0].setBreakpoints(bp);
    grw_sbs_set(bp);
}

function grw_sbs_set(val) {
    let input = document.querySelector('input[name="slider_breakpoints"]');
    if (input.value != val) {
        input.value = val;
        window.grw_sbt = setTimeout(grw_serialize_connections, 3000);
    }
}