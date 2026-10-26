<?php

namespace WP_Rplg_Google_Reviews\Includes;

use WP_Rplg_Google_Reviews\Includes\Core\Core;

class Reviews_Ajax {

    private $core;
    private $view;
    private $feed_deserializer;

    public function __construct(Feed_Deserializer $feed_deserializer, Core $core, View $view) {
        $this->feed_deserializer = $feed_deserializer;
        $this->core              = $core;
        $this->view              = $view;
    }

    public function register() {
        add_action('wp_ajax_grw_reviews', array($this, 'reviews'));
        add_action('wp_ajax_nopriv_grw_reviews', array($this, 'reviews'));
    }

    public function reviews() {
        if (get_option('grw_active') === '0') {
            wp_die('', '', 404);
        }
        // WP_Query drops a non-positive p and would answer with an arbitrary feed instead of nothing.
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $feed = $id > 0 ? $this->feed_deserializer->get_feed($id) : null;
        if (!$feed) {
            wp_die('', '', 404);
        }
        $data = $this->core->get_reviews($feed);
        if (!$this->view->badge_popup($data['reviews'], $data['options'])) {
            wp_die('', '', 404);
        }
        $this->view->render_popup($data['reviews'], $data['options']);
        wp_die();
    }
}
