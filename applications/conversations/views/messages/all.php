<?php if (!defined('APPLICATION')) exit();
?>
<span class="page-title">
   <h1 class="H"><?php echo $this->Data('Title'); ?></h1>
</span>
<?php

// Pager setup
$PagerOptions = array('CurrentRecords' => count($this->Data('Conversations')));
if ($this->Data('_PagerUrl'))
   $PagerOptions['Url'] = $this->Data('_PagerUrl');

// Pre Pager
echo '<div class="PageControls Top self-clearing">';
   PagerModule::Write($PagerOptions);
   if (CheckPermission('Conversations.Conversations.Add')) {
      echo '<div class="BoxButtons BoxNewConversation">';
      echo Anchor(T('New Message'), '/messages/add', 'button Button NewConversation Primary');
      echo '</div>';
   }
echo '</div>';
?>
<ul class="Condensed DataList Conversations">
<?php
if (count($this->Data('Conversations') > 0)):
   $ViewLocation = $this->FetchViewLocation('conversations');
   include $ViewLocation;
else:
   ?>
   <li class="Item Empty Center self-clearing"><?php echo sprintf(T('You do not have any %s yet.'), T('messages')); ?></li>
   <?php
endif;
?>
</ul>
<?php
// Post Pager
echo '<div class="PageControls Bottom self-clearing">';
   PagerModule::Write($PagerOptions);
   
//   echo '<div class="BoxButtons BoxNewConversation">';
//   echo Anchor(T('New Message'), '/messages/add', 'Button NewConversation Primary');
//   echo '</div>';
echo '</div>';
