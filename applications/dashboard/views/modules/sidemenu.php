<?php if (!defined('APPLICATION')) exit();
$this->checkPermissions();

// Loop through all the groups.
foreach ($this->Items as $Item) {
    // Output the group.
    echo '<div class="Box Group '.getValue('class', $Item['Attributes']).'">';
    if ($Item['Text'] != '')
        echo "\n", '<h4>',
        isset($Item['Url']) ? anchor($Item['Text'], $Item['Url']) : $Item['Text'],
        '</h4>';

    if (count($Item['Links'])) {
        echo "\n", '<ul class="PanelInfo">';

        // Loop through all the links in the group.
        foreach ($Item['Links'] as $Link) {
            echo "\n  <li".attribute($Link['Attributes']).">",
            anchor($Link['Text'], $Link['Url']),
            '</li>';
        }

        echo "\n", '</ul>';
    }

    echo "\n", '</div>';
}
