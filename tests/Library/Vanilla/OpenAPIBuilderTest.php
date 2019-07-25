<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2.0-only
 */

namespace VanillaTests\Library\Vanilla;

use PHPUnit\Framework\TestCase;
use Vanilla\Addon;
use Vanilla\AddonManager;
use Vanilla\OpenAPIBuilder;

/**
 * Test Vanilla's OpenAPI build.
 */
class OpenAPIBuilderTest extends TestCase {

    /**
     * The OpenAPI build should validate against the OpenAPI spec.
     */
    public function testValidOpenAPI() {
        $am = new AddonManager(
            [
                Addon::TYPE_ADDON => ['/applications', '/plugins'],
                Addon::TYPE_THEME => '/themes',
                Addon::TYPE_LOCALE => '/locales'
            ],
            PATH_ROOT.'/tests/cache/open-api-builder/vanilla-manager'
        );
        $builder = new OpenAPIBuilder($am);

        $data = $builder->generateFullOpenAPI();
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $path = PATH_ROOT.'/tests/cache/open-api-builder/openapi.json';
        file_put_contents($path, $json);

        $dir = getcwd();
        chdir(PATH_ROOT);
        exec("npx swagger-cli@2.2.1 validate $path", $output, $result);
        chdir($dir);
        $this->assertSame(0, $result);
    }
}
