<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv2;

use Ramsey\Uuid\Uuid;
use Vanilla\Dashboard\Controllers\API\LayoutsApiController;
use Vanilla\Dashboard\Models\FragmentModel;
use Vanilla\Layout\LayoutHydrator;
use Vanilla\Theme\Asset\JsonThemeAsset;
use Vanilla\Theme\FsThemeProvider;
use Vanilla\Theme\ThemeService;
use Vanilla\Web\CacheControlConstantsInterface;
use Vanilla\Widgets\Fragments\CallToActionFragmentMeta;
use Vanilla\Widgets\React\CustomFragmentWidget;
use VanillaTests\DatabaseTestTrait;
use VanillaTests\Fixtures\TestUploader;
use VanillaTests\Fixtures\Theme\MockThemeProvider;
use VanillaTests\SiteTestCase;

/**
 * Tests for /api/v2/fragments and related functionality.
 */
class FragmentsTest extends SiteTestCase
{
    use DatabaseTestTrait;

    protected MockThemeProvider $mockThemeProvider;

    /**
     * @inheritdoc
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->mockThemeProvider = self::container()->get(MockThemeProvider::class);

        $themeService = self::container()->get(ThemeService::class);
        $themeService->clearThemeProviders();
        $themeService->addThemeProvider(self::container()->get(FsThemeProvider::class));
        $themeService->addThemeProvider($this->mockThemeProvider);
    }

    /**
     * Get all the data needed to create a fragment.
     *
     * @param array $overrides
     *
     * @return array
     */
    private function baselineFragment(array $overrides = []): array
    {
        return array_merge(
            [
                "name" => "Test Fragment",
                "fragmentType" => "CallToActionFragment",
                "js" => "console.log('hello world')",
                "jsRaw" => "console.log('hello world')",
                "css" => "body { background-color: red; }",
                "previewData" => [
                    [
                        "name" => "Preview 1",
                        "description" => "This is a preview",
                        "previewDataUUID" => Uuid::uuid7()->toString(),
                        "data" => [
                            "hello" => "world",
                        ],
                    ],
                ],
                "files" => [],
            ],
            $overrides
        );
    }

    /**
     * Test creation of a new fragment.
     *
     * @return void
     */
    public function testCreateNewFragment(): void
    {
        $body = $this->baselineFragment();
        $this->api()
            ->post("/fragments", $body)
            ->assertStatus(201)
            ->assertJsonObjectLike($body);
    }

