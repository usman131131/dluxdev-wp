<?php

namespace WP_Rplg_Google_Reviews\Includes;

use WP_Rplg_Google_Reviews\Includes\Core\Badge_Live;
use WP_Rplg_Google_Reviews\Includes\Core\Core;

class View {

    const G_AVA_SIZE = 's120';

    public static $rendered = array();

    public function render($feed_id, $businesses, $reviews, $options, $is_admin = false) {
        ob_start();

        $max_width = $options->max_width;
        if (is_numeric($max_width)) {
            $max_width = $max_width . 'px';
        }
        $max_height = $options->max_height;
        if (is_numeric($max_height)) {
            $max_height = $max_height . 'px';
        }

        $style = '';
        if (!empty($max_width)) {
            $style .= 'width:' . $max_width . '!important;';
        }
        if (!empty($max_height)) {
            $style .= 'height:' . $max_height . '!important;overflow-y:auto!important;';
        }
        if ($options->centered) {
            $style .= 'margin:0 auto!important;';
        }
        $card = in_array($options->view_mode, array('slider', 'grid'), true) ? $this->card($options, $is_admin) : null;
        if ($card) {
            $style .= $card['style'];
        }
        if (!empty($options->style_vars) && is_string($options->style_vars)) {
            $style .= $options->style_vars;
        }
        $style .= Card_Presets::css_vars(isset($options->css_vars) ? $options->css_vars : '');

        $cls = $this->root_cls($options);
        self::$rendered[] = (string) $feed_id;

        ?><div class="wp-gr rpi<?php echo $cls; ?>"<?php if ($style) { ?> style="<?php echo esc_attr($style); ?>"<?php } ?> data-id="<?php echo esc_attr($feed_id); ?>" data-layout="<?php echo esc_attr($options->view_mode); ?>" data-exec="false" data-options='<?php echo esc_attr($this->options($options, $card)); ?>'<?php if ($this->badge_popup($reviews, $options)) { ?> data-reviews="<?php echo esc_url(admin_url('admin-ajax.php?action=grw_reviews&id=' . (int) $feed_id)); ?>"<?php } ?>><?php
            $rows = false;
            switch ($options->view_mode) {
                case 'slider':
                    $this->render_slider($businesses, $reviews, $options, $is_admin, $card);
                    $rows = true;
                    break;
                case 'grid':
                    $this->render_grid($businesses, $reviews, $options, $is_admin, $card);
                    $rows = true;
                    break;
                case 'list':
                    $this->render_list($businesses, $reviews, $options, $is_admin);
                    break;
                case 'rating':
                    $this->render_rating($businesses, $reviews, $options, $is_admin);
                    break;
                case 'badge':
                    $this->render_badge($businesses, $reviews, $options, $is_admin);
                    break;
                default:
                    $this->render_list($businesses, $reviews, $options, $is_admin);
            }

            if ($rows) {
                ?><script>(function(p){if(!p)return;let w=p.offsetWidth,m=function(a,b){return Math.min(a,b)===a};p.querySelector('.grw-row').classList.replace('grw-row-m','grw-row-'+(m(w,510)?'xs':m(w,750)?'x':m(w,1100)?'s':m(w,1450)?'m':m(w,1800)?'l':'xl'))})(document.currentScript?.closest('.wp-gr'))</script><?php
            }
        ?></div><?php
        return preg_replace('/[\n\r]|(>)\s+(<)/', '$1$2', ob_get_clean());
    }

    private function root_cls($options) {
        return $options->dark_theme ? ' wp-dark' : '';
    }

    private function options($options, $card) {
        return json_encode(
            array(
                'text_size' => $card ? '' : $options->text_size,
                'trans'     => array(
                    'read more' => __('read more', 'widget-google-reviews')
                )
            )
        );
    }

