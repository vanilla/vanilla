<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Controllers\Api;

use Garden\Schema\Schema;
use Garden\Web\Data;

/**
 * /api/v2/emails
 */
class EmailsApiController extends \AbstractApiController
{
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
     * Sets up a new Gdn_Email object with a test email.
     *
     * @return \Gdn_Email The email object with the test colors set.
     */
    public function getTestEmail(array $params): \Gdn_Email
    {
        $email = new \Gdn_Email();

        if (isset($params["emailFormat"])) {
            $email->setFormat($params["emailFormat"]);
        }
        $template = $email->getEmailTemplate();
        $template->applyModernStyles($params["templateStyles"] ?? []);

        $message = t("Test Email Message");

        $template
            ->setMessage($message)
            ->setTitle(t("Test Email"))
            ->setButton(externalUrl("/"), t("Check it out"));
        $footerHtml = $email->getFooterContent($params["footer"] ?? null);
        if ($footerHtml) {
            $template->setFooterHtml($footerHtml);
        }
        return $email;
    }

    /**
     * Common schema for a test email.
     *
     * @return Schema
     */
    private function testEmailSchema(): Schema
    {
        $template = new \EmailTemplate();
        return Schema::parse([
            "emailFormat:s" => [
                "enum" => ["html", "text"],
            ],
            "footer:s?",
            "templateStyles?" => $template->modernStylesSchema(),
        ]);
    }
}
