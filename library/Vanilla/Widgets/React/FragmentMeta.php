<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Widgets\React;

use Garden\Schema\Schema;

abstract class FragmentMeta implements \JsonSerializable
{
    /**
     * Get the type of the fragment.
     *
     * @return string
     */
    abstract public static function getFragmentType(): string;

    abstract public static function getName(): string;

    /**
     * Get the schema of props passed to the fragment.
     *
     * @return Schema
     */
    abstract public function getPropSchema(): Schema;

    /**
     * @return bool
     */
    public static function isAvailableInStyleguide(): bool
    {
        return false;
    }

    /**
     * @return array
     */
    public function jsonSerialize(): array
    {
        return [
            "fragmentType" => static::getFragmentType(),
            "schema" => $this->getPropSchema(),
            "name" => static::getName(),
        ];
    }
}
