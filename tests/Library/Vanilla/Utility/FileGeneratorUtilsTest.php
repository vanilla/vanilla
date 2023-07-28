<?php
/**
 * @author Olivier Lamy-Canuel <olamy-canuel@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\Library\Vanilla\Utility;

use Vanilla\Utility\FileGeneratorUtils;
use VanillaTests\SiteTestCase;

/**
 * Test for FileGeneratorUtils
 */
class FileGeneratorUtilsTest extends SiteTestCase
{
    /**
     * Test for FileGeneratorUtils::getContentType()
     */
    public function testGetContentType()
    {
        $result = FileGeneratorUtils::getContentType("csv");
        $this->assertEquals("application/csv; charset=utf-8", $result);
    }

    /**
     * Test for FileGeneratorUtils::getContentDisposition()
     */
    public function testGetContentDisposition()
    {
        $request = \Gdn_Request::create();
        $request->setPath("/api/v2/users/2.csv");
        $result = FileGeneratorUtils::getContentDisposition($request);
        $this->assertMatchesRegularExpression("~attachment; filename=\"users-2-\d{8}-\d{6}\.csv\"~", $result);
    }

    /**
     * Test for FileGeneratorUtils::getExtension()
     */
    public function testGetExtension()
    {
        $request = \Gdn_Request::create();
        $request->setPath("/api/v2/users/2.csv");
        $result = FileGeneratorUtils::getExtension($request);
        $this->assertEquals("csv", $result);
    }

    /**
     * Test for FileGeneratorUtils::generateFileName()
     */
    public function testGenerateFilename()
    {
        $request = \Gdn_Request::create();
        $request->setPath("/api/v2/users/2.csv");
        $result = FileGeneratorUtils::generateFileName($request);
        $this->assertMatchesRegularExpression("~users-2-\d{8}-\d{6}\.csv~", $result);
    }
}
