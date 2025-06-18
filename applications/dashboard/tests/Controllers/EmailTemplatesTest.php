<?php
/**
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace Controllers;

use Vanilla\Dashboard\Models\EmailTemplateModel;
use VanillaTests\APIv2\AbstractAPIv2Test;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\SchedulerTestTrait;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Email Template controller test
 */
class EmailTemplatesTest extends AbstractAPIv2Test
{
    use CommunityApiTestTrait;
    use UsersAndRolesApiTestTrait;
    use SchedulerTestTrait;

    private EmailTemplateModel $emailTemplateModel;

    /**
     * @inheritdoc
     */
    public function setUp(): void
    {
        parent::setUp();
        self::enableFeature("AutomationRules");
        self::enableFeature("EmailTemplate");
        $this->emailTemplateModel = $this->container()->get(EmailTemplateModel::class);
        $this->resetTable("emailTemplate");
    }

    /**
     * Test feature flag requirement.
     *
     * @return void
     */
    public function testFeatureNotEnabled()
    {
        self::disableFeature("AutomationRules");
        self::disableFeature("EmailTemplate");
        $this->expectExceptionMessage("Email Template not enabled.");
        $this->createEmailTemplateTest();
        $this->expectExceptionMessage("Email Template not enabled.");
        $this->api()->get("/email-templates");

        self::disableFeature("EmailTemplate");
        self::enableFeature("AutomationRules");
        $this->expectExceptionMessage("Email Template not enabled.");
        $this->createEmailTemplateTest();
        $this->expectExceptionMessage("Email Template not enabled.");
        $this->api()->get("/email-templates");
        $this->expectExceptionMessage("Email Template not enabled.");
        $this->api()->delete("/email-templates/1");

        self::disableFeature("AutomationRules");
        self::enableFeature("EmailTemplate");
        $this->expectExceptionMessage("Email Template not enabled.");
        $this->createEmailTemplateTest();
        $this->expectExceptionMessage("Email Template not enabled.");
        $this->api()->get("/email-templates");
        $this->expectExceptionMessage("Email Template not enabled.");
        $this->api()->patch("/email-templates/1", ["name" => "test"]);
    }

    /**
     * Smoke test of the `GET /api/v2/email-template/settings` endpoint without existing settings.
     *
     * @return void
     */
    public function testGetIndex()
    {
        $member = $this->createUser();

        $this->createEmailTemplateTest();
        $this->createEmailTemplateTest(["name" => "Test Template 2"]);
        $this->createEmailTemplateTest(["name" => "Test Template 3", "emailType" => "system"]);

        $response = $this->api()->get("/email-templates");
        $this->assertTrue($response->isSuccessful());

        $templateList = $response->getBody();
        $this->assertCount(3, $templateList);
        $this->assertSame($templateList[1]["name"], "Test Template 2");
        // test Get email template
        $getTemplate = $this->api()->get("/email-templates/" . $templateList[1]["emailTemplateID"]);
        $this->assertSame($getTemplate["name"], "Test Template 2");

        // test Delete
        $this->api()
            ->delete("/email-templates/" . $templateList[1]["emailTemplateID"])
            ->assertSuccess();
        $response = $this->api()->get("/email-templates");
        $this->assertTrue($response->isSuccessful());

        $this->runWithUser(function () use ($templateList) {
            $this->expectExceptionMessage("Permission Problem");
            $this->api()->delete("/email-templates/" . $templateList[1]["emailTemplateID"]);

            $this->expectExceptionMessage("Permission Problem");
            $this->api()->get("/email-templates", $templateList[1]["emailTemplateID"]);
        }, $member["userID"]);

        //Test delete not existent template
        $this->expectExceptionMessage("Email template not found.");
        $this->api()->delete("/email-templates/" . $templateList[2]["emailTemplateID"] + 20);

        $this->expectExceptionMessage("Email template not found.");
        $this->api()->get("/email-templates/" . $templateList[2]["emailTemplateID"] + 20);

        //Test delete already deleted existent template
        $this->expectExceptionMessage("Email template not found.");
        $this->api()->delete("/email-templates/" . $templateList[1]["emailTemplateID"]);

        $this->expectExceptionMessage("Email template not found.");
        $this->api()->get("/email-templates/" . $templateList[1]["emailTemplateID"]);

        //Test delete system existent template
        $this->expectExceptionMessage("System email templates cannot be deleted.");
        $this->api()->delete("/email-templates/" . $templateList[2]["emailTemplateID"]);

        $templateList = $response->getBody();
        $this->assertCount(2, $templateList);
        $this->assertSame($templateList[1]["name"], "Test Template 3");

        //Check permission member is not able to create email template

        $this->runWithUser(function () {
            $this->expectExceptionMessage("Permission denied.");
            $this->api()->get("/email-templates");
        }, $member["userID"]);
    }

