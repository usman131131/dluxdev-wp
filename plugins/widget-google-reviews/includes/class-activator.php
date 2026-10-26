<?php

namespace WP_Rplg_Google_Reviews\Includes;

use WP_Rplg_Google_Reviews\Includes\Core\Database;

class Activator {

    private $database;

    public function __construct(Database $database) {
        $this->database = $database;
    }

    public function options() {
        return array(
            'grw_version',
            'grw_active',
            'grw_async_css',
            'grw_freq_revs_upd',
            'grw_google_api_key',
            'grw_gpa_old',
            'grw_google_api_error',
            'grw_language',
            'grw_activation_time',
            'grw_auth_code',
            'grw_debug_mode',
            'grw_feed_ids',
            'grw_do_activation',
            'grw_demand_assets',
            'grw_revupd_cron',
            'grw_revupd_cron_timeout',
            'grw_revupd_cron_log',
            'grw_save_log',
            'grw_debug_refresh',
            'grw_notice_msg',
            'grw_notice_type',
            'grw_rev_notice_hide',
            'grw_last_error',
            'rplg_rev_notice_show',
            'grw_rate_us',
            'grw_inlinecss',
            'grw_rucss_safelist',
            'grw_badge_id',
        );
    }

    public function register() {
        add_action('init', array($this, 'init'));
    }

    public function init() {
        if (is_admin()) {
            $this->check_version();
        }
    }

    public function check_version() {
        if (version_compare(get_option('grw_version'), GRW_VERSION, '<')) {
            $this->activate();
        }
    }

    /**
	 * Activates the plugin on a multisite
	 */
    public function activate() {
        if ($this->is_multisite_flag()) {
            $this->activate_multisite();
        } else {
            $this->activate_single_site();
        }
    }

    private function activate_multisite() {
        global $wpdb;

        $site_ids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");

        foreach($site_ids as $site_id) {
            try {
                switch_to_blog($site_id);
                $this->activate_single_site();
            } finally {
                restore_current_blog();
            }
        }
    }

    private function activate_single_site() {
        $current_version     = GRW_VERSION;
        $last_active_version = get_option('grw_version');

        if (empty($last_active_version)) {
            $this->first_install();
            update_option('grw_version', $current_version);
            update_option('grw_auth_code', $this->random_str(127));
            update_option('grw_revupd_cron', '1');
        } elseif ($last_active_version !== $current_version) {
            $this->exist_install($last_active_version);
            update_option('grw_version', $current_version);
            add_option('grw_revupd_cron', '1');
        }
    }

    private function first_install() {
        $this->database->create();

        add_option('grw_active', '1');
        add_option('grw_google_api_key', '');
    }

    private function exist_install($last_active_version) {
        $this->update_db($last_active_version);
    }

