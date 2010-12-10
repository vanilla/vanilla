<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/

class UserModel extends Gdn_Model {
   
   public $SessionColumns;
   
   /**
    * Class constructor. Defines the related database table name.
    */
   public function __construct() {
      parent::__construct('User');
   }

   /** Connect a user with a foreign authentication system.
    *
    * @param string $ForeignUserKey The user's unique key in the other authentication system.
    * @param string $ProviderKey The key of the system providing the authentication.
    * @param array $UserData Data to go in the user table.
    * @return int The new/existing user ID.
    */
   public function Connect($ForeignUserKey, $ProviderKey, $UserData) {
      if (!isset($UserData['UserID'])) {
         // Check to see if the user already exists.
         $ConnectUserID = $this->SQL->GetWhere('UserAuthentication',
            array('ForeignUserKey' => $ForeignUserKey, 'ProviderKey' => $ProviderKey))
            ->Value('UserID', FALSE);

         if ($ConnectUserID !== FALSE)
            $UserData['UserID'] = $ConnectUserID;
      }

      $NewUser = !isset($ConnectUserID) && !GetValue('UserID', $UserData);

      // Save the user.
      $UserID = $this->Save($UserData, array('ActivityType' => 'Join', 'CheckExisting' => TRUE));

      // Add the user to the default role(s).
      if ($UserID && $NewUser) {
         $this->SaveRoles($UserID, C('Garden.Registration.DefaultRoles'));
      }

      // Save the authentication.
      if ($UserID && !isset($ConnectUserID)) {
         $this->SQL->Replace('UserAuthentication',
            array('UserID' => $UserID),
            array('ForeignUserKey' => $ForeignUserKey, 'ProviderKey' => $ProviderKey));
      }
      return $UserID;
   }
   
   /**
    * A convenience method to be called when inserting users (because users
    * are inserted in various methods depending on registration setups).
    */
   protected function _Insert($Fields) {
      $UserID = $this->SQL->Insert($this->Name, $Fields);
      // Fire an event for user inserts
      $this->EventArguments['InsertUserID'] = $UserID;
      $this->EventArguments['InsertFields'] = $Fields;
      $this->FireEvent('AfterInsertUser');
      return $UserID;
   }

   public function UserQuery() {
      $this->SQL->Select('u.*')
         ->Select('i.Name', '', 'InviteName')
         ->From('User u')
         ->Join('User as i', 'u.InviteUserID = i.UserID', 'left');
   }

   public function DefinePermissions($UserID) {
      $Data = Gdn::PermissionModel()->CachePermissions($UserID);
      $Permissions = array();
      foreach($Data as $i => $Row) {
         $JunctionTable = $Row['JunctionTable'];
         $JunctionColumn = $Row['JunctionColumn'];
         $JunctionID = $Row['JunctionID'];
         unset($Row['JunctionColumn'], $Row['JunctionColumn'], $Row['JunctionID'], $Row['RoleID'], $Row['PermissionID']);
         
         foreach($Row as $PermissionName => $Value) {
            if($Value == 0)
               continue;
            
            if(is_numeric($JunctionID) && $JunctionID > 0) {
               if (!array_key_exists($PermissionName, $Permissions))
                  $Permissions[$PermissionName] = array();
                  
               if (!is_array($Permissions[$PermissionName]))
                  $Permissions[$PermissionName] = array();
                  
               $Permissions[$PermissionName][] = $JunctionID;
            } else {
               $Permissions[] = $PermissionName;
            }
         }
      }
      // Throw a fatal error if the user has no permissions
      // if (count($Permissions) == 0)
      //    trigger_error(ErrorMessage('The requested user ('.$this->UserID.') has no permissions.', 'Session', 'Start'), E_USER_ERROR);

      // Save the permissions to the user table
      $Permissions = Gdn_Format::Serialize($Permissions);
      if ($UserID > 0)
         $this->SQL->Put('User', array('Permissions' => $Permissions), array('UserID' => $UserID));

      return $Permissions;
   }

   public function Get($UserID) {
      $this->UserQuery();
      $User = $this->SQL->Where('u.UserID', $UserID)->Get()->FirstRow();
      $this->SetCalculatedFields($User);

      return $User;
   }
   
   public function GetByUsername($Username) {
		if ($Username == '')
		 	return FALSE;
			
      $this->UserQuery();
      $User = $this->SQL->Where('u.Name', $Username)->Get()->FirstRow();
      $this->SetCalculatedFields($User);
      return $User;
   }
	public function GetByEmail($Email) {
      $this->UserQuery();
      $User = $this->SQL->Where('u.Email', $Email)->Get()->FirstRow();
      $this->SetCalculatedFields($User);
      return $User;
   }

   public function GetActiveUsers($Limit = 5) {
      $this->UserQuery();
      $this->FireEvent('BeforeGetActiveUsers');
      return $this->SQL
         ->Where('u.Deleted', 0)
         ->OrderBy('u.DateLastActive', 'desc')
         ->Limit($Limit, 0)
         ->Get();
   }
   
   /**
    * Returns all users in the applicant role
    */
   public function GetApplicants() {
      return $this->SQL->Select('u.*')
         ->From('User u')
         ->Join('UserRole ur', 'u.UserID = ur.UserID')
         ->Where('ur.RoleID', (int)C('Garden.Registration.ApplicantRoleID', 0), TRUE, FALSE)
         ->GroupBy('UserID')
         ->OrderBy('DateInserted', 'desc')
         ->Get();
   }

