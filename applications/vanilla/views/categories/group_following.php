<?php if (!defined('APPLICATION')) exit();
$userID = Gdn::session()->UserID;
$categoryID = $this->Category->CategoryID;
echo followButton($categoryID);
