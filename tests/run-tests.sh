#!/bin/bash

DIR=$(realpath $(dirname "$0"))

if [ "$RUN" = 'lint' ]; then
    $DIR/php-lint.sh $DIR/..
elif [ "$RUN" = 'test' ]; then
    phpunit -c $DIR/../phpunit.xml.dist
fi
