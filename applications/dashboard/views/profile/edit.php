<?php if (!defined('APPLICATION')) exit();

use Vanilla\Theme\BoxThemeShim;
use Vanilla\Utility\HtmlUtils;
?>
<div class="FormTitleWrapper">
    <?php BoxThemeShim::startHeading(); ?>
    <h1 class="H"><?php echo $this->data('Title'); ?></h1>
    <?php BoxThemeShim::endHeading(); ?>
    <?php
    /** @var ProfileController $this */
    echo $this->Form->open(['class' => 'pageBox']);
    echo $this->Form->errors();
    ?>
    <ul role="presentation">
        <li class="User-Name" role="presentation">
            <?php
            echo $this->Form->label('Username', 'Name');
            $Attributes = [];

            if (!$this->data('_CanEditUsername')) {
                $Attributes['disabled'] = 'disabled';
            }
            echo $this->Form->textBox('Name', $Attributes);
            ?>
        </li>

        <?php if ($this->data('_CanViewPersonalInfo')) : ?>
            <li class="User-Email" role="presentation">
                <?php
                $emailDescription = HtmlUtils::uniqueElementID('EmailDescription');
                echo $this->Form->label('Email', 'Email');

                if (!$this->data('_CanEditEmail') && UserModel::noEmail()) {

                    echo '<div class="Gloss">',
                    t('Email addresses are disabled.', 'Email addresses are disabled. You can only add an email address if you are an administrator.'),
                    '</div>';

                } else {

                    $EmailAttributes = ['arial-label' => t('Editing this field will require password entry'), 'title' => t('Editing this field will require password entry')];
                    if (!$this->data('_CanEditEmail')) {
                        $EmailAttributes['disabled'] = 'disabled';
                    }

                    // Email confirmation
                    if (!$this->data('_EmailConfirmed'))
                        $EmailAttributes['class'] = 'InputBox Unconfirmed';

                    echo $this->Form->textBox('Email', $EmailAttributes);

                }
                ?>
            </li>
        <?php endif; ?>

        <?php if ($this->data('_CanEditEmail')): ?>
            <li class="User-ShowEmail" role="presentation">
                <?php
                echo $this->Form->checkBox('ShowEmail', t('Allow other members to see your email?'), ['value' => '1']);
                ?>
            </li>
        <?php endif ?>

        <?php if ($this->data('_CanConfirmEmail')): ?>
            <li class="User-ConfirmEmail" role="presentation">
                <?php
                echo $this->Form->checkBox('ConfirmEmail', t("Confirmed email address"), ['value' => '1']);
                ?>
            </li>
        <?php endif ?>

        <?php if ($this->data('_CanAddEditTitle')): ?>
            <li class="User-Title" role="presentation">
                <?php
                echo $this->Form->label($this->data('_TitleLabel', 'Title'), 'Title');
                // Allow "Title" field to be a dropdown.
                if (!is_null($this->data('_TitleFormType')) && $this->data('_TitleFormType') === 'Dropdown') {
                    echo $this->Form->dropDown('Title', $this->data('_TitleOptions'));
                } else {
                    echo $this->Form->textBox('Title');
                }
                ?>
            </li>
        <?php endif; ?>

        <?php if ($this->data('_CanAddEditLocation')): ?>
            <li class="User-Location" role="presentation">
                <?php
                echo $this->Form->label($this->data('_LocationLabel', 'Location'), 'Location');
                echo $this->Form->textBox('Location');
                ?>
            </li>
        <?php endif; ?>

        <?php
        $this->fireEvent('EditMyAccountAfter');
        ?>
    </ul>
    <?php echo $this->Form->close('Save', '', ['class' => 'Button Primary']); ?>
</div>
