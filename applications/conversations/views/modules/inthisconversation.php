<?php if (!defined('APPLICATION')) exit(); ?>
<div class="Box InThisConversation">
    <?php echo panelHeading(t('In this Conversation')); ?>
    <ul class="PanelInfo">
        <?php foreach ($this->Data->result() as $User): ?>
            <li>
                <?php
                $Username = htmlspecialchars(val('Name', $User));
                $Photo = val('Photo', $User);

                if (val('Deleted', $User)) {
                    echo anchor(
                        wrap(
                            ($Photo ? img($Photo, array('class' => 'ProfilePhoto ProfilePhotoSmall')) : '').' '.
                            wrap($Username, 'del', array('class' => 'Username')),
                            'span', array('class' => 'Conversation-User',)
                        ),
                        userUrl($User),
                        array('title' => sprintf(t('%s has left this conversation.'), $Username))
                    );
                } else {
                    echo anchor(
                        wrap(
                            ($Photo ? img($Photo, array('class' => 'ProfilePhoto ProfilePhotoSmall')) : '').' '.
                            wrap($Username, 'span', array('class' => 'Username')),
                            'span', array('class' => 'Conversation-User')
                        ),
                        userUrl($User)
                    );
                }
                ?>
            </li>
        <?php endforeach; ?>
    </ul>
</div>
