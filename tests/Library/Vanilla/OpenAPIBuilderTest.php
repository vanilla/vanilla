<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2.0-only
 */

namespace VanillaTests\Library\Vanilla;

use PHPUnit\Framework\TestCase;
use Vanilla\AddonManager;
use Vanilla\OpenAPIBuilder;
use Vanilla\Utility\ArrayUtils;
use VanillaTests\Fixtures\Request;
use VanillaTests\OpenAPIBuilderTrait;

/**
 * Test Vanilla's OpenAPI build.
 */
class OpenAPIBuilderTest extends TestCase
{
    use OpenAPIBuilderTrait;

    /**
     * @var array
     */
    private $openAPI = null;

    /**
     * Assert than an array schema has valid items.
     *
     * @param array|\ArrayAccess $schema
     * @param string $pathStr
     */
    public static function assertArraySchemaItems($schema, string $pathStr): void
    {
        if (empty($schema["type"]) || $schema["type"] !== "array") {
            return;
        }

        TestCase::assertArrayHasKey("items", $schema, "Schemas of type array must have an items property: " . $pathStr);
        self::assertIsSchema($schema["items"], $pathStr . ".items");
    }

    /**
     * Assert that a variable represents a JSON schema.
     *
     * @param mixed $schema The schema to test.
     * @param string $pathStr The path to the schema for error messages.
     */
    public static function assertIsSchema($schema, string $pathStr): void
    {
        TestCase::assertIsArray($schema, "The schema must be an array: $pathStr");

        if (!empty($schema['$ref'])) {
            return;
        }

        if (!empty($schema["allOf"])) {
            TestCase::assertIsArray($schema["allOf"], "The allOf schema must be an array: $pathStr");
            foreach ($schema["allOf"] as $i => $item) {
                self::assertIsSchema($item, "$pathStr.$i");
            }
        } elseif (!empty($schema["oneOf"])) {
            TestCase::assertIsArray($schema["oneOf"], "The oneOf schema must be an array: $pathStr");
            foreach ($schema["oneOf"] as $i => $item) {
                self::assertIsSchema($item, "$pathStr.$i");
            }
        } else {
            TestCase::assertArrayHasKey("type", $schema, "The schema must have a type: " . $pathStr);
        }
    }

    /**
     * Get the full OpenAPI array for various tests.
     *
     * @return array
     */
    public function getFullOpenAPI()
    {
        if ($this->openAPI === null) {
            $builder = $this->createOpenApiBuilder();

            $this->openAPI = $builder->getFullOpenAPI();
        }
        return $this->openAPI;
    }

    /**
     * Test that we generate a proper base path.
     */
    public function testApiPathGeneration()
    {
        $addonManager = new AddonManager();
        $request = new Request();
        $request->setAssetRoot("root");
        $request->setHost("testhost.com");
        $request->setScheme("https");
        $builder = new OpenAPIBuilder($addonManager, $request);
        $data = $builder->generateFullOpenAPI();
        $this->assertEquals(
            "https://testhost.com/root/api/v2",
            $data["servers"][0]["url"],
            "Generated API base was incorrect"
        );
    }

    /**
     * The OpenAPI build should validate against the OpenAPI spec.
     */
    public function testValidOpenAPI()
    {
        $data = $this->getFullOpenAPI();
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $path = PATH_ROOT . "/tests/cache/open-api-builder/openapi.json";
        if (file_put_contents($path, $json) === false) {
            $this->fail("Unable to write OpenAPI to {$path}");
        }

        $dir = getcwd();
        chdir(PATH_ROOT);
        exec("npx swagger-cli@4.0.4 validate $path 2>&1", $output, $result);
        chdir($dir);

        $this->assertSame(0, $result, implode(PHP_EOL, $output));
    }

    /**
     * Test specific OpenAPI schema merging bugs.
     *
     * @param array $schema1
     * @param array $schema2
     * @param array $expected
     * @dataProvider provideSchemaMergeScenarios
     */
    public function testSchemaMergeBugs(array $schema1, array $schema2, array $expected)
    {
        $actual = OpenAPIBuilder::mergeSchemas($schema1, $schema2);
        $this->assertSame($expected, $actual);
    }

    /**
     * The Swagger UI library has an issue where schemas of type array will fail if they have no `items` property.
     *
     * This isn't something caught by our general test and can only be seen when expanding an endpoint in the UI where
     * it fails to render with an obscure looking error.
     */
    public function testArrayItemsSchema(): void
    {
        $data = $this->getFullOpenAPI();

        ArrayUtils::walkRecursiveArray($data, function ($schema, $path) {
            $pathStr = implode(".", $path);
            self::assertArraySchemaItems($schema, $pathStr);
        });
    }

    /**
     * Data provider.
     *
     * @return array
     */
    public function provideSchemaMergeScenarios(): array
    {
        $r = [
            "enum" => [["enum" => ["a", "c"]], ["enum" => ["a", "b"]], ["enum" => ["a", "b", "c"]]],
            "parameters" => [
                ["parameters" => [["name" => "a", "e" => [1]]]],
                ["parameters" => [["name" => "b"], ["name" => "a", "e" => [2]]]],
                ["parameters" => [["name" => "a", "e" => [1, 2]], ["name" => "b"]]],
            ],
            "required" => [["required" => ["a", "c"]], ["required" => ["a", "b"]], ["required" => ["a", "c", "b"]]],
        ];
        return $r;
    }
}
