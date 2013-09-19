<?php if (!defined('APPLICATION')) exit();
echo '<div class="BoxButtons BoxNewDiscussion">';
$Text = T('Start a New Discussion', 'New Discussion');
$UrlCode = GetValue('UrlCode', GetValue('Category', $Data), '');
$Url = '/post/discussion/'.$UrlCode;
if ($this->QueryString) {
   $Url .= (strpos($Url, '?') !== FALSE ? '&' : '?').$this->QueryString;
}
$Css = 'Button Primary Action NewDiscussion';
$Css .= strpos($this->CssClass, 'Big') !== FALSE ? ' BigButton' : '';

echo ButtonGroup($this->Buttons, $Css, $this->DefaultButton);
Gdn::Controller()->FireEvent('AfterNewDiscussionButton');

echo '</div>';