    public function update_db($last_active_version) {
        global $wpdb;

        $biz = $wpdb->prefix . Database::BUSINESS_TABLE;
        $rev = $wpdb->prefix . Database::REVIEW_TABLE;
        $text = $wpdb->prefix . Database::TEXT_TABLE;

        $biz_colms = $wpdb->get_col("SHOW COLUMNS FROM {$biz}", 0);
        $rev_colms = $wpdb->get_col("SHOW COLUMNS FROM {$rev}", 0);

        $this->add_columns($biz, $biz_colms, array(
            'review_count' => 'INTEGER',
            'map_url'      => 'VARCHAR(512)'
        ));
        $this->add_columns($rev, $rev_colms, array(
            'hide'       => "VARCHAR(1) DEFAULT '' NOT NULL",
            'images'     => 'TEXT',
            'reply'      => 'TEXT',
            'reply_time' => 'INTEGER',
            'url'        => 'VARCHAR(255)',
            'provider'   => 'VARCHAR(32)'
        ));

        if (version_compare($last_active_version, '2.0.1', '<')) {
            $grw_auth_code = get_option('grw_auth_code');
            if (empty($grw_auth_code)) {
                update_option('grw_auth_code', $this->random_str(127));
            }
        }

        if (version_compare($last_active_version, '2.1.5', '<')) {
            if (!function_exists('drop_index') || !function_exists('dbDelta')) {
                require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            }
            if (!function_exists('maybe_drop_column')) {
                // Define 'maybe_drop_column' function without including install-helper.php due to error:
                // Fatal error: Cannot redeclare maybe_create_table()
                // (previously declared in /wp-admin/install-helper.php:52) in /wp-admin/includes/upgrade.php on line 1616
                function maybe_drop_column($table_name, $column_name, $drop_ddl) {
                    global $wpdb;
                    foreach ($wpdb->get_col( "DESC $table_name", 0) as $column) {
                        if ($column === $column_name) {
                            $wpdb->query($drop_ddl);
                            foreach ($wpdb->get_col("DESC $table_name", 0) as $column) {
                                if ($column === $column_name) {
                                    return false;
                                }
                            }
                        }
                    }
                    return true;
                }
            }
            if (drop_index($rev, 'grp_google_review_hash')) {
                maybe_drop_column($rev, "hash", "ALTER TABLE {$rev} DROP COLUMN hash");
            }
            $sql = "CREATE TABLE IF NOT EXISTS " . $wpdb->prefix . "grp_google_stats (".
                "id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,".
                "google_place_id BIGINT(20) UNSIGNED NOT NULL,".
                "time INTEGER NOT NULL,".
                "rating DOUBLE PRECISION,".
                "review_count INTEGER,".
                "PRIMARY KEY (`id`),".
                "INDEX grp_google_place_id (`google_place_id`)".
                ") " . $wpdb->get_charset_collate() . ";";
            dbDelta($sql);
        }

        if (version_compare($last_active_version, '4.8.1', '<')) {
            $wpdb->query("ALTER TABLE {$rev} MODIFY COLUMN `author_url` VARCHAR(127)");
        }

        if (version_compare($last_active_version, '6.9.4.1', '<')) {
            update_option('grw_debug_mode', '0');
        }

        if (version_compare($last_active_version, '6.9.5', '<')) {
            delete_option('grw_inlinecss_off');
        }

        if (version_compare($last_active_version, '6.9.7', '<')) {

            $wpdb->query("UPDATE {$rev} SET provider = 'google' WHERE provider IS NULL OR provider = ''");

            if (!in_array('review_id', $rev_colms, true)) {
                $wpdb->query("ALTER TABLE {$rev} ADD review_id VARCHAR(80)");
            }

            // Set review_id
            $wpdb->query(
                "UPDATE {$rev} r
                 JOIN {$biz} b ON b.id = r.google_place_id
                 SET r.review_id = MD5(CONCAT(
                     r.provider, ':', b.place_id, ':',
                     COALESCE(
                         NULLIF(r.author_url, ''),
                         CONCAT(COALESCE(NULLIF(r.author_name, ''), ''), ':', r.time)
                     )
                 ))
                 WHERE r.review_id IS NULL OR r.review_id = ''"
            );

            // Delete orphan reviews
            $orphan_revs = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$rev} r LEFT JOIN {$biz} b ON b.id = r.google_place_id WHERE b.id IS NULL");
            if ($orphan_revs > 0) {
                $wpdb->query("DELETE r FROM {$rev} r LEFT JOIN {$biz} b ON b.id = r.google_place_id WHERE b.id IS NULL");
            }

            $wpdb->query("ALTER TABLE {$rev} MODIFY COLUMN `review_id` VARCHAR(80) NOT NULL");

            $this->database->create_text_table();
            $wpdb->query("ALTER TABLE " . $wpdb->prefix . Database::TEXT_TABLE . " MODIFY COLUMN `review_id` VARCHAR(80) NOT NULL");
            $this->database->migrate_review_texts();
        }

        if (version_compare($last_active_version, '6.9.8', '<')) {
            $rev_col = $wpdb->get_row("SHOW FULL COLUMNS FROM {$rev} WHERE Field = 'review_id'");
            $txt_col = $wpdb->get_row("SHOW FULL COLUMNS FROM {$text} WHERE Field = 'review_id'");

            if ($rev_col && $txt_col && !empty($rev_col->Collation)) {
                $collation = $rev_col->Collation;

                if ($txt_col->Collation !== $collation) {
                    $valid_collation = $wpdb->get_var($wpdb->prepare("SHOW COLLATION WHERE `Collation` = %s", $collation));
                    if ($valid_collation) {
                        $wpdb->query("ALTER TABLE {$text} MODIFY review_id VARCHAR(80) COLLATE {$collation} NOT NULL");
                    }
                }
            }
        }

