<?php if (!defined('APPLICATION')) exit(); ?>
<div class="Box InThisConversation">
    <?php echo panelHeading(t('In this Conversation')); ?>
    <ul class="PanelInfo">
        <?php foreach ($this->data('Participants') as $User): ?>
            <li>
                <?php
                $Username = htmlspecialchars(val('Name', $User));
                $Photo = val('Photo', $User);

                if (val('Deleted', $User)) {
                    echo anchor(
                        wrap(
                            ($Photo ? img($Photo, ['class' => 'ProfilePhoto ProfilePhotoSmall']) : '').' '.
                            wrap($Username, 'del', ['class' => 'Username']),
                            'span', ['class' => 'Conversation-User',]
                        ),
                        userUrl($User),
                        ['title' => sprintf(t('%s has left this conversation.'), $Username)]
                    );
                } else {
                    echo anchor(
                        wrap(
                            ($Photo ? img($Photo, ['class' => 'ProfilePhoto ProfilePhotoSmall']) : '').' '.
                            wrap($Username, 'span', ['class' => 'Username']),
                            'span', ['class' => 'Conversation-User']
                        ),
                        userUrl($User)
                    );
                }
                ?>
            </li>
        <?php endforeach; ?>
    </ul>
</div>
