#!/bin/bash
#
# @author Eduardo Garcia Julia <eduardo.garciajulia@vanillaforums.com>
# @copyright 2009-2019 Vanilla Forums Inc.
# @license GPL-2.0-only
#

echo "Deleting previous './tests.result'"
rm -rf ./tests.result

echo "Running 'composer dump-autoload -o'"
composer dump-autoload -o

echo "Running 'phpunit phpunit.xml'"
../../../../vendor/bin/phpunit -cschedulerTests.xml
