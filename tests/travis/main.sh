#!/usr/bin/env bash

cd $TRAVIS_BUILD_DIR

DO_COVERAGE=false
DO_LINT=false

if [ "$TRAVIS_BRANCH" = "master" ]; then
    DO_COVERAGE=true
    DO_LINT=true
fi

if [ "$DO_LINT" = true ]; then
    .circleci/scripts/php-lint.sh ./applications
    .circleci/scripts/php-lint.sh ./conf
    .circleci/scripts/php-lint.sh ./library
    .circleci/scripts/php-lint.sh ./plugins
    .circleci/scripts/php-lint.sh ./themes
else
    echo "Skipping code linting..."
fi

if [ "$DO_LINT" = true ]; then
    ./vendor/bin/phpunit -c phpunit.xml.dist --coverage-clover=coverage.clover --exclude-group=ignore
else
    echo "Skipping code coverage..."
    ./vendor/bin/phpunit -c phpunit.xml.dist --exclude-group=ignore
fi

PHPUNIT_RESULT=$?

# Run standards check on pull requests.
if [ "$TRAVIS_PULL_REQUEST" != "false" ]; then
    ./.circleci/scripts/diff-standards.sh $TRAVIS_BRANCH $TRAVIS_BUILD_DIR
    PHPCODESNIFFER_RESULT=$?
else
    PHPCODESNIFFER_RESULT=0
    echo "Skipping coding standards check..."
fi

# Make sure all commands had a zero result.
exit $(($PHPUNIT_RESULT | $PHPCODESNIFFER_RESULT))
