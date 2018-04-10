<?php if (!defined('APPLICATION')) exit();
?>
    <h1><?php echo $this->data('Title'); ?></h1>

    <div class="padded alert alert-warning">
        <?php echo sprintf(t('You must register your application with %s for this plugin to work.'), t('Twitter')); ?>
    </div>
    <div class="padded">
        <?php echo t('Twitter Connect allows users to sign in using their Twitter account.'); ?>
        <?php echo ' '.anchor(sprintf(t('How to set up %s.'), t('Twitter Connect')), 'http://docs.vanillaforums.com/help/sso/social-connect/#twitter', ['target' => '_blank']); ?>
    </div>
<?php
echo $this->Form->open();
echo $this->Form->errors();
?>
    <ul>
        <li class="form-group">
            <?php
            echo $this->Form->labelWrap('Consumer Key', 'ConsumerKey');
            echo $this->Form->textBoxWrap('ConsumerKey');
            ?>
        </li>
        <li class="form-group">
            <?php
            echo $this->Form->labelWrap('Consumer Secret', 'Secret');
            echo $this->Form->textBoxWrap('Secret');
            ?>
        </li>
        <li class="form-group">
            <div class="input-wrap no-label">
                <?php
                echo $this->Form->checkBox('SocialSignIn', 'Enable Social Sign In');
                ?>
            </div>
        </li>
        <li class="form-group">
            <div class="input-wrap no-label">
                <?php
                echo $this->Form->checkBox('SocialReactions', "Enable Social Reactions.");
                ?>
            </div>
        </li>
        <li class="form-group">
            <div class="input-wrap no-label">
                <?php
                echo $this->Form->checkBox('SocialSharing', 'Enable automatic Social Share.');
                ?>
            </div>
        </li>
    </ul>
<?php echo $this->Form->close('Save');
