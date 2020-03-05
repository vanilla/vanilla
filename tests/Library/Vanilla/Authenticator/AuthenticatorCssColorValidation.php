<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla;

use PHPUnit\Framework\TestCase;
use Vanilla\Authenticator\Authenticator;
use Vanilla\Models\AuthenticatorModel;
use VanillaTests\BootstrapTrait;
use VanillaTests\Fixtures\Authenticator\CssColorAuthenticator;

/**
 * Class AuthenticatorCssColorValidation.
 */
class AuthenticatorCssColorValidation extends TestCase {
    use BootstrapTrait;

    /** @var AuthenticatorModel */
    private static $authenticatorModel;

    /**
     * @inheritdoc
     */
    public static function setUpBeforeClass(): void {
        BootstrapTrait::setUpBeforeClass();

        self::$authenticatorModel = self::container()->get(AuthenticatorModel::class);
        self::$authenticatorModel->registerAuthenticatorClass(CssColorAuthenticator::class);
    }

    /**
     * @inheritdoc
     */
    public static function tearDownAfterClass(): void {
        BootstrapTrait::tearDownAfterClass();
    }

    /**
     * @inheritdoc
     */
    public function tearDown() {
        CssColorAuthenticator::resetColor();

        parent::tearDown();
    }

    /**
     * Test valid/supported css colors.
     *
     * @dataProvider validCssColorsProvider
     *
     * @param $color
     *
     * @throws \Garden\Container\ContainerException
     * @throws \Garden\Container\NotFoundException
     * @throws \Garden\Schema\ValidationException
     * @throws \Garden\Web\Exception\ClientException
     * @throws \Garden\Web\Exception\NotFoundException
     * @throws \Garden\Web\Exception\ServerException
     *
     */
    public function testValidColors($color = null) {
        CssColorAuthenticator::setCssColor($color);
        /** @var Authenticator $authenticator */
        $authenticator = self::$authenticatorModel->getAuthenticatorByID(CssColorAuthenticator::getType());

        $this->assertInternalType('object', $authenticator);
        $this->assertEquals(get_class($authenticator), CssColorAuthenticator::class);
        $this->assertEquals($color, $authenticator->getAuthenticatorInfo()['ui']['backgroundColor']);
    }

    /**
     * Test invalid/unsupported css colors.
     *
     * @dataProvider invalidCssColorsProvider
     * @expectedException  \Garden\Schema\ValidationException
     *
     * @param $color
     *
     * @throws \Garden\Container\ContainerException
     * @throws \Garden\Container\NotFoundException
     * @throws \Garden\Web\Exception\ClientException
     * @throws \Garden\Web\Exception\NotFoundException
     * @throws \Garden\Web\Exception\ServerException
     *
     */
    public function testInvalidColors($color = null) {
        CssColorAuthenticator::setCssColor($color);
        self::$authenticatorModel->getAuthenticatorByID(CssColorAuthenticator::getType());
    }

    /**
     * Valid/supported color provider for {@link testValidColors}
     *
     * @return array
     */
    public function validCssColorsProvider() {
        return [
            'long hex' => ['#F8C1A0'],
            'short hex' => ['#09D'],
            'lower case hex' => ['#abcdef'],
            'rgb(0, 0, 0)' => ['rgb(0, 0, 0)'],
            'rgb(0,0, 0) Goofy spacing' => ['rgb(0, 0, 0)'],
            'rgb(255, 255, 255)' => ['rgb(255, 255, 255)'],
            'rgba(192, 7, 10, 0)' => ['rgba(192, 7, 10, 0)'],
            'rgba(192, 7, 10, 1)' => ['rgba(192, 7, 10, 1)'],
            'rgba(192, 7, 10, 0.34)' => ['rgba(192, 7, 10, 0.34)'],
            'rgba(192, 7, 10, 0.34) Goofy spacing' => ['rgba(192,7, 10,0.34)'],
        ];
    }

    /**
     * Invalid color provider for {@link testValidColors}
     *
     * @return array
     */
    public function invalidCssColorsProvider() {
        return [
            'hsl' => ['hsl(120, 100%, 50%)'],
            'hsla' => ['hsla(120, 100%, 50%, 0.3)'],
            'named color (blue)' => ['Blue'],
            'hex with no #' => ['FF1100'],
        ];
    }
}
