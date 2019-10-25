<?php
/**
 * @author Alexander Kim <alexander.k@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Site;

use Vanilla\Contracts\Site\ApplicationInterface;
use Vanilla\Contracts\Site\ApplicationProviderInterface;

/**
 * Class for dealing with applications of a site.
 *
 * @see ApplicationProviderInterface
 */
class ApplicationProvider implements ApplicationProviderInterface {

    /** @var ApplicationInterface[] */
    private $apps;

    /**
     * @inheritdoc
     */
    public function getAll(): array {
        return $this->apps;
    }

    /**
     * @inheritdoc
     */
    public function getReservedSlugs(): array {
        $reservedSlugs = [];
        foreach ($this->apps as $app) {
            $reservedSlugs = array_merge($reservedSlugs, $app->getReservedSlugs());
        }
        return $reservedSlugs;
    }

    /**
     * @inheritdoc
     */
    public function add(ApplicationInterface $application): ApplicationProviderInterface {
        $this->apps[] = $application;
        return $this;
    }
}
