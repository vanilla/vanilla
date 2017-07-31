<?php
/**
 * Invitation model.
 *
 * @copyright 2009-2017 Vanilla Forums Inc.
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
     * @param $invitationID
     * @return array|bool|stdClass
     */
    public function getByInvitationID($invitationID) {
        $dataSet = $this->SQL->from('Invitation i')
            ->join('User su', 'i.InsertUserID = su.UserID')
            ->join('User au', 'i.AcceptedUserID = au.UserID', 'left')
            ->select('i.*')
            ->select('au.UserID', '', 'AcceptedUserID')
            ->select('au.Email', '', 'AcceptedEmail')
            ->select('au.Name', '', 'AcceptedName')
            ->select('su.UserID', '', 'SenderUserID')
            ->select('su.Email', '', 'SenderEmail')
            ->select('su.Name', '', 'SenderName')
            ->where('i.InvitationID', $invitationID)
            ->get();
        return $dataSet->firstRow();
    }

    /**
     *
     *
     * @param $userID
     * @param string $invitationID
     * @param int $limit
     * @param int $offset
     * @return Gdn_DataSet
     * @throws Exception
     */
    public function getByUserID($userID, $invitationID = '', $limit = 50, $offset = 0) {
        $this->SQL->select('i.*')
            ->select('u.Name', '', 'AcceptedName')
            ->from('Invitation i')
            ->join('User u', 'i.AcceptedUserID = u.UserID', 'left')
            ->where('i.InsertUserID', $userID)
            ->orderBy('i.DateInserted', 'desc')
            ->limit($limit, $offset);


        if (is_numeric($invitationID)) {
            $this->SQL->where('Invitation.InvitationID', $invitationID);
        }

        return $this->SQL->get();
    }

    /**
     *
     *
     * @param array $formPostValues
     * @param array|bool $userModel
     * @param array $options
     * @return bool
     * @throws Exception
     */
    public function save($formPostValues, $userModel, $options = []) {
        $session = Gdn::session();
        $userID = $session->UserID;
        $sendEmail = val('SendEmail', $options, true);
        $resend = val('Resend', $options, false);

        // Define the primary key in this model's table.
        $this->defineSchema();

        // Add & apply any extra validation rules:
        $this->Validation->applyRule('Email', 'Email');

        // Make sure required db fields are present.
        $this->AddInsertFields($formPostValues);
        if (!isset($formPostValues['DateExpires'])) {
            $expires = strtotime(c('Garden.Registration.InviteExpiration'));
            if ($expires > time()) {
                $formPostValues['DateExpires'] = Gdn_Format::toDateTime($expires);
            }
        }

        $formPostValues['Code'] = $this->GetInvitationCode();

        // Validate the form posted values
        if ($this->validate($formPostValues, true) === true) {
            $fields = $this->Validation->ValidationFields(); // All fields on the form that need to be validated
            $email = val('Email', $fields, '');

            // Make sure this user has a spare invitation to send.
            $inviteCount = $userModel->GetInvitationCount($userID);
            if ($inviteCount == 0) {
                $this->Validation->addValidationResult('Email', 'You do not have enough invitations left.');
                return false;
            }

            // Make sure that the email does not already belong to an account in the application.
            $testData = $userModel->getWhere(['Email' => $email]);
            if ($testData->numRows() > 0) {
                $this->Validation->addValidationResult('Email', 'The email you have entered is already related to an existing account.');
                return false;
            }

            // Make sure that the email does not already belong to an invitation in the application.
            $testData = $this->getWhere(['Email' => $email]);
            $deleteID = false;
            if ($testData->numRows() > 0) {
                if (!$resend) {
                    $this->Validation->addValidationResult('Email', 'An invitation has already been sent to the email you entered.');
                    return false;
                } else {
                    // Mark the old invitation for deletion.
                    $deleteID = val('InvitationID', $testData->firstRow(DATASET_TYPE_ARRAY));
                }
            }

            // Define the fields to be inserted
            $fields = $this->Validation->SchemaValidationFields();

            // Call the base model for saving
            $invitationID = $this->insert($fields);

            // Delete an old invitation.
            if ($invitationID && $deleteID) {
                $this->delete($deleteID);
            }

            // Now that saving has succeeded, update the user's invitation settings
            if ($inviteCount > 0) {
                $userModel->ReduceInviteCount($userID);
            }

            // And send the invitation email
            if ($sendEmail) {
                try {
                    $this->send($invitationID);
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
     * @param $invitationID
     * @throws Exception
     */
    public function send($invitationID) {
        $invitation = $this->GetByInvitationID($invitationID);
        $session = Gdn::session();
        if ($invitation === false) {
            throw new Exception(t('ErrorRecordNotFound'));
        } elseif ($session->UserID != $invitation->SenderUserID) {
            throw new Exception(t('InviteErrorPermission', t('ErrorPermission')));
        } else {
            // Some information for the email
            $registrationUrl = ExternalUrl("entry/registerinvitation/{$invitation->Code}");

            $appTitle = Gdn::config('Garden.Title');
            $email = new Gdn_Email();
            $email->subject(sprintf(t('[%s] Invitation'), $appTitle));
            $email->to($invitation->Email);

            $emailTemplate = $email->getEmailTemplate();
            $message = t('Hello!').' '.sprintf(t('%s has invited you to join %s.'), $invitation->SenderName, $appTitle);

            $emailTemplate->setButton($registrationUrl, t('Join this Community Now'))
                ->setMessage($message)
                ->setTitle(sprintf(t('Join %s'), $appTitle));

            $email->setEmailTemplate($emailTemplate);

            try {
                $email->send();
            } catch (Exception $e) {
                if (debug()) {
                    throw $e;
                }
            }
        }
    }

    /**
     *
     *
     * @param string|unknown_type $invitationID
     * @return bool
     * @throws Exception
     */
    public function delete($invitationID) {
        $session = Gdn::session();
        $userID = $session->UserID;

        // Validate that this user can delete this invitation:
        $invitation = $this->getID($invitationID, DATASET_TYPE_ARRAY);

        // Does the invitation exist?
        if (!$invitation) {
            throw notFoundException('Invitation');
        }

        // Does this user own the invitation?
        if ($userID != $invitation['InsertUserID'] && !$session->checkPermission('Garden.Moderation.Manage')) {
            throw permissionException('@'.t('InviteErrorPermission', t('ErrorPermission')));
        }

        // Delete it.
        $this->SQL->delete($this->Name, ['InvitationID' => $invitationID]);

        // Add the invitation back onto the user's account if the invitation has not been accepted.
        if (!$invitation->AcceptedUserID) {
            Gdn::userModel()->IncreaseInviteCount($userID);
        }

        return true;
    }

    /**
     * Returns a unique 8 character invitation code.
     */
    protected function getInvitationCode() {
        // Generate a new invitation code.
        $code = BetterRandomString(16, 'Aa0');

        // Make sure the string doesn't already exist in the invitation table
        $codeData = $this->getWhere(['Code' => $code]);
        if ($codeData->numRows() > 0) {
            return $this->GetInvitationCode();
        } else {
            return $code;
        }
    }
}
