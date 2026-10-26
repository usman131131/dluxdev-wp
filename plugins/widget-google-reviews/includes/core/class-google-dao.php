<?php

namespace WP_Rplg_Google_Reviews\Includes\Core;

class Google_Dao {

    private $helper;

    public function __construct(Connect_Helper $helper) {
        $this->helper = $helper;
    }

    public function save($place, $local_img = true) {
        global $wpdb;

        $log = array(round(microtime(true) * 1000));
        update_option('grw_last_error', '');

        $db_place_id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM " . $wpdb->prefix . Database::BUSINESS_TABLE . " WHERE place_id = %s", $place->place_id
            )
        );

        // Insert or update Google place
        if ($db_place_id) {
            $this->update_place($place, $db_place_id, $local_img, $log);
        } else {
            $db_place_id = $this->insert_place($place, $local_img, $log);
        }
        $this->log_last_error($wpdb);

        // Insert or update Google reviews
        if (isset($place->reviews)) {

            $reviews = $place->reviews;

            foreach ($reviews as $review) {
                if (!is_object($review)) continue;

                $db_review_id = 0;

                $review->provider = empty($review->provider) ? 'google' : $review->provider;

                $rid = $this->review_id($place->place_id, $review);
                if (empty($rid)) continue;

                $db_review_id = $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT id FROM " . $wpdb->prefix . Database::REVIEW_TABLE . "
                         WHERE google_place_id = %d AND provider = %s AND review_id = %s
                         LIMIT 1",
                        $db_place_id,
                        $review->provider,
                        $rid
                    )
                );

                if (empty($db_review_id)) {
                    $db_review_id = $this->old_review_id($db_place_id, $review);
                }

                $author_img = null;
                if (isset($review->profile_photo_url)) {
                    if ($local_img === true || $local_img == 'true') {
                        $img_name = $place->place_id . '_' . md5($review->profile_photo_url);
                        $author_img = $this->helper->upload_image($review->profile_photo_url, $img_name);
                    } else {
                        $author_img = $review->profile_photo_url;
                    }
                }

                $images = null;
                if (isset($review->images) && count($review->images) > 0) {
                    if ($local_img === true || $local_img == 'true') {
                        $saved_imgs = [];
                        foreach ($review->images as $img) {
                            $img_name = $place->place_id . '_img_' . md5($img);
                            $saved_img = $this->helper->upload_image($img, $img_name);
                            array_push($saved_imgs, $saved_img);
                        }
                        $images = implode(';', $saved_imgs);
                    } else {
                        $images = implode(';', $review->images);
                    }
                }

                $reply = null;
                $reply_time = null;
                if (isset($review->reply) && strlen($review->reply) > 0) {
                    $reply = $review->reply;
                    $reply_time = $review->reply_time;
                }

                $review_lang = null;
                if (isset($review->language)) {
                    $review_lang = ($review->language == 'en-US' ? 'en' : $review->language);
                }

                if ($db_review_id) {
                    $this->update_review($place->place_id, $review, $review_lang, $author_img, $images, $reply, $reply_time, $db_review_id, $log);
                } else {
                    $this->insert_review($place->place_id, $review, $review_lang, $author_img, $images, $reply, $reply_time, $db_place_id, $log);
                }
                $this->log_last_error($wpdb);
            }
        }

        update_option('grw_save_log', implode('_', $log));
    }

    private function old_review_id($db_place_id, $review) {
        global $wpdb;

        $where = " WHERE";
        $where_params = array();

        if (!empty($review->author_url)) {
            $where .= " author_url = %s";
            array_push($where_params, $review->author_url);
        } else {
            $where .= " time = %d";
            array_push($where_params, $this->review_time($review));

            if (!empty($review->author_name)) {
                $where .= " AND author_name = %s";
                array_push($where_params, $review->author_name);
            }
        }

        if ($db_place_id) {
            $where .= " AND google_place_id = %d";
            array_push($where_params, $db_place_id);
        }

        $sql = "SELECT id FROM " . $wpdb->prefix . Database::REVIEW_TABLE . $where . " ORDER BY time DESC, id DESC LIMIT 1";
        return $wpdb->get_var($wpdb->prepare($sql, $where_params));
    }

    public function insert_place($place, $local_img, &$log = []) {
        global $wpdb;

        // Insert Google place
        $pid = $place->place_id;
        $name = $place->name;
        $rating = isset($place->rating) ? $place->rating : null;
        $review_count = isset($place->user_ratings_total) ? $place->user_ratings_total : (isset($place->reviews) ? count($place->reviews) : null);
        $place->photo = $this->get_place_photo($place, $local_img);

        $atts = array(
            'place_id'     => $pid,
            'rating'       => $rating,
            'review_count' => $review_count,
            'name'         => $name,
            'photo'        => $place->photo,
            'url'          => isset($place->url) ? $place->url     : null,
            'website'      => isset($place->website) ? $place->website : null,
            'icon'         => isset($place->icon) ? $place->icon : null,
            'address'      => isset($place->formatted_address) ? $place->formatted_address : null,
            'updated'      => round(microtime(true) * 1000)
        );
        if (isset($place->map_url) && strlen($place->map_url) > 0) {
            $atts['map_url'] = $place->map_url;
        }

        $wpdb->insert($wpdb->prefix . Database::BUSINESS_TABLE, $atts);
        $db_place_id = $wpdb->insert_id;

        array_push($log, 'ip[' . $pid . ',' . $name . ',' . $rating . ',' . $review_count . ']');

        if ($rating > 0) {
            $this->insert_stats($rating, $review_count, $db_place_id);
        }

        return $db_place_id;
    }

    public function update_place($place, $db_place_id, $local_img, &$log = []) {
        global $wpdb;

        $name = empty($place->name) ? '' : $place->name;
        $rating = empty($place->rating) ? '' : $place->rating;

        // Update Google place name and rating
        $update_params = array('updated' => round(microtime(true) * 1000));

        if (!empty($name)) {
            $update_params['name'] = $name;
        }
        if (!empty($rating)) {
            $update_params['rating'] = $rating;
        }

        // Update total reviews
        $review_count = isset($place->user_ratings_total) ? $place->user_ratings_total : 0;
        if ($review_count > 0) {
            $update_params['review_count'] = $review_count;
        }

        // Update business photo
        if (!empty($place->business_photo)) {
            $place->photo = $this->get_place_photo($place, $local_img);
            $update_params['photo'] = $place->photo;
        }

        // Update map URL
        if (isset($place->map_url) && strlen($place->map_url) > 0) {
            $update_params['map_url'] = $place->map_url;
        }

        $wpdb->update($wpdb->prefix . Database::BUSINESS_TABLE, $update_params, array('ID' => $db_place_id));

        array_push($log, 'up[' . $db_place_id . ',' . $name . ',' . $rating . ',' . $review_count . ']');

        if (!empty($rating)) {
            $this->update_stats($rating, $review_count, $db_place_id);
        }
    }

    public function get_places() {
        global $wpdb;

        return $wpdb->get_results(
            "SELECT p.*, (SELECT COUNT(DISTINCT COALESCE(r.author_url, r.review_id)) FROM " . $wpdb->prefix . Database::REVIEW_TABLE . " r WHERE r.google_place_id = p.id) AS db_review_count" .
            " FROM " . $wpdb->prefix . Database::BUSINESS_TABLE . " p ORDER BY p.name ASC"
        );
    }

    public function get_place($place_id) {
        global $wpdb;

        return $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM " . $wpdb->prefix . Database::BUSINESS_TABLE . " WHERE place_id = %s", $place_id)
        );
    }

    // Languages the reviews of a place were fetched in, most recent first. The review row only keeps
    // the language of the last fetch, so the per-language text table is the source; older rows without texts fall back.
    public function get_place_langs($db_place_id) {
        global $wpdb;

        $langs = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT t.lang FROM " . $wpdb->prefix . Database::TEXT_TABLE . " t" .
                " JOIN " . $wpdb->prefix . Database::REVIEW_TABLE . " r ON r.review_id = t.review_id" .
                " WHERE r.google_place_id = %d AND t.lang <> ''" .
                " GROUP BY t.lang ORDER BY MAX(t.id) DESC", $db_place_id
            )
        );
        if (!empty($langs)) {
            return $langs;
        }
        return $wpdb->get_col(
            $wpdb->prepare(
                "SELECT language FROM " . $wpdb->prefix . Database::REVIEW_TABLE .
                " WHERE google_place_id = %d AND language IS NOT NULL AND language <> ''" .
                " GROUP BY language ORDER BY MAX(id) DESC", $db_place_id
            )
        );
    }

    public function has_local_images($db_place_id) {
        global $wpdb;

        $base_url = wp_upload_dir(null, false)['baseurl'];
        return (bool) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT 1 FROM " . $wpdb->prefix . Database::REVIEW_TABLE .
                " WHERE google_place_id = %d AND profile_photo_url LIKE %s LIMIT 1",
                $db_place_id, $wpdb->esc_like($base_url) . '%'
            )
        );
    }

    public function get_place_by_id($db_place_id) {
        global $wpdb;

        return $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM " . $wpdb->prefix . Database::BUSINESS_TABLE . " WHERE id = %d", $db_place_id)
        );
    }

    public function delete_place($db_place_id) {
        global $wpdb;

        $review_table = $wpdb->prefix . Database::REVIEW_TABLE;

        $urls = $wpdb->get_col($wpdb->prepare("SELECT photo FROM " . $wpdb->prefix . Database::BUSINESS_TABLE . " WHERE id = %d", $db_place_id));
        foreach ($wpdb->get_results($wpdb->prepare("SELECT profile_photo_url, images FROM " . $review_table . " WHERE google_place_id = %d", $db_place_id)) as $row) {
            $urls[] = $row->profile_photo_url;
            $urls = array_merge($urls, explode(';', (string) $row->images));
        }
        $this->delete_local_files($urls);
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM " . $wpdb->prefix . Database::TEXT_TABLE .
                " WHERE review_id IN (SELECT review_id FROM " . $review_table . " WHERE google_place_id = %d)", $db_place_id
            )
        );
        $wpdb->delete($review_table, array("google_place_id" => $db_place_id));
        $wpdb->delete($wpdb->prefix . Database::STATS_TABLE, array("google_place_id" => $db_place_id));
        return $wpdb->delete($wpdb->prefix . Database::BUSINESS_TABLE, array("id" => $db_place_id)) !== false;
    }

    // Images saved by Connect_Helper::upload_image live in uploads and are named after the place, so nothing else refers to them.
    private function delete_local_files($urls) {
        $upload_dir = wp_upload_dir(null, false);
        $base_url = trailingslashit($upload_dir['baseurl']);
        $base_dir = trailingslashit($upload_dir['basedir']);
        foreach (array_unique(array_filter($urls)) as $url) {
            if (strpos($url, $base_url) !== 0) {
                continue;
            }
            $path = $base_dir . str_replace('..', '', substr($url, strlen($base_url)));
            if (is_file($path)) {
                wp_delete_file($path);
            }
        }
    }

    public function get_place_photo($place, $local_img) {
        $photo = null;
        if (!empty($place->business_photo)) {
            // The new Places API path uploads the photo before saving, so it is already local.
            if (strpos($place->business_photo, wp_upload_dir(null, false)['baseurl']) === 0) {
                $photo = $place->business_photo;
            } else if ($local_img === true || $local_img == 'true') {
                $photo = $this->helper->upload_image($place->business_photo, $place->place_id);
            } else {
                $photo = $place->business_photo;
            }
        }
        return $photo;
    }

    public function update_stats($rating, $review_count, $db_place_id) {
        global $wpdb;

        // Insert Google place rating stats
        $stats = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT rating, review_count FROM " . $wpdb->prefix . Database::STATS_TABLE .
                " WHERE google_place_id = %d ORDER BY id DESC LIMIT 1", $db_place_id
            )
        );
        if (count($stats) > 0) {
            if ($stats[0]->rating != $rating || ($review_count > 0 && $stats[0]->review_count != $review_count)) {
                $this->insert_stats($rating, $review_count, $db_place_id);
            }
        } else {
            $this->insert_stats($rating, $review_count, $db_place_id);
        }
    }

    public function insert_stats($rating, $review_count, $db_place_id) {
        global $wpdb;

        $wpdb->insert($wpdb->prefix . Database::STATS_TABLE, array(
            'google_place_id' => $db_place_id,
            'time'            => time(),
            'rating'          => $rating,
            'review_count'    => $review_count
        ));
        $this->log_last_error($wpdb);
    }

    public function update_review($pid, $review, $review_lang, $author_img, $images, $reply, $reply_time, $db_review_id, &$log = []) {
        global $wpdb;

        $update_params = array();

        $rating = empty($review->rating) ? null : $review->rating;
        $text = empty($review->text) ? null : $review->text;
        $text_size = empty($text) ? 0 : strlen($text);
        $author_name = empty($review->author_name) ? null : $review->author_name;

        $review_id = $this->review_id($pid, $review);
        if (!empty($review_id)) {
            $update_params['review_id'] = $review_id;
        }
        if (!empty($rating)) {
            $update_params['rating'] = $rating;
        }
        if ($author_img) {
            $update_params['profile_photo_url'] = $author_img;
        }
        if ($images) {
            $update_params['images'] = $images;
        }
        if ($reply) {
            $update_params['reply'] = $reply;
            $update_params['reply_time'] = $reply_time;
        }
        if (isset($review->url)) {
            $update_params['url'] = $review->url;
        }
        if (isset($review->provider)) {
            $update_params['provider'] = $review->provider;
        }

        if (!empty($update_params)) {
            $wpdb->update($wpdb->prefix . Database::REVIEW_TABLE, $update_params, array('id' => $db_review_id));
        }

        $this->upsert_review_text($pid, $review, $review_lang, $text);

        array_push($log, 'ur[' . $db_review_id . ',' . $author_name . ',' . $rating . ',' . $text_size . ',' . $review_lang . ']');
    }

    public function insert_review($pid, $review, $review_lang, $author_img, $images, $reply, $reply_time, $db_place_id, &$log = []) {
        global $wpdb;

        $review_id = $this->review_id($pid, $review);
        if (empty($review_id)) return;

        $rating = empty($review->rating) ? null : $review->rating;
        $text = empty($review->text) ? null : $review->text;
        $text_size = empty($text) ? 0 : strlen($text);
        $time = $this->review_time($review);
        $author_name = empty($review->author_name) ? null : $review->author_name;

        $wpdb->insert($wpdb->prefix . Database::REVIEW_TABLE, array(
            'google_place_id'   => $db_place_id,
            'review_id'         => $review_id,
            'rating'            => $rating,
            'time'              => $time,
            'language'          => $review_lang,
            'author_name'       => $author_name,
            'author_url'        => isset($review->author_url) ? $review->author_url : null,
            'profile_photo_url' => $author_img,
            'url'               => isset($review->url) ? $review->url : null,
            'provider'          => isset($review->provider) ? $review->provider : null,
            'images'            => $images,
            'reply'             => $reply,
            'reply_time'        => $reply_time
        ));

        $db_review_id = $wpdb->insert_id;

        $this->upsert_review_text($pid, $review, $review_lang, $text);

        array_push($log, 'ir[' . $author_name . ',' . $rating . ',' . $text_size . ',' . $review_lang . ']');
    }

    private function upsert_review_text($pid, $review, $lang, $text) {
        global $wpdb;

        if (empty($lang) || !isset($text) || $text === '') {
            return;
        }

        $review_id = $this->review_id($pid, $review);
        if (empty($review_id)) {
            return;
        }

        $wpdb->query($wpdb->prepare('INSERT INTO ' . $wpdb->prefix . Database::TEXT_TABLE . ' (review_id, lang, text) ' .
            'VALUES (%s, %s, %s) ON DUPLICATE KEY UPDATE text = VALUES(text)', $review_id, $lang, $text));

        $this->log_last_error($wpdb);
    }

    private function review_id($pid, $review) {
        if (empty($review->provider)) return;

        if (!empty($review->id)) {
            return $review->provider . ':' . $review->id;
        }

        if (empty($pid)) return;

        $time = (string)$this->review_time($review);

        return md5($review->provider . ':' . $pid . ':' . (
            empty($review->author_url)
                ? (empty($review->author_name) ? $time : $review->author_name . ':' . $time)
                : $review->author_url
        ));
    }

    private function review_time($review) {
        if (!empty($review->time)) return (int)$review->time;

        if (!empty($review->time_str)) {
            $ts = strtotime($review->time_str);
            if ($ts) return (int)$ts;
        }

        return 0;
    }

    private function log_last_error($wpdb) {
        if (!empty($wpdb->last_error)) {
            $last_error = $wpdb->last_error;
            $opt = get_option('grw_last_error');
            if (empty($opt)) {
                $now = floor(microtime(true) * 1000);
                $opt = $now . ': ';
            }
            update_option('grw_last_error', $opt . $last_error . '; ');
        }
    }
}