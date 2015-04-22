<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/

class InvitationModel extends Gdn_Model {
   /**
    * Class constructor. Defines the related database table name.
    */
   public function __construct() {
      parent::__construct('Invitation');
   }

   public function GetByInvitationID($InvitationID) {
      $DataSet = $this->SQL->From('Invitation i')
         ->Join('User su', 'i.InsertUserID = su.UserID')
         ->Join('User au', 'i.AcceptedUserID = au.UserID', 'left')
         ->Select('i.*')
         ->Select('au.UserID', '', 'AcceptedUserID')
         ->Select('au.Email', '', 'AcceptedEmail')
         ->Select('au.Name', '', 'AcceptedName')
         ->Select('su.UserID', '', 'SenderUserID')
         ->Select('su.Email', '', 'SenderEmail')
         ->Select('su.Name', '', 'SenderName')
         ->Where('i.InvitationID', $InvitationID)
         ->Get();
      return $DataSet->FirstRow();
   }

   public function GetByUserID($UserID, $InvitationID = '', $Limit = 30, $Offset = 0) {
      $this->SQL->Select('i.*')
         ->Select('u.Name', '', 'AcceptedName')
         ->From('Invitation i')
         ->Join('User u', 'i.AcceptedUserID = u.UserID', 'left')
         ->Where('i.InsertUserID', $UserID)
         ->OrderBy('i.DateInserted', 'desc')
         ->Limit($Limit, $Offset);


      if (is_numeric($InvitationID))
         $this->SQL->Where('Invitation.InvitationID', $InvitationID);

      return $this->SQL->Get();
   }

   public function Save($FormPostValues, $UserModel, $Options = array()) {
      $Session = Gdn::Session();
      $UserID = $Session->UserID;
      $SendEmail = val('SendEmail', $Options, true);
      $Resend = val('Resend', $Options, false);

      // Define the primary key in this model's table.
      $this->DefineSchema();

      // Add & apply any extra validation rules:
      $this->Validation->ApplyRule('Email', 'Email');

      // Make sure required db fields are present.
      $this->AddInsertFields($FormPostValues);
      if (!isset($FormPostValues['DateExpires'])) {
         $Expires = strtotime(C('Garden.Registration.InviteExpiration'));
         if ($Expires > time()) {
            $FormPostValues['DateExpires'] = Gdn_Format::ToDateTime($Expires);
         }
      }

      $FormPostValues['Code'] = $this->GetInvitationCode();

      // Validate the form posted values
      if ($this->Validate($FormPostValues, true) === true) {
         $Fields = $this->Validation->ValidationFields(); // All fields on the form that need to be validated
         $Email = ArrayValue('Email', $Fields, '');

         // Make sure this user has a spare invitation to send.
         $InviteCount = $UserModel->GetInvitationCount($UserID);
         if ($InviteCount == 0) {
            $this->Validation->AddValidationResult('Email', 'You do not have enough invitations left.');
            return false;
         }

         // Make sure that the email does not already belong to an account in the application.
         $TestData = $UserModel->GetWhere(array('Email' => $Email));
         if ($TestData->NumRows() > 0) {
            $this->Validation->AddValidationResult('Email', 'The email you have entered is already related to an existing account.');
            return false;
         }

         // Make sure that the email does not already belong to an invitation in the application.
         $TestData = $this->GetWhere(array('Email' => $Email));
         $DeleteID = false;
         if ($TestData->NumRows() > 0) {
            if (!$Resend) {
               $this->Validation->AddValidationResult('Email', 'An invitation has already been sent to the email you entered.');
               return false;
            } else {
               // Mark the old invitation for deletion.
               $DeleteID = val('InvitationID', $TestData->FirstRow(DATASET_TYPE_ARRAY));
            }
         }

         // Define the fields to be inserted
         $Fields = $this->Validation->SchemaValidationFields();

         // Call the base model for saving
         $InvitationID = $this->Insert($Fields);

         // Delete an old invitation.
         if ($InvitationID && $DeleteID) {
            $this->Delete($DeleteID);
         }

         // Now that saving has succeeded, update the user's invitation settings
         if ($InviteCount > 0) {
            $UserModel->ReduceInviteCount($UserID);
         }

         // And send the invitation email
         if ($SendEmail) {
            try {
               $this->Send($InvitationID);
            } catch (Exception $ex) {
               $this->Validation->AddValidationResult('Email', sprintf(T('Although the invitation was created successfully, the email failed to send. The server reported the following error: %s'), strip_tags($ex->getMessage())));
               return false;
            }
         }
         return true;
      }
      return false;
   }

   public function Send($InvitationID) {
      $Invitation = $this->GetByInvitationID($InvitationID);
      $Session = Gdn::Session();
      if ($Invitation === FALSE) {
         throw new Exception(T('ErrorRecordNotFound'));
      } elseif ($Session->UserID != $Invitation->SenderUserID) {
         throw new Exception(T('InviteErrorPermission', T('ErrorPermission')));
      } else {
         // Some information for the email
         $RegistrationUrl = ExternalUrl("entry/registerinvitation/{$Invitation->Code}");

         $AppTitle = Gdn::Config('Garden.Title');
         $Email = new Gdn_Email();
         $Email->Subject(sprintf(T('[%s] Invitation'), $AppTitle));
         $Email->To($Invitation->Email);
         $Email->Message(
            sprintf(
               T('EmailInvitation'),
               $Invitation->SenderName,
               $AppTitle,
               $RegistrationUrl
            )
         );
         $Email->Send();
      }
   }

   public function Delete($InvitationID) {
      $Session = Gdn::Session();
      $UserID = $Session->UserID;

      // Validate that this user can delete this invitation:
      $Invitation = $this->GetID($InvitationID, DATASET_TYPE_ARRAY);

      // Does the invitation exist?
      if (!$Invitation)
         throw NotFoundException('Invitation');

      // Does this user own the invitation?
      if ($UserID != $Invitation['InsertUserID'] && !$Session->CheckPermission('Garden.Moderation.Manage'))
         throw PermissionException('@'.T('InviteErrorPermission', T('ErrorPermission')));

      // Delete it.
      $this->SQL->Delete($this->Name, array('InvitationID' => $InvitationID));

      // Add the invitation back onto the user's account if the invitation has not been accepted.
      if (!$Invitation->AcceptedUserID) {
         Gdn::UserModel()->IncreaseInviteCount($UserID);
      }

      return TRUE;
   }

   /**
    * Returns a unique 8 character invitation code
    */
   protected function GetInvitationCode() {
      // Generate a new invitation code.
      $Code = BetterRandomString(16, 'Aa0');

      // Make sure the string doesn't already exist in the invitation table
      $CodeData = $this->GetWhere(array('Code' => $Code));
      if ($CodeData->NumRows() > 0) {
         return $this->GetInvitationCode();
      } else {
         return $Code;
      }
   }
}
