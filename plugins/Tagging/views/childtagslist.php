<?php

// Grab data
$child_tags = $this->data('ChildTags');

// Build HTML list.
$html_li_build = '';
$tpl_ul = '<ul class="tagging-child-tags">%s</ul>';
$tpl_li = '<li class="tagging-%s"><a href="%s" title="%s">%s</a><span class="count-discussions" title="Total discussions">%u</span></li>';

foreach ($child_tags as $child_tag) {
    $html_li_build .= sprintf(
    // Template
        $tpl_li,
        // Variables
        $child_tag['Name'],
        TagUrl($child_tag),
        $child_tag['FullName'],
        $child_tag['FullName'],
        $child_tag['CountDiscussions']
    );
}

// Output HTML
printf($tpl_ul, $html_li_build);
