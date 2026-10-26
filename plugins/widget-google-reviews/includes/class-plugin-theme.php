<?php

namespace WP_Rplg_Google_Reviews\Includes;

// A theme is an ordinary widget, a slider or grid with a card preset or a list or rating, so after publishing it is edited in the builder like any other.
class Plugin_Theme extends Plugin_Step_Page {

    const CARDS = array('slider', 'grid');
    const LAYOUTS = array('slider', 'grid', 'list', 'rating');

    const KEYS = array('view_mode', 'card_style', 'card_layout', 'style_vars', 'dark_theme', 'anonym_avatar', 'short_last_name', 'header_hide_social', 'header_center',
                       'slider_head_pos', 'header_hide_photo', 'header_hide_name', 'header_show_scale', 'header_platform', 'header_order', 'hide_based_on',
                       'hide_writereview', 'header_short_count', 'header_bold_count', 'header_hide_grade', 'slider_autoplay', 'slider_speed', 'slider_mousestop', 'slider_hide_prevnext', 'slider_hide_dots',
                       'rating_popup');

    // Step 1 shows each layout with this many reviews.
    const GALLERY = 10;

    private $badge;

    public function __construct(Plugin_Badge $badge, ...$deps) {
        parent::__construct(...$deps);
        $this->badge = $badge;
    }

    public function register() {
        add_action('grw_admin_page_grw-widget', array($this, 'render'));
        add_action('wp_ajax_grw_theme_data', array($this, 'data'));
        add_action('wp_ajax_grw_theme_preview', array($this, 'preview'));
        add_action('wp_ajax_grw_theme_save', array($this, 'save'));
        add_action('wp_ajax_grw_theme_page', array($this, 'page'));
    }

    public function render() {
        $feed = $this->feed();
        $this->render_page('grw-themes', 'grw-themes-page', $this->page_vars($feed, array(
            'global'  => $feed && (int) get_option(Plugin_Badge::GLOBAL_OPTION) === (int) $feed->ID,
            'builder' => admin_url('admin.php?page=grw-builder&' . Post_Types::FEED_POST_TYPE . '_id='),
            'cards'   => array('presets' => Card_Presets::PRESETS, 'skin' => Card_Presets::SKIN, 'ink' => Card_Presets::INK, 'shadows' => Card_Presets::SHADOWS, 'vars' => Card_Presets::VARS),
        )), 'Choose a design');
    }

    // Unlike a badge's, these options default to true, so a false one is kept.
    private static function options($raw) {
        $options = array();
        foreach (self::KEYS as $key) {
            if (!isset($raw[$key]) || $raw[$key] === '') continue;
            $options[$key] = is_bool($raw[$key]) ? $raw[$key] : sanitize_text_field((string) $raw[$key]);
        }
        // Not in KEYS: an empty paging means all reviews, and a widget the page sends without one keeps its own.
        if (isset($raw['pagination']) && is_scalar($raw['pagination'])) $options['pagination'] = sanitize_text_field((string) $raw['pagination']);
        if (!isset($options['view_mode']) || !in_array($options['view_mode'], self::LAYOUTS, true)) $options['view_mode'] = 'slider';
        if (empty($options['card_style'])) $options['card_style'] = 'c1';
        return Card_Presets::sanitize($options);
    }

    // Stored as the gallery shows them: the builder fills a missing key from its form (paging off, round corners, popup on).
    private function theme_content($pids, $options, $old, $langs) {
        $stored = array('pagination' => '10');
        if (in_array($options['view_mode'] ?? '', array('list', 'rating'), true)) {
            $stored += array('show_round' => false, 'show_shadow' => false, 'rating_popup' => false);
        }
        return $this->content($pids, $options, $old, $langs, self::KEYS, $stored);
    }

