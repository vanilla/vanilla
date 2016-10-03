<?php if (!defined('APPLICATION')) exit(); ?>
<div class="Summary ActiveUserSummary">
    <div class="dashboard-widget-title"><?php echo t('Active Users'); ?></div>
    <table class="table-data">
        <thead>
        <tr>
            <th><?php echo t('Name'); ?></th>
            <!-- <td><?php echo t('Discussions'); ?></td> -->
            <td class="column-xs"><?php echo t('Comments'); ?></td>
            <!-- <td><?php echo t('PageViews'); ?></td> -->
        </tr>
        </thead>
        <tbody>
        <?php foreach ($this->Data['UserData'] as $User) { ?>
            <tr>
                <th>
                    <div class="media media-sm">
                        <div class="media-left">
                            <div class="media-image-wrap">
                                <?php echo userPhoto($User); ?>
                            </div>
                        </div>
                        <div class="media-body">
                            <div class="media-title username">
                                <?php echo userAnchor($User, 'Username'); ?>
                            </div>
                            <div class="info user-date"><?php echo Gdn_Format::date(val('DateLastActive', $User), 'html'); ?></div>
                        </div>
                    </div>
                </th>
                <td><?php echo number_format($User->CountComments); ?></td>
                <!-- <td><?php // echo number_format($Discussion->CountViews); ?></td> -->
            </tr>
        <?php } ?>
        </tbody>
    </table>
</div>
<div class="Summary PopularDiscussionSummary">
    <div class="dashboard-widget-title"><?php echo t('Popular Discussions'); ?></div>
    <table class="table-data">
        <thead>
        <tr>
            <th class="column-sm"><?php echo t('Title'); ?></th>
            <td class="column-xs"><?php echo t('Comments'); ?></td>
            <td class="column-xs"><?php echo t('Follows'); ?></td>
            <td class="column-xs"><?php echo t('Views'); ?></td>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($this->Data['DiscussionData'] as $Discussion) { ?>
            <tr>
                <th>
                    <div class="media media-sm">
                        <div class="media-body">
                            <div class="media-title">
                                <?php echo anchor(htmlspecialchars($Discussion->Name), DiscussionUrl($Discussion)); ?>
                            </div>
                            <div class="info post-date">
                                <?php echo Gdn_Format::date($Discussion->DateInserted, 'html'); ?>
                            </div>
                        </div>
                    </div>
                </th>
                <td><?php echo number_format($Discussion->CountComments); ?></td>
                <td><?php echo number_format($Discussion->CountBookmarks); ?></td>
                <td><?php echo number_format($Discussion->CountViews); ?></td>
            </tr>
        <?php } ?>
        </tbody>
    </table>
</div>
