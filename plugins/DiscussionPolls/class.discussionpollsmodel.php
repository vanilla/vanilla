<?php if(!defined('APPLICATION')) exit();
/* 	Copyright 2013-2014 Zachary Doll
 * 	This program is free software: you can redistribute it and/or modify
 * 	it under the terms of the GNU General Public License as published by
 * 	the Free Software Foundation, either version 3 of the License, or
 * 	(at your option) any later version.
 *
 * 	This program is distributed in the hope that it will be useful,
 * 	but WITHOUT ANY WARRANTY; without even the implied warranty of
 * 	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * 	GNU General Public License for more details.
 *
 * 	You should have received a copy of the GNU General Public License
 * 	along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

class DiscussionPollsModel extends Gdn_Model {

  //query cache
  private static $Cache = array('Exists' => array(), 'Answered' => array(), 'Responses' => array(), 'Partial' => array(), 'Get' => array());

  /**
   * Class constructor. Defines the related database table name.
   */
  public function __construct() {
    parent::__construct('DiscussionPolls');
  }

  /**
   * Determines if a poll associated with the discussion exists
   * @param int $DiscussionID
   * @return boolean
   */
  public function Exists($DiscussionID) {
    //check for cached result
    $Data = GetValueR('Exists.' . $DiscussionID, self::$Cache);

    if(!empty($Data)) {
      return TRUE;
    }

    $this->SQL
            ->Select('PollID')
            ->From('DiscussionPolls')
            ->Where('DiscussionID', $DiscussionID);

    $Data = $this->SQL->Get()->FirstRow();
    //store in cache
    self::$Cache['Exists'][$DiscussionID] = $Data;
    return !empty($Data);
  }

  /**
   * Determines if a poll associated with the discussion has been answered at all
   * @param int $DiscussionID
   * @return boolean
   */
  public function HasResponses($DiscussionID) {
    //check for cached result
    $Data = GetValueR('Responses.' . $DiscussionID, self::$Cache);

    if(!empty($Data)) {
      return $Data;
    }

    $this->SQL
            ->Select('p.PollID')
            ->From('DiscussionPolls p')
            ->Join('DiscussionPollAnswers a', 'p.PollID = a.PollID')
            ->Where('p.DiscussionID', $DiscussionID);

    $Data = $this->SQL->Get()->Result();
    //store in cache
    self::$Cache['Responses'][$DiscussionID] = $Data;
    return !empty($Data);
  }

  /**
   * Gets a poll object associated with a poll ID which does not include votes
   * @param int $PollID
   * @return stdClass Poll object
   */
  public function Get($PollID) {
    //check for cached result
    $Data = GetValueR('Get.' . $PollID, self::$Cache);

    if(!empty($Data)) {
      return $Data;
    }

    $this->SQL
            ->Select('p.*')
            ->Select('q.Text', '', 'Question')
            ->Select('q.QuestionID')
            ->Select('q.CountResponses')
            ->Select('o.Text', '', 'Option')
            ->Select('o.CountVotes', '', 'CountVotes')
            ->Select('o.OptionID')
            ->From('DiscussionPolls p')
            ->Join('DiscussionPollQuestions q', 'p.PollID = q.PollID')
            ->Join('DiscussionPollQuestionOptions o', 'q.QuestionID = o.QuestionID')
            ->Where('p.PollID', $PollID);

    $DBResult = $this->SQL->Get()->Result();

    if(!empty($DBResult)) {
      $Data = array(
          'PollID' => $DBResult[0]->PollID,
          'DiscussionID' => $DBResult[0]->DiscussionID,
          'Title' => $DBResult[0]->Text,
          'IsOpen' => $DBResult[0]->Open,
          'Questions' => array()
      );
    }
    else {
      // Pass an empty array back
      $Data = array(
          'PollID' => '',
          'DiscussionID' => '',
          'Title' => '',
          'IsOpen' => '',
          'Questions' => array()
      );
    }
    // Loop through the result and assemble an associative array
    foreach($DBResult as $Row) {
      if(array_key_exists($Row->QuestionID, $Data['Questions'])) {
        // Just add the option
        $Data['Questions'][$Row->QuestionID]['Options'][] = array('OptionID' => $Row->OptionID, 'Title' => $Row->Option, 'CountVotes' => $Row->CountVotes);
      }
      else {
        // First time seeing this question
        // Add it and the first option
        $Data['Questions'][$Row->QuestionID] = array(
            'QuestionID' => $Row->QuestionID,
            'Title' => $Row->Question,
            'Options' => array(array('OptionID' => $Row->OptionID, 'Title' => $Row->Option, 'CountVotes' => $Row->CountVotes)),
            'CountResponses' => $Row->CountResponses
        );
      }
    }

    // convert array to object
    $DObject = json_decode(json_encode($Data));
    //store in cache
    self::$Cache['Get'][$PollID] = $DObject;
    return $DObject;
  }

  /**
   * Convenience method to get a poll object associated with a discussion ID
   * @param int $DiscussionID
   * @return stdClass Poll object
   */
  public function GetByDiscussionID($DiscussionID) {
    //check for cached result
    $Data = GetValueR('Exists.' . $DiscussionID, self::$Cache);

    if(!empty($Data)) {
      $PollID = $Data->PollID;
    }
    else {
      $this->SQL
              ->Select('p.PollID')
              ->From('DiscussionPolls p')
              ->Where('p.DiscussionID', $DiscussionID);

      $Data = $this->SQL->Get()->FirstRow();

      if(!empty($Data)) {
        //store in cache
        self::$Cache['Exists'][$DiscussionID] = $Data;
        $PollID = $Data->PollID;
      }
      else {
        $PollID = NULL;
      }
    }
    return $this->Get($PollID);
  }

  /**
   * Saves the poll object
   * @param array $FormPostValues
   */
  public function Save($FormPostValues) {
    //paranoid
    self::PurgeCache();
    try {
      $this->Database->BeginTransaction();

      $this->SQL->Insert('DiscussionPolls', array(
          'DiscussionID' => $FormPostValues['DiscussionID'],
          'Text' => $FormPostValues['DP_Title']));

      // Select the poll ID
      $this->SQL
              ->Select('p.PollID')
              ->From('DiscussionPolls p')
              ->Where('p.DiscussionID', $FormPostValues['DiscussionID']);

      $PollID = $this->SQL->Get()->FirstRow()->PollID;

      // Insert the questions
      foreach($FormPostValues['DP_Questions'] as $Index => $Question) {
        $this->SQL
                ->Insert('DiscussionPollQuestions', array(
                    'PollID' => $PollID,
                    'Text' => $Question)
        );
      }

      // Select the question IDs
      $this->SQL
              ->Select('q.QuestionID')
              ->From('DiscussionPollQuestions q')
              ->Where('q.PollID', $PollID);
      $QuestionIDs = $this->SQL->Get()->Result();

      // Insert the Options
      foreach($QuestionIDs as $Index => $QuestionID) {
        $QuestionOptions = ArrayValue('DP_Options' . $Index, $FormPostValues);
        foreach($QuestionOptions as $Option) {
          $this->SQL
                  ->Insert('DiscussionPollQuestionOptions', array(
                      'QuestionID' => $QuestionID->QuestionID,
                      'PollID' => $PollID,
                      'Text' => $Option)
          );
        }
      }

      $this->Database->CommitTransaction();
    }
    catch(Exception $Ex) {
      $this->Database->RollbackTransaction();
      throw $Ex;
    }
  }

  /**
   * Returns whether or not a user has answered a poll
   * @param int $PollID
   * @param int $UserID
   * @return boolean
   */
  public function HasAnswered($PollID, $UserID) {
    //check for cached result
    $Data = GetValueR('Answered.' . $PollID . '_' . $UserID, self::$Cache);

    if(!empty($Data)) {
      return TRUE;
    }

    $this->SQL
            ->Select('q.PollID, a.UserID')
            ->From('DiscussionPollQuestions q')
            ->Join('DiscussionPollAnswers a', 'q.QuestionID = a.QuestionID')
            ->Where('q.PollID', $PollID)
            ->Where('a.UserID', $UserID);

    $Result = $this->SQL->Get()->Result();
    //store in cache
    self::$Cache['Answered'][$PollID . '_' . $UserID] = $Result;
    return !empty($Result);
  }

  /**
   * Returns whether or not a user has partially answered a poll
   * @param int $PollID
   * @param int $UserID
   * @return mixed false or result
   */
  public function PartialAnswer($PollID, $UserID) {
    //check for cached result
    $Data = GetValueR('Partial.' . $PollID . '_' . $UserID, self::$Cache);

    if(!empty($Data)) {
      return $Data;
    }

    $this->SQL
            ->Select('pa.*')
            ->From('DiscussionPollAnswerPartial pa')
            ->Where('pa.PollID', $PollID)
            ->Where('pa.UserID', $UserID);
    $Answered = array();
    $Answers = $this->SQL->Get()->Result();

    if(empty($Answers)) {
      return $Answered;
    }
    
    //create simple lookup
    foreach($Answers As $Answer) {
      $Answered[$Answer->QuestionID] = $Answer->OptionID;
    }
    //store in cache
    self::$Cache['Partial'][$PollID . '_' . $UserID] = $Answered;
    return $Answered;
  }

  /**
   * Inserts a poll vote for a specific user
   * @param array $FormPostValues
   * @param int $UserID
   * @return boolean False indicates the user has already voted
   */
  public function SaveAnswer($FormPostValues, $UserID) {
    //remove partial answers
    $this->PurgePartialAnswers($FormPostValues['PollID'], $UserID);

    //paranoid
    self::PurgeCache();

    if($this->HasAnswered($FormPostValues['PollID'], $UserID)) {
      return FALSE;
    }
    else {
      try {
        $this->Database->BeginTransaction();
        $this->_InsertAnswerData($FormPostValues, $UserID);
        $this->Database->CommitTransaction();
      }
      catch(Exception $Ex) {
        $this->Database->RollbackTransaction();
        throw $Ex;
      }
      return TRUE;
    }

    return FALSE;
  }
  
  protected function _InsertAnswerData($FormPostValues, $UserID) {
    foreach($FormPostValues['DP_AnswerQuestions'] as $Index => $QuestionID) {
      $MemberKey = 'DP_Answer' . $Index;
      $this->SQL
              ->Insert('DiscussionPollAnswers', array(
                  'PollID' => $FormPostValues['PollID'],
                  'QuestionID' => $QuestionID,
                  'UserID' => $UserID,
                  'OptionID' => $FormPostValues[$MemberKey])
      );

      $this->SQL
              ->Update('DiscussionPollQuestions')
              ->Set('CountResponses', 'CountResponses + 1', FALSE)
              ->Where('QuestionID', $QuestionID)
              ->Put();

      $this->SQL
              ->Update('DiscussionPollQuestionOptions')
              ->Set('CountVotes', 'CountVotes + 1', FALSE)
              ->Where('OptionID', $FormPostValues[$MemberKey])
              ->Put();
    }
  }

  /**
   * Stashes Partial Answers
   * @param array $FormPostValues
   * @param int $UserID
   * @return boolean False if nothing saved
   */
  public function SavePartialAnswer($FormPostValues, $UserID) {
    $Return = FALSE;
    try {
      $this->Database->BeginTransaction();
      //remove partial answers
      $this->PurgePartialAnswers($FormPostValues['PollID'], $UserID);
      foreach($FormPostValues['DP_AnswerQuestions'] as $Index => $QuestionID) {
        $MemberKey = 'DP_Answer' . $Index;
        //ensure no null values
        if(GetValue($MemberKey, $FormPostValues)) {
          $this->SQL
                  ->Insert('DiscussionPollAnswerPartial', array(
                      'PollID' => $FormPostValues['PollID'],
                      'QuestionID' => $QuestionID,
                      'UserID' => $UserID,
                      'OptionID' => $FormPostValues[$MemberKey])
          );
        }
        $Return = TRUE;
      }

      $this->Database->CommitTransaction();
    }
    catch(Exception $Ex) {
      $this->Database->RollbackTransaction();
      error_log($Ex->getMessage());
    }

    return $Return;
  }

  /**
   * Remove Partial Answers from the database
   * @param int $PollID
   * @param int $UserID
   * @return boolean
   */
  public function PurgePartialAnswers($PollID, $UserID) {
    //purge cache
    self::$Cache['Partial'][$PollID . '_' . $UserID] = NULL;
    //remove from db
    return $this->SQL->Delete('DiscussionPollAnswerPartial', array('PollID' => $PollID, 'UserID' => $UserID));
  }

  /**
   * Make sure there are enough answered question for the poll submission
   * @param array $FormPostValues
   * @return boolean
   */
  public function CheckFullyAnswered($FormPostValues) {
    $Answered = array();
    foreach($FormPostValues['DP_AnswerQuestions'] as $Index => $QuestionID) {
      $MemberKey = 'DP_Answer' . $Index;
      if(GetValue($MemberKey, $FormPostValues)) {
        $Answered[$QuestionID] = $FormPostValues[$MemberKey];
      }
    }
    $Poll = $this->Get($FormPostValues['PollID']);

    return count((array) $Poll->Questions) == count($Answered);
  }

  /**
   * Removes all data associated with the poll id
   * @param int $PollID
   */
  public function Delete($PollID) {
    try {
      $this->Database->BeginTransaction();
      $this->SQL->Delete('DiscussionPolls', array('PollID' => $PollID));
      $this->SQL->Delete('DiscussionPollQuestions', array('PollID' => $PollID));
      $this->SQL->Delete('DiscussionPollQuestionOptions', array('PollID' => $PollID));
      $this->SQL->Delete('DiscussionPollAnswers', array('PollID' => $PollID));
      $this->SQL->Delete('DiscussionPollAnswerPartial', array('PollID' => $PollID));
      //clear cache
      self::PurgeCache();
      $this->Database->CommitTransaction();
    }
    catch(Exception $Ex) {
      $this->Database->RollbackTransaction();
      throw $Ex;
    }
  }

  /**
   * A convenience method that removes all poll data associated with the
   * discussion id
   * @param int $DiscussionID
   */
  public function DeleteByDiscussionID($DiscussionID) {
    // make sure it exists
    if($this->Exists($DiscussionID)) {
      // no use caching as delete will wipe it out
      $this->SQL
              ->Select('p.PollID')
              ->From('DiscussionPolls p')
              ->Where('p.DiscussionID', $DiscussionID);

      $Data = $this->SQL->Get()->FirstRow();
      $PollID = $Data->PollID;

      return $this->Delete($PollID);
    }
  }

  /**
   * Closes poll associated with the discussion id
   * @param int $DiscussionID
   */
  public function Close($DiscussionID) {
    $this->SQL
            ->Update('DiscussionPolls p')
            ->Set('Open', 0)
            ->Where('p.DiscussionID', $DiscussionID)
            ->Put();
  }

  /**
   * Returns if the poll associated with a discussion id is closed or open.
   * If the poll doesn't exist, it will return true.
   * @param int $DiscussionID
   * @return boolean
   */
  public function IsClosed($DiscussionID) {
    $this->SQL
            ->Select('p.Open')
            ->From('DiscussionPolls p')
            ->Where('p.DiscussionID', $DiscussionID);
    $IsOpen = $this->SQL->Get()->FirstRow()->Open;

    return !$IsOpen;
  }

  /**
   * Wipes the cache
   */
  public static function PurgeCache() {
    // reset all the store
    foreach(self::$Cache As &$CachStore) {
      $CachStore = array();
    }
  }

}
