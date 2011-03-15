<?php if (!defined('APPLICATION')) exit();
/**
 * Render options that the user has for this discussion.
 */
function GetOptions($Category, $Sender) {
   if (!Gdn::Session()->IsValid())
      return;
   
   $Result = '';
   $Options = '';
   $CategoryID = GetValue('CategoryID', $Category);

   $Result = '<div class="Options">';

   // Mark category read.
   $Options .= '<li>'.Anchor(T('Mark Read'), "/vanilla/category/markread?categoryid=$CategoryID").'</li>';

   // Follow/Unfollow category.
   if (!GetValue('Following', $Category))
      $Options .= '<li>'.Anchor(T('Follow'), "/vanilla/category/follow?categoryid=$CategoryID&value=1").'</li>';
   else
      $Options .= '<li>'.Anchor(T('Unfollow'), "/vanilla/category/follow?categoryid=$CategoryID&value=0").'</li>';

   // Allow plugins to add options
   $Sender->FireEvent('DiscussionOptions');

   if ($Options != '') {
      $Result .= '<div class="ToggleFlyout OptionsMenu"><div class="MenuTitle">'.T('Options').'</div>'
         .'<ul class="Flyout MenuItems">'.$Options.'</ul>'
         .'</div>';
      
   $Result .= '</div>';
   return $Result;
   }
}