<?php

namespace WP_Rplg_Google_Reviews\Includes;

use WP_Rplg_Google_Reviews\Includes\Core\Core;
use WP_Rplg_Google_Reviews\Includes\Core\Google_Dao;

class Builder_Page {

    private $view;
    private $core;
    private $feed_deserializer;
    private $dao;
    private $places;

    public function __construct(Feed_Deserializer $feed_deserializer, Core $core, View $view, Google_Dao $dao, Plugin_Places $places) {
        $this->feed_deserializer = $feed_deserializer;
        $this->core = $core;
        $this->view = $view;
        $this->dao = $dao;
        $this->places = $places;
    }

    public function register() {
        add_action('grw_admin_page_grw-builder', array($this, 'init'));
    }

    public function init() {
        $feed = null;
        $id = isset($_GET[Post_Types::FEED_POST_TYPE . '_id']) ? (int) $_GET[Post_Types::FEED_POST_TYPE . '_id'] : 0;
        if ($id) {
            $feed = $this->feed_deserializer->get_feed($id);
        } else if (!empty($_GET['grw_place_id'])) {
            $feed = $this->place_feed(sanitize_text_field(wp_unslash($_GET['grw_place_id'])));
        }

        $this->render($feed);
    }

    // An unsaved widget preconnected to a place from the Places page; ID stays empty until the first save.
    private function place_feed($place_id) {
        $place = $this->dao->get_place($place_id);
        if (!$place) {
            return null;
        }
        return (object) array(
            'ID'           => '',
            'post_title'   => $place->name,
            'post_content' => wp_json_encode(array(
                'connections' => array($this->places->connection($place)),
                'options'     => new \stdClass(),
            ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        );
    }

    public function render($feed) {
        global $wp_version;
        if (version_compare($wp_version, '3.5', '>=')) {
            wp_enqueue_media();
        }

        $feed_id = '';
        $feed_post_title = '';
        $feed_content = '';
        $decoded_content = array();
        $feed_inited = false;
        $businesses = null;
        $reviews = null;

        $rate_us = get_option('grw_rate_us');
        $authcode = get_option('grw_auth_code');
        $api_key = get_option('grw_google_api_key');

        if ($feed != null) {
            $feed_id = $feed->ID;
            $feed_post_title = $feed->post_title;
            $feed_content = trim($feed->post_content);

            if (!empty($feed_content)) {
                $tmp = json_decode($feed_content, true);
                if (is_array($tmp)) {
                    $decoded_content = $tmp;
                }
            }

            $data = $this->core->get_reviews($feed, true);
            $businesses = $data['businesses'];
            $reviews = $data['reviews'];
            $options = $data['options'];
            // A widget saved before card styles opens as Classic, which its first save keeps; admin-builder.js carries its old look over.
            if (empty($options->card_style)) $options->card_style = 'c1';
            if (isset($businesses) && count($businesses) || isset($reviews) && count($reviews)) {
                $feed_inited = true;
            }
        }

        ?>
        <div class="grw-builder">
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php?action=' . Post_Types::FEED_POST_TYPE . '_save')); ?>">
                <?php wp_nonce_field('grw_wpnonce', 'grw_nonce'); ?>
                <input type="hidden" id="grw_post_id" name="<?php echo Post_Types::FEED_POST_TYPE; ?>[post_id]" value="<?php echo esc_attr($feed_id); ?>">
                <input type="hidden" id="grw_current_url" name="<?php echo Post_Types::FEED_POST_TYPE; ?>[current_url]" value="<?php echo home_url(esc_url_raw(wp_unslash($_SERVER['REQUEST_URI']))); ?>">
                <div class="grw-builder-workspace">
                    <div class="grw-toolbar">
                        <div class="grw-toolbar-title">
                            <input id="grw_title" class="grw-toolbar-title-input" type="text" name="<?php echo Post_Types::FEED_POST_TYPE; ?>[title]" value="<?php if (!empty($feed_post_title)) echo esc_attr($feed_post_title); ?>" placeholder="Enter a widget name" maxlength="255" autofocus>
                        </div>
                        <div class="grw-toolbar-control">
                            <?php if ($feed_inited && $feed_id !== '') { ?>
                            <label>
                                <span id="grw_sc_msg">Copy Shortcode </span>
                                <input id="grw_sc" type="text" value="[grw id=<?php echo esc_attr($feed_id); ?>]" data-grw-shortcode="[grw id=<?php echo esc_attr($feed_id); ?>]" onclick="this.select(); document.execCommand('copy'); window.grw_sc_msg.innerHTML = 'Shortcode Copied! Paste on page. ';" readonly/>
                            </label>
                            <div class="grw-toolbar-options">
                                <label title="Sometimes, you need to use this shortcode in PHP, for instance in header.php or footer.php files, in this case use this option"><input type="checkbox" onclick="var el = window.grw_sc; if (this.checked) { el.value = '&lt;?php echo do_shortcode( \'' + el.getAttribute('data-grw-shortcode') + '\' ); ?&gt;'; } else { el.value = el.getAttribute('data-grw-shortcode'); } el.select();document.execCommand('copy'); window.grw_sc_msg.innerHTML = 'Shortcode Copied! ';"/>Use in PHP</label>
                            </div>
                            <?php } ?>
                            <button id="grw_save" type="submit" class="button button-primary">Save & Update</button>
                        </div>
                    </div>
                    <div class="grw-builder-preview">
                        <textarea id="grw-builder-connection" name="<?php echo Post_Types::FEED_POST_TYPE; ?>[content]" style="display:none"><?php echo esc_textarea($feed_content); ?></textarea>
                        <div id="grw_collection_preview">
                            <?php
                            if ($feed_inited) {
                                echo $this->view->render($feed_id, $businesses, $reviews, $options, true);
                            } else {
                                ?>To show reviews in this preview, firstly connect it on the right menu (CONNECT GOOGLE) and click
                                '<b>Save & Update</b>' button. Then you can use this created widget on a sidebar or through a shortcode.<?php
                            }
                            ?>
                        </div>
                    </div>
                </div>
                <div id="grw-builder-option" class="grw-builder-options"></div>
            </form>
        </div>

        <?php if (!$rate_us) { ?>
        <div id="grw-rate_us-wrap">
            <div id="grw-rate_us">
                <div class="grw-rate_us-content">
                    <button type="button" class="grw-rate_us-close" aria-label="Close">&times;</button>
                    <div class="grw-rate_us-head">
                        How's RichPlugins so far?
                    </div>
                    <div class="grw-rate_us-body">
                        Rate us clicking on the stars:
                        <span class="rpi-star" data-rating="5" style="--rpi-star-size:26px;--gap:0"><i></i><i></i><i></i><i></i><i></i></span>
                    </div>
                </div>
            </div>
        </div>
        <?php } ?>

        <div id="grw-rate_us-feedback" title="Thanks for your feedback!" style="display:none;">
            <b>Please tell us how we can improve the plugin.</b>
            <p>
                <span class="rpi-star" data-rating="5" style="--rpi-star-size:26px;--gap:0"><i></i><i></i><i></i><i></i><i></i></span>
            </p>
            <p style="font-size:16px;">
                <input type="text" value="<?php global $current_user; echo esc_attr($current_user->user_email); ?>" placeholder="Contact email"/>
            </p>
            <p style="font-size:16px;">
                <textarea autofocus placeholder="Describe your experience and how we can improve that"></textarea>
            </p>
            <button class="grw-rate_us-cancel">Cancel</button><button  class="grw-rate_us-send">Send</button>
        </div>

        <div id="dialog" title="Google API key required" style="display:none;">
            <p style="font-size:16px;">
                This plugin uses our default <b>Google Places API key which is mandatory for retrieving Google reviews</b> through official way approved by Google (without crawling). Requests with our API key are limited per WordPress server for every 3 days, and the limit is exceeded at the moment.
            </p>
            <p style="font-size:16px;">
                To continue working with Google API and daily reviews refreshing, please create your own API key by <a href="<?php echo esc_url(admin_url('admin.php?page=grw-support&grw_tab=fig#fig_api_key')); ?>" target="_blank">this instruction</a> and save it on the settings page of the plugin.
            </p>
            <p style="font-size:16px;">
                Don’t worry, it will be free because Google is currently giving free credit a month and it should be enough to use the plugin for connecting several Google places and daily refresh of reviews.
            </p>
        </div>

        <script>
            jQuery(document).ready(function($) {
                function grw_builder_init_listener(attempts) {
                    if (!window.grw_builder_init) {
                        if (attempts > 0) {
                            setTimeout(function() { grw_builder_init_listener(attempts - 1); }, 200);
                        }
                        return;
                    }
                    grw_builder_init($, {
                        el       : '#grw-builder-option',
                        authcode : <?php echo wp_json_encode((string) $authcode); ?>,
                        cards    : <?php echo wp_json_encode(array('presets' => Card_Presets::PRESETS, 'skin' => Card_Presets::SKIN, 'ink' => Card_Presets::INK, 'shadows' => Card_Presets::SHADOWS, 'vars' => Card_Presets::VARS)); ?>,
                        <?php if (!empty($api_key)) echo 'key: true,'; ?>
                        <?php if (!empty($feed_content)) echo 'conns: ' . wp_json_encode($decoded_content); ?>
                    });
                }
                grw_builder_init_listener(20);
            });
        </script>
        <style>
            .grw-admin .update-nag,
            .grw-admin .wp-heading-inline,
            .grw-admin #posts-filter {
                display: none!important;
            }
        </style>
        <?php
    }
}
