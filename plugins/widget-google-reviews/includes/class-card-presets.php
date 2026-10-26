<?php

namespace WP_Rplg_Google_Reviews\Includes;

// A preset is a Card_Engine layout plus skin values over SKIN; a widget stores the preset id and only what it changed.
class Card_Presets {

    const SKIN = array(
        'bg'          => 'color',
        'bg1'         => '#f4f4f4',
        'bg2'         => '#ffffff',
        'border'      => false,
        'borderColor' => '#dddddd',
        'shadow'      => 'none',
        'radius'      => 8,
        'band'        => '#f1f1f1',
        'divider'     => '#e2e2e2',
        'stars'       => '#fb8e28',
        'starSize'    => 20,
        'name'        => '#154fc1',
        'text'        => '#222222',
        'date'        => '#555555',
        'photo'       => 44,
        'gray'        => false,
        'lines'       => '5',
    );

    const INK = array('name' => '#333333', 'text' => '#555555', 'date' => '#999999', 'stars' => '#f6bb06');

    const SHADOWS = array(
        'none'  => 'none',
        'light' => '0 2px 10px rgba(0, 0, 0, .04)',
        'soft'  => '0 1px 2px rgba(0, 0, 0, .06), 0 2px 8px rgba(0, 0, 0, .08)',
        'grw'   => 'var(--rev-shadow)',
    );

    const PRESETS = array(
        'c1'  => array('name' => 'Classic', 'layout' => 'photo(name,time)|stars,text;logo'),
        'r1'  => array('name' => 'Stars under the name', 'layout' => 'photo(name,stars+time)|text;logo', 'ink' => true,
                       'skin' => array('bg1' => '#ffffff', 'shadow' => 'soft', 'radius' => 16, 'starSize' => 16),
                       'vars' => array('--card-name-size' => '15px')),
        'r9'  => array('name' => 'Soft gray', 'layout' => 'photo(name,stars,time)|text;logo', 'ink' => true,
                       'skin' => array('bg1' => '#f3f3f3', 'shadow' => 'soft', 'radius' => 12, 'name' => '#222222', 'text' => '#444444', 'date' => '#888888')),
        'r6'  => array('name' => 'Date on the right', 'layout' => 'photo(name,stars)+~(logo,time)|text', 'ink' => true,
                       'skin' => array('bg1' => '#e3e3e3', 'radius' => 0)),
        'r11' => array('name' => 'Stars on the right', 'layout' => 'photo{logo}(name)+~+stars|text|time', 'ink' => true,
                       'skin' => array('bg1' => '#ffffff', 'border' => true, 'borderColor' => '#e5e5e5', 'shadow' => 'soft', 'radius' => 4,
                                       'name' => '#222222', 'text' => '#444444', 'date' => '#888888', 'stars' => '#222222', 'starSize' => 14)),
        'r12' => array('name' => 'Date and platform at the bottom', 'layout' => 'photo(name,stars)|text|time+~+platform',
                       'skin' => array('bg1' => '#ffffff', 'border' => true, 'borderColor' => '#ebebeb', 'shadow' => 'light', 'radius' => 10, 'photo' => 38,
                                       'name' => '#6e6e6d', 'text' => '#6e6e6d', 'date' => '#999999', 'stars' => '#fbbc04', 'starSize' => 12),
                       'vars' => array('--card-name-size' => '15px', '--card-text-size' => '14px', '--card-line-height' => '1.45', '--card-time-size' => '12px')),
        'r15' => array('name' => 'No background', 'layout' => 'photo(name,time)|stars,text;logo', 'ink' => true,
                       'skin' => array('bg' => 'none', 'radius' => 0, 'name' => '#222222', 'text' => '#444444', 'date' => '#888888')),
        'c4'  => array('name' => 'Bubble – author above', 'layout' => 'photo(name,time)|{bubble}stars,text;logo'),
        'c3'  => array('name' => 'Bubble – author below', 'layout' => '{bubble}stars,text|photo(name,time);logo'),
        'c5'  => array('name' => 'Centered', 'layout' => '{center}photo{top,logo},name,stars,time|{center}text'),
        'r3'  => array('name' => 'Centered header', 'layout' => '{center}photo,name,stars+time|text;logo', 'ink' => true,
                       'skin' => array('bg1' => '#ffffff', 'border' => true, 'borderColor' => '#d5d5d5', 'radius' => 16)),
        'r7'  => array('name' => 'Photo on the border', 'layout' => '{center}photo{top},name,time,stars|text;logo', 'ink' => true,
                       'skin' => array('bg1' => '#ffffff', 'shadow' => 'soft', 'radius' => 4, 'name' => '#222222')),
        'r5'  => array('name' => 'Gradient and gray photos', 'layout' => '{center}photo,name,stars+time|text;logo', 'ink' => true,
                       'skin' => array('bg' => 'gradient', 'bg1' => '#f3efed', 'bg2' => '#d9d9d9', 'radius' => 16, 'gray' => true)),
        'r8'  => array('name' => 'Black card', 'layout' => '{center}photo,name,stars+time|text;logo',
                       'skin' => array('bg1' => '#000000', 'radius' => 16, 'gray' => true, 'band' => '#1c1c1c', 'divider' => '#333333',
                                       'name' => '#ffffff', 'text' => '#ffffff', 'date' => '#a7a7a7', 'stars' => '#5ce12b')),
        'c2'  => array('name' => 'Author below', 'layout' => 'stars,text|photo(name,time);logo'),
        'r10' => array('name' => 'Stars on top with a line', 'layout' => 'stars|text|{sep}photo(name,time);logo', 'ink' => true,
                       'skin' => array('bg1' => '#ffffff', 'shadow' => 'soft', 'radius' => 16, 'name' => '#222222', 'text' => '#444444')),
        'r13' => array('name' => 'Author and logo at the bottom', 'layout' => 'stars,text|photo(name,time)+~+logo',
                       'skin' => array('bg1' => '#ffffff', 'border' => true, 'borderColor' => '#e8e2dc', 'shadow' => 'soft', 'radius' => 9, 'photo' => 42,
                                       'name' => '#332018', 'text' => '#332018', 'date' => '#83756c', 'stars' => '#fbbc05', 'starSize' => 18),
                       'vars' => array('--card-name-size' => '14px', '--card-line-height' => '1.45', '--card-time-size' => '12px')),
        'r2'  => array('name' => 'Author in a band', 'layout' => 'text|{band}photo(name,stars,-time)+~+logo', 'ink' => true,
                       'skin' => array('bg1' => '#ffffff', 'border' => true, 'borderColor' => '#d5d5d5', 'radius' => 16)),
        'r14' => array('name' => 'Stars on top, author in a band', 'layout' => 'stars+~+time|text|{band}photo(name)+~+logo',
                       'skin' => array('bg1' => '#ffffff', 'border' => true, 'borderColor' => '#d5d5d5', 'radius' => 12, 'photo' => 40,
                                       'text' => '#555555', 'date' => '#999999', 'stars' => '#fbbc04', 'starSize' => 12),
                       'vars' => array('--card-name-size' => '15px')),
        'r4'  => array('name' => 'Stars and date in a band', 'layout' => 'photo(name)+~+logo|text|{band}stars+~+time', 'ink' => true,
                       'skin' => array('bg1' => '#ffffff', 'shadow' => 'soft', 'radius' => 4)),
    );

