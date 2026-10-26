<?php

namespace WP_Rplg_Google_Reviews\Includes;

use WP_Rplg_Google_Reviews\Includes\Core\Core;
use WP_Rplg_Google_Reviews\Includes\Core\Badge_Live;

// A badge is an ordinary grw_feed widget with view_mode 'badge', so Widgets, shortcode, block and cron need nothing of their own.
class Plugin_Badge extends Plugin_Step_Page {

    const GLOBAL_OPTION = 'grw_badge_id';
    const LIST_VIEW = 'badge';
    // A badge is told apart by its post_content; every writer in the plugin emits compact JSON.
    const CONTENT_MARK = '"view_mode":"badge"';

    public function register() {
        add_action('grw_admin_page_grw-badge', array($this, 'render'));
        add_action('wp_ajax_grw_badge_data', array($this, 'data'));
        add_action('wp_ajax_grw_badge_save', array($this, 'save'));
        add_action('admin_init', array($this, 'redirect'));
        add_action('pre_get_posts', array($this, 'only_badges'));
        add_filter('posts_where', array($this, 'where'), 10, 2);
        add_filter('views_edit-' . Post_Types::FEED_POST_TYPE, array($this, 'view'));
        add_filter('display_post_states', array($this, 'post_states'), 10, 2);
        add_action('manage_posts_extra_tablenav', array($this, 'keep_view'));
        add_filter('admin_body_class', array($this, 'body_class'));
    }

    public static function is_list() {
        return $GLOBALS['pagenow'] === 'edit.php' && isset($_GET['post_type'], $_GET['grw_view'])
            && $_GET['post_type'] === Post_Types::FEED_POST_TYPE && $_GET['grw_view'] === self::LIST_VIEW;
    }

    public static function list_url() {
        return admin_url('edit.php?post_type=' . Post_Types::FEED_POST_TYPE . '&grw_view=' . self::LIST_VIEW);
    }

    // The Badges page of 7.1 is kept only for its links: bookmarks, the readme and support answers.
    public function redirect() {
        global $plugin_page;
        if ($plugin_page !== 'grw-badge') return;
        $id = isset($_GET['grw_feed_id']) ? (int) $_GET['grw_feed_id'] : 0;
        $create = admin_url('admin.php?page=grw-widget' . ($id ? '&grw_feed_id=' . $id : '&kind=badge'));
        wp_safe_redirect($id || isset($_GET['new']) || !self::count() ? $create : self::list_url());
        exit;
    }

