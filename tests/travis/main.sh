#!/usr/bin/env bash

cd $TRAVIS_BUILD_DIR

DO_COVERAGE=false
DO_LINT=false

if [ "$TRAVIS_BRANCH" = "master" ]; then
    DO_COVERAGE=true
    DO_LINT=true
fi

if [ "$DO_LINT" = true ]; then
    tests/travis/php-lint.sh ./applications
    tests/travis/php-lint.sh ./conf
    tests/travis/php-lint.sh ./library
    tests/travis/php-lint.sh ./plugins
    tests/travis/php-lint.sh ./themes
else
    echo "Skipping code linting..."
fi

if [ "$DO_LINT" = true ]; then
    ./vendor/bin/phpunit -c phpunit.xml.dist --coverage-clover=coverage.clover
else
    echo "Skipping code coverage..."
    ./vendor/bin/phpunit -c phpunit.xml.dist
fi
