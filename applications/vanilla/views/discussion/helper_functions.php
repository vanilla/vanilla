<?php if (!defined('APPLICATION')) exit();

function CssClass($Row) {
   
}

function FormatBody($Row) {
   $Row->FormatBody = Gdn_Format::To($Row->Body, $Row->Format);
   Gdn::Controller()->EventArguments['Object'] = $Row;
   Gdn::Controller()->FireEvent('AfterCommentFormat');
   
   return $Row->FormatBody;
}

function WriteBookmarkLink() {
   if (!Gdn::Session()->IsValid())
      return '';
   
   $Discussion = Gdn::Controller()->Data('Discussion');

   // Bookmark link
   echo Anchor(
      '<span>*</span>',
      '/vanilla/discussion/bookmark/'.$Discussion->DiscussionID.'/'.Gdn::Session()->TransientKey().'?Target='.urlencode(Gdn::Controller()->SelfUrl),
      'Bookmark' . ($Discussion->Bookmarked == '1' ? ' Bookmarked' : ''),
      array('title' => T($Discussion->Bookmarked == '1' ? 'Unbookmark' : 'Bookmark'))
   );
}

/**
 * $Object is either a Comment or the original Discussion.
 */
function WriteComment($Object, $Sender, $Session, $CurrentOffset) {
   $Alt = ($CurrentOffset % 2) != 0;

   $Author = UserBuilder($Object, 'Insert');
   $Type = property_exists($Object, 'CommentID') ? 'Comment' : 'Discussion';
	$Sender->EventArguments['Object'] = $Object;
   $Sender->EventArguments['Type'] = $Type;
   $Sender->EventArguments['Author'] = $Author;
   $CssClass = 'Item Comment';
   $Permalink = GetValue('Url', $Object, FALSE);

   if (!property_exists($Sender, 'CanEditComments'))
		$Sender->CanEditComments = $Session->CheckPermission('Vanilla.Comments.Edit', TRUE, 'Category', 'any') && C('Vanilla.AdminCheckboxes.Use');
		

   if ($Type == 'Comment') {
      $Sender->EventArguments['Comment'] = $Object;   
      $Id = 'Comment_'.$Object->CommentID;
      if ($Permalink === FALSE)
         $Permalink = '/discussion/comment/'.$Object->CommentID.'/#Comment_'.$Object->CommentID;
   } else {
      $Sender->EventArguments['Discussion'] = $Object;   
      $CssClass .= ' FirstComment';
      $Id = 'Discussion_'.$Object->DiscussionID;
      if ($Permalink === FALSE)
         $Permalink = '/discussion/'.$Object->DiscussionID.'/'.Gdn_Format::Url($Object->Name).'/p1';
   }
   $Sender->EventArguments['CssClass'] = &$CssClass;
   $Sender->Options = '';
   $CssClass .= $Object->InsertUserID == $Session->UserID ? ' Mine' : '';

   if ($Alt)
      $CssClass .= ' Alt';
   $Alt = !$Alt;
	
	
   $Sender->FireEvent('BeforeCommentDisplay');
?>
<li class="<?php echo $CssClass; ?>" id="<?php echo $Id; ?>">
   <div class="Comment">
      <div class="Meta">
         <?php $Sender->FireEvent('BeforeCommentMeta'); ?>
         <span class="Author">
            <?php
            echo UserPhoto($Author);
            echo UserAnchor($Author);
            ?>
         </span>
         <span class="MItem DateCreated">
            <?php
            echo Anchor(Gdn_Format::Date($Object->DateInserted, 'html'), $Permalink, 'Permalink', array('name' => 'Item_'.($CurrentOffset), 'rel' => 'nofollow'));
            ?>
         </span>
         <?php
         if ($Source = GetValue('Source', $Object)) {
            echo sprintf(T('via %s'), T($Source.' Source', $Source));
         }
         
			WriteOptionList($Object, $Sender, $Session);
			?>
         <div class="CommentInfo">
            <?php
            $Sender->FireEvent('CommentInfo');
            if ($Session->CheckPermission('Garden.Moderation.Manage')) {
               echo ' <span>'.IPAnchor($Object->InsertIPAddress).'<span class="MItem"> ';
            }
            ?>
         </div>
         <?php $Sender->FireEvent('AfterCommentMeta'); ?>
      </div>
      <div class="Message">
			<?php 
            $Sender->FireEvent('BeforeCommentBody'); 
			   $Object->FormatBody = Gdn_Format::To($Object->Body, $Object->Format);
			   $Sender->FireEvent('AfterCommentFormat');
			   $Object = $Sender->EventArguments['Object'];
			   echo $Object->FormatBody;
			?>
		</div>
      <?php $Sender->FireEvent('AfterCommentBody'); ?>
   </div>
</li>
<?php
	$Sender->FireEvent('AfterComment');
}