    // Step 1: every preset as a slider, then as a grid, a list and a rating, and the badges' own preview data.
    public function data() {
        $this->guard();
        // Each tile's header: plain header options and header variables, keyed "layout:preset".
        $headers = isset($_POST['headers']) ? json_decode(wp_unslash($_POST['headers']), true) : null;
        $sets = array();
        foreach (self::LAYOUTS as $layout) {
            foreach (in_array($layout, self::CARDS, true) ? array_keys(Card_Presets::PRESETS) : array('') as $id) {
                $sets[] = self::options(array_merge(isset($headers[$layout . ':' . $id]) && is_array($headers[$layout . ':' . $id]) ? $headers[$layout . ':' . $id] : array(), array('view_mode' => $layout, 'card_style' => $id, 'slider_autoplay' => false, 'anonym_avatar' => !empty($_POST['anonym']))));
            }
        }
        $html = $this->renders($sets, true, $sample);
        $result = array();
        foreach ($sets as $i => $set) {
            $result[$set['view_mode']][] = $html[$i];
        }
        $post_id = isset($_POST['post_id']) ? (int) $_POST['post_id'] : 0;
        $result['badge'] = $this->badge->preview_data($this->pids(), $post_id);
        $result['sample'] = $sample;
        wp_send_json(array('status' => 'success', 'result' => $result));
    }

    // Step 2: the widget being set up.
    public function preview() {
        $this->guard();
        $raw = isset($_POST['options']) ? json_decode(wp_unslash($_POST['options']), true) : null;
        $html = $this->renders(array(self::options(is_array($raw) ? $raw : array())), false, $sample);
        wp_send_json(array('status' => 'success', 'result' => array('html' => $html[0], 'sample' => $sample)));
    }

    private function renders($sets, $gallery, &$sample) {
        $pids = $this->pids();
        $post_id = isset($_POST['post_id']) ? (int) $_POST['post_id'] : 0;
        $sample = empty($pids);
        // The gallery's sets differ in their headers: each starts from the defaults, not from the first set's options.
        $data = $this->core->get_data($this->theme_content($pids, $gallery ? array() : $sets[0], $sample ? null : $this->existing($post_id, self::LAYOUTS), $this->langs()), false);
        if ($sample) {
            $data['businesses'] = array(self::sample_biz());
            $data['reviews'] = self::sample_reviews();
        }
        $view = new View();
        $html = array();
        foreach ($sets as $i => $set) {
            $options = clone $data['options'];
            foreach ($set as $key => $value) $options->$key = $value;
            // The sample has no profile photos, and the letters look closer to real ones than the gray guest.
            if ($sample) $options->anonym_avatar = true;
            // A gallery grid pages after its first two rows, and only the page knows how many cards a row holds (admin-themes.js).
            if ($gallery && $options->view_mode === 'grid') $options->pagination = 2;
            $bizs = $options->header_hide_social ? array() : $data['businesses'];
            $reviews = $gallery ? array_slice($data['reviews'], 0, self::GALLERY) : $data['reviews'];
            // Real reviews are shortened by Core when they are read; the sample is made here.
            if ($sample && !empty($options->short_last_name)) {
                $reviews = array_map(function($review) {
                    $review = clone $review;
                    $review->author_name = $this->core->get_short_name($review->author_name);
                    return $review;
                }, $reviews);
            }
            $html[] = $view->render('grwt-' . $i, $bizs, $reviews, $options);
        }
        return $html;
    }

    private static function sample_biz() {
        return (object) array('id' => 'sample', 'name' => 'Your business', 'url' => '', 'photo' => GRW_GOOGLE_BIZ, 'address' => '',
                              'rating' => '4.8', 'review_count' => 1284, 'provider' => 'google');
    }

