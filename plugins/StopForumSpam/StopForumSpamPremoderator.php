<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\StopForumSpam;

use Garden\Http\HttpClient;
use Garden\Http\HttpHandlerInterface;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Premoderation\PremoderationHandlerInterface;
use Vanilla\Premoderation\PremoderationItem;
use Vanilla\Premoderation\PremoderationResponse;
use Vanilla\Web\SafeCurlHttpHandler;

/**
 * A client for the StopForumSpam API.
 */
class StopForumSpamPremoderator implements PremoderationHandlerInterface
{
    /**
     * DI.
     */
    public function __construct(
        private SafeCurlHttpHandler $httpHandler,
        private ConfigurationInterface $config,
        private \Gdn_Request $request,
        private \Gdn_Session $session
    ) {
    }

    /**
     * @return HttpClient
     */
    private function http(): HttpClient
    {
        $client = new HttpClient("https://www.stopforumspam.com", $this->httpHandler);
        $client->setThrowExceptions(true);
        return $client;
    }

    /**
     * Check if this is spam.
     *
     * @param PremoderationItem $item
     *
     * @return PremoderationResponse
     */
    public function premoderateItem(PremoderationItem $item): PremoderationResponse
    {
        if ($this->session->isUserVerified()) {
            return PremoderationResponse::valid();
        }
        $body = $this->prepareBody($item);
        $response = $this->http()->post(
            "/api?f=json",
            http_build_query($body),
            [
                "Content-Type" => "application/x-www-form-urlencoded",
            ],
            [
                "timeout" => 3,
            ]
        );

        $responseBody = $response->getBody();

        $ipFrequency = $responseBody["ip"]["frequency"] ?? 0;
        $emailFrequency = $response["email"]["frequency"] ?? 0;

        $modUserID = $this->config->get("Plugins.StopForumSpam.UserID", null);

        // This is super spam.
        if (
            $ipFrequency >= $this->config->get("Plugins.StopForumSpam.IPThreshold2", 20) ||
            $emailFrequency >= $this->config->get("Plugins.StopForumSpam.EmailThreshold2", 50)
        ) {
            return new PremoderationResponse(PremoderationResponse::SUPER_SPAM, $modUserID);
        }

        // Flag registrations as spam above a certain threshold.
        if (
            $ipFrequency >= $this->config->get("Plugins.StopForumSpam.IPThreshold1", 5) ||
            $emailFrequency >= $this->config->get("Plugins.StopForumSpam.EmailThreshold1", 20)
        ) {
            // We are spam.
            return new PremoderationResponse(PremoderationResponse::SPAM, $modUserID);
        }

        return PremoderationResponse::valid();
    }

    /**
     * @param PremoderationItem $item
     * @return array
     */
    private function prepareBody(PremoderationItem $item): array
    {
        $result = [
            "username" => $item->userName,
            "email" => $item->userEmail,
            "ip" => $this->request->getIP(),
        ];
        return $result;
    }
}
