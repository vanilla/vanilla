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
 * User Controller
 *
 * @package Dashboard
 */
 
/**
 * Manage users.
 *
 * @since 2.0.0
 * @package Dashboard
 */
class UserController extends DashboardController {
   /** @var array Models to automatically instantiate. */
   public $Uses = array('Database', 'Form');
   
   /**
    * @var Gdn_Form 
    */
   public $Form;
   
   /**
    * Highlight menu path. Automatically run on every use.
    *
    * @since 2.0.0
    * @access public
    */
   public function Initialize() {
      parent::Initialize();
      Gdn_Theme::Section('Dashboard');
      if ($this->Menu)
         $this->Menu->HighlightRoute('/dashboard/settings');
   }
   
   /**
    * User management list.
    *
    * @since 2.0.0
    * @access public
    * @param mixed $Keywords Term or array of terms to filter list of users.
    * @param int $Page Page number.
    * @param string $Order Sort order for list.
    */
   public function Index($Keywords = '', $Page = '', $Order = '') {
      $this->Permission(
         array(
            'Garden.Users.Add',
            'Garden.Users.Edit',
            'Garden.Users.Delete'
         ),
         '',
         FALSE
      );
      
      // Page setup
      $this->AddJsFile('jquery.gardenmorepager.js');
      $this->AddJsFile('user.js');
      $this->Title(T('Users'));
      $this->AddSideMenu('dashboard/user');
      
      // Form setup
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
      
      // Sorting
      if (in_array($Order, array('DateInserted','DateFirstVisit', 'DateLastActive'))) {
         $Order = 'u.'.$Order;
         $OrderDir = 'desc';
      } else {
         $Order = 'u.Name';
         $OrderDir = 'asc';
      }

      // Get user list
      $this->UserData = $UserModel->Search($Filter, $Order, $OrderDir, $Limit, $Offset);
      $this->SetData('Users', $this->UserData);
      RoleModel::SetUserRoles($this->UserData->Result());
      
      // Deliver json data if necessary
      if ($this->_DeliveryType != DELIVERY_TYPE_ALL && $this->_DeliveryMethod == DELIVERY_METHOD_XHTML) {
         $this->SetJson('LessRow', $this->Pager->ToString('less'));
         $this->SetJson('MoreRow', $this->Pager->ToString('more'));
         $this->View = 'users';
      }

      $this->Render();
   }
   
   /**
    * Create a user.
    *
    * @since 2.0.0
    * @access public
    */
   public function Add() {
      $this->Permission('Garden.Users.Add');
      
      // Page setup
      $this->AddJsFile('user.js');
      $this->Title(T('Add User'));
      $this->AddSideMenu('dashboard/user');
      
      $RoleModel = new RoleModel();
      $AllRoles = $RoleModel->GetArray();
      
      // By default, people with access here can freely assign all roles
      $this->RoleData = $AllRoles;
      
      $UserModel = new UserModel();
      $this->User = FALSE;

      // Set the model on the form.
      $this->Form->SetModel($UserModel);
      
      try {
         // These are all the 'effective' roles for this add action. This list can
         // be trimmed down from the real list to allow subsets of roles to be edited.
         $this->EventArguments['RoleData'] = &$this->RoleData;

         $this->FireEvent("BeforeUserAdd");

         if ($this->Form->AuthenticatedPostBack()) {

            // These are the new roles the creating user wishes to apply to the target
            // user, adjusted for his ability to affect those roles
            $RequestedRoles = $this->Form->GetFormValue('RoleID');

            if (!is_array($RequestedRoles)) $RequestedRoles = array();
            $RequestedRoles = array_flip($RequestedRoles);
            $UserNewRoles = array_intersect_key($this->RoleData, $RequestedRoles);

            // Put the data back into the forum object as if the user had submitted 
            // this themselves
            $this->Form->SetFormValue('RoleID', array_keys($UserNewRoles));

            $NewUserID = $this->Form->Save(array('SaveRoles' => TRUE, 'NoConfirmEmail' => TRUE));
            if ($NewUserID !== FALSE) {
               $Password = $this->Form->GetValue('Password', '');
               $UserModel->SendWelcomeEmail($NewUserID, $Password, 'Add');
               $this->InformMessage(T('The user has been created successfully'));
               $this->RedirectUrl = Url('dashboard/user');
            }

            $this->UserRoleData = $UserNewRoles;
         } else {
            // Set the default roles.
            $this->UserRoleData = C('Garden.Registration.DefaultRoles', array());
         }

      } catch (Exception $Ex) {
         $this->Form->AddError($Ex);
      }
      $this->Render();
   }
   
