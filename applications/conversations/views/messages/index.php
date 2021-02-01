<?php
    if (!defined('APPLICATION')) exit();
    use Vanilla\Theme\BoxThemeShim;
?>
    <?php BoxThemeShim::startHeading(); ?>
        <h1 class="H">
            <?php
                echo $this->Participants;

                if ($this->data('Conversation.Subject')) {
                    echo
                        bullet(' ').
                        '<span class="Gloss">'.htmlspecialchars($this->data('Conversation.Subject')).'</span>';
                }
            ?>
        </h1>
    <?php BoxThemeShim::endHeading(); ?>

    <?php
    BoxThemeShim::startBox();

    if ($this->data('Conversation.Type')) {
        $this->fireEvent('Conversation'.str_replace('_', '', $this->data('Conversation.Type')));
    }

    if ($this->data('_HasDeletedUsers')) {
        echo '<div class="Info">', t('One or more users have left this conversation.', 'One or more users have left this conversation. They won\'t receive any more messages unless you add them back in to the conversation.'), '</div>';
    }
    $this->fireEvent('BeforeConversation');
    echo $this->Pager->toString('less');
    ?>
    <ul class="DataList MessageList Conversation pageBox">
        <?php
        $MessagesViewLocation = $this->fetchViewLocation('messages');
        include($MessagesViewLocation);
        ?>
    </ul>
<?php
echo $this->Pager->toString();
echo Gdn::controller()->fetchView('addmessage');
BoxThemeShim::endBox();
