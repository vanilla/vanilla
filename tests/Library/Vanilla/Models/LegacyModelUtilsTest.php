<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Models;

use Vanilla\Models\LegacyModelUtils;
use VanillaTests\BootstrapTestCase;

/**
 * Tests for the `LegacyModelUtils` class.
 */
class LegacyModelUtilsTest extends BootstrapTestCase {
    /**
     * @var \Gdn_Model
     */
    private $model;

    /**
     * @inheritDoc
     */
    public function setUp(): void {
        parent::setUp();
        $this->model = $this->container()->getArgs(\Gdn_Model::class, ['legacyModelUtilsTest']);

        // Create a basic table for the test.
        $this->model->Database->structure()->table($this->model->Name)
            ->primaryKey($this->model->Name.'ID')
            ->column('name', 'varchar(20)')
            ->set();

        $this->model->delete([]);

        for ($i = 1; $i <= 10; $i++) {
            $this->model->insert(['name' => "row $i"]);
        }
    }

    /**
     * @inheritDoc
     */
    public function tearDown(): void {
        parent::tearDown();
        $this->model->Database->structure()->table($this->model->Name)->drop();
    }

    /**
     * Test a happy path for `LegacyModelUtils::getCrawlInfoFromPrimaryKey()`.
     */
    public function testGetCrawlInfoFromPrimaryKey(): void {
        $crawl = LegacyModelUtils::getCrawlInfoFromPrimaryKey($this->model, 'https://example.com/api', 'foo');

        $this->assertSame('https://example.com/api', $crawl['url']);
        $this->assertSame('foo', $crawl['parameter']);

        $this->assertIsInt($crawl['count']);
        $this->assertIsInt($crawl['min']);
        $this->assertIsInt($crawl['max']);
    }

    /**
     * Test the basic functionality of `LegacyModelUtils::orderFieldDirection()`.
     */
    public function testOrderFieldDirection(): void {
        [$field, $dir] = LegacyModelUtils::orderFieldDirection('a');
        $this->assertSame('a', $field);
        $this->assertSame('asc', $dir);

        [$field, $dir] = LegacyModelUtils::orderFieldDirection('-b');
        $this->assertSame('b', $field);
        $this->assertSame('desc', $dir);

        [$field, $dir] = LegacyModelUtils::orderFieldDirection('');
        $this->assertSame('', $field);
        $this->assertSame('', $dir);
    }
}