    /**
     * Test updating a fragment and commiting it.
     *
     * @return void
     */
    public function testPatchDraftAndCommit(): void
    {
        // Test creating of an initial draft
        $fragment = $this->api()->post("/fragments", $this->baselineFragment());

        $patched = $this->api()
            ->patch("/fragments/{$fragment["fragmentUUID"]}", [
                "js" => "console.log('goodbye world')",
                "jsRaw" => "console.log('goodbye world')",
            ])
            ->assertJsonObjectLike([
                "js" => "console.log('goodbye world')",
                "jsRaw" => "console.log('goodbye world')",

                // Should still be the same.
                "css" => "body { background-color: red; }",

                "status" => FragmentModel::STATUS_DRAFT,
                "isLatest" => true,
            ])
            ->getBody();

        $this->assertNotEquals($fragment["fragmentRevisionUUID"], $patched["fragmentRevisionUUID"]);

        // Test patching an existing draft

        $this->api()
            ->patch("/fragments/{$fragment["fragmentUUID"]}", [
                "js" => "console.log('goodbye world 2')",
                "jsRaw" => "console.log('goodbye world 2')",
            ])
            ->assertJsonObjectLike([
                "js" => "console.log('goodbye world 2')",
                "jsRaw" => "console.log('goodbye world 2')",

                // Should still be the same.
                "css" => "body { background-color: red; }",
                // Existing draft should have been patched.
                "fragmentRevisionUUID" => $patched["fragmentRevisionUUID"],
                "status" => FragmentModel::STATUS_DRAFT,
                "isLatest" => true,
            ]);

        // When fetching from the endpoint we can get either the latest or the active
        $this->api()
            ->get("/fragments/{$fragment["fragmentUUID"]}", ["status" => "latest"])
            ->assertJsonObjectLike([
                "fragmentRevisionUUID" => $patched["fragmentRevisionUUID"],
            ]);

        $this->api()
            ->get("/fragments/{$fragment["fragmentUUID"]}", ["status" => FragmentModel::STATUS_ACTIVE])
            ->assertJsonObjectLike([
                "fragmentRevisionUUID" => $fragment["fragmentRevisionUUID"],
            ]);

        // Now we can commit that draft
        $committed = $this->api()
            ->post("/fragments/{$fragment["fragmentUUID"]}/commit-revision", [
                "fragmentRevisionUUID" => $patched["fragmentRevisionUUID"],
                "commitMessage" => "Hello commit",
            ])
            ->assertJsonObjectLike([
                "fragmentRevisionUUID" => $patched["fragmentRevisionUUID"],
                "status" => FragmentModel::STATUS_ACTIVE,
                "isLatest" => true,
                "commitMessage" => "Hello commit",
            ])
            ->getBody();

        // The commit should be active and the latest.
        $this->api()
            ->get("/fragments/{$fragment["fragmentUUID"]}")
            ->assertSuccess()
            ->assertJsonObjectLike([
                "status" => FragmentModel::STATUS_ACTIVE,
                "isLatest" => true,
                "fragmentRevisionUUID" => $committed["fragmentRevisionUUID"],
            ]);

        // Make one more draft for revisions
        $draft2 = $this->api()
            ->patch("/fragments/{$fragment["fragmentUUID"]}", [
                "js" => "console.log('goodbye world 3')",
                "jsRaw" => "console.log('goodbye world 3')",
            ])
            ->assertSuccess();

        // Now we can see the commits in revisions
        $this->api()
            ->get("/fragments/{$fragment["fragmentUUID"]}/revisions")
            ->assertJsonArrayValues([
                "status" => [
                    FragmentModel::STATUS_DRAFT,
                    FragmentModel::STATUS_ACTIVE,
                    FragmentModel::STATUS_PAST_REVISION,
                ],
                "fragmentRevisionUUID" => [
                    $draft2["fragmentRevisionUUID"],
                    $committed["fragmentRevisionUUID"],
                    $fragment["fragmentRevisionUUID"],
                ],
            ]);
    }

    /**
     * Test updating a fragment and commiting at the same time.
     *
     * @return void
     */
    public function testCommitThroughPatch(): void
    {
        // Test that we can commit directly as patch.
        $fragment = $this->api()
            ->post("/fragments", $this->baselineFragment())
            ->assertSuccess()
            ->getBody();
        $patched = $this->api()
            ->patch("/fragments/{$fragment["fragmentUUID"]}", [
                "css" => "body { background-color: blue; }",
                "commitMessage" => "Hello commit",
                "commitDescription" => "Hello description",
            ])
            ->assertSuccess()
            ->assertJsonObjectLike([
                "css" => "body { background-color: blue; }",
                "status" => FragmentModel::STATUS_ACTIVE,
                "commitMessage" => "Hello commit",
                "commitDescription" => "Hello description",
                "isLatest" => true,
            ])
            ->getBody();

        $this->assertNotEquals($fragment["fragmentRevisionUUID"], $patched["fragmentRevisionUUID"]);

        // The old commit should be inactive.
        $this->api()
            ->get("/fragments/{$fragment["fragmentUUID"]}", [
                "fragmentRevisionUUID" => $fragment["fragmentRevisionUUID"],
            ])
            ->assertSuccess()
            ->assertJsonObjectLike(
                array_merge($fragment, [
                    "status" => FragmentModel::STATUS_PAST_REVISION,
                    "isLatest" => false,
                ])
            );

        // Fetching the fragment should return the new commit.
        $this->api()
            ->get("/fragments/{$fragment["fragmentUUID"]}")
            ->assertSuccess()
            ->assertJsonObjectLike([
                "fragmentRevisionUUID" => $patched["fragmentRevisionUUID"],
            ]);
    }

