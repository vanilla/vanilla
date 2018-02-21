<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

class OAuth2AuthenticatorPlugin extends Gdn_Plugin {

    /**
     * Inject sign-in button into the sign in page.
     *
     * @param EntryController $sender.
     * @param EntryController $args.
     *
     * @return mixed|bool Return null if not configured
     */
    public function entryController_signIn_handler($sender, $args) {
        if (isset($sender->Data['Methods'])) {
            // Add the sign in button method to the controller.
            $method = [
                'Name' => 'oauth2',
                'SignInHtml' => $this->signInButton()
            ];

            $sender->Data['Methods'][] = $method;
        }
    }
    /**
     * Inject a sign-in icon into the ME menu.
     *
     * @param Gdn_Controller $sender.
     * @param Gdn_Controller $args.
     */
    public function base_beforeSignInButton_handler($sender, $args) {
        echo ' '.$this->signInButton('icon').' ';
    }


    public function signInButton($type = 'button') {
        $auth = new \Vanilla\Authenticator\OAuth2Authenticator('oauth2', new Gdn_AuthenticationProviderModel());

        $provider = $auth->providerData;
        $uri = val('AuthorizeUrl', $provider);
        $redirect_uri = '/authenticate/oauth2';
        $defaultParams = [
            'response_type' => 'code',
            'client_id' => val('AssociationKey', $provider),
            'redirect_uri' => url($redirect_uri, true),
            'scope' => val('AcceptedScope', $provider)
        ];

        $url =  $uri.'?'.http_build_query($defaultParams);


        $providerName = val('Name', $provider);
        $linkLabel = sprintf(t('Sign in with %s'), $providerName);
        $result = socialSignInButton($providerName, $url, $type, ['rel' => 'nofollow', 'class' => 'default', 'title' => $linkLabel]);
        return $result;
    }
}
