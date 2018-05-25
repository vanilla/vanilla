#!/usr/bin/env bash

cd $TRAVIS_BUILD_DIR

# Travis will get it's cache files which can prevent a clone.
if [ -f "$TRAVIS_BUILD_DIR/vanilla/applications/dashboard/node_modules" ]; then
    mv "$TRAVIS_BUILD_DIR/vanilla/" "$TRAVIS_BUILD_DIR/vanilla-temp/"
fi


git clone --depth=50 --branch=$TRAVIS_PULL_REQUEST_BRANCH https://github.com/vanilla/vanilla vanilla

# Travis will get it's cache files which can prevent a clone.
if [ -f "$TRAVIS_BUILD_DIR/vanilla-temp" ]; then
    mv "$TRAVIS_BUILD_DIR/vanilla-temp/applications/dashboard/node_modules" \ "$TRAVIS_BUILD_DIR/vanilla/applications/dashboard/node_modules"
    mv "$TRAVIS_BUILD_DIR/vanilla-temp/tests/node_modules" \
    "$TRAVIS_BUILD_DIR/vanilla/tests/node_modules"
fi

# Symlink in the editor plugin
cd "$TRAVIS_BUILD_DIR/vanilla/plugins"
ln -s ../../plugins/* ./

cd "$TRAVIS_BUILD_DIR/vanilla/applications/dashboard"
yarn install --pure-lockfile
cd "$TRAVIS_BUILD_DIR/vanilla/plugins/rich-editor"
yarn install --pure-lockfile
cd "$TRAVIS_BUILD_DIR/vanilla/tests"
yarn install --pure-lockfile
cd "$TRAVIS_BUILD_DIR"
cd ../
ls
