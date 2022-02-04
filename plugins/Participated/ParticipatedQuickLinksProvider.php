<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Addons\Participated;

use Vanilla\Theme\VariableProviders\QuickLink;
use Vanilla\Theme\VariableProviders\QuickLinkProviderInterface;

/**
 * Provide quicklinks.
 */
class ParticipatedQuickLinksProvider implements QuickLinkProviderInterface {

    /** @var \Gdn_Session */
    private $session;

    /** @var \DiscussionModel */
    private $discussionModel;

    /**
     * DI.
     *
     * @param \Gdn_Session $session
     * @param \DiscussionModel $discussionModel
     */
    public function __construct(\Gdn_Session $session, \DiscussionModel $discussionModel) {
        $this->session = $session;
        $this->discussionModel = $discussionModel;
    }

    /**
     * @inheritdoc
     */
    public function provideQuickLinks(): array {
        $session = \Gdn::session();
        if (!$session->isValid()) {
            return [];
        }

        return [
            new QuickLink(
                'Participated',
                '/discussions/participated',
                null,
                $this->discussionModel->getCountParticipated()
            )
        ];
    }
}
