<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Database;

use Garden\Schema\Schema;
use PHPUnit\Framework\TestCase;
use Vanilla\CurrentTimeStamp;
use Vanilla\Database\Operation;
use Vanilla\Database\Operation\CurrentIPAddressProcessor;
use Vanilla\Database\Operation\StatusFieldProcessor;
use Vanilla\Models\Model;
use VanillaTests\BootstrapTrait;
use VanillaTests\SetupTraitsTrait;

/**
 * Test the `StatusFieldProcessor` class.
 */
class StatusFieldProcessorTest extends TestCase {
    use BootstrapTrait, SetupTraitsTrait;

    private const TEST_IP = '2001:db8:85a3::8a2e:370:7334';

    /**
     * @var StatusFieldProcessor
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

        $this->container()->call(function (StatusFieldProcessor $processor, \Gdn_Request $request) {
            $request->setIP(self::TEST_IP);
            $this->processor = $processor;
            $this->processor->setStatusField('state');
            $this->processor->setDateField('date');
            $this->processor->setIpAddressField('ip');
            $this->processor->setUserField('userID');
            $this->model = new class('test') extends Model {
            };
        });

        CurrentTimeStamp::mockTime(time());
    }

    /**
     * {@inheritDoc}
     */
    public function tearDown(): void {
        parent::tearDown();
        CurrentTimeStamp::clearMockTime();
    }

    /**
     * The status fields should be set on inserts by default.
     */
    public function testInsertGeneration(): void {
        $op = new Operation();
        $op->setType(Operation::TYPE_INSERT);
        $r = $this->handleOperation($op);
        $this->assertSetItems($op);
    }

    /**
     * The status fields should only be set on insert if the status is passed.
     */
    public function testInsertNoStatus(): void {
        $this->processor->setSetOnInsert(false);
        $op = new Operation();
        $op->setType(Operation::TYPE_INSERT);
        $r = $this->handleOperation($op);
        $this->assertSetItems($op, false);

        $op->setSetItem($this->processor->getStatusField(), 'foo');
        $r = $this->handleOperation($op);
        $this->assertSetItems($op);
    }

    /**
     * The status fields should update when the status is set.
     */
    public function testUpdate(): void {
        $op = new Operation();
        $op->setType(Operation::TYPE_UPDATE);
        $r = $this->handleOperation($op);
        $this->assertSetItems($op, false);

        $op->setSetItem($this->processor->getStatusField(), 'foo');
        $r = $this->handleOperation($op);
        $this->assertSetItems($op);
    }

    /**
     * Selects should give back a decoded IP address.
     */
    public function testSelectDecode(): void {
        $op = new Operation();
        $op->setType(Operation::TYPE_SELECT);
        $rows = $this->handleOperation($op);

        $this->assertSame(self::TEST_IP, $rows[0][$this->processor->getIpAddressField()]);
    }

    /**
     * Inserts/updates should allow for empty fields.
     *
     * @param string $setter
     * @dataProvider provideFieldSetters
     */
    public function testSparseFields(string $setter): void {
        call_user_func([$this->processor, $setter], '');

        $op = new Operation();
        $op->setType(Operation::TYPE_INSERT);
        $op->setSetItem($this->processor->getStatusField(), 'foo');
        $this->handleOperation($op);
        $this->assertSetItems($op);
    }

    /**
     * The processor shouldn't overwrite explicitly passed fields.
     */
    public function testExistingFields(): void {
        $op = new Operation();
        $op->setType(Operation::TYPE_INSERT);
        $op
            ->setSetItem($this->processor->getDateField(), '2020-08-14')
            ->setSetItem($this->processor->getUserIDField(), 123)
            ->setSetItem($this->processor->getIpAddressField(), '127.0.0.1');
        $this->handleOperation($op);

        $this->assertSame('2020-08-14', $op->getSetItem($this->processor->getDateField()));
        $this->assertSame(123, $op->getSetItem($this->processor->getUserIDField()));
        $this->assertSame('127.0.0.1', $op->getSetItem($this->processor->getIpAddressField()));
    }

    /**
     * Data provider for processor field setters.
     *
     * @return array|\string[][]
     */
    public function provideFieldSetters(): array {
        $r = [
            'date' => ['setDateField'],
            'user' => ['setUserField'],
            'ip' => ['setIPAddressField'],
        ];
        return $r;
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
                    return [['ip' => $this->processor->getCurrentIPAddress()], []];
                default:
                    throw new \InvalidArgumentException("Invalid test operation: " . $op->getType());
            }
        });
        return $r;
    }

    /**
     * Assert whether or not the fields should exist.
     *
     * @param Operation $op
     * @param bool $exists
     */
    private function assertSetItems(Operation $op, bool $exists = true): void {
        if (!empty($this->processor->getDateField())) {
            $this->assertSame($exists, $op->hasSetItem($this->processor->getDateField()), "Operation missing status date.");
            if ($exists) {
                $this->assertSame(CurrentTimeStamp::getMySQL(), $op->getSetItem($this->processor->getDateField()));
            }
        }
        if (!empty($this->processor->getUserIDField())) {
            $this->assertSame($exists, $op->hasSetItem($this->processor->getUserIDField()), "Operation missing user field.");
            if ($exists) {
                $this->assertSame($this->processor->getCurrentUserID(), $op->getSetItem($this->processor->getUserIDField()));
            }
        }
        if (!empty($this->processor->getIpAddressField())) {
            $this->assertSame($exists, $op->hasSetItem($this->processor->getIpAddressField()), "Operation missing the IP Address field.");
            if ($exists) {
                $this->assertSame($this->processor->getCurrentIPAddress(), $op->getSetItem($this->processor->getIpAddressField()));
            }
        }
    }
}
