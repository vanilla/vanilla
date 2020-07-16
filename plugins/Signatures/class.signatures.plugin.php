<?php if (!defined('APPLICATION')) { exit(); }

/**
 * Signatures Plugin
 *
 * This plugin allows users to maintain a 'Signature' which is automatically
 * appended to all discussions and comments they make.
 *
 * Changes:
 *  1.0     Initial release
 *  1.4     Add SimpleAPI hooks
 *  1.4.1   Allow self-API access
 *  1.5     Improve "Hide Images"
 *  1.5.1   Improve permission checking granularity
 *  1.5.3-5 Disallow images plugin-wide from dashboard
 *  1.6     Add signature constraints and enhance mobile capacity
 *  1.6.1   The spacening.
 *
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2003 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Addons
 */

use \Vanilla\Formatting\FormatService;

class SignaturesPlugin extends Gdn_Plugin {

    const Unlimited = 'Unlimited';

    const None = 'None';

    /** @var bool */
    public $Disabled = false;

    /** @var FormatService  */
    private $formatService;

    /** @var array List of config settings can be overridden by sessions in other plugins */
    private $overriddenConfigSettings = ['MaxNumberImages', 'MaxLength'];

    /**
     * SignaturesPlugin constructor.
     * @param FormatService $formatService
     */
    public function __construct(FormatService $formatService) {
        parent::__construct();
        $this->formatService = $formatService;
    }

    /**
     * Add mapper methods
     *
     * @param SimpleApiPlugin $sender
     */
    public function simpleApiPlugin_mapper_handler($sender) {
        switch ($sender->Mapper->Version) {
            case '1.0':
                $sender->Mapper->addMap([
                    'signature/get' => 'profile/signature/modify',
                    'signature/set' => 'profile/signature/modify',
                ], null, [
                    'signature/get' => ['Signature'],
                    'signature/set' => ['Success'],
                ]);
                break;
        }
    }

    /**
     * Add "Signature Settings" to profile edit mode side menu.
     *
     * @param $sender
     */
    public function profileController_afterAddSideMenu_handler($sender) {
        if (!checkPermission('Garden.SignIn.Allow')) {
            return;
        }

        $sideMenu = $sender->EventArguments['SideMenu'];
        $viewingUserID = Gdn::session()->UserID;

        if ($sender->User->UserID == $viewingUserID) {
            $sideMenu->addLink('Options', sprite('SpSignatures').' '.t('Signature Settings'), '/profile/signature', false, ['class' => 'Popup']);
        } else {
            $sideMenu->addLink('Options', sprite('SpSignatures').' '.t('Signature Settings'), userUrl($sender->User, '', 'signature'), ['Garden.Users.Edit', 'Moderation.Signatures.Edit'], ['class' => 'Popup']);
        }
    }

    /**
     * Add "Signature Settings" to Profile Edit button group.
     * Only do this if they cannot edit profiles because otherwise they can't navigate there.
     *
     * @param $sender
     */
    public function profileController_beforeProfileOptions_handler($sender, $args) {
        $canEditProfiles = checkPermission('Garden.Users.Edit') || checkPermission('Moderation.Profiles.Edit');
        if (checkPermission('Moderation.Signatures.Edit') && !$canEditProfiles) {
            $args['ProfileOptions'][] = ['Text' => sprite('SpSignatures').' '.t('Signature Settings'), 'Url' => userUrl($sender->User, '', 'signature')];
        }
    }

    /**
     * Profile settings
     *
     * @param ProfileController $sender
     */
    public function profileController_signature_create($sender) {
        $sender->permission('Garden.SignIn.Allow');
        $sender->title(t('Signature Settings'));

        $this->dispatch($sender);
    }

