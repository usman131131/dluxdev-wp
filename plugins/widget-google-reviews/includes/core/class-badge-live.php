<?php

namespace WP_Rplg_Google_Reviews\Includes\Core;

// The shape of build() is the contract of badge.js and of the cloud (app/instant.js).
class Badge_Live {

    const MIN_RATING   = 4;
    const MIN_LEN      = 20;
    const MAX_LEN      = 90;
    const MAX_PHRASES  = 24;
    const MAX_RECENT   = 10;

    const TOP_RATED_MIN_RATING = 4.5;
    const TOP_RATED_MIN_TOTAL  = 10;

    const NEGATIVE = '/\b(not|never|worst|bad|terrible|awful|rude|dirty|disappoint\w*|trap|avoid|scam|waste|horrible|poor|slow|overpriced|refund|complain\w*)\b/iu';

    public static function build($reviews) {
        $recent = array();
        $pools = array();

        foreach ($reviews as $review) {
            if (count($recent) < self::MAX_RECENT) {
                $recent[] = array(
                    'r' => (int) $review->rating,
                    'a' => (string) $review->author_name,
                    't' => (int) $review->time,
                );
            }
            if ((int) $review->rating < self::MIN_RATING || empty($review->text)) {
                continue;
            }
            $sentences = self::sentences($review->text);
            if ($sentences) {
                shuffle($sentences);
                $pools[] = array('a' => (string) $review->author_name, 's' => $sentences);
            }
        }

        // One sentence per review per round, so a long review cannot fill the line alone.
        shuffle($pools);
        $phrases = array();
        for ($i = 0; count($phrases) < self::MAX_PHRASES; $i++) {
            $added = false;
            foreach ($pools as $pool) {
                if (!isset($pool['s'][$i])) continue;
                $phrases[] = array('t' => $pool['s'][$i], 'a' => $pool['a']);
                $added = true;
                if (count($phrases) >= self::MAX_PHRASES) break;
            }
            if (!$added) break;
        }

        return array('phrases' => $phrases, 'recent' => $recent);
    }

    public static function sentences($text) {
        $text = wp_strip_all_tags(html_entity_decode((string) $text, ENT_QUOTES, 'UTF-8'));
        // Line breaks are sentence ends too, so only the rest of the whitespace collapses.
        $text = preg_replace('/[^\S\n]+/u', ' ', $text);
        $parts = preg_split('/(?<=[.!?…])\s+|\n+/u', $text);
        $out = array();
        foreach ($parts as $s) {
            $s = trim(preg_replace('/[.!?…]+$/u', '', trim($s)));
            $len = mb_strlen($s);
            if ($len < self::MIN_LEN || $len > self::MAX_LEN) continue;
            if (preg_match(self::NEGATIVE, $s)) continue;
            if (preg_match('/^[\p{Lu}\p{N}\s\p{P}]+$/u', $s) && $len > 30) continue;
            $out[] = $s;
        }
        return $out;
    }

    // Mirrors rpi.Badge.Live.shortName(), so the PHP first frame matches what the script rotates.
    public static function short_name($name) {
        return preg_match('/\S+/u', (string) $name, $m) ? $m[0] : '';
    }

    // Several places roll up the same way the cloud does it: total = sum, rating = count-weighted average.
    public static function summary($businesses) {
        if (count($businesses) < 2) {
            return isset($businesses[0]) ? $businesses[0] : null;
        }
        $first = $businesses[0];
        $total = 0;
        $sum = 0;
        foreach ($businesses as $b) {
            $t = (int) $b->review_count;
            $total += $t;
            $sum += ((float) $b->rating) * $t;
        }
        $rating = $total ? $sum / $total : (float) $first->rating;
        return json_decode(json_encode(array(
            'id'           => 'summary',
            'name'         => $first->name,
            'url'          => $first->url,
            'photo'        => $first->photo,
            'rating'       => number_format($rating, 1, '.', ''),
            'review_count' => $total,
            'provider'     => 'summary',
            'wr'           => 'google:' . $first->id,
        )));
    }
}
