#!/usr/bin/env bash

if [ -z "$1" ]; then
    echo "No branch provided. Linting against default branch master"
    echo ""
    DIFF_BRANCH="master"
else
    echo "Linting compared to provided branch $1"
    DIFF_BRANCH=$1
fi

if [ -z "$2" ]; then
    BUILD_DIR=$(pwd)
else
    BUILD_DIR=$2
fi
GIT_DIFF_FILENAME=$BUILD_DIR/git-diff.txt
PHPCS_DIFF_FILENAME=$BUILD_DIR/phpcs-diff.json

cd $BUILD_DIR

echo "Verify changed code against coding standards."

# Without this, Circleci only has a ref to the current branch in a shallow clone. Other branches cannot be compared.
echo "Updating available refs..."
git config --add remote.origin.fetch +refs/heads/*:refs/remotes/origin/*
git fetch --all

GIT_DIFF=$(git diff $DIFF_BRANCH '*.php')
if [ -z "$GIT_DIFF" ]; then
    echo "No PHP file changes detected."
    exit 0
fi
echo "Exporting branch diff of $DIFF_BRANCH to $GIT_DIFF_FILENAME..."
rm -f $GIT_DIFF_FILENAME
echo -n "$GIT_DIFF" > $GIT_DIFF_FILENAME

echo "Exporting full PHP_CodeSniffer scan of changed files to $PHPCS_DIFF_FILENAME..."
rm -f $PHPCS_DIFF_FILENAME
./vendor/bin/phpcs --standard=./vendor/vanilla/standards/code-sniffer/Vanilla --report=json $(git diff --diff-filter=ACM --name-only $DIFF_BRANCH -- '*.php') > $PHPCS_DIFF_FILENAME

echo "Comparing results of PHP_CodeSniffer scan with changed lines from branch diff..."
echo ""
./vendor/bin/diffFilter --phpcs $GIT_DIFF_FILENAME $PHPCS_DIFF_FILENAME

CODESNIFFER_RESULT=$?

# Make sure this script exits with the same status as the PHP_CodeSniffer command.
exit $CODESNIFFER_RESULT
