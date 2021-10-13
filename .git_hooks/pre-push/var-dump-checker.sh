#!/bin/bash

ESC_SEQ="\x1b["
COL_RESET=$ESC_SEQ"39;49;00m"
COL_RED=$ESC_SEQ"0;31m"
COL_GREEN=$ESC_SEQ"0;32m"
COL_YELLOW=$ESC_SEQ"0;33m"

echo
printf "$COL_YELLOW%s$COL_RESET\n" "Running pre-push hook: \"var-dump-checker\""

./vendor/bin/var-dump-check --laravel --exclude bootstrap --exclude node_modules --exclude vendor .

# If the grep command has no hits - echo a warning and exit with non-zero status.
if [ $? == 1 ]; then
    printf "$COL_RED%s$COL_RESET\r\n\r\n" "Some var_dump usage found. Please fix your code"
    exit 1
fi

echo "Okay"
exit 0
