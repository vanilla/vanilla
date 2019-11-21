!function(e){var t={};function n(o){if(t[o])return t[o].exports;var i=t[o]={i:o,l:!1,exports:{}};return e[o].call(i.exports,i,i.exports,n),i.l=!0,i.exports}n.m=e,n.c=t,n.d=function(e,t,o){n.o(e,t)||Object.defineProperty(e,t,{enumerable:!0,get:o})},n.r=function(e){"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(e,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(e,"__esModule",{value:!0})},n.t=function(e,t){if(1&t&&(e=n(e)),8&t)return e;if(4&t&&"object"==typeof e&&e&&e.__esModule)return e;var o=Object.create(null);if(n.r(o),Object.defineProperty(o,"default",{enumerable:!0,value:e}),2&t&&"string"!=typeof e)for(var i in e)n.d(o,i,function(t){return e[t]}.bind(null,i));return o},n.n=function(e){var t=e&&e.__esModule?function(){return e.default}:function(){return e};return n.d(t,"a",t),t},n.o=function(e,t){return Object.prototype.hasOwnProperty.call(e,t)},n.p="",n(n.s=0)}([function(e,t,n){"use strict";n.r(t);
/*!
 * @author Isis (igraziatto) Graziatto <isis.g@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
var o="needsInitialization",i="data-height",r="0px";function u(e){e.style.height=r}function c(e){if(e&&e.classList){e.classList.add(o),e.style.height="auto";var t=e.getBoundingClientRect().height;e.setAttribute(i,t.toString()),u(e),e.classList.remove(o)}}
/*!
 * @author Isis (igraziatto) Graziatto <isis.g@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
$(function(){(function(){var e=document.querySelector("#menu-button"),t=document.querySelector(".js-nav"),n=document.querySelector(".js-mobileMebox"),o=document.querySelector(".mobileMeBox-button"),l=document.querySelector(".mobileMebox-buttonClose"),s=document.querySelector("#MainHeader");function a(e){e.style.height===r?function(e){e.style.height=e.getAttribute(i)+"px"}(e):u(e)}c(n),c(t),window.addEventListener("resize",function(){requestAnimationFrame(function(){c(n),c(t)})}),e&&e.addEventListener("click",function(){e.classList.toggle("isToggled"),s.classList.toggle("hasOpenNavigation"),u(n),a(t)}),o&&o.addEventListener("click",function(){o.classList.toggle("isToggled"),s.classList.remove("hasOpenNavigation"),e.classList.remove("isToggled"),u(t),a(n)}),l&&l.addEventListener("click",function(){u(n)})})(),$("select").wrap('<div class="SelectWrapper"></div>')})}]);
//# sourceMappingURL=custom.js.map