<?php if (!defined('APPLICATION')) exit(); ?>
    <h1><?php echo $this->data('Title'); ?></h1>
<?php
echo $this->Form->open();
echo $this->Form->errors();
?>
    <div class="padded alert alert-warning">
        <?php echo sprintf(t('You must register your application with %s for this plugin to work.'), t('Facebook')); ?>
    </div>
    <div class="padded">
        <?php echo t('Facebook Connect allows users to sign in using their Facebook account.'); ?>
        <?php echo ' '.anchor(sprintf(t('How to set up %s.'), t('Facebook Connect')), 'http://docs.vanillaforums.com/addons/social/facebook/', array('target' => '_blank')); ?>
    </div>
    <ul>
        <li class="form-group row">
            <?php
            echo $this->Form->labelWrap('Application ID', 'ApplicationID');
            echo $this->Form->textBoxWrap('ApplicationID');
            ?>
        </li>
        <li class="form-group row">
            <?php
            echo $this->Form->labelWrap('Application Secret', 'Secret');
            echo $this->Form->textBoxWrap('Secret');
            ?>
        </li>
        <li class="form-group row">
            <div class="input-wrap no-label">
                <?php
                echo $this->Form->checkBox('UseFacebookNames', 'Use Facebook names for usernames.');
                ?>
            </div>
        </li>
        <li class="form-group row">
            <div class="input-wrap no-label">
                <?php
                echo $this->Form->checkBox('SendConnectEmail', 'Send users a welcome email.');
                ?>
            </div>
        </li>
        <li class="form-group row">
            <div class="input-wrap no-label">
                <?php
                echo $this->Form->checkBox('SocialSignIn', 'Enable Social Sign In');
                ?>
            </div>
        </li>
        <li class="form-group row">
            <div class="input-wrap no-label">
                <?php
                echo $this->Form->checkBox('SocialReactions', "Enable Social Reactions.");
                ?>
            </div>
        </li>
        <li class="form-group row">
            <div class="input-wrap no-label">
                <?php
                echo $this->Form->checkBox('SocialSharing', 'Enable automatic Social Share.');
                ?>
            </div>
        </li>
    </ul>
<div class="form-footer js-modal-footer">
<?php echo $this->Form->button('Save', array('class' => 'Button SliceSubmit')); ?>
</div>
<?php
echo $this->Form->close();