    private function render_slider($businesses, $reviews, $options, $is_admin = false, $card = null) {
        $pos = count($businesses) < 1 ? '' : (in_array($options->slider_head_pos, array('1', '3')) ? $options->slider_head_pos : '2');
        $header = function() use ($businesses, $reviews, $options, $pos) {
            $this->grw_place($businesses[0]->rating, $businesses[0], $businesses[0]->photo, $reviews, $options, true, $pos != '2');
        };
        if ($pos == '1') $header();
        ?><div class="grw-row grw-row-m" data-options='<?php
            echo esc_attr(json_encode(
                array(
                    'speed'       => $options->slider_speed ? $options->slider_speed : 3,
                    'autoplay'    => $options->slider_autoplay,
                    'mousestop'   => $options->slider_mousestop,
                    'breakpoints' => $options->slider_breakpoints
                )
            )); ?>'><?php
            if ($pos == '2') $header();
            $count = count($reviews);
            if ($count > 0) {
            ?><div class="rpi-slides-root grw-content">
                <div class="grw-content-inner"><?php
                $cls = 'rpi-slides grw-reviews';
                if (!$options->slider_hide_prevnext) {
                    $cls .= ' rpi-slides-bite';
                    $aria_label = $this->grw_aria_label($options, 'Previous');
                    ?><button class="rpi-ltgt rpi-lt grw-prev"<?php echo $aria_label; ?> tabindex="0"></button><?php
                }
                    ?><div class="<?php echo $cls; ?>" data-count="<?php echo $count; ?>" data-offset="<?php echo $count; ?>"><?php
                        foreach ($reviews as $review) {
                            $this->grw_slider_review($review, false, $options, $is_admin, $card);
                        }
                    ?></div><?php
                if (!$options->slider_hide_prevnext) {
                    $aria_label = $this->grw_aria_label($options, 'Next');
                    ?><button class="rpi-ltgt rpi-gt grw-next"<?php echo $aria_label; ?> tabindex="0"></button><?php
                }
                if (!$options->slider_hide_dots) {
                    ?><div class="rpi-dots-wrap"><div class="rpi-dots"></div></div><?php
                }
                ?></div>
            </div><?php
            }
        ?></div><?php
        if ($pos == '3') $header();
    }

    private function render_grid($businesses, $reviews, $options, $is_admin = false, $card = null) {
        $pagination = max(0, (int) $options->pagination);
        $hr = false;
        // Below only on a card preset grid: an older grid may still carry "below" from its slider days, when grids ignored it.
        $below = $card && $options->slider_head_pos == '3';
        $header = function() use ($businesses, $reviews, $options) {
            if (count($businesses) > 0) {
                $this->grw_place($businesses[0]->rating, $businesses[0], $businesses[0]->photo, $reviews, $options, true, true);
            }
        };
        if (!$below) $header();
        ?><div class="grw-row grw-row-m" data-options='<?php
            echo esc_attr(json_encode(
                array(
                    'breakpoints' => $options->slider_breakpoints
                )
            )); ?>'>
            <?php if (count($reviews) > 0) { ?>
            <div class="grw-content">
                <div class="grw-content-inner">
                    <div class="grw-reviews">
                        <?php
                        if (count($reviews) > 0) {
                            $i = 0;
                            foreach ($reviews as $review) {
                                if ($pagination > 0 && $pagination <= $i++) {
                                    $hr = true;
                                }
                                $this->grw_slider_review($review, $hr, $options, $is_admin, $card);
                            }
                        }
                        ?>
                    </div>
                </div>
                <?php if ($pagination > 0 && $hr) { ?>
                <a class="wp-google-url" href="#" onclick="return rplg_next_reviews.call(this, 'grw', <?php echo $pagination; ?>);">
                    <?php echo __('More reviews', 'widget-google-reviews'); ?>
                </a>
                <?php } ?>
            </div>
            <?php } ?>
        </div><?php
        if ($below) $header();
    }

    private function render_list($businesses, $reviews, $options, $is_admin = false) {
        ?><div class="wp-google-list"><?php
            foreach ($businesses as $business) {
                $this->grw_place(
                    $business->rating,
                    $business,
                    $business->photo,
                    $reviews,
                    $options
                );
            }
            if (!$options->hide_reviews) {
                $this->grw_place_reviews($reviews, $options, $is_admin);
            }
        ?></div><?php
    }

    private function render_rating($businesses, $reviews, $options, $is_admin = false) {
        $popup = $options->rating_popup && !$options->hide_reviews && count($reviews) > 0;
        ?><div class="wp-google-list<?php if ($popup) { echo ' grw-rating'; } ?>"><?php
            foreach ($businesses as $business) {
                $this->grw_place(
                    $business->rating,
                    $business,
                    $business->photo,
                    $reviews,
                    $options
                );
            }
            if ($popup) {
                $this->render_popup($reviews, $options, $is_admin);
            }
        ?></div><?php
    }

