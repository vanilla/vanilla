<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session();
if (!property_exists($this, 'HideActivity'))
   $this->HideActivity = FALSE;
   
if (!property_exists($this, 'ProfileUserID'))
   $this->ProfileUserID = '';
   
if (!function_exists('WriteActivity'))
   include($this->FetchViewLocation('helper_functions', 'activity'));

$Comment = property_exists($this, 'CommentData') && is_object($this->CommentData) ? $this->CommentData->NextRow() : FALSE;
foreach ($this->ActivityData->Result() as $Activity) {
   if ($this->HideActivity)
      $Activity->ActivityType .= ' Hidden';
   
   WriteActivity($Activity, $this, $Session, $Comment);
}