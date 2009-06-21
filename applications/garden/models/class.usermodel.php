<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Mark O'Sullivan
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Mark O'Sullivan at mark [at] lussumo [dot] com
*/

class Gdn_UserModel extends Model {
   /// <summary>
   /// Class constructor. Defines the related database table name.
   /// </summary>
   /// <param name="Name" type="string" required="false" default="get_class($this)">
   /// An optional parameter that allows you to explicitly define the name of
   /// the table that this model represents. You can also explicitly set this
   /// value with $this->Name.
   /// </param>
   public function __construct() {
      parent::__construct('User');
   }

   public function UserQuery() {
      $this->SQL->Select('u.*')
         ->Select('p.Name', '', 'Photo')
         ->Select('i.Name', '', 'InviteName')
         ->From('User u')
         ->Join('Photo as p', 'u.PhotoID = p.PhotoID', 'left')
         ->Join('User as i', 'u.InviteUserID = i.UserID', 'left');
   }

   public function DefinePermissions($UserID) {
      $DataSet = $this->SQL->Select('p.Name, rp.JunctionID')
         ->From('Permission p')
         ->Join('RolePermission rp', 'p.PermissionID = rp.PermissionID')
         ->Join('UserRole ur', 'rp.RoleID = ur.RoleID')
         ->Where('ur.UserID', $UserID)
         ->GroupBy('p.Name, rp.JunctionID')
         ->Get();

      $Permissions = array();
      if ($DataSet->NumRows() > 0) {
         foreach ($DataSet->Result() as $Permission) {
            if (is_numeric($Permission->JunctionID) && $Permission->JunctionID > 0)
               $Permissions[$Permission->Name][] = $Permission->JunctionID;
            else
               $Permissions[] = $Permission->Name;
         }
      }
      // Throw a fatal error if the user has no permissions
      // if (count($Permissions) == 0)
      //    trigger_error(ErrorMessage('The requested user ('.$this->UserID.') has no permissions.', 'Session', 'Start'), E_USER_ERROR);

      // Save the permissions to the user table
      $Permissions = Format::Serialize($Permissions);
      if ($UserID > 0)
         $this->SQL->Put('User', array('Permissions' => $Permissions), array('UserID' => $UserID));

      return $Permissions;
   }

   public function Get($UserReference) {
      $this->UserQuery();
      if (is_numeric($UserReference))
         return $this->SQL->Where('u.UserID', $UserReference)->Get()->FirstRow();
      else
         return $this->SQL->Where('u.Name', $UserReference)->Get()->FirstRow();
   }
   
   /*
    * Returns all users inthe applicant role
    */
   public function GetApplicants() {
      return $this->SQL->Select('u.*')
         ->From('User u')
         ->Join('UserRole ur', 'u.UserID = ur.UserID')
         ->Where('ur.RoleID', '3', TRUE, FALSE) // 3 is Applicant RoleID
         ->GroupBy('UserID')
         ->OrderBy('DateInserted', 'desc')
         ->Get();
   }

   public function GetCountLike($Like = FALSE) {
      $this->SQL
         ->Select('u.UserID', 'count', 'UserCount')
         ->From('User u')
         ->Join('UserRole ur', 'u.UserID = ur.UserID', 'left');
      if (is_array($Like))
         $this->SQL->Like($Like, '', 'right');

      $Data = $this->SQL
         ->BeginWhereGroup()
         ->Where('ur.RoleID is null')
         ->OrWhere('ur.RoleID <>', '3', TRUE, FALSE) // 3 is Applicant RoleID
         ->EndWhereGroup()
         ->Get()
         ->FirstRow();

      return $Data === FALSE ? 0 : $Data->UserCount;
   }

   public function GetLike($Like = FALSE, $OrderFields = '', $OrderDirection = 'asc', $Limit = FALSE, $Offset = FALSE) {
      $this->UserQuery();
      $this->SQL
         ->Join('UserRole ur', 'u.UserID = ur.UserID', 'left');

      if (is_array($Like))
         $this->SQL->Like($Like, '', 'right');

      return $this->SQL
         ->BeginWhereGroup()
         ->Where('ur.RoleID is null')
         ->OrWhere('ur.RoleID <>', '3', TRUE, FALSE) // 3 is Applicant RoleID
         ->EndWhereGroup()
         ->GroupBy('u.UserID')
         ->OrderBy($OrderFields, $OrderDirection)
         ->Limit($Limit, $Offset)
         ->Get();
   }

   public function GetRoles($UserID) {
      return $this->SQL->Select('r.RoleID, r.Name')
         ->From('UserRole ur')
         ->Join('Role r', 'ur.RoleID = r.RoleID')
         ->Where('ur.UserID', $UserID)
         ->Get();
   }

