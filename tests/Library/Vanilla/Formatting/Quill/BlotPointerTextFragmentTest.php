<?php
/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Formatting\Quill;

use Vanilla\Formatting\Quill\BlotPointerTextFragment;
use Vanilla\Formatting\Quill\Blots\TextBlot;
use Vanilla\Formatting\TextFragmentType;
use VanillaTests\BootstrapTrait;
use VanillaTests\SetupTraitsTrait;
use VanillaTests\VanillaTestCase;

/**
 * Verify basic behavior of the BlotPointerTextFragment class.
 */
class BlotPointerTextFragmentTest extends VanillaTestCase
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
     * Verify ability to get the pointer's blot content and that it matches the blot.
     */
    public function testGetInnerContent(): void
    {
        $expected = "bar";
        $blot = new TextBlot(
            [
                "data" => ["foo" => $expected],
            ],
            [],
            []
        );

        $pointer = new BlotPointerTextFragment($blot, "data.foo");

        $this->assertSame($expected, $pointer->getInnerContent());
        $this->assertSame($blot->getCurrentOperationField("data.foo"), $pointer->getInnerContent());
    }

    /**
     * Verify ability to set the inner content of a blot via the pointer.
     */
    public function testSetInnerContent(): void
    {
        $expected = "baz";
        $blot = new TextBlot(
            [
                "data" => ["foo" => "bar"],
            ],
            [],
            []
        );

        $pointer = new BlotPointerTextFragment($blot, "data.foo");
        $pointer->setInnerContent($expected);

        $this->assertSame($expected, $pointer->getInnerContent());
        $this->assertSame($blot->getCurrentOperationField("data.foo"), $pointer->getInnerContent());
    }

    /**
     * Verify ability to retrieve fragment type from a blot pointer.
     */
    public function testGetFragmentType(): void
    {
        $expected = TextFragmentType::URL;
        $blot = new TextBlot(
            [
                "data" => ["foo" => "bar"],
            ],
            [],
            []
        );

        $pointer = new BlotPointerTextFragment($blot, "data.foo", $expected);
        $this->assertSame($expected, $pointer->getFragmentType());
    }
}
