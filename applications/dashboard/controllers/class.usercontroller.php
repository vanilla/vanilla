<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/

class UserController extends DashboardController {

   public $Uses = array('Database', 'Form');

   public function Index($Keywords = '', $Page = '') {
      $this->Permission(
         array(
            'Garden.Users.Add',
            'Garden.Users.Edit',
            'Garden.Users.Delete'
         ),
         '',
         FALSE
      );
      $this->AddJsFile('jquery.gardenmorepager.js');
      $this->AddJsFile('user.js');
      $this->Title(T('Users'));

      $this->AddSideMenu('dashboard/user');

      $this->Form->Method = 'get';

      // Input Validation.
      list($Offset, $Limit) = OffsetLimit($Page, PagerModule::$DefaultPageSize);
      if (!$Keywords) {
         $Keywords = $this->Form->GetFormValue('Keywords');
         if ($Keywords)
            $Offset = 0;
      }

      // Put the Keyword back in the form
      if ($Keywords)
         $this->Form->SetFormValue('Keywords', $Keywords);

      $UserModel = new UserModel();
      //$Like = trim($Keywords) == '' ? FALSE : array('u.Name' => $Keywords, 'u.Email' => $Keywords);
      list($Offset, $Limit) = OffsetLimit($Page, 30);

      $Filter = $this->_GetFilter();
      if ($Filter)
         $Filter['Keywords'] = $Keywords;
      else
         $Filter = $Keywords;

      $this->SetData('RecordCount', $UserModel->SearchCount($Filter));
      $this->UserData = $UserModel->Search($Filter, 'u.Name', 'asc', $Limit, $Offset);
      RoleModel::SetUserRoles($this->UserData->Result());
      
      // Deliver json data if necessary
      if ($this->_DeliveryType != DELIVERY_TYPE_ALL) {
         $this->SetJson('LessRow', $this->Pager->ToString('less'));
         $this->SetJson('MoreRow', $this->Pager->ToString('more'));
         $this->View = 'users';
      }

      $this->Render();
   }

