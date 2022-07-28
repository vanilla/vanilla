<?php
/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Formatting\Quill;

use InvalidArgumentException;
use Vanilla\Formatting\Quill\BlotPointerTextFragment;
use Vanilla\Formatting\Quill\Blots\TextBlot;
use Vanilla\Formatting\Quill\TextFragmentCollection;
use Vanilla\Formatting\TextFragmentInterface;
use VanillaTests\BootstrapTrait;
use VanillaTests\SetupTraitsTrait;
use VanillaTests\VanillaTestCase;

/**
 * Verify basic behavior of the TextFragmentCollection class.
 */
class TextFragmentCollectionTest extends VanillaTestCase
{
    use BootstrapTrait, SetupTraitsTrait;

    /**
     * @inheritDoc
     */
    public function setUp(): void
    {
        $this->setUpTestTraits();
    }

    /**
     * Verify adherence to the TextFragmentCollectionInterface contract.
     */
    public function testGetFragments(): void
    {
        $fragments = [
            "foo" => new BlotPointerTextFragment(new TextBlot([], [], []), "foo"),
            "bar" => new BlotPointerTextFragment(new TextBlot([], [], []), "bar"),
            "baz" => new BlotPointerTextFragment(new TextBlot([], [], []), "baz"),
        ];
        $collection = new TextFragmentcollection($fragments);
        $this->assertSame(array_values($fragments), $collection->getFragments());
    }

    /**
     * Verify ability to use array access to determine a fragment's existence.
     */
    public function testOffsetExists(): void
    {
        $blot = new TextBlot(["foo" => "bar"], [], []);
        $fragment = new BlotPointerTextFragment($blot, "foo");
        $collection = new TextFragmentCollection(["baz" => $fragment]);
        $this->assertTrue(isset($collection["baz"]));
        $this->assertFalse(isset($collection["badOffset"]));
    }

    /**
     * Verify ability to use array access to obtain a fragment from the collection.
     */
    public function testOffsetGet(): void
    {
        $blot = new TextBlot(["foo" => "bar"], [], []);
        $expected = new BlotPointerTextFragment($blot, "foo");
        $collection = new TextFragmentCollection(["baz" => $expected]);
        $this->assertSame($expected, $collection["baz"]);
        $this->assertNull($collection["badOffset"]);
    }

    /**
     * Verify ability to use array access to add a fragment to the collection.
     */
    public function testOffsetSet(): void
    {
        $blot = new TextBlot(["foo" => "bar"], [], []);
        $expected = new BlotPointerTextFragment($blot, "foo");
        $collection = new TextFragmentCollection();
        $collection["baz"] = $expected;
        $this->assertSame($expected, $collection["baz"]);
    }

    /**
     * Verify attempting to set an invalid fragment value in the collection will throw the proper exception.
     */
    public function testOffsetSetInvalidValue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Value must be an instance of " . TextFragmentInterface::class);
        $collection = new TextFragmentCollection();
        $collection["baz"] = "Hello world.";
    }

    /**
     * Verify ability to use array access for removing an item from the collection.
     */
    public function testOffsetUnset(): void
    {
        $blot = new TextBlot(["foo" => "bar"], [], []);
        $pointer = new BlotPointerTextFragment($blot, "foo");
        $collection = new TextFragmentCollection(["baz" => $pointer]);
        $this->assertSame($pointer, $collection["baz"]);
        unset($collection["baz"]);
        $this->assertNull($collection["baz"]);
    }
}
