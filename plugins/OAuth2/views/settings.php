<h1><?php echo $this->data('Title'); ?></h1>

<div class="PageInfo">
    <h2><?php echo t('Create Single Sign On integration using the OAuth2 protocol!'); ?></h2>

    <p>
        <?php
        echo t('<p>If you haven\'t already, create an SSO application.</p>
                <p>Once your application is created you will receive a unique Client ID, Client Secret and Domain.</p>
                <p>You will probably need to provide your SSO application with an allowed callback URL, in part, to validate requests. The callback url for this forum is <code>'.$this->data('redirectUrls').'</code></p>');
        ?>
    </p>
</div>
<?php

echo $this->Form->open(),
   $this->Form->errors();

echo $this->Form->simple($this->data('_Form'));

echo '<div class="Buttons">';
echo $this->Form->button('Save');
echo $this->Form->close();
echo '</div>';