    /**
     *
     *
     * @param $sender
     */
    public function controller_index($sender) {
        $sender->permission([
            'Garden.Profiles.Edit'
        ]);

        $args = $sender->RequestArgs;
        if (sizeof($args) < 2) {
            $args = array_merge($args, [0, 0]);
        } elseif (sizeof($args) > 2) {
            $args = array_slice($args, 0, 2);
        }

        list($userReference, $username) = $args;

        $canEditSignatures = checkPermission('Plugins.Signatures.Edit');


        $sender->getUserInfo($userReference, $username);
        $userPrefs = dbdecode($sender->User->Preferences);
        if (!is_array($userPrefs)) {
            $userPrefs = [];
        }

        $validation = new Gdn_Validation();
        $configurationModel = new Gdn_ConfigurationModel($validation);
        $configArray = [
            'Plugin.Signatures.Sig' => null,
            'Plugin.Signatures.HideAll' => null,
            'Plugin.Signatures.HideImages' => null,
            'Plugin.Signatures.HideMobile' => null,
            'Plugin.Signatures.Format' => null
        ];
        $sigUserID = $viewingUserID = Gdn::session()->UserID;

        if ($sender->User->UserID != $viewingUserID) {
            $sender->permission(['Garden.Users.Edit', 'Moderation.Signatures.Edit'], false);
            $sigUserID = $sender->User->UserID;
            $canEditSignatures = true;
        }

        $sender->setData('CanEdit', $canEditSignatures);
        $sender->setData('Plugin-Signatures-ForceEditing', ($sigUserID == Gdn::session()->UserID) ? false : $sender->User->Name);
        $userMeta = $this->getUserMeta($sigUserID, '%');

        if ($sender->Form->authenticatedPostBack() === false && is_array($userMeta)) {
            $configArray = array_merge($configArray, $userMeta);
        }

        $configurationModel->setField($configArray);

        // Set the model on the form.
        $sender->Form->setModel($configurationModel);

        $data = $configurationModel->Data;
        $sender->setData('Signature', $data);

        $this->setSignatureRules($sender);

        // Form submission handling.
        if ($sender->Form->authenticatedPostBack(true)) {
            $values = $sender->Form->formValues();

            if ($canEditSignatures) {
                $values['Plugin.Signatures.Sig'] = val('Body', $values, null);
                $values['Plugin.Signatures.Format'] = val('Format', $values, null);
            }

            $frmValues = array_intersect_key($values, $configArray);

            if (sizeof($frmValues)) {

                if (!getValue($this->makeMetaKey('Sig'), $frmValues)) {
                    // Delete the signature.
                    $frmValues[$this->makeMetaKey('Sig')] = null;
                    $frmValues[$this->makeMetaKey('Format')] = null;
                }

                $this->crossCheckSignature($values, $sender);

                if ($sender->Form->errorCount() == 0) {
                    foreach ($frmValues as $userMetaKey => $userMetaValue) {
                        $key = $this->trimMetaKey($userMetaKey);

                        switch ($key) {
                            case 'Format':
                                if (strcasecmp($userMetaValue, 'Raw') == 0) {
                                    $userMetaValue = null;
                                } // don't allow raw signatures.
                                break;
                        }

                        $this->setUserMeta($sigUserID, $key, $userMetaValue);
                    }
                    $sender->informMessage(t("Your changes have been saved."));
                }
            }
        } else {
            // Load form data.
            $data['Body'] = val('Plugin.Signatures.Sig', $data);
            $data['Format'] = val('Plugin.Signatures.Format', $data) ?: Gdn_Format::defaultFormat();

            // Apply the config settings to the form.
            $sender->Form->setData($data);
        }

        $sender->render('signature', '', 'plugins/Signatures');
    }

    /**
     * Checks signature against constraints set in config settings,
     * and executes the external ValidateSignature function, if it exists.
     *
     * @param $values Signature settings form values
     * @param $sender Controller
     */
    public function crossCheckSignature($values, &$sender) {
        $this->checkSignatureLength($values, $sender);
        $this->checkNumberOfImages($values, $sender);

        // Validate the signature.
        if (function_exists('ValidateSignature')) {
            $sig = trim(val('Plugin.Signatures.Sig', $values));
            if (validateRequired($sig) && !validateSignature($sig, val('Plugin.Signatures.Format', $values))) {
                $sender->Form->addError('Signature invalid.');
            }
        }
    }

