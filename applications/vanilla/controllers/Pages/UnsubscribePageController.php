<?php
/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2024 Higher Logic Inc.
 * @license Proprietary
 */

namespace Vanilla\Forum\Controllers\Pages;

use Garden\Container\ContainerException;
use Garden\Container\NotFoundException;
use Garden\Web\Data;
use Garden\Web\Exception\ServerException;
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
     * @param string $token
     * @return Data
     * @throws ContainerException
     * @throws NotFoundException
     * @throws ServerException
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
