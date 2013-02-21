<?php if (!defined('APPLICATION')) exit();
/**
 * "Table" layout for discussions. Mimics more traditional forum discussion layout.
 */

$Session = Gdn::Session();
include_once $this->FetchViewLocation('helper_functions', 'discussions', 'vanilla');
include_once $this->FetchViewLocation('table_functions', 'discussions', 'vanilla');

/**
 * Render the page.
 */

$PagerOptions = array('Wrapper' => '<div %1$s>%2$s</div>', 'RecordCount' => $this->Data('CountDiscussions'), 'CurrentRecords' => $this->Data('Discussions')->NumRows());
if ($this->Data('_PagerUrl')) {
   $PagerOptions['Url'] = $this->Data('_PagerUrl');
}

echo '<h1 class="H HomepageTitle">'.$this->Data('Title').'</h1>';

if ($Description = $this->Data('_Description')) {
   echo '<div class="P PageDescription">';
   echo $this->Data('_Description', '&#160;');
   echo '</div>';
}

include $this->FetchViewLocation('Subtree', 'Categories', 'Vanilla');

echo '<div class="PageControls Top">';
   PagerModule::Write($PagerOptions);
   echo Gdn_Theme::Module('NewDiscussionModule', $this->Data('_NewDiscussionProperties', array('CssClass' => 'Button Action Primary')));
echo '</div>';

if ($this->DiscussionData->NumRows() > 0 || (isset($this->AnnounceData) && is_object($this->AnnounceData) && $this->AnnounceData->NumRows() > 0)) {
?>
<div class="DataTableWrap">
<table class="DataTable DiscussionsTable">
	<thead>
		<?php
      WriteDiscussionHeading();
      ?>
	</thead>
	<tbody>
   <?php
		$Alt = '';
		if (property_exists($this, 'AnnounceData') && is_object($this->AnnounceData)) {
			foreach ($this->AnnounceData->Result() as $Discussion) {
				$Alt = $Alt == ' Alt' ? '' : ' Alt';
				WriteDiscussionRow($Discussion, $this, $Session, $Alt);
			}
		}
		
		$Alt = '';
		foreach ($this->DiscussionData->Result() as $Discussion) {
			$Alt = $Alt == ' Alt' ? '' : ' Alt';
			WriteDiscussionRow($Discussion, $this, $Session, $Alt);
		}	
	?>
	</tbody>
</table>
</div>
<?php

   echo '<div class="PageControls Bottom">';
      PagerModule::Write($PagerOptions);
      echo Gdn_Theme::Module('NewDiscussionModule', $this->Data('_NewDiscussionProperties', array('CssClass' => 'Button Action Primary')) );
   echo '</div>';
   
} else {
   ?>
   <div class="Empty"><?php echo T('No discussions were found.'); ?></div>
   <?php
}
