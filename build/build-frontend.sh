#!/usr/bin/env bash
yarn install
INSTALL_RESULT=1

yarn build
BUILD_RESULT=$?

# Make sure all commands had a zero result.
exit $(($INSTALL_RESULT | $BUILD_RESULT))
