<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/

class UserController extends GardenController {

   public $Uses = array('Database', 'Form');

   public function Index($Offset = FALSE, $Keywords = '') {
      $this->Permission(
         array(
            'Garden.Users.Add',
            'Garden.Users.Edit',
            'Garden.Users.Delete'
         ),
         '',
         FALSE
      );
      $this->AddJsFile('js/library/jquery.gardenmorepager.js');
      $this->AddJsFile('user.js');
      $this->Title(T('Users'));

      $this->AddSideMenu('garden/user');

      $this->Form->Method = 'get';

      // Input Validation
      $Offset = is_numeric($Offset) ? $Offset : 0;
      if (!$Keywords) {
         $Keywords = $this->Form->GetFormValue('Keywords');
         if ($Keywords) {
            $Offset = 0;
         }
      }

      // Put the Keyword back in the form
      if ($Keywords)
         $this->Form->SetFormValue('Keywords', $Keywords);

      $UserModel = new Gdn_UserModel();
      $Like = trim($Keywords) == '' ? FALSE : array('u.Name' => $Keywords, 'u.Email' => $Keywords);
      $Limit = 30;
      $TotalRecords = $UserModel->GetCountLike($Like);
      $this->UserData = $UserModel->GetLike($Like, 'u.Name', 'asc', $Limit, $Offset);

      // Build a pager
      $PagerFactory = new PagerFactory();
      $this->Pager = $PagerFactory->GetPager('MorePager', $this);
      $this->Pager->MoreCode = 'More';
      $this->Pager->LessCode = 'Previous';
      $this->Pager->ClientID = 'Pager';
      $this->Pager->Wrapper = '<tr %1$s><td colspan="5">%2$s</td></tr>';
      $this->Pager->Configure(
         $Offset,
         $Limit,
         $TotalRecords,
         'user/browse/%1$s/'.urlencode($Keywords)
      );
      
      // Deliver json data if necessary
      if ($this->_DeliveryType != DELIVERY_TYPE_ALL) {
         $this->SetJson('LessRow', $this->Pager->ToString('less'));
         $this->SetJson('MoreRow', $this->Pager->ToString('more'));
         $this->View = 'users';
      }

      $this->Render();
   }

   public function AutoComplete() {
      $this->DeliveryType(DELIVERY_TYPE_NONE);
      $Q = GetIncomingValue('q');
      $UserModel = new Gdn_UserModel();
      $Data = $UserModel->GetLike(array('u.Name' => $Q), 'u.Name', 'asc', 10, 0);
      foreach ($Data->Result('Text') as $User) {
         echo $User->Name.'|'.$User->UserID."\n";
      }
      $this->Render();
   }

   public function Add() {
      $this->Permission('Garden.Users.Add');
      $this->AddJsFile('user.js');
      $this->Title(T('Add User'));

      $this->AddSideMenu('garden/user');
      $UserModel = new Gdn_UserModel();
      $RoleModel = new Gdn_Model('Role');
      $this->RoleData = $RoleModel->Get();
      $this->UserRoleData = FALSE;
      $this->User = FALSE;

      // Set the model on the form.
      $this->Form->SetModel($UserModel);

      if ($this->Form->AuthenticatedPostBack()) {
         $NewUserID = $this->Form->Save(array('SaveRoles' => TRUE));
         if ($NewUserID !== FALSE) {
            $Password = $this->Form->GetValue('Password', '');
            $UserModel->SendWelcomeEmail($NewUserID, $Password);
            $this->StatusMessage = T('The user has been created successfully');
            $this->RedirectUrl = Url('garden/user');
         }
         $this->UserRoleData = $this->Form->GetFormValue('RoleID');
      }

      $this->Render();
   }

   public function Browse($Offset = FALSE, $Keywords = '') {
      $this->View = 'index';
      $this->Index($Offset, $Keywords);
   }