   /**
    * Get the a user authentication row.
    *
    * @param string $UniqueID The unique ID of the user in the foreign authentication scheme.
    * @param string $Provider The key of the provider.
    * @return array|false
    */
   public function GetAuthentication($UniqueID, $Provider) {
      return $this->SQL->GetWhere('UserAuthentication',
         array('ForeignUserKey' => $UniqueID, 'ProviderKey' => $Provider))->FirstRow(DATASET_TYPE_ARRAY);
   }

   public function GetCountLike($Like = FALSE) {
      $ApplicantRoleID = (int)C('Garden.Registration.ApplicantRoleID', 0);

      $this->SQL
         ->Select('u.UserID', 'count', 'UserCount')
         ->From('User u')
         ->Join('UserRole ur', "u.UserID = ur.UserID and ur.RoleID = $ApplicantRoleID", 'left');
      if (is_array($Like)){
         $this->SQL
				->BeginWhereGroup()
				->OrLike($Like, '', 'right')
				->EndWhereGroup();
		}
		$this->SQL
         ->Where('u.Deleted', 0)
         ->Where('ur.RoleID is null');
		
		$Data =  $this->SQL->Get()->FirstRow();

      return $Data === FALSE ? 0 : $Data->UserCount;
   }

   public function GetCountWhere($Where = FALSE) {
      $this->SQL
         ->Select('u.UserID', 'count', 'UserCount')
         ->From('User u')
         ->Join('UserRole ur', 'u.UserID = ur.UserID and ur.RoleID = '.(int)C('Garden.Registration.ApplicantRoleID', 0), 'left');
		
		if (is_array($Where))
         $this->SQL->Where($Where);

		$Data = $this->SQL
         ->Where('u.Deleted', 0)
         ->Where('ur.RoleID is null')
         ->Get()
         ->FirstRow();

      return $Data === FALSE ? 0 : $Data->UserCount;
   }

   public function GetLike($Like = FALSE, $OrderFields = '', $OrderDirection = 'asc', $Limit = FALSE, $Offset = FALSE) {
      $ApplicantRoleID = (int)C('Garden.Registration.ApplicantRoleID', 0);

      $this->UserQuery();
      $this->SQL
         ->Join('UserRole ur', "u.UserID = ur.UserID and ur.RoleID = $ApplicantRoleID", 'left');

      if (is_array($Like)) {
         $this->SQL
				->BeginWhereGroup()
				->OrLike($Like, '', 'right')
				->EndWhereGroup();
		}
		
      return $this->SQL
         ->Where('u.Deleted', 0)
         ->Where('ur.RoleID is null')
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

   public function GetSession($UserID, $Refresh = FALSE) {
      static $UserCache = array();

      if(!$Refresh && array_key_exists($UserID, $UserCache)) {
         return $UserCache[$UserID];
      }

      $this->SQL
         ->Select('u.*')
         //->Select('u.UserID, u.Name, u.Preferences, u.Permissions, u.Attributes, u.HourOffset, u.CountNotifications, u.Admin, u.DateLastActive')
         //->Select('u.Photo', '', 'Photo')
         ->From('User u')
         // Removing this for now. Will break existing installs because you need to have a session to be authenticated to run the structure changes.
         // ->Where('u.Deleted', 0)
         ->Where('u.UserID', $UserID);
         
      if(is_array($this->SessionColumns)) {
         $this->SQL->Select($this->SessionColumns);
      }

      $this->FireEvent('SessionQuery');

      $User = $this->SQL
         ->Get()
         ->FirstRow();

      if ($User && $User->Permissions == '')
         $User->Permissions = $this->DefinePermissions($UserID);

      $UserCache[$UserID] = $User;

      return $User;
   }
   
   public function RemovePicture($UserID) {
      $this->SQL
         ->Update('User')
         ->Set('Photo', 'null', FALSE)
         ->Where('UserID', $UserID)
         ->Put();
   }

   /**
    * Generic save procedure.
    */
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
      
      $this->EventArguments['FormPostValues'] = $FormPostValues;
      $this->FireEvent('BeforeSaveValidation');

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
            $PasswordHash = new Gdn_PasswordHash();
            $Fields['Password'] = $PasswordHash->HashPassword($Fields['Password']);
         }
         
         $this->EventArguments['Fields'] = $Fields;
         $this->FireEvent('BeforeSave');
         
