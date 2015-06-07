<?php if (!defined('APPLICATION')) exit(); ?>
<div class="FormTitleWrapper">
    <h1 class="H"><?php echo $this->data('Title'); ?></h1>
    <?php
    echo $this->Form->open();
    echo $this->Form->errors();
    ?>
    <ul>
        <li class="User-Name">
            <?php
            echo $this->Form->label('Username', 'Name');
            $Attributes = array();

            if (!$this->data('_CanEditUsername')) {
                $Attributes['disabled'] = 'disabled';
            }
            echo $this->Form->textBox('Name', $Attributes);
            ?>
        </li>

        <?php if ($this->data('_CanViewPersonalInfo')) : ?>
            <li class="User-Email">
                <?php
                echo $this->Form->label('Email', 'Email');

                if (!$this->data('_CanEditEmail') && UserModel::noEmail()) {

                    echo '<div class="Gloss">',
                    t('Email addresses are disabled.', 'Email addresses are disabled. You can only add an email address if you are an administrator.'),
                    '</div>';

                } else {

                    $EmailAttributes = array();
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
            <li class="User-ShowEmail">
                <?php
                echo $this->Form->CheckBox('ShowEmail', t('Allow other members to see your email?'), array('value' => '1'));
                ?>
            </li>
        <?php endif ?>

        <?php if ($this->data('_CanConfirmEmail')): ?>
            <li class="User-ConfirmEmail">
                <?php
                echo $this->Form->CheckBox('ConfirmEmail', t("Confirmed email address"), array('value' => '1'));
                ?>
            </li>
        <?php endif ?>

        <li class="Gender User-Gender">
            <?php
            echo $this->Form->label('Gender', 'Gender');
            echo $this->Form->RadioList('Gender', $this->GenderOptions, array('default' => 'u'))
            ?>
        </li>

        <?php if (c('Garden.Profile.Titles', false)): ?>
            <li class="User-Title">
                <?php
                echo $this->Form->label('Title', 'Title');
                echo $this->Form->textBox('Title');
                ?>
            </li>
        <?php endif; ?>

        <?php if (c('Garden.Profile.Locations', false)): ?>
            <li class="User-Location">
                <?php
                echo $this->Form->label('Location', 'Location');
                echo $this->Form->textBox('Location');
                ?>
            </li>
        <?php endif; ?>

        <?php
        $this->fireEvent('EditMyAccountAfter');
        ?>
    </ul>
    <?php echo $this->Form->close('Save', '', array('class' => 'Button Primary')); ?>
</div>
