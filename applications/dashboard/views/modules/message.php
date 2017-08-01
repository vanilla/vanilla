<?php if (!defined('APPLICATION')) exit();
$Message = $this->_Message;


if (is_array($Message)) {
    echo '<div class="DismissMessage'.($Message['CssClass'] == '' ? '' : ' '.$Message['CssClass']).'">';
    $Session = Gdn::session();
    if (val('AllowDismiss', $Message) == '1' && $Session->isValid()) {
        echo anchor('&times;', "/dashboard/message/dismiss/{$Message['MessageID']}/".$Session->transientKey().'?Target='.$this->_Sender->SelfUrl, 'Dismiss');
    }

    // echo Gdn_Format::to($this->_Message->Content, 'Html');
    echo nl2br(Gdn_Format::links($Message['Content']));
    echo '</div>';
}
