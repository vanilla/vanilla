<?php if (!defined('APPLICATION')) exit();
require_once $this->fetchViewLocation('helper_functions');
function tutLink($TutorialCode, $WriteTitle = TRUE, $ThumbnailSize = 'medium') {
    $Tutorial = GetTutorials($TutorialCode);
    if (!$Tutorial)
        return '';

    $Thumbnail = $ThumbnailSize == 'medium' ? $Tutorial['Thumbnail'] : $Tutorial['LargeThumbnail'];
    return anchor(
        '<img src="'.$Thumbnail.'" alt="'.$Tutorial['Name'].'" title="'.$Tutorial['Name'].'" />'
        .($WriteTitle ? wrap($Tutorial['Name']) : ''),
        'settings/tutorials/'.$Tutorial['Code']
    );
}

?>
<div class="header-menu">
    <a href="<?php echo url('/dashboard/settings/gettingstarted'); ?>" class="active"><?php echo t('Getting Started'); ?></a>
    <a href="<?php echo url('/dashboard/settings/tutorials'); ?>"><?php echo t('Help &amp; Tutorials'); ?></a>
</div>
    <div class="Info">
        <div class="Welcome">
            <h2><?php echo t('Getting Started with Vanilla'); ?></h2>

            <p><strong><?php echo t('Kick-start your community and increase user engagement.'); ?></strong></p>

            <p><?php echo t("Check out these tutorials to get started using Vanilla", "Vanilla is the simplest, most powerful community platform in the world. It's super-duper easy to use. Start with this introductory video and continue with the steps below. Enjoy!"); ?></p>

            <p><?php echo anchor(t("Check out the full list of video tutorials here."), 'settings/tutorials'); ?></p>

            <div class="Video"><?php echo TutLink('introduction', FALSE, 'large'); ?></div>
        </div>
        <div class="Step">
            <div class="NumberPoint"><?= t('1'); ?></div>
            <h2><?php echo t('The Basics'); ?></h2>

            <p><?php echo t('Learn how to use the basic functionality of your forum.'); ?></p>

            <div class="Videos">
                <?php
                echo TutLink('using-the-forum');
                echo TutLink('private-conversations');
                echo TutLink('user-profiles');
                ?>
            </div>
        </div>
        <div class="Step">
            <div class="NumberPoint"><?= t('2'); ?></div>
            <h2><?php echo t("Appearance"); ?></h2>

            <p><?php echo t("Learn how to completely change your forum's look and feel: upload your logo, set your homepage, choose a theme and customize it."); ?></p>

            <div class="Videos">
                <?php echo TutLink('appearance'); ?>
            </div>
        </div>
        <div class="Step">
            <div class="NumberPoint"><?= t('3'); ?></div>
            <h2><?php echo t('Organize'); ?></h2>

            <p><?php echo t('Create & organize discussion categories and manage your users.'); ?></p>

            <div class="Videos">
                <?php
                echo TutLink('user-registration');
                echo TutLink('users');
                echo TutLink('roles-and-permissions');
                echo TutLink('category-management-and-advanced-settings');
                ?>
            </div>
        </div>
        <div class="Step">
            <div class="NumberPoint"><?= t('4'); ?></div>
            <h2><?php echo t('Encourage your friends to join your new community!'); ?></h2>

            <p><?php echo t('Invite your friends to register to your new forum!'); ?></p>
            <?php $registrationURL = url('entry/register', true); ?>
            <p><?php echo sprintf(t('Simply tell them to go to the following URL and register: %s'), anchor($registrationURL, $registrationURL)); ?></p>
        </div>
    </div>
