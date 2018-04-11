<?php
/**
 * Created by PhpStorm.
 * User: patrick
 * Date: 2018-04-11
 * Time: 11:32 AM
 */

class OAuth2controller extends PluginController {


    /**
     * OAuth2Controller constructor.
     */
    public function __construct() {
        parent::__construct();
    }


    /**
     * Delete endpoint.
     *
     * @param $authenticationKey OAuth2 Authentication key
     */
    public function delete($authenticationKey) {
        $this->permission('Garden.Settings.Manage');

        if (!$authenticationKey) {
            return;
        }

        if ($this->Form->authenticatedPostBack()) {
            $model = new Gdn_AuthenticationProviderModel();
            $model->delete([
                'AuthenticationSchemeAlias' => 'oauth2',
                'AuthenticationKey' => $authenticationKey,
            ]);
        }

        $this->setRedirectTo('/settings/oauth2');
        $this->render('blank', 'utility', 'dashboard');
    }


    /**
     * Set the state of a particular OAuth2 connection.
     *
     * @param string $authenticationKey OAuth2 Authentication key
     * @param string $state
     * @throws Exception
     */
    public function state($authenticationKey, $state) {
        $this->permission('Garden.Settings.Manage');
        if (!Gdn::request()->isAuthenticatedPostBack(true)) {
            throw new Exception('Requires POST', 405);
        }

        if (!$authenticationKey || !in_array($state, ['active', 'disabled'])) {
            return;
        }

        $model = new Gdn_AuthenticationProviderModel();

        $updatedFields = [
            'Active' => $state === 'active' ? 1 : 0
        ];
        // Safeguard against stupid people
        if (!$updatedFields['Active']) {
            $updatedFields['IsDefault'] = 0;
        }

        $model->update(
            $updatedFields,
            [
                'AuthenticationSchemeAlias' => 'oauth2',
                'AuthenticationKey' => $authenticationKey,
            ]
        );

        if ($state === 'active' ? 1 : 0) {
            $state = 'on';
            $url = '/oauth2/state/'.$authenticationKey.'/disabled';
        } else {
            $state = 'off';
            $url = '/oauth2/state/'.$authenticationKey.'/active';
        }
        $newToggle = wrap(
            anchor('<div class="toggle-well"></div><div class="toggle-slider"></div>', $url, 'Hijack'),
            'span',
            ['class' => "toggle-wrap toggle-wrap-$state"]
        );

        $this->jsonTarget("#provider_$authenticationKey .toggle-container", $newToggle);

        $this->render('blank', 'utility', 'dashboard');
    }
}
