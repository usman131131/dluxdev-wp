<?php

namespace WP_Rplg_Google_Reviews\Includes;

use WP_Rplg_Google_Reviews\Includes\Core\Google_Utils;

class Reviews_Cron {

    private $google_utils;
    private $feed_deserializer;

    public function __construct(Google_Utils $google_utils, Feed_Deserializer $feed_deserializer) {
        $this->google_utils = $google_utils;
        $this->feed_deserializer = $feed_deserializer;
    }

    public function register() {
        add_filter('cron_schedules', array($this, 'add_schedules'));
        add_action('grw_revupd_schedule', array($this, 'update_schedule'));

        $this->activate();
    }

    public function activate() {
        $revupd_cron = get_option('grw_revupd_cron');
        $next_cron_run = wp_next_scheduled('grw_revupd_schedule');

        if ($revupd_cron !== '0' && !$next_cron_run) {
            $start_at = rand(0, 60 * 60 * 12);
            $freq = get_option('grw_freq_revs_upd', 'daily');
            wp_schedule_event(time(), $freq, 'grw_revupd_schedule');
        }
    }

    public function add_schedules($schedules = array()) {
		$schedules['weekly'] = array(
			'interval' => 60 * 60 * 24 * 7,
			'display'  => 'Once Weekly'
		);
        $schedules['fortnightly'] = array(
			'interval' => 60 * 60 * 24 * 14,
			'display'  => 'Every two weeks'
		);
        $schedules['monthly'] = array(
			'interval' => 60 * 60 * 24 * 30,
			'display'  => 'Once Monthly'
		);
		return $schedules;
	}

    public function update_schedule() {

        $start_time       = floor(microtime(true) * 1000);
        $end_time         = 0;
        $feed_updated_ids = array();
        $denied           = '';

        $feed_conn_cache  = array();

        $feed_ids         = get_option('grw_feed_ids');
        $ids              = explode(",", $feed_ids);
        $ids_count        = count($ids);

        if ($ids_count > 0) {

            // Trying to walk by all reviews feed to update these
            for ($i = 0; $i < $ids_count; $i++) {

                // Get next reviews feed ID
                $id = array_shift($ids);

                // Get next reviews feed
                $feed = $this->feed_deserializer->get_feed($id);
                if ($feed != false && strlen($feed->post_content) > 0) {

                    // Parse json content to obtain reviews connectors
                    $json = json_decode($feed->post_content);
                    if ($json->connections && count($json->connections) > 0) {

                        // Loop by all connectors without repeatable
                        foreach ($json->connections as $conn) {

                            if (isset($conn->refresh) && $conn->refresh) {

                                // Create pair 'place_id:lang' to update it
                                $arg_local_img = isset($conn->local_img) ? $conn->local_img : 'false';
                                $arg_lang = isset($conn->lang) ? $conn->lang : '';

                                $args = array($conn->id, $arg_lang, $arg_local_img);
                                $args_key = $conn->id . ':' . $arg_lang;

                                // If not met, update
                                if (!in_array($args_key, $feed_conn_cache)) {
                                    $res = $this->google_utils->refresh($args);
                                    array_push($feed_conn_cache, $args_key);

                                    if (!empty($res['result']['denied'])) {
                                        $denied = get_option('grw_google_api_error');
                                        array_unshift($ids, $id);
                                        break 2;
                                    }
                                }
                            }
                        }

                        // Put reviews feed ID to log
                        array_push($feed_updated_ids, $id);

                        // Put reviews feed ID to the end of feed_ids option
                        array_push($ids, $id);

                        // Clear feed cache
                        delete_transient('grw_feed_' . GRW_VERSION . '_' . $id . '_reviews', false);

                        // Check execution time
                        $end_time = floor(microtime(true) * 1000) - $start_time;
                        if ($end_time > 5000) {
                            break;
                        }

                    }
                }
            }

            update_option('grw_feed_ids', implode(",", $ids));

        }

        // Log information
        $now = floor(microtime(true) * 1000);
        if ($denied) {
            $last_error = get_option('grw_last_error', '');
            update_option('grw_last_error', ($last_error ? $last_error : $now . ': ') . 'Reviews cron stopped, Google ' . str_replace("\n", ', ', $denied) . '; ');
        }
        update_option('grw_revupd_cron_log', 'Executed at ' . $now . ' in ' . $end_time . 'ms for feeds: ' . implode(", ", $feed_updated_ids));
    }

    public function deactivate() {
        $next_scheduled = wp_next_scheduled('grw_revupd_schedule');
        if ($next_scheduled) {
            wp_unschedule_event($next_scheduled, 'grw_revupd_schedule');
            update_option('grw_revupd_cron_timeout', '');
        }
    }
}