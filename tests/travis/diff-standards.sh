#!/usr/bin/env bash

if [ -z "$1" ]; then
    echo "A branch is required." >&2
    exit 1
fi
DIFF_BRANCH=$1

if [ -z "$2" ]; then
    BUILD_DIR=$(pwd)
else
    BUILD_DIR=$2
fi
GIT_DIFF_FILENAME=$BUILD_DIR/git-diff.txt
PHPCS_DIFF_FILENAME=$BUILD_DIR/phpcs-diff.json

cd $BUILD_DIR

# Begin folding in Travis.
echo "travis_fold:start:coding_standards"
echo "Verify changed code against coding standards."

# Without this, Travis only has a ref to the current branch in a shallow clone. Other branches cannot be compared.
echo "Updating available refs..."
git config --add remote.origin.fetch +refs/heads/*:refs/remotes/origin/*
git fetch --all

GIT_DIFF=$(git diff HEAD $DIFF_BRANCH '*.php')
if [ -z "$GIT_DIFF" ]; then
    echo "No PHP file changes detected."
    echo "travis_fold:end:coding_standards"
    exit
fi
echo "Exporting branch diff of $DIFF_BRANCH to $GIT_DIFF_FILENAME..."
rm -f $GIT_DIFF_FILENAME
echo -n "$GIT_DIFF" > $GIT_DIFF_FILENAME

echo "Exporting full PHP_CodeSniffer scan of changed files to $PHPCS_DIFF_FILENAME..."
rm -f $PHPCS_DIFF_FILENAME
./vendor/bin/phpcs --standard=./vendor/vanilla/standards/code-sniffer/Vanilla --report=json $(git diff --name-only HEAD $DIFF_BRANCH -- '*.php') > $PHPCS_DIFF_FILENAME

echo "Comparing results of PHP_CodeSniffer scan with changed lines from branch diff..."
./vendor/bin/diffFilter --phpcs $GIT_DIFF_FILENAME $PHPCS_DIFF_FILENAME

# End folding in Travis.
echo "travis_fold:end:coding_standards"
