<?php if (!defined('APPLICATION')) exit(); 
$SubjectsVisible = C('Conversations.Subjects.Visible');
?>
<div id="InboxModule" class="Box BoxInbox">
   <h4><?php echo T('Inbox'); ?></h4>
   <?php if (count($this->Data('Conversations')) > 0): ?>
   
   <ul id="" class="DataList Conversations PanelInfo">
      <?php foreach ($this->Data('Conversations') as $Row): ?>
      <li id="Conversation_<?php echo $Row['ConversationID']; ?>" class="Item">
         <?php
         $JumpToItem = $Row['CountMessages'] - $Row['CountNewMessages'];
         $Url = "/messages/{$Row['ConversationID']}/#Item_$JumpToItem";
         
         if ($SubjectsVisible && $Row['Subject'])
            $Message = htmlspecialchars($Row['Title']);
         elseif ($Row['Format'] == 'Text')
            $Message = (SliceString(Gdn_Format::To($Row['LastMessage'], $Conversation['Format']), 100));
         else
            $Message = (SliceString(Gdn_Format::Text(Gdn_Format::To($Row['LastMessage'], $Row['Format']), FALSE), 100));

         if (StringIsNullOrEmpty(trim($Message)))
            $Message = T('Blank Message');
         
         echo Anchor($Message, $Url, 'ConversationLink');
         ?>
         <div class="Meta">
            <span class="MItem Participants">
               <?php
               $First = TRUE;
               foreach ($Row['Participants'] as $User) {
                  if ($First)
                     $First = FALSE;
                  else
                     echo ', ';
                  
                  if ($User['UserID'] == Gdn::Session()->UserID)
                     $User['Name'] = T('You');
                  
                  echo UserAnchor($User);
               }
               ?>
            </span>
            <span class="MItem CountMessages">
               <?php
               echo Plural($Row['CountMessages'], '%s message', '%s messages');
               ?>
            </span>
            <span class="MItem DateLastMessage">
               <?php
               echo Gdn_Format::Date($Row['DateLastMessage']);
               ?>
            </span>
         </div>
      </li>
      <?php endforeach; ?>
   </ul>   
   <div class="P PagerContainer">
      <?php
      if (CheckPermission('Conversations.Conversations.Add'))
         echo Anchor(Sprite('SpNewConversation SpAdd').T('New Message'), '/messages/add');
      ?>
      <span class="Pager"><?php echo Anchor(T('More…'), '/messages/inbox'); ?></span>
   </div>
   <?php else: ?>
      <?php
      echo Wrap(T('Your private conversations with other members.'), 'div', array('class' => 'P'));
      ?>
      <div class="P PagerContainer">
         <?php
         if (CheckPermission('Conversations.Conversations.Add'))
            echo Anchor(Sprite('SpNewConversation SpAdd').T('New Message'), '/messages/add');
         ?>
      </div>
   <?php endif; ?>
</div>
