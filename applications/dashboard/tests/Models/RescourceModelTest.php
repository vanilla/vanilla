<?php
/**
 * @author Olivier Lamy-Canuel <olamy-canuel@higherlogic.com>
 * @copyright 2009-2025 Higher Logic Inc.
 * @license Proprietary
 */

namespace Models;

use Exception;
use Gdn;
use Vanilla\Forum\Models\ResourceModel;
use VanillaTests\SiteTestCase;

/**
 * Test for the RescourceModel class.
 */
class RescourceModelTest extends SiteTestCase
{
    /**
     * @inheritdoc
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->resourceModel = $this->container()->get(ResourceModel::class);
    }

    /**
     * Test that the `GDN_resource` table will be created when running `/utility/update`.
     *
     * @return void
     * @throws Exception
     */
    public function testResourceTableMissing(): void
    {
        $sql = Gdn::database()->createSql();
        $sql->query("DROP TABLE `GDN_resource`");

        $this->bessy()->get("/utility/update");

        // Check if the table exists by trying to get all records.
        $result = $this->resourceModel->getAll();
        $this->assertEmpty($result);
    }
}
