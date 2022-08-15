<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Library;

use VanillaTests\BootstrapTestCase;

class EmailTemplateTest extends BootstrapTestCase
{
    /**
     * Tests that various properties are sanitized by converting special characters
     * to HTML entities
     */
    public function testPropertiesSanitized()
    {
        $evilString = '<a href="https://vulnerableurl.com"></a>';
        $expected = htmlspecialchars($evilString);
        $emailTemplate = new \EmailTemplate();

        $emailTemplate->setTextColor($evilString);
        $this->assertSame($expected, $emailTemplate->getTextColor());

        $emailTemplate->setBackgroundColor($evilString);
        $this->assertSame($expected, $emailTemplate->getBackgroundColor());

        $emailTemplate->setContainerBackgroundColor($evilString);
        $this->assertSame($expected, $emailTemplate->getContainerBackgroundColor());

        $emailTemplate->setImage($evilString, $evilString, $evilString);
        $this->assertSame($expected, $emailTemplate->getImage()["source"]);
        $this->assertSame($expected, $emailTemplate->getImage()["link"]);
        $this->assertSame($expected, $emailTemplate->getImage()["alt"]);

        $emailTemplate->setFooter($evilString, $evilString, $evilString);
        $this->assertSame($expected, $emailTemplate->getFooter()["text"]);
        $this->assertSame($expected, $emailTemplate->getFooter()["textColor"]);
        $this->assertSame($expected, $emailTemplate->getFooter()["backgroundColor"]);
    }
}
