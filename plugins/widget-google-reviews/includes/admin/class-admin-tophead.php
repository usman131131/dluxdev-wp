<?php

namespace WP_Rplg_Google_Reviews\Includes\Admin;

class Admin_Tophead {

    public function register() {
        add_action('wp_after_admin_bar_render', array($this, 'render'));
    }

    public function render() {
        $current_screen = get_current_screen();

        if (empty($current_screen)) {
            return;
        }

        if (Admin_Menu::is_plugin_screen($current_screen->id)) {

            $current_screen->render_screen_meta();

            ?>
            <div class="grw-tophead">
                <div class="grw-tophead-title">
                    <img src="<?php esc_attr_e(GRW_ASSETS_URL . 'img/logo.png') ?>" alt="logo">
                    Google Reviews
                </div>
                <div class="grw-version">
                    <div class="grw-version-free">Free Version: <?php echo GRW_VERSION; ?></div>
                    <div class="grw-version-upgrade">
                        <span class="grw-upgrade-text">Upgrade to business</span>
                        <div id="grw-upgrade-tips">
                            <div class="grw-upgrade-head">Most easiest way to show all G reviews with business version</div>
                            No Place ID, No API key, No Billing needed, only Google My Business (GMB) owner account to show all G reviews with constantly auto synced
                            <a href="https://richplugins.com/business-reviews-bundle-wordpress-plugin?code=SUMR26#pricing" target="_blank">Upgrade today with 45% off!</a>
                        </div>
                    </div>
                </div>
            </div>
            <?php
        }
    }
}
