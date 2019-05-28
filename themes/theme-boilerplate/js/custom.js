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
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";
eval("\n\nvar _mobileNavigation = __webpack_require__(/*! ./mobileNavigation */ \"./src/js/mobileNavigation.js\");\n\n$(function () {\n  (0, _mobileNavigation.setupMobileNavigation)();\n\n  $(\"select\").wrap('<div class=\"SelectWrapper\"></div>');\n}); /*!\n     * @author Isis (igraziatto) Graziatto <isis.g@vanillaforums.com>\n     * @copyright 2009-2018 Vanilla Forums Inc.\n     * @license GPL-2.0-only\n     *///# sourceURL=[module]\n//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJzb3VyY2VzIjpbIndlYnBhY2s6Ly8vLi9zcmMvanMvaW5kZXguanM/N2JhNSJdLCJuYW1lcyI6WyIkIiwid3JhcCJdLCJtYXBwaW5ncyI6Ijs7QUFNQTs7QUFFQUEsRUFBRSxZQUFNO0FBQ0o7O0FBRUFBLElBQUUsUUFBRixFQUFZQyxJQUFaLENBQWlCLG1DQUFqQjtBQUNILENBSkQsRSxDQVJBIiwiZmlsZSI6Ii4vc3JjL2pzL2luZGV4LmpzLmpzIiwic291cmNlc0NvbnRlbnQiOlsiLyohXG4gKiBAYXV0aG9yIElzaXMgKGlncmF6aWF0dG8pIEdyYXppYXR0byA8aXNpcy5nQHZhbmlsbGFmb3J1bXMuY29tPlxuICogQGNvcHlyaWdodCAyMDA5LTIwMTggVmFuaWxsYSBGb3J1bXMgSW5jLlxuICogQGxpY2Vuc2UgR1BMLTIuMC1vbmx5XG4gKi9cblxuaW1wb3J0IHsgc2V0dXBNb2JpbGVOYXZpZ2F0aW9uIH0gZnJvbSBcIi4vbW9iaWxlTmF2aWdhdGlvblwiO1xuXG4kKCgpID0+IHtcbiAgICBzZXR1cE1vYmlsZU5hdmlnYXRpb24oKTtcblxuICAgICQoXCJzZWxlY3RcIikud3JhcCgnPGRpdiBjbGFzcz1cIlNlbGVjdFdyYXBwZXJcIj48L2Rpdj4nKTtcbn0pO1xuIl0sInNvdXJjZVJvb3QiOiIifQ==\n//# sourceURL=webpack-internal:///./src/js/index.js\n");

/***/ }),