function GetDiscussionOptions($Discussion = NULL) {
   $Result = array();
   
   $Sender = Gdn::Controller();
   $Session = Gdn::Session();
   
   if ($Discussion == NULL)
      $Discussion = $Sender->Data('Discussion');
   
   $EditContentTimeout = C('Garden.EditContentTimeout', -1);
	$CategoryID = GetValue('CategoryID', $Discussion);
	if(!$CategoryID && property_exists($Sender, 'Discussion'))
		$CategoryID = GetValue('CategoryID', $Sender->Discussion);
   $PermissionCategoryID = GetValue('PermissionCategoryID', $Discussion, GetValue('PermissionCategoryID', $Sender->Discussion));

	$CanEdit = $EditContentTimeout == -1 || strtotime($Discussion->DateInserted) + $EditContentTimeout > time();
	$TimeLeft = '';
	if ($CanEdit && $EditContentTimeout > 0 && !$Session->CheckPermission('Vanilla.Discussions.Edit', TRUE, 'Category', $PermissionCategoryID)) {
		$TimeLeft = strtotime($Discussion->DateInserted) + $EditContentTimeout - time();
		$TimeLeft = $TimeLeft > 0 ? ' ('.Gdn_Format::Seconds($TimeLeft).')' : '';
	}
   
   // Can the user edit the discussion?
   if (($CanEdit && $Session->UserID == $Discussion->InsertUserID) || $Session->CheckPermission('Vanilla.Discussions.Edit', TRUE, 'Category', $PermissionCategoryID))
      $Result['EditDiscussion'] = array('Label' => T('Edit').' '.$TimeLeft, 'Url' => '/vanilla/post/editdiscussion/'.$Discussion->DiscussionID);

   // Can the user announce?
   if ($Session->CheckPermission('Vanilla.Discussions.Announce', TRUE, 'Category', $PermissionCategoryID))
      $Result['AnnounceDiscussion'] = array('Label' => T($Sender->Discussion->Announce ? 'Unannounce' : 'Announce'), 'Url' => 'vanilla/discussion/announce/'.$Discussion->DiscussionID.'/'.$Session->TransientKey().'?Target='.urlencode($Sender->SelfUrl.'#Head'), 'Class' => 'Hijack');

   // Can the user sink?
   if ($Session->CheckPermission('Vanilla.Discussions.Sink', TRUE, 'Category', $PermissionCategoryID))
      $Result['SinkDiscussion'] = array('Label' => T($Sender->Discussion->Sink ? 'Unsink' : 'Sink'), 'Url' => 'vanilla/discussion/sink/'.$Discussion->DiscussionID.'/'.$Session->TransientKey().'?Target='.urlencode($Sender->SelfUrl.'#Head'), 'Class' => 'Hijack');

   // Can the user close?
   if ($Session->CheckPermission('Vanilla.Discussions.Close', TRUE, 'Category', $PermissionCategoryID))
      $Result['CloseDiscussion'] = array('Label' => T($Sender->Discussion->Closed ? 'Reopen' : 'Close'), 'Url' => 'vanilla/discussion/close/'.$Discussion->DiscussionID.'/'.$Session->TransientKey().'?Target='.urlencode($Sender->SelfUrl.'#Head'), 'Class' => 'Hijack');

   // Can the user delete?
   if ($Session->CheckPermission('Vanilla.Discussions.Delete', TRUE, 'Category', $PermissionCategoryID))
      $Result['DeleteDiscussion'] = array('Label' => T('Delete Discussion'), 'Url' => 'vanilla/discussion/delete/'.$Discussion->DiscussionID.'/'.$Session->TransientKey());
   
   // Allow plugins to add options.
   $Sender->EventArguments['Options'] =& $Result;
   $Sender->FireEvent('GetDiscussionOptions');
   
   return $Result;
}

