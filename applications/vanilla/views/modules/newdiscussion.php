<?php if (!defined('APPLICATION')) exit();
echo '<div class="BoxButtons BoxNewDiscussion">';

$Css = 'Button Primary Action NewDiscussion';
$Css .= strpos($this->CssClass, 'Big') !== FALSE ? ' BigButton' : '';

foreach ($this->getButtonGroups() as $buttonGroup) {
    echo buttonGroup($buttonGroup, $Css, $this->DefaultButton, $this->reorder)."\n";
}

Gdn::controller()->fireEvent('AfterNewDiscussionButton');

echo '</div>';