   public function Add() {
      $this->Permission('Garden.Users.Add');
      $this->AddJsFile('user.js');
      $this->Title(T('Add User'));

      $this->AddSideMenu('dashboard/user');
      $UserModel = new UserModel();
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
            $UserModel->SendWelcomeEmail($NewUserID, $Password, 'Add');
            $this->InformMessage(T('The user has been created successfully'));
            $this->RedirectUrl = Url('dashboard/user');
         }
         $this->UserRoleData = $this->Form->GetFormValue('RoleID');
      } else {
         // Set the default roles.
         $this->UserRoleData = C('Garden.Registration.DefaultRoles', array());
      }

      $this->Render();
   }

   public function ApplicantCount() {
      $this->Permission('Garden.Applicants.Manage');

      $Count = Gdn::SQL()->GetCount('UserRole', array('RoleID' => C('Garden.Registration.ApplicantRoleID', 0)));

      if ($Count > 0)
         echo '<span class="Alert">', $Count, '</span>';
   }
	
	public function Applicants() {
      $this->Permission('Garden.Users.Approve');
      $this->AddSideMenu('dashboard/user/applicants');
      $this->AddJsFile('jquery.gardencheckcolumn.js');
      $this->Title(T('Applicants'));
      
      $this->FireEvent('BeforeApplicants');

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
      if ($Session->ValidateTransientKey($PostBackKey)) {
         $Approved = $this->HandleApplicant('Approve', $UserID);
         if ($Approved) {
            $this->InformMessage(T('Your changes have been saved.'));
         }
      }

      if ($this->_DeliveryType == DELIVERY_TYPE_BOOL) {
         return $this->Form->ErrorCount() == 0 ? TRUE : $this->Form->Errors();
      } else {
         $this->Applicants();
      }
   }
	
   public function AutoComplete() {
      $this->DeliveryType(DELIVERY_TYPE_NONE);
      $Q = GetIncomingValue('q');
      $UserModel = new UserModel();
      $Data = $UserModel->GetLike(array('u.Name' => $Q), 'u.Name', 'asc', 10, 0);
      foreach ($Data->Result() as $User) {
         echo $User->Name.'|'.Gdn_Format::Text($User->UserID)."\n";
      }
      $this->Render();
   }
	
   public function Browse($Keywords = '', $Page = '') {
      $this->View = 'index';
      $this->Index($Keywords, $Page);
   }

   public function Edit($UserID) {
      $this->Permission('Garden.Users.Edit');
      $this->AddJsFile('user.js');
      $this->Title(T('Edit User'));

      $this->AddSideMenu('dashboard/user');
      
      $this->CanEditUsername = TRUE;
      $this->CanEditUsername = $this->CanEditUsername & Gdn::Config("Garden.Profile.EditUsernames");
      $this->CanEditUsername = $this->CanEditUsername | Gdn::Session()->CheckPermission('Garden.Users.Edit');

      $RoleModel = new Gdn_Model('Role');
      $this->RoleData = $RoleModel->Get();

      $UserModel = new UserModel();
      $this->User = $UserModel->Get($UserID);

      // Set the model on the form.
      $this->Form->SetModel($UserModel);

      // Make sure the form knows which item we are editing.
      $this->Form->AddHidden('UserID', $UserID);

      if (!$this->Form->AuthenticatedPostBack()) {
         $this->Form->SetData($this->User);
         $this->UserRoleData = $UserModel->GetRoles($UserID);
      } else {
         if (!$this->CanEditUsername)
            $this->Form->SetFormValue("Name", $this->User->Name);
            
         // If a new password was specified, add it to the form's collection
         $ResetPassword = $this->Form->GetValue('ResetPassword', FALSE);
         $NewPassword = $this->Form->GetValue('NewPassword', '');
         if ($ResetPassword !== FALSE)
            $this->Form->SetFormValue('Password', $NewPassword);

         if ($this->Form->Save(array('SaveRoles' => TRUE)) !== FALSE) {
            if ($this->Form->GetValue('Password', '') != '')
               $UserModel->SendPasswordEmail($UserID, $NewPassword);

            $this->InformMessage(T('Your changes have been saved.'));
         }
         $this->UserRoleData = $this->Form->GetFormValue('RoleID');
      }

      $this->Render();
   }

	public function EmailAvailable($Email = '') {
		$this->_DeliveryType = DELIVERY_TYPE_BOOL;
      $Available = TRUE;

      if (C('Garden.Registration.EmailUnique', TRUE) && $Email != '') {
         $UserModel = Gdn::UserModel();
         if ($UserModel->GetByEmail($Email))
            $Available = FALSE;
      }
      if (!$Available)
         $this->Form->AddError(sprintf(T('%s unavailable'), T('Email')));
         
      $this->Render();
	}

   /**
    * @param Gdn_SQLDriver $SQL
    */
   protected function _GetFilter() {
      $Filter = $this->Request->Get('Filter');
      if ($Filter) {
         $Parts = explode(' ', $Filter, 3);
         if (count($Parts) < 2)
            return FALSE;
         
         $Field = $Parts[0];
         if (count($Parts) == 2) {
            $Op = '=';
            $FilterValue = $Parts[1];
         } else {
            $Op = $Parts[1];
            if (!in_array($Op, array('=', 'like'))) {
               $Op = '=';
            }
            $FilterValue = $Parts[2];
         }

         return array("$Field $Op" => $FilterValue);
      }
      return FALSE;
   }

   public function Decline($UserID = '', $PostBackKey = '') {
      $this->Permission('Garden.Users.Approve');
      $Session = Gdn::Session();
      if ($Session->ValidateTransientKey($PostBackKey)) {
         if ($this->HandleApplicant('Decline', $UserID))
            $this->InformMessage(T('Your changes have been saved.'));
      }

      if ($this->_DeliveryType == DELIVERY_TYPE_BOOL) {
         return $this->Form->ErrorCount() == 0 ? TRUE : $this->Form->Errors();
      } else {
         $this->Applicants();
      }
   }

   public function Delete($UserID = '', $Method = '') {
      $this->Permission('Garden.Users.Delete');
      $Session = Gdn::Session();
      if($Session->User->UserID == $UserID)
         trigger_error(ErrorMessage("You cannot delete the user you are logged in as.", $this->ClassName, 'FetchViewLocation'), E_USER_ERROR);
      $this->AddSideMenu('dashboard/user');
      $this->Title(T('Delete User'));

      $UserModel = new UserModel();
      $this->User = $UserModel->Get($UserID);
      $Method = in_array($Method, array('delete', 'keep', 'wipe')) ? $Method : '';
      $this->Method = $Method;
      if ($Method != '')
         $this->View = 'deleteconfirm';
         
      if ($this->Form->AuthenticatedPostBack() && $Method != '') {
         $UserModel->Delete($UserID, array('DeleteMethod' => $Method));
         $this->View = 'deletecomplete';
      }

      $this->Render();
   }

   private function HandleApplicant($Action, $UserID) {
      $this->Permission('Garden.Users.Approve');
      //$this->_DeliveryType = DELIVERY_TYPE_BOOL;
      if (!in_array($Action, array('Approve', 'Decline')) || !is_numeric($UserID)) {
         $this->Form->AddError('ErrorInput');
         $Result = FALSE;
      } else {
         $Session = Gdn::Session();
         $UserModel = new UserModel();
         if (is_numeric($UserID)) {
            try {
               $this->EventArguments['UserID'] = $UserID;
               $this->FireEvent("Before{$Action}User");
               
               $Email = new Gdn_Email();
               $Result = $UserModel->$Action($UserID, $Email);
               
               $this->FireEvent("After{$Action}User");
            } catch(Exception $ex) {
               $Result = FALSE;
               $this->Form->AddError(strip_tags($ex->getMessage()));
            }
         }
      }
   }

   public function Initialize() {
      parent::Initialize();
      if ($this->Menu)
         $this->Menu->HighlightRoute('/dashboard/settings');
   }
	
	public function UsernameAvailable($Name = '') {
      $this->_DeliveryType = DELIVERY_TYPE_BOOL;
      $Available = TRUE;
      if ($Name != '') {
         $UserModel = Gdn::UserModel();
         if ($UserModel->GetByUsername($Name))
            $Available = FALSE;
      }
      if (!$Available)
         $this->Form->AddError(sprintf(T('%s unavailable'), T('Name')));
         
      $this->Render();
   }
   
   /**
    * Convenience function for listing users. At time of this writing, it is
    * being used by wordpress widgets to display recently active users.
    *
    * @param string $SortField The field to sort users with. Defaults to DateLastActive. Other options are: DateInserted, Name.
    * @param string $SortDirection The direction to sort the users.
    * @param int $Limit The number of users to show.
    * @param int $Offset The offset to start listing users at.
    */
   public function Summary($SortField = 'DateLastActive', $SortDirection = 'desc', $Limit = 30, $Offset = 0) {
      $this->Title(T('User Summary'));

      // Input Validation
      $SortField = !in_array($SortField, array('DateLastActive', 'DateInserted', 'Name')) ? 'DateLastActive' : $SortField;
      $SortDirection = $SortDirection == 'asc' ? 'asc' : 'desc';
      $Limit = is_numeric($Limit) && $Limit < 100 && $Limit > 0 ? $Limit : 30;
      $Offset = is_numeric($Offset) ? $Offset : 0;

      $UserModel = new UserModel();
      $UserData = $UserModel->GetSummary('u.'.$SortField, $SortDirection, $Limit, $Offset);
      $this->SetData('UserData', $UserData);
      $this->MasterView = 'empty';
      $this->Render('filenotfound', 'home');
   }
}