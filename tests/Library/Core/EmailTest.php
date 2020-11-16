<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Core;

use Garden\Web\RequestInterface;
use Gdn_Email;
use Vanilla\Contracts\ConfigurationInterface;
use VanillaTests\BootstrapTestCase;

/**
 * Tests for the Gdn_Email class
 */
class EmailTest extends BootstrapTestCase {
    /** @var ConfigurationInterface */
    private $config;

    /** @var string */
    private $supportAddress = "";

    /** @var string */
    private $supportName = "";

    /**
     * Set the email support address config on both the config instance and locally.
     *
     * @param string $supportAddress
     */
    private function setSupportAddress(string $supportAddress): void {
        $this->supportAddress = $supportAddress;
        $this->config->saveToConfig("Garden.Email.SupportAddress", $supportAddress, false);
    }

    /**
     * Set the email support name config on both the config instance and locally.
     *
     * @param string $supportName
     */
    private function setSupportName(string $supportName): void {
        $this->supportName = $supportName;
        $this->config->saveToConfig("Garden.Email.SupportName", $supportName, false);
    }

    /**
     * @inheritDoc
     */
    public function setUp(): void {
        parent::setUp();

        $this->container()->call(function (ConfigurationInterface $config) {
            $this->config = $config;
            $config->saveToConfig("Garden.Email.EnvelopeAddress", "", false);
        });

        $this->setSupportName(__CLASS__);
        $this->setSupportAddress(rand() . "@example.com");
    }

    /**
     * Verify no configured support address falls back to a standard no-reply address.
     */
    public function testGetDefaultFromAddressNoReply(): void {
        $this->setSupportAddress("");
        $obj = new Gdn_Email();
        $result = $obj->getDefaultFromAddress();
        $this->assertSame($obj->getNoReplyAddress(), $result);
    }

    /**
     * Verify the support address is used, when no envelope address is configured.
     */
    public function testGetDefaultSenderAddressSupport(): void {
        $obj = new Gdn_Email();
        $result = $obj->getDefaultSenderAddress();
        $this->assertSame($this->supportAddress, $result);
    }

    /**
     * Verify the envelope address is used, when configured.
     */
    public function testGetDefaultSenderAddressEnvelope(): void {
        $expected = "envelope@example.com";
        $this->config->saveToConfig("Garden.Email.EnvelopeAddress", $expected, false);
        $obj = new Gdn_Email();
        $result = $obj->getDefaultSenderAddress();
        $this->assertSame($expected, $result);
    }

    /**
     * Verify getting a standard no-reply email address for the current domain.
     */
    public function testGetNoReplyAddress(): void {
        /** @var RequestInterface $request */
        $request = $this->container()->get(RequestInterface::class);
        $request->setHost("example.com");

        $obj = new Gdn_Email();
        $result = $obj->getNoReplyAddress();
        $this->assertSame("noreply@example.com", $result);
    }

    /**
     * Verify invoking Gdn_Email::from properly affects the underlying PHPMailer instance.
     *
     * @param string $email
     * @param string $name
     * @param string|null $expectedEmail
     * @param string|null $expectedName
     * @dataProvider provideFromParameters
     */
    public function testFrom(string $email, string $name, ?string $expectedEmail, ?string $expectedName): void {
        // Provider data is retrieved before the test is setup, so we can't use dynamic properties in them. Do it here.
        $expectedName = $expectedName ?? $this->supportName;
        $expectedEmail = $expectedEmail ?? $this->supportAddress;

        $obj = new Gdn_Email();
        $mailer = $obj->PhpMailer;
        $sender = "sender@example.com";
        $mailer->Sender = $sender;
        $obj->from($email, $name);

        $this->assertSame($sender, $mailer->Sender); // We didn't specify overriding the sender. Don't do it.
        $this->assertSame($expectedEmail, $mailer->From);
        $this->assertSame($expectedName, $mailer->FromName);
    }

    /**
     * Verify ability to override the sender on the underlying PHPMailer reference by way of the from method.
     */
    public function testFromOverrideSender(): void {
        $obj = new Gdn_Email();
        $mailer = $obj->PhpMailer;
        $mailer->Sender = "sender@example.com";
        $override = "foo@example.com";
        $obj->from($override, "foo", true);

        $this->assertSame($override, $mailer->Sender);
    }

    /**
     * Verify the underlying PHPMailer sender will default to the standard default, not the specified from address.
     */
    public function testFromDefaultSender(): void {
        $obj = new Gdn_Email();
        $mailer = $obj->PhpMailer;
        $mailer->Sender = "";
        $obj->from(rand() . "@example.com");
        $this->assertSame($obj->getDefaultSenderAddress(), $mailer->Sender);
    }

    /**
     * Provide data for basic usage of the from method.
     *
     * @return array
     */
    public function provideFromParameters(): array {
        return [
            "Specifying email and name" => ["foo@example.com", "foo", "foo@example.com", "foo"],
            "Specifying email" => ["foo@example.com", "", "foo@example.com", null],
            "Specifying name" => ["", "foo", null, "foo"],
            "Specifying neither email nor name" => ["", "", null, null],
        ];
    }
}
