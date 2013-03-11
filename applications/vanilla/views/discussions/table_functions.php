<?php if (!defined('APPLICATION')) exit();

if (!function_exists('WriteDiscussionHeading')):
   
   function WriteDiscussionHeading() {
   ?>
   <tr>
      <?php echo AdminCheck(NULL, array('<td class="CheckBoxColumn"><div class="Wrap">', '</div></td>')); ?>
      <td class="DiscussionName"><div class="Wrap"><?php echo DiscussionHeading() ?></div></td>
      <td class="BlockColumn BlockColumn-User FirstUser"><div class="Wrap"><?php echo T('Started By'); ?></div></td>
      <td class="BigCount CountReplies"><div class="Wrap"><?php echo T('Replies'); ?></div></td>
      <td class="BigCount CountViews"><div class="Wrap"><?php echo T('Views'); ?></div></td>
      <td class="BlockColumn BlockColumn-User LastUser"><div class="Wrap"><?php echo T('Most Recent Comment', 'Most Recent'); ?></div></td>
   </tr>
   <?php
   }
endif;

if (!function_exists('WriteDiscussionRow')):

/**
 * Writes a discussion in table row format.
 */
function WriteDiscussionRow($Discussion, &$Sender, &$Session, $Alt2) {
   if (!property_exists($Sender, 'CanEditDiscussions'))
      $Sender->CanEditDiscussions = GetValue('PermsDiscussionsEdit', CategoryModel::Categories($Discussion->CategoryID)) && C('Vanilla.AdminCheckboxes.Use');

   $CssClass = CssClass($Discussion);
   $DiscussionUrl = $Discussion->Url;
   
   if ($Session->UserID)
      $DiscussionUrl .= '#latest';
   
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

   $FirstPageUrl = DiscussionUrl($Discussion, 1);
   $LastPageUrl = DiscussionUrl($Discussion, FALSE).'#latest';
?>
<tr id="Discussion_<?php echo $Discussion->DiscussionID; ?>" class="<?php echo $CssClass; ?>">
   <?php echo AdminCheck($Discussion, array('<td class="CheckBoxColumn"><div class="Wrap">', '</div></td>')); ?>
	<td class="DiscussionName">
		<div class="Wrap">
         <span class="Options">
            <?php
            echo OptionsList($Discussion);
            echo BookmarkButton($Discussion);
            ?>
         </span>
			<?php
         
         
			echo Anchor($DiscussionName, $DiscussionUrl, 'Title').' ';
			$Sender->FireEvent('AfterDiscussionTitle');
         
			WriteMiniPager($Discussion);
			echo NewComments($Discussion);
         if ($Sender->Data('_ShowCategoryLink', TRUE))
            echo CategoryLink($Discussion, ' '.T('in').' ');
         
			// Other stuff that was in the standard view that you may want to display:
         echo '<div class="Meta Meta-Discussion">';
			WriteTags($Discussion);
         echo '</div>';
			
//			if ($Source = GetValue('Source', $Discussion))
//				echo ' '.sprintf(T('via %s'), T($Source.' Source', $Source));
//	
			?>
		</div>
	</td>
	<td class="BlockColumn BlockColumn-User FirstUser">
		<div class="Block Wrap">
			<?php
				echo UserPhoto($First, array('Size' => 'Small'));
				echo UserAnchor($First, 'UserLink BlockTitle');
            echo '<div class="Meta">';
				echo Anchor(Gdn_Format::Date($Discussion->FirstDate, 'html'), $FirstPageUrl, 'CommentDate MItem');
            echo '</div>';
			?>
		</div>
   </td>
	<td class="BigCount CountComments">
      <div class="Wrap">
         <?php
         // Exact Number
         // echo number_format($Discussion->CountComments);

         // Round Number
         echo BigPlural($Discussion->CountComments, '%s comment');
         ?>
      </div>
	</td>
	<td class="BigCount CountViews">
      <div class="Wrap">
         <?php
         // Exact Number
         // echo number_format($Discussion->CountViews);

         // Round Number
         echo BigPlural($Discussion->CountViews, '%s view');
         ?>
      </div>
	</td>
	<td class="BlockColumn BlockColumn-User LastUser">
		<div class="Block Wrap">
			<?php
			if ($Last) {
				echo UserPhoto($Last, array('Size' => 'Small'));
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
</tr>
<?php
}

endif;

if (!function_exists('WriteDiscussionTable')) :
   
function WriteDiscussionTable() {
   $c = Gdn::Controller();
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
      $Session = Gdn::Session();
		$Alt = '';
		$Announcements = $c->Data('Announcements');
      if (is_a($Announcements, 'Gdn_DataSet')) {
			foreach ($Announcements->Result() as $Discussion) {
				$Alt = $Alt == ' Alt' ? '' : ' Alt';
				WriteDiscussionRow($Discussion, $c, $Session, $Alt);
			}
		}
		
		$Alt = '';
      $Discussions = $c->Data('Discussions');
      if (is_a($Discussions, 'Gdn_DataSet')) {
         foreach ($Discussions->Result() as $Discussion) {
            $Alt = $Alt == ' Alt' ? '' : ' Alt';
//            var_dump($Discussion);
            WriteDiscussionRow($Discussion, $c, $Session, $Alt);
         }
      }
	?>
	</tbody>
</table>
</div>
<?php
}
   
endif;
