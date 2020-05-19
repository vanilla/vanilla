<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Database;

use PHPUnit\Framework\TestCase;
use Vanilla\Database\Operation;
use Vanilla\Database\Operation\PruneProcessor;
use Vanilla\Models\Model;
use Vanilla\Models\PipelineModel;

/**
 * Tests for the `PruneProcessor` class.
 */
class PruneProcessorTest extends TestCase {
    /**
     * @var PipelineModel
     */
    private $model;

    /**
     * @var PruneProcessor
     */
    private $pruner;

    /**
     * @var array
     */
    private $pruneCall;

    /**
     * {@inheritDoc}
     */
    public function setUp(): void {
        parent::setUp();

        $this->pruner = new PruneProcessor('dateInserted');

        $this->model = $this->createStub(PipelineModel::class);
        $this->model
            ->method('delete')
            ->willReturnCallback(function (array $where, array $options) {
                $this->pruneCall = [$where, $options];
                return true;
            });

        $this->pruneCall = null;
    }

    /**
     * Test a basic prune workflow.
     */
    public function testBasicPrune(): void {
        $r = $this->handlePrune(1234);
        $this->assertSame(1234, $r);

        [$where, $options] = $this->pruneCall;
        $this->assertArrayHasKey('dateInserted <', $where);
        $this->assertSame($this->pruner->getPruneLimit(), $options[Model::OPT_LIMIT]);
        $this->assertDateCloseTo($where['dateInserted <'], '-' . $this->pruner->getPruneAfter());
    }

    /**
     * Handle a basic prune.
     *
     * @param mixed $nextResult
     * @return mixed
     */
    private function handlePrune($nextResult) {
        $op = new Operation();
        $op->setType(Operation::TYPE_INSERT);
        $op->setCaller($this->model);

        $r = $this->pruner->handle($op, function () use ($nextResult) {
            return $nextResult;
        });
        return $r;
    }

    /**
     * Make sure a date is close to another date, within plus or minus one second.
     *
     * @param string $dateTime
     * @param string $sub
     */
    protected function assertDateCloseTo(string $dateTime, string $sub): void {
        $dt1 = new \DateTimeImmutable($dateTime);
        $dt2 = new \DateTimeImmutable($sub);

        if ($dt1 < $dt2->modify('-1 second') || $dt1 > $dt2->modify('+1 second')) {
            $this->fail("Failed asserting that " . $dt1->format('c') . ' is close to ' . $dt2->format('c'));
        } else {
            $this->assertTrue(true);
        }
    }

    /**
     * I should be able to specify a negative date range for the prune date.
     */
    public function testNegativeDate(): void {
        $this->pruner->setPruneAfter('-10 days');
        $r = $this->handlePrune(12345);
        $this->assertSame(12345, $r);
        [$where, $options] = $this->pruneCall;

        $this->assertDateCloseTo($where['dateInserted <'], $this->pruner->getPruneAfter());
    }

    /**
     * Setting an invalid prune date is an exception.
     */
    public function testInvalidPruneDate(): void {
        $this->expectException(\InvalidArgumentException::class);
        $this->pruner->setPruneAfter('zb13');
    }

    /**
     * The prunable trait can take a null date, in which case no pruning should be done.
     */
    public function testNullPruneDate(): void {
        $this->pruner->setPruneAfter(null);
        $r = $this->handlePrune(12);
        $this->assertSame(12, $r);
        $this->assertNull($this->pruneCall);
    }
}
