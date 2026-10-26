<?php

namespace WP_Rplg_Google_Reviews\Includes\Core;

class Core {

    public static function get_default_options() {
        return array(
            'view_mode'                 => 'list',
            'rating_popup'              => null,
            'pagination'                => '10',
            'text_size'                 => '',
            'min_letter'                => '',
            'hide_based_on'             => false,
            'hide_writereview'          => false,
            'hide_reviews'              => false,
            'hide_avatar'               => false,
            'hide_backgnd'              => false,
            'show_round'                => false,
            'show_shadow'               => false,
            'short_last_name'           => false,
            'anonym_avatar'             => false,
            'media'                     => true,
            'reply'                     => true,

            'slider_autoplay'           => true,
            'slider_hide_prevnext'      => false,
            'slider_hide_dots'          => false,
            'slider_text_height'        => '',
            'slider_speed'              => 3,
            'slider_mousestop'          => true,
            'slider_breakpoints'        => '',
            'slider_head_pos'           => '2',

            'header_hide_social'        => false,
            'header_center'             => false,
            'header_hide_photo'         => false,
            'header_hide_name'          => false,
            'header_show_scale'         => false,
            'header_platform'           => false,
            'header_order'              => '',
            'header_bold_count'         => false,
            'header_short_count'        => false,
            'header_hide_grade'         => false,

            'badge_style'               => 'live',
            'badge_pos'                 => 'right',
            'badge_bar'                 => false,
            'badge_click'               => 'sidebar',
            'badge_close'               => false,
            'hide_float_badge'          => false,
            'badge_logo_hide'           => false,
            'badge_based_hide'          => false,
            'badge_tone'                => 'light',
            'badge_size'                => '',
            'badge_width'               => '',
            'badge_onestar'             => false,
            'badge_compact'             => false,
            'badge_order'               => '',
            'badge_dot'                 => false,
            'badge_author_hide'         => false,
            'badge_toprated'            => false,
            'badge_icon'                => 'medal',
            'badge_interval'            => '',

            'dark_theme'                => false,
            'centered'                  => false,
            'max_width'                 => '',
            'max_height'                => '',
            'style_vars'                => '',
            'css_vars'                  => '',
            'card_style'                => '',
            'card_layout'               => '',

            'open_link'                 => true,
            'nofollow_link'             => true,
            'lazy_load_img'             => true,
            'aria_label'                => false,
            'google_def_rev_link'       => false,
            'disable_biz_link'          => false,
            'reviewer_avatar_size'      => 56,
            'reviews_limit'             => '',
            'hidden'                    => '',
            'cache'                     => 12,
        );
    }

    public function get_reviews($feed, $is_admin = false) {
        $connection = json_decode($feed->post_content);

        if ($is_admin && is_admin()) {
            return $this->get_data($connection, $is_admin);
        }

        $cache_time            = isset($connection->options) && isset($connection->options->cache) ? $connection->options->cache : ($feed instanceof \WP_Post ? self::get_default_options()['cache'] : null);
        $data_cache_key        = 'grw_feed_' . GRW_VERSION . '_' . $feed->ID . '_reviews';
        $connection_cache_key  = 'grw_feed_' . GRW_VERSION . '_' . $feed->ID . '_options';

        $data                  = get_transient($data_cache_key);
        $cached_connection     = get_transient($connection_cache_key);
        $serialized_connection = serialize($connection);

        if ($data === false || $serialized_connection !== $cached_connection || !$cache_time) {
            $expiration = $cache_time;
            switch ($expiration) {
                case '1':
                    $expiration = 3600;
                    break;
                case '3':
                    $expiration = 3600 * 3;
                    break;
                case '6':
                    $expiration = 3600 * 6;
                    break;
                case '12':
                    $expiration = 3600 * 12;
                    break;
                case '24':
                    $expiration = 3600 * 24;
                    break;
                case '48':
                    $expiration = 3600 * 48;
                    break;
                case '168':
                    $expiration = 3600 * 168;
                    break;
                default:
                    $expiration = 3600 * 24;
            }
            $data = $this->get_data($connection);
            if ($cache_time) {
                set_transient($data_cache_key, $data, $expiration);
                set_transient($connection_cache_key, $serialized_connection, $expiration);
            }
        }
        return $data;
    }

    // hide_float_badge is a badge key too, the one without the prefix.
    public static function badge_option_keys() {
        return array_values(array_filter(array_keys(self::get_default_options()), function($key) {
            return strpos($key, 'badge_') === 0 || $key === 'hide_float_badge';
        }));
    }

