<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\Addons\Pockets;

use Garden\Schema\ValidationException;
use Vanilla\Addons\Pockets\PocketsModel;
use Vanilla\Utility\ModelUtils;
use VanillaTests\ExpectExceptionTrait;
use VanillaTests\Fixtures\MockWidgets\MockWidget1;
use VanillaTests\Fixtures\TestCache;
use VanillaTests\SiteTestCase;

/**
 * Tests for the pockets model.
 */
class PocketsModelTest extends SiteTestCase {

    use ExpectExceptionTrait;

    /** @var TestCache */
    private $cache;

    /** @var PocketsModel */
    private $pocketModel;

    /**
     * Setup.
     */
    public static function setUpBeforeClass(): void {
        self::$addons = ['vanilla', 'pockets'];
        parent::setUpBeforeClass();
    }

    /**
     * Setup.
     */
    public function setUp(): void {
        parent::setUp();
        $this->cache = $this->enableCaching();
        $this->pocketModel = self::container()->get(PocketsModel::class);
    }

    /**
     * Test getting all pockets.
     */
    public function testGetAllCache() {
        $pocket1 = \Pocket::touch('Pocket 1', 'Hello world');
        $this->assertPocketCount(1);
        $pocket2 = \Pocket::touch('Pocket 2', 'Hello world');
        $this->assertPocketCount(2);
        $this->assertPocketCount(2);
        $this->cache->assertSetCount(PocketsModel::CACHE_KEY_ENABLED, 2);
        $this->cache->assertGetCount(PocketsModel::CACHE_KEY_ENABLED, 1);

        $this->pocketModel->delete(['PocketID' => $pocket2]);
        $this->assertPocketCount(1);
    }

    /**
     * Test getting all pockets.
     */
    public function testEnabledPockets() {
        /** @var PocketsModel $pocketModel */
        $pocketModel = $this->container()->get(PocketsModel::class);
        $this->resetTable('Pocket');
        $pocket1 = \Pocket::touch('Pocket 1', 'Hello world');
        $this->assertPocketCount(1);
        $pocket2 = \Pocket::touch('Pocket 2', 'Hello world');
        $this->assertPocketCount(2);
        $pocketModel->setField($pocket1, 'Disabled', false);
        $this->assertPocketCount(2, 1);
        $pocketModel->setField($pocket1, 'Disabled', true);
        $this->assertPocketCount(2, 0);
        $pocketModel->deleteID($pocket2);
        $this->assertPocketCount(1, 0);
    }

    /**
     * Assert a pocket count.
     *
     * @param int $pocketCount
     * @param int $enabledCount
     */
    private function assertPocketCount(int $pocketCount, int $enabledCount = 0) {
        $this->assertEquals($pocketCount, count($this->pocketModel->getAll()));
        $this->assertEquals($enabledCount, count($this->pocketModel->getEnabled()));
    }

    /**
     * Test that widgets can be saved and switched away.
     */
    public function testWidgetPocket() {
        /** @var PocketsModel $pocketModel */
        $pocketModel = $this->container()->get(PocketsModel::class);
        $this->resetTable('Pocket');

        $pocketID = $pocketModel->save([
            'Name' => 'Widget Pocket',
            'Format' => PocketsModel::FORMAT_WIDGET,
            'WidgetID' => MockWidget1::getWidgetID(),
            'WidgetParameters' => [
                'name' => 'Hello Widget',
            ],
            'Location' => 'Content',
            'Sort' => 1000,
            'Repeat' => \Pocket::REPEAT_ONCE,
        ]);

        ModelUtils::validationResultToValidationException($pocketModel);

        $this->assertNotNull($pocketID);
        $pocket = $pocketModel->getID($pocketID);
        $this->assertArraySubsetRecursive(
            [
                'WidgetParameters' => [
                    'name' => 'Hello Widget',
                ],
                'Format' => PocketsModel::FORMAT_WIDGET,
                'WidgetID' => MockWidget1::getWidgetID(),
            ],
            $pocket
        );

        // Switch back to an HTML pocket.
        $pocketModel->save([
            'PocketID' => $pocketID,
            'Format' => PocketsModel::FORMAT_CUSTOM,
            'Body' => 'hello world',
        ]);
        ModelUtils::validationResultToValidationException($pocketModel);
        $pocket = $pocketModel->getID($pocketID);

        $goodSubset = [
            'Body' => 'hello world',
            'Format' => PocketsModel::FORMAT_CUSTOM,
            'WidgetID' => null,
            'WidgetParameters' => null,
        ];

        $this->assertArraySubsetRecursive(
            $goodSubset,
            $pocket
        );

        // Make sure enabled/disable don't break the pocket.
        $pocketModel->save([
            'PocketID' => $pocketID,
            'Disabled' => \Pocket::DISABLED,
        ]);
        $pocketModel->save([
            'PocketID' => $pocketID,
            'Disabled' => \Pocket::ENABLED,
        ]);

        $this->assertArraySubsetRecursive(
            $goodSubset,
            $pocket
        );
    }

    /**
     * Test that widgets are properly found.
     */
    public function testWidgetValidation() {
        /** @var PocketsModel $pocketModel */
        $pocketModel = $this->container()->get(PocketsModel::class);
        $this->resetTable('Pocket');

        $this->runWithExpectedException(ValidationException::class, function () use ($pocketModel) {
            $pocketModel->save([
                'Name' => 'Widget Failed Pocket',
                'Format' => PocketsModel::FORMAT_WIDGET,
                'WidgetID' => 'bad-id',
                'WidgetParameters' => [
                    'name' => 'Hello Widget',
                ],
                'Location' => 'Content',
                'Sort' => 1000,
                'Repeat' => \Pocket::REPEAT_ONCE,
            ]);
            ModelUtils::validationResultToValidationException($pocketModel);
        });

        $this->runWithExpectedException(ValidationException::class, function () use ($pocketModel) {
            $pocketModel->save([
                'Name' => 'Widget Failed Pocket',
                'Format' => PocketsModel::FORMAT_WIDGET,
                'WidgetID' => MockWidget1::getWidgetID(),
                'WidgetParameters' => [
                    'name' => [],
                ],
                'Location' => 'Content',
                'Sort' => 1000,
                'Repeat' => \Pocket::REPEAT_ONCE,
            ]);
            ModelUtils::validationResultToValidationException($pocketModel);
        });
    }
}
