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
 * Vanilla Model
 *
 * @package Vanilla
 */
 
/**
 * Introduces common methods that child classes can use.
 *
 * @since 2.0.0
 * @package Vanilla
 */
abstract class VanillaModel extends Gdn_Model {
   /**
    * Class constructor. Defines the related database table name.
    * 
    * @since 2.0.0
    * @access public
    *
    * @param string $Name Database table name.
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
    * @since 2.0.0
    * @access public
    * @todo Remove debugging info if/when this is working correctly.
    *
    * @param string $Type Valid values are 'Comment' or 'Discussion'.
    * @return bool Whether spam check is positive (TRUE = spammer).
    */
   public function CheckForSpam($Type) {
      $Session = Gdn::Session();
      
      // If spam checking is disabled or user is an admin, skip
      $SpamCheckEnabled = GetValue('SpamCheck', $this, TRUE);
      if ($SpamCheckEnabled === FALSE || $Session->User->Admin == '1') 
         return FALSE;
      
      $Spam = FALSE;
      
      // Validate $Type
      if (!in_array($Type, array('Comment', 'Discussion')))
         trigger_error(ErrorMessage(sprintf('Spam check type unknown: %s', $Type), 'VanillaModel', 'CheckForSpam'), E_USER_ERROR);
      
      $CountSpamCheck = $Session->GetAttribute('Count'.$Type.'SpamCheck', 0);
      $DateSpamCheck = $Session->GetAttribute('Date'.$Type.'SpamCheck', 0);
      $SecondsSinceSpamCheck = time() - Gdn_Format::ToTimestamp($DateSpamCheck);
      
      // Get spam config settings
      $SpamCount = Gdn::Config('Vanilla.'.$Type.'.SpamCount');
      if (!is_numeric($SpamCount) || $SpamCount < 2)
         $SpamCount = 2; // 2 spam minimum

      $SpamTime = Gdn::Config('Vanilla.'.$Type.'.SpamTime');
      if (!is_numeric($SpamTime) || $SpamTime < 0)
         $SpamTime = 30; // 30 second minimum spam span
         
      $SpamLock = Gdn::Config('Vanilla.'.$Type.'.SpamLock');
      if (!is_numeric($SpamLock) || $SpamLock < 30)
         $SpamLock = 30; // 30 second minimum lockout

      // Apply a spam lock if necessary
      $Attributes = array();
      if ($SecondsSinceSpamCheck < $SpamLock && $CountSpamCheck >= $SpamCount && $DateSpamCheck !== FALSE) {
         // TODO: REMOVE DEBUGGING INFO AFTER THIS IS WORKING PROPERLY
         /*
         echo '<div>SecondsSinceSpamCheck: '.$SecondsSinceSpamCheck.'</div>';
         echo '<div>SpamLock: '.$SpamLock.'</div>';
         echo '<div>CountSpamCheck: '.$CountSpamCheck.'</div>';
         echo '<div>SpamCount: '.$SpamCount.'</div>';
         echo '<div>DateSpamCheck: '.$DateSpamCheck.'</div>';
         echo '<div>SpamTime: '.$SpamTime.'</div>';
         */
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
      $UserModel->SaveAttribute($Session->UserID, $Attributes);
      
      return $Spam;
   }   
}