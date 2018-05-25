#!/usr/bin/env bash

cd $TRAVIS_BUILD_DIR

if [ ! -f "$TRAVIS_BUILD_DIR/vanilla" ]; then
    mkdir "$TRAVIS_BUILD_DIR/vanilla"
fi

cd "$TRAVIS_BUILD_DIR/vanilla"
git init
git remote add origin https://github.com/vanilla/vanilla
git pull origin $TRAVIS_PULL_REQUEST_BRANCH

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
