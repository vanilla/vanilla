<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Menu;

/**
 * A class for dealing with Counter data.
 */
class Counter implements \JsonSerializable {
    /** @var string */
    private $name;

    /** @var int */
    private $count;

    /**
     * Counter constructor.
     *
     * @param string $name
     * @param int $count
     */
    public function __construct(string $name, int $count) {
        $this->name = $name;
        $this->count = $count;
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
    public function getCount(): int {
        return $this->count;
    }

    /**
     * Return counter data as structure ['name'=>'breadcrumb title', 'count'=> 0]
     *
     * @return array
     */
    public function asArray(): array {
        return [
            'name' => $this->getName(),
            'count' => $this->getCount(),
        ];
    }
}
