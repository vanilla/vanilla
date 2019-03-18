<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

 namespace Vanilla\Theme;

 use JsonSerializable;

 /**
  * Basic theme asset.
  */
abstract class Asset implements \JsonSerializable {

    /** @var string Type of asset. */
    protected $type;

    /**
     * Represent the asset as an array.
     *
     * @return array
     */
    abstract public function asArray(): array;

    /**
     * Get the asset's type.
     *
     * @return string
     */
    public function getType(): string {
        return $this->type;
    }

    /**
     * Specify data which should be serialized to JSON.
     */
    public function jsonSerialize(): array {
        return $this->asArray();
    }
}
