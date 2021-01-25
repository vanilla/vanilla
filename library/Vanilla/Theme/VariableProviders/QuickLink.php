<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Theme\VariableProviders;

/**
 * Class representing a quick link default variable.
 */
class QuickLink implements \JsonSerializable {

    /** @var string */
    private $name;

    /** @var string */
    private $url;

    /** @var int|null */
    private $count;

    /** @var string */
    private $id;

    /** @var int|null */
    private $sort;

    /**
     * Constructor.
     *
     * @param string $name
     * @param string $url
     * @param int|null $count
     * @param int|null $sort
     */
    public function __construct(
        string $name,
        string $url,
        ?int $count = null,
        ?int $sort = null
    ) {
        $this->name = $name;
        $this->url = $url;
        $this->count = $count;
        $this->id = slugify($name);
        $this->sort = $sort;
    }

    /**
     * @return array
     */
    public function jsonSerialize() {
        return [
            'name' => $this->name,
            'url' => $this->url,
            'id' => $this->id,
        ];
    }

    /**
     * @return int|null
     */
    public function getCount(): ?int {
        return $this->count;
    }

    /**
     * @return string
     */
    public function getID(): string {
        return $this->id;
    }

    /**
     * @return int|null
     */
    public function getSort(): int {
        return $this->sort ?? 0;
    }
}
