<?php
$grw_revupd_cron = get_option('grw_revupd_cron') == '1';
$grw_debug_mode = get_option('grw_debug_mode') == '1';
$dm_disp = $grw_debug_mode ? 'flex' : 'none';
?>
<form method="post" action="<?php echo esc_url(admin_url('admin-post.php?action=grw_settings_save&grw_tab=advance')); ?>">

    <div class="grw-field">
        <div class="grw-field-label">
            <label>Reviews update schedule is <b><?php echo $grw_revupd_cron ? 'enabled' : 'disabled' ?></b></label>
        </div>
        <div class="wp-review-field-option">
            <?php wp_nonce_field('grw-wpnonce_revupd_cron', 'grw-form_nonce_revupd_cron'); ?>
            <input type="submit" value="<?php echo $grw_revupd_cron ? 'Disable' : 'Enable'; ?>" name="revupd_cron" class="button" />
        </div>
    </div>

    <div class="grw-field">
        <div class="grw-field-label">
            <label>Re-create the database tables of the plugin (service option)</label>
        </div>
        <div class="wp-review-field-option">
            <?php wp_nonce_field('grw-wpnonce_create_db', 'grw-form_nonce_create_db'); ?>
            <input type="submit" value="Re-create Database" name="create_db" onclick="return confirm('Are you sure you want to re-create database tables?')" class="button" />
        </div>
    </div>
    <div class="grw-field">
        <div class="grw-field-label">
            <label><b>Please be careful</b>: this removes all settings, reviews, feeds and install the plugin from scratch</label>
        </div>
        <div class="wp-review-field-option">
            <?php wp_nonce_field('grw-wpnonce_create_db', 'grw-form_nonce_create_db'); ?>
            <input type="submit" value="Install from scratch" name="install" onclick="return confirm('It will delete all current feeds, are you sure you want to install from scratch the plugin?')" class="button" />
            <p><label><input type="checkbox" id="install_multisite" name="install_multisite"> For all sites (WP Multisite)</label></p>
        </div>
    </div>
    <div class="grw-field">
        <div class="grw-field-label">
            <label><b>Please be careful</b>: this removes all plugin-specific settings, reviews and feeds</label>
        </div>
        <div class="wp-review-field-option">
            <?php wp_nonce_field('grw-wpnonce_reset_all', 'grw-form_nonce_reset_all'); ?>
            <input type="submit" value="Delete All Data" name="reset_all" onclick="return confirm('Are you sure you want to reset all plugin data including feeds?')" class="button" />
            <p><label><input type="checkbox" id="reset_all_multisite" name="reset_all_multisite"> For all sites (WP Multisite)</label></p>
        </div>
    </div>
    <div class="grw-field">
        <div class="grw-field-label">
            <label>Remove duplicate reviews</label>
        </div>
        <div class="wp-review-field-option">
            <?php wp_nonce_field('grw-wpnonce_del_dup_revs', 'grw-form_nonce_del_dup_revs'); ?>
            <input type="submit" name="del_dup_revs" value="Remove duplicate reviews" class="button" />
        </div>
    </div>
    <div class="grw-field">
        <div class="grw-field-label">
            <label>Execute db update manually</label>
        </div>
        <div class="wp-review-field-option">
            <?php wp_nonce_field('grw-wpnonce_update_db', 'grw-form_nonce_update_db'); ?>
            <input type="submit" name="update_db" class="button" />
            <input type="text" name="update_db_ver" style="width:94px;height:22px" placeholder="version" />
        </div>
    </div>
    <div id="debug_info" class="grw-field">
        <div class="grw-field-label">
            <label>Debug information</label>
        </div>
        <div class="wp-review-field-option">
            <input type="button" value="Copy Debug Information" name="reset_all" onclick="window.grw_debug_info.select();document.execCommand('copy');window.grw_debug_msg.innerHTML='Debug Information copied, please paste it to your email to support';" class="button" />
            <textarea id="grw_debug_info" style="display:block;width:30em;height:250px;margin-top:10px" onclick="window.grw_debug_info.select();document.execCommand('copy');window.grw_debug_msg.innerHTML='Debug Information copied, please paste it to your email to support';" readonly><?php ob_start(); $this->debug_info->render(); echo esc_textarea(preg_replace('/([?&]key=)[\w-]+/', '$1***', ob_get_clean())); ?></textarea>
            <p id="grw_debug_msg"></p>
        </div>
    </div>
    <div class="grw-field" style="display:<?php echo $dm_disp; ?>">
        <div class="grw-field-label">
            <label>Debug mode is currently <b><?php echo $grw_debug_mode ? 'enabled' : 'disabled' ?></b></label>
        </div>
        <div class="wp-review-field-option">
            <?php wp_nonce_field('grw-wpnonce_debug_mode', 'grw-form_nonce_debug_mode'); ?>
            <input type="submit" name="debug_mode" class="button" value="<?php echo $grw_debug_mode ? 'Disable' : 'Enable'; ?>" />
        </div>
    </div>
</form>