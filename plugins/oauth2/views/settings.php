<h1><?php echo $this->data('Title'); ?></h1>

<div class="padded alert alert-warning">

    <?php echo sprintf(t('oauth2Instructions'), $this->data('redirectUrls')); ?>

</div>
<?php

echo $this->Form->open();
echo $this->Form->errors();

echo $this->Form->simple($this->data('_Form'));

echo '<div class="form-footer js-modal-footer">';
//echo $this->Form->button( );
echo $this->Form->close('Save');
echo '</div>';

