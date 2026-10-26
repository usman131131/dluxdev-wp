<?php

namespace WP_Rplg_Google_Reviews\Includes;

use WP_Rplg_Google_Reviews\Includes\Core\Google_Dao;

class Plugin_Overview {

    private $dao;

    public function __construct(Google_Dao $dao) {
        $this->dao = $dao;
    }

    public function register() {
        add_action('admin_init', array($this, 'first_run'));
        add_action('grw_admin_page_grw', array($this, 'render'));
    }

    // Until the first widget an administrator starts on Create Widget; a site with businesses keeps Overview and Places, one with an API key Settings.
    public function first_run() {
        global $pagenow, $plugin_page;
        $page = $pagenow === 'edit.php' && ($_GET['post_type'] ?? '') === Post_Types::FEED_POST_TYPE ? 'widgets' : $plugin_page;
        if (!in_array($page, array('grw', 'grw-places', 'grw-settings', 'widgets'), true) || count($_GET) > 1
            || !current_user_can('manage_options') || self::has_widgets()) return;
        if ($page === 'grw-settings' ? get_option('grw_google_api_key') : $page !== 'widgets' && $this->dao->get_places()) return;
        wp_safe_redirect(admin_url('admin.php?page=grw-widget'));
        exit;
    }

    // In any status: a site with only trashed widgets keeps its list and its Trash.
    private static function has_widgets() {
        $counts = (array) wp_count_posts(Post_Types::FEED_POST_TYPE);
        unset($counts['auto-draft']);
        return array_sum($counts) > 0;
    }

    public function render() {
        wp_nonce_field('grw_wpnonce', 'grw_nonce');
        wp_enqueue_script('grw-admin-apexcharts-js');
        ?>

        <div class="grw-page-title">
            Overview
        </div>

        <div class="grw-overview-workspace">

            <?php if (!self::has_widgets() && current_user_can('manage_options')) { ?>
            <p><a class="button button-primary" href="<?php echo esc_url(admin_url('admin.php?page=grw-widget')); ?>"><?php echo esc_html__('Create Widget', 'widget-google-reviews'); ?></a></p>
            <?php } ?>

            <div class="grw-overview-places">
                <select id="grw-overview-months">
                    <option value="6" selected>6 months</option>
                    <option value="12">a year</option>
                    <option value="24">2 years</option>
                    <option value="36">3 years</option>
                    <option value="60">5 years</option>
                </select>
                <select id="grw-overview-places" style="max-width:256px"></select>
            </div>

            <div class="grw-flex-row">
                <div class="grw-flex-col6">

                    <div class="grw-card">
                        <div class="grw-card-body">
                            <div id="chart"></div>
                        </div>
                    </div>

                </div>

                <div class="grw-flex-col4">

                    <div class="grw-flex-row">

                        <div class="grw-flex-col">
                            <div class="grw-card">
                                <div class="grw-card-header">Rating</div>
                                <div class="grw-card-body grw-card-fh">
                                    <div id="grw-overview-rating" class="rpi-flx rpi-col8">
                                        <img src="<?php echo GRW_ASSETS_URL; ?>img/dots-spinner.svg">
                                    </div>
                                </div>
                            </div><br>
                            <div class="grw-card">
                                <div class="grw-card-header">Usage Stats</div>
                                <div class="grw-card-body grw-card-fh">
                                    <div id="grw-overview-stats" class="rpi-flx rpi-col8">
                                        <img src="<?php echo GRW_ASSETS_URL; ?>img/dots-spinner.svg">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="grw-flex-col">
                            <div class="grw-card">
                                <div class="grw-card-header">Latest Reviews</div>
                                <div class="grw-card-body grw-card-fh" style="padding-top:0">
                                    <div id="grw-overview-reviews">
                                        <img src="<?php echo GRW_ASSETS_URL; ?>img/dots-spinner.svg">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

            </div>

        </div>

        <?php
    }
}
