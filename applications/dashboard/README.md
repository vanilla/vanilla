# Dashboard asset compilation setup

We don't change CSS files directly. We use Sass for better-structured CSS, which requires a build process. You only need to do this if you're developing the core product. Otherwise, put any changes you want to make to Vanilla's CSS [in your theme instead](http://docs.vanillaforums.com/developer/theming/quickstart/).

## Dependencies

You will need:

* [Homebrew](http://brew.sh/) (macOS only. Optional but strongly recommended.)
* Ruby (**not** the default install if using macOS)
* [Sass](http://sass-lang.com/install)
* [SCSS-Lint](https://github.com/brigade/scss-lint)
* [Node.js](http://nodejs.org/download/) (latest stable; do **not** install using `sudo`.)

Once you have Homebrew, this will get everything else you need globally:

```sh
$ brew install node

$ brew install ruby

$ gem install sass

$ gem install scss-lint
```

Then install the local dependencies using [npm](http://npmjs.org):

```sh
$ cd /path/to/vanilla

$ cd applications/dashboard

$ npm install
```

## Tasks

__Build__ (run one time)

```sh
$ npm run build
```

Compiles all theme assets using Grunt. SCSS stylesheets will be compiled to [`design/admin.css`](design/admin.css) and Javascript in the `js/src` directory will be concatenated and output to [`js/dashboard.js`](js/dashboard.js).

__Watch__ (run continuously)

```sh
$ npm run watch
```

Watches the assets for changes and runs the appropriate Grunt tasks. Also starts a LiveReload server that will push the changes to your Vanilla installation automatically. To make use of this, you may want to install and use [LiveReload's browser extensions](http://livereload.com/extensions/).
