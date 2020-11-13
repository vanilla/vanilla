<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Core;

use VanillaTests\BootstrapTestCase;

/**
 * Tests for the `Gdn_Smarty` class.
 */
class SmartyTest extends BootstrapTestCase {
    /**
     * @var \Gdn_Smarty
     */
    private $smarty;

    /**
     * @inheritDoc
     */
    public function setUp(): void {
        parent::setUp();
        $this->smarty = new \Gdn_Smarty();
        $dir = PATH_ROOT.'/tests/cache/smarty';
        touchFolder($dir);
        $this->smarty->smarty()->setCompileDir($dir);
        $this->smarty->enableSecurity($this->smarty->smarty());
        $this->smarty->smarty()->setTemplateDir(PATH_ROOT.'/tests/fixtures/smarty');
    }


    /**
     * Some keys should be removed.
     */
    public function testSanitizeRemove() {
        $arr = ['Password' => 'a', 'AccessToken' => 'a', 'Fingerprint' => 'a', 'Updatetoken' => 'a'];
        $actual = \Gdn_Smarty::sanitizeVariables($arr);
        $this->assertEmpty($actual);
    }

    /**
     * Some keys should be obscured.
     */
    public function testSanitizeObscure() {
        $arr = [
            'insertipaddress' => 'a',
            'updateipaddress' => 'a',
            'lastipaddress' => 'a',
            'allipaddresses' => 'a',
            'dateofbirth' => 'a',
            'hashmethod' => 'a',
            'email' => 'a',
            'firstemail' => 'a',
            'lastemail' => 'a',
        ];

        $actual = \Gdn_Smarty::sanitizeVariables($arr);

        foreach ($actual as $key => $value) {
            $this->assertSame('***OBSCURED***', $value);
        }
    }

    /**
     * Arrays should sanitize recursively.
     */
    public function testArrayRecurse() {
        $arr = [
            'a' => [
                'b' => 'c',
                'password' => 'foo',
                'lastEmail' => 'bar',
            ],
        ];

        $expected = [
            'a' => [
                'b' => 'c',
                'lastEmail' => '***OBSCURED***',
            ],
        ];

        $actual = \Gdn_Smarty::sanitizeVariables($arr);
        $this->assertSame($expected, $actual);
    }

    /**
     * A nested object should be sanitized, but not change the original object.
     */
    public function testStdClass() {
        $arr = [
            'a' => (object)[
                'b' => 'c',
                'password' => 'foo',
            ],
        ];

        $actual = \Gdn_Smarty::sanitizeVariables($arr);
        $this->assertSame('foo', $arr['a']->password);
        $this->assertInstanceOf(\stdClass::class, $actual['a']);
        $this->assertNotTrue(isset($actual['a']->password));
    }

    /**
     * Test templates with unsafe tags.
     *
     * @param string $path
     * @dataProvider provideUnsafeTemplates
     */
    public function testUnsafeTemplates(string $path): void {
        $this->expectException(\SmartyCompilerException::class);
        $lines = file($path);
        $this->expectExceptionMessage(trim($lines[0]));
        $r = $this->fetch($path);
    }

    /**
     * Test templates with unsafe tags.
     *
     * @param string $path
     * @dataProvider provideDbExtraction
     */
    public function testDbExtraction(string $path): void {
        $expectedNotDbName = \Gdn::config('Database.Name');
        $expectedExceptionName = 'not allowed';

        $exception = null;
        try {
            $rendered = $this->fetch($path);
        } catch (\SmartyCompilerException $e) {
            $exception = $e;
        }


        if (isset($rendered)) {
            $this->assertStringNotContainsString($expectedNotDbName, $rendered);
        } else {
            $this->assertStringContainsString($expectedExceptionName, $exception->getMessage());
        }
    }


    /**
     * Safe templates shouldn't fail and shouldn't contain bad output.
     *
     * @param string $path
     * @dataProvider provideSafeTemplates
     */
    public function testSafeTemplates(string $path): void {
        $r = $this->fetch($path);

        $this->assertStringNotContainsString('foo', $r);
    }

    /**
     * Fetch a template from a path, with notice suppression.
     *
     * @param string $path
     * @return string
     */
    private function fetch(string $path): string {
        try {
            $oldLevel = error_reporting(error_reporting() & ~E_NOTICE);
            $r = $this->smarty->smarty()->fetch($path);
            return $r;
        } finally {
            error_reporting($oldLevel);
        }
    }

    /**
     * Data provider.
     *
     * @return iterable
     */
    public function provideDbExtraction(): iterable {
        $paths = glob(PATH_ROOT.'/tests/fixtures/smarty/db-extraction/*.tpl');
        foreach ($paths as $path) {
            yield basename($path) => [$path];
        }
    }

    /**
     * Data provider.
     *
     * @return iterable
     */
    public function provideUnsafeTemplates(): iterable {
        $paths = glob(PATH_ROOT.'/tests/fixtures/smarty/unsafe/*.tpl');
        foreach ($paths as $path) {
            yield basename($path) => [$path];
        }
    }

    /**
     * Data provider.
     *
     * @return iterable
     */
    public function provideSafeTemplates(): iterable {
        $paths = glob(PATH_ROOT.'/tests/fixtures/smarty/safe/*.tpl');
        foreach ($paths as $path) {
            yield basename($path) => [$path];
        }
    }
}
