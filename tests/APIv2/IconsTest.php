<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv2;

use VanillaTests\SiteTestCase;

/**
 * Test /api/v2/icons and related functions.
 */
class IconsTest extends SiteTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->resetTable("icon");
    }

    /**
     * Test listing active icons
     *
     * @return void
     */
    public function testListActiveIcons()
    {
        $icons = $this->api()
            ->get("/icons/active")
            ->getBody();
        $this->assertNotEmpty($icons, "No active icons were found.");

        $inactiveIconsNames = [];
        foreach ($icons as $icon) {
            if (!$icon["isActive"]) {
                $inactiveIconsNames[] = $icon["iconName"];
            }
        }

        $this->assertEmpty(
            $inactiveIconsNames,
            "Active icons API should not contain inactive icons. Inactive icons found: " .
                implode(", ", $inactiveIconsNames)
        );
    }

    /**
     * Test listing icons by their name.
     *
     * @return void
     */
    public function testIconsByName()
    {
        // First test the 404.
        $this->api()
            ->get(
                "/icons/by-name",
                query: [
                    "iconName" => "bad-name",
                ],
                options: ["throw" => false]
            )
            ->assertStatus(404);

        // Now let's make sure we get just the original icon
        $this->api()
            ->get("/icons/by-name", ["iconName" => "ai-indicator"])
            ->assertSuccess()
            ->assertJsonArrayValues([
                "iconName" => ["ai-indicator"],
                "isCustom" => [false],
            ]);
    }

    /**
     * Test validation errors for overriding icons.
     *
     * @param string $svgRaw
     * @param string $expectedError
     *
     * @return void
     *
     * @dataProvider provideInvalidIcons
     */
    public function testOverrideValidationErrors(string $svgRaw, string $expectedError)
    {
        $this->expectExceptionCode(422);
        $this->expectExceptionMessage($expectedError);
        $this->api()->post("/icons/override", [
            "iconOverrides" => [
                [
                    "iconName" => "ai-indicator",
                    "svgRaw" => $svgRaw,
                ],
            ],
        ]);
    }

    /**
     * @return array[]
     */
    public function provideInvalidIcons()
    {
        return [
            "invalid HTML" => ["<div <span >div><s", "not a valid SVG"],
            "no svg" => ["<div>Hello world</div>", "not a valid SVG"],
        ];
    }

    /**
     * Test overriding and deleting an icon.
     *
     * @return void
     */
    public function testOverrideAndDelete()
    {
        $overrideSvg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" style="transform: rotate(90deg);z-index: 5">
<circle r="12" cx="12" cy="12" fill="#000"/>
</svg>
SVG;

        $overrideUUID = $this->api()
            ->post("/icons/override", [
                "iconOverrides" => [
                    [
                        "iconName" => "ai-indicator",
                        "svgRaw" => $overrideSvg,
                    ],
                ],
            ])
            ->assertSuccess()
            ->getBody()[0]["iconUUID"];

        $this->assertActiveIcon("ai-indicator", [
            "isCustom" => true,
        ]);

        // Icon ID not found
        $this->api()
            ->delete("/icons/bad-uuid", options: ["throw" => false])
            ->assertStatus(404);

        // Icon is active and can't be deleted
        $this->api()
            ->delete("/icons/{$overrideUUID}", options: ["throw" => false])
            ->assertStatus(400);

        // Now let's reactivate the system one.
        $this->api()
            ->post("/icons/restore", [
                "restorations" => [
                    [
                        "iconName" => "ai-indicator",
                        "iconUUID" => "ai-indicator",
                    ],
                ],
            ])
            ->assertSuccess();

        $this->assertActiveIcon("ai-indicator", [
            "isCustom" => false,
        ]);

        // We should be able to delete our icon now.
        $this->api()
            ->delete("/icons/{$overrideUUID}")
            ->assertSuccess();
    }

    /**
     * Test overriding icons.
     *
     * @return void
     */
    public function testOverrideAndRestoreIcon()
    {
        $overrideSvg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" style="transform: rotate(90deg);z-index: 5">
<circle r="12" cx="12" cy="12" fill="#000000"/>
</svg>
SVG;
        $overrideSvg2 = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" id="icon2" viewBox="0 0 24 24" fill="none" style="transform: rotate(90deg);z-index: 5">
<circle r="12" cx="12" cy="12" fill="#000000"/>
</svg>
SVG;

        $overrideUUID = $this->api()
            ->post("/icons/override", [
                "iconOverrides" => [
                    [
                        "iconName" => "ai-indicator",
                        "svgRaw" => $overrideSvg,
                    ],
                ],
            ])
            ->assertSuccess()
            ->getBody()[0]["iconUUID"];

        $override2UUID = $this->api()
            ->post("/icons/override", [
                "iconOverrides" => [
                    [
                        "iconName" => "ai-indicator",
                        "svgRaw" => $overrideSvg2,
                    ],
                ],
            ])
            ->assertSuccess()
            ->getBody()[0]["iconUUID"];

        // Now we should get this icon back from the active list
        $this->assertActiveIcon("ai-indicator", [
            "svgRaw" => $overrideSvg2,
            "svgContents" => '<circle r="12" cx="12" cy="12" fill="currentColor"></circle>',
            "svgAttributes" => [
                "fill" => "none",
                "style" => [
                    "transform" => "rotate(90deg)",
                    "z-index" => "5",
                ],
                "xmlns" => "http://www.w3.org/2000/svg",
                "viewBox" => "0 0 24 24",
            ],
            "isCustom" => true,
            "isActive" => true,
        ]);

        // Icon should come back from by-names endpoint too.
        $this->api()
            ->get("/icons/by-name", ["iconName" => "ai-indicator"])
            ->assertSuccess()
            ->assertJsonArrayContains(
                [
                    "svgRaw" => $overrideSvg,
                    "isCustom" => true,
                    "isActive" => false,
                ],
                "Expected endpoint to return exactly 1 inactive custom icon."
            )
            ->assertJsonArrayContains(
                [
                    "svgRaw" => $overrideSvg2,
                    "isCustom" => true,
                    "isActive" => true,
                ],
                "Expected endpoint to return exactly 1 active custom icon."
            )
            ->assertJsonArrayContains(
                [
                    "isCustom" => false,
                    "isActive" => false,
                ],
                "Expected endpoint to return exactly 1 inactive core icon."
            );

        // Test restoration
        $this->api()
            ->post("/icons/restore", [
                "restorations" => [
                    [
                        "iconName" => "ai-indicator",
                        "iconUUID" => $overrideUUID,
                    ],
                ],
            ])
            ->assertSuccess();

        // Now we should get this icon back from the active list
        $this->assertActiveIcon("ai-indicator", [
            "svgRaw" => $overrideSvg,
            "iconUUID" => $overrideUUID,
            "isCustom" => true,
            "isActive" => true,
        ]);
    }

    /**
     * Assert certain fields about the active icon for a particular name.
     *
     * @param string $iconName
     * @param array $expectedFields
     * @return void
     */
    private function assertActiveIcon(string $iconName, array $expectedFields): void
    {
        $icons = $this->api()
            ->get("/icons/active")
            ->getBody();
        $icon = array_find($icons, fn($icon) => $icon["iconName"] === $iconName);
        $this->assertNotEmpty($icon, "Icon $iconName not found in active icons.");
        foreach ($expectedFields as $field => $expectedValue) {
            $this->assertEquals($expectedValue, $icon[$field], "Icon $iconName field $field does not match.");
        }
    }
}
