!function(e){var t={};function o(n){if(t[n])return t[n].exports;var r=t[n]={i:n,l:!1,exports:{}};return e[n].call(r.exports,r,r.exports,o),r.l=!0,r.exports}o.m=e,o.c=t,o.d=function(e,t,n){o.o(e,t)||Object.defineProperty(e,t,{enumerable:!0,get:n})},o.r=function(e){"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(e,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(e,"__esModule",{value:!0})},o.t=function(e,t){if(1&t&&(e=o(e)),8&t)return e;if(4&t&&"object"==typeof e&&e&&e.__esModule)return e;var n=Object.create(null);if(o.r(n),Object.defineProperty(n,"default",{enumerable:!0,value:e}),2&t&&"string"!=typeof e)for(var r in e)o.d(n,r,function(t){return e[t]}.bind(null,r));return n},o.n=function(e){var t=e&&e.__esModule?function(){return e.default}:function(){return e};return o.d(t,"a",t),t},o.o=function(e,t){return Object.prototype.hasOwnProperty.call(e,t)},o.p="",o(o.s=0)}([function(e,t,o){"use strict";o(1)},function(e,t,o){"use strict";function n(){$(document.body).addClass("NoScroll")}function r(){$(document.body).removeClass("NoScroll")}
/*!
 * @author Isis (igraziatto) Graziatto <isis.g@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
/*!
 * @author Isis (igraziatto) Graziatto <isis.g@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
o.r(t),window.requestAnimationFrame||(window.requestAnimationFrame=window.webkitRequestAnimationFrame||window.mozRequestAnimationFrame||window.oRequestAnimationFrame||window.msRequestAnimationFrame||function(e,t){window.setTimeout(e,1e3/60)}),$(()=>{
/*!
 * @author Isis (igraziatto) Graziatto <isis.g@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
!function(){var e=$("#menu-button"),t=$("#navdrawer");e.on("click",()=>{e.toggleClass("isToggled"),t.toggleClass("isOpen")})}
/*!
 * @author Isis (igraziatto) Graziatto <isis.g@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */(),function(){$(document).undelegate(".ToggleFlyout","click");var e=null;$(document).delegate(".ToggleFlyout","click",function(t){var o=$(".Flyout",this),s=!1;if(0===$(t.target).closest(".Flyout").length)t.stopPropagation(),s=!0;else if($(t.target).hasClass("Hijack")||$(t.target).closest("a").hasClass("Hijack"))return;t.stopPropagation();var l=$(this).attr("rel");if(l&&($(this).attr("rel",""),o.html('<div class="InProgress" style="height: 30px"></div>'),$.ajax({url:gdn.url(l),data:{DeliveryType:"VIEW"},success:function(e){o.html(e)},error:function(e){o.html(""),gdn.informError(e,!0)}})),"hidden"==o.css("visibility")?(null!==e&&($(".Flyout",e).hide(),$(e).removeClass("Open").closest(".Item").removeClass("Open")),$(this).addClass("Open").closest(".Item").addClass("Open"),o.show(),n(),e=this):(o.hide(),$(this).removeClass("Open").closest(".Item").removeClass("Open"),r()),s)return!1}),$(document).delegate(".ToggleFlyout a","mouseup",function(){$(this).hasClass("FlyoutButton")||($(".ToggleFlyout").removeClass("Open").closest(".Item").removeClass("Open"),$(".Flyout").hide())}),$(document).on("click touchstart",function(){e&&($(".Flyout",e).hide(),$(e).removeClass("Open").closest(".Item").removeClass("Open")),$(".ButtonGroup").removeClass("Open"),r()}),$(".Button.Primary.Handle").on("click",e=>{$(document.body)[0].style.overflow?r():n()}),$(".Options .Flyout").on("click",()=>{r()})}(),$("select").wrap('<div class="SelectWrapper"></div>')})}]);
//# sourceMappingURL=custom.js.map