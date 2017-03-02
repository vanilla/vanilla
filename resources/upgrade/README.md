# Updating deletedfiles.txt

1. Navigate to this directory (`/resources/upgrade`).
2. In the following line, replace `{NewReleaseBranch}` and `{OldReleaseBranch}` with the corresponding branch names, then run in your terminal.
```
git diff --diff-filter=D --name-only origin/{OldReleaseBranch} origin/{NewReleaseBranch} > deletedfiles.txt
```
