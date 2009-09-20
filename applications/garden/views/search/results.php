<?php if (!defined('APPLICATION')) exit(); ?>
<h1><?php printf(Gdn::Translate("Search results for '%s'"), $this->SearchTerm); ?></h1>
<?php echo $this->Pager->ToString('less'); ?>
<ul class="DataList SearchResults">
<?php
if ($this->SearchResults->NumRows() > 0) {
	foreach ($this->SearchResults->ResultObject() as $Row) {
?>
	<li class="Row">
		<ul>
			<li class="Title">
				<strong><?php echo Anchor(Format::Text($Row->Title), $Row->Url); ?></strong>
				<?php echo Anchor(Format::Text($Row->Summary), $Row->Url); ?>
			</li>
			<li class="Meta">
				<span><?php printf(Gdn::Translate('Comment by %s'), UserAnchor($Row)); ?></span>
				<span><?php echo Format::Date($Row->DateInserted); ?></span>
				<span><?php echo Anchor(Gdn::Translate('permalink'), $Row->Url); ?></span>
			</li>
		</ul>
	</li>
<?php
	}
} else {
?>
	<li><?php echo Gdn::Translate("Your search returned no results."); ?></li>
<?php
}
$this->Pager->Render();