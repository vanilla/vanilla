<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/

// Define the plugin:
$PluginInfo['LastEdited'] = array(
   'Name' => 'Last Edited',
   'Description' => 'Appends "Post edited by [User] at [Time]" to the end of edited posts and links to change log.',
   'Version' => '1.1.1',
   'MobileFriendly' => TRUE,
   'RequiredApplications' => array('Vanilla' => '2.1'),
   'HasLocale' => TRUE,
   'RegisterPermissions' => FALSE,
   'Author' => "Tim Gunter",
   'AuthorEmail' => 'tim@vanillaforums.com',
   'AuthorUrl' => 'http://www.vanillaforums.com'
);

class LastEditedPlugin extends Gdn_Plugin {
   /**
    * @param AssetModel $Sender
    */
   public function AssetModel_StyleCss_Handler($Sender, $Args) {
      $Sender->AddCssFile('lastedited.css', 'plugins/LastEdited');
   }
   
   public function DiscussionController_AfterDiscussionBody_Handler($Sender) {
      $this->DrawEdited($Sender);
   }
   
   public function DiscussionController_AfterCommentBody_Handler($Sender) {
      $this->DrawEdited($Sender);
   }
   
   public function PostController_AfterCommentBody_Handler($Sender) {
      $this->DrawEdited($Sender);
   }
   
   protected function DrawEdited($Sender) {
      $Record = $Sender->Data('Discussion');
      if (!$Record)
         $Record = $Sender->Data('Record');
      
      if (!$Record)
         return;

      $PermissionCategoryID = GetValue('PermissionCategoryID', $Record);
      
      $Data = $Record;
      $RecordType = 'discussion';
      $RecordID = GetValue('DiscussionID', $Data);
      
      // But override if comment
      if (isset($Sender->EventArguments['Comment']) || GetValue('RecordType', $Record) == 'comment') {
         $Data = $Sender->EventArguments['Comment'];
         $RecordType = 'comment';
         $RecordID = GetValue('CommentID', $Data);
      }
      
      $UserCanEdit = Gdn::Session()->CheckPermission('Vanilla.'.ucfirst($RecordType).'s.Edit', TRUE, 'Category', $PermissionCategoryID);
      
      if (is_null($Data->DateUpdated)) return;
      if (Gdn_Format::ToTimestamp($Data->DateUpdated) <= Gdn_Format::ToTimestamp($Data->DateInserted)) return;
      
      $SourceUserID = $Data->InsertUserID;
      $UpdatedUserID = $Data->UpdateUserID;
      
      $UserData = Gdn::UserModel()->GetID($UpdatedUserID);
      $Edited = array(
         'EditUser'     => GetValue('Name', $UserData, T('Unknown User')),
         'EditDate'     => Gdn_Format::Date($Data->DateUpdated, 'html'),
         'EditLogUrl'   => Url("/log/record/{$RecordType}/{$RecordID}"),
         'EditWord'     => 'at'
      );
      
      $DateUpdateTime = Gdn_Format::ToTimestamp($Data->DateUpdated);
      if (date('ymd', $DateUpdateTime) != date('ymd'))
         $Edited['EditWord'] = 'on';
      
      $Format = T('PostEdited.Plain', 'Post edited by {EditUser} {EditWord} {EditDate}');
      if ($UserCanEdit)
         $Format = T('PostEdited.Log', 'Post edited by {EditUser} {EditWord} {EditDate} (<a href="{EditLogUrl}">log</a>)');
      
      $Display = '<div class="PostEdited">'.FormatString($Format, $Edited).'</div>';
      echo $Display;
      
   }
   
   public function Setup() {
      // Nothing to do!
   }
   
}