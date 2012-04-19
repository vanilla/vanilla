# Vanilla Forums

**Welcome!** Vanilla was born out of the desire to create flexible, customizable, and downright entertaining community solutions. Vanilla has been used to power over 400,000 community forums around the world and we couldn't be happier that you've decided to use Vanilla to grow yours.

# Source Control Ideology

To keep things simple and manageable, we've adopted the [git flow branching model](http://nvie.com/posts/a-successful-git-branching-model) in our projects. The creators of git flow have released a [short introduction video](http://vimeo.com/16018419) to explain their model.

## master

The *master* branch of the vanillaforums repository will always contain our latest production (release) code. It should be the most stable source code you can download from us, but also the oldest. New code only gets into *master* when we release a new version or create a hotfix.

## develop

All of our unreleased development work ends up in the *develop* branch. Sometimes it is committed directly, other times it comes from merged hotfixes again a release, and other times it comes from a merged feature branch. This branch will always contain the most bleeding edge vanillaforums code, so it sometimes has bugs and unfinished features. Use this at your own risk, and avoid deploying it in production.

## feature/x

Feature branches are work-in-progress branches that contain large chunks of new or modified code for a single feature or refactoring task. They are branched to preserve the stability of the *develop* branch during fairly destructive code changes.

## hotfix/x

Hotfixes are branched from master and exist to fix small bugs that are detected in a release after it has been tagged in *master*. These branches are usually small and concise, and are merged back into *master* and *develop* once they are completed. They should never be new features.