    /**
     * Determine if signature exceeds configured character limit.
     *
     * @param array $fields Signature settings form values
     * @param ProfileController $sender
     */
    public function checkSignatureLength($fields, &$sender) {
        if (!property_exists($sender, 'Form') || !($sender->Form instanceof Gdn_Form)) {
            return;
        }

        $maxLength = self::getMaximumTextLength();
        if ($maxLength !== null && $maxLength > 0) {
            $maxLength = intval($maxLength);
            $format = isset($fields['Format']) ? $fields['Format'] : Gdn_Format::defaultFormat();
            $body = val('Plugin.Signatures.Sig', $fields, '');
            $plainTextLength = $this->formatService->getPlainTextLength($body, $format);

            // Validate the amount of text
            $difference = $plainTextLength - $maxLength;
            if ($difference > 0) {
                $sender->Form->addError(sprintf(
                    t('ValidateLength'),
                    t('Signature'),
                    $difference
                ));
            }
        }
    }

    /**
     * Checks number of images in signature against Plugins.Signatures.MaxNumberImages
     *
     * @param $values Signature settings form values
     * @param $sender Controller
     */
    public function checkNumberOfImages($values, &$sender) {
        $maxImages = self::getMaximumNumberOfImages();
        if ($maxImages !== self::Unlimited) {
            $sig = Gdn_Format::to(val('Plugin.Signatures.Sig', $values), val('Plugin.Signatures.Format', $values, c('Garden.InputFormatter')));
            $numMatches = preg_match_all('/<img/i', $sig);

            if($maxImages === self::None && $numMatches > 0) {
                $sender->Form->addError('Images not allowed');
            } elseif (is_int($maxImages) && $numMatches > $maxImages) {
                $sender->Form->addError('@'.formatString('You are only allowed {maxImages,plural,%s image,%s images}.',
                        ['maxImages' => $maxImages]));

            }
        }
    }

    /**
     *
     *
     * @param $sender
     */
    public function setSignatureRules($sender) {
        $rules = [];
        $rulesParams = [];
        $imagesAllowed = true;
        $maxTextLength = self::getMaximumTextLength();
        $maxImageHeight = self::getMaximumImageHeight();
        $maxNumberImages = self::getMaximumNumberOfImages();

        if ($maxNumberImages !== self::Unlimited) {
            if (is_numeric($maxNumberImages) && $maxNumberImages > 0) { //'None' or any other non positive ints
                $rulesParams['maxImages'] = $maxNumberImages;
                $rules[] = formatString(t('Use up to {maxImages,plural,%s image, %s images}.'), $rulesParams);
            } else {
                $rules[] = t('Images not allowed');
                $imagesAllowed = false;
            }
        }

        if ($imagesAllowed && $maxImageHeight > 0) {
            $rulesParams['maxImageHeight'] = $maxImageHeight;
            $rules[] = formatString(t('Images will be scaled to a maximum height of {maxImageHeight}px.'), $rulesParams);
        }

        if ( $maxTextLength > 0) {
            $rulesParams['maxLength'] = $maxTextLength;
            $rules[] = formatString(t('Signatures can be up to {maxLength} characters long.'), $rulesParams);
        }

        $sender->setData('SignatureRules', implode(' ', $rules));
    }


    /**
     * Strips all line breaks from text
     *
     * @param string $text
     * @param string $delimiter
     */
    public function stripLineBreaks(&$text, $delimiter = ' ') {
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $lines = explode("\n", $text);
        $new_lines = [];
        foreach ($lines as $i => $line) {
            $line = trim($line);
            if (!empty($line)) {
                $new_lines[] = $line;
            }
        }
        $text = implode($new_lines, $delimiter);
    }

    /**
     *
     */
    public function stripFormatting() {

    }

