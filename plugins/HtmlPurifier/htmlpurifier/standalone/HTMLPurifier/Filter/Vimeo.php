<?php

class HTMLPurifier_Filter_Vimeo extends HTMLPurifier_Filter
{
    
    public $name = 'Vimeo';
    
    public function preFilter($html, $config, $context) {
		$pre_regex = '#<object[^>]+>.+?'.'http://vimeo.com/moogaloop.swf\?clip_id=([0-9\-_]+).+?</object>#s';
		$pre_replace = '<span class="vimeo-embed">\1</span>';
		return preg_replace($pre_regex, $pre_replace, $html);
    }
    
    public function postFilter($html, $config, $context) {
        $post_regex = '#<span class="vimeo-embed">([A-Za-z0-9\-_]+)</span>#';
        $post_replace = '<object width="400" height="225" '.
            'data="http://vimeo.com/moogaloop.swf?clip_id=\1">'.
            '<param name="movie" value="http://vimeo.com/moogaloop.swf?clip_id=\1"></param>'.
            '<param name="wmode" value="transparent"></param>'.
            '<!--[if IE]>'.
            '<embed src="http://vimeo.com/moogaloop.swf?clip_id=\1"'.
            'type="application/x-shockwave-flash"'.
            'wmode="transparent" width="400" height="225" />'.
            '<![endif]-->'.
            '</object>';
        return preg_replace($post_regex, $post_replace, $html);
    }
}