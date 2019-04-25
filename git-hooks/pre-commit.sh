#!/bin/sh
cd ..

STASH_NAME="pre-commit-$(date +%s)"
git stash save -q --keep-index --include-untracked $STASH_NAME

./run_tests.sh
RESULT=$?

#STASHES=$(git stash list)
#if [[ $STASHES == "$STASH_NAME" ]]; then
#  git stash pop -q 
#fi
git stash pop -q --index 0

[ $RESULT -ne 0 ] && echo $RESULT && exit 1
exit 0