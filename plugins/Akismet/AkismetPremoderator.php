<?php
/**
 * @author Olivier Lamy-Canuel <olamy-canuel@higherlogic.com>
 * @copyright 2009-2024 Higher Logic Inc.
 * @license Proprietary
 */

use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Premoderation\PremoderationHandlerInterface;
use Vanilla\Premoderation\PremoderationItem;
use Vanilla\Premoderation\PremoderationResponse;

/**
 *
 */
class AkismetPremoderator implements PremoderationHandlerInterface
{
    private int $akismetUserID;

    /**
     * D.I.
     *
     * @param AkismetPlugin $akismet
     * @param Gdn_Session $session
     * @param ConfigurationInterface $config
     */
    public function __construct(
        private AkismetPlugin $akismet,
        private Gdn_Session $session,
        private ConfigurationInterface $config,
        private Gdn_Request $request
    ) {
        $this->akismetUserID = $this->config->get("Plugins.Akismet.UserID");
    }

    /**
     * @inheridoc
     */
    public function premoderateItem(PremoderationItem $item): PremoderationResponse
    {
        if ($this->session->isUserVerified()) {
            return PremoderationResponse::valid();
        }

        $body = $this->prepareBody($item);
        $isSpam = $this->akismet->checkAkismet([], $body);

        // This is super spam.
        if ($isSpam) {
            return new PremoderationResponse(PremoderationResponse::SUPER_SPAM, $this->akismetUserID);
        }

        return PremoderationResponse::valid();
    }

    /**
     * Prepare the body for the Akismet service.
     *
     * @param PremoderationItem $item
     * @return array
     */
    public function prepareBody(PremoderationItem $item): array
    {
        return [
            "IPAddress" => $this->request->getIP(),
            "Email" => $item->userEmail,
            "Username" => $item->userName,
            "Body" => $item->recordBody,
            "Format" => $item->recordFormat,
            "Name" => $item->recordName,
        ];
    }
}
