<?php if (!defined('APPLICATION')) exit();

if (is_object($this->_Message)) {
   echo '<div class="DismissMessage'.($this->_Message->CssClass == '' ? '' : ' '.$this->_Message->CssClass).'">';
   $Session = Gdn::Session();
   if ($this->_Message->AllowDismiss == '1' && $Session->IsValid()) {
      echo Anchor('×', '/dashboard/message/dismiss/'.$this->_Message->MessageID.'/'.$Session->TransientKey().'?Target='.$this->_Sender->SelfUrl, 'Dismiss');
   }
   
   echo Gdn_Format::To($this->_Message->Content, 'Html');
   echo '</div>';
}