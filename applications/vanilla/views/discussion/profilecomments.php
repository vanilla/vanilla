<?php if (!defined('APPLICATION')) exit();
foreach ($this->Data('Comments') as $Comment) {
	$Permalink = '/discussion/comment/'.$Comment->CommentID.'/#Comment_'.$Comment->CommentID;
	$User = UserBuilder($Comment, 'Insert');
	$this->EventArguments['User'] = $User;
?>
<li id="<?php echo 'Comment_'.$Comment->CommentID; ?>" class="Item">
	<?php $this->FireEvent('BeforeItemContent'); ?>
	<div class="ItemContent">
		<div class="Message"><?php
			echo SliceString(Gdn_Format::Text(Gdn_Format::To($Comment->Body, $Comment->Format), FALSE), 250);
		?></div>
		<div class="Meta">
         <span class="MItem"><?php echo T('Comment in', 'in').' '; ?><b><?php echo Anchor(Gdn_Format::Text($Comment->DiscussionName), $Permalink); ?></b></span>
			<span class="MItem"><?php printf(T('Comment by %s'), UserAnchor($User)); ?></span>
			<span class="MItem"><?php echo Anchor(Gdn_Format::Date($Comment->DateInserted), $Permalink); ?></span>
		</div>
	</div>
</li>
<?php
}