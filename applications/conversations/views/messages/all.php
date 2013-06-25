<?php if (!defined('APPLICATION')) exit();
?>
<h1 class="H"><?php echo $this->Data('Title'); ?></h1>
<?php

// Pager setup
$PagerOptions = array('CurrentRecords' => count($this->Data('Conversations')));
if ($this->Data('_PagerUrl'))
   $PagerOptions['Url'] = $this->Data('_PagerUrl');

// Pre Pager
echo '<div class="PageControls Top">';
   PagerModule::Write($PagerOptions);
   
   echo '<div class="BoxButtons BoxNewConversation">';
   echo Anchor(Sprite('SpMessage').' '.T('New Message'), '/messages/add', 'Button NewConversation Primary');
   echo '</div>';
echo '</div>';
?>
<div class="DataListWrap">
<h2 class="Hidden"><?php echo $this->Data('Title'); ?></h2>
<ul class="Condensed DataList Conversations">
<?php
if (count($this->Data('Conversations') > 0)):
   $ViewLocation = $this->FetchViewLocation('conversations');
   include $ViewLocation;
else:
   ?>
   <li class="Item Empty Center"><?php echo sprintf(T('You do not have any %s yet.'), T('messages')); ?></li>
   <?php
endif;
?>
</ul>
</div>
<?php
// Post Pager
echo '<div class="PageControls Bottom">';
   PagerModule::Write($PagerOptions);
   
//   echo '<div class="BoxButtons BoxNewConversation">';
//   echo Anchor(T('New Message'), '/messages/add', 'Button NewConversation Primary');
//   echo '</div>';
echo '</div>';
