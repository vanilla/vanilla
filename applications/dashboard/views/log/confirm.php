<?php if (!defined('APPLICATION')) exit(); ?>
<div class="Wrap">
   <?php
   $ItemCount = $this->Data('ItemCount');

   if (!$ItemCount) {
      echo '<h1>', T('No Items Selected'), '</h1>';
      echo '<p>', T('Make sure you select at least one item before continuing.'), '</p>';
   } else {
      echo '<h1>', T('Please Confirm'), '</h1>';

      // Give a description of what is done.'
      switch (strtolower($this->Data('Action'))) {
         case 'delete':
            echo '<p>',
               T('Deleting forever cannot be undone.', 'Deleting is a good way to keep your forum clean. However, when you delete forever then those operations are removed from this list and cannot be undone.'),
               '</p>';

            echo '<p>',
               Plural($ItemCount, T('Are you sure you want to delete 1 item forever?'), T('Are you sure you want to delete %s items forever?')),
               '</p>';
            break;
         case 'restore':
            echo '<p>',
               T('Restoring your selection removes the items from this list.', 'When you restore your selectection the items are removed from this list and put back into the site.'),
               '</p>';

            echo '<p>',
               Plural($ItemCount, T('Are you sure you want to restore 1 item?'), T('Are you sure you want to restore %s items?')),
               '</p>';
            break;
         case 'notspam':
            echo '<p>',
               T('Marking things as not spam will put them back in your forum.'),
               '</p>';

            echo '<p>',
               Plural($ItemCount, T('Are you sure this isn\'t spam?'), T('Are you sure these %s items aren\'t spam?')),
               '</p>';
            break;
            break;
      }

      echo '<div class="Center">',
         Anchor(T('Yes'), '#', array('class' => 'Button ConfirmYes', 'style' => 'display: inline-block; width: 50px')),
         ' ',
         Anchor(T('No'), '#', array('class' => 'Button ConfirmNo', 'style' => 'display: inline-block; width: 50px')),
         '</div>';
   }
   ?>
</div>