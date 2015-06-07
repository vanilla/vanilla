<?php if (!defined('APPLICATION')) exit(); ?>
<div class="FormTitleWrapper">
    <h1><?php echo t("Apply for Membership") ?></h1>

    <div class="FormWrapper">
        <?php
        $TermsOfServiceUrl = Gdn::config('Garden.TermsOfService', '#');
        $TermsOfServiceText = sprintf(t('I agree to the <a id="TermsOfService" class="Popup" target="terms" href="%s">terms of service</a>'), url($TermsOfServiceUrl));
        // Make sure to force this form to post to the correct place in case the view is
        // rendered within another view (ie. /dashboard/entry/index/):
        echo $this->Form->open(array('Action' => url('/entry/register'), 'id' => 'Form_User_Register'));
        echo $this->Form->errors();
        ?>
        <ul>
            <?php if (!$this->data('NoEmail')): ?>
                <li>
                    <?php
                    echo $this->Form->label('Email', 'Email');
                    echo $this->Form->textBox('Email', array('type' => 'email', 'Wrap' => TRUE));
                    echo '<span id="EmailUnavailable" class="Incorrect" style="display: none;">'.t('Email Unavailable').'</span>';
                    ?>
                </li>
            <?php endif; ?>
            <li>
                <?php
                echo $this->Form->label('Username', 'Name');
                echo $this->Form->textBox('Name', array('Wrap' => true, 'autocorrect' => 'off', 'autocapitalize' => 'off'));
                echo '<span id="NameUnavailable" class="Incorrect" style="display: none;">'.t('Name Unavailable').'</span>';
                ?>
            </li>
            <?php $this->fireEvent('RegisterBeforePassword'); ?>
            <li>
                <?php
                echo $this->Form->label('Password', 'Password');
                echo wrap(sprintf(t('Your password must be at least %d characters long.'), c('Garden.Registration.MinPasswordLength')), 'div', array('class' => 'Gloss'));
                echo $this->Form->Input('Password', 'password', array('Wrap' => true, 'Strength' => TRUE));
                ?>
            </li>
            <li>
                <?php
                echo $this->Form->label('Confirm Password', 'PasswordMatch');
                echo $this->Form->Input('PasswordMatch', 'password', array('Wrap' => TRUE));
                echo '<span id="PasswordsDontMatch" class="Incorrect" style="display: none;">'.t("Passwords don't match").'</span>';
                ?>
            </li>
            <li class="Gender">
                <?php
                echo $this->Form->label('Gender', 'Gender');
                echo $this->Form->RadioList('Gender', $this->GenderOptions, array('default' => 'u'))
                ?>
            </li>
            <?php $this->fireEvent('ExtendedRegistrationFields'); ?>
            <?php if ($this->Form->getValue('DiscoveryText') || val('DiscoveryText', $this->Form->validationResults())): ?>
                <li>
                    <?php
                    echo $this->Form->label('Why do you want to join?', 'DiscoveryText');
                    echo $this->Form->textBox('DiscoveryText', array('MultiLine' => true, 'Wrap' => TRUE));
                    ?>
                </li>
            <?php endif; ?>
            <?php $this->fireEvent('RegisterFormBeforeTerms'); ?>
            <li>
                <?php
                echo $this->Form->CheckBox('TermsOfService', '@'.$TermsOfServiceText, array('value' => '1'));
                echo $this->Form->CheckBox('RememberMe', t('Remember me on this computer'), array('value' => '1'));
                ?>
            </li>
            <li class="Buttons">
                <?php echo $this->Form->button('Sign Up', array('class' => 'Button Primary')); ?>
            </li>
        </ul>
        <?php echo $this->Form->close(); ?>
    </div>
</div>