    /**
     * Test deleting of a fragment.
     *
     * @return void
     */
    public function testDelete(): void
    {
        $originalFragment = $this->api()
            ->post("/fragments", $this->baselineFragment())
            ->assertSuccess()
            ->getBody();

        $draftFragment = $this->api()
            ->patch("/fragments/{$originalFragment["fragmentUUID"]}", [
                "js" => "console.log('goodbye world')",
                "jsRaw" => "console.log('goodbye world')",
            ])
            ->assertSuccess()
            ->getBody();

        // We can delete a draft fragment
        $this->api()
            ->delete(
                "/fragments/{$draftFragment["fragmentUUID"]}",
                [
                    "fragmentRevisionUUID" => $draftFragment["fragmentRevisionUUID"],
                ],
                options: ["throw" => false]
            )
            ->assertSuccess();

        // Latest fragment should now be back to the active fragment
        $this->api()
            ->get("/fragments/{$draftFragment["fragmentUUID"]}", ["status" => "latest"])
            ->assertSuccess()
            ->assertJsonObjectLike([
                "fragmentRevisionUUID" => $originalFragment["fragmentRevisionUUID"],
            ]);

        // We can't delete a non-existant fragment
        $this->api()
            ->delete("/fragments/not-a-real-fragment-uuid", options: ["throw" => false])
            ->assertStatus(404);

        // We can't delete a non-draft fragment
        $this->api()
            ->delete(
                "/fragments/{$originalFragment["fragmentUUID"]}",
                [
                    "fragmentRevisionUUID" => $originalFragment["fragmentRevisionUUID"],
                ],
                options: ["throw" => false]
            )
            ->assertStatus(400);

        // We can delete the whole fragment
        $this->api()
            ->delete("/fragments/{$originalFragment["fragmentUUID"]}")
            ->assertSuccess();

        $this->api()
            ->get("/fragments/{$originalFragment["fragmentUUID"]}", options: ["throw" => false])
            ->assertStatus(404);
    }

    /**
     * Test that any files are associated with the fragment on post/patch
     *
     * @return void
     */
    public function testPostPatchAssosciatesMedia(): void
    {
        $photo = TestUploader::uploadFile("photo", PATH_ROOT . "/tests/fixtures/apple.jpg");

        $mediaItem = $this->api()
            ->post("/media", [
                "file" => $photo,
                "type" => "image",
            ])
            ->getBody();

        $fragment = $this->api()
            ->post(
                "/fragments",
                $this->baselineFragment([
                    "files" => [$mediaItem],
                ])
            )
            ->assertJsonObjectLike([
                "files" => [$mediaItem],
            ])
            ->getBody();

        $this->api()
            ->get("/media/{$mediaItem["mediaID"]}")
            ->assertSuccess()
            ->assertJsonObjectLike([
                "foreignType" => "fragment",
                "foreignID" => $fragment["fragmentUUID"],
            ]);

        $photo = TestUploader::uploadFile("photo", PATH_ROOT . "/tests/fixtures/apple.jpg");
        $mediaItem2 = $this->api()
            ->post("/media", [
                "file" => $photo,
                "type" => "image",
            ])
            ->getBody();

        $patched = $this->api()
            ->patch("/fragments/{$fragment["fragmentUUID"]}", [
                "files" => [$mediaItem2],
            ])
            ->assertSuccess();

        $this->api()
            ->get("/media/{$mediaItem2["mediaID"]}")
            ->assertSuccess()
            ->assertJsonObjectLike([
                "foreignType" => "fragment",
                "foreignID" => $fragment["fragmentUUID"],
            ]);
    }

