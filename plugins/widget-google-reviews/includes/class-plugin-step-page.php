<?php

namespace WP_Rplg_Google_Reviews\Includes;

use WP_Rplg_Google_Reviews\Includes\Core\Core;
use WP_Rplg_Google_Reviews\Includes\Core\Google_Dao;

// The server half of admin-steps.js, shared by the theme and badge parts of the Create Widget page: places, connections, saving.
abstract class Plugin_Step_Page {

    protected $dao;
    protected $core;
    protected $places;
    protected $feed_serializer;
    protected $feed_deserializer;

    public function __construct(Google_Dao $dao, Core $core, Plugin_Places $places, Feed_Serializer $feed_serializer, Feed_Deserializer $feed_deserializer) {
        $this->dao               = $dao;
        $this->core              = $core;
        $this->places            = $places;
        $this->feed_serializer   = $feed_serializer;
        $this->feed_deserializer = $feed_deserializer;
    }

    protected function feed() {
        $feed_id = isset($_GET['grw_feed_id']) ? (int) $_GET['grw_feed_id'] : 0;
        return $feed_id ? $this->feed_deserializer->get_feed($feed_id) : null;
    }

    // What every step page passes to grw_steps(); $extra adds the page's own.
    protected function page_vars($feed, $extra = array()) {
        $content = $feed ? json_decode($feed->post_content) : null;

        $places = array();
        foreach ($this->dao->get_places() as $place) {
            $places[] = array(
                'id'      => $place->place_id,
                'name'    => $place->name,
                'address' => (string) $place->address,
                'photo'   => empty($place->photo) ? GRW_GOOGLE_BIZ : $place->photo,
                'rating'  => $place->rating > 0 ? number_format((float) $place->rating, 1, '.', '') : '',
                'total'   => (int) $place->review_count,
                'lang'    => $this->places->fetch_pairs($place)[0]['lang'],
            );
        }

        $selected = array();
        $langs = array();
        if ($content && !empty($content->connections)) {
            foreach ($content->connections as $conn) {
                if (empty($conn->id)) continue;
                $selected[] = $conn->id;
                if (!empty($conn->lang)) $langs[$conn->id] = $conn->lang;
            }
        }

        return array_merge(array(
            'places'   => $places,
            'selected' => $selected,
            'langs'    => (object) $langs,
            'options'  => $content && isset($content->options) ? $content->options : new \stdClass(),
            'feedId'   => $feed ? $feed->ID : 0,
            'title'    => $feed ? $feed->post_title : '',
            'hasKey'   => !empty(get_option('grw_google_api_key')),
            'authcode' => (string) get_option('grw_auth_code'),
            'lang'     => strtolower(explode('_', get_locale())[0]),
            'nonce'    => wp_create_nonce('grw_wpnonce'),
            'widgets'  => admin_url('edit.php?post_type=' . Post_Types::FEED_POST_TYPE),
            'home'     => home_url('/'),
            'blank'    => GRW_GOOGLE_BIZ,
            'connect'  => apply_filters('grw_connect_url', 'https://app.richplugins.com/public/connect'),
        ), $extra);
    }

    protected function render_page($id, $class, $vars, $first) {
        wp_nonce_field('grw_wpnonce', 'grw_nonce');
        ?>
        <div id="<?php echo esc_attr($id); ?>" class="grw-badge-page <?php echo esc_attr($class); ?>" data-vars="<?php echo esc_attr(wp_json_encode($vars)); ?>">
            <ol class="grw-badge-steps">
                <li data-step="1" class="grw-badge-step-on"><span>1</span><?php echo esc_html($first); ?></li>
                <li data-step="2"><span>2</span>Set up</li>
                <li data-step="3"><span>3</span>Publish</li>
            </ol>
            <div class="grw-badge-body"></div>
        </div>
        <?php
    }

    protected function pids() {
        return isset($_POST['pids']) && is_array($_POST['pids']) ? array_map('sanitize_text_field', wp_unslash($_POST['pids'])) : array();
    }