        if (!$wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $text))) {
            $this->database->create_text_table();
            $this->database->migrate_review_texts();
        }

        if (!empty($wpdb->last_error)) {
            update_option('grw_last_error', time() . ': ' . $wpdb->last_error);
        }
    }

    private function add_columns($table, $existing, $columns) {
        global $wpdb;

        foreach ($columns as $name => $type) {
            if (!in_array($name, $existing, true)) {
                $wpdb->query("ALTER TABLE {$table} ADD {$name} {$type}");
            }
        }
    }

    /**
	 * Creates the plugin database on a multisite
	 */
    public function create_db() {
        if ($this->is_multisite_flag()) {
            $this->create_db_multisite();
        } else {
            $this->create_db_single_site();
        }
    }

    private function create_db_multisite() {
        global $wpdb;

        $site_ids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");

        foreach($site_ids as $site_id) {
            try {
                switch_to_blog($site_id);
                $this->create_db_single_site();
            } finally {
                restore_current_blog();
            }
        }
    }

    private function create_db_single_site() {
        $this->database->create();
    }

    /**
	 * Drops the plugin database on a multisite
	 */
    public function drop_db($multisite = false) {
        if ($multisite && $this->is_multisite_flag()) {
            $this->drop_db_multisite();
        } else {
            $this->drop_db_single_site();
        }
    }

    private function drop_db_multisite() {
        global $wpdb;

        $site_ids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");

        foreach($site_ids as $site_id) {
            try {
                switch_to_blog($site_id);
                $this->drop_db_single_site();
            } finally {
                restore_current_blog();
            }
        }
    }

    private function drop_db_single_site() {
        $this->database->drop();
    }

    /**
	 * Delete all options of the plugin on a multisite
	 */
    public function delete_all_options($multisite = false) {
        if ($multisite && $this->is_multisite_flag()) {
            $this->delete_all_options_multisite();
        } else {
            $this->delete_all_options_single_site();
        }
    }

    private function delete_all_options_multisite() {
        global $wpdb;

        $site_ids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");

        foreach($site_ids as $site_id) {
            try {
                switch_to_blog($site_id);
                $this->delete_all_options_single_site();
            } finally {
                restore_current_blog();
            }
        }
    }

    private function delete_all_options_single_site() {
        foreach ($this->options() as $opt) {
            delete_option($opt);
        }
    }

    /**
	 * Delete all feeds of the plugin on a multisite
	 */
    public function delete_all_feeds($multisite = false) {
        if ($multisite && $this->is_multisite_flag()) {
            $this->delete_all_feeds_multisite();
        } else {
            $this->delete_all_feeds_single_site();
        }
    }

    private function delete_all_feeds_multisite() {
        global $wpdb;

        $site_ids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");

        foreach($site_ids as $site_id) {
            try {
                switch_to_blog($site_id);
                $this->delete_all_feeds_single_site();
            } finally {
                restore_current_blog();
            }
        }
    }

    private function delete_all_feeds_single_site() {
        $args = array(
            'post_type'      => Post_Types::FEED_POST_TYPE,
            'post_status'    => array('any', 'trash'),
            'posts_per_page' => -1,
            'fields'         => 'ids',
        );

        $query = new \WP_Query($args);
        $grw_posts = $query->posts;

        if (!empty($grw_posts)) {
            foreach ($grw_posts as $grw_post) {
                wp_delete_post($grw_post, true);
            }
        }
    }

    public function delete_duplicates() {
        global $wpdb;

        $last_error = array();

        $rev = $wpdb->prefix . Database::REVIEW_TABLE;

        // Delete duplicte reviews. No placeholders here, so no prepare().
        $wpdb->query(
            "DELETE `" . $rev . "` " .
            "FROM `" . $rev . "` INNER JOIN (" .
                "SELECT MIN(id) AS last_id, author_url, language FROM `" . $rev . "` " .
                "WHERE author_url IN (" .
                    "SELECT author_url FROM `" . $rev . "` " .
                    "GROUP BY author_url, language HAVING COUNT(*) > 1" .
                ") GROUP BY author_url, language" .
            ") DUPLIC ON DUPLIC.author_url = `" . $rev . "`.author_url " .
                    "AND DUPLIC.language = `" . $rev . "`.language " .
            "WHERE `" . $rev . "`.ID > DUPLIC.last_id;");

        if (!empty($wpdb->last_error)) {
            array_push($last_error, $wpdb->last_error);
        }

        // Database::create() already declares an index of this name, so adding it
        // again is a guaranteed "Duplicate key name" error on an existing install.
        $index = $wpdb->get_results($wpdb->prepare(
            "SHOW INDEX FROM `" . $rev . "` WHERE Key_name = %s", 'grp_author_url_lang'
        ));

        if (empty($index)) {
            $wpdb->query("ALTER TABLE `" . $rev . "` ADD UNIQUE `grp_author_url_lang` (`author_url`, `language`)");

            if (!empty($wpdb->last_error)) {
                array_push($last_error, $wpdb->last_error);
            }
        }

        if (count($last_error) > 0) {
            update_option('grw_last_error', time() . ': ' . implode("; ", $last_error));
        }
    }

    private function is_multisite_flag() {
        if (!is_multisite()) return false;
        return get_option('grw_is_multisite') == '1';
    }

    private function random_str($len) {
        $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charlen = strlen($chars);
        $randstr = '';
        for ($i = 0; $i < $len; $i++) {
            $randstr .= $chars[rand(0, $charlen - 1)];
        }
        return $randstr;
    }

}