         // Check the validation results again in case something was added during the BeforeSave event.
         if (count($this->Validation->Results()) == 0) {
            // If the primary key exists in the validated fields and it is a
            // numeric value greater than zero, update the related database row.
            if ($UserID > 0) {
               // If they are changing the username & email, make sure they aren't
               // already being used (by someone other than this user)
               if (ArrayValue('Name', $Fields, '') != '' || ArrayValue('Email', $Fields, '') != '') {
                  if (!$this->ValidateUniqueFields($Username, $Email, $UserID))
                     return FALSE;
               }
   
               $this->SQL->Put($this->Name, $Fields, array($this->PrimaryKey => $UserID));
   
               // Record activity if the person changed his/her photo
               $Photo = ArrayValue('Photo', $FormPostValues);
               if ($Photo !== FALSE) {
                  if (GetValue('CheckExisting', $Settings)) {
                     $User = $this->Get($UserID);
                     $OldPhoto = GetValue('Photo', $User);
                  }

                  if (!isset($OldPhoto) || $Photo != $Photo) {
                     if (strpos($Photo, '//'))
                        $PhotoUrl = $Photo;
                     else
                        $PhotoUrl = Asset('uploads/'.ChangeBasename($Photo, 't%s'));

                     AddActivity($UserID, 'PictureChange', '<img src="'.$PhotoUrl.'" alt="'.T('Thumbnail').'" />');
                  }
               }
   
            } else {
               $RecordRoleChange = FALSE;
               if (!$this->ValidateUniqueFields($Username, $Email))
                  return FALSE;
   
               // Define the other required fields:
               $Fields['Email'] = $Email;
   
               // And insert the new user
               $UserID = $this->_Insert($Fields);
   
               // Make sure that the user is assigned to one or more roles:
               $SaveRoles = TRUE;
   
               // Report that the user was created
               $Session = Gdn::Session();
               AddActivity(
                  $UserID,
                  GetValue('ActivityType', $Settings, 'JoinCreated'),
                  T('Welcome Aboard!'),
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
         
            $this->EventArguments['UserID'] = $UserID;
            $this->FireEvent('AfterSave');
         } else {
            $UserID = FALSE;
         }
      } else {
         $UserID = FALSE;
      }
      return $UserID;
   }
   