    private function get_ops($connection) {
        if (!isset($connection->options) || !is_object($connection->options)) {
            $connection->options = (object)[];
        }
        foreach ($this->get_default_options() as $field => $value) {
            $connection->options->{$field} = isset($connection->options->{$field}) ? $connection->options->{$field} : $value;
        }
        return $connection->options;
    }

    public function get_data($connection, $is_admin = false) {

        // A widget whose post_content is empty or not valid JSON decodes to null.
        // Every caller indexes the result straight away, so hand back an empty feed.
        if (!is_object($connection)) {
            $connection = new \stdClass();
        }

        $options = $this->get_ops($connection);

        $biz = array();
        $reviews = array();

        if (isset($connection->connections) && is_array($connection->connections)) {
            foreach ($connection->connections as $conn) {
                switch ($conn->platform) {
                    case 'facebook':
                        // TODO
                        // break;
                    default:
                        $result = $this->get_db_reviews($conn, $options, $is_admin);
                        // Null when the connected place is not in the database yet.
                        if (!$options->header_hide_social && $result['business']) {
                            array_push($biz, $result['business']);
                        }
                        if (!$options->hide_reviews) {
                            $reviews = array_merge($reviews, $result['reviews']);
                        }
                }
            }
        }

        usort($reviews, array($this, 'sort_recent'));

        // Trim reviews limit
        if ($options->reviews_limit > 0) {
            $reviews = array_slice($reviews, 0, $options->reviews_limit);
        }

        return array('businesses' => $biz, 'reviews' => $reviews, 'options' => $options);
    }

