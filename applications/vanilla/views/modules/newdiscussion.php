<?php if (!defined('APPLICATION')) exit();
echo '<div class="BoxButtons BoxNewDiscussion">';

$Css = 'Button Primary Action NewDiscussion BigButton';
$default = c('Vanilla.DefaultNewButton');

foreach ($this->getButtonGroups() as $buttonGroup) {
//    $Css .= (count($buttonGroup) == 1) ? ' BigButton' : '';
    echo buttonGroup($buttonGroup, $Css, $default, $this->reorder);
}

Gdn::controller()->fireEvent('AfterNewDiscussionButton');

echo '</div>';