    /**
     * Modify a signature.
     *
     * This method is also used for the GET endpoint.
     *
     * @param ProfileController $sender
     */
    public function controller_Modify($sender) {
        $sender->deliveryMethod(DELIVERY_METHOD_JSON);
        $sender->deliveryType(DELIVERY_TYPE_DATA);

        $userID = Gdn::request()->get('UserID');
        if ($userID != Gdn::session()->UserID) {
            $sender->permission(['Garden.Users.Edit', 'Moderation.Signatures.Edit'], false);
        } else {
            $sender->permission(['Garden.Profiles.Edit', 'Plugins.Signatures.Edit']);
        }
        $user = Gdn::userModel()->getID($userID);
        if (!$user) {
            throw new Exception("No such user '{$userID}'", 404);
        }

        $translation = [
            'Plugin.Signatures.Sig' => 'Body',
            'Plugin.Signatures.Format' => 'Format',
            'Plugin.Signatures.HideAll' => 'HideAll',
            'Plugin.Signatures.HideImages' => 'HideImages',
            'Plugin.Signatures.HideMobile' => 'HideMobile'
        ];

        $userMeta = $this->getUserMeta($userID, '%');
        $sigData = [];
        foreach ($translation as $translationField => $translationShortcut) {
            $sigData[$translationShortcut] = val($translationField, $userMeta, null);
        }

        $sender->setData('Signature', $sigData);

        if ($sender->Request->isAuthenticatedPostBack(true)) {
            $sender->setData('Success', false);

            // Validate the signature.
            if (function_exists('ValidateSignature')) {
                $sig = $sender->Form->getFormValue('Body');
                $format = $sender->Form->getFormValue('Format');
                if (validateRequired($sig) && !validateSignature($sig, $format)) {
                    $sender->Form->addError('Signature invalid.');
                }
            }

            if ($sender->Form->errorCount() == 0) {
                foreach ($translation as $translationField => $translationShortcut) {
                    $userMetaValue = $sender->Form->getValue($translationShortcut, null);
                    if (is_null($userMetaValue)) {
                        continue;
                    }

                    if ($translationShortcut == 'Body' && empty($userMetaValue)) {
                        $userMetaValue = null;
                    }

                    $key = $this->trimMetaKey($translationField);

                    switch ($key) {
                        case 'Format':
                            if (strcasecmp($userMetaValue, 'Raw') == 0) {
                                $userMetaValue = null;
                            } // don't allow raw signatures.
                            break;
                    }

                    if ($sender->Form->errorCount() == 0) {
                        $this->setUserMeta($userID, $key, $userMetaValue);
                    }
                }
                $sender->setData('Success', true);
            }
        }

        $sender->render();
    }

    /**
     *
     *
     * @param null $sigKey
     * @param null $default
     *
     * @return array|bool|mixed|null
     */
    protected function userPreferences($sigKey = null, $default = null) {
        static $userSigData = null;
        if (is_null($userSigData)) {
            $userSigData = $this->getUserMeta(Gdn::session()->UserID, '%');

        }

        if (!is_null($sigKey)) {
            return val($sigKey, $userSigData, $default);
        }

        return $userSigData;
    }

    /**
     *
     *
     * @param $sender
     * @param null $requestUserID
     * @param null $default
     *
     * @return array|bool|mixed|null
     */
    protected function signatures($sender, $requestUserID = null, $default = null) {
        static $signatures = null;

        if (is_null($signatures)) {
            $signatures = [];

            // Short circuit if not needed.
            if ($this->hide()) {
                return $signatures;
            }

            $discussion = $sender->data('Discussion');
            $comments = $sender->data('Comments');
            $userIDList = [];

            if ($discussion) {
                $userIDList[getValue('InsertUserID', $discussion)] = 1;
            }

            if ($comments && $comments->numRows()) {
                $comments->dataSeek(-1);
                while ($comment = $comments->nextRow()) {
                    $userIDList[getValue('InsertUserID', $comment)] = 1;
                }
            }

            if (sizeof($userIDList)) {
                $dataSignatures = $this->getUserMeta(array_keys($userIDList), 'Sig');
                $formats = (array)$this->getUserMeta(array_keys($userIDList), 'Format');

                if (is_array($dataSignatures)) {
                    foreach ($dataSignatures as $userID => $userSig) {
                        $sig = val($this->makeMetaKey('Sig'), $userSig);
                        if (isset($formats[$userID])) {
                            $format = val($this->makeMetaKey('Format'), $formats[$userID], c('Garden.InputFormatter'));
                        } else {
                            $format = c('Garden.InputFormatter');
                        }

                        $signatures[$userID] = [$sig, $format];
                    }
                }
            }

        }

        if (!is_null($requestUserID)) {
            return val($requestUserID, $signatures, $default);
        }

        return $signatures;
    }

    /**
     *
     *
     * @param $sender
     * @deprecated since 2.1
     */
    public function base_afterCommentBody_handler($sender) {
        if ($this->Disabled) {
            return;
        }

        $this->drawSignature($sender);
    }

