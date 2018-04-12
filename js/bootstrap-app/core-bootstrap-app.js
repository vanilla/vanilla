var core_bootstrapApp=function(e){function t(o){if(n[o])return n[o].exports;var r=n[o]={i:o,l:!1,exports:{}};return e[o].call(r.exports,r,r.exports,t),r.l=!0,r.exports}var n={};return t.m=e,t.c=n,t.d=function(e,n,o){t.o(e,n)||Object.defineProperty(e,n,{configurable:!1,enumerable:!0,get:o})},t.n=function(e){var n=e&&e.__esModule?function(){return e.default}:function(){return e};return t.d(n,"a",n),n},t.o=function(e,t){return Object.prototype.hasOwnProperty.call(e,t)},t.p="/js/",t(t.s=4)}([function(e,t){e.exports=lib_core_app},function(e,t,n){e.exports=n(0)(8)},function(e,t,n){e.exports=n(0)(171)},function(e,t,n){e.exports=n(0)(1)},function(e,t,n){"use strict";function o(e){return e&&e.__esModule?e:{default:e}}var r=n(1),a=n(2),i=n(5),u=o(n(10)),l=o(n(11));n(12),(0,a.debug)((0,r.getMeta)("debug",!1)),u.default.apiv2=l.default,(0,r.onContent)(function(e){(0,i._mountComponents)(e.target)}),(0,a.log)("Bootstrapping"),(0,r._executeReady)().then(function(){(0,a.log)("Bootstrapping complete.");var e=new CustomEvent("X-DOMContentReady",{bubbles:!0,cancelable:!1});document.dispatchEvent(e)}).catch(function(e){(0,a.logError)(e)})},function(e,t,n){"use strict";function o(e){return e&&e.__esModule?e:{default:e}}Object.defineProperty(t,"__esModule",{value:!0}),t._mountComponents=function(e){(0,r.componentExists)("App")||(0,r.addComponent)("App",a.default);var t=e.querySelectorAll("[data-react]");Array.prototype.forEach.call(t,function(e){var t=e.getAttribute("data-react"),n=(0,r.getComponent)(t);n?l.default.render(u.default.createElement(n,null),e):(0,i.logError)("Could not find component %s.",t)})};var r=n(1),a=o(n(6)),i=n(2),u=o(n(3)),l=o(n(9))},function(e,t,n){"use strict";function o(e){return e&&e.__esModule?e:{default:e}}Object.defineProperty(t,"__esModule",{value:!0});var r=Object.assign||function(e){for(var t=1;t<arguments.length;t++){var n=arguments[t];for(var o in n)Object.prototype.hasOwnProperty.call(n,o)&&(e[o]=n[o])}return e},a=function(){function e(e,t){for(var n=0;n<t.length;n++){var o=t[n];o.enumerable=o.enumerable||!1,o.configurable=!0,"value"in o&&(o.writable=!0),Object.defineProperty(e,o.key,o)}}return function(t,n,o){return n&&e(t.prototype,n),o&&e(t,o),t}}(),i=o(n(3)),u=n(1),l=n(7),c=o(n(8)),f=function(e){function t(){return function(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}(this,t),function(e,t){if(!e)throw new ReferenceError("this hasn't been initialised - super() hasn't been called");return!t||"object"!=typeof t&&"function"!=typeof t?e:t}(this,(t.__proto__||Object.getPrototypeOf(t)).apply(this,arguments))}return function(e,t){if("function"!=typeof t&&null!==t)throw new TypeError("Super expression must either be null or a function, not "+typeof t);e.prototype=Object.create(t&&t.prototype,{constructor:{value:e,enumerable:!1,writable:!0,configurable:!0}}),t&&(Object.setPrototypeOf?Object.setPrototypeOf(e,t):e.__proto__=t)}(t,i.default.PureComponent),a(t,[{key:"render",value:function(){var e=(0,u.getRoutes)().map(function(e){return i.default.createElement(e.type,r({key:e.key||e.props.path+(e.props.exact?"!":"")},e.props))});return e.push(i.default.createElement(l.Route,{key:"@not-found",component:c.default})),i.default.createElement(l.BrowserRouter,{basename:(0,u.getMeta)("context.basePath","")},i.default.createElement(l.Switch,null,e))}}]),t}();t.default=f},function(e,t,n){e.exports=n(0)(232)},function(e,t,n){e.exports=n(0)(260)},function(e,t,n){e.exports=n(0)(200)},function(e,t,n){e.exports=n(0)(10)},function(e,t,n){e.exports=n(0)(174)},function(e,t,n){"use strict";var o=n(1),r=n(13);$.fn.atwho&&((0,o.onReady)(function(){return(0,r.initializeAtComplete)(".BodyBox,.js-bodybox")}),(0,o.onContent)(function(){return(0,r.initializeAtComplete)(".BodyBox,.js-bodybox")}),window.gdn.atCompleteInit=r.initializeAtComplete)},function(e,t,n){"use strict";function o(e,t,n){function o(e){var t='[^"\\u0000-\\u001f\\u007f-\\u009f\\u2028';return e&&(t+="\\s"),t+="]"}var r=t.split("\n"),a=r[r.length-1],i='@("('+o(!1)+'+?)"?|('+o(!0)+'+?)"?)(?:\\n|$)';n&&(i="(?:^|\\s)"+i);var u=new RegExp(i,"gi").exec(a);return u?(s=u[0],u[2]||u[1]):null}function r(e,t,n){e=e.replace(/[\-\[\]\/\{\}\(\)\*\+\?\.\\\^\$\|]/g,"\\$&"),n&&(e="(?:^|\\s)"+e);var o=new RegExp(e+"([A-Za-z0-9_+-]*|[^\\x00-\\xff]*)(?:\\n)?$","gi").exec(t);return o?o[2]||o[1]:null}Object.defineProperty(t,"__esModule",{value:!0});var a="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(e){return typeof e}:function(e){return e&&"function"==typeof Symbol&&e.constructor===Symbol&&e!==Symbol.prototype?"symbol":typeof e},i=function(){return function(e,t){if(Array.isArray(e))return e;if(Symbol.iterator in Object(e))return function(e,t){var n=[],o=!0,r=!1,a=void 0;try{for(var i,u=e[Symbol.iterator]();!(o=(i=u.next()).done)&&(n.push(i.value),!t||n.length!==t);o=!0);}catch(e){r=!0,a=e}finally{try{!o&&u.return&&u.return()}finally{if(r)throw a}}return n}(e,t);throw new TypeError("Invalid attempt to destructure non-iterable instance")}}();t.matchAtMention=o,t.matchFakeEmoji=r,t.initializeAtComplete=function(e,t){function n(e,n,o){var r=o.view.$el,a=o.$inputor,i=parseInt(a.css("line-height"),10),u=$(t).offset(),l=(u?u.left:0)+n.left,c=u?u.top:0,f=0,s=o.at,p=o.query.text,d=$(".BodyBox,.js-bodybox"),m=d.css("font-size")+" "+d.css("font-family"),y=(s+p).width(m)-2;"@"===s&&(l-=y),":"===s&&(l-=2),$(r).each(function(e,t){var n=$(t).outerHeight(),o=$(t).height();n&&o&&n>0&&(f+=o+i)});var g=$(window).height()||0,b=$(window).scrollTop()||0,h={left:l,top:c=g-(c+n.top-($(window).scrollTop()||0)-b)>=f?c+n.top+f-b:c+n.top-b};$(r).offset(h)}var i=t?t.contentWindow:"";$(e).atwho({at:"@",tpl:'<li data-value="@${name}" data-id="${id}">${name}</li>',limit:d,callbacks:{remote_filter:function(e,t){if((e=e||"").length>=p){for(var n=function(n){Array.isArray(n)&&n.forEach(function(e){"object"===(void 0===e?"undefined":a(e))&&"string"==typeof e.name&&(e.name=e.name.replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;").replace(/"/g,"&quot;").replace(/'/g,"&apos;"))}),t(n),n.length?c[e]=n:f[e]=e},o=!0,r="",i=0,l=e.length;i<l;i++)if(r=e.slice(0,-i),c[r]&&c[r].length<m){f[e]=e,o=!1;break}var s=!1;for(var d in f)if(f.hasOwnProperty(d)&&null!==e.match(new RegExp("^"+d+"+"))){s=!0;break}!o||s||c[e]?t(o?c[e]:c[r]):$.getJSON((0,u.formatUrl)("/user/tagsearch"),{q:e,limit:m},n)}},before_insert:function(e,t){var n=t.data("value")||"";n=n.slice(1,n.length);var o=/[^\w-]/.test(n),r=/(["'])(.+)(["'])/g.test(n),a=n;return o&&!r&&(a='"'+n+'"'),/.?@(["'])/.test(s||"")||(a=this.at+a),a},highlighter:function(e,t){if(!t)return e;var n=new RegExp(">\\s*(\\w*)("+t.replace("+","\\+")+")(\\w*)\\s*(\\s+.+)?<","ig");return e.replace(n,function(e,t,n,o,r){return void 0===o&&(o=""),void 0===r&&(r=""),"> "+t+"<strong>"+n+"</strong>"+o+r+" <"})},matcher:o},cWindow:i}).atwho({at:":",tpl:w,insert_tpl:"${atwho-data-value}",callbacks:{matcher:r,tplEval:function(e,t){return(0,l.log)(t)}},limit:d,data:v,cWindow:i}),i&&$(i).on("reposition.atwho",n)};var u=n(1),l=n(2),c={},f={},s=void 0,p=(0,u.getMeta)("mentionMinChars",2),d=(0,u.getMeta)("mentionSuggestionCount",5),m=30,y=(0,u.getMeta)("emoji",{}),g=y.emoji||{},b=y.format||"",h=y.assetPath||"",v=Object.entries(g).map(function(e){var t=i(e,2),n=t[0],o=t[1],r=o.split(".");return{name:n,filename:o,basename:r[0],ext:"."+r[1]}}),w='<li data-value=":${name}:" class="at-suggest-emoji"><span class="emoji-wrap">'+b.replace(/{(.+?)}/g,"$${$1}").replace("%1$s","${src}").replace("%2$s","${name}").replace("${src}",h+"/${filename}").replace("${dir}",h)+'</span> <span class="emoji-name">${name}</span></li>'}]);
//# sourceMappingURL=core-bootstrap-app.js.map