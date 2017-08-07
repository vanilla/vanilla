<?php if (!defined('APPLICATION')) exit();
$Count = [1, 2, 3, 4, 5, 10, 15, 20, 25, 30];
$Time = [30, 60, 90, 120, 240];
$Lock = [30, 60, 90, 120, 240];
$SpamCount = arrayCombine($Count, $Count);
$SpamTime = arrayCombine($Time, $Time);
$SpamLock = arrayCombine([60, 120, 180, 240, 300, 600], [1, 2, 3, 4, 5, 10]);

$ConversationsEnabled = $this->data('IsConversationsEnabled');

$desc = t('Prevent spam on your forum by limiting the number of discussions &amp; comments that users can post within a given period of time.');
helpAsset($this->data('Title'), $desc);

echo $this->Form->open();
echo $this->Form->errors();
echo heading(t('Flood Control'));
?>
<div class="table-wrap">
    <table class="table-data js-tj">
        <thead>
        <tr>
            <th><?php echo t('Only Allow Each User To Post'); ?></th>
            <th class="column-sm"><?php echo t('Within'); ?></th>
            <th><?php echo t('Or Spamblock For'); ?></th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td>
                <?php echo $this->Form->dropDown('Vanilla.Discussion.SpamCount', $SpamCount); ?>
                <?php echo t('discussion(s)'); ?>
            </td>
            <td class="Alt">
                <?php echo $this->Form->dropDown('Vanilla.Discussion.SpamTime', $SpamTime); ?>
                <?php echo t('seconds'); ?>
            </td>
            <td>
                <?php echo $this->Form->dropDown('Vanilla.Discussion.SpamLock', $SpamLock); ?>
                <?php echo t('minute(s)'); ?>
            </td>
        </tr>
        <tr>
            <td>
                <?php echo $this->Form->dropDown('Vanilla.Comment.SpamCount', $SpamCount); ?>
                <?php echo t('comment(s)'); ?>
            </td>
            <td class="Alt">
                <?php echo $this->Form->dropDown('Vanilla.Comment.SpamTime', $SpamTime); ?>
                <?php echo t('seconds'); ?>
            </td>
            <td>
                <?php echo $this->Form->dropDown('Vanilla.Comment.SpamLock', $SpamLock); ?>
                <?php echo t('minute(s)'); ?>
            </td>
        </tr>
        <tr>
            <td>
                <?php echo $this->Form->dropDown('Vanilla.Activity.SpamCount', $SpamCount); ?>
                <?php echo t('activity(ies)'); ?>
            </td>
            <td class="Alt">
                <?php echo $this->Form->dropDown('Vanilla.Activity.SpamTime', $SpamTime); ?>
                <?php echo t('seconds'); ?>
            </td>
            <td>
                <?php echo $this->Form->dropDown('Vanilla.Activity.SpamLock', $SpamLock); ?>
                <?php echo t('minute(s)'); ?>
            </td>
        </tr>
        <tr>
            <td>
                <?php echo $this->Form->dropDown('Vanilla.ActivityComment.SpamCount', $SpamCount); ?>
                <?php echo t('activity\'s comment(s)'); ?>
            </td>
            <td class="Alt">
                <?php echo $this->Form->dropDown('Vanilla.ActivityComment.SpamTime', $SpamTime); ?>
                <?php echo t('seconds'); ?>
            </td>
            <td>
                <?php echo $this->Form->dropDown('Vanilla.ActivityComment.SpamLock', $SpamLock); ?>
                <?php echo t('minute(s)'); ?>
            </td>
        </tr>

        <?php if ($ConversationsEnabled): ?>

            <tr>
                <td>
                    <?php echo $this->Form->dropDown('Conversations.Conversation.SpamCount', $SpamCount); ?>
                    <?php echo t('private conversation(s)'); ?>
                </td>
                <td class="Alt">
                    <?php echo $this->Form->dropDown('Conversations.Conversation.SpamTime', $SpamTime); ?>
                    <?php echo t('seconds'); ?>
                </td>
                <td>
                    <?php echo $this->Form->dropDown('Conversations.Conversation.SpamLock', $SpamLock); ?>
                    <?php echo t('minute(s)'); ?>
                </td>
            </tr>
            <tr>
                <td>
                    <?php echo $this->Form->dropDown('Conversations.ConversationMessage.SpamCount', $SpamCount); ?>
                    <?php echo t('reply to private conversation(s)'); ?>
                </td>
                <td class="Alt">
                    <?php echo $this->Form->dropDown('Conversations.ConversationMessage.SpamTime', $SpamTime); ?>
                    <?php echo t('seconds'); ?>
                </td>
                <td>
                    <?php echo $this->Form->dropDown('Conversations.ConversationMessage.SpamLock', $SpamLock); ?>
                    <?php echo t('minute(s)'); ?>
                </td>
            </tr>

        <?php endif; ?>

        </tbody>
    </table>
</div>
<?php echo $this->Form->close('Save'); ?>
