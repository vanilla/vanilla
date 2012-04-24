<?php if (!defined('APPLICATION')) exit(); ?>
<style>
   .UserCheckboxes {
      overflow: hidden;
      margin: .7em 0;
   }
   .CheckBoxCell {
      float:left;
      width: 50%;
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
            echo '<p>',
               T('Deleting forever cannot be undone.', 'Deleting is a good way to keep your forum clean. However, when you delete forever then those operations are removed from this list and cannot be undone.'),
               '</p>';

            $AfterHtml = '<p>'.
               Plural($ItemCount, T('Are you sure you want to delete 1 item forever?'), T('Are you sure you want to delete %s items forever?')).
               '</p>';
            break;
         case 'restore':
            echo '<p>',
               T('Restoring your selection removes the items from this list.', 'When you restore your selection the items are removed from this list and put back into the site.'),
               '</p>';

            $AfterHtml = '<p>'.
               Plural($ItemCount, T('Are you sure you want to restore 1 item?'), T('Are you sure you want to restore %s items?')).
               '</p>';
            break;
         case 'deletespam':
            echo '<p>',
               T('Deleting forever cannot be undone.', 'Deleting is a good way to keep your forum clean. However, when you delete forever then those operations are removed from this list and cannot be undone.'),
               '</p>';

            $AfterHtml = '<p>'.
               Plural($ItemCount, T('Are you sure you want to delete 1 item forever?'), T('Are you sure you want to delete %s items forever?')).
               '</p>';
            
            $ShowUsers = TRUE;
            $UsersHtml = '<div class="Warning">'.
               T('You can also ban the users that posted the spam and delete all of their posts.',
               'You can also ban the users that posted the spam and delete all of their posts. <b>Only do this if you are sure these are spammers.</b>').
               '</div>';
            
            break;
         case 'notspam':
            echo '<p>',
               T('Marking things as not spam will put them back in your forum.'),
               '</p>';

            $AfterHtml = '<p>'.
               Plural($ItemCount, T('Are you sure this isn\'t spam?'), T('Are you sure these %s items aren\'t spam?')).
               '</p>';
            
            $ShowUsers = TRUE;
            $UsersHtml = '<p>'.
               T("You can also verify these users so their posts don't get marked as spam again.").
               '</p>';
            
            break;
      }
      
      if ($ShowUsers) {
         echo '<div class="UserCheckboxes">';
         echo $UsersHtml;
         
         if (count($this->Data('Users')) > 1) {
            echo '<div class="CheckBoxCell">';
            echo $this->Form->CheckBox('SelectAll', T('All'));
            echo '</div>';
         }
         
         foreach ($this->Data('Users') as $User) {
            echo '<div class="CheckBoxCell">';
            
            echo $this->Form->CheckBox('UserID[]', htmlspecialchars($User['Name']), array('value' => $User['UserID']));
            
            echo '</div>';
         }
         echo '</div>';
      }
      
      echo $AfterHtml;

      echo '<div class="Center">',
         $this->Form->Button('Yes', array('class' => 'Button ConfirmYes', 'style' => 'display: inline-block; width: 50px')),
//         Anchor(T('Yes'), '#', array('class' => 'Button ConfirmYes', 'style' => 'display: inline-block; width: 50px')),
         ' ',
         Anchor(T('No'), '#', array('class' => 'Button ConfirmNo', 'style' => 'display: inline-block; width: 50px')),
         '</div>';
      
      
      echo $this->Form->Close();
   }
   ?>
</div>