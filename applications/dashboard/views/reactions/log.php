<?php if (!defined('APPLICATION')) exit(); ?>

<h1><?php echo t('Reactions'); ?></h1>
<table class="DataTable DataTable-ReactionsLog">
    <tbody>
        <?php foreach ($this->data('UserTags') as $Row) : ?>
        <tr class="Item">
            <td class="ReactionsLog-Date"><?php echo Gdn_Format::date($Row['DateInserted']); ?></td>
            <td class="ReactionsLog-User"><?php echo userAnchor($Row); ?></td>
            <td class="ReactionsLog-Reaction">
            <?php
                $ReactionType = ReactionModel::fromTagID($Row['TagID']);
                echo htmlspecialchars($ReactionType['Name']);
            ?>
            </td>
            <td class="ReactionsLog-Options">
                <div class="Options">
                <?php
                    echo anchor('&times;',
                        "/reactions/undo/{$this->Data['RecordType']}/{$this->Data['RecordID']}/{$ReactionType['UrlCode']}?userid={$Row['UserID']}&tagid={$Row['TagID']}",
                        'TextColor Hijack',
                        ['title' => sprintf(t('Remove %s'), t('reaction'))]
                    );
                ?>
                <div>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