    public function get_db_reviews($biz, $options, $is_admin = false) {
        global $wpdb;

        $rating = 0;
        $review_count = 0;
        $reviews = array();

        $business = null;
        $google_reviews = array();

        // Get place
        $place = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM " . $wpdb->prefix . Database::BUSINESS_TABLE .
                " WHERE place_id = %s", $biz->id
            )
        );

        if ($place) {

            $lang = empty($biz->lang) ? substr(get_locale(), 0, 2) : $biz->lang;
            $all_langs = isset($options->all_langs) ? (int)(bool)$options->all_langs : (empty($biz->lang) ? 1 : 0);
            $reviews = $this->fetch_reviews([$place->id], $options, $lang, $all_langs, 0, $is_admin);

            // Setup photo
            $place_photo = empty($biz->photo) ? (empty($place->photo) ? GRW_GOOGLE_BIZ : $place->photo) : $biz->photo;
            // Photo URLs stored by 7.1 and older may carry the Google API key
            if (strpos($place_photo, 'key=') !== false) {
                $place_photo = GRW_GOOGLE_BIZ;
            }

            // Calculate reviews count
            if (isset($place->review_count) && $place->review_count > 0) {
                $review_count = $place->review_count;
            } else {
                $review_count = $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT count(*) FROM " . $wpdb->prefix . Database::REVIEW_TABLE .
                        " WHERE google_place_id = %d", $place->id
                    )
                );
            }

            // Calculate rating
            $rating = 0;
            if ($place->rating > 0) {
                $rating = $place->rating;
            } else if (count($reviews) > 0) {
                foreach ($reviews as $review) {
                    $rating = $rating + $review->rating;
                }
                $rating = round($rating / count($reviews), 1);
            }
            $rating = number_format((float)$rating, 1, '.', '');

            $business = json_decode(json_encode(
                array(
                    'id'                  => $biz->id,
                    'name'                => $biz->name ? $biz->name : $place->name,
                    'url'                 => empty($place->url) ? null : $place->url,
                    'photo'               => $place_photo,
                    'address'             => empty($place->address) ? null : $place->address,
                    'rating'              => $rating,
                    'review_count'        => $review_count,
                    'provider'            => 'google'
                )
            ));

            foreach ($reviews as $rev) {
                $text = empty($rev->lang_text) ? (empty($rev->text) ? null : $rev->text) : $rev->lang_text;
                if (!empty($options->min_letter) && ($text === null || strlen($text) < $options->min_letter)) continue;

                $review = json_decode(json_encode(
                    array(
                        'id'            => $rev->id,
                        'hide'          => $rev->hide,
                        'biz_id'        => $biz->id,
                        'biz_url'       => empty($place->url) ? null : $place->url,
                        'rating'        => $rev->rating,
                        'text'          => empty($text) ? null : wp_encode_emoji($text),
                        'lang'          => isset($biz->lang) ? $biz->lang : null,
                        'author_avatar' => $rev->profile_photo_url,
                        'author_url'    => $rev->author_url,
                        'author_name'   => isset($options->short_last_name) && $options->short_last_name ?
                                           $this->get_short_name($rev->author_name) : $rev->author_name,
                        'time'          => $rev->time,
                        'images'        => isset($rev->images) ? $rev->images : null,
                        'reply'         => isset($rev->reply) ? $rev->reply : null,
                        'reply_time'    => isset($rev->reply_time) ? $rev->reply_time : null,
                        'url'           => isset($rev->url) ? $rev->url : null,
                        'provider'      => isset($rev->provider) ? $rev->provider : 'google'
                    )
                ));
                array_push($google_reviews, $review);
            }
        }
        return array('business' => $business, 'reviews' => $google_reviews);
    }

    private function fetch_reviews($place_ids, $options, $lang, $all_langs, $limit, $is_admin) {
        global $wpdb;

        $where_r = $is_admin ? '' : " AND r.hide = ''";

        $hidden_ids = array();
        if (isset($options->hidden) && !$is_admin) {
            $hidden_ids = $this->parse_hidden_ids($options->hidden);
            if (!empty($hidden_ids)) {
                $hidden_phs = implode(',', array_fill(0, count($hidden_ids), '%d'));
                $where_r   .= ' AND r.id NOT IN (' . $hidden_phs . ')';
            }
        }

        if (!is_array($place_ids)) {
            $place_ids = [$place_ids];
        }
        $place_ids_phs = implode(',', array_fill(0, count($place_ids), '%d'));

        $lang_aliases = $this->get_lang_aliases($lang);
        $lang_phs = implode(',', array_fill(0, count($lang_aliases), '%s'));

        $sql = "SELECT r.*, t.text AS lang_text
                FROM {$wpdb->prefix}" . Database::REVIEW_TABLE . " r
                JOIN {$wpdb->prefix}" . Database::BUSINESS_TABLE . " p ON p.id = r.google_place_id
                LEFT JOIN {$wpdb->prefix}" . Database::TEXT_TABLE . " t
                    ON t.review_id = r.review_id AND t.lang IN ({$lang_phs})
                WHERE r.google_place_id IN ({$place_ids_phs}){$where_r}
                  AND r.author_url IS NOT NULL
                  AND (%d = 1 OR t.text IS NOT NULL OR r.language IN ({$lang_phs}) OR r.language IS NULL OR r.language = '')
                  AND NOT EXISTS (
                      SELECT 1 FROM {$wpdb->prefix}" . Database::REVIEW_TABLE . " r2
                      WHERE r2.google_place_id = r.google_place_id AND r2.author_url = r.author_url
                        AND (r2.time > r.time OR (r2.time = r.time AND r2.id > r.id))
                  )
                ORDER BY r.time DESC, r.id DESC";

        if ($limit > 0) {
            $sql .= " LIMIT " . (int)$limit;
        }

        $params = array_merge(
            $lang_aliases,
            $place_ids,
            $hidden_ids,
            [$all_langs],
            $lang_aliases
        );

        return $wpdb->get_results($wpdb->prepare($sql, $params));
    }

    public function get_overview($place_id = 0) {
        global $wpdb;

        // -------------- Get Google place --------------
        $place_sql = "SELECT id, place_id, name, rating, review_count, updated" .
                     " FROM " . $wpdb->prefix . Database::BUSINESS_TABLE .
                     " WHERE rating > 0 AND review_count > 0" . ($place_id > 0 ? ' AND id = %d' : '') .
                     " ORDER BY id DESC";

        $places = $place_id > 0 ?
                  // Query for specific Google place
                  $wpdb->get_results($wpdb->prepare($place_sql, sanitize_text_field(wp_unslash($place_id)))) :
                  // Query for summary (all places)
                  $wpdb->get_results($place_sql);

        $count = count($places);
        if ($count < 1) {
            return null;
        }

        $rating = 0;
        $review_count = 0;
        $google_places = array();
        $google_place_ids = array();

        foreach ($places as $place) {
            $id = $place->id;
            $name = $place->name;
            $rating += $place->rating;
            $review_count += $place->review_count;

            array_push($google_place_ids, $place->id);
            array_push($google_places, json_decode(json_encode(array('id' => $place->id, 'name' => $place->name, 'updated' => $place->updated))));
        }

        if ($count > 1) {
            $rating = round($rating / $count, 1);
            $rating = number_format((float)$rating, 1, '.', '');
            array_unshift($google_places, json_decode(json_encode(array('id' => 0, 'name' => 'Summary for all places'))));
        }

        // -------------- Get Google reviews --------------
        $google_reviews = array();
        $site_lang      = substr(get_locale(), 0, 2);

        $reviews = $this->fetch_reviews($google_place_ids, null, $site_lang, 1, 100, true);

        foreach ($reviews as $rev) {
            $text = empty($rev->lang_text) ? (empty($rev->text) ? null : $rev->text) : $rev->lang_text;

            $review = json_decode(json_encode(
                array(
                    'id'            => $rev->id,
                    'hide'          => $rev->hide,
                    'rating'        => $rev->rating,
                    'text'          => $text,
                    'author_url'    => $rev->author_url,
                    'author_name'   => $rev->author_name,
                    'time'          => $rev->time,
                )
            ));
            array_push($google_reviews, $review);
        }

        // -------------- Get Google stats --------------
        $stats = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM " . $wpdb->prefix . Database::STATS_TABLE .
                " WHERE google_place_id IN (" . implode(', ', array_fill(0, count($google_place_ids), '%d')) . ")" .
                " ORDER BY id DESC LIMIT 10000",
                $google_place_ids
            )
        );

        // -------------- Get min/max stats values --------------
        $stats_minmax = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT t1.* FROM " . $wpdb->prefix . Database::STATS_TABLE . " t1" .
                " JOIN (" .
                    "SELECT min(time) AS min_value, max(time) AS max_value, google_place_id FROM " . $wpdb->prefix . Database::STATS_TABLE .
                    " WHERE google_place_id IN (" . implode(', ', array_fill(0, count($google_place_ids), '%d')) . ")" .
                    " GROUP BY google_place_id" .
                ") AS t2 ON t1.google_place_id = t2.google_place_id AND (t1.time = t2.min_value OR t1.time = t2.max_value)",
                $google_place_ids
            )
        );

        return
            array(
                'rating'       => $rating,
                'review_count' => $review_count,
                'places'       => $google_places,
                'reviews'      => $google_reviews,
                'stats'        => $stats,
                'stats_minmax' => $stats_minmax
            );
    }

    public function get_short_name($author_name){
        $names = explode(" ", $author_name);
        if (count($names) > 1) {
            $last_index = count($names) - 1;
            $last_name = $names[$last_index];
            if ($this->_strlen($last_name) > 1) {
                $last_char = $this->_substr($last_name, 0, 1);
                $last_name = $this->_strtoupper($last_char) . ".";
                $names[$last_index] = $last_name;
                return implode(" ", $names);
            }
        }
        return $author_name;
    }

    private function sort_recent($a, $b) {
        return $b->time - $a->time;
    }

    private function parse_hidden_ids($input) {
        $ids = array();
        if (empty($input)) {
            return $ids;
        }
        if (is_string($input)) {
            $parts = preg_split('/\s*,\s*/', $input, -1, PREG_SPLIT_NO_EMPTY);
        } elseif (is_array($input)) {
            $parts = $input;
        } else {
            return $ids;
        }
        foreach ($parts as $p) {
            $val = intval($p);
            if ($val > 0) {
                $ids[] = $val;
            }
        }
        $ids = array_values(array_unique($ids));
        return $ids;
    }

    private function get_lang_aliases($lang) {
        $key = strtolower(str_replace('_', '-', trim((string)$lang)));
        $traditional = array('zh-TW', 'zh-HK', 'zh-MO', 'zh-Hant', 'zh-Hant-TW', 'zh-Hant-HK', 'zh-Hant-MO');
        $simplified  = array('zh', 'zh-CN', 'zh-SG', 'zh-Hans', 'zh-Hans-CN', 'zh-Hans-SG');

        if (in_array($key, array_map('strtolower', $traditional), true)) {
            return $traditional;
        }
        if (in_array($key, array_map('strtolower', $simplified), true)) {
            return $simplified;
        }
        return array($lang);
    }

    private function _strlen($str) {
        return function_exists('mb_strlen') ? mb_strlen($str, 'UTF-8') : strlen($str);
    }

    private function _substr($str, $start, $length = NULL) {
        return function_exists('mb_substr') ? mb_substr($str, $start, $length, 'UTF-8') : substr($str, $start, $length);
    }

    private function _strtoupper($str) {
        return function_exists('mb_strtoupper') ? mb_strtoupper($str, 'UTF-8') : strtoupper($str);
    }

}