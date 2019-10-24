<?php
/**
 * @author Alexander Kim <alexander.k@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Site;

use Vanilla\Contracts\Site\ApplicationInterface;

/**
 * Class Application
 * @package Vanilla\Site
 */
class Application implements ApplicationInterface {
    /**
     * @var string
     */
    private $name = '';

    /**
     * @var array List of reserved root slugs managed by app
     */
    private $reservedSlugs = [];

    /**
     * Application constructor.
     *
     * @param string $name
     * @param array $reservedSlugs
     */
    public function __construct(string $name, array $reservedSlugs) {
        $this->name = $name;
        $this->reservedSlugs = $reservedSlugs;
    }

    /**
     * @inheritdoc
     */
    public function getName(): string {
        return $this->name;
    }

    /**
     * @inheritdoc
     */
    public function getReservedSlugs(): array {
        return $this->reservedSlugs;
    }
}
