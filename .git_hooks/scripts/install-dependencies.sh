#!/bin/bash

# - 'composer update' if changed composer.json

ESC_SEQ="\x1b["
COL_RESET=$ESC_SEQ"39;49;00m"
COL_RED=$ESC_SEQ"0;31m"
COL_GREEN=$ESC_SEQ"0;32m"
COL_YELLOW=$ESC_SEQ"0;33m"

changed_files="$(git diff-tree -r --name-only --no-commit-id HEAD@{1} HEAD)"

check_run() {
    echo "$changed_files" | grep -q "$1" && echo " * changes detected in $1" && echo " * running $2" && eval "$2"
}

check_run composer.json "composer update"
exit 0
