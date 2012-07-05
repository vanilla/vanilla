<?php if (!defined('APPLICATION')) exit();
echo '<div class="BoxButtons BoxNewDiscussion">';

$Text = T('Start a New Discussion');
$Url = '/post/discussion'.(array_key_exists('CategoryID', $Data) ? '/'.$Data['CategoryID'] : '');
$Css = 'Button Action BigButton NewDiscussion';

if (count($this->Buttons) == 0) {
   echo Anchor($Text, $Url, $Css);
} else {
   // Make the core button action be the first item in the button group.
   array_unshift($this->Buttons, array('Text' => $Text, 'Url' => $Url));
   echo ButtonGroup($this->Buttons, $this->CssClass);
}
Gdn::Controller()->FireEvent('AfterNewDiscussionButton');

echo '</div>';