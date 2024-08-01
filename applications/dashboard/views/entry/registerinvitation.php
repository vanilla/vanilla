<?php if (!defined("APPLICATION")) {
    exit();
} ?>
<div class="FormTitleWrapper AjaxForm">
    <h1><?php echo t("Apply for Membership"); ?></h1>

    <div class="FormWrapper">
        <?php
        $TermsOfServiceUrl = Gdn::config("Garden.TermsOfService", "#");
        $TermsOfServiceText = sprintf(
            t('I agree to the <a id="TermsOfService" class="Popup" target="terms" href="%s">terms of service</a>'),
            url($TermsOfServiceUrl)
        );

        // Make sure to force this form to post to the correct place in case the view is
        // rendered within another view (ie. /dashboard/entry/index/):
        echo $this->Form->open(["Action" => url("/entry/registerinvitation"), "id" => "Form_User_Register"]);
        echo $this->Form->errors();
        ?>
        <ul>
            <li>
                <?php
                echo $this->Form->label("Invitation Code", "InvitationCode");
                echo $this->Form->textBox("InvitationCode", [
                    "value" => $this->InvitationCode,
                    "autocorrect" => "off",
                    "autocapitalize" => "off",
                    "Wrap" => true,
                ]);
                ?>
            </li>
            <li>
                <?php
                echo $this->Form->label("Username", "Name");
                echo $this->Form->textBox("Name", ["autocorrect" => "off", "autocapitalize" => "off", "Wrap" => true]);
                echo '<span id="NameUnavailable" class="Incorrect" style="display: none;">' .
                    t("Name Unavailable") .
                    "</span>";
                ?>
            </li>
            <?php
            if ($this->hasCustomProfileFields()) {
                $this->generateFormCustomProfileFields();
            }
            $this->fireEvent("RegisterBeforePassword");
            ?>
            <li>
                <?php
                echo $this->Form->label("Password", "Password");
                echo wrap(
                    sprintf(t("Your password must be at least %d characters long."), c("Garden.Password.MinLength")) .
                        " " .
                        t(
                            "For a stronger password, increase its length or combine upper and lowercase letters, digits, and symbols."
                        ),
                    "div",
                    ["class" => "Gloss"]
                );
                echo $this->Form->input("Password", "password", ["Wrap" => true, "Strength" => true]);
                ?>
            </li>
            <li>
                <?php
                echo $this->Form->label("Confirm Password", "PasswordMatch");
                echo $this->Form->input("PasswordMatch", "password", ["Wrap" => true]);
                echo '<span id="PasswordsDontMatch" class="Incorrect" style="display: none;">' .
                    t("Passwords don't match") .
                    "</span>";
                ?>
            </li>
            <?php $this->fireEvent("ExtendedRegistrationFields"); ?>
            <?php $this->fireEvent("RegisterFormBeforeTerms"); ?>
            <li>
                <?php
                echo $this->Form->checkBox("TermsOfService", "@" . $TermsOfServiceText, ["value" => "1"], false);
                echo $this->Form->checkBox("RememberMe", "Remember me on this computer", ["value" => "1"]);
                ?>
            </li>
            <li class="Buttons">
                <?php echo $this->Form->button("Sign Up", ["class" => "Button Primary"]); ?>
            </li>
        </ul>
        <?php echo $this->Form->close(); ?>
    </div>
</div>