   /**
    * Show how many applicants are in the queue.
    *
    * @since 2.0.0
    * @access public
    */
   public function ApplicantCount() {
      $this->Permission('Garden.Users.Approve');
      $RoleModel = new RoleModel();
      $Count = $RoleModel->GetApplicantCount();
      if ($Count > 0)
         echo '<span class="Alert">', $Count, '</span>';
   }
	
	/**
    * Show applicants queue.
    *
    * @since 2.0.0
    * @access public
    */
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
   
   /**
    * Approve a user application.
    *
    * @since 2.0.0
    * @access public
    * @param int $UserID Unique ID.
    * @param string $TransientKey Security token.
    */
	public function Approve($UserID = '', $TransientKey = '') {
      $this->Permission('Garden.Users.Approve');
      $Session = Gdn::Session();
      if ($Session->ValidateTransientKey($TransientKey)) {
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
	
	/**
    * Autocomplete a username.
    *
    * @since 2.0.0
    * @access public
    */
   public function AutoComplete() {
      $this->DeliveryType(DELIVERY_TYPE_NONE);
      $Q = GetIncomingValue('q');
      $UserModel = new UserModel();
      $Data = $UserModel->GetLike(array('u.Name' => $Q), 'u.Name', 'asc', 10, 0);
      foreach ($Data->Result() as $User) {
         echo htmlspecialchars($User->Name).'|'.Gdn_Format::Text($User->UserID)."\n";
      }
      $this->Render();
   }
   
   /**
    * Ban a user and optionally delete their content.
    * @since 2.1
    * @param type $UserID 
    */
   public function Ban($UserID, $Unban = FALSE) {
      $this->Permission('Garden.Moderation.Manage');
      
      $User = Gdn::UserModel()->GetID($UserID, DATASET_TYPE_ARRAY);
      if (!$User)
         throw NotFoundException($User);
      
//      $this->Form = new Gdn_Form();
      
      $UserModel = Gdn::UserModel();
      
      if ($this->Form->AuthenticatedPostBack()) {
         if ($Unban) {
            $UserModel->Unban($UserID, array('RestoreContent' => $this->Form->GetFormValue('RestoreContent')));
         } else {
            if (!ValidateRequired($this->Form->GetFormValue('Reason'))) {
               $this->Form->AddError('ValidateRequired', 'Reason');
            }
            if ($this->Form->GetFormValue('Reason') == 'Other' && !ValidateRequired($this->Form->GetFormValue('ReasonText'))) {
               $this->Form->AddError('ValidateRequired', 'Reason Text');
            }

            if ($this->Form->ErrorCount() == 0) {
               if ($this->Form->GetFormValue('Reason') == 'Other')
                  $Reason = $this->Form->GetFormValue('ReasonText');
               else
                  $Reason = $this->Form->GetFormValue('Reason');

               $UserModel->Ban($UserID, array('Reason' => $Reason, 'DeleteContent' => $this->Form->GetFormValue('DeleteContent')));
            }
         }
         
         if ($this->Form->ErrorCount() == 0) {
            // Redirect after a successful save.
            if ($this->Request->Get('Target')) {
               $this->RedirectUrl = $this->Request->Get('Target');
            } else {
               $this->RedirectUrl = Url(UserUrl($User));
            }
         }
      }
      
      $this->SetData('User', $User);
      $this->AddSideMenu();
      $this->Title($Unban ? T('Unban User') : T('Ban User'));
      if ($Unban)
         $this->View = 'Unban';
      $this->Render();
   }
	
	/**
    * Page thru user list.
    *
    * @since 2.0.0
    * @access public
    * @param mixed $Keywords Term or list of terms to limit search.
    * @param int $Page Page number.
    * @param string $Order Sort order.
    */
   public function Browse($Keywords = '', $Page = '', $Order = '') {
      $this->View = 'index';
      $this->Index($Keywords, $Page, $Order = '');
   }
   
   /**
    * Decline a user application.
    *
    * @since 2.0.0
    * @access public
    * @param int $UserID Unique ID.
    * @param string $TransientKey Security token.
    */
   public function Decline($UserID = '', $TransientKey = '') {
      $this->Permission('Garden.Users.Approve');
      $Session = Gdn::Session();
      if ($Session->ValidateTransientKey($TransientKey)) {
         if ($this->HandleApplicant('Decline', $UserID))
            $this->InformMessage(T('Your changes have been saved.'));
      }

      if ($this->_DeliveryType == DELIVERY_TYPE_BOOL) {
         return $this->Form->ErrorCount() == 0 ? TRUE : $this->Form->Errors();
      } else {
         $this->Applicants();
      }
   }
   
   /**
    * Delete a user account.
    *
    * @since 2.0.0
    * @access public
    * @param int $UserID Unique ID.
    * @param string $Method Type of deletion to do (delete, keep, or wipe).
    */
   public function Delete($UserID = '', $Method = '') {
      $this->Permission('Garden.Users.Delete');
      $Session = Gdn::Session();
      if($Session->User->UserID == $UserID)
         trigger_error(ErrorMessage("You cannot delete the user you are logged in as.", $this->ClassName, 'FetchViewLocation'), E_USER_ERROR);
      $this->AddSideMenu('dashboard/user');
      $this->Title(T('Delete User'));

      $RoleModel = new RoleModel();
      $AllRoles = $RoleModel->GetArray();
      
      // By default, people with access here can freely assign all roles
      $this->RoleData = $AllRoles;
      
      $UserModel = new UserModel();
      $this->User = $UserModel->GetID($UserID);
      
      try {
         
         $CanDelete = TRUE;
         $this->EventArguments['CanDelete'] = &$CanDelete;
         $this->EventArguments['TargetUser'] = &$this->User;
         
         // These are all the 'effective' roles for this delete action. This list can
         // be trimmed down from the real list to allow subsets of roles to be
         // edited.
         $this->EventArguments['RoleData'] = &$this->RoleData;
         
         $UserRoleData = $UserModel->GetRoles($UserID)->ResultArray();
         $RoleIDs = ConsolidateArrayValuesByKey($UserRoleData, 'RoleID');
         $RoleNames = ConsolidateArrayValuesByKey($UserRoleData, 'Name');
         $this->UserRoleData = ArrayCombine($RoleIDs, $RoleNames);
         $this->EventArguments['UserRoleData'] = &$this->UserRoleData;
         
         $this->FireEvent("BeforeUserDelete");
         $this->SetData('CanDelete', $CanDelete);
         
         $Method = in_array($Method, array('delete', 'keep', 'wipe')) ? $Method : '';
         $this->Method = $Method;
         if ($Method != '')
            $this->View = 'deleteconfirm';

         if ($this->Form->AuthenticatedPostBack() && $Method != '') {
            $UserModel->Delete($UserID, array('DeleteMethod' => $Method));
            $this->View = 'deletecomplete';
         }

      } catch (Exception $Ex) {
         $this->Form->AddError($Ex);
      }
      $this->Render();
   }
   
   public function Delete2() {
      $this->Permission('Garden.Users.Delete');
      
      if (!Gdn::Request()->IsPostBack())
         throw new Exception('Requires POST', 405);
      
      $this->Form->ValidateRule('UserID', 'ValidateRequired');
      $DeleteType = $this->Form->GetFormValue('DeleteMethod');
      if (!in_array($DeleteType, array('delete', 'keep', 'wipe'))) {
         $this->Form->AddError(T('DeleteMethod must be one of: delete, keep, wipe.'));
      }
      
      $UserID = $this->Form->GetFormValue('UserID');
      
      $User = Gdn::UserModel()->GetID($UserID, DATASET_TYPE_ARRAY);
      if ($UserID && !$User)
         throw NotFoundException('User');
      
      if ($User['Admin'] == 2)
         $this->Form->AddError(T('You cannot delete a system-created user.'));
      elseif ($User['Admin'])
         $this->Form->AddError(T('You cannot delete a super-admin.'));
      
      if ($this->Form->ErrorCount() == 0) {
         Gdn::UserModel()->Delete($UserID, array(
            'DeleteMethod' => $this->Form->GetFormValue('DeleteMethod'),
            'Log' => TRUE));
         $this->SetData('Result', sprintf(T('%s was deleted.'), $User['Name']));
      }
      $this->Render('Blank', 'Utility');
   }
   
   public function DeleteContent($UserID) {
      $this->Permission('Garden.Moderation.Manage');
      
      $User = Gdn::UserModel()->GetID($UserID);
      if (!$User)
         throw NotFoundException('User');
      
      if ($this->Form->AuthenticatedPostBack()) {
         Gdn::UserModel()->DeleteContent($UserID, array('Log' => TRUE));

         if ($this->Request->Get('Target')) {
            $this->RedirectUrl = Url($this->Request->Get('Target'));
         } else {
            $this->RedirectUrl = Url(UserUrl($User));
         }
      } else {
         $this->SetData('Title', T('Are you sure you want to do this?'));
      }
      
      $this->SetData('User', $User);
      $this->Render();
   }
   
   /**
    * Edit a user account.
    *
    * @since 2.0.0
    * @access public
    * @param int $UserID Unique ID.
    */
   public function Edit($UserID) {
      $this->Permission('Garden.Users.Edit');
      
      // Page setup
      $this->AddJsFile('user.js');
      $this->Title(T('Edit User'));
      $this->AddSideMenu('dashboard/user');
      
      // Determine if username can be edited
      $this->CanEditUsername = TRUE;
      $this->CanEditUsername = $this->CanEditUsername & Gdn::Config("Garden.Profile.EditUsernames");
      $this->CanEditUsername = $this->CanEditUsername | Gdn::Session()->CheckPermission('Garden.Users.Edit');

      $RoleModel = new RoleModel();
      $AllRoles = $RoleModel->GetArray();
      
      // By default, people with access here can freely assign all roles
      $this->RoleData = $AllRoles;

      $UserModel = new UserModel();
      $this->User = $UserModel->GetID($UserID);

      // Set the model on the form.
      $this->Form->SetModel($UserModel);

      // Make sure the form knows which item we are editing.
      $this->Form->AddHidden('UserID', $UserID);

      try {
         
         $AllowEditing = TRUE;
         $this->EventArguments['AllowEditing'] = &$AllowEditing;
         $this->EventArguments['TargetUser'] = &$this->User;
         
         // These are all the 'effective' roles for this edit action. This list can
         // be trimmed down from the real list to allow subsets of roles to be
         // edited.
         $this->EventArguments['RoleData'] = &$this->RoleData;
         
         $UserRoleData = $UserModel->GetRoles($UserID)->ResultArray();
         $RoleIDs = ConsolidateArrayValuesByKey($UserRoleData, 'RoleID');
         $RoleNames = ConsolidateArrayValuesByKey($UserRoleData, 'Name');
         $this->UserRoleData = ArrayCombine($RoleIDs, $RoleNames);
         $this->EventArguments['UserRoleData'] = &$this->UserRoleData;
         
         $this->FireEvent("BeforeUserEdit");
         $this->SetData('AllowEditing', $AllowEditing);
         
         if (!$this->Form->AuthenticatedPostBack()) {
            $this->Form->SetData($this->User);
            
         } else {
            if (!$this->CanEditUsername)
               $this->Form->SetFormValue("Name", $this->User->Name);
            
            // If a new password was specified, add it to the form's collection
            $ResetPassword = $this->Form->GetValue('ResetPassword', FALSE);
            $NewPassword = $this->Form->GetValue('NewPassword', '');
            if ($ResetPassword == 'Manual') 
               $this->Form->SetFormValue('Password', $NewPassword);
            
            // Role changes
            
            // These are the new roles the editing user wishes to apply to the target
            // user, adjusted for his ability to affect those roles
            $RequestedRoles = $this->Form->GetFormValue('RoleID');
            
            if (!is_array($RequestedRoles)) $RequestedRoles = array();
            $RequestedRoles = array_flip($RequestedRoles);
            $UserNewRoles = array_intersect_key($this->RoleData, $RequestedRoles);
            
            // These roles will stay turned on regardless of the form submission contents 
            // because the editing user does not have permission to modify them
            $ImmutableRoles = array_diff_key($AllRoles, $this->RoleData);
            $UserImmutableRoles = array_intersect_key($ImmutableRoles, $this->UserRoleData);
            
            // Apply immutable roles
            foreach ($UserImmutableRoles as $IMRoleID => $IMRoleName)
               $UserNewRoles[$IMRoleID] = $IMRoleName;
            
            // Put the data back into the forum object as if the user had submitted 
            // this themselves
            $this->Form->SetFormValue('RoleID', array_keys($UserNewRoles));
            
            if ($this->Form->Save(array('SaveRoles' => TRUE)) !== FALSE) {
               if ($this->Form->GetValue('ResetPassword', '') == 'Auto') {
                  $UserModel->PasswordRequest($this->User->Email);
                  $UserModel->SetField($UserID, 'HashMethod', 'Reset'); 
               }

               $this->InformMessage(T('Your changes have been saved.'));
            }
            
            $this->UserRoleData = $UserNewRoles;
         }
      } catch (Exception $Ex) {
         $this->Form->AddError($Ex);
      }
      
      $this->Render();
   }
   
   /**
    * Determine whether user can register with this email address.
    *
    * @since 2.0.0
    * @access public
    * @param string $Email Email address to be checked.
    */
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
    * Get filter from current request.
    *
    * @since 2.0.0
    * @access protected
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
         
         if (!in_array($Field, array('InsertIPAddress', 'RankID', 'DateFirstVisit', 'DateLastVisit')))
            return FALSE;

         return array("$Field $Op" => $FilterValue);
      }
      return FALSE;
   }
   
