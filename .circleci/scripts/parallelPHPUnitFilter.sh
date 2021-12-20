#!/usr/bin/env bash

circleci tests glob $1 > tmpTestsToRun
# Trim off the cloud directories. They don't run directly.
sed -i '/^cloud/d' tmpTestsToRun

if [ -z $2 ]
then
  # APIv0 tests can't be parallelised and are run separately.
  sed -i '/APIv0/d' tmpTestsToRun

  # ElasticSearch tests are run separately.
  sed -i '/Elastic/d' tmpTestsToRun

  # Vendor tests should always be left off.
  sed -i '/vendor/d' tmpTestsToRun

  # Abstract tests aren't actually tests themselves.
  sed -i '/Abstract/d' tmpTestsToRun
else
  if [ $2 = "elastic-only" ]
  then
    # Only need ElasticSearch tests.
    sed -i '/Elastic/!d' tmpTestsToRun
  fi
fi

testFiles=$(cat tmpTestsToRun | circleci tests split)
testFileNames=$(echo $testFiles)
filter=$(php ./.circleci/scripts/makePHPUnitFilter.php "$testFileNames")
echo $filter;
