<?php if(!defined('APPLICATION')) exit();
/* Copyright 2013-2014 Zachary Doll */
function DiscussionPollAnswerForm($PollForm, $Poll, $PartialAnswers) {
  echo '<div class="DP_AnswerForm">';
  if(GetValue('Title', $Poll) || C('Plugins.DiscussionPolls.DisablePollTitle', FALSE)) {
    echo $Poll->Title;
    if(trim($Poll->Title) != FALSE) {
      echo '<hr />';
    }
  }
  echo $PollForm->Open(array('action' => Url('/discussion/poll/submit/'), 'method' => 'post', 'ajax' => TRUE));
  echo $PollForm->Errors();

  $m = 0;
  // Render poll questions
  echo '<ol class="DP_AnswerQuestions">';
  foreach($Poll->Questions as $Question) {
    echo '<li class="DP_AnswerQuestion">';
    echo $PollForm->Hidden('DP_AnswerQuestions[]', array('value' => $Question->QuestionID));
    echo Wrap($Question->Title, 'span');
    echo '<ol class="DP_AnswerOptions">';
    foreach($Question->Options as $Option) {
      if(GetValue($Question->QuestionID, $PartialAnswers) == $Option->OptionID) {
        //fill in partial answer
        echo Wrap($PollForm->Radio('DP_Answer' . $m, $Option->Title, array('Value' => $Option->OptionID, 'checked' => 'checked')), 'li');
      }
      else {
        echo Wrap($PollForm->Radio('DP_Answer' . $m, $Option->Title, array('Value' => $Option->OptionID)), 'li');
      }
    }
    echo '</ol>';
    echo '</li>';
    $m++;
  }
  echo '</ol>';
  echo $PollForm->Close('Submit');
  echo '</div>';
}
