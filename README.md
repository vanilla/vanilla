_Vanilla uses Composer!
You cannot clone this repo right into a web directory - it requires a build step. [Learn more](https://open.vanillaforums.com/discussion/31083/vanilla-now-uses-compose) or just [download the latest stable build](https://open.vanillaforums.com/addon/vanilla-core) instead_.

![Vanilla](http://cdn.vanillaforums.com/vanilla-black-logo-400.svg)

[![Build Status](https://img.shields.io/travis/vanilla/vanilla.svg?style=flat-square)](https://travis-ci.org/vanilla/vanilla)
[![PR Stats](http://issuestats.com/github/vanilla/vanilla/badge/pr?style=flat-square)](http://issuestats.com/github/vanilla/vanilla)
[![Issue Stats](http://issuestats.com/github/vanilla/vanilla/badge/issue?style=flat-square)](http://issuestats.com/github/vanilla/vanilla)

## Howdy, Stranger!

Vanilla was born out of the desire to create flexible, customizable, and downright entertaining
community solutions. Vanilla has been used to power tens of thousands of community forums around the world
and we couldn't be happier if you've decided to use Vanilla to grow yours.

Every community is unique. Vanilla is a finely-crafted platform on which designers and developers
can build a custom-tailored environment that meets your community's particular needs.

## 5 reasons Vanilla is the sweetest forum

1. We've reimagined traditional forums for mass-appeal.
1. Our theming flexibility is second-to-none.
1. Impossibly good integration options with single sign-ons and embedding.
1. The best tools available for community management.
1. Curated features with great plugin options, not the kitchen sink.

## Open Source

Vanilla is free, open source software distributed under the GNU GPL2.
We accept and encourage contributions from our community and sometimes give hugs in return.
You can join us on the [Vanilla Community Forums](https://open.vanillaforums.com/discussions) to be part of that discussion.

The latest stable release is always [listed here](https://open.vanillaforums.com/addon/vanilla-core). Currently, it is the `release/2.3` branch.

New plugins and themes can be listed in the [Official Addon Directory](https://open.vanillaforums.com/addon/).
We encourage addon developers to release their code under the GPL as well, but do not require it.

## Cloud Solution

Vanilla Forums provides an official cloud hosting solution at [vanillaforums.com](http://vanillaforums.com)
with a 1-click install, automatic upgrades, amazing professional support, incredible scalability,
integration assistance, theming and migration services, and exclusive features. For the very best Vanilla forum experience,
you can skip the rest of this technical stuff and [go there directly](http://vanillaforums.com/plans).

If you professionally run a large community or enterprise forum, our cloud solution will make the best technical and economic sense by far.

## Self-Hosting Requirements

We strongly recommend:

*  **PHP 5.6** or **7.0**.
*  MySQL 5.5 or higher (or Percona/MariaDB equivalent).

If your server is not running PHP 5.6 or higher, **you must address this immediately**. All lower versions of PHP are no longer updated and are potentially unsafe.

Our _minimum_ requirements are now:

* PHP 5.4 or newer with `--enable-mbstring` and the pdo_mysql module enabled.
* If you intend to [Migrate to Vanilla](#migrating-to-vanilla) you will _also_ need PHP with `--with-mysqli`.
* MySQL 5.0 or newer (or Percona/MariaDB equivalent).

To use our social plugins, PHP's [OpenSSL support](http://php.net/manual/en/openssl.installation.php) must be enabled.

Vanilla ships with a `.htaccess` file required for Apache support. Using nginx or IIS may require additional configuration.

On the client side, Vanilla should run & look good in just about any modern browser.
Still using IE? How exotic. You'll want IE8 or greater. IE7 *might* work if you squint hard and click gently, but we make no promises.

We've been natively mobile since before it was cool. Vanilla ships with a mobile-optimized theme enabled
by default for all smartphones & tablets. Heck, it even works on the PlayStation Vita.

## Installation

Vanilla is built to be simple, and its installation is no exception.

* Upload Vanilla's [pre-built version](https://open.vanillaforums.com/addon/vanilla-core) to your server.
* Confirm the cache, conf, and uploads folders are writable by PHP.
* Navigate to that folder in your web browser.
* Follow the instructions on screen.

## Upgrading

Follow these steps to upgrade Vanilla when a new stable release is announced.

* Backup your database, `.htaccess` and `conf/config.php` file somewhere safe.
* Upload the new release's files so they overwrite the old ones.
* Delete all files in `/cache`.
* Go to `yourforum.com/utility/update` to run any database updates needed. (404? See next paragraph.)
* If it fails, try it a second times by refreshing the page.

If your forum still uses URLs including `?p=`:

Support for this URL structure is ending after 2.3 in favor of "pretty" URLs so it's time to make the switch. First, confirm your server is setup to handle rewrites. On Apache, using the `.htaccess` file provided will accomplish this. Additional setup is required on nginx and other platforms. Test whether it is working by visiting `/discussions` - if you see a discussions list (rather than a 404), it is likely setup correctly. Then, open `conf/config.php` and find the line with `$Configuration['Garden']['RewriteUrls'] = false;` and **delete the entire line**. Your site should immediately switch to "pretty" URL paths instead of using the 'p' parameter. If there is a problem, re-add the line to your config and do further troubleshooting.

To upgrade from **2.1 or earlier**:

* Update any locales you have installed (their name format changed in 2.2). Verify they are working after upgrade.
* Apache users must update their `.htaccess` file.
* Delete these files from your server if they exist: `/themes/mobile/views/discussions/helper_functions.php` and  `/applications/dashboard/views/default.master.php`

To upgrade from Vanilla **1.0** requires a full migration (see next section). Themes and plugins are not compatible. Backup your Vanilla 1 data and files completely, then delete them from your server before attempting to install Vanilla 2.

## Migrating to Vanilla

* Get [Vanilla Porter](https://open.vanillaforums.com/addon/porter-core) and verify it supports your platform.
* Read the Advanced Uses notes on that page.
* Upload it to your current server.
* Navigate to the file in your web browser & run it.
* Take the file it produces and import it to Vanilla.

## Getting Help

* [Troubleshooting upgrades & installs](http://docs.vanillaforums.com/developers/troubleshooting/)
* [Official documentation](http://docs.vanillaforums.com)
* [Vanilla community forums](https://open.vanillaforums.com/discussions)
* [Official cloud hosting with professional support & migration services](http://vanillaforums.com/plans)

## Contributing

* Troubleshoot issues you run into on the community forum so everyone can help & reference it later.
* Got an idea or suggestion? Use the [forum](https://open.vanillaforums.com/discussions) to discuss it.
* File detailed [issues](https://github.com/vanilla/vanilla/issues) on GitHub (version number, what you did, and actual vs expected outcomes).
* Sign the [Contributors' Agreement](https://open.vanillaforums.com/contributors) to send us code.
* Use pull requests against the `master` branch.
* Keep our to-do list fresh by reviewing our open issues for resolved or duplicated items.

## Building with Phing

Vanilla includes a  buildfile for [Phing](https://www.phing.info/), a build system for PHP, in the build directory. Running the `phing` command from the build directory will create a deploy-ready copy of Vanilla.  This process automatically fetches dependencies with Composer, filters out any unnecessary developer files (Git files/directories, .editorconfig, unit tests, etc.) and compresses the result into an archive.

## Version Control Strategy

We've adopted the [git flow branching model](http://nvie.com/posts/a-successful-git-branching-model) in our projects.
The creators of git flow released a [short intro video](http://vimeo.com/16018419) to explain the model.

The `master` branch is production-ready for our cloud product but is not yet vetted for open source release (alternate platforms & configurations).
Reviewed, stable changes land against `master` via pull-request.

Our open source release branches are named by version number, e.g. `release/2.3`.
We begin release branches with a beta (b1) designation and progress them thru release candidate to stable.
All open source releases (included pre-releases) are tagged.

## Reporting Security Issues

Please disclose security issues responsibly by emailing support@vanillaforums.com with a full description.
We'll work on releasing an updated version as quickly as possible.
Please do not email non-security issues; use the [issue tracker](https://github.com/vanilla/vanilla/issues) instead.

## Legal Stuff
Copyright &copy; 2008-2016 Vanilla Forums Inc.

Vanilla Forums is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License
as published by the Free Software Foundation, either version 2 of the License, or (at your option) any later version.
Vanilla Forums is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU General Public License for more details. You should have received a copy of the GNU General Public License
along with Vanilla Forums.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com

## Bonk!

Just kidding, everything's awesome. ![dance](http://cdn.vanillaforums.com/dance.gif)