    /**
     * Test behaviour of a fragment applied in a theme.
     *
     * @return void
     */
    public function testAppliedTheme(): void
    {
        $foundationAddon = \Gdn::addonManager()->lookupTheme("theme-foundation");

        $fragment = $this->api()
            ->post(
                "/fragments",
                $this->baselineFragment([
                    "fragmentType" => "TitleBarFragment",
                ])
            )
            ->getBody();
        $deletedFragment = $this->api()
            ->post("/fragments", $this->baselineFragment())
            ->getBody();
        $this->api()->delete("/fragments/{$deletedFragment["fragmentUUID"]}");

        $this->mockThemeProvider->addTheme(
            [
                "name" => "My Mock Theme",
                "themeID" => "mock-theme",
                "assets" => [
                    "variables" => new JsonThemeAsset(
                        json_encode([
                            "globalFragmentImpls" => [
                                "TitleBarFragment" => [
                                    "fragmentUUID" => $fragment["fragmentUUID"],
                                ],
                                "CallToActionFragment" => [
                                    "fragmentUUID" => $deletedFragment["fragmentUUID"],
                                ],
                            ],
                        ]),
                        ""
                    ),
                ],
            ],
            $foundationAddon
        );

        // Test that we get a fragment view from the theme.
        $this->api()
            ->get("/fragments/{$fragment["fragmentUUID"]}")
            ->assertSuccess()
            ->assertJsonObjectLike(
                [
                    "status" => FragmentModel::STATUS_ACTIVE,
                    "isLatest" => true,
                    "isApplied" => true,
                    "fragmentViews.0.recordName" => "My Mock Theme",
                ],
                "Fragment view should be applied from the theme."
            );

        // Now when fetching the theme the fragment metadata should be expanded
        $theme = $this->api()
            ->get("/themes/mock-theme?expand=all")
            ->assertSuccess()
            ->assertJsonObjectLike(
                [
                    "assets.variables.data.globalFragmentImpls.TitleBarFragment" => [
                        "fragmentUUID" => $fragment["fragmentUUID"],
                        "fragmentRevisionUUID" => $fragment["fragmentRevisionUUID"],
                        "jsUrl" => $fragment["jsUrl"],
                        "cssUrl" => $fragment["cssUrl"],
                        "css" => $fragment["css"],
                    ],
                    // Should be back to system because it's deleted.
                    "assets.variables.data.globalFragmentImpls.CallToActionFragment" => [
                        "fragmentUUID" => "system",
                    ],
                ],
                "Theme response should expand fragment metadata."
            );

        // Loading a page with this theme should
        // Script should be preloaded on legacy controllers
        $this->runWithConfig(
            [
                "Garden.Theme" => "mock-theme",
                "Feature.customLayout.discussionList.Enabled" => false,
            ],
            function () use ($fragment) {
                $this->assertModulePreloaded("/discussions", [$fragment["jsUrl"]]);
            }
        );

        $this->runWithConfig(
            [
                "Garden.Theme" => "mock-theme",
                "Feature.customLayout.discussionList.Enabled" => true,
            ],
            function () use ($fragment) {
                $this->assertModulePreloaded("/discussions", [$fragment["jsUrl"]]);
            }
        );
    }

