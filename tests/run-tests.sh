#!/bin/bash

DIR=$(realpath $(dirname "$0"))

if [ "$CI_STEP" = 'lint' ]; then
    $DIR/php-lint.sh $DIR/..
elif [ "$CI_STEP" = 'test' ]; then
    phpunit -c $DIR/../phpunit.xml.dist
fi
