<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv2;

use Garden\Web\Exception\ForbiddenException;
use Garden\Web\Exception\NotFoundException;
use Gdn_Upload;
use Garden\Http\HttpResponse;
use Vanilla\UploadedFile;
use VanillaTests\Fixtures\TestUploader;
use VanillaTests\SchedulerTestTrait;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Test the /api/v2/media endpoints.
 */
class MediaTest extends AbstractAPIv2Test
{
    use UsersAndRolesApiTestTrait;
    use SchedulerTestTrait;

    /** @var string */
    private $baseUrl = "/media";

    /**
     * Test updating a media item's attachment state.
     */
    public function testPatchAttachment()
    {
        $row = $this->testPost();
        $mediaID = $row["responseBody"]["mediaID"];

        // Attachments default as "embed" and to the current user. Try attaching to a discussion.
        $updatedAttachment = [
            "foreignID" => 1,
            "foreignType" => "discussion",
        ];
        $result = $this->api()->patch("{$this->baseUrl}/{$mediaID}/attachment", $updatedAttachment);
        $this->assertEquals($updatedAttachment, array_intersect_assoc($updatedAttachment, $result->getBody()));
    }

    /**
     * Test posting.
     *
     * @return array ['uploadedFile' => UploadedFile, 'responseBody' => $body]
     */
    public function testPost()
    {
        TestUploader::resetUploads();
        $photo = TestUploader::uploadFile("photo", PATH_ROOT . "/tests/fixtures/apple.jpg");

        $row = [
            "file" => $photo,
            "type" => "image",
        ];
        $result = $this->api()->post($this->baseUrl, $row);

        $this->assertEquals(201, $result->getStatusCode());

        $this->validateMedia($photo, $result);

        return [
            "uploadedFile" => $photo,
            "responseBody" => $result->getBody(),
        ];
    }

    /**
     * Test posting/uploading an SVG file through the /media API endpoint depending on the permissions.
     */
    public function testPostSVGPermissions()
    {
        TestUploader::resetUploads();

        // Perform an SVG file upload with both the `Garden.Uploads.Add` & `Garden.Community.Manage` permissions.
        $permissions = [
            "uploads.add" => true,
            "community.manage" => true,
        ];
        $this->runWithPermissions(function () {
            $photo = TestUploader::uploadFile("photo", PATH_ROOT . "/tests/fixtures/test.svg");

            $row = ["file" => $photo, "type" => "image"];
            $result = $this->api()->post($this->baseUrl, $row);
            // We are expecting a valid response status code.
            $this->assertEquals(201, $result->getStatusCode());
            // We validate the uploaded media file.
            $this->validateMedia($photo, $result);
        }, $permissions);

        // Perform a JPG file upload with the `Garden.Community.Manage` permission missing.
        $permissions = [
            "uploads.add" => true,
            "community.manage" => false,
        ];
        $this->runWithPermissions(function () {
            $photo = TestUploader::uploadFile("photo", PATH_ROOT . "/tests/fixtures/apple.jpg");

            $row = ["file" => $photo, "type" => "image"];
            $result = $this->api()->post($this->baseUrl, $row);
            // We are expecting a valid response status code.
            $this->assertEquals(201, $result->getStatusCode());
            // We validate the uploaded media file.
            $this->validateMedia($photo, $result);
        }, $permissions);

        // Try & fail an SVG file upload with the `Garden.Community.Manage` permission missing.
        $permissions = [
            "uploads.add" => true,
            "community.manage" => false,
        ];
        $this->runWithPermissions(function () {
            $photo = TestUploader::uploadFile("photo", PATH_ROOT . "/tests/fixtures/test.svg");

            $row = ["file" => $photo, "type" => "image"];
            // We are expecting the user will be denied the svg file format upload.
            $this->expectExceptionMessage("file contains an invalid file extension: svg.");
            $this->api()->post($this->baseUrl, $row);
        }, $permissions);
    }

    /**
     * Get a media.
     */
    public function testGet()
    {
        $postResult = $this->testPost();
        $mediaID = $postResult["responseBody"]["mediaID"];

        $result = $this->api()->get($this->baseUrl . "/" . $mediaID);

        $this->assertEquals(200, $result->getStatusCode());

        $this->validateMedia($postResult["uploadedFile"], $result);
    }

