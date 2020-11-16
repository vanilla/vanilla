<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Core;

use PHPUnit\Framework\TestCase;
use Vanilla\Models\TrustedDomainModel;
use VanillaTests\EventSpyTestTrait;
use VanillaTests\SiteTestTrait;

/**
 * Tests for trusted domains.
 */
class TrustedDomainsTest extends TestCase {

    use SiteTestTrait;
    use EventSpyTestTrait;

    /** @var \Gdn_AuthenticationProviderModel */
    private $authProviderModel;

    /**
     * Setup.
     */
    public function setUp(): void {
        $this->authProviderModel = self::container()->get(\Gdn_AuthenticationProviderModel::class);
        \Gdn::sql()->truncate('UserAuthenticationProvider');
    }

    /**
     * @return TrustedDomainModel
     */
    private function trustedDomainModel(): TrustedDomainModel {
        return self::container()->get(TrustedDomainModel::class);
    }

    /**
     * Test trusted domains.
     */
    public function testConfigTrustedDomains() {
        $this->runWithConfig([
            'Garden.TrustedDomains' => "example.com\n\n\n\nother.com",
            'Garden.Installed' => false,
        ], function () {
            $this->assertEquals(['vanilla.test', "example.com", "other.com"], $this->trustedDomainModel()->getAll());
        });

        $this->runWithConfig([
            'Garden.TrustedDomains' => ["example.com"]
        ], function () {
            $this->assertEquals(['vanilla.test', "example.com"], $this->trustedDomainModel()->getAll());
        });
    }

    /**
     * Test trusted domains from auth providers.
     */
    public function testAuthProviderDomains() {
        $this->authProviderModel->save([
            'AuthenticationKey' => 'mycustom',
            'AuthenticationSchemeAlias' => 'mycustom',
            'AssosciationSecret' => 'asdf',
            'RegisterUrl' => 'http://registerdomain.com/auth',
        ]);

        $this->assertEquals(['vanilla.test', "registerdomain.com"], $this->trustedDomainModel()->getAll());
    }

    /**
     * Test event firing.
     */
    public function testEventDomains() {
        $this->getEventManager()->bindClass($this);
        $this->assertEquals(['vanilla.test', "eventdomain.com"], $this->trustedDomainModel()->getAll());
    }

    /**
     * Event handler.
     *
     * @param null $sender
     * @param array $args
     */
    public function entryController_beforeTargetReturn_handler($sender, array $args) {
        $args['TrustedDomains'][] = 'eventdomain.com';
    }
}