   /**
    * Handle a user application.
    *
    * @since 2.0.0
    * @access private
    * @see self::Decline, self::Approve
    * @param string $Action Approve or Decline.
    * @param int $UserID Unique ID.
    */
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
               
               // Re-calculate applicant count
               $RoleModel = new RoleModel();
               $RoleModel->GetApplicantCount(TRUE);
               
               $this->FireEvent("After{$Action}User");
            } catch(Exception $ex) {
               $Result = FALSE;
               $this->Form->AddError(strip_tags($ex->getMessage()));
            }
         }
      }
   }
   
   public function Merge() {
      $this->Permission('Garden.Settings.Manage');
      
      // This must be a postback.
      if (!$this->Request->IsAuthenticatedPostBack()) {
         throw ForbiddenException('GET');
      }
      
      $Validation = new Gdn_Validation();
      $Validation->ApplyRule('OldUserID', 'ValidateRequired');
      $Validation->ApplyRule('NewUserID', 'ValidateRequired');
      if ($Validation->Validate($this->Request->Post())) {
         $Result = Gdn::UserModel()->Merge(
            $this->Request->Post('OldUserID'),
            $this->Request->Post('NewUserID'));
         $this->SetData($Result);
      } else {
         $this->Form->SetValidationResults($Validation->Results());
      }
      $this->Render('Blank', 'Utility');
   }

   /**
    * Build URL to order users by value passed.
    *
    * @since 2.0.0
    * @access protected
    * @param string $Field Column to order users by.
    * @return string URL of user list with orderby query appended.
    */
   protected function _OrderUrl($Field) {
      $Get = Gdn::Request()->Get();
      $Get['order'] = $Field;
      return '/dashboard/user?'.http_build_query($Get);
   }	
   
   /**
    * Convenience function for listing users. At time of this writing, it is
    * being used by wordpress widgets to display recently active users.
    *
    * @since 2.0.?
    * @access public
    * @param string $SortField The field to sort users with. Defaults to DateLastActive. Other options are: DateInserted, Name.
    * @param string $SortDirection The direction to sort the users.
    * @param int $Limit The number of users to show.
    * @param int $Offset The offset to start listing users at.
    */
   public function Summary($SortField = 'DateLastActive', $SortDirection = 'desc', $Limit = 30, $Offset = 0) {
      $this->Title(T('User Summary'));

      // Input validation
      $SortField = !in_array($SortField, array('DateLastActive', 'DateInserted', 'Name')) ? 'DateLastActive' : $SortField;
      $SortDirection = $SortDirection == 'asc' ? 'asc' : 'desc';
      $Limit = is_numeric($Limit) && $Limit < 100 && $Limit > 0 ? $Limit : 30;
      $Offset = is_numeric($Offset) ? $Offset : 0;
      
      // Get user list
      $UserModel = new UserModel();
      $UserData = $UserModel->GetSummary('u.'.$SortField, $SortDirection, $Limit, $Offset);
      $this->SetData('UserData', $UserData);
      
      $this->MasterView = 'empty';
      $this->Render('filenotfound', 'home');
   }
   
   public function Save() {
      $this->Permission('Garden.Users.Edit');
      if (!Gdn::Request()->IsPostBack())
         throw new Exception('Requires POST', 405);
      
      $Form = new Gdn_Form();
      
      if ($SSOString = $Form->GetFormValue('SSOString')) {
         $Parts = explode(' ', $SSOString);
         $String = $Parts[0];
         $Data = json_decode(base64_decode($String), TRUE);
         $User = ArrayTranslate($Data, array('name' => 'Name', 'email' => 'Email', 'photourl' => 'Photo', 'client_id' => 'ClientID', 'uniqueid' => 'UniqueID'));
      } else {
         $User = $Form->FormValues();
      }
      
      if (!isset($User['UserID']) && isset($User['UniqueID'])) {
         // Try and find the user based on SSO.
         $Auth = Gdn::UserModel()->GetAuthentication($User['UniqueID'], $User['ClientID']);
         if ($Auth)
            $User['UserID'] = $Auth['UserID'];
      }
      
      if (!isset($User['UserID'])) {
         // Add some default values to make saving easier.
         if (!isset($User['RoleID'])) {
            $DefaultRoles = C('Garden.Registration.DefaultRoles', array());
            $User['RoleID'] = $DefaultRoles;
         }
         
         if (!isset($User['Password'])) {
            $User['Password'] = md5(microtime());
            $User['HashMethod'] = 'Random';
         }
      }
      
      $UserID = Gdn::UserModel()->Save($User, array('SaveRoles' => isset($User['RoleID']), 'NoConfirmEmail' => TRUE));
      if ($UserID) {
         if (!isset($User['UserID']))
            $User['UserID'] = $UserID;

         if (isset($User['ClientID']) && isset($User['UniqueID'])) {
            Gdn::UserModel()->SaveAuthentication(array(
               'UserID' => $User['UserID'],
               'Provider' => $User['ClientID'],
               'UniqueID' => $User['UniqueID']
            ));
         }
         
         $this->SetData('User', $User);
      } else {
         throw new Gdn_UserException(Gdn::UserModel()->Validation->ResultsText());
      }
      
      $this->Render('Blank', 'Utility');
   }
   
   public function SSO($UserID = FALSE) {
      $this->Permission('Garden.Users.Edit');
      
      $ProviderModel = new Gdn_AuthenticationProviderModel();
      
      $Form = new Gdn_Form();
      
      if ($this->Request->IsPostBack()) {
         // Make sure everything has been posted.
         $Form->ValidateRule('ClientID', 'ValidateRequired');
         $Form->ValidateRule('UniqueID', 'ValidateRequired');
         
         if (!ValidateRequired($Form->GetFormValue('Username')) && !ValidateRequired($Form->GetFormValue('Email'))) {
            $Form->AddError('Username or Email is required.');
         }
         
         $Form->ValidateRule('Password', 'ValidateRequired');
         
         $Provider = $ProviderModel->GetProviderByKey($Form->GetFormValue('ClientID'));
         if (!$Provider) {
            $Form->AddError(sprintf('%1$s "%2$s" not found.', T('Provider'), $Form->GetFormValue('ClientID')));
         }
         
         if ($Form->ErrorCount() > 0) {
            throw new Gdn_UserException($Form->ErrorString());
         }
         
         // Grab the user.
         $User = FALSE;
         if ($Email = $Form->GetFormValue('Email')) {
            $User = Gdn::UserModel()->GetByEmail($Email);
         }
         if (!$User && ($Username = $Form->GetFormValue('Username'))) {
            $User = Gdn::UserModel()->GetByUsername($Username);
         }
         if (!$User) {
            throw new Gdn_UserException(sprintf(T('User not found.'), strtolower(T(UserModel::SigninLabelCode()))), 404);
         }
         
         // Valide the user's password.
         $PasswordHash = new Gdn_PasswordHash();
         $Password = $this->Form->GetFormValue('Password');
         if (!$PasswordHash->CheckPassword($Password, GetValue('Password', $User), GetValue('HashMethod', $User))) {
            throw new Gdn_UserException(T('Invalid password.'), 401);
         }
         
         // Okay. We've gotten this far. Let's save the authentication.
         $User = (array)$User;
         
         Gdn::UserModel()->SaveAuthentication(array(
            'UserID' => $User['UserID'],
            'Provider' => $Form->GetFormValue('ClientID'),
            'UniqueID' => $Form->GetFormValue('UniqueID')
         ));
         
         $Row = Gdn::UserModel()->GetAuthentication($Form->GetFormValue('UniqueID'), $Form->GetFormValue('UniqueID'));
         
         if ($Row) {
            $this->SetData('Result', $Row);
         } else {
            throw new Gdn_UserException(T('There was an error saving the data.'));
         }
      } else {
         $User = Gdn::UserModel()->GetID($UserID);
         if (!$User)
            throw NotFoundException('User');
         
         $Result = Gdn::SQL()
               ->Select('ua.ProviderKey', '', 'ClientID')
               ->Select('ua.ForeignUserKey', '', 'UniqueID')
               ->Select('ua.UserID')
               ->Select('p.Name')
               ->Select('p.AuthenticationSchemeAlias', '', 'Type')
               ->From('UserAuthentication ua')
               ->Join('UserAuthenticationProvider p', 'ua.ProviderKey = p.AuthenticationKey')
               ->Where('UserID', $UserID)
               ->Get()->ResultArray();
         
         $this->SetData('Result', $Result);
      }
      
      $this->Render('Blank', 'Utility', 'Dashboard');
   }
   
   /**
    * Determine whether user can register with this username.
    *
    * @since 2.0.0
    * @access public
    * @param string $Name Username to be checked.
    */
   public function UsernameAvailable($Name = '') {
      $this->_DeliveryType = DELIVERY_TYPE_BOOL;
      $Available = TRUE;
      if (C('Garden.Registration.NameUnique', TRUE) && $Name != '') {
         $UserModel = Gdn::UserModel();
         if ($UserModel->GetByUsername($Name))
            $Available = FALSE;
      }
      if (!$Available)
         $this->Form->AddError(sprintf(T('%s unavailable'), T('Name')));
         
      $this->Render();
   }
   
   public function Verify($UserID, $Verified) {
      $this->Permission('Garden.Moderation.Manage');
      
      if (!$this->Request->IsAuthenticatedPostBack()) {
         throw PermissionException('Javascript');
      }
      
      // First, set the field value.
      Gdn::UserModel()->SetField($UserID, 'Verified', $Verified);
      
      $User = Gdn::UserModel()->GetID($UserID);
      if (!$User)
         throw NotFoundException('User');
      
      // Send back the verified button.
      require_once $this->FetchViewLocation('helper_functions', 'Profile', 'Dashboard');
      $this->JsonTarget('.User-Verified', UserVerified($User), 'ReplaceWith');
      
      $this->Render('Blank', 'Utility', 'Dashboard');
   }
}