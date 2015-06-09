<?php if (!defined('APPLICATION')) exit(); ?>
    <div class="SearchForm">
        <?php
        $Form = $this->Form;
        $Form->InputPrefix = '';
        echo $Form->open(array('action' => url('/search'), 'method' => 'get')),
        '<div class="SiteSearch InputAndButton">',
        $Form->textBox('Search'),
        $Form->button('Search', array('Name' => '')),
        '</div>',
        $Form->errors(),
        $Form->close();
        ?>
    </div>
<?php
$ViewLocation = $this->fetchViewLocation('results');
include($ViewLocation);
