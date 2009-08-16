<?php if (!defined('APPLICATION')) exit();

if (is_object($this->_Message)) {
   echo '<div class="DismissMessage'.($this->_Message->CssClass == '' ? '' : ' '.$this->_Message->CssClass).'">';
   if ($this->_Message->AllowDismiss == '1' ) {
      $Session = Gdn::Session();
      echo Anchor('Dismiss', '/garden/message/dismiss/'.$this->_Message->MessageID.'/'.$Session->TransientKey().'?Target='.$this->_Sender->SelfUrl, 'Dismiss');
   }
   
   echo Format::To($this->_Message->Content, 'Html');
   echo '</div>';
}