   public function GetSession($UserID) {
      $this->SQL
         ->Select('u.UserID, u.Name, u.Preferences, u.Permissions, u.Attributes, u.HourOffset, u.CountNotifications, u.Admin')
         ->Select('p.Name', '', 'Photo')
         ->From('User u')
         ->Join('Photo as p', 'u.PhotoID = p.PhotoID', 'left')
         ->Where('UserID', $UserID);

      $this->FireEvent('SessionQuery');

      $User = $this->SQL
         ->Get()
         ->FirstRow();

      if ($User && $User->Permissions == '')
         $User->Permissions = $this->DefinePermissions($UserID);

      return $User;
   }
   
   public function RemovePicture($UserID) {
      $this->SQL
         ->Update('User')
         ->Set('PhotoID', 'null', FALSE)
         ->Where('UserID', $UserID)
         ->Put();
   }

   /// <summary>
   /// Generic save procedure.
   /// </summary>
   public function Save($FormPostValues, $Settings = FALSE) {
      // See if the user's related roles should be saved or not.
      $SaveRoles = ArrayValue('SaveRoles', $Settings);

      // Define the primary key in this model's table.
      $this->DefineSchema();

      // Add & apply any extra validation rules:
      if (array_key_exists('Email', $FormPostValues))
         $this->Validation->ApplyRule('Email', 'Email');

      // Custom Rule: This will make sure that at least one role was selected if saving roles for this user.
      if ($SaveRoles) {
         $this->Validation->AddRule('OneOrMoreArrayItemRequired', 'function:ValidateOneOrMoreArrayItemRequired');
         // $this->Validation->AddValidationField('RoleID', $FormPostValues);
         $this->Validation->ApplyRule('RoleID', 'OneOrMoreArrayItemRequired');
      }

      // Make sure that the checkbox val for email is saved as the appropriate enum
      if (array_key_exists('ShowEmail', $FormPostValues))
         $FormPostValues['ShowEmail'] = ForceBool($FormPostValues['ShowEmail'], '0', '1', '0');

      // Validate the form posted values
      $UserID = ArrayValue('UserID', $FormPostValues);
      $Insert = $UserID > 0 ? FALSE : TRUE;
      if ($Insert) {
         $this->AddInsertFields($FormPostValues);
      } else {
         $this->AddUpdateFields($FormPostValues);
      }

      $RecordRoleChange = TRUE;
      if ($this->Validate($FormPostValues, $Insert) === TRUE) {
         $Fields = $this->Validation->ValidationFields(); // All fields on the form that need to be validated (including non-schema field rules defined above)
         $RoleIDs = ArrayValue('RoleID', $Fields, 0);
         $Username = ArrayValue('Name', $Fields);
         $Email = ArrayValue('Email', $Fields);
         $Fields = $this->Validation->SchemaValidationFields(); // Only fields that are present in the schema
         // Remove the primary key from the fields collection before saving
         $Fields = RemoveKeyFromArray($Fields, $this->PrimaryKey);
         // Make sure to encrypt the password for saving...
         if (array_key_exists('Password', $Fields)) {
            $PasswordHash = new GardenPasswordHash();
            $Fields['Password'] = $PasswordHash->HashPassword($Fields['Password']);
         }

         // If the primary key exists in the validated fields and it is a
         // numeric value greater than zero, update the related database row.
         if ($UserID > 0) {
            // Make sure the username & email aren't already being used (by someone other than this user)
            if (!$this->ValidateUniqueFields($Username, $Email, $UserID))
               return FALSE;

            $this->SQL->Put($this->Name, $Fields, array($this->PrimaryKey => $UserID));

            // Record activity if the person changed his/her photo
            $Photo = ArrayValue('Photo', $FormPostValues);
            if ($Photo !== FALSE)
               AddActivity($UserID, 'PictureChange', '<img src="'.Url('uploads/t'.$Photo).'" />');

         } else {
            $RecordRoleChange = FALSE;
            if (!$this->ValidateUniqueFields($Username, $Email))
               return FALSE;

            // Define the other required fields:
            $Fields['Email'] = $Email;

            // And insert the new user
            $UserID = $this->SQL->Insert($this->Name, $Fields);

            // Make sure that the user is assigned to one or more roles:
            $SaveRoles = TRUE;

            // Report that the user was created
            $Session = Gdn::Session();
            AddActivity(
               $UserID,
               'JoinCreated',
               Gdn::Translate('Welcome Aboard!'),
               $Session->UserID > 0 ? $Session->UserID : ''
            );
         }
         // Now update the role settings if necessary
         if ($SaveRoles) {
            // If no RoleIDs were provided, use the system defaults
            if (!is_array($RoleIDs))
               $RoleIDs = Gdn::Config('Garden.Registration.DefaultRoles');

            $this->SaveRoles($UserID, $RoleIDs, $RecordRoleChange);
         }
      } else {
         $UserID = FALSE;
      }
      return $UserID;
   }
   
