<img src="https://user-images.githubusercontent.com/1770056/51494323-414e8980-1d86-11e9-933c-e647b5ea49f4.png" alt="Vanilla Repo Logo" width="500" />

![License](https://img.shields.io/github/license/vanilla/vanilla.svg)  
[License](https://github.com/vanilla/vanilla/blob/master/LICENSE)  
![CircleCI](https://circleci.com/gh/vanilla/vanilla/tree/master.svg?style=svg)  
[CircleCI](https://circleci.com/gh/vanilla/vanilla/tree/master)  

## Howdy, Stranger!

Vanilla was born out of the desire to create flexible, customizable, and downright entertaining community solutions. Vanilla has been used to power tens of thousands of community forums around the world, and we couldn't be happier if you've decided to use Vanilla to grow yours.

| Forum                                                                                                         | Rich Editor                                                                                                   | Dashboard                                                                                                     |
| ------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------- |
| ![image](https://user-images.githubusercontent.com/1770056/51584623-2a9e5480-1ea4-11e9-9650-b37b0d6da609.png) | ![image](https://user-images.githubusercontent.com/1770056/51584966-8fa67a00-1ea5-11e9-8fe2-1b110035a025.png) | ![image](https://user-images.githubusercontent.com/1770056/51422470-00cfef80-1b7d-11e9-9d3f-25ada61cecea.png) |

Every community is unique. Vanilla is a finely crafted platform where designers and developers can build a custom-tailored environment that meets your community's needs.

## 5 Reasons Vanilla is the Sweetest Forum

1. We've reimagined traditional forums for mass appeal.
2. Our theming flexibility is second to none.
3. Impossibly good integration options with single sign-on and embedding.
4. The best tools available for community management.
5. Curated features with great plugin options, not the kitchen sink.

## Installation

The current version of Vanilla requires PHP 7.2+ and MySQL 5.7+. The following PHP extensions are also needed: cURL, DOM, Fileinfo, GD, intl, JSON, libxml, and PDO.

- [Local Installation](https://github.com/vanilla/vanilla-docker)
- [Our Cloud Hosting Solution](https://vanillaforums.com/en/plans/)
- [The Basics of Self Hosting](https://docs.vanillaforums.com/developer/installation/self-hosting/)

*Upgrading from an earlier version of Vanilla? See [our upgrade notes](https://docs.vanillaforums.com/developer/installation/self-hosting/#upgrading).*

## Changes to Full-Text Indexing

Full-Text index support has been disabled by default as of Vanilla 4. To enable full-text index support, add a `FullTextIndexing` key under the `Database` section of your site config and set its value to `true`. **Failure to add this config value will result in full-text indexes being removed from Vanilla's database tables.**

## Contributing

- Local Development - [Environment](https://github.com/vanilla/vanilla-docker), [Configuration & Debugging](https://docs.vanillaforums.com/developer/tools/environment/) & [Build Tools](https://docs.vanillaforums.com/developer/tools/building-frontend/).
- [Running Unit Tests](https://github.com/vanilla/vanilla/blob/master/tests/README.md).
- Coding Standard - [PHP](https://docs.vanillaforums.com/developer/contributing/coding-standard-php/), [Typescript](https://docs.vanillaforums.com/developer/contributing/coding-standard-typescript/), [Database Naming](https://docs.vanillaforums.com/developer/contributing/database-naming-standards/)
- [Writing Pull Requests](https://docs.vanillaforums.com/developer/contributing/pull-requests/)
- [Contributing Guidelines](https://github.com/vanilla/vanilla/blob/master/CONTRIBUTING.md)
- [Contributing to Translations](https://github.com/vanilla/locales/blob/master/README.md)

## Getting Help

- [Troubleshooting upgrades & installs](http://docs.vanillaforums.com/developers/troubleshooting/)
- [Official documentation](http://docs.vanillaforums.com)
- [Vanilla community forums](https://open.vanillaforums.com/discussions)
- [Official cloud hosting with professional support & migration services](https://vanillaforums.com/plans)
- [File a detailed bug report](https://github.com/vanilla/vanilla/issues/new?template=bug_report.md)
- [Plan out a new feature](https://github.com/vanilla/vanilla/issues/new?template=new_feature.md)

## Reporting Security Issues

Please disclose security issues responsibly by emailing support@vanillaforums.com with a full description or joining our [bug bounty program](https://hackerone.com/vanilla). We cannot award bounties outside that program.

We'll work on releasing an updated version as quickly as possible. Please do not email non-security issues; use the [issue tracker](https://github.com/vanilla/vanilla/issues) instead.

## Building Releases

Vanilla releases are built using [Phing](https://www.phing.info/) to create pre-built deploy-ready copies of Vanilla. To build these, run the following in the root of the repository:
