<?php if (!defined('APPLICATION')) exit();
echo '<div class="BoxButtons BoxNewDiscussion">';


echo Anchor(
   T('Start a New Discussion'), 
   '/post/discussion'.(array_key_exists('CategoryID', $Data) ? '/'.$Data['CategoryID'] : ''), 
   'Button BigButton NewDiscussion'
);
Gdn::Controller()->FireEvent('AfterNewDiscussionButton');

echo '</div>';