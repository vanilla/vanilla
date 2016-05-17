<?php if (!defined('APPLICATION')) exit(); ?>
    <div class="SearchForm">
        <?php
        $Form = $this->Form;
        echo $Form->open(array('action' => url('/search'), 'method' => 'get')),
        '<div class="SiteSearch InputAndButton">',
        $Form->textBox('Search', array('aria-label' => t('Enter your search term.'), 'title' => t('Enter your search term.') )),
        $Form->button('Search', array('aria-label' => t('Search'), 'Name' => '')),
        '</div>',
        $Form->errors(),
        $Form->close();
        ?>
    </div>
<?php
$ViewLocation = $this->fetchViewLocation('results');
include($ViewLocation);