    private static function sample_reviews() {
        $now = time();
        $rows = array(
            array(5, 'Emma Johnson', 'Friendly staff and really quick service. They explained every step, the price was exactly as quoted and the result is even better than we hoped.', 3),
            array(5, 'Liam Carter', 'Everything was clean and well organized.', 6),
            array(4, 'Sofia Martinez', 'Great value, we will definitely come back. The only wait was at the front desk on a busy Saturday, but the team kept us informed the whole time and offered coffee while we waited.', 9),
            array(5, 'Noah Williams', 'The team went out of their way to help us with a last-minute change. Highly recommended!', 14),
            array(5, 'Olivia Brown', 'Best experience we have had in a long time. Thank you!', 21),
            array(5, 'Lucas Schneider', 'Professional, punctual and kind. Booking online took a minute, and the reminders made it easy not to forget the appointment.', 30),
            array(5, 'Mia Fischer', 'Clear advice, fair prices and a result that speaks for itself. We have already recommended them to our friends.', 35),
            array(4, 'Arjun Patel', 'Good service overall. Parking was a little tricky, but the staff helped us find a spot.', 41),
            array(5, 'Chloe Dubois', 'Warm welcome, spotless place and everything was ready on time.', 48),
            array(5, 'Mateo Rossi', 'Fast, friendly and reliable. This was our third visit and it has never disappointed. The small details make the difference here, from the follow-up call to the tidy invoice.', 56),
        );
        $reviews = array();
        foreach ($rows as $i => $r) {
            $reviews[] = (object) array('id' => 'sample' . $i, 'hide' => '', 'biz_id' => 'sample', 'biz_url' => '', 'rating' => $r[0], 'text' => $r[2],
                                        'lang' => 'en', 'author_avatar' => '', 'author_url' => '', 'author_name' => $r[1], 'time' => $now - $r[3] * 86400,
                                        'images' => null, 'reply' => null, 'reply_time' => null, 'url' => null, 'provider' => 'google');
        }
        return $reviews;
    }

    public function save() {
        $this->guard();

        $pids = $this->pids();
        if (empty($pids)) {
            wp_send_json(array('status' => 'failed', 'result' => array('error_message' => 'Connect a place to publish the widget.')));
        }

        $post_id = isset($_POST['post_id']) ? (int) $_POST['post_id'] : 0;
        $title = isset($_POST['title']) ? sanitize_text_field(wp_unslash($_POST['title'])) : '';
        $raw = isset($_POST['options']) ? json_decode(wp_unslash($_POST['options']), true) : array();
        $options = self::options(is_array($raw) ? $raw : array());
        $old = $this->existing($post_id, self::LAYOUTS);
        // The page keeps the id of what it published last; a slider picked after a badge is a widget of its own.
        if (!$old) {
            $post_id = 0;
            $title = '';
        }

        $content = $this->theme_content($pids, $options, $old, $this->langs());
        $post_id = $this->save_feed($post_id, $title, $content, $options['view_mode']);

        // The raw title: the page sends it back on the next save, and a texturized one would be stored with its entities.
        wp_send_json(array('status' => 'success', 'result' => array(
            'id'        => $post_id,
            'shortcode' => '[grw id=' . $post_id . ']',
            'title'     => get_post_field('post_title', $post_id, 'raw'),
        )));
    }

    // A new draft page holding only the widget's block; existing pages are left alone.
    public function page() {
        $this->guard();
        $id = isset($_POST['post_id']) ? (int) $_POST['post_id'] : 0;
        // get_feed(0) would answer with the newest widget: WP_Query ignores p=0.
        $feed = $id ? $this->feed_deserializer->get_feed($id) : null;
        if (!$feed) {
            wp_send_json(array('status' => 'failed', 'result' => array('error_message' => 'Save the widget first.')));
        }
        $page_id = wp_insert_post(array(
            'post_type'    => 'page',
            'post_status'  => 'draft',
            'post_title'   => 'Reviews',
            'post_content' => '<!-- wp:widget-google-reviews/reviews {"id":"' . (int) $feed->ID . '"} /-->',
        ), true);
        if (is_wp_error($page_id)) {
            wp_send_json(array('status' => 'failed', 'result' => array('error_message' => $page_id->get_error_message())));
        }
        wp_send_json(array('status' => 'success', 'result' => array(
            'view' => get_preview_post_link($page_id),
            'edit' => get_edit_post_link($page_id, 'raw'),
        )));
    }
}
