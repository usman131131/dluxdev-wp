<?php

namespace WP_Rplg_Google_Reviews\Includes\Core;

class Google_Utils {

    private $api_old;
    private $api_new;
    private $dao;
    private $legacy_denied = '';

    public function __construct(Google_Api_Old $api_old, Google_Api_New $api_new, Google_Dao $dao) {
        $this->api_old = $api_old;
        $this->api_new = $api_new;
        $this->dao = $dao;
    }

    public function refresh($args) {
        $pid = $args[0];
        $lang = $args[1];
        $local_img = isset($args[2]) ? $args[2] : 'false';

        // A place deleted on the Places page must not be re-created by a widget that still refers to it.
        if (!$this->dao->get_place($pid)) {
            return;
        }

        $key = get_option('grw_google_api_key');
        if ($key && strlen($key) > 0) {
            return $this->call('refresh', $pid, $lang, $key, $local_img);
        }
    }

    // Only the legacy API sorts reviews by newest, and Google projects created after March 2025 cannot enable it
    public function call($method, $pid, $lang, $key, $local_img = false) {
        if (!$this->legacy_denied) {
            $response = $this->api_old->$method($pid, $lang, $key, $local_img);
            $this->legacy_denied = empty($response['result']['denied']) ? '' : $response['result']['error_message'];
        }
        if ($this->legacy_denied) {
            $response = $method == 'place' ? $this->api_new->place($pid, $lang, $key) : $this->api_new->connect($pid, $lang, $key, $local_img);
        }
        if (!empty($response['result']['denied'])) {
            $error = $response['result']['error_message'];
            update_option('grw_google_api_error', 'Places API: ' . $this->legacy_denied . "\nPlaces API (New): " . (isset($error->message) ? $error->message : $error));
        } elseif ($response['status'] == 'success' && get_option('grw_google_api_error')) {
            delete_option('grw_google_api_error');
        }
        return $response;
    }

}
