<?php if (!defined('APPLICATION')) exit(); ?>
<div class="Box InThisConversation">
    <?php echo panelHeading(T('In this Conversation')); ?>
    <ul class="PanelInfo">
        <?php foreach ($this->Data->Result() as $User): ?>
            <li>
                <?php
                $Username = htmlspecialchars(GetValue('Name', $User));
                $Photo = GetValue('Photo', $User);

                if (GetValue('Deleted', $User)) {
                    echo Anchor(
                        Wrap(
                            ($Photo ? Img($Photo, array('class' => 'ProfilePhoto ProfilePhotoSmall')) : '').' '.
                            Wrap($Username, 'del', array('class' => 'Username')),
                            'span', array('class' => 'Conversation-User',)
                        ),
                        UserUrl($User),
                        array('title' => sprintf(T('%s has left this conversation.'), $Username))
                    );
                } else {
                    echo Anchor(
                        Wrap(
                            ($Photo ? Img($Photo, array('class' => 'ProfilePhoto ProfilePhotoSmall')) : '').' '.
                            Wrap($Username, 'span', array('class' => 'Username')),
                            'span', array('class' => 'Conversation-User')
                        ),
                        UserUrl($User)
                    );
                }
                ?>
            </li>
        <?php endforeach; ?>
    </ul>
</div>
