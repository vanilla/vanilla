#!/usr/bin/env bash

cd $TRAVIS_BUILD_DIR

# This directory might already exist (from the cache) but its not a big deal.
mkdir "$TRAVIS_BUILD_DIR/vanilla"
cd "$TRAVIS_BUILD_DIR/vanilla"

# Because cached files are already possibly here we can't do a clone.
echo "";
echo "Cloning the main vanilla repository..."
git init
git remote add origin https://github.com/vanilla/vanilla
git pull --depth 50 origin master

# Symlink in the editor plugin
echo ""
echo "Symlinking plugins from the rich-editor repo..."
cd "$TRAVIS_BUILD_DIR/vanilla/plugins"
ln -s ../../plugins/* ./

echo ""
echo "Installing node_modules for dashboard..."
cd "$TRAVIS_BUILD_DIR/vanilla/applications/dashboard"
yarn install --pure-lockfile

echo ""
echo "Installing node_modules for rich-editor..."
cd "$TRAVIS_BUILD_DIR/vanilla/plugins/rich-editor"
yarn install --pure-lockfile

echo ""
echo "Installing node_modules for tests..."
cd "$TRAVIS_BUILD_DIR/vanilla/tests"
yarn install --pure-lockfile
cd "$TRAVIS_BUILD_DIR"
cd ../
