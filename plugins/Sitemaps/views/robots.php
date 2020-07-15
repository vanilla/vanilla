<?php if (!defined('APPLICATION')) { exit; }

/* @var \Vanilla\Sitemaps\Robots $robots */
$robots = $this->data('robots');

foreach ($robots->getSitemaps() as $sitemap) {
    echo 'Sitemap: '.url($sitemap, true)."\n";
}

foreach ($robots->getRules() as $rule) {
    echo $rule."\n";
}

