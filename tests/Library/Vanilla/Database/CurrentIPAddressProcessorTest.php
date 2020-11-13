<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Database;

use Garden\Schema\Schema;
use PHPUnit\Framework\TestCase;
use Vanilla\Database\Operation;
use Vanilla\Database\Operation\CurrentIPAddressProcessor;
use Vanilla\Models\Model;
use VanillaTests\BootstrapTrait;
use VanillaTests\SetupTraitsTrait;

/**
 * Tests for the `CurrentIPAddressProcessor` class.
 */
class CurrentIPAddressProcessorTest extends TestCase {
    use BootstrapTrait, SetupTraitsTrait;

    private const TEST_IP = '2001:db8:85a3::8a2e:370:7334';

    /**
     * @var CurrentIPAddressProcessor
     */
    private $processor;

    /**
     * @var Model
     */
    private $model;

    /**
     * @inheritDoc
     */
    public function setUp(): void {
        parent::setUp();

        $this->container()->call(function (CurrentIPAddressProcessor $processor, \Gdn_Request $request) {
            $request->setIP(self::TEST_IP);
            $this->processor = $processor;
            $this->processor->setInsertFields(['insertIP']);
            $this->processor->setUpdateFields(['updateIP']);
            $this->model = new class('test') extends Model {
                /**
                 * {@inheritDoc}
                 */
                public function getWriteSchema(): Schema {
                    return Schema::parse([
                        'insertIP:s',
                        'updateIP:s',
                    ]);
                }
            };
        });
    }

    /**
     * Test IP generation on insert.
     */
    public function testInsertIPGeneration(): void {
        $op = new Operation();
        $op->setType(Operation::TYPE_INSERT);
        $op->setCaller($this->model);
        $r = $this->handleOperation($op);

        $expected = ipEncode($this->processor->getCurrentIPAddress());
        $this->assertCount(1, $op->getSet());
        $this->assertSame($expected, $op->getSet()['insertIP']);
    }

    /**
     * Test IP generation on update.
     */
    public function testUpdateIPGeneration(): void {
        $op = new Operation();
        $op->setType(Operation::TYPE_UPDATE);
        $op->setCaller($this->model);
        $r = $this->handleOperation($op);

        $expected = ipEncode($this->processor->getCurrentIPAddress());
        $this->assertCount(1, $op->getSet());
        $this->assertSame($expected, $op->getSet()['updateIP']);
    }

    /**
     * IP addresses should be decoded to basic strings when fetched from the database.
     */
    public function testIPDecoding(): void {
        $op = new Operation();
        $op->setType(Operation::TYPE_SELECT);
        $rows = $this->handleOperation($op);
        $this->assertNotEmpty($rows);
        $this->assertSame($this->processor->getCurrentIPAddress(), $rows[0]['insertIP']);
        $this->assertSame($this->processor->getCurrentIPAddress(), $rows[0]['updateIP']);
    }

    /**
     * An operation that doesn't do anything shouldn't change the set.
     */
    public function testDelete(): void {
        $op = new Operation();
        $op->setType(Operation::TYPE_DELETE);
        $r = $this->handleOperation($op);
        $this->assertTrue($r);
        $this->assertEmpty($op->getSet());
    }

    /**
     * A test handler for the processor.
     *
     * @param Operation $op
     * @return array[]|bool
     */
    public function handleOperation(Operation $op) {
        $r = $this->processor->handle($op, function (Operation $op) {
            switch ($op->getType()) {
                case Operation::TYPE_INSERT:
                case Operation::TYPE_UPDATE:
                case Operation::TYPE_DELETE:
                    return true;
                case Operation::TYPE_SELECT:
                    $row = [];
                    foreach (array_merge($this->processor->getInsertFields(), $this->processor->getUpdateFields()) as $field) {
                        $row[$field] = ipEncode($this->processor->getCurrentIPAddress());
                    }
                    return [$row, []];
                default:
                    throw new \InvalidArgumentException("Invalid test operation: " . $op->getType());
            }
        });

        return $r;
    }
}
