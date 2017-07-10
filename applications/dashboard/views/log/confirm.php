<?php if (!defined('APPLICATION')) exit(); ?>
<div class="modal-dialog modal-sm modal-confirm" role="document">
    <div class="modal-content">
        <?php
        $ItemCount = $this->data('ItemCount');
        $title = '';
        if ($this->data('ItemCount')) {
            $title = t('Please Confirm');
        }
        ?>
        <div class="modal-header">
            <button type="button" class="btn-icon modal-close close Close" aria-label="Close">
                <?php echo dashboardSymbol('close'); ?>
            </button>
            <h4 class="modal-title"><?php echo $title ?></h4>
        </div>
        <?php
        if (!$ItemCount) { ?>
            <?php echo '<div class="modal-body">', t('Make sure you select at least one item before continuing.'), '</div>';
        } else { ?>
            <?php
            echo $this->Form->open(['id' => 'ConfirmForm', 'Action' => $this->data('ActionUrl')]);
            echo $this->Form->errors(); ?>
            <div class="modal-body">
                <?php
                // Give a description of what is done.'
                $ShowUsers = FALSE;
                switch (strtolower($this->data('Action'))) {
                    case 'delete':
                        echo '<div class="alert alert-danger">'.t('Warning: deleting is permanent', 'WARNING: deleted items are removed from this list and cannot be brought back.').'</div>';
                        echo wrap(t('Deleting forever cannot be undone.', 'Deleting is a good way to keep your forum clean.'), 'p');
                        $AfterHtml = plural($ItemCount, t('Are you sure you want to delete 1 item forever?'), t('Are you sure you want to delete %s items forever?'));
                        break;
                    case 'restore':
                        echo wrap(t('Restoring your selection removes the items from this list.', 'When you restore, the items are removed from this list and put back into the site.'), 'p');
                        $AfterHtml = plural($ItemCount, t('Are you sure you want to restore 1 item?'), t('Are you sure you want to restore %s items?'));
                        break;
                    case 'deletespam':
                        echo '<div class="alert alert-danger">'.t('Warning: deleting is permanent', 'WARNING: deleted items are removed from this list and cannot be brought back.').'</div>';
                        echo wrap(t('Marking as spam cannot be undone.', 'Marking something as SPAM will cause it to be deleted forever. Deleting is a good way to keep your forum clean.'), 'p');
                        $AfterHtml = t('Are you ABSOLUTELY sure you want to take this action?');
                        $ShowUsers = TRUE;
                        $UsersHtml = t('You can also ban the users that posted the spam and delete all of their posts.',
                            'Check the box next to the user that posted the spam to also ban them and delete all of their posts. <b>Only do this if you are sure these are spammers.</b>');
                        break;
                    case 'notspam':
                        echo wrap(t('Marking things as NOT spam will put them back in your forum.'), 'p');
                        $AfterHtml = plural($ItemCount, t('Are you sure this isn\'t spam?'), t('Are you sure these %s items aren\'t spam?'));
                        $ShowUsers = TRUE;
                        $UsersHtml = t("Check the box next to the user to mark them as <b>Verified</b> so their posts don't get marked as spam again.");
                        break;
                }
                if ($ShowUsers && sizeof($this->data('Users'))) {
                    echo '<div class="ExtraAction">';
                    echo '<div class="ExtraActionTitle">'.$UsersHtml.'</div>'; ?>
                    <div class="padded">
                    <?php
                    if (count($this->data('Users')) > 1) {
                        echo '<div class="CheckBoxCell">';
                        echo wrap(
                            $this->Form->checkBox(
                                'SelectAll',
                                t('All'),
                                ['display' => 'after', 'class' => 'js-check-all checkbox checkbox-inline']
                            ).' '.t('All'),
                            'span',
                            ['class' => 'checkbox-painted-wrapper']
                        );

                        echo '</div>';
                    }

                    foreach ($this->data('Users') as $User) {
                        $RecordUser = Gdn::userModel()->getID($User['UserID'], DATASET_TYPE_ARRAY);
                        echo '<div class="CheckBoxCell">';
                        echo wrap(
                            $this->Form->checkBox(
                                'UserID[]',
                                htmlspecialchars($User['Name']),
                                ['value' => $User['UserID'], 'display' => 'after', 'class' => 'js-check-me checkbox checkbox-inline']
                            ).' '.htmlspecialchars($User['Name']),
                            'span',
                            ['class' => 'checkbox-painted-wrapper']
                        );
                        echo ' <span class="Count">'.plural($RecordUser['CountDiscussions'] + $RecordUser['CountComments'], '%s post', '%s posts').'</span>';

                        echo '</div>';
                    } ?>
                    </div>
                    <?php echo '</div>';
                }

                echo '<div class="ConfirmText">'.$AfterHtml.'</div>'; ?>
            </div>
            <div class="modal-footer">
                <?php
                echo anchor(t("No, get me outta here!"), '#', ['class' => 'btn btn-text ConfirmNo']);
                echo $this->Form->button('Yes, continue', ['class' => 'btn btn-primary ConfirmYes']);
                ?>
            </div>

            <?php echo $this->Form->close();
        }
        ?>
    </div>
</div>
