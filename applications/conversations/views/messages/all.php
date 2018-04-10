<?php if (!defined('APPLICATION')) exit();
?>
    <h1 class="H"><?php echo $this->data('Title'); ?></h1>
<?php

// Pager setup
$PagerOptions = ['CurrentRecords' => count($this->data('Conversations'))];
if ($this->data('_PagerUrl'))
    $PagerOptions['Url'] = $this->data('_PagerUrl');

// Pre Pager
echo '<div class="PageControls Top">';
PagerModule::write($PagerOptions);
if (checkPermission('Conversations.Conversations.Add')) {
    echo '<div class="BoxButtons BoxNewConversation">';
    echo anchor(sprite('SpMessage').' '.t('New Message'), '/messages/add', 'Button NewConversation Primary');
    echo '</div>';
}
echo '</div>';
?>
    <div class="DataListWrap">
        <ul class="Condensed DataList Conversations">
            <?php
            if (count($this->data('Conversations') > 0)):
                $ViewLocation = $this->fetchViewLocation('conversations');
                include $ViewLocation;
            else:
                ?>
                <li class="Item Empty Center"><?php echo t('Your inbox is empty.', sprintf(t('You do not have any %s yet.'), t('messages'))); ?></li>
            <?php
            endif;
            ?>
        </ul>
    </div>
<?php
// Post Pager
echo '<div class="PageControls Bottom">';
PagerModule::write($PagerOptions);

//   echo '<div class="BoxButtons BoxNewConversation">';
//   echo anchor(t('New Message'), '/messages/add', 'Button NewConversation Primary');
//   echo '</div>';
echo '</div>';
