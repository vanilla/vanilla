<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Controllers\Api;

use Garden\Schema\Schema;
use Garden\Web\Data;
use Garden\Web\Exception\ClientException;
use Garden\Web\Exception\NotFoundException;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\CurrentTimeStamp;
use Vanilla\FeatureFlagHelper;
use Vanilla\Forum\Digest\DigestEmail;
use Vanilla\Forum\Digest\DigestModel;
use Vanilla\Forum\Digest\EmailDigestGenerator;
use Vanilla\Permissions;
use Vanilla\Scheduler\LongRunner;
use Vanilla\Scheduler\LongRunnerAction;
use Vanilla\Scheduler\LongRunnerMultiAction;

/**
 * /api/v2/emails
 */
class EmailsApiController extends \AbstractApiController
{
    private EmailDigestGenerator $digestGenerator;
    private \UserModel $userModel;
    private DigestModel $digestModel;
    private LongRunner $longRunner;
    private ConfigurationInterface $config;

    /**
     * @param EmailDigestGenerator $digestGenerator
     * @param \UserModel $userModel
     * @param DigestModel $digestModel
     * @param LongRunner $longRunner
     * @param ConfigurationInterface $config
     */
    public function __construct(
        EmailDigestGenerator $digestGenerator,
        \UserModel $userModel,
        DigestModel $digestModel,
        LongRunner $longRunner,
        ConfigurationInterface $config
    ) {
        $this->digestGenerator = $digestGenerator;
        $this->userModel = $userModel;
        $this->digestModel = $digestModel;
        $this->longRunner = $longRunner;
        $this->config = $config;
    }

    /**
     * View a preview of the email template.
     *
     * @param array $body
     * @return Data
     */
    public function post_preview(array $body): Data
    {
        $this->permission("community.manage");
        $in = $this->testEmailSchema();
        $body = $in->validate($body);

        $testEmail = $this->getTestEmail($body);
        return new Data($testEmail->getEmailTemplate()->toString(), 200, ["content-type" => "text/html"]);
    }

    /**
     * Send a test email.
     *
     * @param array $body
     *
     * @return Data
     */
    public function post_sendTest(array $body): Data
    {
        $this->permission("community.manage");
        $in = Schema::parse([
            "destinationAddress:s" => [
                "format" => "email",
            ],
            "from?" => Schema::parse(["supportName:s?", "supportAddress:s?"]),
        ])->merge($this->testEmailSchema());
        $body = $in->validate($body);

        $testEmail = $this->getTestEmail($body);
        $testEmail->from($body["from"]["supportAddress"] ?? "", $body["from"]["supportName"] ?? "");
        $testEmail->to($body["destinationAddress"]);

        $testEmail->subject(sprintf(t("Test email from %s"), c("Garden.Title")));

        $testEmail->send();

        return new Data(["success" => true], 201);
    }

    /**
     * POST /api/v2/emails/send-test-digest
     *
     * Send a test email digest calculated against a specific user.
     *
     * @param array $body
     *
     * @return Data
     */
    public function post_sendTestDigest(array $body): Data
    {
        $this->permission("community.manage");
        FeatureFlagHelper::ensureFeature(DigestEmail::FEATURE_FLAG);
        $in = Schema::parse([
            "destinationAddress:s" => [
                "format" => "email",
            ],
            "destinationUserID:i",
            "from?" => Schema::parse(["supportName:s?", "supportAddress:s?"]),
            "deliveryDate:dt?",
        ])->merge($this->testEmailSchema());
        $body = $in->validate($body);

        $destinationUser = $this->userModel->getID($body["destinationUserID"], DATASET_TYPE_ARRAY);

        if (!$destinationUser) {
            throw new NotFoundException("User", ["userID" => $body["destinationUserID"]]);
        }

        /** @var DigestEmail $testDigest */
        $testDigest = $this->runWithTestEmailConfigs($body, function () use ($body) {
            $digestEmail = $this->digestGenerator->prepareSingleUserDigest($body["destinationUserID"]);
            return $digestEmail;
        });
        $testDigest->from($body["from"]["supportAddress"] ?? "", $body["from"]["supportName"] ?? "");
        $testDigest->subject("[" . c("Garden.Title") . "] Test Weekly Digest");
        $testDigest->PhpMailer->clearAllRecipients();
        $testDigest->to($body["destinationAddress"], $destinationUser["Name"]);

        if (isset($body["deliveryDate"])) {
            $testDigest->scheduleDelivery($body["deliveryDate"]);
        }

        $testDigest->send();

        return new Data(["success" => true], 201);
    }