    private static function count() {
        global $wpdb;
        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = %s AND post_status NOT IN ('trash', 'auto-draft') AND post_content LIKE %s",
            Post_Types::FEED_POST_TYPE, '%' . $wpdb->esc_like(self::CONTENT_MARK) . '%'
        ));
    }

    public function view($views) {
        $n = self::count();
        if ($n || self::is_list()) {
            $views[self::LIST_VIEW] = '<a href="' . esc_url(self::list_url()) . '"' . (self::is_list() ? ' class="current" aria-current="page"' : '') . '>'
                . esc_html__('Badges', 'widget-google-reviews') . ' <span class="count">(' . number_format_i18n($n) . ')</span></a>';
        }
        return $views;
    }

    public function only_badges($query) {
        if (is_admin() && $query->is_main_query() && self::is_list() && $query->get('post_type') === Post_Types::FEED_POST_TYPE) {
            $query->set('grw_badges', true);
        }
    }

    public function where($where, $query) {
        if (!$query->get('grw_badges')) return $where;
        global $wpdb;
        return $where . $wpdb->prepare(" AND {$wpdb->posts}.post_content LIKE %s", '%' . $wpdb->esc_like(self::CONTENT_MARK) . '%');
    }

    public function post_states($states, $post) {
        if ($post->post_type === Post_Types::FEED_POST_TYPE && $post->post_status !== 'trash' && (int) get_option(self::GLOBAL_OPTION) === (int) $post->ID) {
            $states['grw_badge_global'] = __('Displayed on the site', 'widget-google-reviews');
        }
        return $states;
    }

    // The list's GET form (search, date filter, bulk actions) carries only core fields; without this it lands on Widgets.
    public function keep_view($which) {
        if ($which === 'top' && self::is_list()) {
            echo '<input type="hidden" name="grw_view" value="' . esc_attr(self::LIST_VIEW) . '">';
        }
    }

    // Every row here is a badge, so the Theme column is hidden by CSS; dropping it from the column set would
    // desync the rows Quick Edit returns over ajax, where the list is not recognisable.
    public function body_class($classes) {
        return self::is_list() ? $classes . ' grw-badges-list' : $classes;
    }

    private static function keys() {
        return array_merge(Core::badge_option_keys(), array('style_vars', 'anonym_avatar'));
    }

    public function render() {
        $feed = $this->feed();
        $this->render_page('grw-badge', '', $this->page_vars($feed, array(
            'global' => $feed ? (int) get_option(self::GLOBAL_OPTION) === (int) $feed->ID : true,
        )), 'Choose a badge');
    }

    public function data() {
        $this->guard();
        $pids = $this->pids();
        $post_id = isset($_POST['post_id']) ? (int) $_POST['post_id'] : 0;
        wp_send_json(array('status' => 'success', 'result' => $this->preview_data($pids, $post_id)));
    }

    public function preview_data($pids, $post_id = 0) {
        if (empty($pids)) {
            return self::sample();
        }
        $data = $this->core->get_data($this->badge_content($pids, array(), $this->existing($post_id, array('badge')), $this->langs()), false);
        $bizs = array();
        foreach ($data['businesses'] as $b) {
            $bizs[] = array(
                'id'       => $b->id,
                'name'     => $b->name,
                'photo'    => $b->photo,
                'url'      => $b->url,
                'provider' => 'google',
                'rating'   => $b->rating,
                'total'    => (int) $b->review_count,
            );
        }
        $summary = Badge_Live::summary($data['businesses']);
        if ($summary && $summary->provider === 'summary') {
            array_unshift($bizs, array(
                'id'       => 'summary',
                'name'     => $summary->name,
                'photo'    => $summary->photo,
                'url'      => $summary->url,
                'provider' => 'summary',
                'rating'   => $summary->rating,
                'total'    => (int) $summary->review_count,
                'wr'       => $summary->wr,
            ));
        }
        $popup = function($anonym) use ($data) {
            $data['options']->anonym_avatar = $anonym;
            ob_start();
            (new View())->render_popup($data['reviews'], $data['options']);
            return ob_get_clean();
        };
        return array(
            'bizs'         => $bizs,
            'live'         => Badge_Live::build($data['reviews']),
            'popup'        => $popup(false),
            'popup_anonym' => $popup(true),
            'sample'       => false,
        );
    }

    public static function sample() {
        $now = time();
        $recent = array();
        $phrases = array();
        $rows = array(
            array(5, 'Emma Johnson', 'Friendly staff and really quick service', 3),
            array(5, 'Liam Carter', 'Everything was clean and well organized', 6),
            array(4, 'Sofia Martinez', 'Great value, we will definitely come back', 9),
            array(5, 'Noah Williams', 'The team went out of their way to help us', 14),
            array(5, 'Olivia Brown', 'Best experience we have had in a long time', 21),
        );
        foreach ($rows as $r) {
            $recent[] = array('r' => $r[0], 'a' => $r[1], 't' => $now - $r[3] * 86400);
            $phrases[] = array('t' => $r[2], 'a' => $r[1]);
        }
        return array(
            'bizs'   => array(array('id' => 'sample', 'name' => 'Your business', 'photo' => '', 'url' => '', 'provider' => 'google', 'rating' => '4.8', 'total' => 1284)),
            'live'   => array('phrases' => $phrases, 'recent' => $recent),
            'sample' => true,
        );
    }

    private function badge_content($pids, $options, $old = null, $langs = array()) {
        $content = $this->content($pids, $options, $old, $langs, array_merge(self::keys(), array('view_mode')), array('view_mode' => 'badge'));
        // A badge switched to a slider or grid in the builder must not land on the legacy Standard card.
        if (empty($content->options->card_style)) $content->options->card_style = 'c1';
        return $content;
    }

    public function save() {
        $this->guard();

        $pids = $this->pids();
        if (empty($pids)) {
            wp_send_json(array('status' => 'failed', 'result' => array('error_message' => 'Connect a place to publish the badge.')));
        }

        $post_id = isset($_POST['post_id']) ? (int) $_POST['post_id'] : 0;
        $title = isset($_POST['title']) ? sanitize_text_field(wp_unslash($_POST['title'])) : '';
        $old = $this->existing($post_id, array('badge'));
        // The Create Widget page keeps the id of what it published last; a badge picked after a slider is a widget of its own.
        if (!$old) {
            $post_id = 0;
            $title = '';
        }

        $content = $this->badge_content($pids, $this->posted_options(self::keys()), $old, $this->langs());
        $post_id = $this->save_feed($post_id, $title, $content, 'badge');

        $global = !empty($_POST['global']) && $_POST['global'] !== 'false' && $_POST['global'] !== '0';
        // An embed badge belongs in the page content, and a new badge goes on the site only by its step 3 switch, after this save.
        $global = $global && $old && ($content->options->badge_pos ?? '') !== 'embed';
        $current = (int) get_option(self::GLOBAL_OPTION);
        if ($global) {
            update_option(self::GLOBAL_OPTION, $post_id);
        } else if ($current === $post_id) {
            delete_option(self::GLOBAL_OPTION);
        }

        wp_send_json(array('status' => 'success', 'result' => array(
            'id'        => $post_id,
            'title'     => get_post_field('post_title', $post_id, 'raw'),
            'shortcode' => '[grw id=' . $post_id . ']',
            'global'    => $global,
            'replaced'  => $global && $current && $current !== $post_id ? $current : 0,
        )));
    }
}