   /**
    * Force the admin user into UserID 1.
    */
   public function SaveAdminUser($FormPostValues) {
      $UserID = 0;

      // Add & apply any extra validation rules:
      $Name = ArrayValue('Name', $FormPostValues, '');
      $FormPostValues['Email'] = ArrayValue('Email', $FormPostValues, strtolower($Name.'@'.Gdn_Url::Host()));
      $FormPostValues['ShowEmail'] = '0';
      $FormPostValues['TermsOfService'] = '1';
      $FormPostValues['DateOfBirth'] = '1975-09-16';
      $FormPostValues['DateLastActive'] = Gdn_Format::ToDateTime();
      $FormPostValues['DateUpdated'] = Gdn_Format::ToDateTime();
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
            $UserID = $this->_Insert($Fields);
            AddActivity(
               $UserID,
               'Join',
               T('Welcome to Vanilla!')
            );
         }
         $this->SaveRoles($UserID, array(16), FALSE);
      }
      return $UserID;
   }

   public function SaveRoles($UserID, $RoleIDs, $RecordActivity = TRUE) {
      if(is_string($RoleIDs) && !is_numeric($RoleIDs)) {
         // The $RoleIDs are a comma delimited list of role names.
         $RoleNames = preg_split('/\s*,\s*/', $RoleIDs);
         $RoleIDs = $this->SQL
            ->Select('r.RoleID')
            ->From('Role r')
            ->WhereIn('r.Name', $RoleNames)
            ->Get()->ResultArray();
         $RoleIDs = ConsolidateArrayValuesByKey($RoleIDs, 'RoleID');
      }
      
      if (!is_array($RoleIDs))
         $RoleIDs = array($RoleIDs);

      // Get the current roles.
      $OldRoleIDs = array();
      $OldRoleData = $this->SQL
         ->Select('ur.RoleID, r.Name')
         ->From('Role r')
         ->Join('UserRole ur', 'r.RoleID = ur.RoleID')
         ->Where('ur.UserID', $UserID)
         ->Get()
         ->ResultArray();

      if ($OldRoleData !== FALSE) {
         $OldRoleIDs = ConsolidateArrayValuesByKey($OldRoleData, 'RoleID');
      }
      
      // 1a) Figure out which roles to delete.
      $DeleteRoleIDs = array_diff($OldRoleIDs, $RoleIDs);
      // 1b) Remove old role associations for this user.
      if(count($DeleteRoleIDs) > 0)
         $this->SQL->WhereIn('RoleID', $DeleteRoleIDs)->Delete('UserRole', array('UserID' => $UserID));
      
      // 2a) Figure out which roles to insert.
      $InsertRoleIDs = array_diff($RoleIDs, $OldRoleIDs);
      // 2b) Insert the new role associations for this user.
      foreach($InsertRoleIDs as $InsertRoleID) {
         if (is_numeric($InsertRoleID))
            $this->SQL->Insert('UserRole', array('UserID' => $UserID, 'RoleID' => $InsertRoleID));
      }      

      // 3. Remove the cached permissions for this user.
      // Note: they are not reset here because I want this action to be
      // performed in one place - /dashboard/library/core/class.session.php
      // It is done in the session because when a role's permissions are changed
      // I can then just erase all cached permissions on the user table for
      // users that are assigned to that changed role - and they can reset
      // themselves the next time the session is referenced.
      $this->SQL->Put('User', array('Permissions' => ''), array('UserID' => $UserID));


      if ($RecordActivity && (count($DeleteRoleIDs) > 0 || count($InsertRoleIDs) > 0)) {
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
            $Story = sprintf(T('%1$s was removed from the %2$s %3$s and added to the %4$s %5$s.'),
               $User->Name,
               implode(', ', $RemovedRoles),
               Plural($RemovedCount, 'role', 'roles'),
               implode(', ', $NewRoles),
               Plural($NewCount, 'role', 'roles')
            );
         } else if ($RemovedCount > 0) {
            $Story = sprintf(T('%1$s was removed from the %2$s %3$s.'),
               $User->Name,
               implode(', ', $RemovedRoles),
               Plural($RemovedCount, 'role', 'roles')
            );
         } else if ($NewCount > 0) {
            $Story = sprintf(T('%1$s was added to the %2$s %3$s.'),
               $User->Name,
               implode(', ', $NewRoles),
               Plural($NewCount, 'role', 'roles')
            );
         }

         AddActivity(
            $Session->UserID != 0 ? $Session->UserID : $UserID,
            'RoleChange',
            $Story,
            $UserID
         );
      }
   }

   public function Search($Keywords, $OrderFields = '', $OrderDirection = 'asc', $Limit = FALSE, $Offset = FALSE) {
      $ApplicantRoleID = (int)C('Garden.Registration.ApplicantRoleID', 0);

      // Check to see if the search exactly matches a role name.
      $RoleID = $this->SQL->GetWhere('Role', array('Name' => $Keywords))->Value('RoleID');

      $this->UserQuery();
      $this->SQL
         ->Join('UserRole ur', "u.UserID = ur.UserID and ur.RoleID = $ApplicantRoleID", 'left');

      if ($RoleID) {
         $this->SQL->Join('UserRole ur2', "u.UserID = ur2.UserID and ur2.RoleID = $RoleID");
      } else {
         // Search on the user table.
         $Like = trim($Keywords) == '' ? FALSE : array('u.Name' => $Keywords, 'u.Email' => $Keywords);
         
         if (is_array($Like)) {
            $this->SQL
               ->BeginWhereGroup()
               ->OrLike($Like, '', 'right')
               ->EndWhereGroup();
         }
      }

      return $this->SQL
         ->Where('u.Deleted', 0)
         ->Where('ur.RoleID is null')
         ->OrderBy($OrderFields, $OrderDirection)
         ->Limit($Limit, $Offset)
         ->Get();
   }

   public function SearchCount($Keywords = FALSE) {
      $ApplicantRoleID = (int)C('Garden.Registration.ApplicantRoleID', 0);


      // Check to see if the search exactly matches a role name.
      $RoleID = $this->SQL->GetWhere('Role', array('Name' => $Keywords))->Value('RoleID');
      
      $this->SQL
         ->Select('u.UserID', 'count', 'UserCount')
         ->From('User u')
         ->Join('UserRole ur', "u.UserID = ur.UserID and ur.RoleID = $ApplicantRoleID", 'left');

      if ($RoleID) {
         $this->SQL->Join('UserRole ur2', "u.UserID = ur2.UserID and ur2.RoleID = $RoleID");
      } else {
         // Search on the user table.
         $Like = trim($Keywords) == '' ? FALSE : array('u.Name' => $Keywords, 'u.Email' => $Keywords);

         if (is_array($Like)) {
            $this->SQL
               ->BeginWhereGroup()
               ->OrLike($Like, '', 'right')
               ->EndWhereGroup();
         }
      }

		$this->SQL
         ->Where('u.Deleted', 0)
         ->Where('ur.RoleID is null');

		$Data =  $this->SQL->Get()->FirstRow();

      return $Data === FALSE ? 0 : $Data->UserCount;
   }

   /**
    * To be used for invitation registration
    */
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
         $this->SQL->Where('i.DateInserted >=', Gdn_Format::ToDateTime(strtotime($InviteExpiration)));

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
         $UserID = $this->_Insert($Fields);

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
            T('Welcome Aboard!'),
            $InviteUserID
         );

         // Save the user's roles
         $RoleIDs = (array)Gdn::Config('Garden.Registration.DefaultRoles', C('Garden.Registration.ApplicantRoleID', array()));
         $this->SaveRoles($UserID, $RoleIDs, FALSE);
      } else {
         $UserID = FALSE;
      }
      return $UserID;
   }

   /**
    * To be used for approval registration
    */
   public function InsertForApproval($FormPostValues) {
      // Define the primary key in this model's table.
      $this->DefineSchema();

      // Add & apply any extra validation rules:
      $this->Validation->ApplyRule('Email', 'Email');

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
         $UserID = $this->_Insert($Fields);

         // Now update the role for this user
         $RoleIDs = array(Gdn::Config('Garden.Registration.ApplicantRoleID', 4));
         $this->SaveRoles($UserID, $RoleIDs, FALSE);
      } else {
         $UserID = FALSE;
      }
      return $UserID;
   }

   /**
    * To be used for basic registration, and captcha registration
    */
   public function InsertForBasic($FormPostValues, $CheckCaptcha = TRUE) {
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
         if ($CheckCaptcha && Gdn::Config('Garden.Registration.Method') == 'Captcha') {
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
         $UserID = $this->_Insert($Fields);

         AddActivity(
            $UserID,
            'Join',
            T('Welcome Aboard!')
         );

         // Now update the role settings if necessary
         $RoleIDs = Gdn::Config('Garden.Registration.DefaultRoles', array(8));
         $this->SaveRoles($UserID, $RoleIDs, FALSE);
      }
      return $UserID;
   }

   // parent override
   public function AddInsertFields(&$Fields) {
      $this->DefineSchema();

      // Set the hour offset based on the client's clock.
      $ClientHour = ArrayValue('ClientHour', $Fields, '');
      if (is_numeric($ClientHour) && $ClientHour >= 0 && $ClientHour < 24) {
         $HourOffset = $ClientHour - date('G', time());
         $Fields['HourOffset'] = $HourOffset;
      }

      // Set some required dates
      $Fields[$this->DateInserted] = Gdn_Format::ToDateTime();
      $Fields['DateFirstVisit'] = Gdn_Format::ToDateTime();
      $Fields['DateLastActive'] = Gdn_Format::ToDateTime();
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
         ->Set('DateLastActive', Gdn_Format::ToDateTime())
         ->Set('CountVisits', 'CountVisits + 1', FALSE);

      if (isset($Attributes) && is_array($Attributes)) {
         // Generate a new transient key for the user (used to authenticate postbacks).
         $Attributes['TransientKey'] = RandomString(12);
         $this->SQL->Set(
         	'Attributes', Gdn_Format::Serialize($Attributes));
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
    * Fetches a user row by email (or name) and compare the password.
    * The password can be stored in plain text, in a md5
    * or a blowfish hash.
    *
    * If the password was not stored as a blowfish hash,
    * the password will be saved again.
    *
    * Return the user's id, admin status and attributes.
    *
    * @param string $Email
    * @param string $Password
    * @return object
    */
   public function ValidateCredentials($Email = '', $ID = 0, $Password) {
      $this->EventArguments['Credentials'] = array('Email'=>$Email, 'ID'=>$ID, 'Password'=>$Password);
      $this->FireEvent('BeforeValidateCredentials');

      if (!$Email && !$ID)
         throw new Exception('The email or id is required');

		try {
			$this->SQL->Select('UserID, Attributes, Admin, Password, HashMethod, Deleted')
				->From('User');
	
			if ($ID) {
				$this->SQL->Where('UserID', $ID);
			} else {
				if (strpos($Email, '@') > 0) {
					$this->SQL->Where('Email', $Email);
				} else {
					$this->SQL->Where('Name', $Email);
				}
			}
	
			$DataSet = $this->SQL->Get();
		} catch(Exception $Ex) {
         $this->SQL->Reset();
         
			// Try getting the user information without the new fields.
			$this->SQL->Select('UserID, Attributes, Admin, Password')
				->From('User');
	
			if ($ID) {
				$this->SQL->Where('UserID', $ID);
			} else {
				if (strpos($Email, '@') > 0) {
					$this->SQL->Where('Email', $Email);
				} else {
					$this->SQL->Where('Name', $Email);
				}
			}
	
			$DataSet = $this->SQL->Get();
		}
		
      if ($DataSet->NumRows() < 1)
         return FALSE;

      $UserData = $DataSet->FirstRow();
		// Check for a deleted user.
		if(GetValue('Deleted', $UserData))
			return FALSE;
		
		
      $PasswordHash = new Gdn_PasswordHash();
		$HashMethod = GetValue('HashMethod', $UserData);
      if(!$PasswordHash->CheckPassword($Password, $UserData->Password, $HashMethod))
         return FALSE;

      if ($PasswordHash->Weak || ($HashMethod && strcasecmp($HashMethod, 'Vanilla') != 0)) {
         $PasswordHash = new Gdn_PasswordHash();
         $this->SQL->Update('User')
            ->Set('Password', $PasswordHash->HashPassword($Password))
				->Set('HashMethod', 'Vanilla')
            ->Where('UserID', $UserData->UserID)
            ->Put();
      }

      $UserData->Attributes = Gdn_Format::Unserialize($UserData->Attributes);
      return $UserData;
   }

   /**
    * Checks to see if $Username and $Email are already in use by another member.
    */
   public function ValidateUniqueFields($Username, $Email, $UserID = '') {
      $Valid = TRUE;
      $Where = array();
      if (is_numeric($UserID))
         $Where['UserID <> '] = $UserID;

      // Make sure the username & email aren't already being used
      if (C('Garden.Registration.NameUnique', TRUE)) {
         $Where['Name'] = $Username;
         $TestData = $this->GetWhere($Where);
         if ($TestData->NumRows() > 0) {
            $this->Validation->AddValidationResult('Name', 'The name you entered is already in use by another member.');
            $Valid = FALSE;
         }
         unset($Where['Name']);
      }
      if (C('Garden.Registration.EmailUnique')) {
         $Where['Email'] = $Email;
         $TestData = $this->GetWhere($Where);
         if ($TestData->NumRows() > 0) {
            $this->Validation->AddValidationResult('Email', 'The email you entered is in use by another member.');
            $Valid = FALSE;
         }
      }
      return $Valid;
   }

   /**
    * Approve a membership applicant.
    */
   public function Approve($UserID, $Email) {
      $ApplicantRoleID = C('Garden.Registration.ApplicantRoleID', 0);

      // Make sure the $UserID is an applicant
      $RoleData = $this->GetRoles($UserID);
      if ($RoleData->NumRows() == 0) {
         throw new Exception(T('ErrorRecordNotFound'));
      } else {
         $ApplicantFound = FALSE;
         foreach ($RoleData->Result() as $Role) {
            if ($Role->RoleID == $ApplicantRoleID)
               $ApplicantFound = TRUE;
         }
      }

      if ($ApplicantFound) {
         // Retrieve the default role(s) for new users
         $RoleIDs = Gdn::Config('Garden.Registration.DefaultRoles', array(8));

         // Wipe out old & insert new roles for this user
         $this->SaveRoles($UserID, $RoleIDs, FALSE);

         // Send out a notification to the user
         $User = $this->Get($UserID);
         if ($User) {
				$Email->Subject(sprintf(T('[%1$s] Membership Approved'), C('Garden.Title')));
				$Email->Message(sprintf(T('EmailMembershipApproved'), $User->Name, Url(Gdn::Authenticator()->SignInUrl(), TRUE)));
				$Email->To($User->Email);
				//$Email->From(C('Garden.SupportEmail'), C('Garden.SupportName'));
				$Email->Send();
         }

         // Report that the user was approved
         $Session = Gdn::Session();
         AddActivity(
            $Session->UserID,
            'JoinApproved',
            T('Welcome Aboard!'),
            $UserID,
            '',
            FALSE
         );
      }
      return TRUE;
   }

   public function Delete($UserID, $Options = array()) {
      // Fire an event so applications can remove their associated user data.
      $this->EventArguments['UserID'] = $UserID;
      $this->EventArguments['Options'] = $Options;
      $this->FireEvent('BeforeDeleteUser');

      // Remove photos
      $PhotoData = $this->SQL->Select()->From('Photo')->Where('InsertUserID', $UserID)->Get();
      foreach ($PhotoData->Result() as $Photo) {
         @unlink(PATH_UPLOADS.DS.$Photo->Name);
      }
      $this->SQL->Delete('Photo', array('InsertUserID' => $UserID));
      
      // Remove invitations
      $this->SQL->Delete('Invitation', array('InsertUserID' => $UserID));
      $this->SQL->Delete('Invitation', array('AcceptedUserID' => $UserID));
      
      // Remove activities
      $this->SQL->Delete('Activity', array('ActivityUserID' => $UserID));
      $this->SQL->Delete('Activity', array('RegardingUserID' => $UserID));
      
      // Remove shared authentications
      $this->SQL->Delete('UserAuthentication', array('UserID' => $UserID));

      // Remove role associations
      $this->SQL->Delete('UserRole', array('UserID' => $UserID));

      // Remove the user's information
      $this->SQL->Update('User')
         ->Set(array(
            'Name' => '[Deleted User]',
            'Photo' => 'null',
            'Password' => RandomString('10'),
            'About' => '',
            'Email' => 'user_'.$UserID.'@deleted.email',
            'ShowEmail' => '0',
            'Gender' => 'm',
            'CountVisits' => 0,
            'CountInvitations' => 0,
            'CountNotifications' => 0,
            'InviteUserID' => null,
            'DiscoveryText' => '',
            'Preferences' => null,
            'Permissions' => null,
            'Attributes' => Gdn_Format::Serialize(array('State' => 'Deleted')),
            'DateSetInvitations' => null,
            'DateOfBirth' => null,
            'DateFirstVisit' => null,
            'DateLastActive' => null,
//            'DateInserted' => '1975-09-16 00:00:00',
            'DateUpdated' => Gdn_Format::ToDateTime(),
            'HourOffset' => '0',
            'Score' => null,
            'Admin' => 0,
            'Deleted' => 1
            ))
         ->Where('UserID', $UserID)
         ->Put();

      return TRUE;
   }

   public function Decline($UserID) {
      // Make sure the user is an applicant
      $RoleData = $this->GetRoles($UserID);
      if ($RoleData->NumRows() == 0) {
         throw new Exception(T('ErrorRecordNotFound'));
      } else {
         $ApplicantFound = FALSE;
         foreach ($RoleData->Result() as $Role) {
            if ($Role->RoleID == C('Garden.Registration.ApplicantRoleID', 0))
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
      if ($User->CountInvitations == '' || is_null($User->DateSetInvitations) || Gdn_Format::Date($User->DateSetInvitations, 'n Y') != Gdn_Format::Date('', 'n Y')) {
         // Reset CountInvitations and DateSetInvitations
         $this->SQL->Put(
            $this->Name,
            array(
               'CountInvitations' => $InviteCount,
               'DateSetInvitations' => Gdn_Format::Date('', 'Y-m-01') // The first day of this month
            ),
            array('UserID' => $UserID)
         );
         return $InviteCount;
      } else {
         // Otherwise return CountInvitations
         return $User->CountInvitations;
      }
   }

   /**
    * Reduces the user's CountInvitations value by the specified amount.
    *
    * @param int The unique id of the user being affected.
    * @param int The number to reduce CountInvitations by.
    */
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

   /**
    * Increases the user's CountInvitations value by the specified amount.
    *
    * @param int The unique id of the user being affected.
    * @param int The number to increase CountInvitations by.
    */
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

   /**
    * Saves the user's About field.
    *
    * @param int The UserID to save.
    * @param string The about message being saved.
    */
   public function SaveAbout($UserID, $About) {
      $About = substr($About, 0, 1000);
      $this->SQL->Update($this->Name)->Set('About', $About)->Where('UserID', $UserID)->Put();
      if (strlen($About) > 500)
         $About = SliceString($About, 500) . '...';
   }

   /**
    * Saves a name/value to the user's specified $Column.
    *
    * This method throws exceptions when errors are encountered. Use try ...
    * catch blocks to capture these exceptions.
    *
    * @param string The name of the serialized column to save to. At the time of this writing there are three serialized columns on the user table: Permissions, Preferences, and Attributes.
    * @param int The UserID to save.
    * @param mixed The name of the value being saved, or an associative array of name => value pairs to be saved. If this is an associative array, the $Value argument will be ignored.
    * @param mixed The value being saved.
    */
   public function SaveToSerializedColumn($Column, $UserID, $Name, $Value = '') {
      // Load the existing values
      $UserData = $this->SQL->Select($Column)
         ->From('User')
         ->Where('UserID', $UserID)
         ->Get()
         ->FirstRow();

      if (!$UserData)
         throw new Exception(T('ErrorRecordNotFound'));

      $Values = Gdn_Format::Unserialize($UserData->$Column);
      // Throw an exception if the field was not empty but is also not an object or array
      if (is_string($Values) && $Values != '')
         throw new Exception(T('Serialized column failed to be unserialized.'));

      if (!is_array($Values))
         $Values = array();

      // Assign the new value(s)
      if (!is_array($Name))
         $Name = array($Name => $Value);

      $Values = Gdn_Format::Serialize(array_merge($Values, $Name));

      // Save the values back to the db
      return $this->SQL->Put('User', array($Column => $Values), array('UserID' => $UserID));
   }

   /**
    * Saves a user preference to the database.
    *
    * This is a convenience method that uses $this->SaveToSerializedColumn().
    *
    * @param int The UserID to save.
    * @param mixed The name of the preference being saved, or an associative array of name => value pairs to be saved. If this is an associative array, the $Value argument will be ignored.
    * @param mixed The value being saved.
    */
   public function SavePreference($UserID, $Preference, $Value = '') {
      // Make sure that changes to the current user become effective immediately.
      $Session = Gdn::Session();
      if ($UserID == $Session->UserID)
         $Session->SetPreference($Preference, $Value, FALSE);

      return $this->SaveToSerializedColumn('Preferences', $UserID, $Preference, $Value);
   }

   /**
    * Saves a user attribute to the database.
    *
    * This is a convenience method that uses $this->SaveToSerializedColumn().
    *
    * @param int The UserID to save.
    * @param mixed The name of the attribute being saved, or an associative array of name => value pairs to be saved. If this is an associative array, the $Value argument will be ignored.
    * @param mixed The value being saved.
    */
   public function SaveAttribute($UserID, $Attribute, $Value = '') {
      // Make sure that changes to the current user become effective immediately.
      $Session = Gdn::Session();
      if ($UserID == $Session->UserID)
         $Session->SetAttribute($Attribute, $Value);

      return $this->SaveToSerializedColumn('Attributes', $UserID, $Attribute, $Value);
   }

   public function SaveAuthentication($Data) {
      $Cn = $this->Database->Connection();
      $Px = $this->Database->DatabasePrefix;

      $UID = $Cn->quote($Data['UniqueID']);
      $Provider = $Cn->quote($Data['Provider']);
      $UserID = $Cn->quote($Data['UserID']);

      $Sql = "insert {$Px}UserAuthentication (ForeignUserKey, ProviderKey, UserID) values ($UID, $Provider, $UserID) on duplicate key update UserID = $UserID";
      $Result = $this->Database->Query($Sql);
      return $Result;
   }

   public function SetCalculatedFields(&$User) {
      if ($v = GetValue('Attributes', $User))
         SetValue('Attributes', $User, @unserialize($v));
      if ($v = GetValue('Permissions', $User))
         SetValue('Permissions', $User, @unserialize($v));
      if ($v = GetValue('Preferences', $User))
         SetValue('Preferences', $User, @unserialize($v));
      if ($v = GetValue('Photo', $User))
         SetValue('PhotoUrl', $User, Asset('uploads/'.$v, TRUE));
   }

   public function SetTransientKey($UserID, $ExplicitKey = '') {
      $Key = $ExplicitKey == '' ? RandomString(12) : $ExplicitKey;
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
         $Attributes = Gdn_Format::Unserialize($Data->Attributes);
         if (is_array($Attributes))
            return ArrayValue($Attribute, $Attributes, $DefaultValue);

      }
      return $DefaultValue;
   }

   public function SendWelcomeEmail($UserID, $Password, $RegisterType = 'Add', $AdditionalData = NULL) {
      $Session = Gdn::Session();
      $Sender = $this->Get($Session->UserID);
      $User = $this->Get($UserID);
      $AppTitle = Gdn::Config('Garden.Title');
      $Email = new Gdn_Email();
      $Email->Subject(sprintf(T('[%s] Welcome Aboard!'), $AppTitle));
      $Email->To($User->Email);

      // Check for the new email format.
      if (($EmailFormat = T("EmailWelcome{$RegisterType}", '#')) != '#') {
         $Data = array();
         $Data['User'] = ArrayTranslate((array)$User, array('Name', 'Email'));
         $Data['Sender'] = ArrayTranslate((array)$Sender, array('Name', 'Email'));
         $Data['Title'] = $AppTitle;
         if (is_array($AdditionalData))
            $Data = array_merge($Data, $AdditionalData);

         $Message = FormatString($EmailFormat, $Data);
         $Email->Message($Message);
      } else {
         $Email->Message(
            sprintf(
               T('EmailWelcome'),
               $User->Name,
               $Sender->Name,
               $AppTitle,
               Gdn_Url::WebRoot(TRUE),
               $Password,
               $User->Email
            )
         );
      }

      $Email->Send();
   }

   public function SendPasswordEmail($UserID, $Password) {
      $Session = Gdn::Session();
      $Sender = $this->Get($Session->UserID);
      $User = $this->Get($UserID);
      $AppTitle = Gdn::Config('Garden.Title');
      $Email = new Gdn_Email();
      $Email->Subject(sprintf(T('[%s] Password Reset'), $AppTitle));
      $Email->To($User->Email);
      //$Email->From($Sender->Email, $Sender->Name);
      $Email->Message(
         sprintf(
            T('EmailPassword'),
            $User->Name,
            $Sender->Name,
            $AppTitle,
            Gdn_Url::WebRoot(TRUE),
            $Password,
            $User->Email
         )
      );
      $Email->Send();
   }
   
   /**
    * Synchronizes the user based on a given UserKey.
    *
    * @param string $UserKey A string that uniquely identifies this user.
    * @param array $Data Information to put in the user table.
    * @return int The ID of the user.
    */
   public function Synchronize($UserKey, $Data) {
      $UserID = 0;
      
      $Attributes = ArrayValue('Attributes', $Data);
      if (!is_array($Attributes))
         $Attributes = array();

      // If the user didnt log in, they won't have a UserID yet. That means they want a new
      // account. So create one for them.
      if (!isset($Data['UserID']) || $Data['UserID'] <= 0) {
      
         // Prepare the user data.
         $UserData['Name'] = $Data['Name'];
         $UserData['Password'] = RandomString(16);
         $UserData['Email'] = ArrayValue('Email', $Data, 'no@email.com');
         $UserData['Gender'] = strtolower(substr(ArrayValue('Gender', $Attributes, 'm'), 0, 1));
         $UserData['HourOffset'] = ArrayValue('HourOffset', $Attributes, 0);
         $UserData['DateOfBirth'] = ArrayValue('DateOfBirth', $Attributes, '');
         $UserData['CountNotifications'] = 0;
         $UserData['Attributes'] = Gdn_Format::Serialize($Attributes);
         if ($UserData['DateOfBirth'] == '')
            $UserData['DateOfBirth'] = '1975-09-16';
            
         // Make sure there isn't another user with this username.
         if ($this->ValidateUniqueFields($UserData['Name'], $UserData['Email'])) {
            // Insert the new user.
            $this->AddInsertFields($UserData);
            $UserID = $this->_Insert($UserData);
         }

         if ($UserID) {
            $RoleID = $this->NewUserRoleIDs();
            
            // Save the roles.
            $Roles = (array)GetValue('Roles', $Data, $RoleID);
            $this->SaveRoles($UserID, $Roles, FALSE);
         }
      } else {
         $UserID = $Data['UserID'];
      }
      
      // Synchronize the transientkey from the external user data source if it is present (eg. WordPress' wpnonce).
      if (array_key_exists('TransientKey', $Attributes) && $Attributes['TransientKey'] != '' && $UserID > 0)
         $this->SetTransientKey($UserID, $Attributes['TransientKey']);

      return $UserID;
   }
   
   public function NewUserRoleIDs() {
      // Registration method
      $RegistrationMethod = C('Garden.Registration.Method', 'Captcha');
      $DefaultRoleID = C('Garden.Registration.DefaultRoles');
      switch ($RegistrationMethod) {
      
         case 'Approval':
            $RoleID = C('Garden.Registration.ApplicantRoleID', $DefaultRoleID);
         break;
         
         case 'Invitation':
            throw new Gdn_UserException(T('This forum is currently set to invitation only mode.'));
         break;
         
         case 'Basic':
         case 'Captcha':
         default:
            $RoleID = $DefaultRoleID;
         break;
      }
      
      return $RoleID;
   }
   
   public function PasswordRequest($Email) {
      $User = $this->GetWhere(array('Email' => $Email))->FirstRow();
      if (!is_object($User) || $Email == '')
         return FALSE;
      
      $PasswordResetKey = RandomString(6);
      $this->SaveAttribute($User->UserID, 'PasswordResetKey', $PasswordResetKey);
      $AppTitle = C('Garden.Title');
      $Email = new Gdn_Email();
      $Email->Subject(sprintf(T('[%s] Password Reset Request'), $AppTitle));
      $Email->To($User->Email);
      //$Email->From(Gdn::Config('Garden.Support.Email'), Gdn::Config('Garden.Support.Name'));
      $Email->Message(
         sprintf(
            T('PasswordRequest'),
            $User->Name,
            $AppTitle,
            Url('/entry/passwordreset/'.$User->UserID.'/'.$PasswordResetKey, TRUE)
         )
      );
      $Email->Send();
      return TRUE;
   }

   public function PasswordReset($UserID, $Password) {
      // Encrypt the password before saving
      $PasswordHash = new Gdn_PasswordHash();
      $Password = $PasswordHash->HashPassword($Password);

      $this->SQL->Update('User')->Set('Password', $Password)->Where('UserID', $UserID)->Put();
      $this->SaveAttribute($UserID, 'PasswordResetKey', '');
      return $this->Get($UserID);
   }
}