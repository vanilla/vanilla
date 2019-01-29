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
eval("\n\nObject.defineProperty(exports, \"__esModule\", {\n    value: true\n});\nexports.setupMobileNavigation = setupMobileNavigation;\n/*!\n * @author Isis (igraziatto) Graziatto <isis.g@vanillaforums.com>\n * @copyright 2009-2018 Vanilla Forums Inc.\n * @license GPL-2.0-only\n */\n\nfunction setupMobileNavigation() {\n    var CALC_HEIGHT_ATTR = \"data-height\";\n    var COLLAPSED_HEIGHT = \"0px\";\n\n    var menuButton = document.querySelector(\"#menu-button\");\n    /** @type {HTMLElement} */\n    var navdrawer = document.querySelector(\"#navdrawer\");\n    /** @type {HTMLElement} */\n    var mobileMebox = document.querySelector(\".js-mobileMebox\");\n    var mobileMeBoxBtn = document.querySelector(\".mobileMeBox-button\");\n    var mobileMeboxBtnClose = document.querySelector(\".mobileMebox-buttonClose\");\n    var mainHeader = document.querySelector(\"#MainHeader\");\n\n    prepareElement(mobileMebox);\n    prepareElement(navdrawer);\n\n    menuButton.addEventListener(\"click\", function () {\n        menuButton.classList.toggle(\"isToggled\");\n        mainHeader.classList.toggle(\"hasOpenNavigation\");\n        collapseElement(mobileMebox);\n        toggleElement(navdrawer);\n    });\n\n    mobileMeBoxBtn.addEventListener(\"click\", function () {\n        mobileMeBoxBtn.classList.toggle(\"isToggled\");\n        mainHeader.classList.remove(\"hasOpenNavigation\");\n        menuButton.classList.remove(\"isToggled\");\n        collapseElement(navdrawer);\n        toggleElement(mobileMebox);\n    });\n\n    mobileMeboxBtnClose.addEventListener(\"click\", function () {\n        collapseElement(mobileMebox);\n    });\n\n    /**\n     * @param {HTMLElement} element\n     */\n    function toggleElement(element) {\n        if (element.style.height === COLLAPSED_HEIGHT) {\n            expandElement(element);\n        } else {\n            collapseElement(element);\n        }\n    }\n\n    /**\n     * @param {HTMLElement} element\n     */\n    function collapseElement(element) {\n        element.style.height = COLLAPSED_HEIGHT;\n    }\n\n    /**\n     *\n     * @param {HTMLElement} element\n     */\n    function expandElement(element) {\n        element.style.height = element.getAttribute(CALC_HEIGHT_ATTR) + \"px\";\n    }\n\n    /**\n     * Get the calculated height of an element and\n     *\n     * @param {HTMLElement} element\n     */\n    function prepareElement(element) {\n        element.style.visibility = \"hidden\";\n        element.style.height = \"auto\";\n        var calcedHeight = element.getBoundingClientRect().height;\n\n        // Visual hide the element.\n        element.setAttribute(CALC_HEIGHT_ATTR, calcedHeight.toString());\n        collapseElement(element);\n        element.style.visibility = \"initial\";\n    }\n}//# sourceURL=[module]\n//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJzb3VyY2VzIjpbIndlYnBhY2s6Ly8vLi9zcmMvanMvbW9iaWxlTmF2aWdhdGlvbi5qcz9mN2JlIl0sIm5hbWVzIjpbInNldHVwTW9iaWxlTmF2aWdhdGlvbiIsIkNBTENfSEVJR0hUX0FUVFIiLCJDT0xMQVBTRURfSEVJR0hUIiwibWVudUJ1dHRvbiIsImRvY3VtZW50IiwicXVlcnlTZWxlY3RvciIsIm5hdmRyYXdlciIsIm1vYmlsZU1lYm94IiwibW9iaWxlTWVCb3hCdG4iLCJtb2JpbGVNZWJveEJ0bkNsb3NlIiwibWFpbkhlYWRlciIsInByZXBhcmVFbGVtZW50IiwiYWRkRXZlbnRMaXN0ZW5lciIsImNsYXNzTGlzdCIsInRvZ2dsZSIsImNvbGxhcHNlRWxlbWVudCIsInRvZ2dsZUVsZW1lbnQiLCJyZW1vdmUiLCJlbGVtZW50Iiwic3R5bGUiLCJoZWlnaHQiLCJleHBhbmRFbGVtZW50IiwiZ2V0QXR0cmlidXRlIiwidmlzaWJpbGl0eSIsImNhbGNlZEhlaWdodCIsImdldEJvdW5kaW5nQ2xpZW50UmVjdCIsInNldEF0dHJpYnV0ZSIsInRvU3RyaW5nIl0sIm1hcHBpbmdzIjoiOzs7OztRQU1nQkEscUIsR0FBQUEscUI7QUFOaEI7Ozs7OztBQU1PLFNBQVNBLHFCQUFULEdBQWlDO0FBQ3BDLFFBQU1DLG1CQUFtQixhQUF6QjtBQUNBLFFBQU1DLG1CQUFtQixLQUF6Qjs7QUFFQSxRQUFNQyxhQUFhQyxTQUFTQyxhQUFULENBQXVCLGNBQXZCLENBQW5CO0FBQ0E7QUFDQSxRQUFNQyxZQUFZRixTQUFTQyxhQUFULENBQXVCLFlBQXZCLENBQWxCO0FBQ0E7QUFDQSxRQUFNRSxjQUFjSCxTQUFTQyxhQUFULENBQXVCLGlCQUF2QixDQUFwQjtBQUNBLFFBQU1HLGlCQUFpQkosU0FBU0MsYUFBVCxDQUF1QixxQkFBdkIsQ0FBdkI7QUFDQSxRQUFNSSxzQkFBc0JMLFNBQVNDLGFBQVQsQ0FBdUIsMEJBQXZCLENBQTVCO0FBQ0EsUUFBTUssYUFBYU4sU0FBU0MsYUFBVCxDQUF1QixhQUF2QixDQUFuQjs7QUFFQU0sbUJBQWVKLFdBQWY7QUFDQUksbUJBQWVMLFNBQWY7O0FBRUFILGVBQVdTLGdCQUFYLENBQTRCLE9BQTVCLEVBQXFDLFlBQU07QUFDdkNULG1CQUFXVSxTQUFYLENBQXFCQyxNQUFyQixDQUE0QixXQUE1QjtBQUNBSixtQkFBV0csU0FBWCxDQUFxQkMsTUFBckIsQ0FBNEIsbUJBQTVCO0FBQ0FDLHdCQUFnQlIsV0FBaEI7QUFDQVMsc0JBQWNWLFNBQWQ7QUFDSCxLQUxEOztBQU9BRSxtQkFBZUksZ0JBQWYsQ0FBZ0MsT0FBaEMsRUFBeUMsWUFBTTtBQUMzQ0osdUJBQWVLLFNBQWYsQ0FBeUJDLE1BQXpCLENBQWdDLFdBQWhDO0FBQ0FKLG1CQUFXRyxTQUFYLENBQXFCSSxNQUFyQixDQUE0QixtQkFBNUI7QUFDQWQsbUJBQVdVLFNBQVgsQ0FBcUJJLE1BQXJCLENBQTRCLFdBQTVCO0FBQ0FGLHdCQUFnQlQsU0FBaEI7QUFDQVUsc0JBQWNULFdBQWQ7QUFDSCxLQU5EOztBQVFBRSx3QkFBb0JHLGdCQUFwQixDQUFxQyxPQUFyQyxFQUE4QyxZQUFNO0FBQ2hERyx3QkFBZ0JSLFdBQWhCO0FBQ0gsS0FGRDs7QUFJQTs7O0FBR0EsYUFBU1MsYUFBVCxDQUF1QkUsT0FBdkIsRUFBZ0M7QUFDNUIsWUFBSUEsUUFBUUMsS0FBUixDQUFjQyxNQUFkLEtBQXlCbEIsZ0JBQTdCLEVBQStDO0FBQzNDbUIsMEJBQWNILE9BQWQ7QUFDSCxTQUZELE1BRU87QUFDSEgsNEJBQWdCRyxPQUFoQjtBQUNIO0FBQ0o7O0FBRUQ7OztBQUdBLGFBQVNILGVBQVQsQ0FBeUJHLE9BQXpCLEVBQWtDO0FBQzlCQSxnQkFBUUMsS0FBUixDQUFjQyxNQUFkLEdBQXVCbEIsZ0JBQXZCO0FBQ0g7O0FBRUQ7Ozs7QUFJQSxhQUFTbUIsYUFBVCxDQUF1QkgsT0FBdkIsRUFBZ0M7QUFDNUJBLGdCQUFRQyxLQUFSLENBQWNDLE1BQWQsR0FBdUJGLFFBQVFJLFlBQVIsQ0FBcUJyQixnQkFBckIsSUFBeUMsSUFBaEU7QUFDSDs7QUFFRDs7Ozs7QUFLQSxhQUFTVSxjQUFULENBQXdCTyxPQUF4QixFQUFpQztBQUM3QkEsZ0JBQVFDLEtBQVIsQ0FBY0ksVUFBZCxHQUEyQixRQUEzQjtBQUNBTCxnQkFBUUMsS0FBUixDQUFjQyxNQUFkLEdBQXVCLE1BQXZCO0FBQ0EsWUFBTUksZUFBZU4sUUFBUU8scUJBQVIsR0FBZ0NMLE1BQXJEOztBQUVBO0FBQ0FGLGdCQUFRUSxZQUFSLENBQXFCekIsZ0JBQXJCLEVBQXVDdUIsYUFBYUcsUUFBYixFQUF2QztBQUNBWix3QkFBZ0JHLE9BQWhCO0FBQ0FBLGdCQUFRQyxLQUFSLENBQWNJLFVBQWQsR0FBMkIsU0FBM0I7QUFDSDtBQUNKIiwiZmlsZSI6Ii4vc3JjL2pzL21vYmlsZU5hdmlnYXRpb24uanMuanMiLCJzb3VyY2VzQ29udGVudCI6WyIvKiFcbiAqIEBhdXRob3IgSXNpcyAoaWdyYXppYXR0bykgR3JhemlhdHRvIDxpc2lzLmdAdmFuaWxsYWZvcnVtcy5jb20+XG4gKiBAY29weXJpZ2h0IDIwMDktMjAxOCBWYW5pbGxhIEZvcnVtcyBJbmMuXG4gKiBAbGljZW5zZSBHUEwtMi4wLW9ubHlcbiAqL1xuXG5leHBvcnQgZnVuY3Rpb24gc2V0dXBNb2JpbGVOYXZpZ2F0aW9uKCkge1xuICAgIGNvbnN0IENBTENfSEVJR0hUX0FUVFIgPSBcImRhdGEtaGVpZ2h0XCI7XG4gICAgY29uc3QgQ09MTEFQU0VEX0hFSUdIVCA9IFwiMHB4XCI7XG5cbiAgICBjb25zdCBtZW51QnV0dG9uID0gZG9jdW1lbnQucXVlcnlTZWxlY3RvcihcIiNtZW51LWJ1dHRvblwiKTtcbiAgICAvKiogQHR5cGUge0hUTUxFbGVtZW50fSAqL1xuICAgIGNvbnN0IG5hdmRyYXdlciA9IGRvY3VtZW50LnF1ZXJ5U2VsZWN0b3IoXCIjbmF2ZHJhd2VyXCIpO1xuICAgIC8qKiBAdHlwZSB7SFRNTEVsZW1lbnR9ICovXG4gICAgY29uc3QgbW9iaWxlTWVib3ggPSBkb2N1bWVudC5xdWVyeVNlbGVjdG9yKFwiLmpzLW1vYmlsZU1lYm94XCIpO1xuICAgIGNvbnN0IG1vYmlsZU1lQm94QnRuID0gZG9jdW1lbnQucXVlcnlTZWxlY3RvcihcIi5tb2JpbGVNZUJveC1idXR0b25cIik7XG4gICAgY29uc3QgbW9iaWxlTWVib3hCdG5DbG9zZSA9IGRvY3VtZW50LnF1ZXJ5U2VsZWN0b3IoXCIubW9iaWxlTWVib3gtYnV0dG9uQ2xvc2VcIik7XG4gICAgY29uc3QgbWFpbkhlYWRlciA9IGRvY3VtZW50LnF1ZXJ5U2VsZWN0b3IoXCIjTWFpbkhlYWRlclwiKTtcblxuICAgIHByZXBhcmVFbGVtZW50KG1vYmlsZU1lYm94KTtcbiAgICBwcmVwYXJlRWxlbWVudChuYXZkcmF3ZXIpO1xuXG4gICAgbWVudUJ1dHRvbi5hZGRFdmVudExpc3RlbmVyKFwiY2xpY2tcIiwgKCkgPT4ge1xuICAgICAgICBtZW51QnV0dG9uLmNsYXNzTGlzdC50b2dnbGUoXCJpc1RvZ2dsZWRcIik7XG4gICAgICAgIG1haW5IZWFkZXIuY2xhc3NMaXN0LnRvZ2dsZShcImhhc09wZW5OYXZpZ2F0aW9uXCIpO1xuICAgICAgICBjb2xsYXBzZUVsZW1lbnQobW9iaWxlTWVib3gpO1xuICAgICAgICB0b2dnbGVFbGVtZW50KG5hdmRyYXdlcik7XG4gICAgfSk7XG5cbiAgICBtb2JpbGVNZUJveEJ0bi5hZGRFdmVudExpc3RlbmVyKFwiY2xpY2tcIiwgKCkgPT4ge1xuICAgICAgICBtb2JpbGVNZUJveEJ0bi5jbGFzc0xpc3QudG9nZ2xlKFwiaXNUb2dnbGVkXCIpO1xuICAgICAgICBtYWluSGVhZGVyLmNsYXNzTGlzdC5yZW1vdmUoXCJoYXNPcGVuTmF2aWdhdGlvblwiKTtcbiAgICAgICAgbWVudUJ1dHRvbi5jbGFzc0xpc3QucmVtb3ZlKFwiaXNUb2dnbGVkXCIpO1xuICAgICAgICBjb2xsYXBzZUVsZW1lbnQobmF2ZHJhd2VyKVxuICAgICAgICB0b2dnbGVFbGVtZW50KG1vYmlsZU1lYm94KTtcbiAgICB9KTtcblxuICAgIG1vYmlsZU1lYm94QnRuQ2xvc2UuYWRkRXZlbnRMaXN0ZW5lcihcImNsaWNrXCIsICgpID0+IHtcbiAgICAgICAgY29sbGFwc2VFbGVtZW50KG1vYmlsZU1lYm94KTtcbiAgICB9KTtcblxuICAgIC8qKlxuICAgICAqIEBwYXJhbSB7SFRNTEVsZW1lbnR9IGVsZW1lbnRcbiAgICAgKi9cbiAgICBmdW5jdGlvbiB0b2dnbGVFbGVtZW50KGVsZW1lbnQpIHtcbiAgICAgICAgaWYgKGVsZW1lbnQuc3R5bGUuaGVpZ2h0ID09PSBDT0xMQVBTRURfSEVJR0hUKSB7XG4gICAgICAgICAgICBleHBhbmRFbGVtZW50KGVsZW1lbnQpO1xuICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICAgY29sbGFwc2VFbGVtZW50KGVsZW1lbnQpO1xuICAgICAgICB9XG4gICAgfVxuXG4gICAgLyoqXG4gICAgICogQHBhcmFtIHtIVE1MRWxlbWVudH0gZWxlbWVudFxuICAgICAqL1xuICAgIGZ1bmN0aW9uIGNvbGxhcHNlRWxlbWVudChlbGVtZW50KSB7XG4gICAgICAgIGVsZW1lbnQuc3R5bGUuaGVpZ2h0ID0gQ09MTEFQU0VEX0hFSUdIVDtcbiAgICB9XG5cbiAgICAvKipcbiAgICAgKlxuICAgICAqIEBwYXJhbSB7SFRNTEVsZW1lbnR9IGVsZW1lbnRcbiAgICAgKi9cbiAgICBmdW5jdGlvbiBleHBhbmRFbGVtZW50KGVsZW1lbnQpIHtcbiAgICAgICAgZWxlbWVudC5zdHlsZS5oZWlnaHQgPSBlbGVtZW50LmdldEF0dHJpYnV0ZShDQUxDX0hFSUdIVF9BVFRSKSArIFwicHhcIjtcbiAgICB9XG5cbiAgICAvKipcbiAgICAgKiBHZXQgdGhlIGNhbGN1bGF0ZWQgaGVpZ2h0IG9mIGFuIGVsZW1lbnQgYW5kXG4gICAgICpcbiAgICAgKiBAcGFyYW0ge0hUTUxFbGVtZW50fSBlbGVtZW50XG4gICAgICovXG4gICAgZnVuY3Rpb24gcHJlcGFyZUVsZW1lbnQoZWxlbWVudCkge1xuICAgICAgICBlbGVtZW50LnN0eWxlLnZpc2liaWxpdHkgPSBcImhpZGRlblwiO1xuICAgICAgICBlbGVtZW50LnN0eWxlLmhlaWdodCA9IFwiYXV0b1wiO1xuICAgICAgICBjb25zdCBjYWxjZWRIZWlnaHQgPSBlbGVtZW50LmdldEJvdW5kaW5nQ2xpZW50UmVjdCgpLmhlaWdodDtcblxuICAgICAgICAvLyBWaXN1YWwgaGlkZSB0aGUgZWxlbWVudC5cbiAgICAgICAgZWxlbWVudC5zZXRBdHRyaWJ1dGUoQ0FMQ19IRUlHSFRfQVRUUiwgY2FsY2VkSGVpZ2h0LnRvU3RyaW5nKCkpO1xuICAgICAgICBjb2xsYXBzZUVsZW1lbnQoZWxlbWVudCk7XG4gICAgICAgIGVsZW1lbnQuc3R5bGUudmlzaWJpbGl0eSA9IFwiaW5pdGlhbFwiO1xuICAgIH1cbn1cbiJdLCJzb3VyY2VSb290IjoiIn0=\n//# sourceURL=webpack-internal:///./src/js/mobileNavigation.js\n");

/***/ })

/******/ });