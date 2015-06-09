_Cloning to your production site? Checkout the 2.1 branch! It's our vetted stable release._

![Vanilla](http://cdn.vanillaforums.com/vanilla-black-logo-400.svg)

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
You can join us on the [Vanilla Community Forums](http://vanillaforums.org/discussions) to be part of that discussion.

The latest stable release is always [listed here](http://vanillaforums.org/addon/vanilla-core). Currently, it is the 2.1 branch.

New plugins and themes can be listed in the [Official Addon Directory](http://vanillaforums.org/addon/).
We encourage addon developers to release their code under the GPL as well, but do not require it.

## Cloud Solution

Vanilla Forums provides an official cloud hosting solution at [vanillaforums.com](http://vanillaforums.com)
with a 1-click install, automatic upgrades, amazing professional support, incredible scalability,
integration assistance, theming and migration services, and exclusive features. For the very best Vanilla forum experience,
you can skip the rest of this technical stuff and [go there directly](http://vanillaforums.com/plans).

If you professionally run a large community or enterprise forum, our cloud solution will make the best technical and economic sense by far.

## Self-Hosting Requirements

* PHP version 5.3 or newer with `--enable-mbstring`.
* pdo_mysql module must be enabled.
* MySQL 5 or newer.

Vanilla 2.2 requires PHP 5.3. Running `master` branch requires PHP 5.4, as will future official releases.

Vanilla is compatible up to PHP 5.6 (the current release) and we always recommend using the lastest stable PHP release whenever possible.

Vanilla ships with a `.htaccess` file required for Apache support. Nginx and IIS require additional configuration.

On the client side, Vanilla should run & look good in just about any modern browser.
Using IE? How exotic. You'll want IE8 or greater. IE7 *might* work if you squint hard and click gently, but we make no promises.

We've been natively mobile since before it was cool. Vanilla ships with a mobile-optimized theme enabled
by default for all smartphones & tablets. Heck, it even works on the PlayStation Vita.

## Installation

Vanilla is built to be simple, and its installation is no exception.

* Upload this entire file structure up to your web server.
* Confirm the cache, conf, and uploads folders are writable by PHP.
* Navigate to that folder in your web browser.
* Follow the instructions on screen.

## Upgrading

Follow these steps to upgrade Vanilla when a new stable release is announced.


* Backup your database, `.htaccess` and `conf/config.php` file somewhere safe.
* Upload the new release's files so they overwrite the old ones.
* Go to `yourforum.com/utility/update` to force any updates needed.
* If it fails, try it a second times by refreshing the page.

To upgrade from **2.0.18 or earlier**, add these steps:

* Delete the file [`/themes/mobile/views/discussions/helper_functions.php`](https://github.com/vanillaforums/Garden/blob/2.0/themes/mobile/views/discussions/helper_functions.php)
* Delete the file [`/applications/dashboard/views/default.master.php`](https://github.com/vanilla/vanilla/blob/2.0/applications/dashboard/views/default.master.php)

To upgrade from Vanilla **1.0**, you must export your data using the Vanilla Porter as if it were a migration. Your theme and any customizations will need to be recreated. Backup your Vanilla 1 data and files completely, then delete them from your server before attempting to install Vanilla 2.

## Migrating to Vanilla

* Get [Vanilla Porter](http://vanillaforums.org/addon/porter-core) and verify it supports your platform.
* Read the Advanced Uses notes on that page.
* Upload it to your current server.
* Navigate to the file in your web browser & run it.
* Take the file it produces and import it to Vanilla.

## Getting Help

* [Troubleshooting upgrades & installs](http://docs.vanillaforums.com/developers/troubleshooting/)
* [Official documentation](http://docs.vanillaforums.com)
* [Vanilla community forums](http://vanillaforums.org/discussions)
* [Official cloud hosting with professional support & migration services](http://vanillaforums.com/plans)

## Contributing

* Troubleshoot issues you run into on the community forum so everyone can help & reference it later.
* Got an idea or suggestion? Use the [forum](http://vanillaforums.org/discussions) to discuss it.
* File detailed [issues](https://github.com/vanilla/vanilla/issues) on GitHub (version number, what you did, and actual vs expected outcomes).
* Sign the [Contributors' Agreement](http://vanillaforums.org/contributors) to send us code.
* Use pull requests against the correct release (currently `stage` for most changes).
* Keep our to-do list fresh by reviewing our open issues for resolved or duplicated items.

## Version Control Strategy

We've adopted the [git flow branching model](http://nvie.com/posts/a-successful-git-branching-model) in our projects.
The creators of git flow released a [short intro video](http://vimeo.com/16018419) to explain the model.

The `master` branch is production-ready for our cloud product but is not yet vetted for open source release (alternate platforms & configurations).
Reviewed, stable changes land against `master` via pull-request.

Our open source release branches are named by version number, e.g. `release/2.2`.
We begin release branches with a beta (b1) designation and progress them thru release candidate to stable.
All open source releases (included pre-releases) are tagged.

## Reporting Security Issues

Please disclose security issues responsibly by emailing support@vanillaforums.com with a full description.
We'll work on releasing an updated version as quickly as possible.
Please do not email non-security issues; use the [issue tracker](https://github.com/vanilla/vanilla/issues) instead.

## Legal Stuff
Copyright &copy; 2008-2015 Vanilla Forums Inc.

Vanilla Forums is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License
as published by the Free Software Foundation, either version 2 of the License, or (at your option) any later version.
Vanilla Forums is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU General Public License for more details. You should have received a copy of the GNU General Public License
along with Vanilla Forums.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com

## Bonk!

Just kidding, everything's awesome. ![dance](http://cdn.vanillaforums.com/dance.gif)
