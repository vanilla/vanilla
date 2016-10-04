<?php if (!defined('APPLICATION')) exit(); ?>
<h1><?php echo t('Dashboard Home'); ?></h1>
<?php $this->RenderAsset('Messages'); ?>
<div class="summaries">
    <div class="table-summary-wrap ActiveUserSummary">
        <div class="dashboard-widget-title"><?php echo t('Active Users'); ?></div>
        <table class="table-summary">
            <thead>
                <tr>
                    <th><?php echo t('Name'); ?></th>
                    <th><?php echo t('Comments'); ?></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($this->ActiveUserData as $User) { ?>
                <tr>
                    <td>
                        <div class="media media-sm">
                            <div class="media-left">
                                <div class="media-image-wrap">
                                    <?php
                                    $PhotoUser = UserBuilder($User);
                                    echo userPhoto($PhotoUser); ?>
                                </div>
                            </div>
                            <div class="media-body">
                                <div class="media-title username">
                                    <?php echo userAnchor($User, 'Username'); ?>
                                </div>
                                <div class="info user-date"><?php echo Gdn_Format::date(val('DateLastActive', $User), 'html'); ?></div>
                            </div>
                        </div>
                    </td>
                    <td><?php echo number_format($User->CountComments); ?></td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
    </div>
</div>

<div class="summaries">
    <div class="ReleasesColumn">
        <div class="table-summary-title"><?php echo t('Updates'); ?></div>
        <div class="List"></div>
    </div>
    <div class="NewsColumn">
        <div class="table-summary-title"><?php echo t('Recent News'); ?></div>
        <div class="List"></div>
    </div>
</div>