    // The markup mirrors rpi.Badge.Live.render() in badge.js: PHP prints the first frame, the script only rotates the second line.
    private function render_badge($businesses, $reviews, $options, $is_admin = false) {
        $biz = Badge_Live::summary($businesses);
        if (!$biz) return;

        $live = Badge_Live::build($reviews);
        $pos = $this->badge_pos($options);
        $bar = !empty($options->badge_bar);
        $edge = (!$bar || $pos === 'embed') ? $pos : (strpos($pos, 'top') === 0 ? 'top' : 'bottom');
        $compact = $options->badge_compact || $bar;
        $tone = in_array($options->badge_tone, array('light', 'dark', 'glass', 'glass-dark'), true) ? $options->badge_tone : 'light';

        $cls = array('rpi-badge', 'rpi-badge-' . $tone);
        if ($compact)                          $cls[] = 'rpi-badge-compact';
        if ($options->badge_onestar)           $cls[] = 'rpi-badge-onestar';
        if ($options->badge_order === 'stars') $cls[] = 'rpi-badge-starsfirst';
        if ($options->badge_width === 'auto')  $cls[] = 'rpi-badge-fit';
        if ($bar)                              $cls[] = 'rpi-badge-bar';

        $style = array();
        if ($options->badge_size) $style[] = '--badge-fs:' . (float) $options->badge_size . 'px';
        if ($options->badge_width && $options->badge_width !== 'auto') $style[] = '--badge-width:' . (int) $options->badge_width . 'px';
        $style[] = 'display:none';

        $count = number_format_i18n((int) $biz->review_count);
        $stars = '<span class="rpi-stars" style="--rating:' . esc_attr($biz->rating) . '">' . esc_html($biz->rating) . '</span>';
        $provider = $biz->provider === 'summary' ? 'google' : $biz->provider;
        $top_rated = $options->badge_toprated
            && (float) $biz->rating >= Badge_Live::TOP_RATED_MIN_RATING
            && (int) $biz->review_count >= Badge_Live::TOP_RATED_MIN_TOTAL;
        $popup = $this->badge_popup($reviews, $options);

        ?><div class="rpi-badge-cnt rpi-badge-live rpi-badge-<?php echo esc_attr($edge); if ($bar) echo ' rpi-badge-cnt-bar'; if ($options->hide_float_badge) echo ' rpi-badge-hide'; ?>" data-opts='<?php echo esc_attr($this->badge_options($options, $live, $biz, $businesses)); ?>'>
            <div class="<?php echo implode(' ', $cls); ?>" style="<?php echo esc_attr(implode(';', $style)); ?>"<?php if ($biz->provider !== 'summary') { ?> data-id="<?php echo esc_attr($biz->id); ?>" data-provider="<?php echo esc_attr($biz->provider); ?>"<?php } ?>>
                <?php if ($options->badge_close) { ?><div class="rpi-x"></div><?php } ?>
                <div class="rpi-badge-body<?php if ($popup) echo ' rpi-badge-clickable'; ?>">
                    <?php if (!$compact && !$options->badge_logo_hide) echo $this->logo($provider); ?>
                    <div class="rpi-badge-main">
                        <div class="rpi-badge-head">
                            <?php echo ($compact && !$options->badge_logo_hide) ? $this->logo($provider, $stars) : $stars; ?>
                            <?php if (!$options->badge_based_hide) { ?><span class="rpi-badge-count"><?php echo $options->badge_dot ? '· ' . $count : '(' . $count . ')'; ?></span><?php } ?>
                            <?php if ($top_rated) { ?><span class="rpi-badge-label<?php if ($options->badge_icon) echo ' rpi-badge-ico-' . esc_attr($options->badge_icon); ?>">· <i><?php echo esc_html__('Top rated', 'widget-google-reviews'); ?></i></span><?php } ?>
                        </div>
                        <div class="rpi-badge-feed"><span><?php echo $this->badge_first($biz, $live, $options); ?></span></div>
                    </div>
                </div>
            </div>
        </div><?php
    }

    public function badge_popup($reviews, $options) {
        return $options->view_mode === 'badge' && $options->badge_click !== 'disable' && !$options->hide_reviews && count($reviews) > 0;
    }

    public function render_popup($reviews, $options, $is_admin = false) {
        // The popup scrolls, so paging inside it would only hide reviews behind a click.
        $popup_options = clone $options;
        $popup_options->pagination = 0;
        ?><div class="wp-gr rpi<?php echo $this->root_cls($options); ?> grw-popup"><?php
            $this->grw_place_reviews($reviews, $popup_options, $is_admin);
        ?></div><?php
    }

    private function badge_pos($options) {
        $valid = array('left', 'right', 'top-left', 'top-right', 'top', 'bottom', 'embed');
        return in_array($options->badge_pos, $valid, true) ? $options->badge_pos : 'right';
    }