    /**
     * Get a media by URL.
     */
    public function testGetByUrl()
    {
        $postResult = $this->testPost();

        $result = $this->api()->get($this->baseUrl . "/by-url", ["url" => $postResult["responseBody"]["url"]]);

        $this->assertEquals(200, $result->getStatusCode());

        $this->validateMedia($postResult["uploadedFile"], $result);
    }

    /**
     * Delete a media.
     */
    public function testDelete()
    {
        $postResult = $this->testPost();
        $mediaID = $postResult["responseBody"]["mediaID"];

        $result = $this->api()->delete($this->baseUrl . "/" . $mediaID);

        $this->assertEquals(204, $result->getStatusCode());

        try {
            $this->api()->get("{$this->baseUrl}/$mediaID");
            $this->fail("The media did not get deleted.");
        } catch (\Exception $ex) {
            $this->assertEquals(404, $ex->getCode());
            return;
        }
        $this->fail("Something odd happened while deleting a media.");
    }

    /**
     * Test that deleting a file from the CDN by it's id fail for non-community manager users.
     */
    public function testDeleteFileDeletionPermissionFailure()
    {
        $user = $this->createUser(["roleID" => [\RoleModel::MEMBER_ID]]);
        $this->expectException(ForbiddenException::class);

        $this->runWithUser(function () {
            $postResult = $this->testPost();
            $mediaID = $postResult["responseBody"]["mediaID"];
            $result = $this->api()->delete($this->baseUrl . "/" . $mediaID, [
                "deleteFile" => 1,
            ]);
        }, $user);
    }

    /**
     * Delete a media by URL.
     */
    public function testDeleteByURL()
    {
        $postResult = $this->testPost();
        $mediaID = $postResult["responseBody"]["mediaID"];

        $result = $this->api()->delete($this->baseUrl . "/by-url", ["url" => $postResult["responseBody"]["url"]]);
        $this->assertEquals(204, $result->getStatusCode());

        try {
            $this->api()->get("{$this->baseUrl}/$mediaID");
            $this->fail("The media did not get deleted.");
        } catch (\Exception $ex) {
            $this->assertEquals(404, $ex->getCode());
            $fileDeletedFromDB = true;
        }

        if (!$fileDeletedFromDB) {
            $this->fail("Something odd happened while deleting a media.");
        }
    }

    /**
     * Test that deleting a file from the CDN by it's url fail for non-community manager users.
     */
    public function testDeleteByURLFileDeletionPermissionFailure()
    {
        $user = $this->createUser(["roleID" => [\RoleModel::MEMBER_ID]]);
        $this->expectException(ForbiddenException::class);

        $this->runWithUser(function () {
            $postResult = $this->testPost();
            $this->api()->delete($this->baseUrl . "/by-url", [
                "url" => $postResult["responseBody"]["url"],
                "deleteFile" => 1,
            ]);
        }, $user);
    }

    /**
     * Validate a media.
     *
     * @param UploadedFile $uploadedFile
     * @param HttpResponse $result
     */
    private function validateMedia(UploadedFile $uploadedFile, HttpResponse $result)
    {
        $sizelessMediaTypes = ["image/svg+xml"];

        $body = $result->getBody();
        $this->assertIsArray($body);

        $this->assertArrayHasKey("mediaID", $body);
        $this->assertTrue(is_int($body["mediaID"]));

        $urlPrefix = Gdn_Upload::urls("");
        $this->assertArrayHasKey("url", $body);
        $this->assertStringStartsWith($urlPrefix, $body["url"]);
        $filename = PATH_UPLOADS . substr($body["url"], strlen($urlPrefix));

        $this->assertArrayHasKey("name", $body);
        $this->assertEquals($uploadedFile->getClientFilename(), $body["name"]);

        $this->assertArrayHasKey("type", $body);
        $this->assertEquals($uploadedFile->getClientMediaType(), $body["type"]);

        $this->assertArrayHasKey("size", $body);
        $this->assertEquals($uploadedFile->getSize(), $body["size"]);

        if (!in_array($body["type"], $sizelessMediaTypes)) {
            $this->assertArrayHasKey("width", $body);
            $this->assertArrayHasKey("height", $body);
            if (strpos($body["type"], "image/") === 0) {
                $imageInfo = getimagesize($filename);
                $this->assertEquals($imageInfo[0], $body["width"]);
                $this->assertEquals($imageInfo[1], $body["height"]);
            } else {
                $this->assertEquals(null, $body["width"]);
                $this->assertEquals(null, $body["height"]);
            }
        }

        $this->assertArrayHasKey("foreignType", $body);
        $this->assertEquals("embed", $body["foreignType"]);

        $this->assertArrayHasKey("foreignID", $body);
        $this->assertEquals(\Gdn::session()->UserID, $body["foreignID"]);
    }