    /**
     * POST /api/v2/emails/send-digest-internal
     *
     * Internal API endpoint for QA testing.
     * This sends a digest email immediately to all users set to receive them.
     *
     * @return Data
     * @throws \Exception
     */
    public function post_sendDigestInternal(): Data
    {
        // Must be system user to run this.
        $this->permission("Garden.Admin.Only");
        FeatureFlagHelper::ensureFeature(DigestEmail::FEATURE_FLAG);

        if (!$this->config->get("Garden.Digest.Enabled")) {
            throw new ClientException("Email digest is not enabled.");
        }

        if ($this->config->get(\Gdn_Email::CONF_DISABLED)) {
            throw new ClientException("Email is disabled.");
        }

        $fiveMinutesFromNow = CurrentTimeStamp::getDateTime()->modify("+5 minutes");

        // Now we know it's time to start generating the digest.
        // First we check if was already generated (or another process is already generating it).
        $newDigestID = $this->digestModel->insert([
            "digestType" => DigestModel::DIGEST_TYPE_TEST_WEEKLY,
            "dateScheduled" => $fiveMinutesFromNow,
        ]);

        $action = new LongRunnerMultiAction([
            new LongRunnerAction(EmailDigestGenerator::class, "createDigestsIterator", [$newDigestID]),
            new LongRunnerAction(EmailDigestGenerator::class, "sendDigestsIterator", [$newDigestID]),
        ]);

        $response = $this->longRunner->runApi($action);
        $response->setDataItem("dateScheduled", $fiveMinutesFromNow);
        return $response;
    }

    /**
     * Sets up a new Gdn_Email object with a test email.
     *
     * @param array $testEmailParams Data matching {@link self::testEmailSchema()}.
     *
     * @return \Gdn_Email The email object with the test colors set.
     */
    public function getTestEmail(array $testEmailParams): \Gdn_Email
    {
        $email = $this->runWithTestEmailConfigs($testEmailParams, function () use ($testEmailParams) {
            $email = new \Gdn_Email();
            $footerHtml = $email->getFooterContent($testEmailParams["footer"] ?? null);
            if ($footerHtml) {
                $email->getEmailTemplate()->setFooterHtml($footerHtml);
            }
            return $email;
        });

        $message = t("Test Email Message");

        $email
            ->getEmailTemplate()
            ->setMessage($message)
            ->setTitle(t("Test Email"))
            ->setButton(externalUrl("/"), t("Check it out"));

        return $email;
    }

    /**
     * Common schema for a test email.
     *
     * @return Schema
     */
    private function testEmailSchema(): Schema
    {
        return Schema::parse([
            "emailFormat:s?" => [
                "enum" => ["html", "text"],
            ],
            "footer:s?",
            "templateStyles?" => Schema::parse([
                "image:s?",
                "textColor:s?",
                "backgroundColor:s?",
                "containerBackgroundColor:s?",
                "buttonTextColor:s?",
                "buttonBackgroundColor:s?",
            ]),
        ]);
    }

    /**
     * Given data matching our {@link self::testEmailSchema()} apply the relevant configs temporarily in memory.
     *
     * @template T
     *
     * @param array $templateConfig
     * @param callable(): T $callable
     *
     * @return T
     */
    public function runWithTestEmailConfigs(array $templateConfig, callable $callable)
    {
        $validated = $this->testEmailSchema()->validate($templateConfig);
        $config = \Gdn::config();
        $configsToRestore = [
            "Garden.EmailTemplate" => $config->get("Garden.EmailTemplate", []),
            "Garden.Email" => $config->get("Garden.Email", []),
        ];

        foreach ($validated["templateStyles"] ?? [] as $key => $value) {
            $configKey = "Garden.EmailTemplate." . ucfirst($key);
            $config->saveToConfig($configKey, $value, false);
        }

        if (isset($validated["footer"])) {
            $config->saveToConfig("Garden.Email.Footer", $validated["footer"], false);
        }

        if (isset($validated["emailFormat"])) {
            $config->saveToConfig("Garden.Email.Format", $validated["emailFormat"], false);
        }

        try {
            return call_user_func($callable);
        } finally {
            $config->saveToConfig($configsToRestore, null, false);
        }
    }
}
