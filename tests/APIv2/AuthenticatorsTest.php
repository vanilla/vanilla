<?php
/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv2;

use Gdn_AuthenticationProviderModel;
use Garden\Web\Exception\NotFoundException;
use Vanilla\Web\CacheControlConstantsInterface;

/**
 * Class AuthenticatorsTest
 */
class AuthenticatorsTest extends AbstractAPIv2Test
{
    /** Authentication alias used by authenticators generated by this class. */
    private const AUTHENTICATION_SCHEME_ALIAS = "test";

    /** @var Gdn_AuthenticationProviderModel */
    private $authenticatorModel;

    /** @var string[] */
    private $patchFields = ["active", "default", "visible"];

    /**
     * Add a new authenticator.
     *
     * @param array $fields
     * @return array
     */
    private function addAuthenticator(array $fields = []): array
    {
        $fields += [
            "AuthenticationKey" => "test-" . uniqid(),
            "AuthenticationSchemeAlias" => self::AUTHENTICATION_SCHEME_ALIAS,
        ];
        $rowID = $this->authenticatorModel->save($fields);
        $row = $this->authenticatorModel->getID($rowID, DATASET_TYPE_ARRAY);
        return $row;
    }

    /**
     * Modify fields in a row.
     *
     * @param array $row
     * @return array
     */
    private function modifyRow(array $row): array
    {
        $row["active"] = !$row["active"];
        $row["default"] = !$row["default"];
        $row["visible"] = !$row["visible"];
        return $row;
    }

    /**
     * Provide fields for testing sparse updates.
     */
    public function providePatchFields(): array
    {
        $result = [];
        foreach ($this->patchFields as $field) {
            $result[$field] = [$field];
        }
        return $result;
    }

    /**
     * @inheritdoc
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->container()->call(function (Gdn_AuthenticationProviderModel $authenticatorModel) {
            $this->authenticatorModel = $authenticatorModel;
        });
    }

    /**
     * Test deleting a single authenticator.
     */
    public function testDelete(): void
    {
        $row = $this->addAuthenticator();
        $rowID = $row["UserAuthenticationProviderID"];

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage("Authenticator not found.");

        $response = $this->api()->delete("authenticators/{$rowID}");
        $this->assertSame(204, $response->getStatusCode());

        $this->api()->get("authenticators/{$rowID}");
    }

    /**
     * Test getting a single authenticator.
     */
    public function testGet(): void
    {
        $row = $this->addAuthenticator();
        $rowID = $row["UserAuthenticationProviderID"];

        $response = $this->api()->get("authenticators/{$rowID}");
        $this->assertSame(200, $response->getStatusCode());
        $this->assertCamelCase($response->getBody());

        $header = $response->getHeaderLines("cache-control");
        $this->assertSame(CacheControlConstantsInterface::NO_CACHE, $header[0]);
    }

    /**
     * Verify basic behavior of the authenticators index.
     */
    public function testIndex(): void
    {
        $rows = [];
        for ($i = 0; $i < 10; $i++) {
            $rows[] = $this->addAuthenticator();
        }
        $this->pagingTest("authenticators");
    }

    /**
     * Verify filtering authenticators by type.
     */
    public function testIndexByType(): void
    {
        $total = 2;
        for ($i = 1; $i <= $total; $i++) {
            $this->addAuthenticator(["AuthenticationSchemeAlias" => __FUNCTION__]);
        }

        // Make sure we have other types of authenticators.
        $this->addAuthenticator();
        $this->addAuthenticator();

        $result = $this->api()
            ->get("authenticators", ["type" => __FUNCTION__])
            ->getBody();
        $this->assertCount($total, $result);

        $validType = true;
        foreach ($result as $row) {
            if ($row["type"] !== __FUNCTION__) {
                $validType = false;
                break;
            }
        }
        $this->assertTrue($validType);
    }

    /**
     * Test editing multiple fields in a single request.
     */
    public function testPatchFull(): void
    {
        $dbRow = $this->addAuthenticator();
        $rowID = $dbRow["UserAuthenticationProviderID"];

        $row = $this->api()
            ->get("authenticators/{$rowID}")
            ->getBody();
        $fields = $this->modifyRow($row);
        $expected = array_intersect_key($fields, array_flip($this->patchFields));
        $response = $this->api()->patch("authenticators/{$rowID}", $fields);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertArraySubsetRecursive($expected, $response->getBody());
    }

    /**
     * Test sparse row updating.
     *
     * @param string $field
     * @dataProvider providePatchFields
     */
    public function testPatchSparse(string $field): void
    {
        $dbRow = $this->addAuthenticator();
        $rowID = $dbRow["UserAuthenticationProviderID"];

        $row = $this->api()
            ->get("authenticators/{$rowID}")
            ->getBody();
        $fields = $this->modifyRow($row);
        $expected = $fields[$field];
        $this->api()->patch("authenticators/{$rowID}", [$field => $expected]);

        $response = $this->api()
            ->get("authenticators/{$rowID}")
            ->getBody();
        $this->assertSame($expected, $response[$field]);
    }
}