    // A widget made in the builder keeps the connection details and options a step page has no controls for.
    // Only a widget of one of $modes counts: a badge saved over a slider (or back) would carry the other kind's options.
    protected function existing($post_id, $modes) {
        if (!$post_id) return null;
        $feed = $this->feed_deserializer->get_feed((int) $post_id);
        $old = $feed ? json_decode($feed->post_content, true) : null;
        return is_array($old) && in_array($old['options']['view_mode'] ?? '', $modes, true) ? $old : null;
    }

    protected function langs() {
        $raw = isset($_POST['langs']) ? json_decode(wp_unslash($_POST['langs']), true) : null;
        $langs = array();
        foreach (is_array($raw) ? $raw : array() as $pid => $lang) {
            if (is_string($lang) && $lang !== '') $langs[sanitize_text_field($pid)] = sanitize_text_field($lang);
        }
        return $langs;
    }

    // The posted options limited to $keys; empty and false values are left to the defaults, except an empty choice
    // over a default that is not empty (a badge's label icon "None"), which the default would otherwise replace.
    protected function posted_options($keys) {
        $raw = isset($_POST['options']) ? json_decode(wp_unslash($_POST['options']), true) : array();
        return self::pick(is_array($raw) ? $raw : array(), $keys);
    }

    protected static function pick($raw, $keys) {
        $defaults = Core::get_default_options();
        $options = array();
        foreach ($keys as $key) {
            if (!isset($raw[$key]) || $raw[$key] === false || ($raw[$key] === '' && empty($defaults[$key]))) {
                continue;
            }
            $options[$key] = is_bool($raw[$key]) ? $raw[$key] : sanitize_text_field((string) $raw[$key]);
        }
        return $options;
    }

    // The connections for $pids, then the old options without $own keys under $defaults and $options.
    protected function content($pids, $options, $old, $langs, $own, $defaults) {
        $kept = array();
        foreach ((array) ($old['connections'] ?? array()) as $conn) {
            if (!empty($conn['id'])) $kept[$conn['id']] = $conn;
        }
        $connections = array();
        foreach ($pids as $pid) {
            $conn = null;
            if (isset($kept[$pid])) {
                $conn = $kept[$pid];
            } else {
                $place = $this->dao->get_place($pid);
                if ($place) $conn = $this->places->connection($place);
            }
            if (!$conn) continue;
            if (!empty($langs[$pid])) $conn['lang'] = $langs[$pid];
            $connections[] = $conn;
        }
        $base = is_array($old['options'] ?? null) ? $old['options'] : array();
        foreach ($own as $key) {
            unset($base[$key]);
        }
        $options = array_merge($defaults, $base, $options);
        return json_decode(wp_json_encode(array('connections' => $connections, 'options' => $options)));
    }

    // Saves $content as the widget $post_id (0 for a new one) and returns its id; answers the request itself on failure.
    protected function save_feed($post_id, $title, $content, $noun) {
        if (empty($content->connections)) {
            wp_send_json(array('status' => 'failed', 'result' => array('error_message' => 'The chosen place is not connected yet.')));
        }
        if ($title === '') {
            $title = $content->connections[0]->name . ' ' . $noun;
        }
        // Feed_Serializer::save() unslashes its content as it would POST data, so PHP-built JSON goes in slashed.
        $post_id = $this->feed_serializer->save($post_id, $title, wp_slash(wp_json_encode($content, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)));
        if (!$post_id) {
            wp_send_json(array('status' => 'failed', 'result' => array('error_message' => 'Could not save the ' . $noun . '.')));
        }
        return (int) $post_id;
    }

    protected function guard() {
        if (!current_user_can('manage_options')) {
            wp_send_json(array('status' => 'failed', 'result' => array('error_message' => 'Permission denied.')));
        }
        check_admin_referer('grw_wpnonce', 'grw_nonce');
    }
}
