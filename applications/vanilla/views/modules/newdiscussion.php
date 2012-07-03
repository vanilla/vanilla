<?php if (!defined('APPLICATION')) exit();
echo '<div class="BoxButtons BoxNewDiscussion">';

$Text = T('Start a New Discussion');
$Url = '/post/discussion'.(array_key_exists('CategoryID', $Data) ? '/'.$Data['CategoryID'] : '');
$Css = 'Button Action BigButton NewDiscussion';

if (count($this->Buttons) == 0) {
   echo Anchor($Text, $Url, $Css);
} else {
   echo '<div class="ButtonGroup Action Big">';
      echo Anchor($Text, $Url, 'Button');
      echo Anchor(Sprite('SpDropDownHandle'), '#', 'Button Handle');
      echo '<ul class="Dropdown MenuItems">';
         echo Wrap(Anchor($Text, $Url), 'li');
         foreach ($this->Buttons as $Button) {
            echo Wrap(Anchor($Button['Text'], $Button['Url']), 'li');
         }
      echo '</ul>';
   echo '</div>';
}
Gdn::Controller()->FireEvent('AfterNewDiscussionButton');

echo '</div>';