   // Force the admin user into UserID 1.
   public function SaveAdminUser($FormPostValues) {
      $UserID = 0;

      // Add & apply any extra validation rules:
      $Name = ArrayValue('Name', $FormPostValues, '');
      $FormPostValues['Email'] = ArrayValue('Email', $FormPostValues, strtolower($Name.'@'.Url::Host()));
      $FormPostValues['ShowEmail'] = '0';
      $FormPostValues['TermsOfService'] = '1';
      $FormPostValues['DateOfBirth'] = '1975-09-16';
      $FormPostValues['DateLastActive'] = Format::ToDateTime();
      $FormPostValues['Gender'] = 'm';
      $FormPostValues['Admin'] = '1';

      $this->AddInsertFields($FormPostValues);

      if ($this->Validate($FormPostValues, TRUE) === TRUE) {
         $UserID = 1;
         $Fields = $this->Validation->ValidationFields(); // All fields on the form that need to be validated (including non-schema field rules defined above)
         $Username = ArrayValue('Name', $Fields);
         $Email = ArrayValue('Email', $Fields);
         $Fields = $this->Validation->SchemaValidationFields(); // Only fields that are present in the schema
         $Fields['UserID'] = 1;
         $Fields['Password'] = array('md5' => $Fields['Password']);
         
         if ($this->Get($UserID) !== FALSE) {
            $this->SQL->Put($this->Name, $Fields);
         } else {
            // Insert the new user
            $this->SQL->Insert($this->Name, $Fields);
            AddActivity(
               $UserID,
               'Join',
               Gdn::Translate('Welcome to Vanilla!')
            );
         }
         $this->SaveRoles($UserID, array(5), FALSE);
      }
      return $UserID;
   }

   public function SaveRoles($UserID, $RoleIDs, $RecordActivity = TRUE) {
      if (!is_array($RoleIDs))
         $RoleIDs = array($RoleIDs);

      // Get the old roles
      $OldRoleIDs = array();
      $OldRoleData = $this->SQL
         ->Select('ur.RoleID, r.Name')
         ->From('Role r')
         ->Join('UserRole ur', 'r.RoleID = ur.RoleID')
         ->Where('ur.UserID', $UserID)
         ->Get()
         ->ResultArray();

      if ($OldRoleData !== FALSE)
         $OldRoleIDs = ConsolidateArrayValuesByKey($OldRoleData, 'RoleID');

      // 1. Remove old role associations for this user
      $this->SQL->Delete('UserRole', array('UserID' => $UserID));

      // 2. Remove the cached permissions for this user.
      // Note: they are not reset here because I want this action to be
      // performed in one place - /garden/library/core/class.session.php
      // It is done in the session because when a role's permissions are changed
      // I can then just erase all cached permissions on the user table for
      // users that are assigned to that changed role - and they can reset
      // themselves the next time the session is referenced.
      $this->SQL->Put('User', array('Permissions' => ''), array('UserID' => $UserID));

      // 3. Insert the new role associations for this user.
      $Count = count($RoleIDs);
      for ($i = 0; $i < $Count; $i++) {
         if (is_numeric($RoleIDs[$i]))
            $this->SQL->Insert('UserRole', array('UserID' => $UserID, 'RoleID' => $RoleIDs[$i]));
      }

      if ($RecordActivity && (count(array_diff($OldRoleIDs, $RoleIDs)) > 0 || count(array_diff($RoleIDs, $OldRoleIDs)) > 0)) {
         $User = $this->Get($UserID);
         $Session = Gdn::Session();

         $OldRoles = FALSE;
         if ($OldRoleData !== FALSE)
            $OldRoles = ConsolidateArrayValuesByKey($OldRoleData, 'Name');

         $NewRoles = FALSE;
         $NewRoleData = $this->SQL
            ->Select('r.RoleID, r.Name')
            ->From('Role r')
            ->Join('UserRole ur', 'r.RoleID = ur.RoleID')
            ->Where('ur.UserID', $UserID)
            ->Get()
            ->ResultArray();
         if ($NewRoleData !== FALSE)
            $NewRoles = ConsolidateArrayValuesByKey($NewRoleData, 'Name');


         $RemovedRoles = array_diff($OldRoles, $NewRoles);
         $NewRoles = array_diff($NewRoles, $OldRoles);

         $RemovedCount = count($RemovedRoles);
         $NewCount = count($NewRoles);
         $Story = '';
         if ($RemovedCount > 0 && $NewCount > 0) {
            $Story = sprintf(Gdn::Translate('%1$s was removed from the %2$s %3$s and added to the %4$s %5$s.'),
               $User->Name,
               implode(', ', $RemovedRoles),
               Plural($RemovedCount, 'role', 'roles'),
               implode(', ', $NewRoles),
               Plural($NewCount, 'role', 'roles')
            );
         } else if ($RemovedCount > 0) {
            $Story = sprintf(Gdn::Translate('%1$s was removed from the %2$s %3$s.'),
               $User->Name,
               implode(', ', $RemovedRoles),
               Plural($RemovedCount, 'role', 'roles')
            );
         } else if ($NewCount > 0) {
            $Story = sprintf(Gdn::Translate('%1$s was added to the %2$s %3$s.'),
               $User->Name,
               implode(', ', $NewRoles),
               Plural($NewCount, 'role', 'roles')
            );
         }

         AddActivity(
            $Session->UserID,
            'RoleChange',
            $Story,
            $UserID
         );
      }
   }

