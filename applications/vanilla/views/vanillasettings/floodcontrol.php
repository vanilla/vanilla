<?php if (!defined('APPLICATION')) exit();
$Count = array(1, 2, 3, 4, 5, 10, 15, 20, 25, 30);
$Time = array(30, 60, 90, 120, 240);
$Lock = array(30, 60, 90, 120, 240);
$SpamCount = ArrayCombine($Count, $Count);
$SpamTime = ArrayCombine($Time, $Time);
$SpamLock = ArrayCombine(array(60, 120, 180, 240, 300, 600), array(1, 2, 3, 4, 5, 10));

$ConversationsEnabled = Gdn::ApplicationManager()->IsEnabled('Conversations');

echo $this->Form->open();
echo $this->Form->errors();
?>
<h1><?php echo t('Flood Control'); ?></h1>
<div
    class="Info"><?php echo t('Prevent spam on your forum by limiting the number of discussions &amp; comments that users can post within a given period of time.'); ?></div>
<table class="AltColumns">
    <thead>
    <tr>
        <th><?php echo t('Only Allow Each User To Post'); ?></th>
        <th class="Alt"><?php echo t('Within'); ?></th>
        <th><?php echo t('Or Spamblock For'); ?></th>
    </tr>
    </thead>
    <tbody>
    <tr>
        <td>
            <?php echo $this->Form->DropDown('Vanilla.Discussion.SpamCount', $SpamCount); ?>
            <?php echo t('discussion(s)'); ?>
        </td>
        <td class="Alt">
            <?php echo $this->Form->DropDown('Vanilla.Discussion.SpamTime', $SpamTime); ?>
            <?php echo t('seconds'); ?>
        </td>
        <td>
            <?php echo $this->Form->DropDown('Vanilla.Discussion.SpamLock', $SpamLock); ?>
            <?php echo t('minute(s)'); ?>
        </td>
    </tr>
    <tr>
        <td>
            <?php echo $this->Form->DropDown('Vanilla.Comment.SpamCount', $SpamCount); ?>
            <?php echo t('comment(s)'); ?>
        </td>
        <td class="Alt">
            <?php echo $this->Form->DropDown('Vanilla.Comment.SpamTime', $SpamTime); ?>
            <?php echo t('seconds'); ?>
        </td>
        <td>
            <?php echo $this->Form->DropDown('Vanilla.Comment.SpamLock', $SpamLock); ?>
            <?php echo t('minute(s)'); ?>
        </td>
    </tr>

    <?php if ($ConversationsEnabled): ?>

        <tr>
            <td>
                <?php echo $this->Form->DropDown('Conversations.Conversation.SpamCount', $SpamCount); ?>
                <?php echo t('private conversation(s)'); ?>
            </td>
            <td class="Alt">
                <?php echo $this->Form->DropDown('Conversations.Conversation.SpamTime', $SpamTime); ?>
                <?php echo t('seconds'); ?>
            </td>
            <td>
                <?php echo $this->Form->DropDown('Conversations.Conversation.SpamLock', $SpamLock); ?>
                <?php echo t('minute(s)'); ?>
            </td>
        </tr>
        <tr>
            <td>
                <?php echo $this->Form->DropDown('Conversations.ConversationMessage.SpamCount', $SpamCount); ?>
                <?php echo t('reply to private conversation(s)'); ?>
            </td>
            <td class="Alt">
                <?php echo $this->Form->DropDown('Conversations.ConversationMessage.SpamTime', $SpamTime); ?>
                <?php echo t('seconds'); ?>
            </td>
            <td>
                <?php echo $this->Form->DropDown('Conversations.ConversationMessage.SpamLock', $SpamLock); ?>
                <?php echo t('minute(s)'); ?>
            </td>
        </tr>

    <?php endif; ?>

    </tbody>
</table>

<p><?php echo $this->Form->close('Save'); ?> </p>
