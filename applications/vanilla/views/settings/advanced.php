<?php if (!defined('APPLICATION')) exit();
if (!function_exists('DiscussionSortText')) {
    function discussionSortText($Field) {
        $Text = FALSE;
        switch ($Field) {
            case 'd.DiscussionID' :
                $Text = t('SortDiscussionsByID', 'ID number (like support tickets)');
                break;
            case 'd.DateLastComment' :
                $Text = t('SortDiscussionsByLastComment', 'Most recent comment (like a forum)');
                break;
            case 'd.DateInserted' :
                $Text = t('SortDiscussionsByDateInserted', 'Start time (like a blog)');
                break;
        }
        return ($Text) ? $Text : $Field;
    }
}
?>
    <div class="Help Aside">
        <?php
        echo wrap(t('Need More Help?'), 'h2');
        echo '<ul>';
        echo wrap(Anchor(t("Video tutorial on advanced settings"), 'settings/tutorials/category-management-and-advanced-settings'), 'li');
        echo '</ul>';
        ?>
    </div>
    <h1><?php echo t('Advanced'); ?></h1>
<?php
echo $this->Form->open();
echo $this->Form->errors();
?>
    <ul>
        <li>
            <?php
            $Options = array('10' => '10', '15' => '15', '20' => '20', '25' => '25', '30' => '30', '40' => '40', '50' => '50', '100' => '100');
            $Fields = array('TextField' => 'Code', 'ValueField' => 'Code');
            echo $this->Form->label('Discussions per Page', 'Vanilla.Discussions.PerPage');
            echo $this->Form->DropDown('Vanilla.Discussions.PerPage', $Options, $Fields);
            ?>
        </li>
        <li>
            <?php
            echo $this->Form->label('Comments per Page', 'Vanilla.Comments.PerPage');
            echo $this->Form->DropDown('Vanilla.Comments.PerPage', $Options, $Fields);
            ?>
        </li>
        <li>
            <?php
            $AllowedSortFields = DiscussionModel::AllowedSortFields();
            $SortFields = array();
            foreach ($AllowedSortFields as $Field) {
                $SortFields[$Field] = DiscussionSortText($Field);
            }
            echo $this->Form->label('Sort discussions by', 'Vanilla.Discussions.SortField');
            echo $this->Form->DropDown('Vanilla.Discussions.SortField', $SortFields, $Fields);
            ?>
        </li>
        <li>
            <?php
            $Options = array('0' => t('Authors may never edit'),
                '350' => sprintf(t('Authors may edit for %s'), t('5 minutes')),
                '900' => sprintf(t('Authors may edit for %s'), t('15 minutes')),
                '3600' => sprintf(t('Authors may edit for %s'), t('1 hour')),
                '14400' => sprintf(t('Authors may edit for %s'), t('4 hours')),
                '86400' => sprintf(t('Authors may edit for %s'), t('1 day')),
                '604800' => sprintf(t('Authors may edit for %s'), t('1 week')),
                '2592000' => sprintf(t('Authors may edit for %s'), t('1 month')),
                '-1' => t('Authors may always edit'));
            $Fields = array('TextField' => 'Text', 'ValueField' => 'Code');
            echo $this->Form->label('Discussion & Comment Editing', 'Garden.EditContentTimeout');
            echo $this->Form->DropDown('Garden.EditContentTimeout', $Options, $Fields);
            echo wrap(t('EditContentTimeout.Notes', 'If a user is in a role that has permission to edit content, those permissions will override this.'), 'div', array('class' => 'Info'));
            ?>
        </li>
        <!--   <li>
      <?php
        $Options2 = array('0' => t('Never - Users Must Refresh Page'),
            '5' => t('Every 5 seconds'),
            '10' => t('Every 10 seconds'),
            '30' => t('Every 30 seconds'),
            '60' => t('Every 1 minute'),
            '300' => t('Every 5 minutes'));
        echo $this->Form->label('Auto-Fetch New Comments', 'Vanilla.Comments.AutoRefresh');
        echo $this->Form->DropDown('Vanilla.Comments.AutoRefresh', $Options2, $Fields);
        ?>
   </li>-->
        <li>
            <?php
            echo $this->Form->label('Archive Discussions', 'Vanilla.Archive.Date');
            echo '<div class="Info">',
            t('Vanilla.Archive.Description', 'You can choose to archive forum discussions older than a certain date. Archived discussions are effectively closed, allowing no new posts.'),
            '</div>';
            echo $this->Form->Calendar('Vanilla.Archive.Date');
            echo ' '.t('(YYYY-mm-dd)');
            ?>
        </li>
        <li>
            <?php
            echo $this->Form->CheckBox('Vanilla.Archive.Exclude', 'Exclude archived discussions from the discussions list');
            ?>
        </li>
        <li>
            <?php
            echo $this->Form->CheckBox('Vanilla.AdminCheckboxes.Use', 'Enable admin checkboxes on discussions and comments.');
            ?>
        </li>
    </ul>
    <ul>
        <li>
            <div
                class="Info"><?php echo t("It is a good idea to keep the maximum number of characters allowed in a comment down to a reasonable size."); ?></div>
            <?php
            echo $this->Form->label('Max Comment Length', 'Vanilla.Comment.MaxLength');
            echo $this->Form->textBox('Vanilla.Comment.MaxLength', array('class' => 'InputBox SmallInput'));
            ?>
        </li>
        <li>
            <div
                class="Info"><?php echo t("You can specify a minimum comment length to discourage short comments."); ?></div>
            <?php
            echo $this->Form->label('Min Comment Length', 'Vanilla.Comment.MinLength');
            echo $this->Form->textBox('Vanilla.Comment.MinLength', array('class' => 'InputBox SmallInput'));
            ?>
        </li>
    </ul>
<?php echo $this->Form->close('Save');
