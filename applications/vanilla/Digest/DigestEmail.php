<?php
/**
 * @author Sooraj Francis <sfrancis@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Forum\Digest;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Vanilla\Dashboard\Models\UserNotificationPreferencesModel;
use Vanilla\Formatting\Html\HtmlSanitizer;
use Vanilla\Logging\ErrorLogger;

class DigestEmail extends \Gdn_Email implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    // Day names for strtotime based on the digest.dayOfWeek config.
    private const SCHEDULE_DAYS = [
        1 => "monday",
        2 => "tuesday",
        3 => "wednesday",
        4 => "thursday",
        5 => "friday",
        6 => "saturday",
        7 => "sunday",
    ];

    private string $htmlContent;

    private string $textContent;

    private \ActivityModel $activityModel;

    /**
     * @param \ActivityModel $activityModel
     * @param UserNotificationPreferencesModel $userNotificationPreferencesModel
     */
    public function __construct(
        \ActivityModel $activityModel,
        private UserNotificationPreferencesModel $userNotificationPreferencesModel
    ) {
        $this->activityModel = $activityModel;
        parent::__construct();
    }

    public function setHtmlContent($content): void
    {
        $this->htmlContent = $content;
    }

    public function setTextContent($content): void
    {
        $this->textContent = $this->formatContent($content);
    }

    public function getHtmlContent(): string
    {
        return $this->htmlContent;
    }

    public function getTextContent(): string
    {
        return $this->textContent;
    }

    /**
     * Filters an unsafe HTML string and returns it.
     *
     * @param string $content The HTML to filter.
     * @param bool $convertNewlines Whether to convert new lines to html br tags.
     * @param bool $filterHtml Whether to escape HTML or not.
     * @return string
     */
    private function formatContent(string $content, bool $convertNewlines = false, bool $filterHtml = false): string
    {
        if ($filterHtml) {
            $htmlSanitizer = \Gdn::getContainer()->get(HtmlSanitizer::class);
            $content = $htmlSanitizer->filter($content);
        }
        if ($convertNewlines) {
            $content = preg_replace('/(\015\012)|(\015)|(\012)/', "<br>", $content);
        }
        return $content;
    }

    /**
     * Set the recipient of the email.
     *
     * @param string $email
     * @param string $username
     */
    public function setToAddress(string $email, string $username)
    {
        $this->mailer->clearRecipients();
        $this->addTo($email, $username);
    }

    public function formatMessage($message)
    {
        if ($this->format === "html") {
            $this->mailer->setHtmlContent($this->getHtmlContent());
            $this->mailer->setTextContent(trim($this->getTextContent()));
        } else {
            $this->mailer->setTextOnlyContent(trim($this->getTextContent()));
        }
    }

    /**
     * Merge unsubscribe url links to unsubscribe from the category
     *
     * @param array $user
     * @param int[] $categoryIDs
     * @return void
     */
    public function mergeCategoryUnSubscribe(array $user, array $categoryIDs): void
    {
        $mergeCode = [];
        foreach ($categoryIDs as $categoryID) {
            $unsubscribeTextLink = $this->activityModel->getUnfollowCategoryLink($user, $categoryID);
            $mergeCode["*/unsubscribe_{$categoryID}/*"] = $unsubscribeTextLink;
        }
        $this->setUnSubscribeMergeCodes(array_keys($mergeCode), $mergeCode);
    }

    /**
     * Merge Digest unsubscribe info to the email template
     *
     * @param array $user
     * @return void
     */
    public function mergeDigestUnsubscribe(array $user): void
    {
        $userPref = $this->userNotificationPreferencesModel->getUserPrefs($user["UserID"]);
        $subscribeReason =
            $userPref["Email.DigestEnabled"] === 3
                ? "You are receiving this email because you were opted in to receive email digests."
                : "You are receiving this email because you opted in to receive email digests.";
        $reasonMergeCode = "*/digest_subscribe_reason/*";
        $html = str_replace($reasonMergeCode, $subscribeReason, $this->getHtmlContent());
        $this->setHtmlContent($html);
        $text = str_replace($reasonMergeCode, $subscribeReason, $this->getTextContent());
        $this->setTextContent($text);

        $linkMergeCode = "*/digest_unsubscribe/*";
        $unsubscribeTextLink = $this->activityModel->getUnsubscribeDigestLink($user);
        $html = str_replace($linkMergeCode, $unsubscribeTextLink, $this->getHtmlContent());
        $this->setHtmlContent($html);
        $text = str_replace($linkMergeCode, $unsubscribeTextLink, $this->getTextContent());
        $this->setTextContent($text);
        $this->setUnSubscribeMergeCodes($linkMergeCode, $unsubscribeTextLink);
    }

    /**
     * Replace merge code form digest contents with unsubscribe link
     *
     * @param array|string $mergeCode
     * @param array|string $unsubscribeLink
     * @return void
     */
    public function setUnSubscribeMergeCodes(array|string $mergeCode, array|string $unsubscribeLink): void
    {
        $html = str_replace($mergeCode, $unsubscribeLink, $this->getHtmlContent());
        $this->setHtmlContent($html);
        $text = str_replace($mergeCode, $unsubscribeLink, $this->getTextContent());
        $this->setTextContent($text);
    }

    /**
     * @inheritdoc
     */
    public function getFooterContent(string $content = null): string
    {
        $footerContent = $content ?? $this->config->get("Garden.Digest.Footer", "");
        if (empty($footerContent)) {
            return "";
        }
        try {
            $digestFooter = $this->formatConfigContent($footerContent);
        } catch (\Exception $exception) {
            ErrorLogger::error(
                "Failed to render digest email footer.",
                ["digest", "email"],
                [
                    "exception" => $exception,
                ]
            );
            $digestFooter = "";
        }
        return $digestFooter;
    }

    public function getIntroductionContentForDigest(): string
    {
        $introductionContent = $this->config->get("Garden.Digest.Introduction", "");
        if (empty($introductionContent)) {
            return "";
        }
        try {
            $digestIntroduction = $this->formatConfigContent($introductionContent);
        } catch (\Exception $exception) {
            ErrorLogger::error(
                "Failed to render digest email introduction.",
                ["digest", "email"],
                [
                    "exception" => $exception,
                ]
            );
            $digestIntroduction = "";
        }
        return $digestIntroduction;
    }
}
