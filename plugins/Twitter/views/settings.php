<?php if (!defined('APPLICATION')) exit();
?>
    <h1><?php echo $this->data('Title'); ?></h1>
<?php
echo $this->Form->open();
echo $this->Form->errors();
?>
    <div class="Info">
        <?php echo t('Twitter Connect allows users to sign in using their Twitter account.', 'Twitter Connect allows users to sign in using their Twitter account. <b>You must register your application with Twitter for this plugin to work.</b>'); ?>
    </div>
    <div class="Configuration">
        <div class="ConfigurationForm">
            <ul>
                <li>
                    <?php
                    echo $this->Form->label('Consumer Key', 'ConsumerKey');
                    echo $this->Form->textBox('ConsumerKey');
                    ?>
                </li>
                <li>
                    <?php
                    echo $this->Form->label('Consumer Secret', 'Secret');
                    echo $this->Form->textBox('Secret');
                    ?>
                </li>
                <li>
                    <?php
                    echo $this->Form->checkBox('SocialSignIn', 'Enable Social Sign In');
                    ?>
                </li>
                <li>
                    <?php
                    echo $this->Form->checkBox('SocialReactions', "Enable Social Reactions.");
                    ?>
                </li>
                <li>
                    <?php
                    echo $this->Form->checkBox('SocialSharing', 'Enable automatic Social Share.');
                    ?>
                </li>
            </ul>
            <?php echo $this->Form->button('Save', array('class' => 'Button SliceSubmit')); ?>
        </div>
        <div class="ConfigurationHelp">
            <p><strong>How to set up Twitter Connect</strong></p>
            <ol>
                <li>You must register Vanilla with Twitter at: <a href="http://dev.twitter.com/apps/new">http://dev.twitter.com/apps/new</a>
                </li>
                <li>Set the <strong>Callback URL</strong> by appending &ldquo;/entry/twauthorize&rdquo; to the end of
                    your forum&rsquo;s URL.
                    (If your forum is at example.com/forum, your Callback URL would be
                    http://example.com/forum/entry/twauthorize).
                </li>
                <li>After registering, copy the "Consumer Key" and "Consumer Secret" into the form on this page and
                    click Save.
                </li>
            </ol>
            <p><?php echo anchor(Img('/plugins/Twitter/design/help-consumervalues-sm.png', array('style' => 'max-width: 763px;')), '/plugins/Twitter/design/help-consumervalues.png', array('target' => '_blank')); ?></p>
        </div>
    </div>
<?php
echo $this->Form->close();
