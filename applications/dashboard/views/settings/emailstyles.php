<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::session();

?>
    <h1><?php echo t('Email Styles'); ?></h1>
<?php
echo $this->Form->open(array('enctype' => 'multipart/form-data'));
echo $this->Form->errors();

$emailImage = c('Garden.Email.Styles.Image');
?>
    <div class="Padded email-image">
        <?php
        if ($this->data('EmailImage')) {
            echo img($this->data('EmailImage'), array('class' => 'js-email-image'));
        } else {
            echo '<img class="js-email-image Hidden"/>';
        }
        ?>
    </div>
<?php
echo wrap(anchor(t('Upload New Email Banner'), '/dashboard/settings/emailimage/'.Gdn::session()->transientKey(), 'UploadImage Button'), 'div', array('style' => 'margin-bottom: 20px'));
?>
<?php
$hideCssClass = $emailImage ? '' : ' Hidden';
echo wrap(t('Remove Email Banner'), 'div', array('class' => 'js-remove-email-image-button RemoveButton Button'.$hideCssClass));
?>
    <ul>
        <li>
            <?php
            echo $this->Form->label('Background Color', 'Garden.Email.Styles.BackgroundColor');
            echo $this->Form->color('Garden.Email.Styles.BackgroundColor', 'background-color');
            ?>
        </li>
        <li>
            <?php
            echo $this->Form->label('Button Background Color', 'Garden.Email.Styles.ButtonBackgroundColor');
            echo $this->Form->color('Garden.Email.Styles.ButtonBackgroundColor', 'button-background-color');
            ?>
        </li>
        <li>
            <?php
            echo $this->Form->label('Link Color', 'Garden.Email.Styles.LinkColor');
            echo $this->Form->color('Garden.Email.Styles.LinkColor', 'link-color');
            ?>
        </li>
    </ul>
<?php echo $this->Form->close(t('Save Colors'));

