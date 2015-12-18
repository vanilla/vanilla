# Dashboard Asset Compilation

## Compiling assets

The following instructions assume that... 

* You already have [Sass](http://sass-lang.com/install) and [SCSS-Lint](https://github.com/brigade/scss-lint) installed on your computer. 

* You have already installed Node.js on your computer. If this is not the case, please download and install the latest stable release from the official [Node.js download page](http://nodejs.org/download/). If you are using [Homebrew](http://brew.sh/), you can also install Node.js via the command line:
```sh
$ brew install node
```
> __Notice__: It is important that you install Node in a way that does not require you to `sudo`.

Once you have Node.js up and running, you will need to install the local dependencies using [npm](http://npmjs.org):

```sh
$ npm install
```

### Tasks

#### Build - `npm run build`

Compiles all theme assets using Grunt. SCSS stylesheets will be compiled to [`design/admin.css`](design/admin.css) and Javascript will be concatenated and output to [`js/dashboard.js`](js/dashboard.js).

#### Watch - `npm run watch`

Watches the assets for changes and runs the appropriate Grunt tasks. Also starts a LiveReload server that will push the changes to your Vanilla installation automatically. To make use of this, you may want to install and use [LiveReload's browser extensions](http://livereload.com/extensions/).
