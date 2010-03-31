<ul class="DataList SearchResults">
<?php
if (method_exists($this->SearchResults, 'NumRows') && $this->SearchResults->NumRows() > 0) {
	foreach ($this->SearchResults->ResultObject() as $Row) {
?>
	<li class="Row">
		<ul>
			<li class="Title">
				<strong><?php echo Anchor(Format::Text($Row->Title), $Row->Url); ?></strong>
				<?php echo Anchor(Format::Text(SliceString($Row->Summary, 250)), $Row->Url); ?>
			</li>
			<li class="Meta">
				<span><?php printf(T('Comment by %s'), UserAnchor($Row)); ?></span>
				<span><?php echo Format::Date($Row->DateInserted); ?></span>
				<span><?php echo Anchor(T('permalink'), $Row->Url); ?></span>
			</li>
		</ul>
	</li>
<?php
	}
}
?>
</ul>