   /// <summary>
   /// To be used for invitation registration
   /// </summary>
   public function InsertForInvite($FormPostValues) {
      // Define the primary key in this model's table.
      $this->DefineSchema();

      // Add & apply any extra validation rules:
      $this->Validation->ApplyRule('Email', 'Email');

      // Make sure that the checkbox val for email is saved as the appropriate enum
      // TODO: DO I REALLY NEED THIS???
      if (array_key_exists('ShowEmail', $FormPostValues))
         $FormPostValues['ShowEmail'] = ForceBool($FormPostValues['ShowEmail'], '0', '1', '0');

      $this->AddInsertFields($FormPostValues);

      // Make sure that the user has a valid invitation code, and also grab
      // the user's email from the invitation:
      $InviteUserID = 0;
      $InviteUsername = '';
      $InvitationCode = ArrayValue('InvitationCode', $FormPostValues, '');
      $this->SQL->Select('i.InvitationID, i.InsertUserID, i.Email')
         ->Select('s.Name', '', 'SenderName')
         ->From('Invitation i')
         ->Join('User s', 'i.InsertUserID = s.UserID', 'left')
         ->Where('Code', $InvitationCode)
         ->Where('AcceptedUserID is null'); // Do not let them use the same invitation code twice!
      $InviteExpiration = Gdn::Config('Garden.Registration.InviteExpiration');
      if ($InviteExpiration != 'FALSE' && $InviteExpiration !== FALSE)
         $this->SQL->Where('i.DateInserted >=', Format::ToDateTime(strtotime($InviteExpiration)));

      $Invitation = $this->SQL->Get()->FirstRow();
      if ($Invitation !== FALSE) {
         $InviteUserID = $Invitation->InsertUserID;
         $InviteUsername = $Invitation->SenderName;
         $FormPostValues['Email'] = $Invitation->Email;
      }
      if ($InviteUserID <= 0) {
         $this->Validation->AddValidationResult('InvitationCode', 'ErrorBadInvitationCode');
         return FALSE;
      }

      if ($this->Validate($FormPostValues, TRUE) === TRUE) {
         $Fields = $this->Validation->ValidationFields(); // All fields on the form that need to be validated (including non-schema field rules defined above)
         $Username = ArrayValue('Name', $Fields);
         $Email = ArrayValue('Email', $Fields);
         $Fields = $this->Validation->SchemaValidationFields(); // Only fields that are present in the schema
         $Fields = RemoveKeyFromArray($Fields, $this->PrimaryKey);
         $Fields['Password'] = array('md5' => $Fields['Password']);

         // Make sure the username & email aren't already being used
         if (!$this->ValidateUniqueFields($Username, $Email))
            return FALSE;

         // Define the other required fields:
         if ($InviteUserID > 0)
            $Fields['InviteUserID'] = $InviteUserID;

         // And insert the new user
         $UserID = $this->SQL->Insert($this->Name, $Fields);

         // Associate the new user id with the invitation (so it cannot be used again)
         $this->SQL
            ->Update('Invitation')
            ->Set('AcceptedUserID', $UserID)
            ->Where('InvitationID', $Invitation->InvitationID)
            ->Put();

         // Report that the user was created
         AddActivity(
            $UserID,
            'JoinInvite',
            Gdn::Translate('Welcome Aboard!'),
            $InviteUserID
         );

         // Save the user's roles
         $RoleIDs = Gdn::Config('Garden.Registration.DefaultRoles', array(4)); // 4 is "Member"
         $this->SaveRoles($UserID, $RoleIDs, FALSE);
      } else {
         $UserID = FALSE;
      }
      return $UserID;
   }

   /// <summary>
   /// To be used for approval registration
   /// </summary>
   public function InsertForApproval($FormPostValues) {
      // Define the primary key in this model's table.
      $this->DefineSchema();

      // Add & apply any extra validation rules:
      $this->Validation->ApplyRule('Email', 'Email');

      // TODO: DO I NEED THIS?!?!
      // Make sure that the checkbox val for email is saved as the appropriate enum
      if (array_key_exists('ShowEmail', $FormPostValues))
         $FormPostValues['ShowEmail'] = ForceBool($FormPostValues['ShowEmail'], '0', '1', '0');

      $this->AddInsertFields($FormPostValues);

      if ($this->Validate($FormPostValues, TRUE) === TRUE) {
         $Fields = $this->Validation->ValidationFields(); // All fields on the form that need to be validated (including non-schema field rules defined above)
         $Username = ArrayValue('Name', $Fields);
         $Email = ArrayValue('Email', $Fields);
         $Fields = $this->Validation->SchemaValidationFields(); // Only fields that are present in the schema
         $Fields = RemoveKeyFromArray($Fields, $this->PrimaryKey);
         $Fields['Password'] = array('md5' => $Fields['Password']);

         if (!$this->ValidateUniqueFields($Username, $Email))
            return FALSE;

         // Define the other required fields:
         $Fields['Email'] = $Email;

         // And insert the new user
         $UserID = $this->SQL->Insert($this->Name, $Fields);

         // Now update the role for this user
         $RoleIDs = array(Gdn::Config('Garden.Registration.ApplicantRoleID', 3));
         $this->SaveRoles($UserID, $RoleIDs, FALSE);
      } else {
         $UserID = FALSE;
      }
      return $UserID;
   }

