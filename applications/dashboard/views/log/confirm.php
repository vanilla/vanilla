<?php if (!defined('APPLICATION')) exit(); ?>
<style>
    .ExtraActionTitle {
        background: #FF9;
        margin: -10px -10px 10px;
        padding: 10px;
    }

    .ExtraAction {
        margin: 10px -10px;
        padding: 10px;
        background: #ffe;
        float: left;
        width: 100%;
    }

    .CheckBoxCell {
        float: left;
        width: 50%;
    }

    .ClearFix {
        clear: both;
    }

    .Buttons {
        margin: 10px 0 0;
    }

    .ConfirmNo {
        margin-left: 14px;
        color: #d00;
        font-weight: bold;
    }

    .ConfirmNo:hover {
        text-decoration: underline;
    }

    .WarningMessage {
        padding: 6px 10px;
        margin: 10px 0 4px;
    }
</style>
<div>
    <?php

    $ItemCount = $this->data('ItemCount');

    if (!$ItemCount) {
        echo '<h1>', t('No Items Selected'), '</h1>';
        echo '<p class="Wrap">', t('Make sure you select at least one item before continuing.'), '</p>';
    } else {
        echo '<h1>', t('Please Confirm'), '</h1>';
        echo $this->Form->open(array('id' => 'ConfirmForm', 'Action' => $this->data('ActionUrl')));
        echo $this->Form->errors();

        // Give a description of what is done.'
        $ShowUsers = FALSE;
        switch (strtolower($this->data('Action'))) {
            case 'delete':
                echo wrap(t('Deleting forever cannot be undone.', 'Deleting is a good way to keep your forum clean.'), 'p');
                echo '<div class="WarningMessage">'.t('Warning: deleting is permanent', 'WARNING: deleted items are removed from this list and cannot be brought back.').'</div>';
                $AfterHtml = plural($ItemCount, t('Are you sure you want to delete 1 item forever?'), t('Are you sure you want to delete %s items forever?'));
                break;
            case 'restore':
                echo wrap(t('Restoring your selection removes the items from this list.', 'When you restore, the items are removed from this list and put back into the site.'), 'p');
                $AfterHtml = plural($ItemCount, t('Are you sure you want to restore 1 item?'), t('Are you sure you want to restore %s items?'));
                break;
            case 'deletespam':
                echo wrap(t('Marking as spam cannot be undone.', 'Marking something as SPAM will cause it to be deleted forever. Deleting is a good way to keep your forum clean.'), 'p');
                echo '<div class="WarningMessage">'.t('Warning: deleting is permanent', 'WARNING: deleted items are removed from this list and cannot be brought back.').'</div>';
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
            echo '<div class="ExtraActionTitle">'.$UsersHtml.'</div>';
            if (count($this->data('Users')) > 1) {
                echo '<div class="CheckBoxCell">';
                echo $this->Form->CheckBox('SelectAll', t('All'));
                echo '</div>';
            }

            foreach ($this->data('Users') as $User) {
                $RecordUser = Gdn::userModel()->getID($User['UserID'], DATASET_TYPE_ARRAY);
                echo '<div class="CheckBoxCell">';
                echo $this->Form->CheckBox('UserID[]', htmlspecialchars($User['Name']), array('value' => $User['UserID']));
                echo ' <span class="Count">'.plural($RecordUser['CountDiscussions'] + $RecordUser['CountComments'], '%s post', '%s posts').'</span>';

                echo '</div>';
            }
            echo '</div>';
            echo '<div class="ClearFix"></div>';
        }

        echo '<div class="ConfirmText">'.$AfterHtml.'</div>';

        echo '<div class="Buttons">',
        $this->Form->button('Yes, continue', array('class' => 'Button ConfirmYes')),
//         anchor(t('Yes'), '#', array('class' => 'Button ConfirmYes', 'style' => 'display: inline-block; width: 50px')),
        ' ',
        anchor(t("No, get me outta here!"), '#', array('class' => 'ConfirmNo')),
        '</div>';


        echo $this->Form->close();
    }
    ?>
</div>
