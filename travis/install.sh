#!/usr/bin/env bash

cd $TRAVIS_BUILD_DIR

git clone --depth=50 --branch=master https://github.com/vanilla/vanilla vanilla

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
