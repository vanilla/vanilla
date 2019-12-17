!function(e){var t={};function n(o){if(t[o])return t[o].exports;var i=t[o]={i:o,l:!1,exports:{}};return e[o].call(i.exports,i,i.exports,n),i.l=!0,i.exports}n.m=e,n.c=t,n.d=function(e,t,o){n.o(e,t)||Object.defineProperty(e,t,{enumerable:!0,get:o})},n.r=function(e){"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(e,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(e,"__esModule",{value:!0})},n.t=function(e,t){if(1&t&&(e=n(e)),8&t)return e;if(4&t&&"object"==typeof e&&e&&e.__esModule)return e;var o=Object.create(null);if(n.r(o),Object.defineProperty(o,"default",{enumerable:!0,value:e}),2&t&&"string"!=typeof e)for(var i in e)n.d(o,i,function(t){return e[t]}.bind(null,i));return o},n.n=function(e){var t=e&&e.__esModule?function(){return e.default}:function(){return e};return n.d(t,"a",t),t},n.o=function(e,t){return Object.prototype.hasOwnProperty.call(e,t)},n.p="",n(n.s=0)}([function(e,t,n){"use strict";n(1)},function(e,t,n){"use strict";n.r(t);
/*!
 * @author Isis (igraziatto) Graziatto <isis.g@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
const o="needsInitialization",i="data-height",r="0px";function s(e){e.style.height=r}function u(e){if(e&&e.classList){e.classList.add(o),e.style.height="auto";const t=e.getBoundingClientRect().height;e.setAttribute(i,t.toString()),s(e),e.classList.remove(o)}}
/*!
 * @author Isis (igraziatto) Graziatto <isis.g@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
$(()=>{window.gdn.getMeta("featureFlags.DataDrivenTitleBar.Enabled",!1)||function(){const e=document.querySelector("#menu-button"),t=document.querySelector(".js-nav"),n=document.querySelector(".js-mobileMebox"),o=document.querySelector(".mobileMeBox-button"),c=document.querySelector(".mobileMebox-buttonClose"),l=document.querySelector("#MainHeader");function a(e){e.style.height===r?function(e){e.style.height=e.getAttribute(i)+"px"}(e):s(e)}u(n),u(t),window.addEventListener("resize",()=>{requestAnimationFrame(()=>{u(n),u(t)})}),e&&e.addEventListener("click",()=>{e.classList.toggle("isToggled"),l.classList.toggle("hasOpenNavigation"),s(n),a(t)}),o&&o.addEventListener("click",()=>{o.classList.toggle("isToggled"),l.classList.remove("hasOpenNavigation"),e.classList.remove("isToggled"),s(t),a(n)}),c&&c.addEventListener("click",()=>{s(n)})}(),$("select").wrap('<div class="SelectWrapper"></div>')})}]);
//# sourceMappingURL=custom.js.map