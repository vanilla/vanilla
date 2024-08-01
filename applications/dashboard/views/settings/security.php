<?php if (!defined("APPLICATION")) {
    exit();
}
helpAsset(
    t("Need More Help?"),
    anchor(
        t("Video tutorial on advanced settings"),
        "https://success.vanillaforums.com/kb/articles/561-security-settings-in-vanilla"
    )
);
?>
<h1><?php echo t("Security"); ?></h1>
<?php
/** @var Gdn_Form $form */
$form = $this->Form;
echo $form->open();
echo $form->errors();
?>
<ul>
    <li class="form-group"><?php
    $leavingLabel = "Warn users if a link in a post will cause them to leave the forum";
    $leavingDesc = "@" . t("Alert users if they click external link.");
    echo $form->toggle("Garden.Format.WarnLeaving", $leavingLabel, [], $leavingDesc);
    ?></li>
    <li class="form-group">
        <div class="label-wrap">
            <?php echo $form->label("Trusted Domains", "Garden.TrustedDomains"); ?>
            <div class="info">
                <p>
                    <?php echo t(
                        "You can specify an allow list of trusted domains.",
                        "You can specify a list of trusted domains that are safe for redirects & embedding."
                    ); ?>
                </p>
                <p><?php
                echo t("Specify one domain per line. Use * for wildcard matches.");
                echo "<br/>";
                echo t(
                    "For example, to allow yourdomain.com, www.yourdomain.com, and help.yourdomain.com, you would add *.yourdomain.com"
                );
                ?></p>
                <p>
                    <?php echo t(
                        "Protocols (ex. https://) and paths (ex. yourdomain.com/some/path) should be omitted."
                    ); ?>
                </p>
            </div>
        </div>
        <div class="input-wrap">
            <?php echo $form->textBox("Garden.TrustedDomains", ["MultiLine" => true]); ?>
        </div>
    </li>
    <li class="form-group">
        <div class="label-wrap">
            <?php echo $form->label("Content Security Domains", SettingsController::CONFIG_CSP_DOMAINS); ?>
            <div class="info">
                <p>
                    <?php echo t(
                        "You can specify an allow list of trusted domains. (CSP)",
                        "You can specify an allow list of trusted domains (ex. yourdomain.com) that are safe to load javascript from."
                    ); ?>
                </p>
                <p><?php
                echo t("Specify one domain per line. Use * for wildcard matches.");
                echo "<br/>";
                echo t(
                    "For example, to allow yourdomain.com, www.yourdomain.com, and help.yourdomain.com, you would add *.yourdomain.com"
                );
                ?></p>
                <p>
                    <?php echo t(
                        "Protocols (ex. https://) and paths (ex. yourdomain.com/some/path) should be omitted."
                    ); ?>
                </p>
            </div>
        </div>
        <div class="input-wrap">
            <?php echo $form->textBox(SettingsController::CONFIG_CSP_DOMAINS, [
                "MultiLine" => true,
                "implode" => "\n",
            ]); ?>
        </div>
    </li>
<?php
$leavingLabel = t("Allow Third-Party Script Execution");
$leavingDesc =
    t(
        "Enabling this feature will modify your Content Security Policy to trust additional scripts injected as dependencies by your custom scripts."
    ) .
    " " .
    t("This is not necessary on most sites except those that are using features like AdSense and Google Tag Manager.");
$modalBody =
    t(
        "Enabling this feature will modify your site's Content Security Policy (CSP) to permit approved scripts in your current trusted domain list to dynamically load other scripts,it will add a ‘strict-dynamic’ directive to your CSP offering more flexibility in incorporating third-party content."
    ) .
    "<p>" .
    t("However, this comes with increased responsibility to ensure the security of your site.") .
    "</p></br>" .
    t("Before activating this feature, please be aware of the following potential implications:") .
    "<p><b>" .
    t("Increased Security Risks:") .
    "</b> " .
    t("Could introduce vulnerabilities like Cross-Site Scripting (XSS) if initial scripts are compromised.") .
    "</p><p><b>" .
    t("Browser Compatibility:") .
    "</b> " .
    t("Possible compatibility problems with older browsers, affecting user experience.") .
    "</br></br>" .
    " <a target=\"_blank\" href=\"https://caniuse.com/?search=strict-dynamic\">" .
    t("View browser compatibility chart") .
    "<img style=\"margin-bottom: 2px;\" src=\"/applications/dashboard/design/images/external-link.svg\"/> </a> </p>";

