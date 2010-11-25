<ul class="DataList SearchResults">
<?php
if (is_array($this->SearchResults) && count($this->SearchResults) > 0) {
	foreach ($this->SearchResults as $Key => $Row) {
		$Row = Gdn_Format::ArrayAsObject($Row);
?>
	<li class="Item">
		<div class="ItemContent">
			<?php echo Anchor(Gdn_Format::Text($Row->Title), $Row->Url, 'Title'); ?>
			<div class="Excerpt"><?php
				echo Anchor(SliceString($Row->Summary, 250), $Row->Url);
			?></div>
			<div class="Meta">
				<span><?php printf(T('Comment by %s'), UserAnchor($Row)); ?></span>
				<span><?php echo Gdn_Format::Date($Row->DateInserted); ?></span>
				<span><?php echo Anchor(T('permalink'), $Row->Url); ?></span>
			</div>
		</div>
	</li>
<?php
	}
}
?>
</ul>