    /**
     * Test that a fragments used in layouts appear as active.
     *
     * @return void
     */
    public function testAppliedLayout(): void
    {
        $this->resetTable("fragment");
        $notAppliedFragment = $this->api()
            ->post("/fragments", $this->baselineFragment())
            ->getBody();
        $fragment = $this->api()
            ->post("/fragments", $this->baselineFragment())
            ->getBody();

        $deletedFragment = $this->api()
            ->post("/fragments", $this->baselineFragment())
            ->getBody();
        $this->api()->delete("/fragments/{$deletedFragment["fragmentUUID"]}");

        $layout = $this->api()->post("/layouts", [
            "layoutViewType" => "home",
            "name" => "My Home",
            "layout" => [
                [
                    '$hydrate' => "react.section.1-column",
                    "children" => [
                        [
                            // Assets should be available.
                            '$hydrate' => "react.cta",
                            '$fragmentImpls' => [
                                "CallToActionFragment" => [
                                    "fragmentUUID" => $fragment["fragmentUUID"],
                                ],
                            ],
                        ],
                        [
                            // Assets should be available.
                            '$hydrate' => "react.cta",
                            '$fragmentImpls' => [
                                "CallToActionFragment" => [
                                    "fragmentUUID" => $deletedFragment["fragmentUUID"],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $this->api()
            ->get("/fragments/{$fragment["fragmentUUID"]}")
            ->assertSuccess()
            ->assertJsonObjectLike(
                [
                    "status" => FragmentModel::STATUS_ACTIVE,
                    "isLatest" => true,
                    "isApplied" => true,
                    "fragmentViews.0.recordName" => "My Home",
                ],
                "Fragment view should be applied from the layout."
            );

        // Apply the home layout
        $this->api()->put("/layouts/{$layout["layoutID"]}/views", [["recordID" => -1, "recordType" => "global"]]);

        // Ensure the expanded layout has the fragment URLs.
        $layout = $this->api()
            ->get("/layouts/{$layout["layoutID"]}/hydrate")
            ->assertSuccess()
            ->assertJsonObjectLike(
                [
                    'layout.0.$reactProps.children.0.$reactComponent' => "CallToActionWidget",
                    'layout.0.$reactProps.children.0.$fragmentImpls' => [
                        "CallToActionFragment" => [
                            "fragmentUUID" => $fragment["fragmentUUID"],
                            "fragmentRevisionUUID" => $fragment["fragmentRevisionUUID"],
                            "jsUrl" => $fragment["jsUrl"],
                            "cssUrl" => $fragment["cssUrl"],
                            "css" => $fragment["css"],
                        ],
                    ],
                    'layout.0.$reactProps.children.1.$reactComponent' => "CallToActionWidget",
                    // Should be empty as the fragment is deleted.
                    'layout.0.$reactProps.children.1.$fragmentImpls' => null,
                ],
                "Layout response should expand fragment metadata."
            );

        // Ensure our modules were preloaded
        $this->assertModulePreloaded("/", [$fragment["jsUrl"]]);

        // The fragment should be returned as applied
        $this->api()
            ->get("/fragments", ["appliedStatus" => "applied"])
            ->assertSuccess()
            ->assertJsonArrayValues([
                // Notably the not-applied fragment is excluded
                "fragmentUUID" => [$fragment["fragmentUUID"]],
            ]);

        $this->api()
            ->get("/fragments", ["appliedStatus" => "not-applied"])
            ->assertSuccess()
            ->assertJsonArrayValues([
                // Notably the not-applied fragment is excluded
                "fragmentUUID" => [$notAppliedFragment["fragmentUUID"]],
            ]);
    }

    /**
     * Assert that certain modules are preloaded.
     *
     * @param string $pageUrl
     * @param array $expectedPreloads
     *
     * @return void
     */
    private function assertModulePreloaded(string $pageUrl, array $expectedPreloads): void
    {
        $html = $this->bessy()->getHtml(
            $pageUrl,
            options: [
                "deliveryType" => DELIVERY_TYPE_ALL,
            ]
        );
        $preloadLinks = $html->queryCssSelector("link[rel=modulepreload]");
        $preloadedModuleUrls = [];
        /** @var \DOMElement $link */
        foreach ($preloadLinks as $link) {
            $preloadedModuleUrls[] = $link->getAttribute("href");
        }

        $actualPreloads = json_encode($preloadedModuleUrls, JSON_PRETTY_PRINT);
        foreach ($expectedPreloads as $expectedPreload) {
            $this->assertContains(
                $expectedPreload,
                $preloadedModuleUrls,
                "Expected script '{$expectedPreload}' module to be preloaded. Instead only found $actualPreloads"
            );
        }
    }

    /**
     * @return void
     */
    public function testList(): void
    {
        $this->resetTable("fragment");

        $fragment1 = $this->api()
            ->post("/fragments", $this->baselineFragment(["name" => "Fragment 1"]))
            ->getBody();
        $fragment2 = $this->api()
            ->post("/fragments", $this->baselineFragment(["name" => "Fragment 2"]))
            ->getBody();

        $fragment1a = $this->api()
            ->patch("/fragments/{$fragment1["fragmentUUID"]}", ["name" => "Fragment 1a"])
            ->getBody();

        $deletedFragment = $this->api()
            ->post("/fragments", $this->baselineFragment(["name" => "Deleted Fragment"]))
            ->getBody();
        $this->api()->delete("/fragments/{$deletedFragment["fragmentUUID"]}");

        $titleBarFragment = $this->api()
            ->post("/fragments", $this->baselineFragment(["name" => "TitleBar", "fragmentType" => "TitleBarFragment"]))
            ->getBody();

        // We can filter chose active or latest versions of fragments
        $this->api()
            ->get("/fragments", ["status" => FragmentModel::STATUS_ACTIVE])
            ->assertJsonArrayValues([
                "name" => ["Fragment 1", "Fragment 2", "TitleBar"],
            ]);
        $this->api()
            ->get("/fragments", ["status" => "latest"])
            ->assertJsonArrayValues([
                "name" => ["Fragment 1a", "Fragment 2", "TitleBar"],
            ]);

        // We can filter by fragment type
        $this->api()
            ->get("/fragments", ["fragmentType" => "TitleBarFragment"])
            ->assertJsonArrayValues([
                "name" => ["TitleBar"],
            ]);
    }

    /**
     * Test that fetching css or js without a fragmentRevisionUUID returns a redirect.
     *
     * @return void
     */
    public function testGetJsAndCssRedirect(): void
    {
        $fragment = $this->api()->post("/fragments", $this->baselineFragment());

        // notably the fragment draft revision is not used.
        $fragmentDraft = $this->api()->patch("/fragments/{$fragment["fragmentUUID"]}", [
            "js" => "console.log('goodbye world')",
        ]);

        $this->assertRedirectsTo($fragment["jsUrl"], 302, function () use ($fragment) {
            $this->api()->get("/fragments/{$fragment["fragmentUUID"]}/js");
        });

        $this->assertRedirectsTo($fragment["cssUrl"], 302, function () use ($fragment) {
            $this->api()->get("/fragments/{$fragment["fragmentUUID"]}/css");
        });
    }

    /**
     * Test that getting the fragment css and js
     *
     * @return void
     */
    public function testGetJsAndCssCached(): void
    {
        $fragment = $this->api()->post("/fragments", $this->baselineFragment());

        $js = $this->api()
            ->get($fragment["jsUrl"])
            ->assertSuccess()
            ->assertHeader("Content-Type", "application/javascript")
            ->assertHeader("Cache-Control", CacheControlConstantsInterface::MAX_CACHE);
        $this->assertEquals($fragment["js"], $js->getBody());

        $css = $this->api()
            ->get($fragment["cssUrl"])
            ->assertSuccess()
            ->assertHeader("Content-Type", "text/css")
            ->assertHeader("Cache-Control", CacheControlConstantsInterface::MAX_CACHE);
        $this->assertEquals($fragment["css"], $css->getBody());
    }

    /**
     * Test that creating new revisions and drafts doesn't touch deleted fragments.
     *
     * @return void
     */
    public function testCommitAndPatchDontClearDeletes(): void
    {
        $fragment = $this->api()->post("/fragments", $this->baselineFragment());
        $deletedDraft = $this->api()->patch("/fragments/{$fragment["fragmentUUID"]}", [
            "js" => "console.log('goodbye world')",
        ]);
        $this->api()->delete("/fragments/{$fragment["fragmentUUID"]}", [
            "fragmentRevisionUUID" => $deletedDraft["fragmentRevisionUUID"],
        ]);
        $patchedCommit = $this->api()->patch("/fragments/{$fragment["fragmentUUID"]}", [
            "js" => "console.log('goodbye world 2')",
        ]);
        $patchedCommit = $this->api()->post("/fragments/{$fragment["fragmentUUID"]}/commit-revision", [
            "fragmentRevisionUUID" => $patchedCommit["fragmentRevisionUUID"],
            "commitMessage" => "update the thing",
        ]);
        $directCommit = $this->api()->patch("/fragments/{$fragment["fragmentUUID"]}", [
            "js" => "console.log('goodbye world 3')",
            "commitMessage" => "update the thing again",
        ]);

        $rows = self::container()
            ->get(FragmentModel::class)
            ->select([
                "fragmentUUID" => $fragment["fragmentUUID"],
            ]);

        $this->assertRowsLike(
            [
                "fragmentRevisionUUID" => [
                    $fragment["fragmentRevisionUUID"],
                    $deletedDraft["fragmentRevisionUUID"],
                    $patchedCommit["fragmentRevisionUUID"],
                    $directCommit["fragmentRevisionUUID"],
                ],
                "status" => [
                    FragmentModel::STATUS_PAST_REVISION,
                    FragmentModel::STATUS_DELETED,
                    FragmentModel::STATUS_PAST_REVISION,
                    FragmentModel::STATUS_ACTIVE,
                ],
            ],
            $rows,
            strictOrder: true,
            count: 4
        );
    }

    /**
     * Test that custom fragments appear in the layout catalog.
     *
     * @return void
     */
    public function testCustomFragmentsAddedAsLayoutCatalog(): void
    {
        $fragment = $this->api()->post(
            "/fragments",
            $this->baselineFragment([
                "fragmentType" => "CustomFragment",
                "name" => "My Special Fragment",
                "customSchema" => [
                    "type" => "object",
                    "properties" => [
                        "mytitle" => [
                            "type" => "string",
                        ],
                    ],
                ],
            ])
        );

        // Clear out cached container stuff
        self::container()->setInstance(LayoutHydrator::class, null);
        self::container()->setInstance(LayoutsApiController::class, null);

        $catalog = $this->api()
            ->get("/layouts/catalog")
            ->getBody();

        $fragmentWidget = $catalog["widgets"]["react.custom.{$fragment["fragmentUUID"]}"] ?? null;
        $this->assertNotNull($fragmentWidget, "Fragment should be in the catalog");
        $this->assertArraySubsetRecursive(
            [
                "\$reactComponent" => CustomFragmentWidget::getComponentName(),
                "name" => $fragment["name"],
                "schema" => $fragment["customSchema"],
                "widgetGroup" => "Custom",
            ],
            $fragmentWidget
        );
    }

    /**
     * Test that fragment impls is available or not available on a widget depending on defined fragments.
     *
     * @return void
     */
    public function testFragmentLayoutSchemaOnlyIfFragmentsExist(): void
    {
        $this->resetTable("fragment");
        $catalog = $this->api()
            ->get("/layouts/catalog")
            ->getBody();

        $ctaWidget = $catalog["widgets"]["react.cta"] ?? null;
        $this->assertNotNull($ctaWidget, "CTA widget should be in the catalog");

        $fragmentImpls = $ctaWidget["schema"]["properties"]["\$fragmentImpls"] ?? null;
        $this->assertNull($fragmentImpls, "Fragment impls should not be in the schema if no fragments exist for it.");

        // Now create a CTA fragment
        $this->api()->post("/fragments", $this->baselineFragment(["fragmentType" => "CallToActionFragment"]));
        $catalog = $this->api()
            ->get("/layouts/catalog")
            ->getBody();
        $ctaWidget = $catalog["widgets"]["react.cta"] ?? null;

        $fragmentImpls = $ctaWidget["schema"]["properties"]["\$fragmentImpls"] ?? null;
        $this->assertNotNull(
            $fragmentImpls,
            "Fragment impls should not be in the schema if no fragments exist for it."
        );
        $this->assertArrayHasKey(
            CallToActionFragmentMeta::getFragmentType(),
            $fragmentImpls["properties"],
            "Fragment impls should have the CTA fragment type."
        );
    }
}
