<?php if (!defined('APPLICATION')) exit(); ?>
<div class="Summary PopularDiscussionSummary">
    <table>
        <thead>
        <tr>
            <th><?php echo t('Popular Discussions'); ?></th>
            <td><?php echo t('Comments'); ?></td>
            <td><?php echo t('Follows'); ?></td>
            <td><?php echo t('Views'); ?></td>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($this->Data['DiscussionData'] as $Discussion) { ?>
            <tr>
                <th><?php echo anchor(htmlspecialchars($Discussion->Name), DiscussionUrl($Discussion)); ?></th>
                <td><?php echo number_format($Discussion->CountComments); ?></td>
                <td><?php echo number_format($Discussion->CountBookmarks); ?></td>
                <td><?php echo number_format($Discussion->CountViews); ?></td>
            </tr>
        <?php } ?>
        </tbody>
    </table>
</div>
<div class="Summary ActiveUserSummary">
    <table>
        <thead>
        <tr>
            <th><?php echo t('Active Users'); ?></th>
            <!-- <td><?php echo t('Discussions'); ?></td> -->
            <td><?php echo t('Comments'); ?></td>
            <!-- <td><?php echo t('PageViews'); ?></td> -->
        </tr>
        </thead>
        <tbody>
        <?php foreach ($this->Data['UserData'] as $User) { ?>
            <tr>
                <th><?php echo anchor($User->Name, 'profile/'.$User->UserID.'/'.Gdn_Format::url($User->Name)); ?></th>
                <td><?php echo number_format($User->CountComments); ?></td>
                <!-- <td><?php // echo number_format($Discussion->CountViews); ?></td> -->
            </tr>
        <?php } ?>
        </tbody>
    </table>
</div>
