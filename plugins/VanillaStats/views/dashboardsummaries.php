<?php if (!defined('APPLICATION')) exit(); ?>
<div class="Summary ActiveUserSummary">
    <div class="dashboard-widget-title"><?php echo t('Active Users'); ?></div>
    <table>
        <thead>
        <tr>
            <th><?php echo t('Name'); ?></th>
            <!-- <td><?php echo t('Discussions'); ?></td> -->
            <td><?php echo t('Comments'); ?></td>
            <!-- <td><?php echo t('PageViews'); ?></td> -->
        </tr>
        </thead>
        <tbody>
        <?php foreach ($this->Data['UserData'] as $User) { ?>
            <tr>
                <th>
                    <div class="media-sm">
                        <div class="media-sm-image-wrap">
                            <?php echo userPhoto($User); ?>
                        </div>
                        <div class="media-sm-content">
                            <div class="media-sm-title username">
                                <?php echo userAnchor($User, 'Username'); ?>
                            </div>
                            <div class="media-sm-info user-date"><?php echo Gdn_Format::date(val('DateLastActive', $User), 'html'); ?></div>
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
    <table>
        <thead>
        <tr>
            <th><?php echo t('Title'); ?></th>
            <td><?php echo t('Comments'); ?></td>
            <td><?php echo t('Follows'); ?></td>
            <td><?php echo t('Views'); ?></td>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($this->Data['DiscussionData'] as $Discussion) { ?>
            <tr>
                <th>
                    <div class="media-sm-item">
                        <div class="media-sm-content">
                            <div class="media-sm-title">
                                <?php echo anchor(htmlspecialchars($Discussion->Name), DiscussionUrl($Discussion)); ?>
                            </div>
                            <div class="media-sm-info post-date">
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
