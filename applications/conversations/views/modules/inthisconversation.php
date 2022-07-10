<?php if (!defined('APPLICATION')) exit();
/** @var InThisConversationModule $this */
use Vanilla\Theme\BoxThemeShim;
?>
<div class="Box InThisConversation">

    <?php
        BoxThemeShim::startHeading();
        echo panelHeading(t('In this Conversation'));
        BoxThemeShim::endHeading();
    ?>
    <ul class="PanelInfo  <?php BoxThemeShim::activeHtml(' pageBox')?>">
        <?php foreach ($this->data('Participants') as $User): ?>
            <li>
                <?php
                $Username = htmlspecialchars(val('Name', $User));
                $Photo = val('Photo', $User);
                $userID = $user->UserID ?? $user['UserID'];


                if (val('Deleted', $User)) {
                    echo anchor(
                        wrap(
                            ($Photo ? img($Photo, ['class' => 'ProfilePhoto ProfilePhotoSmall']) : '').' '.
                            wrap($Username, 'del', ['class' => 'Username']),
                            'span', ['class' => 'Conversation-User',]
                        ),
                        userUrl($User),
                        [
                            'title' => sprintf(t('%s has left this conversation.'), $Username),
                            "data-userid"=> $userID
                        ]
                    );
                } else {
                    echo anchor(
                        wrap(
                            ($Photo ? img($Photo, ['class' => 'ProfilePhoto ProfilePhotoSmall']) : '').' '.
                            wrap($Username, 'span', ['class' => 'Username']),
                            'span', ['class' => 'Conversation-User']
                        ),
                        userUrl($User),
                        [
                            "data-userid"=> $userID
                        ]
                    );
                }
                ?>
            </li>
        <?php endforeach; ?>
    </ul>
</div>
