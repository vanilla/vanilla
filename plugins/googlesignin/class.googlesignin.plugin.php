<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */


/**
 * Class GoogleSignInPlugin
 *
 * Expose the functionality of the core class Gdn_OAuth2 to create SSO workflows.
 */

class GoogleSignInPlugin extends Gdn_OAuth2 {
    CONST AUTHORIZEURL = 'https://accounts.google.com/o/oauth2/v2/auth';
    CONST TOKENURL = 'https://oauth2.googleapis.com/token';
    CONST PROFILEURL = 'https://openidconnect.googleapis.com/v1/userinfo';
    CONST PROFILENAME = 'name';
    CONST ACCEPTEDSCOPE = 'email openid profile';
    /**
     * Set the key for saving OAuth settings in GDN_UserAuthenticationProvider
     */
    public function __construct() {
        $this->setProviderKey('googlesignin');
        $this->settingsView = 'plugins/settings/googlesignin';
    }

    /**
     *  Return all the information saved in provider table, add hardcoded values.
     *
     * @return array Stored provider data (secret, client_id, etc.).
     */
    public function provider() {
        if (!$this->provider) {
            $this->provider = Gdn_AuthenticationProviderModel::getProviderByKey($this->providerKey);

            // These URLs are added here instead of being stored in the DB in case they ever change, we will not have to update the DB.
            $this->provider['AuthorizeUrl'] = self::AUTHORIZEURL;
            $this->provider['TokenUrl'] = self::TOKENURL;
            $this->provider['ProfileUrl'] = self::PROFILEURL;

            // Scope
            $this->provider['AcceptedScope'] = self::ACCEPTEDSCOPE;

            // Translate claims coming back from Google.
            $this->provider['ProfileKeyName'] = self::PROFILENAME;
            $this->provider['AcceptedScope'] = 'email openid profile';

            $this->provider['ProfileKeyUniqueID'] = 'sub';
            $this->provider['ProfileKeyFullName'] = null;

            // provider Name is what puts the text on the button and determines the CSS class.
            $this->provider['Name'] = 'Google';
        }

        return $this->provider;
    }

    /**
     * Create a controller to deal with plugin settings in dashboard.
     *
     * @param Gdn_Controller $sender.
     * @param Gdn_Controller $args.
     */
    public function settingsEndpoint($sender, $args) {
        $sender->permission('Garden.Settings.Manage');
        $model = new Gdn_AuthenticationProviderModel();

        /* @var Gdn_Form $form */
        $form = new Gdn_Form();
        $form->setModel($model);
        $sender->Form = $form;

        if (!$form->authenticatedPostBack()) {
            $provider = $this->provider();
            $form->setData($provider);
        } else {

            $form->setFormValue('AuthenticationKey', $this->getProviderKey());

            $sender->Form->validateRule('AssociationKey', 'ValidateRequired', 'You must provide a unique AccountID.');
            $sender->Form->validateRule('AssociationSecret', 'ValidateRequired', 'You must provide a Secret');

            // To satisfy the AuthenticationProviderModel, create a BaseUrl.
            $baseUrlParts = parse_url($form->getValue('AuthorizeUrl'));
            $baseUrl = (val('scheme', $baseUrlParts) && val('host', $baseUrlParts)) ? val('scheme', $baseUrlParts).'://'.val('host', $baseUrlParts) : null;
            if ($baseUrl) {
                $form->setFormValue('BaseUrl', $baseUrl);
                $form->setFormValue('SignInUrl', $baseUrl); // kludge for default provider
            }
            if ($form->save()) {
                $sender->informMessage(t('Saved'));
            }
        }

        // Set up the form.
        $formFields = [
            'AssociationKey' =>  ['LabelCode' => 'Client ID', 'Description' => 'Unique ID of the authentication application.'],
            'AssociationSecret' =>  ['LabelCode' => 'Secret', 'Description' => 'Secret provided by the authentication provider.'],
        ];

        $formFields['IsDefault'] = ['LabelCode' => 'Make this connection your default signin method.', 'Control' => 'checkbox'];

        $sender->setData([
            'formData' => $formFields,
            'form' => $sender->Form
        ]);

        $sender->setHighlightRoute();
        if (!$sender->data('Title')) {
            $sender->setData('Title', sprintf(t('%s Settings'), 'Google Sign-In'));
        }

        $view = ($this->settingsView) ? $this->settingsView : 'plugins/googlesignin';

        // Create and send the possible redirect URLs that will be required by the authenticating server and display them in the dashboard.
        // Use Gdn::Request instead of convience function so that we can return http and https.
        $redirectUrls = Gdn::request()->url('/entry/'. $this->getProviderKey(), true, true);
        $sender->setData('Instructions', sprintf(t('oauth2Instructions'), $redirectUrls));

        $sender->render('settings', '', $view);
    }

}
