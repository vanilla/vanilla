<?php if (!defined('APPLICATION')) exit();
$Message = $this->_Message;


if (is_array($Message)) {
   echo '<div class="DismissMessage'.($Message['CssClass'] == '' ? '' : ' '.$Message['CssClass']).'">';
   $Session = Gdn::Session();
   if ($Message['AllowDismiss'] == '1' && $Session->IsValid()) {
      echo Anchor('×', "/dashboard/message/dismiss/{$Message['MessageID']}/".$Session->TransientKey().'?Target='.$this->_Sender->SelfUrl, 'Dismiss');
   }
   
   // echo Gdn_Format::To($this->_Message->Content, 'Html');
   echo nl2br($Message['Content']);
   echo '</div>';
}