    /**
     * Test that the `patch /api/v2/email-template` updates the email template.
     *
     * @return void
     */
    public function testPatchSettings()
    {
        $emailTemplate = $this->createEmailTemplateTest(["name" => "Test Template 2"]);
        $updateEmail = [
            "name" => "Test Update",
            "emailTemplateID" => $emailTemplate["emailTemplateID"],
            "subject" => "Updated Subject",
            "fromEmail" => "UpdateEmail@template.com",
            "fromName" => "Updated From",
            "body" => "[{\"type\":\"p\",\"children\":[{\"text\":\"Updated Body of the email\"}]}]",
            "footer" => "[{\"type\":\"p\",\"children\":[{\"text\":\"Updated Footer of the email\"}]}]",
        ];

        // Patch with invalid ID
        $this->expectExceptionMessage("Email template not found.");
        $this->api()
            ->patch("/email-templates/" . $emailTemplate["emailTemplateID"] + 20, $updateEmail)
            ->getBody();

        $updatedEmail = $this->api()
            ->patch("/email-templates/" . $emailTemplate["emailTemplateID"], $updateEmail)
            ->getBody();
        $this->assertArraySubsetRecursive($updateEmail, $updatedEmail);
    }

    /**
     * Test duplicate email tempalte error.
     *
     * @return void
     */
    public function testPostDuplicateName()
    {
        $this->createEmailTemplateTest(["name" => "Test Template 20"]);

        $params = [
            "name" => "Test Template 20",
            "subject" => "Email Subject",
            "body" => "[{\"type\":\"p\",\"children\":[{\"text\":\"Body of the email\"}]}]",
            "footer" => "[{\"type\":\"p\",\"children\":[{\"text\":\"Footer of the email\"}]}]",
            "fromEmail" => "estEmail@template.com",
            "fromName" => "Test Template",
            "status" => EmailTemplateModel::STATUS_ACTIVE,
        ];
        $this->expectExceptionMessage("Email template name already exists. Enter a unique name to proceed.");
        $this->api()->post("/email-templates", $params);

        //Check permission member is not able to create email template
        $member = $this->createUser();
        $this->runWithUser(function () use ($params) {
            $this->expectExceptionMessage("Permission denied.");
            $this->api()->post("/email-templates", $params);
        }, $member["userID"]);
    }

    /**
     * Test that the `POST /api/v2/email-template`.
     *
     * @param array $overrides
     *
     * @return array
     */
    public function createEmailTemplateTest(array $overrides = []): array
    {
        $params = $overrides + [
            "name" => "Test Template" . time(),
            "emailType" => "custom",
            "subject" => "Email Subject",
            "body" => "[{\"type\":\"p\",\"children\":[{\"text\":\"Body of the email\"}]}]",
            "footer" => "[{\"type\":\"p\",\"children\":[{\"text\":\"Footer of the email\"}]}]",
            "fromEmail" => "estEmail@template.com",
            "fromName" => "Test Template",
            "status" => EmailTemplateModel::STATUS_ACTIVE,
        ];
        $response = $this->api()->post("/email-templates", $params);
        $this->assertEquals(201, $response->getStatusCode());

        $body = $response->getBody();
        $this->assertIsArray($body);
        $this->assertSame($body["name"], $params["name"]);
        return $body;
    }

    /**
     * Test sending preview email template.
     */
    public function testSendEmailTemplatePreview(): void
    {
        $member = $this->createUser();

        $temp = $this->createEmailTemplateTest();

        $user = $this->createUser([
            "email" => "testuser@example.com",
        ]);

        // test interact with endpoint as not admin
        $this->runWithUser(function () use ($temp, $user) {
            $this->expectExceptionMessage("Permission Problem");
            $response = $this->api()->post("/email-templates/{$temp["emailTemplateID"]}/preview", [
                "destinationAddress" => "dest@example.com",
                "destinationUserID" => $user["userID"],
            ]);
        }, $member["userID"]);

        //Test Preview none existent template
        $this->expectExceptionMessage("Email template not found.");
        $noneExistentID = $temp["emailTemplateID"] + 10;
        $this->api()->post("/email-templates/{$noneExistentID}/preview", [
            "destinationAddress" => "dest@example.com",
            "destinationUserID" => $user["userID"],
        ]);

        $response = $this->api()->post("/email-templates/{$temp["emailTemplateID"]}/preview", [
            "destinationAddress" => "dest@example.com",
            "destinationUserID" => $user["userID"],
        ]);

        $this->assertEquals(201, $response->getStatusCode());
        // Ensure no email was sent to the actual user email since we provided the destination address.
        $this->assertEmailNotSentTo("testuser@example.com");
        $email = $this->assertEmailSentTo("dest@example.com");

        // I've received a content
        $emailHtml = $email->getHtmlDocument();

        $emailHtml->assertContainsString("Body of the email");
    }
}