    /**
     * Add a custom signature style tag to enforce image height.
     *
     * @param Gdn_Control $sender
     * @param array $args
     */
    public function base_render_before($sender, $args) {

        $maxImageHeight = self::getMaximumImageHeight();

        if ($maxImageHeight > 0) {

            $style = <<<EOT
.Signature img, .UserSignature img {
   max-height: {$maxImageHeight}px !important;
}
EOT;

            $sender->Head->addTag('style', ['_sort' => 100], $style);
        }
    }

    /**
     * Load signatures.
     *
     * @param $sender
     */
    public function discussionController_beforeDiscussionRender_handler($sender) {
        $this->signatures($sender);
    }

    /**
     *
     *
     * @param $sender
     * @since 2.1
     */
    public function discussionController_afterDiscussionBody_handler($sender) {
        if ($this->Disabled) {
            return;
        }
        $this->drawSignature($sender);
    }

    /**
     *
     *
     * @param $sender
     */
    protected function drawSignature($sender) {
        if ($this->hide()) {
            return;
        }

        if (isset($sender->EventArguments['Discussion'])) {
            $data = $sender->EventArguments['Discussion'];
        }

        if (isset($sender->EventArguments['Comment'])) {
            $data = $sender->EventArguments['Comment'];
        }

        $sourceUserID = val('InsertUserID', $data);
        $user = Gdn::userModel()->getID($sourceUserID, DATASET_TYPE_ARRAY);
        if (!empty($user['HideSignature']) || !empty($user['Deleted']) || !empty($user['Banned'])) {
            return;
        }

        $signature = $this->signatures($sender, $sourceUserID);

        if (is_array($signature)) {
            list($signature, $sigFormat) = $signature;
        } else {
            $sigFormat = c('Garden.InputFormatter');
        }

        if (!$sigFormat) {
            $sigFormat = c('Garden.InputFormatter');
        }

        $this->EventArguments = [
            'UserID' => $sourceUserID,
            'Signature' => &$signature
        ];
        $this->fireEvent('BeforeDrawSignature');

        $sigClasses = '';
        if (!is_null($signature)) {
            $hideImages = $this->userPreferences('Plugin.Signatures.HideImages', false);

            if ($hideImages) {
                $sigClasses .= 'HideImages ';
            }

            // Don't show empty sigs
            if ($signature == '') {
                return;
            }

            $allowEmbeds = self::getAllowEmbeds();

            // If embeds were disabled from the dashboard, temporarily set the
            // universal config to make sure no URLs are turned into embeds.
            if (!$allowEmbeds) {
                $originalEnableUrlEmbeds = c('Garden.Format.DisableUrlEmbeds', false);
                saveToConfig([
                    'Garden.Format.DisableUrlEmbeds' => true
                ], null, [
                    'Save' => false
                ]);
            }

            $userSignature = Gdn_Format::to($signature, $sigFormat);

            // Restore original config.
            if (!$allowEmbeds) {
                saveToConfig([
                    'Garden.Format.DisableUrlEmbeds' => $originalEnableUrlEmbeds
                ], null, [
                    'Save' => false
                ]);
            }

            $this->EventArguments = [
                'UserID' => $sourceUserID,
                'String' => &$userSignature
            ];

            $this->fireEvent('FilterContent');

            if ($userSignature) {
                echo "<div class=\"Signature UserSignature userContent {$sigClasses}\">{$userSignature}</div>";
            }
        }
    }

    /**
     *
     *
     * @return bool
     */
    protected function hide() {
        if ($this->Disabled) {
            return true;
        }

        if (!Gdn::session()->isValid() && self::getHideGuest()) {
            return true;
        }

        if (strcasecmp(Gdn::controller()->RequestMethod, 'embed') == 0 && self::getHideEmbed()) {
            return true;
        }

        if ($this->userPreferences('Plugin.Signatures.HideAll', false)) {
            return true;
        }

        if (isMobile() && (self::getHideMobile() || $this->userPreferences('Plugin.Signatures.HideMobile', false))) {
            return true;
        }

        return false;
    }

