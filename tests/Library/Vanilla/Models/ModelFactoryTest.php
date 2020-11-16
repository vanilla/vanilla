<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Models;

use Garden\Container\NotFoundException;
use Psr\Log\LoggerInterface;
use Vanilla\Models\Model;
use Vanilla\Models\ModelFactory;
use VanillaTests\BootstrapTestCase;

/**
 * Tests for the `ModelFactory` class.
 */
class ModelFactoryTest extends BootstrapTestCase {
    const RECORD_TYPE = 'test';
    const ALIAS = 't';

    /**
     * @var ModelFactory
     */
    private $factory;

    /**
     * @inheritDoc
     */
    public function setUp(): void {
        parent::setUp();

        $this->factory = ModelFactory::fromContainer($this->container());
        $this->factory->addModel(self::RECORD_TYPE, TestModel::class, self::ALIAS);
    }

    /**
     * The factory should have its models' record types and aliases.
     */
    public function testHas(): void {
        $this->assertTrue($this->factory->has(self::RECORD_TYPE));
        $this->assertTrue($this->factory->has(self::ALIAS));
    }

    /**
     * You should be able to add multiple aliases to record types.
     */
    public function testAddAlias(): void {
        $this->factory->addAlias(self::RECORD_TYPE, 'tt');
        $this->assertTrue($this->factory->has('tt'));
    }

    /**
     * You should be able to create a model from its record type.
     */
    public function testModelCreation(): void {
        $model = $this->factory->get(self::RECORD_TYPE);
        $this->assertInstanceOf(TestModel::class, $model);
    }

    /**
     * You should be able to create a model from its alias.
     */
    public function testModelCreationAlias(): void {
        $model = $this->factory->get(self::ALIAS);
        $this->assertInstanceOf(TestModel::class, $model);
    }

    /**
     * Record types and aliases should be case insensitive.
     */
    public function testModelCreationCaseInsensitive(): void {
        $recordType = strtoupper(self::RECORD_TYPE);
        $this->assertNotSame($recordType, self::RECORD_TYPE);
        $model = $this->factory->get($recordType);
        $this->assertInstanceOf(TestModel::class, $model);

        $alias = strtoupper(self::ALIAS);
        $this->assertNotSame($alias, self::ALIAS);
        $model = $this->factory->get($alias);
        $this->assertInstanceOf(TestModel::class, $model);
    }

    /**
     * The `getRecordType()` method should work with a variety of references.
     *
     * @param string $ref
     * @dataProvider provideValidRefs
     */
    public function testGetRecordType(string $ref): void {
        $this->assertSame(self::RECORD_TYPE, $this->factory->getRecordType($ref));
    }

    /**
     * Provide some valid references.
     *
     * @return array
     */
    public function provideValidRefs(): array {
        $r = [
            [self::ALIAS],
            [self::RECORD_TYPE],
            [strtoupper(self::RECORD_TYPE)],
            [TestModel::class],
        ];

        return array_column($r, null, 0);
    }

    /**
     * An invalid record type ref should be an exception.
     */
    public function testInvalidRecordTypeRef(): void {
        $this->expectException(NotFoundException::class);
        $this->factory->getRecordType('foo');
    }

    /**
     * Adding an alias to an invalid record type should be an exception.
     */
    public function testInvalidAlias(): void {
        $this->expectException(NotFoundException::class);
        $this->factory->addAlias('foo', 'bar');
    }

    /**
     * Smoke test `getAll()`.
     */
    public function testGetAll(): void {
        $r = $this->factory->getAll();
        $this->assertArrayHasKey(self::RECORD_TYPE, $r);
        $this->assertInstanceOf(TestModel::class, $r[self::RECORD_TYPE]);
    }

    /**
     * Smoke test `getAllByInterface()`.
     */
    public function testGetAllByInterface(): void {
        $r = $this->factory->getAllByInterface(LoggerInterface::class);
        $this->assertEmpty($r);

        $r2 = $this->factory->getAllByInterface(Model::class);
        $this->assertArrayHasKey(self::RECORD_TYPE, $r2);
        $this->assertInstanceOf(TestModel::class, $r2[self::RECORD_TYPE]);
    }
}
