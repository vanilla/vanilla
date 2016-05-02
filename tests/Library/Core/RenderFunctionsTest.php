<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2016 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace VanillaTests\Library\Core;

/**
 * Test some of the functions in functions.render.php.
 */
class RenderFunctionsTest extends \PHPUnit_Framework_TestCase {
    /**
     * Make sure the render functions are included.
     */
    public static function setUpBeforeClass() {
        parent::setUpBeforeClass();
        require_once PATH_ROOT.'/library/core/functions.render.php';
    }

    /**
     * Test a basic {@link userBuilder()}.
     */
    public function testUserBuilder() {
        $userRow = [
            'InsertUserID' => 123,
            'InsertName' => 'Fank',
            'InsertPhoto' => 'foo.png',
            'InsertEmail' => 'foo@noreply.com',
            'InsertGender' => 'mf'
        ];

        $user = userBuilder($userRow, 'Insert');
        $this->assertSame(array_values($userRow), array_values((array)$user));
    }

    /**
     * Test the multiple prefix version of {@link userBuilder()}.
     */
    public function testUserBuilderMultiplePrefixes() {
        $userRow = [
            'InsertUserID' => 123,
            'InsertUserName' => 'Frank',
            'FirstUserID' => 234,
            'FirstName' => 'Barry'
        ];
        
        $user = userBuilder($userRow, ['First', 'Insert']);
        $this->assertSame(234, $user->UserID);
        $this->assertSame('Barry', $user->Name);

        $user = userBuilder($userRow, ['Blarg', 'First']);
        $this->assertSame(234, $user->UserID);
        $this->assertSame('Barry', $user->Name);
    }
}
