<?php if (!defined('APPLICATION')) exit(); ?>
<h1><?php echo t('Dashboard Home'); ?></h1>
<?php $this->RenderAsset('Messages'); ?>
<div class="dashboard-widgets">
    <div class="Summary ActiveUserSummary">
        <div class="dashboard-widget-title"><?php echo t('Active Users'); ?></div>
        <table>
            <thead>
            <tr>
                <th><?php echo t('Name'); ?></th>
                <td><?php echo t('Comments'); ?></td>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($this->ActiveUserData as $User) { ?>
                <tr>
                    <th>
                        <div class="media-sm">
                            <div class="media-sm-image-wrap">
                                <?php
                                $PhotoUser = UserBuilder($User);
                                echo userPhoto($PhotoUser); ?>
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
</div>
<div class="dashboard-widgets">
    <div class="Summary Column Column1 ReleasesColumn">
        <div class="dashboard-widget-title"><?php echo t('Updates'); ?></div>
        <div class="List"></div>
    </div>
    <div class="Summary Column Column2 NewsColumn">
        <div class="dashboard-widget-title"><?php echo t('Recent News'); ?></div>
        <div class="List"></div>
    </div>
</div>