    private function badge_options($options, $live, $biz, $businesses) {
        $opts = array();
        foreach (Core::badge_option_keys() as $key) {
            if (isset($options->$key) && $options->$key !== '' && $options->$key !== false) {
                $opts[$key] = $options->$key;
            }
        }
        $opts['badge_style'] = 'live';
        $opts['badge_pos'] = $this->badge_pos($options);
        $opts['live'] = $live;
        // badge.js reads the business behind the facts from data.bizs, the same shape the preview sends.
        $bizs = array();
        foreach ($businesses as $b) {
            $bizs[] = array('id' => $b->id, 'provider' => $b->provider, 'rating' => $b->rating, 'total' => (int) $b->review_count);
        }
        if ($biz->provider === 'summary') {
            array_unshift($bizs, array('id' => 'summary', 'provider' => 'summary', 'rating' => $biz->rating, 'total' => (int) $biz->review_count));
        }
        $opts['data'] = array('bizs' => $bizs);
        $opts['lang'] = substr(get_locale(), 0, 2);
        $opts['trans'] = array(
            'Top rated'                            => __('Top rated', 'widget-google-reviews'),
            'Latest review %s'                     => __('Latest review %s', 'widget-google-reviews'),
            '%1 of the last %2 reviews are 5 stars' => __('%1 of the last %2 reviews are 5 stars', 'widget-google-reviews'),
            '%1 reviews on %2'                     => __('%1 reviews on %2', 'widget-google-reviews'),
            '%1 reviews'                           => __('%1 reviews', 'widget-google-reviews'),
            '%1 left %2 review'                    => __('%1 left %2 review', 'widget-google-reviews'),
        );
        return json_encode($opts, JSON_UNESCAPED_UNICODE);
    }

    private function badge_first($biz, $live, $options) {
        if (!empty($live['phrases'])) {
            $p = $live['phrases'][0];
            $author = $options->badge_author_hide ? '' : Badge_Live::short_name($p['a']);
            return '<q>' . esc_html($p['t']) . '…</q>' . ($author ? ' <b>' . esc_html($author) . '</b>' : '');
        }
        $provider = $biz->provider === 'summary' ? '' : ucfirst($biz->provider);
        $count = '<b>' . number_format_i18n((int) $biz->review_count) . '</b>';
        return $provider
            ? str_replace(array('%1', '%2'), array($count, esc_html($provider)), esc_html__('%1 reviews on %2', 'widget-google-reviews'))
            : str_replace('%1', $count, esc_html__('%1 reviews', 'widget-google-reviews'));
    }

    function grw_place($rating, $place, $place_img, $reviews, $options, $show_powered = true, $wide = false) {
        $style = $options->header_center ? 'style="--dir:column;--align:center;--star-align-self:center"' : '';
        $wr = function() use ($place, $options) {
            if ($options->hide_writereview) return;
            ?><div class="wp-google-wr"><?php
                echo $this->grw_anchor(
                    'https://search.google.com/local/writereview?placeid=' . $place->id,
                    '',
                    __('review us on', 'widget-google-reviews'),
                    $options,
                    __('review us on Google', 'widget-google-reviews'),
                    'return rplg_leave_review_window.call(this)',
                    $this->logo('google')
                );
            ?></div><?php
        };
        ?><div class="grw-header<?php if ($wide) echo ' rpi-flx rpi-row12-center'; ?>"<?php if ($wide && $options->slider_hide_prevnext) echo ' style="--slider-offset:0px"'; ?>>
            <div class="grw-header-inner rpi-flx rpi-row12<?php if ($wide && $options->header_center) echo ' grw-center'; ?>"<?php echo $style; ?>><?php
                if (!$options->header_hide_photo) {
                    $alt_val = sprintf(__('%s place picture', 'widget-google-reviews'), $place->name);
                    $alt = empty($options->aria_label) ? $alt_val : ($options->header_hide_name ? $alt_val : '');
                    $this->grw_image($place_img, $alt, $options->lazy_load_img);
                }
                ?><div class="rpi-flx rpi-col8"<?php echo $style; ?>><?php
                    $item = function($key) use ($rating, $place, $options, $show_powered) {
                        switch ($key) {
                            case 'platform':
                                if ($show_powered && $options->header_platform) $this->grw_powered(true);
                                break;
                            case 'scale':
                                if ($options->header_show_scale) {
                                    ?><div class="wp-google-scale"><?php echo esc_html($this->scale($rating, $place->review_count)); ?></div><?php
                                }
                                break;
                            case 'name':
                                if ($options->header_hide_name) break;
                                ?><div class="wp-google-name"><?php
                                if ($options->disable_biz_link || empty($place->url)) {
                                    ?><span><?php echo esc_html($place->name); ?></span><?php
                                } else {
                                    echo $this->grw_anchor($place->url, '', $place->name, $options, sprintf(__('%s place profile', 'widget-google-reviews'), $place->name));
                                }
                                ?></div><?php
                                break;
                            case 'stars':
                                ?><span class="rpi-stars"<?php echo $this->grw_rating_label($options, $rating); ?> style="--rating:<?php echo $rating; ?>"><?php if (!$options->header_hide_grade) echo $rating; ?></span><?php
                                break;
                            case 'based':
                                if (!$options->hide_based_on && isset($place->review_count)) {
                                    // The badge's '%1 reviews' is already translated into every plugin language.
                                    $text = $options->header_short_count ? str_replace('%1', '%s', __('%1 reviews', 'widget-google-reviews')) : __('Based on %s reviews', 'widget-google-reviews');
                                    ?><div class="wp-google-based"><?php echo $this->based($text, number_format_i18n((int) $place->review_count), $options->header_bold_count); ?></div><?php
                                }
                        }
                    };
                    foreach ($this->header_order($options->header_order) as $group) {
                        $parts = $this->header_group($group, $item);
                        echo count($parts) > 1
                            ? '<div class="rpi-flx rpi-row12-center" style="--wrap:wrap;--star-align-self:center' . ($options->header_center ? ';justify-content:center' : '') . '">' . implode('', $parts) . '</div>'
                            : implode('', $parts);
                    }
                    if ($show_powered && !$options->header_platform) $this->grw_powered();
                    if (!$wide) $wr();
                ?></div><?php
                if ($wide) $wr();
            ?></div>
        </div><?php
    }

