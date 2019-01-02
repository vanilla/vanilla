<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Fixtures\Authenticator;

use Garden\Schema\Schema;
use Garden\Web\RequestInterface;
use Vanilla\Authenticator\SSOAuthenticator;
use Vanilla\Models\AuthenticatorModel;
use Vanilla\Models\SSOData;

/**
 * Class MockSSOAuthenticator
 */
class MockSSOAuthenticator extends SSOAuthenticator {

    /**
     * MockSSOAuthenticator constructor.
     *
     * @param \Vanilla\Models\AuthenticatorModel $authenticatorModel
     *
     * @throws \Garden\Schema\ValidationException
     * @throws \Garden\Web\Exception\NotFoundException
     */
    public function __construct(AuthenticatorModel $authenticatorModel) {
        parent::__construct('MockSSO', $authenticatorModel);
    }

    /**
     * @inheritdoc
     */
    public static function getAuthenticatorSchema(): Schema {
        $schema = parent::getAuthenticatorSchema();

        $defaults = [
            'properties.name' => 'MockSSO',
            'properties.isActive' => true,
            'properties.signInUrl' => 'http://example.com/MockSSOSignInPage',
            'properties.sso.properties.canSignIn' => true,
            'properties.sso.properties.isTrusted' => true,
            'properties.sso.properties.canAutoLinkUser' => false,
            'properties.sso.properties.canLinkSession' => false,
        ];

        foreach ($defaults as $fieldPath => $defaultValue) {
            $field = $schema->getField($fieldPath);
            $field['default'] = $defaultValue;
            unset($field['x-instance-required']);
            $schema->setField($fieldPath, $field);
        }

        return $schema;
    }

    /**
     * @inheritdoc
     */
    protected function setAuthenticatorInfo(array $data) {
        $data['attributes'] = $data['attributes'] ?? [];
        $data['attributes']['SSOData'] = $data['attributes']['SSOData'] ?? [];
        $data['attributes']['SSOData']['uniqueID'] = $data['attributes']['SSOData']['uniqueID'] ?? uniqid('MockSSOUserID_');

        parent::setAuthenticatorInfo($data);

        $this->attributes['SSOData'] = SSOData::fromArray($this->attributes['SSOData'] ?? []);
    }

    /**
     * @inheritDoc
     */
    protected function sso(RequestInterface $request): SSOData {
        return $this->getData();
    }

    /**
     * @return \Vanilla\Models\SSOData
     */
    protected function getData() {
        return $this->attributes['SSOData'];
    }

    /**
     * @inheritDoc
     */
    protected static function getAuthenticatorTypeInfoImpl(): array {
        return [
            'ui' => [
                'photoUrl' => 'http://www.example.com/image.jpg',
                'backgroundColor' => '#ffffff',
                'foregroundColor' => '#000000',
            ],
        ];
    }

    /**
     * @inheritDoc
     */
    public static function isUnique(): bool {
        return true;
    }

    /**
     * @inheritDoc
     */
    protected function getAuthenticatorInfoImpl(): array {
        return [
            'ui' => [
                'buttonName' => 'Sign in with MockAuthenticator',
            ]
        ];
    }

    /**
     * @inheritdoc
     */
    public function setTrusted(bool $trusted) {
        return parent::setTrusted($trusted);
    }
}