    /**
     *
     *
     * @param $str
     * @param $tags
     * @param bool $stripContent
     *
     * @return mixed
     */
    protected function _stripOnly($str, $tags, $stripContent = false) {
        $content = '';
        if (!is_array($tags)) {
            $tags = (strpos($str, '>') !== false ? explode('>', str_replace('<', '', $tags)) : [$tags]);
            if (end($tags) == '') {
                array_pop($tags);
            }
        }
        foreach ($tags as $tag) {
            if ($stripContent) {
                $content = '(.+</'.$tag.'[^>]*>|)';
            }
            $str = preg_replace('#</?'.$tag.'[^>]*>'.$content.'#is', '', $str);
        }
        return $str;
    }

    /**
     * Run on utility/update.
     */
    public function structure() {

        // Update old config settings for backwards compatibility.
        if (c('Plugins.Signatures.Default.MaxNumberImages') || c('Plugins.Signatures.MaxNumberImages')) {
            saveToConfig('Signatures.Images.MaxNumber', c('Plugins.Signatures.Default.MaxNumberImages', c('Plugins.Signatures.MaxNumberImages')));
        }
        if (c('Plugins.Signatures.MaxImageHeight')) {
            saveToConfig('Signatures.Images.MaxHeight', c('Plugins.Signatures.MaxImageHeight'));
        }
        if (c('Plugins.Signatures.Default.MaxLength') || c('Plugins.Signatures.MaxLength')) {
            saveToConfig('Signatures.Text.MaxLength', c('Plugins.Signatures.Default.MaxLength', c('Plugins.Signatures.MaxLength')));
        }
        if (c('Plugins.Signatures.HideGuest')) {
            saveToConfig('Signatures.Hide.Guest', c('Plugins.Signatures.HideGuest'));
        }
        if (c('Plugins.Signatures.HideEmbed')) {
            saveToConfig('Signatures.Hide.Embed', c('Plugins.Signatures.HideEmbed', true));
        }
        if (c('Plugins.Signatures.HideMobile')) {
            saveToConfig('Signatures.Hide.Mobile', c('Plugins.Signatures.HideMobile', true));
        }
        if (c('Plugins.Signatures.AllowEmbeds')) {
            saveToConfig('Signatures.Allow.Embeds', c('Plugins.Signatures.AllowEmbeds', true));
        }
        removeFromConfig([
            'Plugins.Signatures.Default.MaxNumberImages',
            'Plugins.Signatures.MaxNumberImages',
            'Plugins.Signatures.MaxImageHeight',
            'Plugins.Signatures.Default.MaxLength',
            'Plugins.Signatures.MaxLength',
            'Plugins.Signatures.HideGuest',
            'Plugins.Signatures.HideEmbed',
            'Plugins.Signatures.HideMobile',
            'Plugins.Signatures.AllowEmbeds',
        ]);
    }

    /**
     *
     *
     * @param $sender
     */
    public function assetModel_styleCss_handler($sender) {
        $sender->addCssFile('signature.css', 'plugins/Signatures');
    }

