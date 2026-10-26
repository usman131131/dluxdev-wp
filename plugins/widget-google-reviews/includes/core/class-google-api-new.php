<?php

namespace WP_Rplg_Google_Reviews\Includes\Core;

class Google_Api_New {

    const FIELDS = ['id', 'displayName', 'photos', 'googleMapsUri', 'websiteUri', 'formattedAddress', 'rating', 'userRatingCount', 'reviews'];

    const PLACE_FIELDS = ['id', 'displayName', 'photos', 'googleMapsUri', 'websiteUri', 'rating', 'userRatingCount', 'addressComponents'];

    const ISOTIME_9D_REGEXP = '/\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.(\d{9})Z/';

    private $dao;
    private $helper;

    public function __construct(Google_Dao $dao, Connect_Helper $helper) {
        $this->dao = $dao;
        $this->helper = $helper;
    }

    public function connect($pid, $lang, $key, $local_img = false) {
        $url = $this->url($pid, $key, self::FIELDS, $lang);
        $result = $this->call($url, $key, $local_img);

        return $result;
    }

    public function place($pid, $lang, $key) {
        $url = $this->url($pid, $key, self::PLACE_FIELDS, $lang);
        $result = $this->call($url, $key, false, false);

        return $result;
    }

    private function call($url, $key, $local_img = false, $db_save = true) {
        $res = wp_remote_get($url);
        $body = wp_remote_retrieve_body($res);
        $json = json_decode($body);

        if ($json && isset($json->rating)) {
            $photo = $this->photo($json, $key);
            $json->business_photo = $photo;

            if ($db_save) {
                $old_place = $this->convert($json);
                $this->dao->save($old_place, $local_img);
            }

            $result = array(
                'id'                 => $json->id,
                'name'               => $json->displayName->text,
                'rating'             => $json->rating,
                'user_ratings_total' => $json->userRatingCount,
                'photo'              => empty($json->business_photo) ? GRW_GOOGLE_BIZ : $json->business_photo,
                'reviews'            => isset($json->reviews) ? $json->reviews : null
            );
            if (isset($json->addressComponents)) {
                foreach ($json->addressComponents as $component) {
                    if (isset($component->types) && in_array('country', $component->types) && isset($component->shortText)) {
                        $result['country'] = strtoupper($component->shortText);
                        break;
                    }
                }
            }
            $status = 'success';
        } else {
            if (isset($json->error)) {
                $result = array('error_message' => $json->error);
            } else {
                $result = array('error_message' => 'The place you are trying to connect to does not have a rating yet.');
            }
            $error_status = isset($json->error->status) ? $json->error->status : '';
            $error_reason = isset($json->error->details[0]->reason) ? $json->error->details[0]->reason : '';
            // A wrong key and a wrong place ID both answer INVALID_ARGUMENT, only the key must stop the cron
            $result['denied'] = $error_reason == 'API_KEY_INVALID' || in_array($error_status, array('PERMISSION_DENIED', 'UNAUTHENTICATED', 'RESOURCE_EXHAUSTED'), true);
            $status = 'failed';
        }

        return compact('status', 'result');
    }

    private function url($pid, $key, $fields = [], $lang = '') {
        $url = GRW_GOOGLE_PLACE_API_NEW . $pid . '?fields=' . implode(',', $fields) . '&key=' . $key;
        if (!empty($lang)) {
            $url = $url . '&languageCode=' . $lang;
        }
        return $url;
    }

    private function photo($json, $key) {
        if (isset($json->photos) && count($json->photos) > 0) {
            $url = add_query_arg(
                array(
                    'maxWidthPx'  => '300',
                    'maxHeightPx' => '300',
                    'key' => $key
                ),
                'https://places.googleapis.com/v1/' . $json->photos[0]->name . '/media'
            );
            $res = $this->helper->upload_image($url, $json->id);
            return $res === $url ? null : $res;
        }
        return null;
    }

    private function convert($new_place) {
        $old_place = [
            'place_id'           => $new_place->id,
            'rating'             => $new_place->rating,
            'user_ratings_total' => isset($new_place->userRatingCount)  ? $new_place->userRatingCount  : 0,
            'name'               => $new_place->displayName->text,
            'photo'              => isset($new_place->business_photo)   ? $new_place->business_photo   : null,
            'business_photo'     => isset($new_place->business_photo)   ? $new_place->business_photo   : null,
            'url'                => isset($new_place->googleMapsUri)    ? $new_place->googleMapsUri    : null,
            'website'            => isset($new_place->websiteUri)       ? $new_place->websiteUri       : null,
            'formatted_address'  => isset($new_place->formattedAddress) ? $new_place->formattedAddress : null,
            'icon'               => null
        ];

        if (isset($new_place->reviews) && count($new_place->reviews) > 0) {
            $old_reviews = [];
            foreach ($new_place->reviews as $review) {
                array_push($old_reviews, [
                    'rating'            => $review->rating,
                    'text'              => isset($review->text) ? $review->text->text : null,
                    'time'              => $this->get_time($review),
                    'language'          => $this->get_lang($review),
                    'author_name'       => $this->get_author_name($review),
                    'author_url'        => $this->get_author_url($review),
                    'profile_photo_url' => $this->get_author_img($review)
                ]);
            }
            $old_place['reviews'] = $old_reviews;
        }

        return json_decode(json_encode($old_place));
    }

    // Special case for long (with 9 digits after T, for instance 2018-12-16T22:06:12.830950891Z) dates, unsupported in PHP < 8
    private function get_time($review) {
        $publishTime = $review->publishTime;
        $matches = array();
        preg_match(self::ISOTIME_9D_REGEXP, $publishTime, $matches, PREG_OFFSET_CAPTURE);
        if (count($matches) > 1) {
            $publishTime = str_replace($matches[1][0], substr($matches[1][0], 0, 6), $publishTime);
        }
        return date('U', strtotime($publishTime));
    }

    private function get_lang($review) {
        if (isset($review->text) && isset($review->text->languageCode)) {
            return ($review->text->languageCode == 'en-US' ? 'en' : $review->text->languageCode);
        } else {
            return null;
        }
    }

    private function get_author_name($review) {
        return isset($review->authorAttribution) && strlen($review->authorAttribution->displayName) > 0 ? $review->authorAttribution->displayName : null;
    }

    private function get_author_url($review) {
        return isset($review->authorAttribution) && strlen($review->authorAttribution->uri) > 0 ? $review->authorAttribution->uri : null;
    }

    private function get_author_img($review) {
        return isset($review->authorAttribution) && isset($review->authorAttribution->photoUri) ? $review->authorAttribution->photoUri : null;
    }
}