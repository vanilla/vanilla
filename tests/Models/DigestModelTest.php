<?php
/**
 * @author Sooraj Francis <sfrancis@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Models;

use Vanilla\Forum\Digest\DigestModel;
use VanillaTests\SiteTestCase;

/**
 * Test the DigestModel
 */
class DigestModelTest extends SiteTestCase
{
    protected DigestModel $digestModel;

    /**
      @inheritDoc
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->digestModel = $this->container()->get(DigestModel::class);
    }

    /**
     * Test the function checkIfDigestScheduledForDay throws exception when provided invalid digest type
     */
    public function testCheckIfDigestScheduledForDayThrowsException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $dateTime = new \DateTimeImmutable("2024-12-12 12:00:00");
        $this->digestModel->checkIfDigestScheduledForDay($dateTime, "invalid");
    }

    /**
     * Test the function checkIfDigestScheduledForDay returns false when no digest is scheduled for the day
     */
    public function testCheck()
    {
        $this->assertFalse(
            $this->digestModel->checkIfDigestScheduledForDay(
                new \DateTimeImmutable("2024-12-12 12:00:00"),
                DigestModel::DIGEST_TYPE_DAILY
            )
        );
    }

    /**
     * Test the function checkIfDigestScheduledForDay returns true when a digest is scheduled for the day
     */
    public function testCheckIfDigestScheduledForDayReturnsTrue()
    {
        $this->digestModel->insert([
            "digestType" => DigestModel::DIGEST_TYPE_DAILY,
            "dateScheduled" => "2024-12-12 12:00:00",
            "totalSubscribers" => 1,
        ]);

        $this->assertTrue(
            $this->digestModel->checkIfDigestScheduledForDay(
                new \DateTimeImmutable("2024-12-12 12:00:00"),
                DigestModel::DIGEST_TYPE_DAILY
            )
        );
    }

    /**
     *  Test RecentDigestScheduleDatesByType returns the recent digest scheduled dates
     */
    public function testRecentDigestScheduleDatesByType()
    {
        $this->resetTable("digest");
        $this->digestModel->insert([
            "digestType" => DigestModel::DIGEST_TYPE_DAILY,
            "dateScheduled" => "2024-12-12 12:00:00",
            "totalSubscribers" => 1,
        ]);

        $this->digestModel->insert([
            "digestType" => DigestModel::DIGEST_TYPE_DAILY,
            "dateScheduled" => "2024-12-11 12:00:00",
            "totalSubscribers" => 1,
        ]);

        $this->digestModel->insert([
            "digestType" => DigestModel::DIGEST_TYPE_DAILY,
            "dateScheduled" => "2024-12-10 12:00:00",
            "totalSubscribers" => 1,
        ]);

        $this->assertEquals(
            ["2024-12-12 12:00:00", "2024-12-11 12:00:00", "2024-12-10 12:00:00"],
            $this->digestModel->getRecentDigestScheduleDatesByType(DigestModel::DIGEST_TYPE_DAILY, 3)
        );
    }

    /**
     * Test the function getRecentDigestScheduleDatesByType throws exception when provided invalid digest type
     */
    public function testGetRecentDigestScheduleDatesByTypeThrowsException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->digestModel->getRecentDigestScheduleDatesByType("invalid", 2);
    }
}
