# Dashboard 2016

A new dashboard design for Vanilla.

function (obj) {
obj || (obj = {});
var __t, __p = '';
with (obj) {
__p += '## Compiling assets\n\nThe following instructions assume that you have already installed Node.js on your computer. If this is not the case, please download and install the latest stable release from the official [Node.js download page](http://nodejs.org/download/). If you are using [Homebrew](http://brew.sh/), you can also install Node.js via the command line:\n\n```sh\n$ brew install node\n```\n\n> __Notice__: It is important that you install Node in a way that does not require you to `sudo`.\n\nOnce you have Node.js up and running, you will need to install the local dependencies using [npm](http://npmjs.org):\n\n```sh\n$ npm install\n```\n\n### Tasks\n\n#### Build - `npm run build`\nCompiles all theme assets using Grunt. SCSS stylesheets will be compiled to [`design/custom.css`](design/custom.css) and Javascripts will be concatenated and output to [`js/custom.js`](js/custom.js).\n\n#### Watch - `npm run watch`\nWatches the assets for changes and runs the appropriate Grunt tasks. Also starts a Livereload server that will push the changes to your Vanilla installation automatically.\n';

}
return __p
}
---
Copyright &copy; 2016 [Becky Van Bussel](https://vanillaforums.com). Licensed under the terms of the [MIT License](LICENSE.md).