function WriteAdminCheck($Row = NULL) {
   if (!Gdn::Controller()->CanEditComments || !C('Vanilla.AdminCheckboxes.Use'))
      return;
   
   echo '<span class="AdminCheck"><input type="checkbox" name="Toggle"></span>';
}

function WriteDiscussionOptions($Discussion = NULL) {
   $Options = GetDiscussionOptions();
   
   if (empty($Options))
      return;
   
   echo '<span class="ToggleFlyout OptionsMenu">';
   
   echo '<span class="OptionsTitle" title="'.T('Options').'">'.T('Options').'</span>';
   
   echo '<ul class="Flyout MenuItems" style="display: none;">';
   foreach ($Options as $Code => $Option) {
      echo '<li>'.Anchor($Option['Label'], $Option['Url'], GetValue('Class', $Option, $Code)).'</li>';
   }
   echo '</ul>';
   
   echo '</span>';
}

function WriteOptionList($Object = NULL) {
   $Sender = Gdn::Controller();
   $Session = Gdn::Session();
   
   if ($Object == NULL)
      $Object = $Sender->Data('Discussion');
   
   $EditContentTimeout = C('Garden.EditContentTimeout', -1);
	$CategoryID = GetValue('CategoryID', $Object);
	if(!$CategoryID && property_exists($Sender, 'Discussion'))
		$CategoryID = GetValue('CategoryID', $Sender->Discussion);
   $PermissionCategoryID = GetValue('PermissionCategoryID', $Object, GetValue('PermissionCategoryID', $Sender->Discussion));

	$CanEdit = $EditContentTimeout == -1 || strtotime($Object->DateInserted) + $EditContentTimeout > time();
	$TimeLeft = '';
	if ($CanEdit && $EditContentTimeout > 0 && !$Session->CheckPermission('Vanilla.Discussions.Edit', TRUE, 'Category', $PermissionCategoryID)) {
		$TimeLeft = strtotime($Object->DateInserted) + $EditContentTimeout - time();
		$TimeLeft = $TimeLeft > 0 ? ' ('.Gdn_Format::Seconds($TimeLeft).')' : '';
	}

   $Sender->Options = '';
   
   if (GetValue('CommentID', $Object))
      $Type = 'Comment';
   else
      $Type = 'Discussion';
   $Sender->EventArguments['Type'] = $Type;
		
   // Show discussion options if this is the discussion / first comment
   if ($Type == 'Discussion') {
      $Sender->EventArguments['Discussion'] = $Object;
      
      // Can the user edit the discussion?
      if (($CanEdit && $Session->UserID == $Object->InsertUserID) || $Session->CheckPermission('Vanilla.Discussions.Edit', TRUE, 'Category', $PermissionCategoryID))
         $Sender->Options .= ' <span class="MItem">'.Anchor(T('Edit'), '/vanilla/post/editdiscussion/'.$Object->DiscussionID, 'EditDiscussion').$TimeLeft.'</span> ';
         
      // Can the user announce?
      if ($Session->CheckPermission('Vanilla.Discussions.Announce', TRUE, 'Category', $PermissionCategoryID))
         $Sender->Options .= ' <span class="MItem">'.Anchor(T($Sender->Discussion->Announce == '1' ? 'Unannounce' : 'Announce'), 'vanilla/discussion/announce/'.$Object->DiscussionID.'/'.$Session->TransientKey().'?Target='.urlencode($Sender->SelfUrl), 'AnnounceDiscussion') . '</span> ';

      // Can the user sink?
      if ($Session->CheckPermission('Vanilla.Discussions.Sink', TRUE, 'Category', $PermissionCategoryID))
         $Sender->Options .= ' <span class="MItem">'.Anchor(T($Sender->Discussion->Sink == '1' ? 'Unsink' : 'Sink'), 'vanilla/discussion/sink/'.$Object->DiscussionID.'/'.$Session->TransientKey().'?Target='.urlencode($Sender->SelfUrl), 'SinkDiscussion') . '</span> ';

      // Can the user close?
      if ($Session->CheckPermission('Vanilla.Discussions.Close', TRUE, 'Category', $PermissionCategoryID))
         $Sender->Options .= ' <span class="MItem">'.Anchor(T($Sender->Discussion->Closed == '1' ? 'Reopen' : 'Close'), 'vanilla/discussion/close/'.$Object->DiscussionID.'/'.$Session->TransientKey().'?Target='.urlencode($Sender->SelfUrl), 'CloseDiscussion') . '</span> ';
      
      // Can the user delete?
      if ($Session->CheckPermission('Vanilla.Discussions.Delete', TRUE, 'Category', $PermissionCategoryID))
         $Sender->Options .= ' <span class="MItem">'.Anchor(T('Delete Discussion'), 'vanilla/discussion/delete/'.$Object->DiscussionID.'/'.$Session->TransientKey(), 'DeleteDiscussion') . '</span> ';
   } else {
      // And if this is just another comment in the discussion ...
      $Sender->EventArguments['Comment'] = $Object;
      
      // Can the user edit the comment?
      if (($CanEdit && $Session->UserID == $Object->InsertUserID) || $Session->CheckPermission('Vanilla.Comments.Edit', TRUE, 'Category', $PermissionCategoryID))
         $Sender->Options .= ' <span class="MItem">'.Anchor(T('Edit'), '/vanilla/post/editcomment/'.$Object->CommentID, 'EditComment').$TimeLeft.'</span> ';

      // Can the user delete the comment?
      if ($Session->CheckPermission('Vanilla.Comments.Delete', TRUE, 'Category', $PermissionCategoryID))
         $Sender->Options .= ' <span class="MItem">'.Anchor(T('Delete'), 'vanilla/discussion/deletecomment/'.$Object->CommentID.'/'.$Session->TransientKey().'/?Target='.urlencode("/discussion/{$Object->DiscussionID}/x"), 'DeleteComment') . '</span> ';
   }
   
   // Allow plugins to add options
   $Sender->FireEvent('CommentOptions');
   echo $Sender->Options;

   if ($Sender->CanEditComments) {
      if ($Sender->EventArguments['Type'] == 'Comment') {
         $Id = $Object->CommentID;
         echo '<div class="Options">';
         if (!property_exists($Sender, 'CheckedComments'))
            $Sender->CheckedComments = $Session->GetAttribute('CheckedComments', array());

         $ItemSelected = InSubArray($Id, $Sender->CheckedComments);
         echo '<span class="AdminCheck"><input type="checkbox" name="'.'Comment'.'ID[]" value="'.$Id.'"'.($ItemSelected?' checked="checked"':'').' /></span>';
         echo '</div>';
      } else {
         echo '<div class="Options">';

         echo '<div class="AdminCheck"><input type="checkbox" name="Toggle"></div>';

         echo '</div>';
      }
   }
}