   /// <summary>
   /// To be used for basic registration, and captcha registration
   /// </summary>
   public function InsertForBasic($FormPostValues) {
      $UserID = FALSE;

      // Define the primary key in this model's table.
      $this->DefineSchema();

      // Add & apply any extra validation rules:
      $this->Validation->ApplyRule('Email', 'Email');

      // TODO: DO I NEED THIS?!
      // Make sure that the checkbox val for email is saved as the appropriate enum
      if (array_key_exists('ShowEmail', $FormPostValues))
         $FormPostValues['ShowEmail'] = ForceBool($FormPostValues['ShowEmail'], '0', '1', '0');

      $this->AddInsertFields($FormPostValues);

      if ($this->Validate($FormPostValues, TRUE) === TRUE) {
         $Fields = $this->Validation->ValidationFields(); // All fields on the form that need to be validated (including non-schema field rules defined above)
         $Username = ArrayValue('Name', $Fields);
         $Email = ArrayValue('Email', $Fields);
         $Fields = $this->Validation->SchemaValidationFields(); // Only fields that are present in the schema
         $Fields = RemoveKeyFromArray($Fields, $this->PrimaryKey);
         $Fields['Password'] = array('md5' => $Fields['Password']);

         // If in Captcha registration mode, check the captcha value
         if (Gdn::Config('Garden.Registration.Method') == 'Captcha') {
            $CaptchaPublicKey = ArrayValue('Garden.Registration.CaptchaPublicKey', $FormPostValues, '');
            $CaptchaValid = ValidateCaptcha($CaptchaPublicKey);
            if ($CaptchaValid !== TRUE) {
               $this->Validation->AddValidationResult('Garden.Registration.CaptchaPublicKey', 'The reCAPTCHA value was not entered correctly. Please try again.');
               return FALSE;
            }
         }

         if (!$this->ValidateUniqueFields($Username, $Email))
            return FALSE;

         // Define the other required fields:
         $Fields['Email'] = $Email;

         // And insert the new user
         $UserID = $this->SQL->Insert($this->Name, $Fields);

         AddActivity(
            $UserID,
            'Join',
            Gdn::Translate('Welcome Aboard!')
         );

         // Now update the role settings if necessary
         $RoleIDs = Gdn::Config('Garden.Registration.DefaultRoles');
         $this->SaveRoles($UserID, $RoleIDs, FALSE);
      }
      return $UserID;
   }

   /// parent override
   public function AddInsertFields(&$Fields) {
      $this->DefineSchema();

      // Set the hour offset based on the client's clock.
      $ClientHour = ArrayValue('ClientHour', $Fields, '');
      if (is_numeric($ClientHour) && $ClientHour >= 0 && $ClientHour < 24) {
         $HourOffset = $ClientHour - date('G', time());
         $Fields['HourOffset'] = $HourOffset;
      }

      // Set some required dates
      $Fields[$this->DateInserted] = Format::ToDateTime();
      $Fields['DateFirstVisit'] = Format::ToDateTime();
      $Fields['DateLastActive'] = Format::ToDateTime();
   }

   /**
    * Update last visit.
    *
    * Regenerates other related user properties.
    *
    * @param int $UserID
    * @param array $Attributes
    * @param string|int|float $ClientHour
    */
   function UpdateLastVisit($UserID, $Attributes, $ClientHour='') {
      $UserID = (int) $UserID;
      if (!$UserID) {
         throw new Exception('A valid UserId is required.');
      }

      $this->SQL->Update('User')
         ->Set('DateLastActive', Format::ToDateTime())
         ->Set('CountVisits', 'CountVisits + 1', FALSE);

      if (isset($Attributes) && is_array($Attributes)) {
         // Generate a new transient key for the user (used to authenticate postbacks).
         $Attributes['TransientKey'] = RandomString(12);
         $this->SQL->Set(
         	'Attributes', Format::Serialize($Attributes));
      }

      // Set the hour offset based on the client's clock.
      if (is_numeric($ClientHour) && $ClientHour >= 0 && $ClientHour < 24) {
         $HourOffset = $ClientHour - date('G', time());
         $this->SQL->Set('HourOffset', $HourOffset);
      }

      $this->SQL->Where('UserID', $UserID)->Put();
   }

