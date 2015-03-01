# Emoji Extender

Change your emoji set!

## Compiling assets

The following instructions assume that you have already installed Node.js on your computer. If this is not the case, please download and install the latest stable release from the official [Node.js download page](http://nodejs.org/download/). If you are using [Homebrew](http://brew.sh/), you can also install Node.js via the command line:

```sh
$ brew install node
```

> __Notice__: It is important that you install Node in a way that does not require you to `sudo`.

Once you have Node.js up and running, you will need to install the local dependencies using [npm](npmjs.org):

```sh
$ npm install
```

### Tasks

#### Build - `npm run build`
Compiles all theme assets using Gulp. SCSS stylesheets will be compiled to [`design/custom.css`](design/custom.css) and Javascripts will be concatenated and output to [`js/custom.js`](js/custom.js).

#### Watch - `npm run watch`
Watches the assets for changes and runs the appropriate Gulp tasks. Also starts a Livereload server that will push the changes to your Vanilla installation automatically.

---
Copyright &copy; 2014 [Becky Van Bussel](http://vanillaforums.com).