    // Every variable a preset sets: the builder leaves these out of style_vars, so a preset's values are never saved as the user's.
    const VARS = array('--card-bg', '--card-tail', '--card-br', '--card-br-color', '--card-shadow', '--card-radius', '--card-band-bg', '--card-sep-color',
                       '--card-star-color', '--card-star-size', '--name-color', '--card-color', '--time-color', '--card-img-size', '--img-filter',
                       '--card-text-lines', '--card-name-size', '--card-text-size', '--card-line-height', '--card-time-size');

    const CSS_VARS_MAX = 20000;

    // In the CSS variables field every card variable may start with --card-; these three keep their older names on the page.
    const ALIASES = array('--card-name-color' => '--name-color', '--card-time-color' => '--time-color', '--card-img-filter' => '--img-filter');

    // On save: a known preset, its layout only when it differs and in canonical form, the card variables only as the builder sets them.
    public static function sanitize($options) {
        $preset = isset($options['card_style']) ? self::get($options['card_style']) : null;
        if (isset($options['card_style']) && !$preset) $options['card_style'] = '';
        if (isset($options['card_layout'])) {
            try {
                $layout = $preset && is_string($options['card_layout']) ? Card_Engine::serialize(Card_Engine::parse($options['card_layout'])) : '';
            } catch (\InvalidArgumentException $e) {
                $layout = '';
            }
            $options['card_layout'] = $preset && $layout !== $preset['layout'] ? $layout : '';
        }
        if (isset($options['style_vars']) && !is_string($options['style_vars'])) {
            $options['style_vars'] = '';
        }
        if (!empty($options['style_vars'])) {
            $options['style_vars'] = preg_replace_callback('/(--[\w-]+)\s*:([^;]*);?/', function($m) {
                return in_array($m[1], self::VARS, true) && !self::valid($m[1], trim($m[2])) ? '' : $m[0];
            }, $options['style_vars']);
        }
        if (isset($options['css_vars'])) {
            $text = is_string($options['css_vars']) ? str_replace(array('<', '>'), '', $options['css_vars']) : '';
            // Cut after the last whole declaration: a cut inside a value would put another value on the page.
            if (mb_strlen($text, 'UTF-8') > self::CSS_VARS_MAX) $text = preg_replace('~[^;\n]*$~', '', mb_substr($text, 0, self::CSS_VARS_MAX, 'UTF-8'));
            $options['css_vars'] = $text;
        }
        return $options;
    }

