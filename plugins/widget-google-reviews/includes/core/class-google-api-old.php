<?php

namespace WP_Rplg_Google_Reviews\Includes\Core;

class Google_Api_Old {

    private $dao;
    private $helper;

    public function __construct(Google_Dao $dao, Connect_Helper $helper) {
        $this->dao = $dao;
        $this->helper = $helper;
    }

    public function connect($pid, $lang, $key, $local_img = false) {
        // First call with default sorting method
        $url = $this->url($pid, $lang, $key);
        $result = $this->call($url, $key, $local_img);
        if ($result['status'] == 'failed') {
            return $result;
        }

        // Second call with default 'newest' sorting method
        $result = $this->refresh($pid, $lang, $key, $local_img);

        return $result;
    }

    public function refresh($pid, $lang, $key, $local_img = false) {
        // Second call with default 'newest' sorting method
        $url = $this->url($pid, $lang, $key, 'newest');
        $result = $this->call($url, $key, $local_img);

        return $result;
    }

    public function place($pid, $lang, $key) {
        $url = $this->url($pid, $lang, $key);
        $result = $this->call($url, $key, false, false);

        return $result;
    }

    public function post($url, $args = [], $key = null, $local_img = false, $db_save = true) {
        $res = wp_remote_post($url, ['body' => $args]);
        return $this->save($res, $key, $local_img, $db_save);
    }

    public function call($url, $key = null, $local_img = false, $db_save = true) {
        $res = wp_remote_get($url);
        return $this->save($res, $key, $local_img, $db_save);
    }

    private function save($res, $key = null, $local_img = false, $db_save = true) {
        $body = wp_remote_retrieve_body($res);
        $json = json_decode($body);

        if ($json && isset($json->result) && isset($json->result->rating)) {
            $data = $json->result;

            if ($key) {
                $photo = $this->photo($data, $key);
                $data->business_photo = $photo;
            }

            if ($db_save) {
                $this->dao->save($data, $local_img);
            }

            $business_photo = empty($data->business_photo) ? GRW_GOOGLE_BIZ : $data->business_photo;

            $result = array(
                'id'                 => $data->place_id,
                'name'               => $data->name,
                'rating'             => $data->rating,
                'user_ratings_total' => $data->user_ratings_total,
                'photo'              => empty($data->photo) ? $business_photo : $data->photo,
                'business_photo'     => $business_photo,
                'reviews'            => isset($data->reviews) ? $data->reviews : null
            );
            if (isset($json->credits)) {
                $result['credits'] = $json->credits;
            }
            if (!empty($json->cached)) {
                $result['cached'] = true;
            }
            if (isset($data->map_url) && strlen($data->map_url) > 0) {
                $result['map_url'] = $data->map_url;
            }
            if (isset($data->address_components)) {
                foreach ($data->address_components as $component) {
                    if (isset($component->types) && in_array('country', $component->types) && isset($component->short_name)) {
                        $result['country'] = strtoupper($component->short_name);
                        break;
                    }
                }
            }
            $status = 'success';
        } else {
            if (isset($json->error_message)) {
                $result = array('error_message' => $json->error_message);
            } else {
                $result = array('error_message' => 'The place you are trying to connect to does not have a rating yet.');
            }
            $result['denied'] = isset($json->status) && $json->status == 'REQUEST_DENIED';
            $status = 'failed';
        }
        return compact('status', 'result');
    }

    private function url($pid, $lang, $key = '', $reviews_sort = '') {
        $url = GRW_GOOGLE_PLACE_API . 'details/json?placeid=' . $pid . '&key=' . $key;
        if (!empty($lang)) {
            $url = $url . '&language=' . $lang;
        }
        if (strlen($reviews_sort) > 0) {
            $url = $url . '&reviews_sort=' . $reviews_sort;
        }
        return $url;
    }

    private function photo($json, $key) {
        if (isset($json->photos) && count($json->photos) > 0) {
            $url = add_query_arg(
                array(
                    'maxwidth'       => '300',
                    'maxheight'      => '300',
                    'photoreference' => $json->photos[0]->photo_reference,
                    'key'            => $key
                ),
                'https://maps.googleapis.com/maps/api/place/photo'
            );
            $res = $this->helper->upload_image($url, $json->place_id);
            return $res === $url ? null : $res;
        }
        return null;
    }
}