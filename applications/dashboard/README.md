# Dashboard Asset Compilation

We don't change CSS files directly. We use Sass for better-structured CSS, which requires a build process. You only need to do this if you're developing the core product. Otherwise, put any changes you want to make to Vanilla's CSS [in your theme instead](http://docs.vanillaforums.com/developer/theming/quickstart/).

## Installing Dependencies

Compiling the assets for the dashboard application has a few dependencies. You will need [node.js](https://nodejs.org/en/) and [yarn](https://yarnpkg.com/en/).

### For macOS

MacOS users can easily install the dependencies with [Homebrew](http://brew.sh/). Using homebrew is optional but is generally the easiest setup. You can also install them manually using the above links.

```bash
brew install node
brew install yarn
```

### For Debian/Ubuntu Linux
```bash
curl -sL https://deb.nodesource.com/setup_8.x | sudo -E bash -
curl -sS https://dl.yarnpkg.com/debian/pubkey.gpg | sudo apt-key add -
echo "deb https://dl.yarnpkg.com/debian/ stable main" | sudo tee /etc/apt/sources.list.d/yarn.list

sudo apt-get update && sudo apt-get install nodejs yarn
```

Then install the local dependencies using `yarn install`.

```sh
cd /path/to/vanilla
cd applications/dashboard

yarn install
```

## Tasks

**Build** (Run once)

```sh
$ yarn build
```

Compiles all theme assets using Grunt. SCSS stylesheets will be compiled to [`design/admin.css`](design/admin.css) and Javascript in the `js/src` directory will be concatenated and output to [`js/dashboard.js`](js/dashboard.js).

**Watch** (run continuously)

```sh
$ yarn watch
```

Watches the assets for changes and runs the appropriate re-runs the individual parts of the build. Also starts a LiveReload server that will automatically reload your browser after every compilation. To make use of this, install the [LiveReload browser extension](http://livereload.com/extensions/).
