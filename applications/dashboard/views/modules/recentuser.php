<?php if (!defined('APPLICATION')) exit(); ?>
<div class="Box RecentUsers">
    <h4><?php echo t('Recently Active Users'); ?></h4>

    <div class="Icons">
        <?php
        $Data = $this->_Sender->RecentUserData;
        foreach ($Data->result() as $User) {
            $Visitor = UserBuilder($User);
            echo userPhoto($Visitor);
        }
        ?>
    </div>
</div>