    /**
     * Test for [DELETE] media/list.
     */
    public function testDeleteMediaList(): void
    {
        $postResult1 = $this->testPost();
        $postResult2 = $this->testPost();
        $mediaID1 = $postResult1["responseBody"]["mediaID"];
        $mediaID2 = $postResult2["responseBody"]["mediaID"];

        $result = $this->api()->deleteWithBody("/media/list", [
            "mediaIDs" => [0, $mediaID1, $mediaID2],
            "deleteFile" => true,
        ]);
        $this->assertEquals(200, $result->getStatusCode());
        $this->assertEmpty($this->getMediaByID($mediaID1));
        $this->assertEmpty($this->getMediaByID($mediaID2));
    }

    /**
     * Test for [DELETE] media/list-by-url.
     */
    public function testDeleteMediaByURLsList(): void
    {
        $postResult1 = $this->testPost();
        $postResult2 = $this->testPost();
        $url1 = $postResult1["responseBody"]["url"];
        $url2 = $postResult2["responseBody"]["url"];

        $result = $this->api()->deleteWithBody("/media/list-by-url", [
            "urls" => [__FUNCTION__, $url1, $url2],
            "deleteFile" => true,
        ]);
        $this->assertEquals(200, $result->getStatusCode());
        $this->assertEmpty($this->getMediaByID($postResult1["responseBody"]["mediaID"]));
        $this->assertEmpty($this->getMediaByID($postResult2["responseBody"]["mediaID"]));
    }

    /**
     * Test that [DELETE] media/list can be resumed with the long runner.
     */
    public function testDeleteMediasListIteratorLongRunnerContinue()
    {
        $this->resetTable("Media");
        $postResult1 = $this->testPost();
        $postResult2 = $this->testPost();
        $mediaID1 = $postResult1["responseBody"]["mediaID"];
        $mediaID2 = $postResult2["responseBody"]["mediaID"];

        $this->getLongRunner()->setMaxIterations(1);
        $response = $this->api()->deleteWithBody(
            "/media/list",
            [
                "mediaIDs" => [$mediaID1, $mediaID2, 0],
                "deleteFile" => false,
            ],
            [],
            ["throw" => false]
        );
        $this->assertNotNull($response["callbackPayload"]);
        $this->assertEmpty($this->getMediaByID($mediaID1));

        // Resume and finish.
        $this->getLongRunner()->setMaxIterations(100);
        $response = $this->resumeLongRunner($response["callbackPayload"]);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEmpty($this->getMediaByID($mediaID2));
    }

    /**
     * Test that [DELETE] media/list-by-url can be resumed with the long runner.
     */
    public function testDeleteMediasListURLsIteratorLongRunnerContinue()
    {
        $this->resetTable("Media");
        $postResult1 = $this->testPost();
        $postResult2 = $this->testPost();
        $url1 = $postResult1["responseBody"]["url"];
        $url2 = $postResult2["responseBody"]["url"];

        $this->getLongRunner()->setMaxIterations(1);
        $response = $this->api()->deleteWithBody(
            "/media/list-by-url",
            [
                "urls" => [$url1, $url2, __FUNCTION__],
                "deleteFile" => false,
            ],
            [],
            ["throw" => false]
        );
        $this->assertNotNull($response["callbackPayload"]);
        $this->assertEmpty($this->getMediaByID($postResult1["responseBody"]["mediaID"]));

        // Resume and finish.
        $this->getLongRunner()->setMaxIterations(100);
        $response = $this->resumeLongRunner($response["callbackPayload"]);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEmpty($this->getMediaByID($postResult2["responseBody"]["mediaID"]));
    }

    /**
     * Get a Media row by it's ID. Will return an emmpty array if no media is found.
     *
     * @param int $mediaID
     * @return array
     */
    public function getMediaByID(int $mediaID): array
    {
        try {
            $result = $this->api()
                ->get("{$this->baseUrl}/$mediaID")
                ->getBody();
        } catch (NotFoundException $e) {
            return [];
        }

        return $result;
    }
}
