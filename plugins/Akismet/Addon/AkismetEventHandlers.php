<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Akismet\Addon;

use Garden\PsrEventHandlersInterface;
use Vanilla\Akismet\Clients\AkismetClient;
use Vanilla\Community\Events\SpamEvent;
use Vanilla\Logging\ErrorLogger;

class AkismetEventHandlers implements PsrEventHandlersInterface
{
    /**
     * D.I.
     *
     * @param AkismetClient $akismetClient
     */
    public function __construct(private AkismetClient $akismetClient)
    {
    }

    /**
     * @inheritDoc
     */
    public static function getPsrEventHandlerMethods(): array
    {
        return ["handleSpamEvent"];
    }

    /**
     * Submits missed spam events to Akismet for analysis.
     *
     * @param SpamEvent $event
     * @return SpamEvent
     */
    public function handleSpamEvent(SpamEvent $event): SpamEvent
    {
        $spamReport = $event->spamReport;
        $response = $this->akismetClient->submitSpam([
            "user_ip" => $spamReport->insertIPAddress,
            "permalink" => $spamReport->url,
            "comment_type" => $this->getAkismetCommentType($spamReport->recordType),
            "comment_author" => $spamReport->insertUserName,
            "comment_author_email" => $spamReport->insertUserEmail,
            "comment_content" => $spamReport->bodyPlainText,
        ]);
        if ($response->getRawBody() !== "Thanks for making the web a better place.") {
            ErrorLogger::error(
                "Failed to submit spam to akismet",
                ["akismet"],
                ["status" => $response->getStatusCode(), "body" => $response->getRawBody()]
            );
        }

        return $event;
    }

    /**
     * Convert the vanilla record type to value that Akismet uses.
     *
     * @param $type
     * @return string
     */
    private function getAkismetCommentType($type): string
    {
        return match (strtolower($type)) {
            "discussion" => "forum-post",
            "comment" => "reply",
            default => $type,
        };
    }
}
