<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Navigation;

/**
 * A class for dealing with Breadcrumb data.
 */
class Breadcrumb implements \JsonSerializable {
    /** @var string */
    private $name;

    /** @var string */
    private $url;

    /**
     * Breadcrumb constructor.
     *
     * @param string $name
     * @param string $url
     */
    public function __construct(string $name, string $url) {
        $this->name = $name;
        $this->url = $url;
    }

    /**
     * @inheritdoc
     */
    public function jsonSerialize() {
        return $this->asArray();
    }

    /**
     * @return string
     */
    public function getName(): string {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getUrl(): string {
        return $this->url;
    }

    /**
     * Return breadcrumb data as structure ['name'=>'breadcrumb title', 'url'=>'breadcrumb url']
     *
     * @return array
     */
    public function asArray(): array {
        return [
            'name' => $this->getName(),
            'url' => $this->getUrl(),
        ];
    }
}
