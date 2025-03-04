<?php
/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Formatting\Quill;

use ArrayAccess;
use InvalidArgumentException;
use Vanilla\Formatting\TextFragmentCollectionInterface;
use Vanilla\Formatting\TextFragmentInterface;

/**
 * Class TextFragmentCollection
 */
class TextFragmentCollection implements ArrayAccess, TextFragmentCollectionInterface
{
    /** @var TextFragmentInterface[] */
    private $fragments = [];

    /**
     * Setup the collection.
     *
     * @param array $fragments
     */
    public function __construct(array $fragments = [])
    {
        foreach ($fragments as $name => $fragment) {
            $this->offsetSet($name, $fragment);
        }
    }

    /**
     * Get the fragments from this instance.
     *
     * @return TextFragmentInterface[] Returns an array of text fragments.
     */
    public function getFragments(): array
    {
        return array_values($this->fragments);
    }

    /**
     * Whether an offset exists.
     *
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset): bool
    {
        return isset($this->fragments[$offset]);
    }

    /**
     * Offset to retrieve.
     *
     * @param mixed $offset
     * @return TextFragmentInterface|null
     */
    public function offsetGet($offset): mixed
    {
        return $this->fragments[$offset] ?? null;
    }

    /**
     * Assign a value to the specified offset.
     *
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value): void
    {
        if (!($value instanceof TextFragmentInterface)) {
            throw new InvalidArgumentException("Value must be an instance of " . TextFragmentInterface::class);
        }
        $this->fragments[$offset] = $value;
    }

    /**
     * Unset an offset.
     *
     * @param mixed $offset
     */
    public function offsetUnset($offset): void
    {
        unset($this->fragments[$offset]);
    }
}