   /**
    * Validate User Credential
    *
    * It will fetch a a user row by its name and compare the password.
    * The password can be stored in plain text, in a md5
    * or a blowfish hash.
    *
    * If the password was not stored as a blowfish hash,
    * the password will be saved again.
    *
    * Return the user's id, admin status and attributes.
    *
    * @param string $Name
    * @param string $Password
    * @return object
    */
   public function ValidateCredentials($Name='', $ID=0, $Password) {
      if (!$Name && !$ID) {
         throw new Exception('The user name or id is required');
      }

      $this->SQL->Select('UserID, Attributes, Admin, Password')
         ->From('User');

      if ($ID) {
         $this->SQL->Where('UserID', $ID);
      } else {
         $this->SQL->Where('Name', $Name);
      }

      $DataSet = $this->SQL->Get();

      if ($DataSet->NumRows() < 1) {
         return False;
      }

      $UserData = $DataSet->FirstRow();
      $PasswordHash = new GardenPasswordHash();
      if (!$PasswordHash->CheckPassword($Password, $UserData->Password)) {
         return False;
      }

      if ($PasswordHash->Weak) {
         $PasswordHash = new GardenPasswordHash();
         $this->SQL->Update('User')
            ->Set('Password', $PasswordHash->HashPassword($Password))
            ->Where('UserID', $UserData->UserID)
            ->Put();
      }

      $UserData->Attributes = Format::Unserialize($UserData->Attributes);
      return $UserData;
   }

   /// <summary>
   /// Checks to see if FieldValue is unique in FieldName.
   /// </summary>
   public function ValidateUniqueFields($Username, $Email, $UserID = '') {
      $Where = array();
      if (is_numeric($UserID))
         $Where['UserID <> '] = $UserID;

      // Make sure the username & email aren't already being used
      $Where['Name'] = $Username;
      $TestData = $this->GetWhere($Where);
      if ($TestData->NumRows() > 0) {
         $this->Validation->AddValidationResult('Name', 'The name you entered is already in use by another member.');
         return FALSE;
      }
      unset($Where['Name']);
      $Where['Email'] = $Email;
      $TestData = $this->GetWhere($Where);
      if ($TestData->NumRows() > 0) {
         $this->Validation->AddValidationResult('Email', 'The email you entered in use by another member.');
         return FALSE;
      }
      return TRUE;
   }

   public function Approve($UserID, $Email) {
      // Make sure the $UserID is an applicant
      $RoleData = $this->GetRoles($UserID);
      if ($RoleData->NumRows() == 0) {
         throw new Exception(Gdn::Translate('ErrorRecordNotFound'));
      } else {
         $ApplicantFound = FALSE;
         foreach ($RoleData->Result() as $Role) {
            if ($Role->RoleID == 3)
               $ApplicantFound = TRUE;
         }
      }

      if ($ApplicantFound) {
         // Retrieve the default role(s) for new users
         $RoleIDs = Gdn::Config('Garden.Registration.DefaultRoles');

         // Wipe out old & insert new roles for this user
         $this->SaveRoles($UserID, $RoleIDs);

         // Send out a notification to the user
         $SignInUrl = CombinePaths(array(Url::WebRoot(TRUE), 'entry', 'signin'), '/');
         $User = $this->Get($UserID);
         if ($User) {
            $Email->Subject(Gdn::Translate('MembershipApprovedSubject'));
            $Email->Message(sprintf(Gdn::Translate('MembershipApprovedEmail'), $User->Name, $SignInUrl));
            $Email->To($User->Email);
            $Email->Send();
         }

         // Report that the user was approved
         $Session = Gdn::Session();
         AddActivity(
            $UserID,
            'JoinApproved',
            Gdn::Translate('Welcome Aboard!'),
            $Session->UserID
         );
      }
      return TRUE;
   }

   public function Decline($UserID) {
      // Make sure the user is an applicant
      $RoleData = $this->GetRoles($UserID);
      if ($RoleData->NumRows() == 0) {
         throw new Exception(Gdn::Translate('ErrorRecordNotFound'));
      } else {
         $ApplicantFound = FALSE;
         foreach ($RoleData->Result() as $Role) {
            if ($Role->RoleID == 3)
               $ApplicantFound = TRUE;
         }
      }

      if ($ApplicantFound) {
         // 1. Remove old role associations for this user
         $this->SQL->Delete('UserRole', array('UserID' => $UserID));

         // Remove the user
         $this->SQL->Delete('User', array('UserID' => $UserID));
      }
      return TRUE;
   }

