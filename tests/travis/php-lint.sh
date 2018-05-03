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

for file in `find $dir -type f -name "*.php"`
do
    haveerror=false
    out=`php -l $file 2>/dev/null`
    out=`echo $out | xargs`
    code=$?

    if [ ! $code -eq 0 ]; then
        haveerror=true
    fi

    if [[ ! $out =~ ^"No syntax errors" ]]; then
        haveerror=true
    fi

    if [ "$haveerror" = true ]; then
        echo "$file: $out"
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
