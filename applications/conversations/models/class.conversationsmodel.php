<?php if (!defined('APPLICATION')) exit();
/**
 * @copyright 2009-2014 Vanilla Forums Inc.
 * @license GNU GPLv2
 */

/**
 * Introduces common methods that child classes can use.
 *
 * @since 2.2
 * @package Conversations
 */
abstract class ConversationsModel extends Gdn_Model {
   /**
    * Class constructor. Defines the related database table name.
    *
    * @since 2.2
    * @access public
    */
   public function __construct($Name = '') {
      parent::__construct($Name);
   }

   /**
    * Checks to see if the user is spamming. Returns TRUE if the user is spamming.
    *
    * Users cannot post more than $SpamCount comments within $SpamTime
    * seconds or their account will be locked for $SpamLock seconds.
    *
    * @since 2.2
    * @return bool Whether spam check is positive (TRUE = spammer).
    */
   public function CheckForSpam($Type) {
      // If spam checking is disabled or user is an admin, skip
      $SpamCheckEnabled = GetValue('SpamCheck', $this, TRUE);
      if ($SpamCheckEnabled === FALSE || CheckPermission('Garden.Moderation.Manage'))
         return FALSE;

      $Spam = FALSE;

      // Validate $Type
      if (!in_array($Type, array('Conversation', 'ConversationMessage'))) {
         trigger_error(ErrorMessage(sprintf('Spam check type unknown: %s', $Type), 'ConversationsModel', 'CheckForSpam'), E_USER_ERROR);
      }

      $CountSpamCheck = Gdn::Session()->GetAttribute('Count'.$Type.'SpamCheck', 0);
      $DateSpamCheck = Gdn::Session()->GetAttribute('Date'.$Type.'SpamCheck', 0);
      $SecondsSinceSpamCheck = time() - Gdn_Format::ToTimestamp($DateSpamCheck);

      // Get spam config settings
      $SpamCount = C("Conversations.$Type.SpamCount", 5); // 5 messages
      if (!is_numeric($SpamCount) || $SpamCount < 2)
         $SpamCount = 2; // 2 spam minimum

      $SpamTime = C("Conversations.$Type.SpamTime", 60); // 1 minute
      if (!is_numeric($SpamTime) || $SpamTime < 0)
         $SpamTime = 30; // 30 second minimum spam span

      $SpamLock = C("Conversations.$Type.SpamLock", 300); // 5 minutes
      if (!is_numeric($SpamLock) || $SpamLock < 30)
         $SpamLock = 30; // 30 second minimum lockout

      // Apply a spam lock if necessary
      $Attributes = array();
      if ($SecondsSinceSpamCheck < $SpamLock && $CountSpamCheck >= $SpamCount && $DateSpamCheck !== FALSE) {
         $Spam = TRUE;
         $this->Validation->AddValidationResult(
            'Body',
            sprintf(
               T('You have posted %1$s times within %2$s seconds. A spam block is now in effect on your account. You must wait at least %3$s seconds before attempting to post again.'),
               $SpamCount,
               $SpamTime,
               $SpamLock
            )
         );

         // Update the 'waiting period' every time they try to post again
         $Attributes['Date'.$Type.'SpamCheck'] = Gdn_Format::ToDateTime();
      } else {

         if ($SecondsSinceSpamCheck > $SpamTime) {
            $Attributes['Count'.$Type.'SpamCheck'] = 1;
            $Attributes['Date'.$Type.'SpamCheck'] = Gdn_Format::ToDateTime();
         } else {
            $Attributes['Count'.$Type.'SpamCheck'] = $CountSpamCheck + 1;
         }
      }
      // Update the user profile after every comment
      $UserModel = Gdn::UserModel();
      if (Gdn::Session()->UserID)
         $UserModel->SaveAttribute(Gdn::Session()->UserID, $Attributes);

      return $Spam;
   }
}