    private function header_order($order) {
        $def = array('scale', 'name', 'stars', 'based', 'platform');
        $seen = array();
        $groups = array();
        foreach (array_merge(explode(',', (string) $order), $def) as $group) {
            $keys = array_values(array_unique(array_diff(array_intersect(array_map('trim', explode('+', $group)), $def), $seen)));
            if (!$keys) continue;
            $groups[] = $keys;
            $seen = array_merge($seen, $keys);
        }
        return $groups;
    }

    private function header_group($group, $item) {
        $parts = array();
        foreach ($group as $key) {
            ob_start();
            $item($key);
            $html = ob_get_clean();
            if ($html !== '') $parts[] = $html;
        }
        return $parts;
    }

    private function based($text, $count, $bold) {
        $text = esc_html($text);
        if ($bold) {
            // Translations put the count either before its noun ("%s reviews") or after a colon ("Отзывов: %s").
            $text = preg_replace('/%s\s+[^\s%]+|[^\s:]+:\s*%s|%s/u', '<b>$0</b>', $text, 1);
        }
        return str_replace('%s', esc_html($count), $text);
    }

    private function scale($rating, $review_count) {
        if ($rating >= Badge_Live::TOP_RATED_MIN_RATING && $review_count >= Badge_Live::TOP_RATED_MIN_TOTAL) {
            return __('Top rated', 'widget-google-reviews');
        } elseif ($rating > 4.2) {
            return __('Excellent', 'widget-google-reviews');
        } elseif ($rating > 3.7) {
            return __('Great', 'widget-google-reviews');
        } elseif ($rating > 2.7) {
            return __('Good', 'widget-google-reviews');
        } elseif ($rating > 1.7) {
            return __('Fair', 'widget-google-reviews');
        }
        return __('Poor', 'widget-google-reviews');
    }

    function grw_powered($logo_only = false) {
        $label = $logo_only ? 'Google' : __('powered by Google', 'widget-google-reviews');
        ?><div class="wp-google-powered" role="img" aria-label="<?php echo esc_attr($label); ?>"><?php if (!$logo_only) echo 'powered by '; ?><span class="rpi-goog" aria-hidden="true"></span></div><?php
    }

    function grw_place_reviews($reviews, $options, $is_admin = false) {
        $pagination = max(0, (int) $options->pagination);
        ?><div class="rpi-flx rpi-col16"><?php
        $place_id = null;
        $place_url = null;

        $hr = false;
        if (count($reviews) > 0) {
            $i = 0;
            foreach ($reviews as $review) {
                if (!$place_id) {
                    $place_id = $review->biz_id;
                    $place_url = $review->biz_url;
                }
                if ($pagination > 0 && $pagination <= $i++) {
                    $hr = true;
                }
                $this->grw_place_review($review, $hr, $options, $is_admin);
            }
        }
        ?></div><?php
        if ($pagination > 0 && $hr) {
        ?><a class="wp-google-url" href="#" onclick="return rplg_next_reviews.call(this, 'wp-google', <?php echo $pagination; ?>);"><?php
            echo __('More reviews', 'widget-google-reviews');
        ?></a><?php
        } else {
            $reviews_link = $options->google_def_rev_link ? $place_url : 'https://search.google.com/local/reviews?placeid=' . $place_id;
            $this->grw_anchor($reviews_link, 'wp-google-url', __('See All Reviews', 'widget-google-reviews'), $options, __('All reviews', 'widget-google-reviews'));
        }
    }

