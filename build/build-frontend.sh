#!/usr/bin/env bash
echo ""
echo "Installing node_modules"
yarn install
INSTALL_RESULT=$?

echo ""
echo "Building frontend assets"
yarn build
BUILD_RESULT=$?

# Make sure all commands had a zero result.
exit $(($INSTALL_RESULT | $BUILD_RESULT))