    /**
     *
     *
     * @param $sender
     */
    public function settingsController_signatures_create($sender) {
        $sender->permission('Garden.Settings.Manage');

        $maxNumberImages = self::getMaximumNumberOfImages();
        $maxImageHeight = self::getMaximumImageHeight();
        $maxTextLength = self::getMaximumTextLength();
        $hideGuest = self::getHideGuest();
        $hideEmbed = self::getHideEmbed();
        $hideMobile = self::getHideMobile();
        $allowEmbeds = self::getAllowEmbeds();

        $conf = new ConfigurationModule($sender);
        $conf->initialize([
            'Signatures.Images.MaxNumber' => [
                'Control' => 'Dropdown',
                'LabelCode' => '@'.sprintf(t('Max number of %s'), t('images')),
                'Items' => [
                    'Unlimited' => t('Unlimited'),
                    'None' => t('None'),
                    1 => 1,
                    2 => 2,
                    3 => 3,
                    4 => 4,
                    5 => 5
                ],
                'Default' => $maxNumberImages
            ],
            'Signatures.Images.MaxHeight' => [
                'Control' => 'TextBox',
                'Description' => 'Only enter number, no "px" needed.',
                'LabelCode' => '@'.sprintf(t('Max height of %s'), t('images'))." ".t('in pixels'),
                'Options' => [
                    'class' => 'InputBox SmallInput',
                    'type' => 'number',
                    'min' => '0'
                ],
                'Default' => $maxImageHeight
            ],
            'Signatures.Text.MaxLength' => [
                'Control' => 'TextBox',
                'Type' => 'int',
                'Description' => 'Leave blank for no limit.',
                'LabelCode' => '@'.sprintf(t('Max %s length'), t('signature')),
                'Options' => [
                    'class' => 'InputBox SmallInput',
                    'type' => 'number',
                    'min' => '1'
                ],
                'Default' => $maxTextLength
            ],
            'Signatures.Hide.Guest' => [
                'Control' => 'CheckBox',
                'LabelCode' => 'Hide signatures for guests',
                'Default' => $hideGuest
            ],
            'Signatures.Hide.Embed' => [
                'Control' => 'CheckBox',
                'LabelCode' => 'Hide signatures on embedded comments',
                'Default' => $hideEmbed
            ],
            'Signatures.Hide.Mobile' => [
                'Control' => 'CheckBox',
                'LabelCode' => 'Hide signatures on mobile',
                'Default' => $hideMobile
            ],
            'Signatures.Allow.Embeds' => [
                'Control' => 'CheckBox',
                'LabelCode' => 'Allow embedded content',
                'Default' => $allowEmbeds
            ],
        ]);

        $this->setConfigSettingsToDefault('Plugins.Signatures', $this->overriddenConfigSettings);

        $sender->addSideMenu();
        $sender->setData('Title', sprintf(t('%s Settings'), t('Signature')));
        $sender->ConfigurationModule = $conf;
        $conf->renderAll();
    }

    /**
     * Sets config settings to the default settings.
     *
     * Why do we need this? (i.e., Mantra for the function)
     * We retrieve the signature restraints from the config settings.
     * These are sometimes overridden by plugins (i.e., Ranks)
     * If we load the dashboard signature settings form from the config file,
     * we will get whatever session config settings are present, not
     * the default. As such, we've created default config variables that
     * populate the form, but we've got to transfer them over to the
     * config settings in use.
     *
     * @param string $basename
     * @param array $settings
     */
    public function setConfigSettingsToDefault($basename, $settings) {
        foreach ($settings as $setting) {
            saveToConfig($basename.'.'.$setting, c($basename.'.Default.'.$setting));
        }
    }

    /**
     * Make sure we get valid integer from form. Allow "null" as a valid value.
     *
     * @param mixed $num
     * @param null $fallback
     * @return mixed
     */
    private function getPositiveIntOrFallback($num, $fallback = null) {
        $num = (int)$num;
        if (filter_var($num, FILTER_VALIDATE_INT) && $num > 0) {
            return $num;
        } else {
            return $fallback;
        }
    }

    /**
     * Get allowed number of images.
     *
     * @return mixed 'Unlimited', 'None', or positive integer.
     */
    private function getMaximumNumberOfImages() {
        $val = c('Signatures.Images.MaxNumber', 0);

        if (is_bool($val) && $val == false) {
            $val = 'None';
        }

        if ($val != self::Unlimited && $val != self::None) {
            $max = self::getPositiveIntOrFallback($val, 0);
        } else {
            $max = $val;
        }

        return $max;
    }

    /**
     * Make sure we get a valid value for Image Height. fall back is 0 if
     * the config value is not a positive int.
     *
     * @return mixed
     */
    private function getMaximumImageHeight() {
        return self::getPositiveIntOrFallback(c('Signatures.Images.MaxHeight', 0));
    }

    /**
     * Make sure we get a valid value for text length. fall back is 0 if
     * the config value is not a positive int.
     *
     * @return mixed
     */
    private function getMaximumTextLength() {
        return self::getPositiveIntOrFallback(c('Signatures.Text.MaxLength', 0));
    }

    /**
     * @return bool
     */
    private function getHideGuest() {
        return c('Signatures.Hide.Guest', false);
    }

    /**
     * @return bool
     */
    private function getHideEmbed() {
        return c('Signatures.Hide.Embed', true);
    }

    /**
     * @return bool
     */
    private function getHideMobile() {
        return c('Signatures.Hide.Mobile', true);
    }

    /**
     * @return bool
     */
    private function getAllowEmbeds() {
        return c('Signatures.Allow.Embeds', true);
    }
}
