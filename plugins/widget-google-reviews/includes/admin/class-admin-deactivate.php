<?php

namespace WP_Rplg_Google_Reviews\Includes\Admin;

class Admin_Deactivate {

    const REASONS = array(
        'connect'   => 'I could not connect my Google place',
        'reviews'   => 'Reviews are missing or do not update',
        'design'    => 'I need more layout or design options',
        'switch'    => 'I am switching to another plugin',
        'temporary' => 'Temporary, I will activate it again',
    );

    public function register() {
        add_action('admin_footer-plugins.php', array($this, 'render'));
        add_action('wp_ajax_grw_deactivate_feedback', array($this, 'feedback'));
    }

    public function render() {
        ?>
        <div id="grw-deactivate-dialog" title="Before you deactivate Google Reviews" data-plugin="<?php echo esc_attr(plugin_basename(GRW_PLUGIN_FILE)); ?>" hidden>
            <?php wp_nonce_field('grw_wpnonce', 'grw_deactivate_nonce'); ?>
            <p>What did not work for you? One click helps us fix it.</p>
            <?php foreach (self::REASONS as $key => $label) { ?>
            <label class="grw-deactivate-reason"><input type="radio" name="grw_deactivate_reason" value="<?php echo esc_attr($key); ?>"> <?php echo esc_html($label); ?></label>
            <?php } ?>
            <textarea id="grw-deactivate-msg" rows="2" placeholder="Anything else? (optional)"></textarea>
            <p class="description">Only your answer and the plugin version are sent, nothing about your site.</p>
            <p class="grw-deactivate-buttons">
                <button type="button" class="button button-primary grw-deactivate-submit">Submit &amp; Deactivate</button>
                <button type="button" class="button grw-deactivate-skip">Skip &amp; Deactivate</button>
            </p>
        </div>
        <?php
    }

    public function feedback() {
        if (!current_user_can('activate_plugins')) {
            die('The account you\'re logged in to doesn\'t have permission to access this page.');
        }
        check_admin_referer('grw_wpnonce', 'grw_nonce');

        $reason = isset($_POST['reason']) ? sanitize_key(wp_unslash($_POST['reason'])) : '';
        $msg = isset($_POST['msg']) ? sanitize_textarea_field(wp_unslash($_POST['msg'])) : '';
        if (!isset(self::REASONS[$reason]) && $msg === '') {
            wp_send_json(array('status' => 'skipped'));
        }

        wp_remote_post('https://admin.richplugins.com/plugins/feedback', array(
            'timeout'  => 5,
            'blocking' => false,
            'body'    => array(
                'plugin'  => 'grw',
                'version' => GRW_VERSION,
                'event'   => 'deactivate',
                'reason'  => $reason,
                'msg'     => $msg,
            ),
        ));
        wp_send_json(array('status' => 'success'));
    }
}
