<?php if (!defined('APPLICATION')) exit();
/**
 * "Table" layout for discussions. Mimics more traditional forum discussion layout.
 */

$Session = Gdn::Session();
include($this->FetchViewLocation('helper_functions', 'discussions', 'vanilla'));

/**
 * Writes a discussion in table row format.
 */
function WriteDiscussionRow($Discussion, &$Sender, &$Session, $Alt2) {
   if (!property_exists($Sender, 'CanEditDiscussions'))
      $Sender->CanEditDiscussions = GetValue('PermsDiscussionsEdit', CategoryModel::Categories($Discussion->CategoryID)) && C('Vanilla.AdminCheckboxes.Use');

   $CssClass = CssClass($Discussion);
   $DiscussionUrl = $Discussion->Url;
   
   if ($Session->UserID)
      $DiscussionUrl .= '#Item_'.($Discussion->CountCommentWatch);
   
   $Sender->EventArguments['DiscussionUrl'] = &$DiscussionUrl;
   $Sender->EventArguments['Discussion'] = &$Discussion;
   $Sender->EventArguments['CssClass'] = &$CssClass;
   
   $First = UserBuilder($Discussion, 'First');
   if ($Discussion->LastUserID)
      $Last = UserBuilder($Discussion, 'Last');
   else {
      $Last = $First;
   }
//   $Sender->EventArguments['FirstUser'] = &$First;
//   $Sender->EventArguments['LastUser'] = &$Last;
//   
//   $Sender->FireEvent('BeforeDiscussionName');
   
   $DiscussionName = $Discussion->Name;
   if ($DiscussionName == '')
      $DiscussionName = T('Blank Discussion Topic');
      
   $Sender->EventArguments['DiscussionName'] = &$DiscussionName;
	$Discussion->CountPages = ceil($Discussion->CountComments / $Sender->CountCommentsPerPage);

   $FirstPageUrl = '/discussion/'.$Discussion->DiscussionID.'/'.Gdn_Format::Url($Discussion->Name);
   $LastPageUrl = $FirstPageUrl . '/p'.$Discussion->CountPages.'/#Comment_'.$Discussion->LastCommentID;
	
	$Discussion->CountReplies = $Discussion->CountComments - 1;

?>
<tr class="<?php echo $CssClass; ?>">
	<td class="DiscussionName">
		<div class="Wrap">
			<?php
			echo Anchor($DiscussionName, $DiscussionUrl, 'Title');
			WriteMiniPager($Discussion);
			echo NewComments($Discussion);
			$Sender->FireEvent('AfterDiscussionTitle');
			// Other stuff that was in the standard view that you may want to display:
         echo '<div class="Meta">';
			WriteTags($Discussion);
         echo '</div>';
			
//			if ($Source = GetValue('Source', $Discussion))
//				echo ' '.sprintf(T('via %s'), T($Source.' Source', $Source));
//	
//			if (C('Vanilla.Categories.Use') && $Discussion->CategoryUrlCode != '')
//				echo Wrap(Anchor($Discussion->Category, '/categories/'.rawurlencode($Discussion->CategoryUrlCode)), 'span', array('class' => 'MItem Category'));
			?>
		</div>
	</td>
	<td class="BlockColumn FirstUser">
		<div class="Block Wrap">
			<?php
				echo UserPhoto($First, 'PhotoLink');
				echo UserAnchor($First, 'UserLink BlockTitle');
            echo '<div class="Meta">';
				echo Anchor(Gdn_Format::Date($Discussion->FirstDate, 'html'), $FirstPageUrl, 'CommentDate MItem');
            echo '</div>';
			?>
		</div>
   </td>
	<td class="BlockColumn LastUser">
		<div class="Block Wrap">
			<?php
			if ($Last) {
				echo UserPhoto($Last, 'PhotoLink');
				echo UserAnchor($Last, 'UserLink BlockTitle');
            echo '<div class="Meta">';
				echo Anchor(Gdn_Format::Date($Discussion->LastDate, 'html'), $LastPageUrl, 'CommentDate MItem');
            echo '</div>';
			} else {
				echo '&nbsp;';
			}
			?>
		</div>
	</td>
	<td class="BigCount CountComments">
		<?php
		// Exact Number
		// echo number_format($Discussion->CountComments);
		
		// Round Number
		echo Gdn_Format::BigNumber($Discussion->CountReplies, 'html');
		?>
	</td>
	<td class="BigCount CountViews">
		<?php
		// Exact Number
		// echo number_format($Discussion->CountViews);
		
		// Round Number
		echo Gdn_Format::BigNumber($Discussion->CountViews, 'html');
		?>
	</td>
	<td class="Opts">
		<div class="Wrap">
			<?php WriteOptions($Discussion, $Sender, $Session); ?>
		</div>
	</td>
</tr>
<?php
}

/**
 * Render the page.
 */

echo '<h1 class="HomepageTitle">'.$this->Data('Title').'</h1>';
if ($this->DiscussionData->NumRows() > 0 || (isset($this->AnnounceData) && is_object($this->AnnounceData) && $this->AnnounceData->NumRows() > 0)) {
?>
<table class="DataTable DiscussionsTable">
	<thead>
		<tr>
			<td class="DiscussionName"><?php echo T('Discussion'); ?></td>
			<td class="BlockColumn User FirstUser"><?php echo T('Started By'); ?></td>
			<td class="BlockColumn User LastUser"><?php echo T('Most Recent'); ?></td>
			<td class="BigCount CountReplies"><?php echo T('Replies'); ?></td>
			<td class="BigCount CountViews"><?php echo T('Views'); ?></td>
			<td class="Opts"><?php WriteCheckController(); ?></td>
		</tr>
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
<?php
   $PagerOptions = array('RecordCount' => $this->Data('CountDiscussions'), 'CurrentRecords' => $this->Data('Discussions')->NumRows());
   if ($this->Data('_PagerUrl')) {
      $PagerOptions['Url'] = $this->Data('_PagerUrl');
   }
   echo PagerModule::Write($PagerOptions);
} else {
   ?>
   <div class="Empty"><?php echo T('No discussions were found.'); ?></div>
   <?php
}
