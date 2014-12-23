<?php if (!defined('APPLICATION')) exit();

// 0.2 - 2011-09-07 - mosullivan - Added InjectCssClass, Optimized querying.
// 0.3 - 2011-12-13 - linc - Add class to title span, make injected CSS class Vanilla-like (capitalized, no dashes).
// 0.2 - 2012-05-21 - mosullivan - Add _CssClass to Discussion object so first comment in list gets the role css.

$PluginInfo['RoleTitle'] = array(
   'Name' => 'Role Titles',
   'Description' => "Lists users' roles under their name and adds role-specific CSS classes to their comments for theming.",
   'Version' => '1.0',
   'RequiredApplications' => array('Vanilla' => '2.0.18'),
   'MobileFriendly' => TRUE,
   'RegisterPermissions' => FALSE,
   'Author' => "Matt Lincoln Russell",
   'AuthorEmail' => 'lincolnwebs@gmail.com',
   'AuthorUrl' => 'http://lincolnwebs.com'
);

class RoleTitlePlugin extends Gdn_Plugin {
   /**
    * Inject the roles under the username on comments.
    */
//   public function DiscussionController_CommentInfo_Handler($Sender) {
//      $this->_AttachTitle($Sender);
//   }
//   public function DiscussionController_AfterDiscussionMeta_Handler($Sender) {
//      $this->_AttachTitle($Sender);
//   }
//   public function PostController_CommentInfo_Handler($Sender) {
//      $this->_AttachTitle($Sender);
//   }
   
   public function DiscussionController_AuthorInfo_Handler($Sender) {
      $this->_AttachTitle($Sender);
   }
   
   private function _AttachTitle($Sender) {
      $Object = GetValue('Object', $Sender->EventArguments);
      $Roles = $Object ? GetValue('Roles', $Object, array()) : FALSE;
      if (!$Roles)
         return;

      echo '<span class="MItem RoleTitle">'.implode(', ', $Roles).'</span> ';
   }

   /**
    * Inject css classes into the comment containers.
    */
   public function DiscussionController_BeforeCommentDisplay_Handler($Sender) {
      $this->_InjectCssClass($Sender);
   }
   public function PostController_BeforeCommentDisplay_Handler($Sender) {
      $this->_InjectCssClass($Sender);
   }
   private function _InjectCssClass($Sender) {
      $Object = GetValue('Object', $Sender->EventArguments);
      $CssRoles = $Object ? GetValue('Roles', $Object, array()) : FALSE;
      if (!$CssRoles)
         return;
      
      foreach ($CssRoles as &$RawRole)
         $RawRole = $this->_FormatRoleCss($RawRole);
   
      if (count($CssRoles))
         $Sender->EventArguments['CssClass'] .= ' '.implode(' ',$CssRoles);
      
   }
   
   /**
    * Add the insert user's roles to the comment data so we can visually
    * identify different roles in the view.
    */ 
	public function DiscussionController_Render_Before($Sender) {
		$Session = Gdn::Session();
		if ($Session->IsValid()) {
			$JoinUser = array($Session->User);
			RoleModel::SetUserRoles($JoinUser, 'UserID');
		}
		if (property_exists($Sender, 'Discussion')) {
			$JoinDiscussion = array($Sender->Discussion);
			RoleModel::SetUserRoles($JoinDiscussion, 'InsertUserID');
	      $Comments = $Sender->Data('Comments');
			RoleModel::SetUserRoles($Comments->Result(), 'InsertUserID');

         $Answers = $Sender->Data('Answers');
         if (is_array($Answers)) {
            RoleModel::SetUserRoles($Answers, 'InsertUserID');
         }

         // And add the css class to the discussion
         if (is_array($Sender->Discussion->Roles)) {
            if (count($Sender->Discussion->Roles)) {
               $CssRoles = GetValue('Roles', $Sender->Discussion);
               foreach ($CssRoles as &$RawRole)
                  $RawRole = $this->_FormatRoleCss($RawRole);
   
               $Sender->Discussion->_CssClass = GetValue('_CssClass', $Sender->Discussion, '').' '.implode(' ',$CssRoles);
            }
         }
		}
   }

   public function PostController_Render_Before($Sender) {
      $Data = $Sender->Data('Comments');
		if (is_object($Data))
			RoleModel::SetUserRoles($Data->Result(), 'InsertUserID');
	}

   // Add it to the comment form
   public function Base_BeforeCommentForm_Handler($Sender) {
      $CssClass = GetValue('FormCssClass', $Sender->EventArguments, '');
      $CssRoles = GetValue('Roles', Gdn::Session()->User);
      if (!is_array($CssRoles))
         return;
         
      foreach ($CssRoles as &$RawRole)
         $RawRole = $this->_FormatRoleCss($RawRole);

      $Sender->EventArguments['FormCssClass'] = $CssClass.' '.implode(' ',$CssRoles);
   }
   
   
   private function _FormatRoleCss($RawRole) {
      return 'Role_'.str_replace(' ','_', Gdn_Format::AlphaNumeric($RawRole));
   }
   
   // Add the roles to the profile body tag
   public function ProfileController_Render_Before($Sender) {
      $CssRoles = $Sender->Data('UserRoles');
      if (!is_array($CssRoles))
         return;
      
      foreach ($CssRoles as &$RawRole)
         $RawRole = $this->_FormatRoleCss($RawRole);
      
      $Sender->CssClass = trim($Sender->CssClass.' '.implode(' ',$CssRoles));
   }
}