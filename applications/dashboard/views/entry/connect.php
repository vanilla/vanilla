<?php if (!defined("APPLICATION")) {
    exit();
}

/** @var \EntryController $this */

//Do we have username passed from sso?
$userName = $this->Form->getFormValue("Name", null) ?? $this->Form->getFormValue("ConnectName");
// Get the connection information.
if (!($connectName = $this->Form->getFormValue("FullName"))) {
    $connectName = $userName;
}

$connectPhoto = $this->Form->getFormValue("Photo");
if (!$connectPhoto) {
    $connectPhoto = "/applications/dashboard/design/images/usericon.gif";
}
$connectSource = $this->Form->getFormValue("ProviderName");

// By default, clients will try to connect existing users.
// Turning this off forces connecting clients to choose unique usernames.
$allowConnect = $this->data("AllowConnect");

$hasUserID = $this->Form->getFormValue("UserID");

if (!$hasUserID) {
    // Determine whether to show ConnectName field.
    $existingUsers = (array) $this->data("ExistingUsers", []);
    $noConnectName = $this->data("NoConnectName");

    // You just landed on this page.
    $firstTimeHere = !$this->isConnectPostBack();
    $connectNameProvided = (bool) $this->Form->getFormValue("ConnectName");

    $validationResults = $this->Form->validationResults();
    $usernameNotValid =
        array_key_exists("Name", $validationResults) || array_key_exists("ConnectName", $validationResults);

    // Buckle up, deciding whether to show this field is intense.
    // Any of these 3 scenarios will do it:
    $displayConnectName =
        // 1) If you arrived with NO ConnectName OR you've clicked Submit WITH a ConnectName (not not both!)
        //    we need to display the field again so that you can add/edit it.
        ($firstTimeHere xor $connectNameProvided) ||
        // 2) If you clicked submit and we found matches (but validation failed and you need to try again).
        (!$firstTimeHere && count($existingUsers)) ||
        // 3) We're forcing a manual username selection.
        !$allowConnect ||
        // 4) There was an error with the submitted name.
        $usernameNotValid;
}
?>
<div class="Connect FormTitleWrapper AjaxForm">
    <h1><?php echo stringIsNullOrEmpty($connectSource)
        ? t("Sign In")
        : sprintf(t("%s Connect"), htmlentities($connectSource)); ?></h1>

    <div class="FormWrapper">
        <?php
        echo $this->Form->open();
        echo $this->Form->errors();

        /**
         *  HideName can be passed by any plugin that hooks into
         *  the EntryController that has rules that require this form to be
         *  shown but not the Name Field.
         */
        if ($connectName || ($connectPhoto && !$this->data("HideName"))): ?>
            <div class="MeBox">
                <?php
                if ($connectPhoto) {
                    echo '<span class="PhotoWrap">',
                        img($connectPhoto, ["alt" => t("Profile Picture"), "class" => "ProfilePhoto"]),
                        "</span>";
                }

                echo '<div class="WhoIs">';
                if ($connectName && $connectSource) {
                    $NameFormat = t("You are connected as %s through %s.");
                } elseif ($connectName) {
                    $NameFormat = t("You are connected as %s.");
                } elseif ($connectSource) {
                    $NameFormat = t('You are connected through %2$s.');
                } else {
                    $NameFormat = "";
                }

                $NameFormat = '%1$s';
                echo sprintf(
                    $NameFormat,
                    '<span class="Name">' . htmlspecialchars($connectName) . "</span>",
                    '<span class="Source">' . htmlspecialchars($connectSource) . "</span>"
                );

                echo wrap(t("ConnectCreateAccount", "Add Info &amp; Create Account"), "h3", ["aria-level" => 2]);

                echo "</div>";
                ?>
            </div>
        <?php endif;
        ?>

        <?php
        if ($hasUserID): ?>
            <div class="SignedIn">
                <?php echo '<div class="Info">', t("You are now signed in."), "</div>"; ?>
            </div>
            <?php else: ?>
            <ul>
                <?php if ($this->Form->getFormValue("EmailVisible")): ?>
                <li>
                    <?php
                    echo $this->Form->label("Email", "Email", ["required" => true]);
                    echo $this->Form->textBox("Email", ['required' => true]);
                    ?>
                </li>
        <?php endif; ?>

        <?php if ($displayConnectName && !$this->data("HideName")): ?>

                <li>
                    <?php
                    if (count($existingUsers) == 1 && $noConnectName) {
                        $Row = reset($existingUsers);

                        echo '<div class="FinePrint">',
                            t("ConnectAccountExists", "You already have an account here."),
                            "</div>",
                            wrap(
                                sprintf(
                                    t("ConnectRegisteredName", "Your registered username: <strong>%s</strong>"),
                                    htmlspecialchars($Row["Name"])
                                ),
                                "div",
                                ["class" => "ExistingUsername"]
                            );
                        $this->addDefinition("NoConnectName", true);
                        echo $this->Form->hidden("UserSelect", ["Value" => $Row["UserID"]]);
                    }

                    if (count($existingUsers) >= 1 && !$noConnectName) {
                        echo $this->Form->label("Username", "ConnectName", ['required' => true]);
                        echo \Gdn::translate(
                            "ConnectWithExistingUser",
                            "One or more users with your name already exist, would you like to connect as them?"
                        );
                        $connectNameMessage = !$allowConnect
                            ? ""
                            : ' <span class="FinePrint">' . \Gdn::translate("(Requires a password.)") . "</span>";
                        foreach ($existingUsers as $Row) {
                            echo wrap(
                                $this->Form->radio("UserSelect", $Row["Name"] . $connectNameMessage, [
                                    "value" => $Row["UserID"],
                                    "class" => "existingConnectName",
                                ]),
                                "div"
                            );
                        }
                        $connectChooseName =
                            ' <span class="FinePrint">(' .
                            \Gdn::translate("ConnectChooseName", "Choose a name to identify yourself on the site.") .
                            ")</span>";
                        echo wrap(
                            $this->Form->radio("UserSelect", "Other" . $connectChooseName, ["value" => "other"]),
                            "div"
                        );
                        echo $this->Form->textbox("ConnectName");
                    }

                    if (count($existingUsers) === 0 && !$noConnectName) {
                        if (!$userName || $usernameNotValid) {
                            echo $this->Form->label("Username", "ConnectName", ['required' => true]);
                            echo wrap(
                                \Gdn::translate("ConnectChooseName", "Choose a name to identify yourself on the site."),
                                "div",
                                ["class" => "FinePrint"]
                            );
                            echo $this->Form->textbox("ConnectName", ["aria-label" => t("Username"), 'required' => true]);
                        }
                    }
                    ?>
                </li>
        <?php endif; ?>

                <?php
                $this->fireEvent("RegisterBeforePassword");

                if (
                    $this->hasRegistrationCustomFields() &&
                    (count($existingUsers) === 0 || (count($existingUsers) >= 1 && !$noConnectName))
                ) {
                    $wrapper =
                        count($existingUsers) >= 1
                            ? [
                                "tag" => "div",
                                "attributes" => ["id" => "connect-custom-profile-fields", "style" => "display: none"],
                            ]
                            : null;

                    $this->generateFormCustomProfileFields($wrapper, true);
                }
                ?>

                <?php if (!$this->data("HidePassword")) {
                    echo '<li id="ConnectPassword">';
                    echo $this->Form->label("Password", "ConnectPassword", ['required' => true]);
                    $PasswordMessage = t("ConnectExistingPassword", "Enter your existing account password.");
                    echo wrap($PasswordMessage, "div", ["class" => "FinePrint"]);
                    echo $this->Form->input("ConnectPassword", "password", ['required' => true]);
                    echo "</li>";
                } ?>
            </ul>

            <?php echo '<div class="Buttons">',
                wrap($this->Form->button("Connect", ["class" => "Button Primary"]), "div", [
                    "class" => "ButtonContainer",
                ]),
                "</div>";endif;

        echo $this->Form->close();
        ?>
    </div>
</div>