/***/ "./src/js/mobileNavigation.js":
/*!************************************!*\
  !*** ./src/js/mobileNavigation.js ***!
  \************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";
eval("\n\nObject.defineProperty(exports, \"__esModule\", {\n    value: true\n});\nexports.setupMobileNavigation = setupMobileNavigation;\n/*!\n * @author Isis (igraziatto) Graziatto <isis.g@vanillaforums.com>\n * @copyright 2009-2018 Vanilla Forums Inc.\n * @license GPL-2.0-only\n */\n\nvar INIT_CLASS = \"needsInitialization\";\nvar CALC_HEIGHT_ATTR = \"data-height\";\nvar COLLAPSED_HEIGHT = \"0px\";\n\nfunction setupMobileNavigation() {\n\n    var menuButton = document.querySelector(\"#menu-button\");\n    /** @type {HTMLElement} */\n    var navdrawer = document.querySelector(\".js-nav\");\n    /** @type {HTMLElement} */\n    var mobileMebox = document.querySelector(\".js-mobileMebox\");\n    var mobileMeBoxBtn = document.querySelector(\".mobileMeBox-button\");\n    var mobileMeboxBtnClose = document.querySelector(\".mobileMebox-buttonClose\");\n    var mainHeader = document.querySelector(\"#MainHeader\");\n\n    // Calculate the values initially.\n    prepareElement(mobileMebox);\n    prepareElement(navdrawer);\n\n    // Update the calculated values on resize.\n    window.addEventListener(\"resize\", function () {\n        requestAnimationFrame(function () {\n            prepareElement(mobileMebox);\n            prepareElement(navdrawer);\n        });\n    });\n\n    menuButton.addEventListener(\"click\", function () {\n        menuButton.classList.toggle(\"isToggled\");\n        mainHeader.classList.toggle(\"hasOpenNavigation\");\n        collapseElement(mobileMebox);\n        toggleElement(navdrawer);\n    });\n\n    mobileMeBoxBtn && mobileMeBoxBtn.addEventListener(\"click\", function () {\n        mobileMeBoxBtn.classList.toggle(\"isToggled\");\n        mainHeader.classList.remove(\"hasOpenNavigation\");\n        menuButton.classList.remove(\"isToggled\");\n        collapseElement(navdrawer);\n        toggleElement(mobileMebox);\n    });\n\n    mobileMeboxBtnClose && mobileMeboxBtnClose.addEventListener(\"click\", function () {\n        collapseElement(mobileMebox);\n    });\n\n    /**\n     * @param {HTMLElement} element\n     */\n    function toggleElement(element) {\n        if (element.style.height === COLLAPSED_HEIGHT) {\n            expandElement(element);\n        } else {\n            collapseElement(element);\n        }\n    }\n\n    /**\n     * @param {HTMLElement} element\n     */\n    function collapseElement(element) {\n        element.style.height = COLLAPSED_HEIGHT;\n    }\n\n    /**\n     *\n     * @param {HTMLElement} element\n     */\n    function expandElement(element) {\n        element.style.height = element.getAttribute(CALC_HEIGHT_ATTR) + \"px\";\n    }\n\n    /**\n     * Get the calculated height of an element and\n     *\n     * @param {HTMLElement} element\n     */\n    function prepareElement(element) {\n        element.classList.add(INIT_CLASS);\n        element.style.height = \"auto\";\n        var calcedHeight = element.getBoundingClientRect().height;\n\n        // Visual hide the element.\n        element.setAttribute(CALC_HEIGHT_ATTR, calcedHeight.toString());\n        collapseElement(element);\n        element.classList.remove(INIT_CLASS);\n    }\n}//# sourceURL=[module]\n//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJzb3VyY2VzIjpbIndlYnBhY2s6Ly8vLi9zcmMvanMvbW9iaWxlTmF2aWdhdGlvbi5qcz9mN2JlIl0sIm5hbWVzIjpbInNldHVwTW9iaWxlTmF2aWdhdGlvbiIsIklOSVRfQ0xBU1MiLCJDQUxDX0hFSUdIVF9BVFRSIiwiQ09MTEFQU0VEX0hFSUdIVCIsIm1lbnVCdXR0b24iLCJkb2N1bWVudCIsInF1ZXJ5U2VsZWN0b3IiLCJuYXZkcmF3ZXIiLCJtb2JpbGVNZWJveCIsIm1vYmlsZU1lQm94QnRuIiwibW9iaWxlTWVib3hCdG5DbG9zZSIsIm1haW5IZWFkZXIiLCJwcmVwYXJlRWxlbWVudCIsIndpbmRvdyIsImFkZEV2ZW50TGlzdGVuZXIiLCJyZXF1ZXN0QW5pbWF0aW9uRnJhbWUiLCJjbGFzc0xpc3QiLCJ0b2dnbGUiLCJjb2xsYXBzZUVsZW1lbnQiLCJ0b2dnbGVFbGVtZW50IiwicmVtb3ZlIiwiZWxlbWVudCIsInN0eWxlIiwiaGVpZ2h0IiwiZXhwYW5kRWxlbWVudCIsImdldEF0dHJpYnV0ZSIsImFkZCIsImNhbGNlZEhlaWdodCIsImdldEJvdW5kaW5nQ2xpZW50UmVjdCIsInNldEF0dHJpYnV0ZSIsInRvU3RyaW5nIl0sIm1hcHBpbmdzIjoiOzs7OztRQVVnQkEscUIsR0FBQUEscUI7QUFWaEI7Ozs7OztBQU1BLElBQU1DLGFBQWEscUJBQW5CO0FBQ0EsSUFBTUMsbUJBQW1CLGFBQXpCO0FBQ0EsSUFBTUMsbUJBQW1CLEtBQXpCOztBQUVPLFNBQVNILHFCQUFULEdBQWlDOztBQUVwQyxRQUFNSSxhQUFhQyxTQUFTQyxhQUFULENBQXVCLGNBQXZCLENBQW5CO0FBQ0E7QUFDQSxRQUFNQyxZQUFZRixTQUFTQyxhQUFULENBQXVCLFNBQXZCLENBQWxCO0FBQ0E7QUFDQSxRQUFNRSxjQUFjSCxTQUFTQyxhQUFULENBQXVCLGlCQUF2QixDQUFwQjtBQUNBLFFBQU1HLGlCQUFpQkosU0FBU0MsYUFBVCxDQUF1QixxQkFBdkIsQ0FBdkI7QUFDQSxRQUFNSSxzQkFBc0JMLFNBQVNDLGFBQVQsQ0FBdUIsMEJBQXZCLENBQTVCO0FBQ0EsUUFBTUssYUFBYU4sU0FBU0MsYUFBVCxDQUF1QixhQUF2QixDQUFuQjs7QUFFQTtBQUNBTSxtQkFBZUosV0FBZjtBQUNBSSxtQkFBZUwsU0FBZjs7QUFFQTtBQUNBTSxXQUFPQyxnQkFBUCxDQUF3QixRQUF4QixFQUFrQyxZQUFNO0FBQ3BDQyw4QkFBc0IsWUFBTTtBQUN4QkgsMkJBQWVKLFdBQWY7QUFDQUksMkJBQWVMLFNBQWY7QUFDSCxTQUhEO0FBSUgsS0FMRDs7QUFPQUgsZUFBV1UsZ0JBQVgsQ0FBNEIsT0FBNUIsRUFBcUMsWUFBTTtBQUN2Q1YsbUJBQVdZLFNBQVgsQ0FBcUJDLE1BQXJCLENBQTRCLFdBQTVCO0FBQ0FOLG1CQUFXSyxTQUFYLENBQXFCQyxNQUFyQixDQUE0QixtQkFBNUI7QUFDQUMsd0JBQWdCVixXQUFoQjtBQUNBVyxzQkFBY1osU0FBZDtBQUNILEtBTEQ7O0FBT0FFLHNCQUFrQkEsZUFBZUssZ0JBQWYsQ0FBZ0MsT0FBaEMsRUFBeUMsWUFBTTtBQUM3REwsdUJBQWVPLFNBQWYsQ0FBeUJDLE1BQXpCLENBQWdDLFdBQWhDO0FBQ0FOLG1CQUFXSyxTQUFYLENBQXFCSSxNQUFyQixDQUE0QixtQkFBNUI7QUFDQWhCLG1CQUFXWSxTQUFYLENBQXFCSSxNQUFyQixDQUE0QixXQUE1QjtBQUNBRix3QkFBZ0JYLFNBQWhCO0FBQ0FZLHNCQUFjWCxXQUFkO0FBQ0gsS0FOaUIsQ0FBbEI7O0FBUUFFLDJCQUF1QkEsb0JBQW9CSSxnQkFBcEIsQ0FBcUMsT0FBckMsRUFBOEMsWUFBTTtBQUN2RUksd0JBQWdCVixXQUFoQjtBQUNILEtBRnNCLENBQXZCOztBQUlBOzs7QUFHQSxhQUFTVyxhQUFULENBQXVCRSxPQUF2QixFQUFnQztBQUM1QixZQUFJQSxRQUFRQyxLQUFSLENBQWNDLE1BQWQsS0FBeUJwQixnQkFBN0IsRUFBK0M7QUFDM0NxQiwwQkFBY0gsT0FBZDtBQUNILFNBRkQsTUFFTztBQUNISCw0QkFBZ0JHLE9BQWhCO0FBQ0g7QUFDSjs7QUFFRDs7O0FBR0EsYUFBU0gsZUFBVCxDQUF5QkcsT0FBekIsRUFBa0M7QUFDOUJBLGdCQUFRQyxLQUFSLENBQWNDLE1BQWQsR0FBdUJwQixnQkFBdkI7QUFDSDs7QUFFRDs7OztBQUlBLGFBQVNxQixhQUFULENBQXVCSCxPQUF2QixFQUFnQztBQUM1QkEsZ0JBQVFDLEtBQVIsQ0FBY0MsTUFBZCxHQUF1QkYsUUFBUUksWUFBUixDQUFxQnZCLGdCQUFyQixJQUF5QyxJQUFoRTtBQUNIOztBQUVEOzs7OztBQUtBLGFBQVNVLGNBQVQsQ0FBd0JTLE9BQXhCLEVBQWlDO0FBQzdCQSxnQkFBUUwsU0FBUixDQUFrQlUsR0FBbEIsQ0FBc0J6QixVQUF0QjtBQUNBb0IsZ0JBQVFDLEtBQVIsQ0FBY0MsTUFBZCxHQUF1QixNQUF2QjtBQUNBLFlBQU1JLGVBQWVOLFFBQVFPLHFCQUFSLEdBQWdDTCxNQUFyRDs7QUFFQTtBQUNBRixnQkFBUVEsWUFBUixDQUFxQjNCLGdCQUFyQixFQUF1Q3lCLGFBQWFHLFFBQWIsRUFBdkM7QUFDQVosd0JBQWdCRyxPQUFoQjtBQUNBQSxnQkFBUUwsU0FBUixDQUFrQkksTUFBbEIsQ0FBeUJuQixVQUF6QjtBQUNIO0FBQ0oiLCJmaWxlIjoiLi9zcmMvanMvbW9iaWxlTmF2aWdhdGlvbi5qcy5qcyIsInNvdXJjZXNDb250ZW50IjpbIi8qIVxuICogQGF1dGhvciBJc2lzIChpZ3JhemlhdHRvKSBHcmF6aWF0dG8gPGlzaXMuZ0B2YW5pbGxhZm9ydW1zLmNvbT5cbiAqIEBjb3B5cmlnaHQgMjAwOS0yMDE4IFZhbmlsbGEgRm9ydW1zIEluYy5cbiAqIEBsaWNlbnNlIEdQTC0yLjAtb25seVxuICovXG5cbmNvbnN0IElOSVRfQ0xBU1MgPSBcIm5lZWRzSW5pdGlhbGl6YXRpb25cIjtcbmNvbnN0IENBTENfSEVJR0hUX0FUVFIgPSBcImRhdGEtaGVpZ2h0XCI7XG5jb25zdCBDT0xMQVBTRURfSEVJR0hUID0gXCIwcHhcIjtcblxuZXhwb3J0IGZ1bmN0aW9uIHNldHVwTW9iaWxlTmF2aWdhdGlvbigpIHtcblxuICAgIGNvbnN0IG1lbnVCdXR0b24gPSBkb2N1bWVudC5xdWVyeVNlbGVjdG9yKFwiI21lbnUtYnV0dG9uXCIpO1xuICAgIC8qKiBAdHlwZSB7SFRNTEVsZW1lbnR9ICovXG4gICAgY29uc3QgbmF2ZHJhd2VyID0gZG9jdW1lbnQucXVlcnlTZWxlY3RvcihcIi5qcy1uYXZcIik7XG4gICAgLyoqIEB0eXBlIHtIVE1MRWxlbWVudH0gKi9cbiAgICBjb25zdCBtb2JpbGVNZWJveCA9IGRvY3VtZW50LnF1ZXJ5U2VsZWN0b3IoXCIuanMtbW9iaWxlTWVib3hcIik7XG4gICAgY29uc3QgbW9iaWxlTWVCb3hCdG4gPSBkb2N1bWVudC5xdWVyeVNlbGVjdG9yKFwiLm1vYmlsZU1lQm94LWJ1dHRvblwiKTtcbiAgICBjb25zdCBtb2JpbGVNZWJveEJ0bkNsb3NlID0gZG9jdW1lbnQucXVlcnlTZWxlY3RvcihcIi5tb2JpbGVNZWJveC1idXR0b25DbG9zZVwiKTtcbiAgICBjb25zdCBtYWluSGVhZGVyID0gZG9jdW1lbnQucXVlcnlTZWxlY3RvcihcIiNNYWluSGVhZGVyXCIpO1xuXG4gICAgLy8gQ2FsY3VsYXRlIHRoZSB2YWx1ZXMgaW5pdGlhbGx5LlxuICAgIHByZXBhcmVFbGVtZW50KG1vYmlsZU1lYm94KTtcbiAgICBwcmVwYXJlRWxlbWVudChuYXZkcmF3ZXIpO1xuXG4gICAgLy8gVXBkYXRlIHRoZSBjYWxjdWxhdGVkIHZhbHVlcyBvbiByZXNpemUuXG4gICAgd2luZG93LmFkZEV2ZW50TGlzdGVuZXIoXCJyZXNpemVcIiwgKCkgPT4ge1xuICAgICAgICByZXF1ZXN0QW5pbWF0aW9uRnJhbWUoKCkgPT4ge1xuICAgICAgICAgICAgcHJlcGFyZUVsZW1lbnQobW9iaWxlTWVib3gpO1xuICAgICAgICAgICAgcHJlcGFyZUVsZW1lbnQobmF2ZHJhd2VyKTtcbiAgICAgICAgfSlcbiAgICB9KVxuXG4gICAgbWVudUJ1dHRvbi5hZGRFdmVudExpc3RlbmVyKFwiY2xpY2tcIiwgKCkgPT4ge1xuICAgICAgICBtZW51QnV0dG9uLmNsYXNzTGlzdC50b2dnbGUoXCJpc1RvZ2dsZWRcIik7XG4gICAgICAgIG1haW5IZWFkZXIuY2xhc3NMaXN0LnRvZ2dsZShcImhhc09wZW5OYXZpZ2F0aW9uXCIpO1xuICAgICAgICBjb2xsYXBzZUVsZW1lbnQobW9iaWxlTWVib3gpO1xuICAgICAgICB0b2dnbGVFbGVtZW50KG5hdmRyYXdlcik7XG4gICAgfSk7XG5cbiAgICBtb2JpbGVNZUJveEJ0biAmJiBtb2JpbGVNZUJveEJ0bi5hZGRFdmVudExpc3RlbmVyKFwiY2xpY2tcIiwgKCkgPT4ge1xuICAgICAgICBtb2JpbGVNZUJveEJ0bi5jbGFzc0xpc3QudG9nZ2xlKFwiaXNUb2dnbGVkXCIpO1xuICAgICAgICBtYWluSGVhZGVyLmNsYXNzTGlzdC5yZW1vdmUoXCJoYXNPcGVuTmF2aWdhdGlvblwiKTtcbiAgICAgICAgbWVudUJ1dHRvbi5jbGFzc0xpc3QucmVtb3ZlKFwiaXNUb2dnbGVkXCIpO1xuICAgICAgICBjb2xsYXBzZUVsZW1lbnQobmF2ZHJhd2VyKVxuICAgICAgICB0b2dnbGVFbGVtZW50KG1vYmlsZU1lYm94KTtcbiAgICB9KTtcblxuICAgIG1vYmlsZU1lYm94QnRuQ2xvc2UgJiYgbW9iaWxlTWVib3hCdG5DbG9zZS5hZGRFdmVudExpc3RlbmVyKFwiY2xpY2tcIiwgKCkgPT4ge1xuICAgICAgICBjb2xsYXBzZUVsZW1lbnQobW9iaWxlTWVib3gpO1xuICAgIH0pO1xuXG4gICAgLyoqXG4gICAgICogQHBhcmFtIHtIVE1MRWxlbWVudH0gZWxlbWVudFxuICAgICAqL1xuICAgIGZ1bmN0aW9uIHRvZ2dsZUVsZW1lbnQoZWxlbWVudCkge1xuICAgICAgICBpZiAoZWxlbWVudC5zdHlsZS5oZWlnaHQgPT09IENPTExBUFNFRF9IRUlHSFQpIHtcbiAgICAgICAgICAgIGV4cGFuZEVsZW1lbnQoZWxlbWVudCk7XG4gICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgICBjb2xsYXBzZUVsZW1lbnQoZWxlbWVudCk7XG4gICAgICAgIH1cbiAgICB9XG5cbiAgICAvKipcbiAgICAgKiBAcGFyYW0ge0hUTUxFbGVtZW50fSBlbGVtZW50XG4gICAgICovXG4gICAgZnVuY3Rpb24gY29sbGFwc2VFbGVtZW50KGVsZW1lbnQpIHtcbiAgICAgICAgZWxlbWVudC5zdHlsZS5oZWlnaHQgPSBDT0xMQVBTRURfSEVJR0hUO1xuICAgIH1cblxuICAgIC8qKlxuICAgICAqXG4gICAgICogQHBhcmFtIHtIVE1MRWxlbWVudH0gZWxlbWVudFxuICAgICAqL1xuICAgIGZ1bmN0aW9uIGV4cGFuZEVsZW1lbnQoZWxlbWVudCkge1xuICAgICAgICBlbGVtZW50LnN0eWxlLmhlaWdodCA9IGVsZW1lbnQuZ2V0QXR0cmlidXRlKENBTENfSEVJR0hUX0FUVFIpICsgXCJweFwiO1xuICAgIH1cblxuICAgIC8qKlxuICAgICAqIEdldCB0aGUgY2FsY3VsYXRlZCBoZWlnaHQgb2YgYW4gZWxlbWVudCBhbmRcbiAgICAgKlxuICAgICAqIEBwYXJhbSB7SFRNTEVsZW1lbnR9IGVsZW1lbnRcbiAgICAgKi9cbiAgICBmdW5jdGlvbiBwcmVwYXJlRWxlbWVudChlbGVtZW50KSB7XG4gICAgICAgIGVsZW1lbnQuY2xhc3NMaXN0LmFkZChJTklUX0NMQVNTKTtcbiAgICAgICAgZWxlbWVudC5zdHlsZS5oZWlnaHQgPSBcImF1dG9cIjtcbiAgICAgICAgY29uc3QgY2FsY2VkSGVpZ2h0ID0gZWxlbWVudC5nZXRCb3VuZGluZ0NsaWVudFJlY3QoKS5oZWlnaHQ7XG5cbiAgICAgICAgLy8gVmlzdWFsIGhpZGUgdGhlIGVsZW1lbnQuXG4gICAgICAgIGVsZW1lbnQuc2V0QXR0cmlidXRlKENBTENfSEVJR0hUX0FUVFIsIGNhbGNlZEhlaWdodC50b1N0cmluZygpKTtcbiAgICAgICAgY29sbGFwc2VFbGVtZW50KGVsZW1lbnQpO1xuICAgICAgICBlbGVtZW50LmNsYXNzTGlzdC5yZW1vdmUoSU5JVF9DTEFTUyk7XG4gICAgfVxufVxuIl0sInNvdXJjZVJvb3QiOiIifQ==\n//# sourceURL=webpack-internal:///./src/js/mobileNavigation.js\n");

/***/ })

/******/ });