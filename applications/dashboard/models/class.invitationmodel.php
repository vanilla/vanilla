<?php
/**
 * Invitation model.
 *
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Dashboard
 * @since 2.0
 */

/**
 * Handles invitation data.
 */
class InvitationModel extends Gdn_Model {

    /**
     * Class constructor. Defines the related database table name.
     */
    public function __construct() {
        parent::__construct('Invitation');
    }

    /**
     *
     *
     * @param $InvitationID
     * @return array|bool|stdClass
     */
    public function getByInvitationID($InvitationID) {
        $DataSet = $this->SQL->from('Invitation i')
            ->join('User su', 'i.InsertUserID = su.UserID')
            ->join('User au', 'i.AcceptedUserID = au.UserID', 'left')
            ->select('i.*')
            ->select('au.UserID', '', 'AcceptedUserID')
            ->select('au.Email', '', 'AcceptedEmail')
            ->select('au.Name', '', 'AcceptedName')
            ->select('su.UserID', '', 'SenderUserID')
            ->select('su.Email', '', 'SenderEmail')
            ->select('su.Name', '', 'SenderName')
            ->where('i.InvitationID', $InvitationID)
            ->get();
        return $DataSet->firstRow();
    }

    /**
     *
     *
     * @param $UserID
     * @param string $InvitationID
     * @param int $Limit
     * @param int $Offset
     * @return Gdn_DataSet
     * @throws Exception
     */
    public function getByUserID($UserID, $InvitationID = '', $Limit = 30, $Offset = 0) {
        $this->SQL->select('i.*')
            ->select('u.Name', '', 'AcceptedName')
            ->from('Invitation i')
            ->join('User u', 'i.AcceptedUserID = u.UserID', 'left')
            ->where('i.InsertUserID', $UserID)
            ->orderBy('i.DateInserted', 'desc')
            ->limit($Limit, $Offset);


        if (is_numeric($InvitationID)) {
            $this->SQL->where('Invitation.InvitationID', $InvitationID);
        }

        return $this->SQL->get();
    }

    /**
     *
     *
     * @param array $FormPostValues
     * @param array|bool $UserModel
     * @param array $Options
     * @return bool
     * @throws Exception
     */
    public function save($FormPostValues, $UserModel, $Options = array()) {
        $Session = Gdn::session();
        $UserID = $Session->UserID;
        $SendEmail = val('SendEmail', $Options, true);
        $Resend = val('Resend', $Options, false);

        // Define the primary key in this model's table.
        $this->defineSchema();

        // Add & apply any extra validation rules:
        $this->Validation->applyRule('Email', 'Email');

        // Make sure required db fields are present.
        $this->AddInsertFields($FormPostValues);
        if (!isset($FormPostValues['DateExpires'])) {
            $Expires = strtotime(c('Garden.Registration.InviteExpiration'));
            if ($Expires > time()) {
                $FormPostValues['DateExpires'] = Gdn_Format::toDateTime($Expires);
            }
        }

        $FormPostValues['Code'] = $this->GetInvitationCode();

        // Validate the form posted values
        if ($this->validate($FormPostValues, true) === true) {
            $Fields = $this->Validation->ValidationFields(); // All fields on the form that need to be validated
            $Email = arrayValue('Email', $Fields, '');

            // Make sure this user has a spare invitation to send.
            $InviteCount = $UserModel->GetInvitationCount($UserID);
            if ($InviteCount == 0) {
                $this->Validation->addValidationResult('Email', 'You do not have enough invitations left.');
                return false;
            }

            // Make sure that the email does not already belong to an account in the application.
            $TestData = $UserModel->getWhere(array('Email' => $Email));
            if ($TestData->numRows() > 0) {
                $this->Validation->addValidationResult('Email', 'The email you have entered is already related to an existing account.');
                return false;
            }

            // Make sure that the email does not already belong to an invitation in the application.
            $TestData = $this->getWhere(array('Email' => $Email));
            $DeleteID = false;
            if ($TestData->numRows() > 0) {
                if (!$Resend) {
                    $this->Validation->addValidationResult('Email', 'An invitation has already been sent to the email you entered.');
                    return false;
                } else {
                    // Mark the old invitation for deletion.
                    $DeleteID = val('InvitationID', $TestData->firstRow(DATASET_TYPE_ARRAY));
                }
            }

            // Define the fields to be inserted
            $Fields = $this->Validation->SchemaValidationFields();

            // Call the base model for saving
            $InvitationID = $this->insert($Fields);

            // Delete an old invitation.
            if ($InvitationID && $DeleteID) {
                $this->delete($DeleteID);
            }

            // Now that saving has succeeded, update the user's invitation settings
            if ($InviteCount > 0) {
                $UserModel->ReduceInviteCount($UserID);
            }

            // And send the invitation email
            if ($SendEmail) {
                try {
                    $this->send($InvitationID);
                } catch (Exception $ex) {
                    $this->Validation->addValidationResult('Email', sprintf(t('Although the invitation was created successfully, the email failed to send. The server reported the following error: %s'), strip_tags($ex->getMessage())));
                    return false;
                }
            }
            return true;
        }
        return false;
    }

    /**
     *
     *
     * @param $InvitationID
     * @throws Exception
     */
    public function send($InvitationID) {
        $Invitation = $this->GetByInvitationID($InvitationID);
        $Session = Gdn::session();
        if ($Invitation === false) {
            throw new Exception(t('ErrorRecordNotFound'));
        } elseif ($Session->UserID != $Invitation->SenderUserID) {
            throw new Exception(t('InviteErrorPermission', t('ErrorPermission')));
        } else {
            // Some information for the email
            $RegistrationUrl = ExternalUrl("entry/registerinvitation/{$Invitation->Code}");

            $AppTitle = Gdn::config('Garden.Title');
            $Email = new Gdn_Email();
            $Email->subject(sprintf(t('[%s] Invitation'), $AppTitle));
            $Email->to($Invitation->Email);
            $Email->message(
                sprintf(
                    t('EmailInvitation'),
                    $Invitation->SenderName,
                    $AppTitle,
                    $RegistrationUrl
                )
            );
            $Email->send();
        }
    }

    /**
     *
     *
     * @param string|unknown_type $InvitationID
     * @return bool
     * @throws Exception
     */
    public function delete($InvitationID) {
        $Session = Gdn::session();
        $UserID = $Session->UserID;

        // Validate that this user can delete this invitation:
        $Invitation = $this->getID($InvitationID, DATASET_TYPE_ARRAY);

        // Does the invitation exist?
        if (!$Invitation) {
            throw notFoundException('Invitation');
        }

        // Does this user own the invitation?
        if ($UserID != $Invitation['InsertUserID'] && !$Session->checkPermission('Garden.Moderation.Manage')) {
            throw permissionException('@'.t('InviteErrorPermission', t('ErrorPermission')));
        }

        // Delete it.
        $this->SQL->delete($this->Name, array('InvitationID' => $InvitationID));

        // Add the invitation back onto the user's account if the invitation has not been accepted.
        if (!$Invitation->AcceptedUserID) {
            Gdn::userModel()->IncreaseInviteCount($UserID);
        }

        return true;
    }

    /**
     * Returns a unique 8 character invitation code.
     */
    protected function getInvitationCode() {
        // Generate a new invitation code.
        $Code = BetterRandomString(16, 'Aa0');

        // Make sure the string doesn't already exist in the invitation table
        $CodeData = $this->getWhere(array('Code' => $Code));
        if ($CodeData->numRows() > 0) {
            return $this->GetInvitationCode();
        } else {
            return $Code;
        }
    }
}
