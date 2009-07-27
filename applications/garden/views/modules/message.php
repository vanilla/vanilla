<?php if (!defined('APPLICATION')) exit();

if (is_object($this->_Message)) {
   // todo: Wrap this string in a div with a "dismiss" link if necessary
   if ($this->_Message->AllowDismiss == '1') {
      $Session = Gdn::Session();
      echo '<div class="DismissMessage">';
      echo Anchor('Dismiss', '/garden/messages/dismiss/'.$this->_Message->MessageID.'/'.$Session->TransientKey().'?Target='.$this->_Sender->SelfUrl, 'DismissMessage');
   }
   
   echo Format::To($this->_Message->Content, 'Html');
   
   if ($this->_Message->AllowDismiss == '1')
      echo '</div>';
}