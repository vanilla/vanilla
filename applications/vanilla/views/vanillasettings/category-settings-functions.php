<?php

function writeCategoryTree($categories, $indent = 0) {
    $i = str_repeat('  ', $indent);

    echo "$i<ol class=\"dd-list tree-list\">\n";

    foreach ($categories as $category) {
        writeCategoryItem($category, $indent + 1);
    }
    echo "$i</ol>\n";
}

function writeCategoryItem($category, $indent = 0) {
    $i = str_repeat('  ', $indent);

    echo "$i<li class=\"dd-item tree-item\" data-id=\"{$category['CategoryID']}\">\n",
        "$i  <div class=\"dd-handle tree-handle\">".symbol('handle', t('Drag'))."</div>",
        "<div class=\"dd-content tree-content\">";

    if (in_array($category['DisplayAs'], ['Categories'])) {
        echo anchor(
            htmlspecialchars($category['Name']),
            '/vanilla/settings/categories/'.rawurlencode($category['UrlCode'])
        );
    } else {
        echo htmlspecialchars($category['Name']);
    }

    echo "\n$i  <div class=\"options\">",
        displayAsSymbol($category['DisplayAs']),
        "</div>";

    echo "</div>\n";

    if (!empty($category['Children'])) {
        writeCategoryTree($category['Children'], $indent + 1);
    }

    echo "$i</li>\n";
}

function displayAsSymbol($displayAs) {
    switch (strtolower($displayAs)) {
        case 'heading':
            return symbol('heading');
        case 'categories':
            return symbol('nested');
        case 'discussions':
        default:
            return symbol('discussions');
    }
}

function symbol($name, $alt = '') {
    if (!empty($alt)) {
        $alt = 'alt="'.htmlspecialchars($alt).'" ';
    }

    $r = <<<EOT
<svg {$alt}class="icon icon-16 icon-$name" viewBox="0 0 16 16"><use xlink:href="#$name" /></svg>
EOT;

    return $r;
}