    function grw_place_review($review, $hr, $options, $is_admin = false) {
        ?><div class="wp-google-review<?php if ($hr) { echo ' wp-google-hide'; } if ($is_admin && $review->hide != '') { echo ' wp-review-hidden'; } ?>"><?php
            ?><div class="rpi-flx rpi-row12"><?php
                $author_name = empty($review->author_name) ? __('Google User', 'widget-google-reviews') : $review->author_name;
                if (!$options->hide_avatar) {
                    $this->review_photo($review, $options);
                }
                ?><div class="rpi-flx rpi-col4"><?php
                    if (!empty($review->author_url)) {
                        $aria_label = sprintf(__('%s user profile', 'widget-google-reviews'), $author_name);
                        $this->grw_anchor($review->author_url, 'wp-google-name', $author_name, $options, $aria_label);
                    } else {
                        ?><div class="wp-google-name"><?php echo esc_html($author_name); ?></div><?php
                    }
                    $this->review_time($review, $options);
                    ?><div class="wp-google-feedback">
                        <span class="rpi-stars"<?php echo $this->grw_rating_label($options, $review->rating); ?> style="--rating:<?php echo $review->rating; ?>"></span><?php
                        if (!empty($review->text)) { ?><span class="wp-google-text"><?php echo $this->text($review->text); ?></span><?php }
                    ?></div><?php
                    if ($is_admin) {
                        echo '<a href="#" class="wp-review-hide" data-id=' . $review->id . '>' . ($review->hide == '' ? 'Hide' : 'Show') . ' review</a>';
                    }
                ?></div>
            </div><?php
        ?></div><?php
    }

    function grw_slider_review($review, $hr, $options, $is_admin = false, $card = null) {
        $cls = $hr ? ' grw-hide' : '';
        $cls .= $is_admin && ($review->hide != '' || (isset($options->hidden) && $this->is_hidden($review->id, $options->hidden))) ? ' wp-review-hidden' : '';
        $inr_cls = $options->hide_backgnd ? "" : " grw-backgnd";
        $inr_cls .= $options->show_round ? " grw-round" : "";
        $inr_cls .= $options->show_shadow ? " grw-shadow" : "";
        if ($card) {
            $card['product']['slot'] = 'rpi-slide grw-review' . $cls;
            $card['product']['frame'] = 'grw-review-inner' . $inr_cls;
            echo Card_Engine::render($review, $card['model'], $card['product']);
            return;
        }
        ?><div class="rpi-slide grw-review<?php echo $cls; ?>">
            <div class="rpi-flx rpi-col12 grw-review-inner<?php echo $inr_cls; ?>">
                <div class="rpi-flx rpi-row12-center"><?php
                    $this->review_photo($review, $options);
                    ?><div class="rpi-flx rpi-col6"><?php
                        $this->review_name($review, $options);
                        $this->review_time($review, $options);
                    ?></div>
                </div>
                <span class="rpi-stars"<?php echo $this->grw_rating_label($options, $review->rating); ?> style="--rating:<?php echo $review->rating; ?>"></span>
                <div class="rpi-flx rpi-col4"><?php
                    $this->review_body($review, $options);
                ?></div><?php
                $this->review_reply($review, $options);
                if ($is_admin) {
                    echo $this->hide_link($review);
                }
                echo $this->logo($review->provider);
            ?></div>
        </div><?php
    }

    // A widget with a card style gets the engine's card; its elements are the ones grw_slider_review() prints.
    private function card($options, $is_admin) {
        $preset = empty($options->card_style) ? null : Card_Presets::get($options->card_style);
        if (!$preset) return null;
        try {
            $model = Card_Engine::parse(empty($options->card_layout) ? $preset['layout'] : $options->card_layout);
        } catch (\InvalidArgumentException $e) {
            $model = Card_Engine::parse($preset['layout']);
        }
        $logo = function($review, $o) {
            return $o['logo'] ? ' rpi-logo_after rpi-logo-' . esc_attr($review->provider) : '';
        };
        return array('model' => $model, 'style' => Card_Presets::style($preset), 'product' => array(
            'photo' => function($review, $o) use ($options, $logo) {
                return $this->capture('review_photo', $review, $options, trim($logo($review, $o) . ' ' . $o['cls']));
            },
            'name'  => function($review, $o) use ($options, $logo) {
                return $this->capture('review_name', $review, $options, trim('wp-google-name' . $logo($review, $o) . ' ' . $o['cls']));
            },
            'time'  => function($review, $o) use ($options) {
                return $this->capture('review_time', $review, $options, false, $o['cls']);
            },
            // A logo drawn by ::before would clash with the stars' own pseudo-elements, so the stars get a wrapper that carries it.
            'stars' => function($review, $o) use ($options) {
                $stars = '<span class="' . trim('rpi-stars ' . ($o['logo'] ? '' : $o['cls'])) . '"' . $this->grw_rating_label($options, $review->rating) . ' style="--rating:' . $review->rating . '"></span>';
                return $o['logo'] ? '<span class="' . trim('rpi-logo rpi-logo-' . esc_attr($review->provider) . ' ' . $o['cls']) . '">' . $stars . '</span>' : $stars;
            },
            // The admin's hide link sits in the text block: as the frame's last child it would break the band's corners.
            'text'  => function($review, $o) use ($options, $is_admin) {
                return '<div class="' . trim('rpi-flx rpi-col4 ' . $o['cls']) . '">' . $this->capture('review_body', $review, $options, true) .
                    $this->capture('review_reply', $review, $options) . ($is_admin ? $this->hide_link($review) : '') . '</div>';
            },
            'logo'  => function($review, $o) {
                return $this->logo($review->provider, '', $o['cls']);
            },
            'platform' => function($review, $o) {
                return '<span class="' . trim('grw-platform ' . $o['cls']) . '">' . esc_html(ucfirst(empty($review->provider) ? 'google' : $review->provider)) . '</span>';
            },
        ));
    }