echo $form->toggleInputReact(SettingsController::CONFIG_CSP_STRICT_DYNAMIC, $leavingLabel, $leavingDesc, [
    "content" => $modalBody,
    "title" => t("Are you Sure?"),
]);
?>

</ul>
<h2 class="subheading"><?php echo t("HTTP Strict Transport Security (HSTS) Settings"); ?></h2>
<ul>
    <li>
        <div class="info"><?php echo sprintf(
            t("Learn more about HSTS at %s."),
            '<a href="https://hstspreload.org/">https://hstspreload.org/</a>'
        ); ?></div>
    </li>
    <li class="form-group">
        <div class="label-wrap">
            <?php echo $form->label("Max Age", "Garden.Security.Hsts.MaxAge"); ?>
            <div class="info">
                <p><?php echo t(
                    "Security.Hsts.MaxAgeRecommendation",
                    "We recommend starting with a max age of 1 week" .
                        " and then increasing it to 1 month then 1 year once you see your site works as expected."
                ); ?>
                </p>
            </div>
        </div>
        <div class="input-wrap inline">
            <?php echo $form->radioList(
                "Garden.Security.Hsts.MaxAge",
                [
                    604800 => plural(1, "%s week", "%s weeks"),
                    2592000 => plural(1, "%s month", "%s months"),
                    31536000 => plural(1, "%s year", "%s years"),
                    63072000 => plural(2, "%s year", "%s years"),
                ],
                ["class" => "inline"]
            ); ?>
        </div>
    </li>
    <li class="form-group">
        <div class="label-wrap-wide">
            <?php echo $form->label("Include Subdomains", "Garden.Security.Hsts.IncludeSubDomains"); ?>
            <div class="info">
                <p>
                    <?php echo t(
                        "Security.Hsts.IncludeSubDomains",
                        'When enabled, this rule applies to all of your site\'s subdomains as well.'
                    ); ?>
                </p>
                <p><?php echo t(
                    "Security.Hsts.HTTPSWarning",
                    "Warning: Only enable this feature if you are sure all your subdomains are configured for HTTPS with valid certificates."
                ); ?></p>
            </div>
        </div>
        <div class="input-wrap-right">
            <?php echo $form->toggle("Garden.Security.Hsts.IncludeSubDomains"); ?>
        </div>
    </li>

    <li class="form-group">
        <div class="label-wrap-wide">
            <?php echo $form->label("Preload", "Garden.Security.Hsts.Preload"); ?>
            <div class="info">
                <p class="warning">
                    <?php echo t(
                        "Security.Hsts.SubmitWarning",
                        "Warning: It's great to support HSTS preloading as a best practice. However, you must submit your site to hstspreload.org " .
                            "to ensure that it is successfully pre-loaded (i.e. to get the full protection for the intended configuration)."
                    ); ?></p>
            </div>
        </div>
        <div class="input-wrap-right">
            <?php echo $form->toggle("Garden.Security.Hsts.Preload"); ?>
        </div>
    </li>
