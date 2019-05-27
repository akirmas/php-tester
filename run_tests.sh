#!/bin/sh
echo $(php test/index.php "$@")
find . -name '*.test.php' | xargs -L 1 --replace="{}" php {} "${@}"