   public function GetInvitationCount($UserID) {
      // If this user is master admin, they should have unlimited invites.
      if ($this->SQL
         ->Select('UserID')
         ->From('User')
         ->Where('UserID', $UserID)
         ->Where('Admin', '1')
         ->Get()
         ->NumRows() > 0
      ) return -1;

      // Get the Registration.InviteRoles settings:
      $InviteRoles = Gdn::Config('Garden.Registration.InviteRoles', array());
      if (!is_array($InviteRoles) || count($InviteRoles) == 0)
         return 0;

      // Build an array of roles that can send invitations
      $CanInviteRoles = array();
      foreach ($InviteRoles as $RoleID => $Invites) {
         if ($Invites > 0 || $Invites == -1)
            $CanInviteRoles[] = $RoleID;
      }

      if (count($CanInviteRoles) == 0)
         return 0;

      // See which matching roles the user has
      $UserRoleData = $this->SQL->Select('RoleID')
         ->From('UserRole')
         ->Where('UserID', $UserID)
         ->WhereIn('RoleID', $CanInviteRoles)
         ->Get();

      if ($UserRoleData->NumRows() == 0)
         return 0;

      // Define the maximum number of invites the user is allowed to send
      $InviteCount = 0;
      foreach ($UserRoleData->Result() as $UserRole) {
         $Count = $InviteRoles[$UserRole->RoleID];
         if ($Count == -1) {
            $InviteCount = -1;
         } else if ($InviteCount != -1 && $Count > $InviteCount) {
            $InviteCount = $Count;
         }
      }

      // If the user has unlimited invitations, return that value
      if ($InviteCount == -1)
         return -1;

      // Get the user's current invitation settings from their profile
      $User = $this->SQL->Select('CountInvitations, DateSetInvitations')
         ->From('User')
         ->Where('UserID', $UserID)
         ->Get()
         ->FirstRow();

      // If CountInvitations is null (ie. never been set before) or it is a new month since the DateSetInvitations
      if (!is_numeric($User->CountInvitations) || Format::Date($User->DateSetInvitations, 'n Y') != Format::Date('', 'n Y')) {
         // Reset CountInvitations and DateSetInvitations
         $this->SQL->Put(
            $this->Name,
            array(
               'CountInvitations' => $InviteCount,
               'DateSetInvitations' => Format::Date('', 'Y-m-01') // The first day of this month
            ),
            array('UserID' => $UserID)
         );

      } else {
         // Otherwise return CountInvitations
         return $User->CountInvitations;
      }
   }

   /// <summary>
   /// Reduces the user's CountInvitations value by the specified amount.
   /// </summary>
   /// <param name="UserID" type="int">
   /// The unique id of the user being affected.
   /// </param>
   /// <param name="ReduceBy" type="int" required="false" default="1">
   /// The number to reduce CountInvitations by.
   /// </param>
   public function ReduceInviteCount($UserID, $ReduceBy = 1) {
      $CurrentCount = $this->GetInvitationCount($UserID);

      // Do not reduce if the user has unlimited invitations
      if ($CurrentCount == -1)
         return TRUE;

      // Do not reduce the count below zero.
      if ($ReduceBy > $CurrentCount)
         $ReduceBy = $CurrentCount;

      $this->SQL->Update($this->Name)
         ->Set('CountInvitations', 'CountInvitations - '.$ReduceBy, FALSE)
         ->Where('UserID', $UserID)
         ->Put();
   }

   /// <summary>
   /// Increases the user's CountInvitations value by the specified amount.
   /// </summary>
   /// <param name="UserID" type="int">
   /// The unique id of the user being affected.
   /// </param>
   /// <param name="IncreaseBy" type="int" required="false" default="1">
   /// The number to increase CountInvitations by.
   /// </param>
   public function IncreaseInviteCount($UserID, $IncreaseBy = 1) {
      $CurrentCount = $this->GetInvitationCount($UserID);

      // Do not alter if the user has unlimited invitations
      if ($CurrentCount == -1)
         return TRUE;

      $this->SQL->Update($this->Name)
         ->Set('CountInvitations', 'CountInvitations + '.$IncreaseBy, FALSE)
         ->Where('UserID', $UserID)
         ->Put();
   }

   /// <summary>
   /// Saves the user's About field.
   /// </summary>
   /// <param name="UserID" type="int">
   /// The UserID to save.
   /// </param>
   /// <param name="About" type="string">
   /// The about message being saved.
   /// </param>
   public function SaveAbout($UserID, $About) {
      $About = substr($About, 0, 1000);
      $this->SQL->Update($this->Name)->Set('About', $About)->Where('UserID', $UserID)->Put();
      if (strlen($About) > 500)
         $About = SliceString($About, 500) . '...';

      if (strlen(trim($About)) > 0)
         AddActivity($UserID, 'AboutUpdate', $About);
   }

