#!/bin/bash

# Lint all added php-files via 'php -l'

ROOT_DIR="$(pwd)/"
LIST=$(git diff-index --cached --name-only --diff-filter=ACMR HEAD)
ERRORS_BUFFER=""
ESC_SEQ="\x1b["
COL_RESET=$ESC_SEQ"39;49;00m"
COL_RED=$ESC_SEQ"0;31m"
COL_GREEN=$ESC_SEQ"0;32m"
COL_YELLOW=$ESC_SEQ"0;33m"
COL_BLUE=$ESC_SEQ"0;34m"
COL_MAGENTA=$ESC_SEQ"0;35m"
COL_CYAN=$ESC_SEQ"0;36m"

echo
printf "$COL_YELLOW%s$COL_RESET\n" "Running pre-commit hook: \"php-linter\""

for file in $LIST
do
    EXTENSION=$(echo "$file" | grep  -E ".php$|.module$|.inc$|.install$")
    if [ "$EXTENSION" != "" ]; then
        ERRORS=$(php -l $ROOT_DIR$file 2>&1 | grep "Parse error")
        if [ "$ERRORS" != "" ]; then
            if [ "$ERRORS_BUFFER" != "" ]; then
                ERRORS_BUFFER="$ERRORS_BUFFER\n$ERRORS"
            else
                ERRORS_BUFFER="$ERRORS"
            fi
            echo "Syntax errors found in file: $file "
        fi
    fi
done
if [ "$ERRORS_BUFFER" != "" ]; then
    echo
    echo "These errors were found in try-to-commit files: "
    echo -e $ERRORS_BUFFER
    echo
    printf "$COL_RED%s$COL_RESET\r\n\r\n" "Can't commit, fix errors first."
    exit 1
else
    echo "Okay"
    exit 0
fi
