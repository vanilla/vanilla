<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Core;

use Garden\Web\RequestInterface;
use Gdn_Email;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Email\StandardMailer;
use VanillaTests\SiteTestCase;

/**
 * Tests for the Gdn_Email class
 */
class EmailTest extends SiteTestCase
{
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
    private function setSupportAddress(string $supportAddress): void
    {
        $this->supportAddress = $supportAddress;
        $this->config->saveToConfig("Garden.Email.SupportAddress", $supportAddress, false);
    }

    /**
     * Set the email support name config on both the config instance and locally.
     *
     * @param string $supportName
     */
    private function setSupportName(string $supportName): void
    {
        $this->supportName = $supportName;
        $this->config->saveToConfig("Garden.Email.SupportName", $supportName, false);
    }

    /**
     * @inheritDoc
     */
    public function setUp(): void
    {
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
    public function testGetDefaultFromAddressNoReply(): void
    {
        $this->setSupportAddress("");
        $obj = new Gdn_Email();
        $result = $obj->getDefaultFromAddress();
        $this->assertSame($obj->getNoReplyAddress(), $result);
    }

    /**
     * Verify the support address is used, when no envelope address is configured.
     */
    public function testGetDefaultSenderAddressSupport(): void
    {
        $obj = new Gdn_Email();
        $result = $obj->getDefaultSenderAddress();
        $this->assertSame($this->supportAddress, $result);
    }

    /**
     * Verify the envelope address is used, when configured.
     */
    public function testGetDefaultSenderAddressEnvelope(): void
    {
        $expected = "envelope@example.com";
        $this->config->saveToConfig("Garden.Email.EnvelopeAddress", $expected, false);
        $obj = new Gdn_Email();
        $result = $obj->getDefaultSenderAddress();
        $this->assertSame($expected, $result);
    }

    /**
     * Verify getting a standard no-reply email address for the current domain.
     */
    public function testGetNoReplyAddress(): void
    {
        /** @var RequestInterface $request */
        $request = $this->container()->get(RequestInterface::class);
        $request->setHost("example.com");

        $obj = new Gdn_Email();
        $result = $obj->getNoReplyAddress();
        $this->assertSame("noreply@example.com", $result);
    }

    /**
     * Verify invoking Gdn_Email::from properly affects the underlying mailer instance.
     *
     * @param string $email
     * @param string $name
     * @param string|null $expectedEmail
     * @param string|null $expectedName
     * @dataProvider provideFromParameters
     */
    public function testFrom(string $email, string $name, ?string $expectedEmail, ?string $expectedName): void
    {
        // Provider data is retrieved before the test is setup, so we can't use dynamic properties in them. Do it here.
        $expectedName = $expectedName ?? $this->supportName;
        $expectedEmail = $expectedEmail ?? $this->supportAddress;

        $obj = new Gdn_Email();
        $mailer = $obj->getMailer();
        $sender = "sender@example.com";
        $mailer->setSender($sender);
        $obj->from($email, $name);

        $this->assertSame($sender, $mailer->getSender()); // We didn't specify overriding the sender. Don't do it.
        $this->assertSame($expectedEmail, $mailer->getFromAddress());
        $this->assertSame($expectedName, $mailer->getFromName());
    }

    /**
     * Verify ability to override the sender on the underlying mailer reference by way of the from method.
     */
    public function testFromOverrideSender(): void
    {
        $obj = new Gdn_Email();
        $mailer = $obj->getMailer();
        $mailer->setSender("sender@example.com");
        $override = "foo@example.com";
        $obj->from($override, "foo", true);

        $this->assertSame($override, $mailer->getSender());
    }

    /**
     * Verify the underlying mailer sender will default to the standard default, not the specified from address.
     */
    public function testFromDefaultSender(): void
    {
        $obj = new Gdn_Email();
        $mailer = $obj->getMailer();
        $mailer->setSender("");
        $obj->from(rand() . "@example.com");
        $this->assertSame($obj->getDefaultSenderAddress(), $mailer->getSender());
    }

    /**
     * Provide data for basic usage of the from method.
     *
     * @return array
     */
    public function provideFromParameters(): array
    {
        return [
            "Specifying email and name" => ["foo@example.com", "foo", "foo@example.com", "foo"],
            "Specifying email" => ["foo@example.com", "", "foo@example.com", null],
            "Specifying name" => ["", "foo", null, "foo"],
            "Specifying neither email nor name" => ["", "", null, null],
        ];
    }

    /**
     * Test email footer settings
     *
     * @dataProvider provideFooterContent
     */
    public function testFooterContent(array $configs, string $expected): void
    {
        $this->runWithConfig($configs, function () use ($expected) {
            $email = new Gdn_Email();
            $this->assertEquals($expected, $email->getFooterContent());
        });
    }

    /**
     * @return iterable
     */
    public function provideFooterContent(): iterable
    {
        yield "empty config" => [
            [
                "Garden.Email.Footer" => "",
            ],
            "",
        ];

        yield "invalid config" => [
            [
                "Garden.Email.Footer" => "invalid rich text",
            ],
            "",
        ];

        yield "Rich content text email" => [
            [
                "Garden.Email.Footer" =>
                    '[{"type":"h2","children":[{"text":"Hello World!!"}]},{"type":"p","children":[{"text":"test"}]}]',
            ],
            "Hello World!!\ntest",
        ];

        yield "Rich content html email" => [
            [
                "Garden.Email.Format" => "html",
                "Garden.Email.Footer" =>
                    '[{"type":"h2","children":[{"text":"Hello World!!"}]},{"type":"p","children":[{"text":"test"}]}]',
            ],
            '<h2 data-id="hello-world">Hello World!!</h2><p>test</p>',
        ];
    }

    /**
     * Tests that the mailer type is correct.
     *
     * @return void
     */
    public function testMailerType()
    {
        $email = new Gdn_Email();
        $this->assertInstanceOf(StandardMailer::class, $email->getMailer());
    }

    public function provideEmail()
    {
    }

    /**
     * Tests Gdn_Email::to(). Only the first email is added to the "To" field. The rest are added to the "Cc" field.
     *
     * @return void
     */
    public function testTo()
    {
        $email = new Gdn_Email();
        $email->to("test1@example.com");
        $email->to("test2@example.com");

        $tos = $email->getMailer()->getToAddresses();
        $this->assertCount(1, $tos);
        $this->assertSame($tos[0]["email"], "test1@example.com");

        $ccs = $email->getMailer()->getCcAddresses();
        $this->assertCount(1, $ccs);
        $this->assertSame($ccs[0]["email"], "test2@example.com");
    }

    /**
     * Tests Gdn_Email::addTo().
     *
     * @return void
     */
    public function testAddTo()
    {
        $email = new Gdn_Email();
        $email->addTo("test1@example.com");
        $email->addTo("test2@example.com");

        $tos = $email->getMailer()->getToAddresses();
        $this->assertCount(2, $tos);
        $this->assertSame($tos[0]["email"], "test1@example.com");
        $this->assertSame($tos[1]["email"], "test2@example.com");
    }

    /**
     * Tests Gdn_Email::cc().
     *
     * @return void
     */
    public function testCc()
    {
        $email = new Gdn_Email();
        $email->cc("test1@example.com");
        $email->cc("test2@example.com");

        $ccs = $email->getMailer()->getCcAddresses();
        $this->assertCount(2, $ccs);
        $this->assertSame($ccs[0]["email"], "test1@example.com");
        $this->assertSame($ccs[1]["email"], "test2@example.com");
    }

    /**
     * Tests Gdn_Email::bcc().
     *
     * @return void
     */
    public function testBcc()
    {
        $email = new Gdn_Email();
        $email->bcc("test1@example.com");
        $email->bcc("test2@example.com");

        $bccs = $email->getMailer()->getBccAddresses();
        $this->assertCount(2, $bccs);
        $this->assertSame($bccs[0]["email"], "test1@example.com");
        $this->assertSame($bccs[1]["email"], "test2@example.com");
    }

    /**
     * Test Gdn_Email::subject()
     *
     * @return void
     */
    public function testSubject()
    {
        $email = new Gdn_Email();
        $email->subject("I am a subject");
        $this->assertSame("I am a subject", $email->getMailer()->getSubject());
    }

    /**
     * Test Gdn_Email::formatMessage() when format is HTML.
     *
     * @return void
     */
    public function testFormatMessageWithHtml()
    {
        $email = new Gdn_Email();
        $email->setFormat("html");
        $email->formatMessage("<span>Html Part</span>" . \EmailTemplate::PLAINTEXT_START . "Text part");
        $this->assertSame("<span>Html Part</span>", $email->getMailer()->getBodyContent());
        $this->assertSame("Text part", $email->getMailer()->getTextContent());
    }

    /**
     * Test Gdn_Email::formatMessage() when format is text.
     *
     * @return void
     */
    public function testFormatMessageWithText()
    {
        $email = new Gdn_Email();
        $email->setFormat("text");
        $email->formatMessage("<span>Html Part</span>" . \EmailTemplate::PLAINTEXT_START . "Text part");
        $this->assertSame(
            "<span>Html Part</span><!-- //TEXT VERSION FOLLOWS//Text part",
            $email->getMailer()->getTextOnlyContent()
        );
    }

    /**
     * Test getting and setting the format.
     *
     * @return void
     */
    public function testFormat()
    {
        $email = new Gdn_Email();
        $email->setFormat("html");
        $this->assertSame("html", $email->getFormat());

        $email->setFormat("text");
        $this->assertSame("text", $email->getFormat());
    }
}
