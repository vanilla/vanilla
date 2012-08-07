<?php if (!defined('APPLICATION')) exit();
$SearchTerm = GetValue('SearchTerm', $this);
?>
<ul class="DataList SearchResults">
<?php
if (is_array($this->SearchResults) && count($this->SearchResults) > 0) {
	foreach ($this->SearchResults as $Key => $Row) {
		$Row = (object)$Row;
		$this->EventArguments['Row'] = $Row;
?>
	<li class="Item">
		<?php $this->FireEvent('BeforeItemContent'); ?>
		<div class="ItemContent">
			<?php echo Anchor(Gdn_Format::Text($Row->Title), $Row->Url, 'Title'); ?>
			<div class="Message Excerpt"><?php
            if ($SearchTerm)
               echo MarkString($SearchTerm, $Row->Summary);
            else
               echo $Row->Summary;
			?></div>
			<div class="Meta">
				<span class="MItem"><?php echo UserPhoto($Row, array('ImageClass' => 'ProfilePhotoSmall')).' '.UserAnchor($Row); ?></span>
				<span class="MItem"><?php echo Anchor(Gdn_Format::Date($Row->DateInserted, 'html'), $Row->Url); ?></span>
            <?php
            if (isset($Row->CategoryID)) {
               $Category = CategoryModel::Categories($Row->CategoryID);
               if ($Category !== NULL) {
                  $Url = Url('categories/'.$Category['UrlCode']);
                  echo '<span class="MItem"><a class="Category" href="'.$Url.'">'.$Category['Name'].'</a></span>';
               }
            }
            ?>
			</div>
		</div>
	</li>
<?php
	}
}
?>
</ul>