<?php

if (!function_exists('normalizeVietnamese')) {
    function normalizeVietnamese($str){
        $unicode = array(
            'a'=>'á|à|ả|ã|ạ|ă|ắ|ặ|ằ|ẳ|ẵ|â|ấ|ầ|ẩ|ẫ|ậ',
            'd'=>'đ',
            'e'=>'é|è|ẻ|ẽ|ẹ|ê|ế|ề|ể|ễ|ệ',
            'i'=>'í|ì|ỉ|ĩ|ị',
            'o'=>'ó|ò|ỏ|õ|ọ|ô|ố|ồ|ổ|ỗ|ộ|ơ|ớ|ờ|ở|ỡ|ợ',
            'u'=>'ú|ù|ủ|ũ|ụ|ư|ứ|ừ|ử|ữ|ự',
            'y'=>'ý|ỳ|ỷ|ỹ|ỵ',
            'A'=>'Á|À|Ả|Ã|Ạ|Ă|Ắ|Ặ|Ằ|Ẳ|Ẵ|Â|Ấ|Ầ|Ẩ|Ẫ|Ậ',
            'D'=>'Đ',
            'E'=>'É|È|Ẻ|Ẽ|Ẹ|Ê|Ế|Ề|Ể|Ễ|Ệ',
            'I'=>'Í|Ì|Ỉ|Ĩ|Ị',
            'O'=>'Ó|Ò|Ỏ|Õ|Ọ|Ô|Ố|Ồ|Ổ|Ỗ|Ộ|Ơ|Ớ|Ờ|Ở|Ỡ|Ợ',
            'U'=>'Ú|Ù|Ủ|Ũ|Ụ|Ư|Ứ|Ừ|Ử|Ữ|Ự',
            'Y'=>'Ý|Ỳ|Ỷ|Ỹ|Ỵ',
        );
        foreach($unicode as $nonUnicode=>$uni){
            $str = preg_replace("/($uni)/i", $nonUnicode, $str);
        }
        return $str;
    }
}

if (!function_exists('textToTocSlugify')) {
    function textToTocSlugify($str){
        $str = normalizeVietnamese($str);
        $str = strtolower($str);
        $str = preg_replace('/[^a-z0-9]+/i', '-', $str);
        return trim($str, " \t\n\r\0\x0B-");
    }
}


if (!function_exists('generateTocHtml')) {
    /**
     * @param string $text text to index
     * @param string $type html or markdown
     */
    function generateTocHtml($toc) {
        $html = '<ul>';
        if ($toc['children']) {
            foreach ($toc['children'] as $child) {
                $html .= "<li><a href=\"#{$child['id']}\">{$child['text']}</a>";
                $html .= generateTocHtml($child);
                $html .= '</li>';
            }
        }
        $html .= '</ul>';
        return $html;
    }
}


if (!function_exists('textToToc')) {
    /**
     * @param string $text text to index
     * @param string $type html or markdown
     */
    function textToToc($text, $type = 'html') {
        $index = 0;
        $replaced = preg_replace_callback(
            '/\<(h[1-6])([^\>]*)\>(.*?)\<\/\s*\1\s*\>/is',
            function ($match) use (&$index) {
                $index++;
                $origin = $match[0];
                $tag = $match[1];
                $attributes = $match[2];
                $text = $match[3];
                if (preg_match('/id\=(\'|")[^\1]+\1/i', $attributes)) {
                    return $origin;
                }
                $slug = 'section-' . textToTocSlugify($text) . '-' . str_pad($index, 5, '0', STR_PAD_LEFT);
                return "<$tag id=\"$slug\" $attributes>$text</$tag>";
            },
            $text
        );
        $toc = [
            'level' => 0,
            'children' => [],
        ];
        if (preg_match_all('/\<h([1-6])([^\>]*)id\=([\'"])([^\s\3]+)\3([^\>]*)\>(.*?)\<\/\s*h\1\s*\>/is', $replaced, $matches)) {
            $levels = $matches[1];
            $ids = $matches[4];
            $texts = $matches[6];
            $parents = [];
            $parent = &$toc;
            foreach ($levels as $index => $level) {
                $level = (integer) $level;
                while ($level <= $parent['level']) {
                    $parent = &$parents[count($parents) - 1];
                    array_pop($parents);
                }
                $item = [
                    'id' => $ids[$index],
                    'level' => $level,
                    'text' => trim(preg_replace([
                        '/\<(\/)?[^\<\>]+\>/',
                        '/(\r\n|\n|\r)/',
                    ], [
                        '',
                        ' ',
                    ], $texts[$index])),
                    'children' => []
                ];
                $parent['children'][] = &$item;
                $parents[] = &$parent;
                unset($parent);
                $parent = &$item;
                unset($item);
            }
        }


        return [
            'html' => $replaced,
            'toc' => $toc,
        ];
    }
}