   /// <summary>
   /// Saves a name/value to the user's specified $Column. This method throws
   /// exceptions when errors are encountered. Use try ... catch blocks to
   /// capture these exceptions.
   /// </summary>
   /// <param name="Column" type="string">
   /// The name of the serialized column to save to. At the time of this writing
   /// there are three serialized columns on the user table: Permissions,
   /// Preferences, and Attributes.
   /// </param>
   /// <param name="UserID" type="int">
   /// The UserID to save.
   /// </param>
   /// <param  name="Name" type="mixed">
   /// The name of the value being saved, or an associative array of name =>
   /// value pairs to be saved. If this is an associative array, the $Value
   /// argument will be ignored.
   /// </param>
   /// <param name="Value" type="mixed" required="false" default="empty">
   /// The value being saved.
   /// </param>
   public function SaveToSerializedColumn($Column, $UserID, $Name, $Value = '') {
      // Load the existing values
      $UserData = $this->SQL->Select($Column)
         ->From('User')
         ->Where('UserID', $UserID)
         ->Get()
         ->FirstRow();

      if (!$UserData)
         throw new Exception(Gdn::Translate('ErrorRecordNotFound'));

      $Values = Format::Unserialize($UserData->$Column);
      // Throw an exception if the field was not empty but is also not an object or array
      if (is_string($Values) && $Values != '')
         throw new Exception(Gdn::Translate('Serialized column failed to be unserialized.'));

      if (!is_array($Values))
         $Values = array();

      // Assign the new value(s)
      if (!is_array($Name))
         $Name = array($Name => $Value);

      $Values = Format::Serialize(array_merge($Values, $Name));

      // Save the values back to the db
      return $this->SQL->Put('User', array($Column => $Values), array('UserID' => $UserID));
   }

   /// <summary>
   /// Saves a user preference to the database. This is a convenience method
   /// that uses $this->SaveToSerializedColumn().
   /// </summary>
   /// <param name="UserID" type="int">
   /// The UserID to save.
   /// </param>
   /// <param  name="Preference" type="mixed">
   /// The name of the preference being saved, or an associative array of name =>
   /// value pairs to be saved. If this is an associative array, the $Value
   /// argument will be ignored.
   /// </param>
   /// <param name="Value" type="mixed" required="false" default="empty">
   /// The value being saved.
   /// </param>
   public function SavePreference($UserID, $Preference, $Value = '') {
      // Make sure that changes to the current user become effective immediately.
      $Session = Gdn::Session();
      if ($UserID == $Session->UserID)
         $Session->SetPreference($Preference, $Value);

      return $this->SaveToSerializedColumn('Preferences', $UserID, $Preference, $Value);
   }

   /// <summary>
   /// Saves a user attribute to the database. This is a convenience method
   /// that uses $this->SaveToSerializedColumn().
   /// </summary>
   /// <param name="UserID" type="int">
   /// The UserID to save.
   /// </param>
   /// <param  name="Attribute" type="mixed">
   /// The name of the attribute being saved, or an associative array of name =>
   /// value pairs to be saved. If this is an associative array, the $Value
   /// argument will be ignored.
   /// </param>
   /// <param name="Value" type="mixed" required="false" default="empty">
   /// The value being saved.
   /// </param>
   public function SaveAttribute($UserID, $Attribute, $Value = '') {
      // Make sure that changes to the current user become effective immediately.
      $Session = Gdn::Session();
      if ($UserID == $Session->UserID)
         $Session->SetAttribute($Attribute, $Value);

      return $this->SaveToSerializedColumn('Attributes', $UserID, $Attribute, $Value);
   }

   public function SetTransientKey($UserID) {
      $Key = RandomString(12);
      $this->SaveAttribute($UserID, 'TransientKey', $Key);
      return $Key;
   }

   public function GetAttribute($UserID, $Attribute, $DefaultValue = FALSE) {
      $Data = $this->SQL
         ->Select('Attributes')
         ->From('User')
         ->Where('UserID', $UserID)
         ->Get()
         ->FirstRow();

      if ($Data !== FALSE) {
         $Attributes = Format::Unserialize($Data->Attributes);
         if (is_array($Attributes))
            return ArrayValue($Attribute, $Attributes, $DefaultValue);

      }
      return $DefaultValue;
   }

   public function SendWelcomeEmail($UserID, $Password) {
      $Session = Gdn::Session();
      $Sender = $this->Get($Session->UserID);
      $User = $this->Get($UserID);
      $AppTitle = Gdn::Config('Garden.Title');
      $Email = new Email();
      $Email->Subject(sprintf(Gdn::Translate('[%s] Welcome Aboard!'), $AppTitle));
      $Email->To($User->Email);
      $Email->From($Sender->Email, $Sender->Name);
      $Email->Message(
         sprintf(
            Gdn::Translate('EmailWelcome'),
            $User->Name,
            $Sender->Name,
            $AppTitle,
            Url::WebRoot(TRUE),
            $Password
         )
      );
      $Email->Send();
   }

   public function SendPasswordEmail($UserID, $Password) {
      $Session = Gdn::Session();
      $Sender = $this->Get($Session->UserID);
      $User = $this->Get($UserID);
      $AppTitle = Gdn::Config('Garden.Title');
      $Email = new Email();
      $Email->Subject(sprintf(Gdn::Translate('[%s] Password Reset'), $AppTitle));
      $Email->To($User->Email);
      $Email->From($Sender->Email, $Sender->Name);
      $Email->Message(
         sprintf(
            Gdn::Translate('EmailPassword'),
            $User->Name,
            $Sender->Name,
            $AppTitle,
            Url::WebRoot(TRUE),
            $Password
         )
      );
      $Email->Send();
   }
}