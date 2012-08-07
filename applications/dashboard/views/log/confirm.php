<?php if (!defined('APPLICATION')) exit(); ?>
<style>
   .ExtraActionTitle {
      background: #FF9;
      margin: -10px -10px 10px;
      padding: 10px;
   }
   .ExtraAction {
      margin: 10px -10px;
      padding: 10px;
      background: #ffe;
      float: left;
      width: 100%;
   }
   .CheckBoxCell {
      float:left;
      width: 50%;
   }
   .ClearFix {
      clear: both;
   }
   .Buttons {
    margin: 10px 0 0;
   }
   .ConfirmNo {
      margin-left: 14px;
      color: #d00;
      font-weight: bold;
   }
   .ConfirmNo:hover {
      text-decoration: underline;
   }
   .WarningMessage {
      padding: 6px 10px;
      margin: 10px 0 4px;
   }
</style>
<div>
   <?php
   
   $ItemCount = $this->Data('ItemCount');

   if (!$ItemCount) {
      echo '<h1>', T('No Items Selected'), '</h1>';
      echo '<p class="Wrap">', T('Make sure you select at least one item before continuing.'), '</p>';
   } else {
      echo '<h1>', T('Please Confirm'), '</h1>';
      echo $this->Form->Open(array('id' => 'ConfirmForm', 'Action' => $this->Data('ActionUrl')));
      echo $this->Form->Errors();

      // Give a description of what is done.'
      $ShowUsers = FALSE;
      switch (strtolower($this->Data('Action'))) {
         case 'delete':
            echo Wrap(T('Deleting forever cannot be undone.', 'Deleting is a good way to keep your forum clean.'), 'p');
            echo '<div class="WarningMessage">'.T('Warning: deleting is permanent', 'WARNING: deleted items are removed from this list and cannot be brought back.').'</div>';
            $AfterHtml = Plural($ItemCount, T('Are you sure you want to delete 1 item forever?'), T('Are you sure you want to delete %s items forever?'));
            break;
         case 'restore':
            echo Wrap(T('Restoring your selection removes the items from this list.', 'When you restore, the items are removed from this list and put back into the site.'), 'p');
            $AfterHtml = Plural($ItemCount, T('Are you sure you want to restore 1 item?'), T('Are you sure you want to restore %s items?'));
            break;
         case 'deletespam':
            echo Wrap(T('Marking as spam cannot be undone.', 'Marking something as SPAM will cause it to be deleted forever. Deleting is a good way to keep your forum clean.'), 'p');
            echo '<div class="WarningMessage">'.T('Warning: deleting is permanent', 'WARNING: deleted items are removed from this list and cannot be brought back.').'</div>';
            $AfterHtml = T('Are you ABSOLUTELY sure you want to take this action?');
            $ShowUsers = TRUE;
            $UsersHtml = T('You can also ban the users that posted the spam and delete all of their posts.',
               'Check the box next to the user that posted the spam to also ban them and delete all of their posts. <b>Only do this if you are sure these are spammers.</b>');
            break;
         case 'notspam':
            echo Wrap(T('Marking things as NOT spam will put them back in your forum.'), 'p');
            $AfterHtml = Plural($ItemCount, T('Are you sure this isn\'t spam?'), T('Are you sure these %s items aren\'t spam?'));
            $ShowUsers = TRUE;
            $UsersHtml = T("Check the box next to the user to mark them as <b>Verified</b> so their posts don't get marked as spam again.");
            break;
      }
      
      if ($ShowUsers && sizeof($this->Data('Users'))) {
         echo '<div class="ExtraAction">';
            echo '<div class="ExtraActionTitle">'.$UsersHtml.'</div>';
            if (count($this->Data('Users')) > 1) {
               echo '<div class="CheckBoxCell">';
               echo $this->Form->CheckBox('SelectAll', T('All'));
               echo '</div>';
            }

            foreach ($this->Data('Users') as $User) {
               $RecordUser = Gdn::UserModel()->GetID($User['UserID'], DATASET_TYPE_ARRAY);
               echo '<div class="CheckBoxCell">';
               echo $this->Form->CheckBox('UserID[]', htmlspecialchars($User['Name']), array('value' => $User['UserID']));
               echo ' <span class="Count">'.Plural($RecordUser['CountDiscussions'] + $RecordUser['CountComments'], '%s post', '%s posts').'</span>';            

               echo '</div>';
            }
         echo '</div>';
         echo '<div class="ClearFix"></div>';
      }
      
      echo '<div class="ConfirmText">'.$AfterHtml.'</div>';

      echo '<div class="Buttons">',
         $this->Form->Button('Yes, continue', array('class' => 'Button ConfirmYes')),
//         Anchor(T('Yes'), '#', array('class' => 'Button ConfirmYes', 'style' => 'display: inline-block; width: 50px')),
         ' ',
         Anchor(T("No, get me outta here!"), '#', array('class' => 'ConfirmNo')),
         '</div>';
      
      
      echo $this->Form->Close();
   }
   ?>
</div>