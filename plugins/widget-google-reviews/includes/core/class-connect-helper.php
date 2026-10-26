<?php
namespace WP_Rplg_Google_Reviews\Includes\Core;

class Connect_Helper {

    public function upload_image($url, $name) {

        $res = wp_remote_get($url, array('timeout' => 8));
        if (is_wp_error($res)) {
            $this->log_error('remote_get: ' . $res->get_error_message());
            return $url;
        }

        $code = wp_remote_retrieve_response_code($res);
        if ($code !== 200) {
            $this->log_error('remote_get http ' . $code . ' for ' . strtok($url, '?'));
            return $url;
        }

        $bits = wp_remote_retrieve_body($res);
        if (empty($bits)) {
            $this->log_error('remote_get empty body for ' . strtok($url, '?'));
            return $url;
        }

        $upload_dir = wp_upload_dir(null, true);
        if (!empty($upload_dir['error'])) {
            $this->log_error('upload_dir: ' . $upload_dir['error']);
            return $url;
        }

        $filename = $name . '.jpg';
        $full_filepath = trailingslashit($upload_dir['path']) . $filename;
        if (file_exists($full_filepath)) {
            wp_delete_file($full_filepath);
        }

        $upload = wp_upload_bits($filename, null, $bits);
        if (!empty($upload['error'])) {
            $this->log_error('upload_bits: ' . $upload['error']);
            return $url;
        }

        return isset($upload['url']) ? $upload['url'] : $url;
    }

    private function log_error($message) {
        $opt = get_option('grw_last_error', '');
        if (empty($opt)) {
            $opt = floor(microtime(true) * 1000) . ': ';
        }
        update_option('grw_last_error', $opt . $message . '; ');
    }
}