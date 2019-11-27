/******/ (function(modules) { // webpackBootstrap
/******/ 	// The module cache
/******/ 	var installedModules = {};
/******/
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/
/******/ 		// Check if module is in cache
/******/ 		if(installedModules[moduleId]) {
/******/ 			return installedModules[moduleId].exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = installedModules[moduleId] = {
/******/ 			i: moduleId,
/******/ 			l: false,
/******/ 			exports: {}
/******/ 		};
/******/
/******/ 		// Execute the module function
/******/ 		modules[moduleId].call(module.exports, module, module.exports, __webpack_require__);
/******/
/******/ 		// Flag the module as loaded
/******/ 		module.l = true;
/******/
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/
/******/
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = modules;
/******/
/******/ 	// expose the module cache
/******/ 	__webpack_require__.c = installedModules;
/******/
/******/ 	// define getter function for harmony exports
/******/ 	__webpack_require__.d = function(exports, name, getter) {
/******/ 		if(!__webpack_require__.o(exports, name)) {
/******/ 			Object.defineProperty(exports, name, { enumerable: true, get: getter });
/******/ 		}
/******/ 	};
/******/
/******/ 	// define __esModule on exports
/******/ 	__webpack_require__.r = function(exports) {
/******/ 		if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 			Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 		}
/******/ 		Object.defineProperty(exports, '__esModule', { value: true });
/******/ 	};
/******/
/******/ 	// create a fake namespace object
/******/ 	// mode & 1: value is a module id, require it
/******/ 	// mode & 2: merge all properties of value into the ns
/******/ 	// mode & 4: return value when already ns object
/******/ 	// mode & 8|1: behave like require
/******/ 	__webpack_require__.t = function(value, mode) {
/******/ 		if(mode & 1) value = __webpack_require__(value);
/******/ 		if(mode & 8) return value;
/******/ 		if((mode & 4) && typeof value === 'object' && value && value.__esModule) return value;
/******/ 		var ns = Object.create(null);
/******/ 		__webpack_require__.r(ns);
/******/ 		Object.defineProperty(ns, 'default', { enumerable: true, value: value });
/******/ 		if(mode & 2 && typeof value != 'string') for(var key in value) __webpack_require__.d(ns, key, function(key) { return value[key]; }.bind(null, key));
/******/ 		return ns;
/******/ 	};
/******/
/******/ 	// getDefaultExport function for compatibility with non-harmony modules
/******/ 	__webpack_require__.n = function(module) {
/******/ 		var getter = module && module.__esModule ?
/******/ 			function getDefault() { return module['default']; } :
/******/ 			function getModuleExports() { return module; };
/******/ 		__webpack_require__.d(getter, 'a', getter);
/******/ 		return getter;
/******/ 	};
/******/
/******/ 	// Object.prototype.hasOwnProperty.call
/******/ 	__webpack_require__.o = function(object, property) { return Object.prototype.hasOwnProperty.call(object, property); };
/******/
/******/ 	// __webpack_public_path__
/******/ 	__webpack_require__.p = "";
/******/
/******/
/******/ 	// Load entry module and return exports
/******/ 	return __webpack_require__(__webpack_require__.s = "./src/js/index.js");
/******/ })
/************************************************************************/
/******/ ({

/***/ "./src/js/index.js":
/*!*************************!*\
  !*** ./src/js/index.js ***!
  \*************************/
/*! no exports provided */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony import */ var _mobileNavigation__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./mobileNavigation */ \"./src/js/mobileNavigation.js\");\n/*!\n * @author Isis (igraziatto) Graziatto <isis.g@vanillaforums.com>\n * @copyright 2009-2018 Vanilla Forums Inc.\n * @license GPL-2.0-only\n */\n\n\n\n$(function () {\n  if (!window.gdn.getMeta(\"featureFlags.DataDrivenTitleBar.Enabled\", false)) {\n    Object(_mobileNavigation__WEBPACK_IMPORTED_MODULE_0__[\"setupMobileNavigation\"])();\n  }\n\n  $(\"select\").wrap('<div class=\"SelectWrapper\"></div>');\n});//# sourceURL=[module]\n//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJzb3VyY2VzIjpbIndlYnBhY2s6Ly8vLi9zcmMvanMvaW5kZXguanM/N2JhNSJdLCJuYW1lcyI6WyIkIiwid2luZG93IiwiZ2RuIiwiZ2V0TWV0YSIsInNldHVwTW9iaWxlTmF2aWdhdGlvbiIsIndyYXAiXSwibWFwcGluZ3MiOiI7QUFBQTtBQUFBOzs7OztBQUtBOztBQUVBO0FBRUFBLENBQUMsQ0FBQyxZQUFNO0FBQ0osTUFBSSxDQUFDQyxNQUFNLENBQUNDLEdBQVAsQ0FBV0MsT0FBWCxDQUFtQix5Q0FBbkIsRUFBOEQsS0FBOUQsQ0FBTCxFQUEyRTtBQUN2RUMsSUFBQSwrRUFBcUI7QUFDeEI7O0FBQ0RKLEdBQUMsQ0FBQyxRQUFELENBQUQsQ0FBWUssSUFBWixDQUFpQixtQ0FBakI7QUFDSCxDQUxBLENBQUQiLCJmaWxlIjoiLi9zcmMvanMvaW5kZXguanMuanMiLCJzb3VyY2VzQ29udGVudCI6WyIvKiFcbiAqIEBhdXRob3IgSXNpcyAoaWdyYXppYXR0bykgR3JhemlhdHRvIDxpc2lzLmdAdmFuaWxsYWZvcnVtcy5jb20+XG4gKiBAY29weXJpZ2h0IDIwMDktMjAxOCBWYW5pbGxhIEZvcnVtcyBJbmMuXG4gKiBAbGljZW5zZSBHUEwtMi4wLW9ubHlcbiAqL1xuXCJ1c2Ugc3RyaWN0XCJcblxuaW1wb3J0IHsgc2V0dXBNb2JpbGVOYXZpZ2F0aW9uIH0gZnJvbSBcIi4vbW9iaWxlTmF2aWdhdGlvblwiO1xuXG4kKCgpID0+IHtcbiAgICBpZiAoIXdpbmRvdy5nZG4uZ2V0TWV0YShcImZlYXR1cmVGbGFncy5EYXRhRHJpdmVuVGl0bGVCYXIuRW5hYmxlZFwiLCBmYWxzZSkpIHtcbiAgICAgICAgc2V0dXBNb2JpbGVOYXZpZ2F0aW9uKCk7XG4gICAgfVxuICAgICQoXCJzZWxlY3RcIikud3JhcCgnPGRpdiBjbGFzcz1cIlNlbGVjdFdyYXBwZXJcIj48L2Rpdj4nKTtcbn0pO1xuIl0sInNvdXJjZVJvb3QiOiIifQ==\n//# sourceURL=webpack-internal:///./src/js/index.js\n");

/***/ }),