    // The CSS variables field as kept for the page: only "--name: value" declarations with plain values, the rest of the text ignored.
    // !important wins over the preset and the settings, and keeps the builder from reading these back into style_vars.
    public static function css_vars($text) {
        if (!is_string($text) || $text === '') return '';
        $css = '';
        // Saved text is capped in characters already; this bound in bytes only guards older data, and it too ends at a whole declaration.
        if (strlen($text) > 4 * self::CSS_VARS_MAX) $text = preg_replace('~[^;\n]*$~', '', substr($text, 0, 4 * self::CSS_VARS_MAX));
        $text = preg_replace('~/\*.*?(\*/|$)~s', '', $text);
        // A line ending in a comma goes on: a shadow or a font list pasted over several lines.
        $text = preg_replace('~,\s*[\r\n]\s*~', ', ', $text);
        preg_match_all('~(?<![\w-])(--[\w-]{1,100})\s*:([^;{}\r\n]*)~', $text, $m, PREG_SET_ORDER);
        $bg = null;
        $tail = false;
        foreach ($m as $d) {
            $v = trim(preg_replace('~!\s*important\s*$~i', '', trim($d[2])));
            if ($v !== '' && strlen($v) <= 500 && preg_match('~^[\w\s#%.,()/*+-]+$~', $v) && !preg_match('~url\(|expression\(|/\*|,$~i', $v) && self::balanced($v)) {
                $name = isset(self::ALIASES[$d[1]]) ? self::ALIASES[$d[1]] : $d[1];
                $css .= $name . ':' . $v . '!important;';
                if ($name === '--card-bg') $bg = $v;
                if ($name === '--card-tail') $tail = true;
            }
        }
        // As with the builder's background control, the speech bubble's tail takes the background unless the field sets it.
        return $bg !== null && !$tail ? $css . '--card-tail:' . $bg . '!important;' : $css;
    }

    // An unclosed bracket would swallow every declaration after it.
    private static function balanced($v) {
        $depth = 0;
        foreach (str_split($v) as $c) {
            $depth += $c === '(' ? 1 : ($c === ')' ? -1 : 0);
            if ($depth < 0) return false;
        }
        return $depth === 0;
    }

    // Colours keep the alphabet the builder has always saved (#hex, names); the rest are the values of the card controls.
    private static function valid($name, $v) {
        $px = function($min, $max) use ($v) {
            return preg_match('/^(\d+)px$/', $v, $m) && $m[1] >= $min && $m[1] <= $max;
        };
        switch ($name) {
            case '--card-bg':
            case '--card-tail':      return (bool) preg_match('/^([#0-9a-z]+|linear-gradient\(135deg, #[0-9a-f]{6}, #[0-9a-f]{6}\))$/i', $v);
            case '--card-br':        return $v === '1px solid' || $v === 'none';
            case '--card-shadow':    return in_array($v, self::SHADOWS, true);
            case '--card-radius':    return $px(0, 24);
            case '--card-star-size': return $px(12, 28);
            case '--card-img-size':  return $px(32, 64);
            case '--img-filter':     return $v === 'none' || $v === 'grayscale(1)';
            case '--card-text-lines': return (bool) preg_match('/^([1-9]|1[0-9]|20|none)$/', $v);
        }
        return (bool) preg_match('/^[#0-9a-z]+$/i', $v);
    }

    public static function get($id) {
        return is_string($id) && isset(self::PRESETS[$id]) ? self::PRESETS[$id] : null;
    }

    public static function skin($preset) {
        return array_merge(self::SKIN, empty($preset['ink']) ? array() : self::INK, isset($preset['skin']) ? $preset['skin'] : array());
    }

    // The card variables carry brb's and the cloud's names; the star and photo ones are the card's own, apart from the header's.
    public static function vars($s) {
        $bg = $s['bg'] === 'none' ? 'transparent' : ($s['bg'] === 'gradient' ? 'linear-gradient(135deg, ' . $s['bg1'] . ', ' . $s['bg2'] . ')' : $s['bg1']);
        return array(
            '--card-bg'         => $bg,
            // An unfilled tail would show the bubble's border line across its base.
            '--card-tail'       => $s['bg'] === 'none' && $s['border'] ? $s['borderColor'] : $bg,
            '--card-br'         => $s['border'] ? '1px solid' : 'none',
            '--card-br-color'   => $s['borderColor'],
            '--card-shadow'     => isset(self::SHADOWS[$s['shadow']]) ? self::SHADOWS[$s['shadow']] : 'none',
            '--card-radius'     => $s['radius'] . 'px',
            '--card-band-bg'    => $s['band'],
            '--card-sep-color'  => $s['divider'],
            '--card-star-color' => $s['stars'],
            '--card-star-size'  => $s['starSize'] . 'px',
            '--name-color'      => $s['name'],
            '--card-color'      => $s['text'],
            '--time-color'      => $s['date'],
            '--card-img-size'   => $s['photo'] . 'px',
            '--img-filter'      => $s['gray'] ? 'grayscale(1)' : 'none',
            '--card-text-lines' => $s['lines'],
        );
    }

    public static function style($preset) {
        $css = '';
        foreach (self::vars(self::skin($preset)) + (isset($preset['vars']) ? $preset['vars'] : array()) as $k => $v) $css .= $k . ':' . $v . ';';
        return $css;
    }
}
