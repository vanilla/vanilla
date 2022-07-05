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
class QnaQuickLinksProvider implements QuickLinkProviderInterface
{
    /** @var \QnaModel */
    private $qnaModel;

    /** @var int */
    private $countLimit = 99;

    /**
     * DI.
     *
     * @param \QnaModel $qnaModel
     */
    public function __construct(\QnaModel $qnaModel)
    {
        $this->qnaModel = $qnaModel;
    }

    /**
     * Provide some quick links.
     *
     * @return QuickLink[]
     */
    public function provideQuickLinks(): array
    {
        $quickLink = new QuickLink(
            t("Unanswered"),
            "/discussions/unanswered",
            $this->qnaModel->getUnansweredCount($this->countLimit),
            null,
            "discussions.view"
        );
        $quickLink->setCountLimit($this->countLimit);
        return [$quickLink];
    }
}
