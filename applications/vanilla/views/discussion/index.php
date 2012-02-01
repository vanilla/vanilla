<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session(); 
if (!function_exists('WriteComment'))
   include $this->FetchViewLocation('helper_functions', 'discussion');

// Wrap the discussion related content in a div.
echo '<div class="MessageList Discussion '.CssClass($this->Data('Discussion')).'">';

// Write the page title.
echo '<!-- Page Title -->
<div id="Item_0" class="PageTitle">';

echo '<div class="Options">';

WriteBookmarkLink();
WriteDiscussionOptions();
WriteAdminCheck();

echo '</div>';

echo '<h1>'.$this->Data('Discussion.Name').'</h1>';

echo "</div>\n\n";

// Write the initial discussion.
if ($this->Data('Page') == 1) {
   include $this->FetchViewLocation('discussion', 'discussion');
   echo '</div>'; // close discussion wrap
} else {
   echo '</div>'; // close discussion wrap
}

echo '<div class="CommentsWrap">';

// Write the comments.
$this->Pager->Wrapper = '<span %1$s>%2$s</span>';
echo '<span class="BeforeCommentHeading">';
$this->FireEvent('CommentHeading');
echo $this->Pager->ToString('less');
echo '</span>';

echo '<h2 class="CommentHeading">'.T('Comments').'</h2>';

?>
<ul class="MessageList DataList Comments">
	<?php include $this->FetchViewLocation('comments'); ?>
</ul>
<?php
$this->FireEvent('AfterDiscussion');
if($this->Pager->LastPage()) {
   $LastCommentID = $this->AddDefinition('LastCommentID');
   if(!$LastCommentID || $this->Data['Discussion']->LastCommentID > $LastCommentID)
      $this->AddDefinition('LastCommentID', (int)$this->Data['Discussion']->LastCommentID);
   $this->AddDefinition('Vanilla_Comments_AutoRefresh', Gdn::Config('Vanilla.Comments.AutoRefresh', 0));
}

echo '<div class="P">';
$this->Pager->Wrapper = '<div %1$s>%2$s</div>';
echo $this->Pager->ToString('more');
echo '</div>';
echo '</div>';

WriteCommentForm();
