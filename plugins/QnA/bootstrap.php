<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */

use Vanilla\Forum\Modules\QnATabFactory;
use Vanilla\QnA\Models\QnaQuickLinksProvider;
use Vanilla\Theme\VariableProviders\QuickLinksVariableProvider;
use Vanilla\Widgets\TabWidgetTabService;

\Gdn::getContainer()
    ->rule(TabWidgetTabService::class)
    ->addCall("registerTabFactory", [QnATabFactory::getNewQuestionReference()])
    ->addCall("registerTabFactory", [QnATabFactory::getUnansweredQuestionReference()])
    ->addCall("registerTabFactory", [QnATabFactory::getRecentlyAnsweredReference()]);
