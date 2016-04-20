<li id="CaptchaSettings">
    <div
        class="Info"><?php echo t('The basic registration form requires new users to copy text from a CAPTCHA image.', '<strong>The basic registration form requires</strong> new users to use reCAPTCHA to keep spammers out of the site. You need an account at <a href="https://www.google.com/recaptcha/">https://www.google.com/recaptcha/</a>. Signing up is FREE and easy. Once you have signed up, come back here and enter the following settings:'); ?></div>
    <table class="Label AltColumns">
        <thead>
        <tr>
            <th><?php echo t('Key Type'); ?></th>
            <th class="Alt"><?php echo t('Key Value'); ?></th>
        </tr>
        </thead>
        <tbody>
        <tr class="Alt">
            <th><?php echo t('Public Key'); ?></th>
            <td class="Alt"><?php echo $this->Form->textBox('Recaptcha.PublicKey'); ?></td>
        </tr>
        <tr>
            <th><?php echo t('Private Key'); ?></th>
            <td class="Alt"><?php echo $this->Form->textBox('Recaptcha.PrivateKey'); ?></td>
        </tr>
        </tbody>
    </table>
</li>