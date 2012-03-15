<?php if (!defined('APPLICATION')) exit();

function WriteDiscussion($Discussion, &$Sender, &$Session) {
   $CssClass = CssClass($Discussion);
   $DiscussionUrl = $Discussion->Url;
   
   if ($Session->UserID)
      $DiscussionUrl .= '#Item_'.($Discussion->CountCommentWatch);
   
   $Sender->EventArguments['DiscussionUrl'] = &$DiscussionUrl;
   $Sender->EventArguments['Discussion'] = &$Discussion;
   $Sender->EventArguments['CssClass'] = &$CssClass;
   
   $First = UserBuilder($Discussion, 'First');
   $Last = UserBuilder($Discussion, 'Last');
   $Sender->EventArguments['FirstUser'] = &$First;
   $Sender->EventArguments['LastUser'] = &$Last;
   
   $Sender->FireEvent('BeforeDiscussionName');
   
   $DiscussionName = $Discussion->Name;
   if ($DiscussionName == '')
      $DiscussionName = T('Blank Discussion Topic');
      
   $Sender->EventArguments['DiscussionName'] = &$DiscussionName;

   static $FirstDiscussion = TRUE;
   if (!$FirstDiscussion)
      $Sender->FireEvent('BetweenDiscussion');
   else
      $FirstDiscussion = FALSE;
      
   $Discussion->CountPages = ceil($Discussion->CountComments / $Sender->CountCommentsPerPage);
?>
<li class="<?php echo $CssClass; ?>">
   <?php
      echo UserPhoto($First);

   if (!property_exists($Sender, 'CanEditDiscussions'))
      $Sender->CanEditDiscussions = GetValue('PermsDiscussionsEdit', CategoryModel::Categories($Discussion->CategoryID)) && C('Vanilla.AdminCheckboxes.Use');

   $Sender->FireEvent('BeforeDiscussionContent');
   ?>
   <div class="ItemContent Discussion">
      <div class="Title">
      <?php 
         echo Anchor($DiscussionName, $DiscussionUrl);
         $Sender->FireEvent('AfterDiscussionTitle'); 
      ?>
      </div>
      <div class="Meta">
         <span class="Author"><?php echo $Discussion->FirstName; ?></span>
         <?php WriteTags($Discussion); ?>
         <span class="MItem CommentCount"><?php 
            printf(Plural($Discussion->CountComments, '%s comment', '%s comments'), $Discussion->CountComments);
         ?></span>
         <?php
            echo NewComments($Discussion);
         
            $Sender->FireEvent('AfterCountMeta');

            if ($Discussion->LastCommentID != '') {
               echo ' <span class="MItem LastCommentBy">'.sprintf(T('Most recent by %1$s'), UserAnchor($Last)).'</span> ';
               echo ' <span class="MItem LastCommentDate">'.Gdn_Format::Date($Discussion->LastDate, 'html').'</span>';
            } else {
               echo ' <span class="MItem LastCommentBy">'.sprintf(T('Started by %1$s'), UserAnchor($First)).'</span> ';
               echo ' <span class="MItem LastCommentDate">'.Gdn_Format::Date($Discussion->FirstDate, 'html');
               
               if ($Source = GetValue('Source', $Discussion)) {
                  echo ' '.sprintf(T('via %s'), T($Source.' Source', $Source));
               }
               
               echo '</span> ';
            }
         
            if (C('Vanilla.Categories.Use') && $Discussion->CategoryUrlCode != '')
               echo Wrap(Anchor($Discussion->Category, '/categories/'.rawurlencode($Discussion->CategoryUrlCode)), 'span', array('class' => 'MItem Category'));
               
            $Sender->FireEvent('DiscussionMeta');
         ?>
      </div>
   </div>
</li>
<?php
}

// These options do not appear in mobile.
function WriteFilterTabs($Sender) {}
function WriteOptions($Discussion, &$Sender, &$Session) {}

// Now that we've overrided what we want, include the defaults.
include_once PATH_APPLICATIONS.'/vanilla/views/discussions/helper_functions.php';

