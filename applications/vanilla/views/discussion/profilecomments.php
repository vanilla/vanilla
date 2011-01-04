<?php if (!defined('APPLICATION')) exit();
foreach ($this->CommentData->Result() as $Comment) {
	$Permalink = '/discussion/comment/'.$Comment->CommentID.'/#Comment_'.$Comment->CommentID;
	$User = UserBuilder($Comment, 'Insert');
	$this->EventArguments['User'] = $User;
?>
<li class="Item">
	<?php $this->FireEvent('BeforeItemContent'); ?>
	<div class="ItemContent">
		<?php echo Anchor(Gdn_Format::Text($Comment->DiscussionName), $Permalink, 'Title'); ?>
		<div class="Excerpt"><?php
			echo Anchor(SliceString(Gdn_Format::Text($Comment->Body, FALSE), 250), $Permalink);
		?></div>
		<div class="Meta">
			<span><?php printf(T('Comment by %s'), UserAnchor($User)); ?></span>
			<span><?php echo Gdn_Format::Date($Comment->DateInserted); ?></span>
			<span><?php echo Anchor(T('permalink'), $Permalink); ?></span>
		</div>
	</div>
</li>
<?php
}