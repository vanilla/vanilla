<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\QnA\Models;

use Vanilla\Theme\VariableProviders\QuickLink;
use Vanilla\Theme\VariableProviders\QuickLinkProviderInterface;

/**
 * Provide some quick links.
 */
class QnaQuickLinksProvider implements QuickLinkProviderInterface {

    /** @var \QnAPlugin */
    private $qnaPlugin;

    /**
     * DI.
     *
     * @param \QnAPlugin $qnaPlugin
     */
    public function __construct(\QnAPlugin $qnaPlugin) {
        $this->qnaPlugin = $qnaPlugin;
    }

    /**
     * Provide some quick links.
     *
     * @return QuickLink[]
     */
    public function provideQuickLinks(): array {
        return [
            new QuickLink(
                t('Unanswered'),
                '/discussions/unanswered',
                $this->qnaPlugin->getUnansweredCount() ?? 0
            )
        ];
    }
}
