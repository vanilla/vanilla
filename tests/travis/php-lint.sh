#!/bin/bash

me=`basename $0`
if [ $# -lt 1 ]; then
    echo "Usage: $me [-q] <folder>"
    exit 1;
fi

quick=false
if [ "$1" = "-q" ]; then
    quick=true
    dir=$2
else
    dir=$1
fi
dir=$(realpath ${dir})

if [ ! -d "$dir" ]; then
    echo "No such folder: $dir"
    exit 1;
fi

echo "Checking syntax for php files in '$dir'"

if [ "$quick" = true ]; then
    echo "Quick mode is enabled. Execution will stop if an error is found."
fi

errored=false

for file in `find $dir -type f -regex ".*\.php" | grep php`
do
    out=`php -l $file`
    code=$?

    if [ ! $code -eq 0 ]; then
      echo $out;
      errored=true
      if [ "$quick" = true ]; then
          exit 1;
      fi
    fi
done

if [ "$errored" = true ]; then
    echo "PHP files in '$dir' contain syntax errors."
    exit 1;
fi

echo "Syntax check completed successfully.";
exit 0;
