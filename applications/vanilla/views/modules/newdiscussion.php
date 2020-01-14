<?php if (!defined('APPLICATION')) exit();
echo '<div class="BoxButtons BoxNewDiscussion">';

$Css = 'Button Primary Action NewDiscussion BigButton';

foreach ($this->getButtonGroups() as $buttonGroup) {
//    $Css .= (count($buttonGroup) == 1) ? ' BigButton' : '';
    echo buttonGroup($buttonGroup, $Css, $this->DefaultButton, $this->reorder);
}

Gdn::controller()->fireEvent('AfterNewDiscussionButton');

echo '</div>';
