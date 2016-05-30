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
            '/vanilla/settings/categories?parent='.urlencode($category['UrlCode'])
        );
    } else {
        echo htmlspecialchars($category['Name']);
    }

    echo "\n$i  <div class=\"options\">";
    writeCategoryOptions($category);
    echo "</div>";

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

function writeCategoryOptions($category) {
    $cdd = new DropdownModule('');

    $cdd->setTrigger(displayAsSymbol($category['DisplayAs']), 'span')
        ->setForceDivider(true);

    $cdd->addGroup('', 'edit')
        ->addLink(t('Edit'), url("/vanilla/settings/editcategory?categoryid={$category['CategoryID']}"), 'edit.edit');

    $cdd->addGroup(t('Display as'), 'displayas');
    $displayasOptions = ['Heading', 'Categories', 'Discussions'];
    foreach ($displayasOptions as $displayAs) {
        $cssClass = strcasecmp($displayAs, $category['DisplayAs']) === 0 ? 'selected': '';

        $icon = displayAsSymbol($displayAs);

        $cdd->addLink(
            t($displayAs),
            '#',
            'displayas.'.strtolower($displayAs),
            'js-displayas '.$cssClass,
            [],
            false,
            ['icon' => $icon, 'attributes' => ['data-displayas' => strtolower($displayAs)]]
        );
    }

    $cdd->addGroup('', 'actions')
        ->addLink(
            t('Add Subcategory'),
            url("/vanilla/settings/addcategory?parent={$category['CategoryID']}"),
            'actions.add'
        );

    $cdd->addGroup('', 'delete')
        ->addLink(
            t('Delete'),
            url("/vanilla/settings/deletecategory?categoryid={$category['CategoryID']}"),
            'delete.delete',
            'Popup'
        );

    echo $cdd->toString();
}