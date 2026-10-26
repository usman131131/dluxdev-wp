<?php

namespace WP_Rplg_Google_Reviews\Includes;

class Feed_Page {

    public function register() {
        add_filter('views_edit-' . Post_Types::FEED_POST_TYPE, array($this, 'render'), 20);
    }

    public function render($views) {
        if (current_user_can('manage_options')) {
            $url = admin_url('admin.php?page=grw-widget' . (Plugin_Badge::is_list() ? '&kind=badge' : ''));
            ?><div class="grw-admin-feeds"><a class="button button-primary" href="<?php echo esc_url($url); ?>"><?php echo esc_html__('Create Widget', 'widget-google-reviews'); ?></a></div><?php
        }
        return $views;
    }
}
