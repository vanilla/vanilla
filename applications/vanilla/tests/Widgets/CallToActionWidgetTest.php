<?php
/**
 * @copyright 2008-2022 Vanilla Forums, Inc.
 * @license Proprietary
 */

namespace VanillaTests\Forum\Widgets;

use Vanilla\ImageSrcSet\ImageSrcSet;
use Vanilla\Layout\LayoutHydrator;
use VanillaTests\SiteTestCase;
use VanillaTests\Layout\LayoutTestTrait;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Test CallToActionWidget.
 */
class CallToActionWidgetTest extends SiteTestCase
{
    use LayoutTestTrait;
    use UsersAndRolesApiTestTrait;

    /**
     * Test that we can hydrate CallToAction Widget.
     */
    public function testHydrateCalltoActionWidget()
    {
        $spec = [
            '$hydrate' => "react.cta",
            "title" => "My CallToAction Widget",
            "titleType" => "static",
            "description" => "Some description here.",
            "descriptionType" => "static",
            "button" => [
                "title" => "My Button",
                "type" => "standard",
                "url" => "https://testurl.com",
            ],
            '$reactTestID' => "cta1",
        ];

        //with background image
        $spec2 = array_merge($spec, [
            "button" => [
                "title" => "My Button",
                "url" => "https://testurl.com",
                "type" => "standard",
            ],
            "background" => [
                "image" => "https://myimage.jpg",
            ],
            '$reactTestID' => "cta2",
        ]);

        $expected = [
            '$reactComponent' => "CallToActionWidget",
            '$reactProps' => [
                "title" => "My CallToAction Widget",
                "titleType" => "static",
                "description" => "Some description here.",
                "descriptionType" => "static",
                "button" => [
                    "title" => "My Button",
                    "type" => "standard",
                    "url" => "https://testurl.com",
                ],
            ],
            '$reactTestID' => "cta1",
            '$seoContent' => <<<HTML
<div class=pageBox>
    <div class=pageHeadingBox>
        <h2>My CallToAction Widget</h2>
        <p>Some description here.</p>
    </div>
    <ul class=linkList>
        <li><a href=https://testurl.com>My Button</a></li>
    </ul>
</div>
HTML
        ,
        ];
        $expected2 = [
            '$reactComponent' => "CallToActionWidget",
            '$reactProps' => [
                "title" => "My CallToAction Widget",
                "description" => "Some description here.",
                "background" => [
                    "image" => "https://myimage.jpg",
                    "imageUrlSrcSet" => [
                        "data" => [
                            10 => "",
                            300 => "",
                            800 => "",
                            1200 => "",
                            1600 => "",
                        ],
                    ],
                ],
            ],
            '$reactTestID' => "cta2",
            '$seoContent' => <<<HTML
<div class=pageBox>
    <div class=pageHeadingBox>
        <h2>My CallToAction Widget</h2>
        <p>Some description here.</p>
    </div>
    <ul class=linkList>
        <li><a href=https://testurl.com>My Button</a></li>
    </ul>
</div>
HTML
        ,
        ];
        $this->assertHydratesTo($spec, [], $expected);

        $layoutService = self::container()->get(LayoutHydrator::class);
        $hydrator = $layoutService->getHydrator(null);
        $result = $hydrator->resolve($spec2, []);

        $this->assertInstanceOf(ImageSrcSet::class, $result["\$reactProps"]["background"]["imageUrlSrcSet"]);
        $this->assertSame(
            $expected2["\$reactProps"]["background"]["imageUrlSrcSet"]["data"],
            $result["\$reactProps"]["background"]["imageUrlSrcSet"]->jsonSerialize()
        );
    }

    /**
     * Test the guest CTA.
     */
    public function testGuestCallToActionHydrate()
    {
        $spec = [
            [
                '$hydrate' => "react.guest-cta",
                '$reactTestID' => "guestcta",
                "button" => [],
                "secondButton" => [],
            ],
        ];

        // As a signed-in user this doesn't render at all.
        $this->assertHydratesTo($spec, [], [null]);

        $expected = [
            [
                '$reactComponent' => "GuestCallToActionWidget",
                '$reactProps' => [
                    // Defaults
                    "title" => "Welcome!",
                    "titleType" => "static",
                    "description" => "It looks like you're new here. Sign in or register to get started.",
                    "descriptionType" => "static",
                    "button" => [
                        "title" => "Sign In",
                        "type" => "primary",
                    ],
                    "secondButton" => [
                        "title" => "Register",
                        "type" => "standard",
                    ],
                ],
                '$reactTestID' => "guestcta",
                '$seoContent' => <<<HTML
<div class=pageBox>
    <div class=pageHeadingBox>
        <h2>Welcome!</h2>
    <p>It looks like you&#039;re new here. Sign in or register to get started.</p>
    </div>
    <ul class=linkList>
        <li>
            <a href=https://vanilla.test/calltoactionwidgettest/entry/signin>Sign In</a>
        </li>
        <li>
            <a href=https://vanilla.test/calltoactionwidgettest/entry/register>Register</a>
        </li>
    </ul>
</div>
HTML
            ,
            ],
        ];

        $this->runWithUser(function () use ($spec, $expected) {
            $this->assertHydratesTo($spec, [], $expected);
        }, \UserModel::GUEST_USER_ID);
    }
}
