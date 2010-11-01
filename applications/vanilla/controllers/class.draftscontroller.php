<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/
/**
 * Drafts Controller
 *
 * @package Vanilla
 */
 
/**
 * Handles displaying saved drafts of unposted comments.
 *
 * @since 2.0.0
 * @package Vanilla
 */
class DraftsController extends VanillaController {
   /**
    * Models to include.
    * 
    * @since 2.0.0
    * @access public
    * @var array
    */
   public $Uses = array('Database', 'DraftModel');
   
   /**
    * Default all drafts view: chronological by time saved.
    * 
    * @since 2.0.0
    * @access public
    * 
    * @param int $Offset Number of drafts to skip.
    */
   public function Index($Offset = '0') {
      // Setup head
      $this->Permission('Garden.SignIn.Allow');
      $this->AddCssFile('vanilla.css');
      $this->AddJsFile('jquery.gardenmorepager.js');
      $this->AddJsFile('discussions.js');
      $this->AddJsFile('options.js');
      $this->Title(T('My Drafts'));
      
      // Validate $Offset
      if (!is_numeric($Offset) || $Offset < 0)
         $Offset = 0;
      
      // Set criteria & get drafts data
      $Limit = Gdn::Config('Vanilla.Discussions.PerPage', 30);
      $Session = Gdn::Session();
      $Wheres = array('d.InsertUserID' => $Session->UserID);
      $this->DraftData = $this->DraftModel->Get($Session->UserID, $Offset, $Limit);
      $CountDrafts = $this->DraftModel->GetCount($Session->UserID);
      
      // Build a pager
      $PagerFactory = new Gdn_PagerFactory();
      $this->Pager = $PagerFactory->GetPager('MorePager', $this);
      $this->Pager->MoreCode = 'More drafts';
      $this->Pager->LessCode = 'Newer drafts';
      $this->Pager->ClientID = 'Pager';
      $this->Pager->Configure(
         $Offset,
         $Limit,
         $CountDrafts,
         'drafts/%1$s'
      );
      
      // Deliver JSON data if necessary
      if ($this->_DeliveryType != DELIVERY_TYPE_ALL) {
         $this->SetJson('LessRow', $this->Pager->ToString('less'));
         $this->SetJson('MoreRow', $this->Pager->ToString('more'));
         $this->View = 'drafts';
      }
      
      // Add modules
      $this->AddModule('NewDiscussionModule');
      $this->AddModule('CategoriesModule');
      $BookmarkedModule = new BookmarkedModule($this);
      $BookmarkedModule->GetData();
      $this->AddModule($BookmarkedModule);
      
      // Render default view (drafts/index.php)
      $this->Render();
   }
   
   /**
    * Delete a single draft.
    *
    * Redirects user back to Index unless DeliveryType is set.
    * 
    * @since 2.0.0
    * @access public
    * 
    * @param int $DraftID Unique ID of draft to be deleted.
    * @param string $TransientKey Single-use hash to prove intent.
    */
   public function Delete($DraftID = '', $TransientKey = '') {
      $Form = Gdn::Factory('Form');
      $Session = Gdn::Session();
      if (
         is_numeric($DraftID)
         && $DraftID > 0
         && $Session->UserID > 0
         && $Session->ValidateTransientKey($TransientKey)
      ) {
         // Delete the draft
         $Draft = $this->DraftModel->GetID($DraftID);
         if ($Draft && !$this->DraftModel->Delete($DraftID))
            $Form->AddError('Failed to delete discussion');
      } else {
         // Log an error
         $Form->AddError('ErrPermission');
      }
      
      // Redirect
      if ($this->_DeliveryType === DELIVERY_TYPE_ALL) {
         $Target = GetIncomingValue('Target', '/vanilla/drafts');
         Redirect($Target);
      }
      
      // Return any errors  
      if ($Form->ErrorCount() > 0)
         $this->SetJson('ErrorMessage', $Form->Errors());
      
      // Render default view
      $this->Render();         
   }
}