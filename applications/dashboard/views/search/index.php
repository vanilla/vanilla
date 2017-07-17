<?php if (!defined('APPLICATION')) exit(); ?>
    <div class="SearchForm">
        <?php
        $Form = $this->Form;
        echo $Form->open(['action' => url('/search'), 'method' => 'get']),
        '<div class="SiteSearch InputAndButton">',
        $Form->textBox('Search', ['aria-label' => t('Enter your search term.'), 'title' => t('Enter your search term.') ]),
        $Form->button('Search', ['aria-label' => t('Search'), 'Name' => '']),
        '</div>',
        $Form->errors(),
        $Form->close();
        ?>
    </div>
<?php
$ViewLocation = $this->fetchViewLocation('results');
include($ViewLocation);
