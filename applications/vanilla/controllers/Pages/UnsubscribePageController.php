<?php
/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Forum\Controllers\Pages;

use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Web\PageDispatchController;

/**
 * Controller for /unsubscribe page.
 */
class UnsubscribePageController extends PageDispatchController
{
    /**
     * @var ConfigurationInterface
     */
    private ConfigurationInterface $configuration;

    /**
     * @param ConfigurationInterface $configuration
     */
    public function __construct(ConfigurationInterface $configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * Handle /unsubscribe/:token?
     *
     * @param array $query
     * @return \Garden\Web\Data
     */
    public function index(string $token)
    {
        return $this->useSimplePage("Unsubscribe")
            ->setCanonicalUrl("/unsubscribe/$token")
            ->setSeoTitle(t("Unsubscribe"))
            ->blockRobots()
            ->render();
    }
}