    private function capture($method, ...$args) {
        ob_start();
        $this->$method(...$args);
        return ob_get_clean();
    }

    private function author_name($review) {
        return empty($review->author_name) ? __('Google User', 'widget-google-reviews') : $review->author_name;
    }

    private function review_photo($review, $options, $cls = '') {
        if (!empty($options->anonym_avatar)) {
            $this->char_avatar($this->author_name($review), $cls);
            return;
        }
        $default_avatar = GRW_ASSETS_URL . 'img/guest.png';
        $author_avatar = empty($review->author_avatar) ? $default_avatar : $review->author_avatar;
        if (isset($options->reviewer_avatar_size)) {
            $author_avatar = str_replace(self::G_AVA_SIZE, 's' . $options->reviewer_avatar_size, $author_avatar);
            $default_avatar = str_replace(self::G_AVA_SIZE, 's' . $options->reviewer_avatar_size, $default_avatar);
        }
        $alt = empty($options->aria_label) ? sprintf(__('%s profile picture', 'widget-google-reviews'), $this->author_name($review)) : '';
        $this->grw_image($author_avatar, $alt, $options->lazy_load_img, $default_avatar, '', $cls);
    }

    // The palette and the rule are rpi.View.charAvatar's in the CDN view.js and brb's char_avatar(): one reviewer, one avatar in every product.
    private function char_avatar($name, $cls) {
        $colors = array(array('#d93025', '#f28b82'), array('#e8710a', '#fcc934'), array('#f09300', '#fdd663'), array('#0b8043', '#57bb8a'), array('#00897b', '#4db6ac'),
                        array('#1a73e8', '#7baaf7'), array('#3949ab', '#7986cb'), array('#7b1fa2', '#ba68c8'), array('#c2185b', '#f06292'), array('#455a64', '#90a4ae'));
        $name = trim($name);
        $char = $name === '' ? '?' : mb_substr($name, 0, 1);
        $char = function_exists('mb_strtoupper') ? mb_strtoupper($char) : strtoupper($char);
        $color = $colors[(function_exists('mb_ord') ? mb_ord($char, 'UTF-8') : ord($char)) % count($colors)];
        ?><div class="grw-img-wrap<?php if ($cls) echo ' ' . $cls; ?>"><span class="grw-img grw-img-char" style="background:linear-gradient(135deg, <?php echo $color[0] . ', ' . $color[1]; ?>)" aria-hidden="true"><?php echo esc_html($char); ?></span></div><?php
    }

    private function review_name($review, $options, $cls = 'wp-google-name') {
        $author_name = $this->author_name($review);
        if (!empty($review->author_url) || !empty($review->url)) {
            $url = empty($review->author_url) ? $review->url : $review->author_url;
            $aria_label = sprintf(__('%s user profile', 'widget-google-reviews'), $author_name);
            $this->grw_anchor($url, $cls, $author_name, $options, $aria_label);
        } else {
            ?><div class="<?php echo $cls; ?>"><?php echo esc_html($author_name); ?></div><?php
        }
    }