</ul>
<h2 class="subheading"><?php echo t("Logins"); ?></h2>
<ul>
    <li>
        <div class="info"><?php echo t("Configuration settings user logins in Vanilla"); ?></div>
    </li>
    <li class="form-group">
        <div class="label-wrap-wide">
            <?php echo $form->label("Session Timeout", "Garden.Cookie.PersistExpiry"); ?>
            <div class="info">
                <?php echo t(
                    "Garden.Cookie.PersistExpiry.Description",
                    "If a user does not visit the site within this time period they will be automatically signed out."
                ); ?>
            </div>
        </div>
        <div class="input-wrap-right">
            <div class="textbox-suffix">
                <?php echo $form->dropDown(
                    "Garden.Cookie.PersistExpiry",
                    [
                        "1 month" => plural(1, "%s month", "%s months"),
                        "2 weeks" => plural(2, "%s week", "%s weeks"),
                        "1 week" => plural(1, "%s week", "%s weeks"),
                        "1 days" => plural(1, "%s day", "%s days"),
                        "6 hours" => plural(6, "%s hour", "%s hours"),
                        "1 hour" => plural(1, "%s hour", "%s hours"),
                    ],
                    [
                        "addMissing" => true,
                        "optionFormat" => function ($value) {
                            $parts = explode(" ", $value);
                            return plural($parts[0], "%s {$parts[1]}", "%s {$parts[1]}");
                        },
                    ]
                ); ?>
            </div>
        </div>
    </li>
    <li class="form-group">
        <div class="label-wrap-wide">
            <?php echo $form->label("Password Minimum Length", "Garden.Password.MinLength"); ?>
            <div class="info">
                <?php echo t(
                    "Password.MinLength",
                    "Minimum character length allowed for users passwords on password create and reset pages."
                ); ?>
            </div>
        </div>
        <div class="input-wrap-right">
            <div class="textbox-suffix">
                <?php
                $attributes = [
                    "type" => "number",
                    "min" => SettingsController::DEFAULT_PASSWORD_LENGTH,
                    "required" => "required",
                ];
                echo $form->textBox("Garden.Password.MinLength", $attributes);
                ?>
            </div>
        </div>
    </li>
    <li class="form-group">
        <div class="label-wrap-wide">
            <?php echo $form->label("Number of Login Attempts", "Garden.SignIn.Attempts"); ?>
            <div class="info">
                <?php echo t(
                    "SignIn.Attempts",
                    "Number of concurrent login attempts before user account is locked out, 0 means this is turned off."
                ); ?>
            </div>
        </div>
        <div class="input-wrap-right">
            <div class="textbox-suffix">
                <?php
                $attributes = [
                    "type" => "number",
                    "min" => 0,
                    "max" => 999,
                ];
                echo $form->textBox("Garden.SignIn.Attempts", $attributes);
                ?>
            </div>
        </div>
    </li>
    <li class="form-group">
        <div class="label-wrap-wide">
            <?php echo $form->label("Lockout Time (seconds)", "Garden.SignIn.LockoutTime"); ?>
            <div class="info">
                <?php echo t(
                    "SignIn.LockoutTime",
                    "The amount of time a user is blocked from logging in after exceeding the number of login attempts."
                ); ?>
            </div>
        </div>
        <div class="input-wrap-right">
            <div class="textbox-suffix">
                <?php
                $attributes = [
                    "type" => "number",
                    "min" => 0,
                    "max" => "999999",
                ];
                echo $form->textBox("Garden.SignIn.LockoutTime", $attributes);
                ?>
            </div>
        </div>
    </li>
</ul>
<ul>
    <li class="form-group">
        <div class="label-wrap-wide">
            <?php echo $form->label("Anonymize IP Addresses", "Garden.Privacy.IPs"); ?>
            <div class="info">
                <p>
                    <?php echo t(
                        "Garden.Privacy.IPs.Description",
                        "User IP addresses are typically collected for automated ban rules, moderation and spam prevention purposes. Enabling IP Anonymization will anonymize all IP addresses tracked in the site for any purpose, and will reduce the effectiveness of these tools. Changes to this setting are not retroactive."
                    ); ?>
                </p>
                <p>
                    <?php echo t(
                        "PartialAnonymization.Description",
                        "<strong>Partial Anonymization</strong> anonymizes only the last octet of the IP address. For example 254.230.05.153 would become 254.230.05.0. This provides some anonymization while still allowing IP ban rules to function."
                    ); ?>
                </p>
                <p>
                    <?php echo t(
                        "FullAnonymization.Description",
                        "<strong>Full Anonymization</strong> replaces every single IP address with 0.0.0.0 effectively denying the application any access to work with IP addresses. IP ban rules will not work at all in this case."
                    ); ?>
                </p>
            </div>
        </div>
        <div class="input-wrap-right">
            <div class="textbox-suffix">
                <?php echo $form->dropDown("Garden.Privacy.IPs", [
                    "" => t("No Anonymization"),
                    "partial" => t("Partial Anonymization"),
                    "full" => t("Full Anonymization"),
                ]); ?>
            </div>
        </div>
    </li>
</ul>

<?php echo $form->close("Save"); ?>
