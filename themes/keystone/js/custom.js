!function(e){var t={};function n(o){if(t[o])return t[o].exports;var r=t[o]={i:o,l:!1,exports:{}};return e[o].call(r.exports,r,r.exports,n),r.l=!0,r.exports}n.m=e,n.c=t,n.d=function(e,t,o){n.o(e,t)||Object.defineProperty(e,t,{enumerable:!0,get:o})},n.r=function(e){"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(e,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(e,"__esModule",{value:!0})},n.t=function(e,t){if(1&t&&(e=n(e)),8&t)return e;if(4&t&&"object"==typeof e&&e&&e.__esModule)return e;var o=Object.create(null);if(n.r(o),Object.defineProperty(o,"default",{enumerable:!0,value:e}),2&t&&"string"!=typeof e)for(var r in e)n.d(o,r,function(t){return e[t]}.bind(null,r));return o},n.n=function(e){var t=e&&e.__esModule?function(){return e.default}:function(){return e};return n.d(t,"a",t),t},n.o=function(e,t){return Object.prototype.hasOwnProperty.call(e,t)},n.p="",n(n.s=0)}([function(e,t,n){"use strict";n(1)},function(e,t,n){"use strict";n.r(t);
/*!
 * @author Isis (igraziatto) Graziatto <isis.g@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
const o="needsInitialization",r="data-height",i="0px";
/*!
 * @author Isis (igraziatto) Graziatto <isis.g@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
$(()=>{(function(){const e=document.querySelector("#menu-button"),t=document.querySelector(".js-nav"),n=document.querySelector(".js-mobileMebox"),u=document.querySelector(".mobileMeBox-button"),c=document.querySelector(".mobileMebox-buttonClose"),s=document.querySelector("#MainHeader");function l(e){e.style.height===i?function(e){e.style.height=e.getAttribute(r)+"px"}(e):a(e)}function a(e){e.style.height=i}function d(e){e.classList.add(o),e.style.height="auto";const t=e.getBoundingClientRect().height;e.setAttribute(r,t.toString()),a(e),e.classList.remove(o)}d(n),d(t),window.addEventListener("resize",()=>{requestAnimationFrame(()=>{d(n),d(t)})}),e.addEventListener("click",()=>{e.classList.toggle("isToggled"),s.classList.toggle("hasOpenNavigation"),a(n),l(t)}),u&&u.addEventListener("click",()=>{u.classList.toggle("isToggled"),s.classList.remove("hasOpenNavigation"),e.classList.remove("isToggled"),a(t),l(n)}),c&&c.addEventListener("click",()=>{a(n)})})(),$("select").wrap('<div class="SelectWrapper"></div>')})}]);
//# sourceMappingURL=custom.js.map