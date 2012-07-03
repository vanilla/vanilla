<?php if (!defined('APPLICATION')) exit();
if (count($this->Data('Conversations'))):
?>
   <ul class="PopList Conversations">
      <?php 
      foreach ($this->Data('Conversations') as $Row):
         
      $Subject = '';
      if ($Row['Subject']) {
         $Subject = Gdn_Format::Text($Row['Subject']);
      } else {
         $Subject = '';
         foreach ($Row['Participants'] as $User) {
            if (!isset($PhotoUser))
               $PhotoUser = $User;
            $Subject = ConcatSep(', ', $Subject, FormatUsername($User, 'You'));
         }
      }
      
      if (!isset($PhotoUser))
         $PhotoUser = UserBuilder($Row, 'LastMessage');
      ?>
      <li class="Item">
         <div class="Author Photo"><?php echo UserPhoto($PhotoUser); ?></div>
         <div class="ItemContent">
            <b class="Subject"><?php echo Anchor($Subject, "/messages/{$Row['ConversationID']}#latest"); ?></b>
            <?php
            $Excerpt = SliceString(Gdn_Format::PlainText($Row['LastMessage']), 80);
            echo Wrap(nl2br($Excerpt), 'div', array('class' => 'Excerpt'));
            ?>
            <div class="Meta">
               <?php 
               echo ' <span class="MItem">'.Plural($Row['CountMessages'], '%s message', '%s messages').'</span> ';

               if ($Row['CountNewMessages'] > 0) {
                  echo ' <strong class="HasNew"> '.Plural($Row['CountNewMessages'], '%s new', '%s new').'</strong> ';
               }

               echo ' <span class="MItem">'.Gdn_Format::Date($Row['DateLastMessage']).'</span> ';
               ?>
            </div>
         </div>
      </li>
      <?php endforeach; ?>
      <li class="Item Center">
         <?php
         echo Anchor(sprintf(T('All %s'), T('Messages')), '/messages/inbox'); 
         ?>
      </li>
   </ul>
<?php else: ?>
<div class="Empty"><?php echo sprintf(T('You do not have any %s yet.'), T('messages')); ?></div>
<?php endif; ?>