#!/usr/bin/env bash

cd $TRAVIS_BUILD_DIR

# This directory might already exist (from the cache) but its not a big deal.
mkdir "$TRAVIS_BUILD_DIR/vanilla"
cd "$TRAVIS_BUILD_DIR/vanilla"

# Because cached files are already possibly here we can't do a clone.
git init
git remote add origin https://github.com/vanilla/vanilla
git pull --depth 50 origin $TRAVIS_PULL_REQUEST_BRANCH

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