    private function review_body($review, $options, $card = false) {
        ?><div class="wp-google-feedback grw-scroll" <?php if (!$card && !empty($options->slider_text_height)) {?> style="height:<?php echo esc_attr($options->slider_text_height); ?>!important"<?php } ?>>
            <?php if (!empty($review->text)) { ?>
            <span class="wp-google-text"><?php echo $this->text($review->text); ?></span>
            <?php } ?>
        </div><?php
        if (isset($options->media) && $options->media && !empty($review->images)) {
        ?><div class="wp-google-img"><?php
            $images = explode(';', $review->images);
            foreach ($images as $img) {
                $thumb_alt = __('Photo from customer review', 'widget-google-reviews');
                ?><img class="rpi-thumb" src="<?php echo preg_replace('/(=.*)s\d{2,3}/', '$1s50', esc_url($img)); ?>" alt="<?php echo esc_attr($thumb_alt); ?>" loading="lazy"><?php
            }
        ?></div><?php
        }
    }

    private function review_reply($review, $options) {
        if (isset($options->reply) && $options->reply && !empty($review->reply)) {
        ?><div class="wp-google-reply grw-scroll">
            <div>
                <span class="grw-b"><?php echo __('Response from the owner', 'widget-google-reviews'); ?></span><?php
                $this->review_time($review, $options, true);
            ?></div><?php
            echo $this->text($review->reply);
        ?></div><?php
        }
    }

    private function hide_link($review) {
        return '<a href="#" class="wp-review-hide" data-id=' . $review->id . '>' . ($review->hide == '' ? 'hide' : 'show') . ' review</a>';
    }

    // render() strips raw newlines from the whole output, so the text carries them as entities.
    private function text($text) {
        return str_replace(array("\r\n", "\n"), '&#10;', esc_html($text));
    }

    private function review_time($review, $opts, $reply = false, $cls = '') {
        $rt = $reply ? (empty($review->reply_time) ? null : $review->reply_time) : (empty($review->time) ? null : $review->time);
        if (!empty($rt)) {
            $lang = empty($review->lang) ? '' : str_replace('_', '-', $review->lang);
            ?><div class="wp-google-time<?php if ($cls) echo ' ' . $cls; ?>" data-time="<?php echo esc_attr($rt); ?>"<?php if ($lang) { ?> data-lang="<?php echo esc_attr($lang); ?>"<?php } ?>><?php
                echo esc_html($this->get_time($rt, $opts));
            ?></div><?php
        }
    }

    private function get_time($rt, $opts) {
        $time_format = empty($opts->time_format) ? null : $opts->time_format;
        $time_int = (int)$rt;
        $time = $time_int > 9999999999 ? (int)($time_int / 1000) : $time_int;

        if (empty($time_format)) {
            return sprintf(__('%s ago', 'default'), human_time_diff($time));
        }
        return function_exists('wp_date') ? wp_date($time_format, $time) : date_i18n($time_format, $time);
    }

    function is_hidden($id, $hidden) {
        $ids = array_map('intval', explode(',', $hidden));
        return in_array((int)$id, $ids, true);
    }

    private function logo($provider, $inner = '', $cls = '') {
        return '<span class="rpi-logo rpi-logo-' . esc_attr($provider) . ($cls ? ' ' . $cls : '') . '" aria-hidden="true">' . $inner . '</span>';
    }

    function grw_anchor($url, $class, $text, $options, $aria_label = '', $onclick = '', $after_raw = '') {
        $al = $this->grw_aria_label($options, esc_attr($aria_label) . ' - ' . __('opens in a new window', 'widget-google-reviews'));
        echo '<a href="' . esc_url($url) . '"' . ($class ? ' class="' . $class . '"' : '') . ($options->open_link ? ' target="_blank"' : '') . ' rel="' . ($options->nofollow_link ? 'nofollow ' : '') . 'noopener"' . $al . (empty($onclick) ? '' : ' onclick="' . $onclick . '"') . '>' . esc_html($text) . $after_raw . '</a>';
    }

    function grw_rating_label($options, $rating) {
        return $this->grw_aria_label($options, sprintf(__('Rating: %s out of 5', 'widget-google-reviews'), $rating), 'img');
    }

    function grw_aria_label($options, $aria_label, $role = '') {
        return empty($options->aria_label) || empty($aria_label) ? '' : (empty($role) ? '' : ' role="' . $role . '"') . ' aria-label="' . $aria_label . '"';
    }

    function grw_image($src, $alt, $lazy, $def_ava = 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7', $atts = '', $cls = '') {
        ?><div class="grw-img-wrap<?php if ($cls) echo ' ' . $cls; ?>"><img class="grw-img" src="<?php echo esc_url($src); ?>"<?php if ($lazy) { ?> loading="lazy"<?php } ?> alt="<?php echo esc_attr($alt); ?>" width="50" height="50" onerror="if(this.src!='<?php echo $def_ava; ?>')this.src='<?php echo $def_ava; ?>';" <?php echo $atts; ?>></div><?php
    }
}
