# The vnla Command Line Tool

This folder contains the **vnla** command line tool that has some useful utilities for maintaining Vanilla.

## Installation

To install vnla you can follow these steps.

1. Make sure you have run `composer install` on the root directory of this repo. That will ensure that the command line tool will work here. You don't need to do a `composer install` from this directory.
2. In order to do backports you will need to install [hub](https://hub.github.com/) and have it alias the `git` command on your machine.
3. You should ensure that the [cli/bin/vnla](./bin/vnla) script is in your path so that you can execute it anywhere on your machine.

## Usage

The **vnla** tool has several commands. To get a list of commands and descriptions you can use the `--help` option.

```
vnla --help
```

To get the options for an individual command you can also use `--help` with the command.

```
vnla <command> --help
```

## The backport command

In general, the command that you are going to be the most concerned with is the `backport` command. This command allows you to backport any PR in any Github backed git repo to any branch. This utility is very useful as it is repeatable and provides consistent backport PRs. If a PR consists of several commits then this tool is a huge help.

There are a few things to know about the tool.

-   Always run `vnla backport` from the repo that you are backporting. It uses the current working directory to determine which git repo to use.
-   This command uses [hub](https://hub.github.com/) so make sure you have that set up properly on your machine.
-   You can run the command in _any_ git repo as long as its origin is Github. Go nuts!
-   Although the command is called "backport" you can really port a PR to any other branch. So you can use it to "forward port" too.
-   If you have a merge conflict then the backport tool won't fix it. You'll have to resolve it yourself. If you are not comfortable resolving conflicts then it is best to ask for help from a colleague.
-   This tool really does save a lot of time and bugs when porting PRs. Please try and use it!