   public function Edit($UserID) {
      $this->Permission('Garden.Users.Edit');
      $this->AddJsFile('user.js');

      $this->AddSideMenu('garden/user');

      $RoleModel = new Gdn_Model('Role');
      $this->RoleData = $RoleModel->Get();

      $UserModel = new Gdn_UserModel();
      $this->User = $UserModel->Get($UserID);

      // Set the model on the form.
      $this->Form->SetModel($UserModel);

      // Make sure the form knows which item we are editing.
      $this->Form->AddHidden('UserID', $UserID);

      if (!$this->Form->AuthenticatedPostBack()) {
         $this->Form->SetData($this->User);
         $this->UserRoleData = $UserModel->GetRoles($UserID);
      } else {
         // If a new password was specified, add it to the form's collection
         $ResetPassword = $this->Form->GetValue('ResetPassword', FALSE);
         $NewPassword = $this->Form->GetValue('NewPassword', '');
         if ($ResetPassword !== FALSE)
            $this->Form->SetFormValue('Password', $NewPassword);

         if ($this->Form->Save(array('SaveRoles' => TRUE)) !== FALSE) {
            if ($this->Form->GetValue('Password', '') != '')
               $UserModel->SendPasswordEmail($UserID, $NewPassword);

            $this->StatusMessage = T('Your changes have been saved successfully.');
         }
         $this->UserRoleData = $this->Form->GetFormValue('RoleID');
      }

      $this->Render();
   }

   public function Applicants() {
      $this->Permission('Garden.Users.Approve');
      $this->AddSideMenu('garden/user/applicants');
      $this->AddJsFile('/js/library/jquery.gardencheckcolumn.js');
      $this->Title(T('Applicants'));

      if ($this->Form->AuthenticatedPostBack() === TRUE) {
         $Action = $this->Form->GetValue('Submit');
         $Applicants = $this->Form->GetValue('Applicants');
         $ApplicantCount = is_array($Applicants) ? count($Applicants) : 0;
         if ($ApplicantCount > 0 && in_array($Action, array('Approve', 'Decline'))) {
            $Session = Gdn::Session();
            for ($i = 0; $i < $ApplicantCount; ++$i) {
               $this->HandleApplicant($Action, $Applicants[$i]);
            }
         }
      }
      $UserModel = Gdn::UserModel();
      $this->UserData = $UserModel->GetApplicants();
      $this->View = 'applicants';
      $this->Render();
   }

   public function Approve($UserID = '', $PostBackKey = '') {
      $this->Permission('Garden.Users.Approve');
      $Session = Gdn::Session();
      if ($Session->ValidateTransientKey($PostBackKey))
      
         if($this->HandleApplicant('Approve', $UserID)) {
            $this->StatusMessage = T('Your changes have been saved.');
         }

      if ($this->_DeliveryType == DELIVERY_TYPE_BOOL) {
         return $this->Form->ErrorCount() == 0 ? TRUE : $this->Form->Errors();
      } else {
         $this->Applicants();
      }
   }

   public function Decline($UserID = '', $PostBackKey = '') {
      $this->Permission('Garden.Users.Approve');
      $Session = Gdn::Session();
      if ($Session->ValidateTransientKey($PostBackKey)) {
         if ($this->HandleApplicant('Decline', $UserID))
            $this->StatusMessage = T('Your changes have been saved.');
      }

      if ($this->_DeliveryType == DELIVERY_TYPE_BOOL) {
         return $this->Form->ErrorCount() == 0 ? TRUE : $this->Form->Errors();
      } else {
         $this->Applicants();
      }
   }

   private function HandleApplicant($Action, $UserID) {
      $this->Permission('Garden.Users.Approve');
      //$this->_DeliveryType = DELIVERY_TYPE_BOOL;
      if (!in_array($Action, array('Approve', 'Decline')) || !is_numeric($UserID)) {
         $this->Form->AddError('ErrorInput');
         $Result = FALSE;
      } else {
         $Session = Gdn::Session();
         //if (!$Session->CheckPermission('Garden.Users.Approve')) {
         //   $this->Form->AddError('ErrorPermission');
         //} else {
            $UserModel = new Gdn_UserModel();
            if (is_numeric($UserID)) {
               try {
                  $Email = new Gdn_Email();
                  $Result = $UserModel->$Action($UserID, $Email);
               } catch(Exception $ex) {
                  $Result = FALSE;
                  $this->Form->AddError(strip_tags($ex->getMessage()));
               }
            }
         //}
      }
   }


   public function Initialize() {
      parent::Initialize();
      if ($this->Menu)
         $this->Menu->HighlightRoute('/garden/settings');
   }
}