/***/ "./src/js/mobileNavigation.js":
/*!************************************!*\
  !*** ./src/js/mobileNavigation.js ***!
  \************************************/
/*! exports provided: setupMobileNavigation */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, \"setupMobileNavigation\", function() { return setupMobileNavigation; });\n/*!\n * @author Isis (igraziatto) Graziatto <isis.g@vanillaforums.com>\n * @copyright 2009-2018 Vanilla Forums Inc.\n * @license GPL-2.0-only\n */\n\n\nvar INIT_CLASS = \"needsInitialization\";\nvar CALC_HEIGHT_ATTR = \"data-height\";\nvar COLLAPSED_HEIGHT = \"0px\";\n/**\n * @param {HTMLElement} element\n */\n\nfunction collapseElement(element) {\n  element.style.height = COLLAPSED_HEIGHT;\n}\n/**\n *\n * @param {HTMLElement} element\n */\n\n\nfunction expandElement(element) {\n  element.style.height = element.getAttribute(CALC_HEIGHT_ATTR) + \"px\";\n}\n/**\n * Get the calculated height of an element and\n *\n * @param {HTMLElement} element\n */\n\n\nfunction prepareElement(element) {\n  if (!!element && element.classList) {\n    element.classList.add(INIT_CLASS);\n    element.style.height = \"auto\";\n    var calcedHeight = element.getBoundingClientRect().height; // Visual hide the element.`\n\n    element.setAttribute(CALC_HEIGHT_ATTR, calcedHeight.toString());\n    collapseElement(element);\n    element.classList.remove(INIT_CLASS);\n  }\n}\n\nfunction setupMobileNavigation() {\n  var menuButton = document.querySelector(\"#menu-button\");\n  /** @type {HTMLElement} */\n\n  var navdrawer = document.querySelector(\".js-nav\");\n  /** @type {HTMLElement} */\n\n  var mobileMebox = document.querySelector(\".js-mobileMebox\");\n  var mobileMeBoxBtn = document.querySelector(\".mobileMeBox-button\");\n  var mobileMeboxBtnClose = document.querySelector(\".mobileMebox-buttonClose\");\n  var mainHeader = document.querySelector(\"#MainHeader\");\n  /**\n   * @param {HTMLElement} element\n   */\n\n  function toggleElement(element) {\n    if (element.style.height === COLLAPSED_HEIGHT) {\n      expandElement(element);\n    } else {\n      collapseElement(element);\n    }\n  } // Calculate the values initially.\n\n\n  prepareElement(mobileMebox);\n  prepareElement(navdrawer); // Update the calculated values on resize.\n\n  window.addEventListener(\"resize\", function () {\n    requestAnimationFrame(function () {\n      prepareElement(mobileMebox);\n      prepareElement(navdrawer);\n    });\n  });\n\n  if (menuButton) {\n    menuButton.addEventListener(\"click\", function () {\n      menuButton.classList.toggle(\"isToggled\");\n      mainHeader.classList.toggle(\"hasOpenNavigation\");\n      collapseElement(mobileMebox);\n      toggleElement(navdrawer);\n    });\n  }\n\n  mobileMeBoxBtn && mobileMeBoxBtn.addEventListener(\"click\", function () {\n    mobileMeBoxBtn.classList.toggle(\"isToggled\");\n    mainHeader.classList.remove(\"hasOpenNavigation\");\n    menuButton.classList.remove(\"isToggled\");\n    collapseElement(navdrawer);\n    toggleElement(mobileMebox);\n  });\n  mobileMeboxBtnClose && mobileMeboxBtnClose.addEventListener(\"click\", function () {\n    collapseElement(mobileMebox);\n  });\n}//# sourceURL=[module]\n//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJzb3VyY2VzIjpbIndlYnBhY2s6Ly8vLi9zcmMvanMvbW9iaWxlTmF2aWdhdGlvbi5qcz9mN2JlIl0sIm5hbWVzIjpbIklOSVRfQ0xBU1MiLCJDQUxDX0hFSUdIVF9BVFRSIiwiQ09MTEFQU0VEX0hFSUdIVCIsImNvbGxhcHNlRWxlbWVudCIsImVsZW1lbnQiLCJzdHlsZSIsImhlaWdodCIsImV4cGFuZEVsZW1lbnQiLCJnZXRBdHRyaWJ1dGUiLCJwcmVwYXJlRWxlbWVudCIsImNsYXNzTGlzdCIsImFkZCIsImNhbGNlZEhlaWdodCIsImdldEJvdW5kaW5nQ2xpZW50UmVjdCIsInNldEF0dHJpYnV0ZSIsInRvU3RyaW5nIiwicmVtb3ZlIiwic2V0dXBNb2JpbGVOYXZpZ2F0aW9uIiwibWVudUJ1dHRvbiIsImRvY3VtZW50IiwicXVlcnlTZWxlY3RvciIsIm5hdmRyYXdlciIsIm1vYmlsZU1lYm94IiwibW9iaWxlTWVCb3hCdG4iLCJtb2JpbGVNZWJveEJ0bkNsb3NlIiwibWFpbkhlYWRlciIsInRvZ2dsZUVsZW1lbnQiLCJ3aW5kb3ciLCJhZGRFdmVudExpc3RlbmVyIiwicmVxdWVzdEFuaW1hdGlvbkZyYW1lIiwidG9nZ2xlIl0sIm1hcHBpbmdzIjoiO0FBQUE7QUFBQTs7Ozs7QUFNQTs7QUFDQSxJQUFNQSxVQUFVLEdBQUcscUJBQW5CO0FBQ0EsSUFBTUMsZ0JBQWdCLEdBQUcsYUFBekI7QUFDQSxJQUFNQyxnQkFBZ0IsR0FBRyxLQUF6QjtBQUNBOzs7O0FBR0EsU0FBU0MsZUFBVCxDQUF5QkMsT0FBekIsRUFBa0M7QUFDOUJBLFNBQU8sQ0FBQ0MsS0FBUixDQUFjQyxNQUFkLEdBQXVCSixnQkFBdkI7QUFDSDtBQUVEOzs7Ozs7QUFJQSxTQUFTSyxhQUFULENBQXVCSCxPQUF2QixFQUFnQztBQUM1QkEsU0FBTyxDQUFDQyxLQUFSLENBQWNDLE1BQWQsR0FBdUJGLE9BQU8sQ0FBQ0ksWUFBUixDQUFxQlAsZ0JBQXJCLElBQXlDLElBQWhFO0FBQ0g7QUFFRDs7Ozs7OztBQUtB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Q0FBQ21CLFdBQUQsQ0FBZjtBQUNILEdBRnNCLENBQXZCO0FBR0giLCJmaWxlIjoiLi9zcmMvanMvbW9iaWxlTmF2aWdhdGlvbi5qcy5qcyIsInNvdXJjZXNDb250ZW50IjpbIi8qIVxuICogQGF1dGhvciBJc2lzIChpZ3JhemlhdHRvKSBHcmF6aWF0dG8gPGlzaXMuZ0B2YW5pbGxhZm9ydW1zLmNvbT5cbiAqIEBjb3B5cmlnaHQgMjAwOS0yMDE4IFZhbmlsbGEgRm9ydW1zIEluYy5cbiAqIEBsaWNlbnNlIEdQTC0yLjAtb25seVxuICovXG5cblwidXNlIHN0cmljdFwiXG5jb25zdCBJTklUX0NMQVNTID0gXCJuZWVkc0luaXRpYWxpemF0aW9uXCI7XG5jb25zdCBDQUxDX0hFSUdIVF9BVFRSID0gXCJkYXRhLWhlaWdodFwiO1xuY29uc3QgQ09MTEFQU0VEX0hFSUdIVCA9IFwiMHB4XCI7XG4vKipcbiAqIEBwYXJhbSB7SFRNTEVsZW1lbnR9IGVsZW1lbnRcbiAqL1xuZnVuY3Rpb24gY29sbGFwc2VFbGVtZW50KGVsZW1lbnQpIHtcbiAgICBlbGVtZW50LnN0eWxlLmhlaWdodCA9IENPTExBUFNFRF9IRUlHSFQ7XG59XG5cbi8qKlxuICpcbiAqIEBwYXJhbSB7SFRNTEVsZW1lbnR9IGVsZW1lbnRcbiAqL1xuZnVuY3Rpb24gZXhwYW5kRWxlbWVudChlbGVtZW50KSB7XG4gICAgZWxlbWVudC5zdHlsZS5oZWlnaHQgPSBlbGVtZW50LmdldEF0dHJpYnV0ZShDQUxDX0hFSUdIVF9BVFRSKSArIFwicHhcIjtcbn1cblxuLyoqXG4gKiBHZXQgdGhlIGNhbGN1bGF0ZWQgaGVpZ2h0IG9mIGFuIGVsZW1lbnQgYW5kXG4gKlxuICogQHBhcmFtIHtIVE1MRWxlbWVudH0gZWxlbWVudFxuICovXG5mdW5jdGlvbiBwcmVwYXJlRWxlbWVudChlbGVtZW50KSB7XG4gICAgaWYgKCEhZWxlbWVudCAmJiBlbGVtZW50LmNsYXNzTGlzdCkge1xuICAgICAgICBlbGVtZW50LmNsYXNzTGlzdC5hZGQoSU5JVF9DTEFTUyk7XG4gICAgICAgIGVsZW1lbnQuc3R5bGUuaGVpZ2h0ID0gXCJhdXRvXCI7XG4gICAgICAgIGNvbnN0IGNhbGNlZEhlaWdodCA9IGVsZW1lbnQuZ2V0Qm91bmRpbmdDbGllbnRSZWN0KCkuaGVpZ2h0O1xuXG4gICAgICAgIC8vIFZpc3VhbCBoaWRlIHRoZSBlbGVtZW50LmBcbiAgICAgICAgZWxlbWVudC5zZXRBdHRyaWJ1dGUoQ0FMQ19IRUlHSFRfQVRUUiwgY2FsY2VkSGVpZ2h0LnRvU3RyaW5nKCkpO1xuICAgICAgICBjb2xsYXBzZUVsZW1lbnQoZWxlbWVudCk7XG4gICAgICAgIGVsZW1lbnQuY2xhc3NMaXN0LnJlbW92ZShJTklUX0NMQVNTKTtcbiAgICB9XG59XG5cbmV4cG9ydCBmdW5jdGlvbiBzZXR1cE1vYmlsZU5hdmlnYXRpb24oKSB7XG4gICAgY29uc3QgbWVudUJ1dHRvbiA9IGRvY3VtZW50LnF1ZXJ5U2VsZWN0b3IoXCIjbWVudS1idXR0b25cIik7XG4gICAgLyoqIEB0eXBlIHtIVE1MRWxlbWVudH0gKi9cbiAgICBjb25zdCBuYXZkcmF3ZXIgPSBkb2N1bWVudC5xdWVyeVNlbGVjdG9yKFwiLmpzLW5hdlwiKTtcbiAgICAvKiogQHR5cGUge0hUTUxFbGVtZW50fSAqL1xuICAgIGNvbnN0IG1vYmlsZU1lYm94ID0gZG9jdW1lbnQucXVlcnlTZWxlY3RvcihcIi5qcy1tb2JpbGVNZWJveFwiKTtcbiAgICBjb25zdCBtb2JpbGVNZUJveEJ0biA9IGRvY3VtZW50LnF1ZXJ5U2VsZWN0b3IoXCIubW9iaWxlTWVCb3gtYnV0dG9uXCIpO1xuICAgIGNvbnN0IG1vYmlsZU1lYm94QnRuQ2xvc2UgPSBkb2N1bWVudC5xdWVyeVNlbGVjdG9yKFwiLm1vYmlsZU1lYm94LWJ1dHRvbkNsb3NlXCIpO1xuICAgIGNvbnN0IG1haW5IZWFkZXIgPSBkb2N1bWVudC5xdWVyeVNlbGVjdG9yKFwiI01haW5IZWFkZXJcIik7XG5cbiAgICAvKipcbiAgICAgKiBAcGFyYW0ge0hUTUxFbGVtZW50fSBlbGVtZW50XG4gICAgICovXG4gICAgZnVuY3Rpb24gdG9nZ2xlRWxlbWVudChlbGVtZW50KSB7XG4gICAgICAgIGlmIChlbGVtZW50LnN0eWxlLmhlaWdodCA9PT0gQ09MTEFQU0VEX0hFSUdIVCkge1xuICAgICAgICAgICAgZXhwYW5kRWxlbWVudChlbGVtZW50KTtcbiAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICAgIGNvbGxhcHNlRWxlbWVudChlbGVtZW50KTtcbiAgICAgICAgfVxuICAgIH1cblxuICAgIC8vIENhbGN1bGF0ZSB0aGUgdmFsdWVzIGluaXRpYWxseS5cbiAgICBwcmVwYXJlRWxlbWVudChtb2JpbGVNZWJveCk7XG4gICAgcHJlcGFyZUVsZW1lbnQobmF2ZHJhd2VyKTtcblxuICAgIC8vIFVwZGF0ZSB0aGUgY2FsY3VsYXRlZCB2YWx1ZXMgb24gcmVzaXplLlxuICAgIHdpbmRvdy5hZGRFdmVudExpc3RlbmVyKFwicmVzaXplXCIsICgpID0+IHtcbiAgICAgICAgcmVxdWVzdEFuaW1hdGlvbkZyYW1lKCgpID0+IHtcbiAgICAgICAgICAgIHByZXBhcmVFbGVtZW50KG1vYmlsZU1lYm94KTtcbiAgICAgICAgICAgIHByZXBhcmVFbGVtZW50KG5hdmRyYXdlcik7XG4gICAgICAgIH0pO1xuICAgIH0pXG5cbiAgICBpZiAobWVudUJ1dHRvbikge1xuICAgICAgICBtZW51QnV0dG9uLmFkZEV2ZW50TGlzdGVuZXIoXCJjbGlja1wiLCAoKSA9PiB7XG4gICAgICAgICAgICBtZW51QnV0dG9uLmNsYXNzTGlzdC50b2dnbGUoXCJpc1RvZ2dsZWRcIik7XG4gICAgICAgICAgICBtYWluSGVhZGVyLmNsYXNzTGlzdC50b2dnbGUoXCJoYXNPcGVuTmF2aWdhdGlvblwiKTtcbiAgICAgICAgICAgIGNvbGxhcHNlRWxlbWVudChtb2JpbGVNZWJveCk7XG4gICAgICAgICAgICB0b2dnbGVFbGVtZW50KG5hdmRyYXdlcik7XG4gICAgICAgIH0pO1xuICAgIH1cblxuICAgIG1vYmlsZU1lQm94QnRuICYmIG1vYmlsZU1lQm94QnRuLmFkZEV2ZW50TGlzdGVuZXIoXCJjbGlja1wiLCAoKSA9PiB7XG4gICAgICAgIG1vYmlsZU1lQm94QnRuLmNsYXNzTGlzdC50b2dnbGUoXCJpc1RvZ2dsZWRcIik7XG4gICAgICAgIG1haW5IZWFkZXIuY2xhc3NMaXN0LnJlbW92ZShcImhhc09wZW5OYXZpZ2F0aW9uXCIpO1xuICAgICAgICBtZW51QnV0dG9uLmNsYXNzTGlzdC5yZW1vdmUoXCJpc1RvZ2dsZWRcIik7XG4gICAgICAgIGNvbGxhcHNlRWxlbWVudChuYXZkcmF3ZXIpO1xuICAgICAgICB0b2dnbGVFbGVtZW50KG1vYmlsZU1lYm94KTtcbiAgICB9KTtcblxuICAgIG1vYmlsZU1lYm94QnRuQ2xvc2UgJiYgbW9iaWxlTWVib3hCdG5DbG9zZS5hZGRFdmVudExpc3RlbmVyKFwiY2xpY2tcIiwgKCkgPT4ge1xuICAgICAgICBjb2xsYXBzZUVsZW1lbnQobW9iaWxlTWVib3gpO1xuICAgIH0pO1xufVxuIl0sInNvdXJjZVJvb3QiOiIifQ==\n//# sourceURL=webpack-internal:///./src/js/mobileNavigation.js\n");

/***/ })

/******/ });