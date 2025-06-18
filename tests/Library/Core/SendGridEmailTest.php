<?php

namespace VanillaTests\Library\Core;

use Vanilla\Email\SendGridMailer;

/**
 * Performs all the tests of EmailTest but with the SendGrid API mailer enabled.
 */
class SendGridEmailTest extends EmailTest
{
    /**
     * @inheritdoc
     */
    public static function getAddons(): array
    {
        $addons = parent::getAddons();
        $addons[] = "vanilla-queue";
        return $addons;
    }

    /**
     * @inheritdoc
     */
    public function setUp(): void
    {
        parent::setUp();
        \Gdn::config()->saveToConfig(\Gdn_Email::CONF_SENDGRID_MAILER_ENABLED, true, false);
        \Gdn::config()->saveToConfig(\Gdn_Email::CONF_SENDGRID_MAILER_API_KEY, "fake-api-key", false);
    }

    /**
     * @inheritdoc
     */
    public function testMailerType()
    {
        $email = new \Gdn_Email();
        $this->assertInstanceOf(SendGridMailer::class, $email->getMailer());
    }
}
