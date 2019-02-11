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
eval("\n\nObject.defineProperty(exports, \"__esModule\", {\n    value: true\n});\nexports.setupMobileNavigation = setupMobileNavigation;\n/*!\n * @author Isis (igraziatto) Graziatto <isis.g@vanillaforums.com>\n * @copyright 2009-2018 Vanilla Forums Inc.\n * @license GPL-2.0-only\n */\n\nvar INIT_CLASS = \"needsInitialization\";\nvar CALC_HEIGHT_ATTR = \"data-height\";\nvar COLLAPSED_HEIGHT = \"0px\";\n\nfunction setupMobileNavigation() {\n\n    var menuButton = document.querySelector(\"#menu-button\");\n    /** @type {HTMLElement} */\n    var navdrawer = document.querySelector(\".js-nav\");\n    /** @type {HTMLElement} */\n    var mobileMebox = document.querySelector(\".js-mobileMebox\");\n    var mobileMeBoxBtn = document.querySelector(\".mobileMeBox-button\");\n    var mobileMeboxBtnClose = document.querySelector(\".mobileMebox-buttonClose\");\n    var mainHeader = document.querySelector(\"#MainHeader\");\n\n    // Calculate the values initially.\n    prepareElement(mobileMebox);\n    prepareElement(navdrawer);\n\n    // Update the calculated values on resize.\n    window.addEventListener(\"resize\", function () {\n        requestAnimationFrame(function () {\n            prepareElement(mobileMebox);\n            prepareElement(navdrawer);\n        });\n    });\n\n    menuButton.addEventListener(\"click\", function () {\n        menuButton.classList.toggle(\"isToggled\");\n        mainHeader.classList.toggle(\"hasOpenNavigation\");\n        collapseElement(mobileMebox);\n        toggleElement(navdrawer);\n    });\n\n    mobileMeBoxBtn.addEventListener(\"click\", function () {\n        mobileMeBoxBtn.classList.toggle(\"isToggled\");\n        mainHeader.classList.remove(\"hasOpenNavigation\");\n        menuButton.classList.remove(\"isToggled\");\n        collapseElement(navdrawer);\n        toggleElement(mobileMebox);\n    });\n\n    mobileMeboxBtnClose.addEventListener(\"click\", function () {\n        collapseElement(mobileMebox);\n    });\n\n    /**\n     * @param {HTMLElement} element\n     */\n    function toggleElement(element) {\n        if (element.style.height === COLLAPSED_HEIGHT) {\n            expandElement(element);\n        } else {\n            collapseElement(element);\n        }\n    }\n\n    /**\n     * @param {HTMLElement} element\n     */\n    function collapseElement(element) {\n        element.style.height = COLLAPSED_HEIGHT;\n    }\n\n    /**\n     *\n     * @param {HTMLElement} element\n     */\n    function expandElement(element) {\n        element.style.height = element.getAttribute(CALC_HEIGHT_ATTR) + \"px\";\n    }\n\n    /**\n     * Get the calculated height of an element and\n     *\n     * @param {HTMLElement} element\n     */\n    function prepareElement(element) {\n        element.classList.add(INIT_CLASS);\n        element.style.height = \"auto\";\n        var calcedHeight = element.getBoundingClientRect().height;\n\n        // Visual hide the element.\n        element.setAttribute(CALC_HEIGHT_ATTR, calcedHeight.toString());\n        collapseElement(element);\n        element.classList.remove(INIT_CLASS);\n    }\n}//# sourceURL=[module]\n//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJzb3VyY2VzIjpbIndlYnBhY2s6Ly8vLi9zcmMvanMvbW9iaWxlTmF2aWdhdGlvbi5qcz9mN2JlIl0sIm5hbWVzIjpbInNldHVwTW9iaWxlTmF2aWdhdGlvbiIsIklOSVRfQ0xBU1MiLCJDQUxDX0hFSUdIVF9BVFRSIiwiQ09MTEFQU0VEX0hFSUdIVCIsIm1lbnVCdXR0b24iLCJkb2N1bWVudCIsInF1ZXJ5U2VsZWN0b3IiLCJuYXZkcmF3ZXIiLCJtb2JpbGVNZWJveCIsIm1vYmlsZU1lQm94QnRuIiwibW9iaWxlTWVib3hCdG5DbG9zZSIsIm1haW5IZWFkZXIiLCJwcmVwYXJlRWxlbWVudCIsIndpbmRvdyIsImFkZEV2ZW50TGlzdGVuZXIiLCJyZXF1ZXN0QW5pbWF0aW9uRnJhbWUiLCJjbGFzc0xpc3QiLCJ0b2dnbGUiLCJjb2xsYXBzZUVsZW1lbnQiLCJ0b2dnbGVFbGVtZW50IiwicmVtb3ZlIiwiZWxlbWVudCIsInN0eWxlIiwiaGVpZ2h0IiwiZXhwYW5kRWxlbWVudCIsImdldEF0dHJpYnV0ZSIsImFkZCIsImNhbGNlZEhlaWdodCIsImdldEJvdW5kaW5nQ2xpZW50UmVjdCIsInNldEF0dHJpYnV0ZSIsInRvU3RyaW5nIl0sIm1hcHBpbmdzIjoiOzs7OztRQVVnQkEscUIsR0FBQUEscUI7QUFWaEI7Ozs7OztBQU1BLElBQU1DLGFBQWEscUJBQW5CO0FBQ0EsSUFBTUMsbUJBQW1CLGFBQXpCO0FBQ0EsSUFBTUMsbUJBQW1CLEtBQXpCOztBQUVPLFNBQVNILHFCQUFULEdBQWlDOztBQUVwQyxRQUFNSSxhQUFhQyxTQUFTQyxhQUFULENBQXVCLGNBQXZCLENBQW5CO0FBQ0E7QUFDQSxRQUFNQyxZQUFZRixTQUFTQyxhQUFULENBQXVCLFNBQXZCLENBQWxCO0FBQ0E7QUFDQSxRQUFNRSxjQUFjSCxTQUFTQyxhQUFULENBQXVCLGlCQUF2QixDQUFwQjtBQUNBLFFBQU1HLGlCQUFpQkosU0FBU0MsYUFBVCxDQUF1QixxQkFBdkIsQ0FBdkI7QUFDQSxRQUFNSSxzQkFBc0JMLFNBQVNDLGFBQVQsQ0FBdUIsMEJBQXZCLENBQTVCO0FBQ0EsUUFBTUssYUFBYU4sU0FBU0MsYUFBVCxDQUF1QixhQUF2QixDQUFuQjs7QUFFQTtBQUNBTSxtQkFBZUosV0FBZjtBQUNBSSxtQkFBZUwsU0FBZjs7QUFFQTtBQUNBTSxXQUFPQyxnQkFBUCxDQUF3QixRQUF4QixFQUFrQyxZQUFNO0FBQ3BDQyw4QkFBc0IsWUFBTTtBQUN4QkgsMkJBQWVKLFdBQWY7QUFDQUksMkJBQWVMLFNBQWY7QUFDSCxTQUhEO0FBSUgsS0FMRDs7QUFPQUgsZUFBV1UsZ0JBQVgsQ0FBNEIsT0FBNUIsRUFBcUMsWUFBTTtBQUN2Q1YsbUJBQVdZLFNBQVgsQ0FBcUJDLE1BQXJCLENBQTRCLFdBQTVCO0FBQ0FOLG1CQUFXSyxTQUFYLENBQXFCQyxNQUFyQixDQUE0QixtQkFBNUI7QUFDQUMsd0JBQWdCVixXQUFoQjtBQUNBVyxzQkFBY1osU0FBZDtBQUNILEtBTEQ7O0FBT0FFLG1CQUFlSyxnQkFBZixDQUFnQyxPQUFoQyxFQUF5QyxZQUFNO0FBQzNDTCx1QkFBZU8sU0FBZixDQUF5QkMsTUFBekIsQ0FBZ0MsV0FBaEM7QUFDQU4sbUJBQVdLLFNBQVgsQ0FBcUJJLE1BQXJCLENBQTRCLG1CQUE1QjtBQUNBaEIsbUJBQVdZLFNBQVgsQ0FBcUJJLE1BQXJCLENBQTRCLFdBQTVCO0FBQ0FGLHdCQUFnQlgsU0FBaEI7QUFDQVksc0JBQWNYLFdBQWQ7QUFDSCxLQU5EOztBQVFBRSx3QkFBb0JJLGdCQUFwQixDQUFxQyxPQUFyQyxFQUE4QyxZQUFNO0FBQ2hESSx3QkFBZ0JWLFdBQWhCO0FBQ0gsS0FGRDs7QUFJQTs7O0FBR0EsYUFBU1csYUFBVCxDQUF1QkUsT0FBdkIsRUFBZ0M7QUFDNUIsWUFBSUEsUUFBUUMsS0FBUixDQUFjQyxNQUFkLEtBQXlCcEIsZ0JBQTdCLEVBQStDO0FBQzNDcUIsMEJBQWNILE9BQWQ7QUFDSCxTQUZELE1BRU87QUFDSEgsNEJBQWdCRyxPQUFoQjtBQUNIO0FBQ0o7O0FBRUQ7OztBQUdBLGFBQVNILGVBQVQsQ0FBeUJHLE9BQXpCLEVBQWtDO0FBQzlCQSxnQkFBUUMsS0FBUixDQUFjQyxNQUFkLEdBQXVCcEIsZ0JBQXZCO0FBQ0g7O0FBRUQ7Ozs7QUFJQSxhQUFTcUIsYUFBVCxDQUF1QkgsT0FBdkIsRUFBZ0M7QUFDNUJBLGdCQUFRQyxLQUFSLENBQWNDLE1BQWQsR0FBdUJGLFFBQVFJLFlBQVIsQ0FBcUJ2QixnQkFBckIsSUFBeUMsSUFBaEU7QUFDSDs7QUFFRDs7Ozs7QUFLQSxhQUFTVSxjQUFULENBQXdCUyxPQUF4QixFQUFpQztBQUM3QkEsZ0JBQVFMLFNBQVIsQ0FBa0JVLEdBQWxCLENBQXNCekIsVUFBdEI7QUFDQW9CLGdCQUFRQyxLQUFSLENBQWNDLE1BQWQsR0FBdUIsTUFBdkI7QUFDQSxZQUFNSSxlQUFlTixRQUFRTyxxQkFBUixHQUFnQ0wsTUFBckQ7O0FBRUE7QUFDQUYsZ0JBQVFRLFlBQVIsQ0FBcUIzQixnQkFBckIsRUFBdUN5QixhQUFhRyxRQUFiLEVBQXZDO0FBQ0FaLHdCQUFnQkcsT0FBaEI7QUFDQUEsZ0JBQVFMLFNBQVIsQ0FBa0JJLE1BQWxCLENBQXlCbkIsVUFBekI7QUFDSDtBQUNKIiwiZmlsZSI6Ii4vc3JjL2pzL21vYmlsZU5hdmlnYXRpb24uanMuanMiLCJzb3VyY2VzQ29udGVudCI6WyIvKiFcbiAqIEBhdXRob3IgSXNpcyAoaWdyYXppYXR0bykgR3JhemlhdHRvIDxpc2lzLmdAdmFuaWxsYWZvcnVtcy5jb20+XG4gKiBAY29weXJpZ2h0IDIwMDktMjAxOCBWYW5pbGxhIEZvcnVtcyBJbmMuXG4gKiBAbGljZW5zZSBHUEwtMi4wLW9ubHlcbiAqL1xuXG5jb25zdCBJTklUX0NMQVNTID0gXCJuZWVkc0luaXRpYWxpemF0aW9uXCI7XG5jb25zdCBDQUxDX0hFSUdIVF9BVFRSID0gXCJkYXRhLWhlaWdodFwiO1xuY29uc3QgQ09MTEFQU0VEX0hFSUdIVCA9IFwiMHB4XCI7XG5cbmV4cG9ydCBmdW5jdGlvbiBzZXR1cE1vYmlsZU5hdmlnYXRpb24oKSB7XG5cbiAgICBjb25zdCBtZW51QnV0dG9uID0gZG9jdW1lbnQucXVlcnlTZWxlY3RvcihcIiNtZW51LWJ1dHRvblwiKTtcbiAgICAvKiogQHR5cGUge0hUTUxFbGVtZW50fSAqL1xuICAgIGNvbnN0IG5hdmRyYXdlciA9IGRvY3VtZW50LnF1ZXJ5U2VsZWN0b3IoXCIuanMtbmF2XCIpO1xuICAgIC8qKiBAdHlwZSB7SFRNTEVsZW1lbnR9ICovXG4gICAgY29uc3QgbW9iaWxlTWVib3ggPSBkb2N1bWVudC5xdWVyeVNlbGVjdG9yKFwiLmpzLW1vYmlsZU1lYm94XCIpO1xuICAgIGNvbnN0IG1vYmlsZU1lQm94QnRuID0gZG9jdW1lbnQucXVlcnlTZWxlY3RvcihcIi5tb2JpbGVNZUJveC1idXR0b25cIik7XG4gICAgY29uc3QgbW9iaWxlTWVib3hCdG5DbG9zZSA9IGRvY3VtZW50LnF1ZXJ5U2VsZWN0b3IoXCIubW9iaWxlTWVib3gtYnV0dG9uQ2xvc2VcIik7XG4gICAgY29uc3QgbWFpbkhlYWRlciA9IGRvY3VtZW50LnF1ZXJ5U2VsZWN0b3IoXCIjTWFpbkhlYWRlclwiKTtcblxuICAgIC8vIENhbGN1bGF0ZSB0aGUgdmFsdWVzIGluaXRpYWxseS5cbiAgICBwcmVwYXJlRWxlbWVudChtb2JpbGVNZWJveCk7XG4gICAgcHJlcGFyZUVsZW1lbnQobmF2ZHJhd2VyKTtcblxuICAgIC8vIFVwZGF0ZSB0aGUgY2FsY3VsYXRlZCB2YWx1ZXMgb24gcmVzaXplLlxuICAgIHdpbmRvdy5hZGRFdmVudExpc3RlbmVyKFwicmVzaXplXCIsICgpID0+IHtcbiAgICAgICAgcmVxdWVzdEFuaW1hdGlvbkZyYW1lKCgpID0+IHtcbiAgICAgICAgICAgIHByZXBhcmVFbGVtZW50KG1vYmlsZU1lYm94KTtcbiAgICAgICAgICAgIHByZXBhcmVFbGVtZW50KG5hdmRyYXdlcik7XG4gICAgICAgIH0pXG4gICAgfSlcblxuICAgIG1lbnVCdXR0b24uYWRkRXZlbnRMaXN0ZW5lcihcImNsaWNrXCIsICgpID0+IHtcbiAgICAgICAgbWVudUJ1dHRvbi5jbGFzc0xpc3QudG9nZ2xlKFwiaXNUb2dnbGVkXCIpO1xuICAgICAgICBtYWluSGVhZGVyLmNsYXNzTGlzdC50b2dnbGUoXCJoYXNPcGVuTmF2aWdhdGlvblwiKTtcbiAgICAgICAgY29sbGFwc2VFbGVtZW50KG1vYmlsZU1lYm94KTtcbiAgICAgICAgdG9nZ2xlRWxlbWVudChuYXZkcmF3ZXIpO1xuICAgIH0pO1xuXG4gICAgbW9iaWxlTWVCb3hCdG4uYWRkRXZlbnRMaXN0ZW5lcihcImNsaWNrXCIsICgpID0+IHtcbiAgICAgICAgbW9iaWxlTWVCb3hCdG4uY2xhc3NMaXN0LnRvZ2dsZShcImlzVG9nZ2xlZFwiKTtcbiAgICAgICAgbWFpbkhlYWRlci5jbGFzc0xpc3QucmVtb3ZlKFwiaGFzT3Blbk5hdmlnYXRpb25cIik7XG4gICAgICAgIG1lbnVCdXR0b24uY2xhc3NMaXN0LnJlbW92ZShcImlzVG9nZ2xlZFwiKTtcbiAgICAgICAgY29sbGFwc2VFbGVtZW50KG5hdmRyYXdlcilcbiAgICAgICAgdG9nZ2xlRWxlbWVudChtb2JpbGVNZWJveCk7XG4gICAgfSk7XG5cbiAgICBtb2JpbGVNZWJveEJ0bkNsb3NlLmFkZEV2ZW50TGlzdGVuZXIoXCJjbGlja1wiLCAoKSA9PiB7XG4gICAgICAgIGNvbGxhcHNlRWxlbWVudChtb2JpbGVNZWJveCk7XG4gICAgfSk7XG5cbiAgICAvKipcbiAgICAgKiBAcGFyYW0ge0hUTUxFbGVtZW50fSBlbGVtZW50XG4gICAgICovXG4gICAgZnVuY3Rpb24gdG9nZ2xlRWxlbWVudChlbGVtZW50KSB7XG4gICAgICAgIGlmIChlbGVtZW50LnN0eWxlLmhlaWdodCA9PT0gQ09MTEFQU0VEX0hFSUdIVCkge1xuICAgICAgICAgICAgZXhwYW5kRWxlbWVudChlbGVtZW50KTtcbiAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICAgIGNvbGxhcHNlRWxlbWVudChlbGVtZW50KTtcbiAgICAgICAgfVxuICAgIH1cblxuICAgIC8qKlxuICAgICAqIEBwYXJhbSB7SFRNTEVsZW1lbnR9IGVsZW1lbnRcbiAgICAgKi9cbiAgICBmdW5jdGlvbiBjb2xsYXBzZUVsZW1lbnQoZWxlbWVudCkge1xuICAgICAgICBlbGVtZW50LnN0eWxlLmhlaWdodCA9IENPTExBUFNFRF9IRUlHSFQ7XG4gICAgfVxuXG4gICAgLyoqXG4gICAgICpcbiAgICAgKiBAcGFyYW0ge0hUTUxFbGVtZW50fSBlbGVtZW50XG4gICAgICovXG4gICAgZnVuY3Rpb24gZXhwYW5kRWxlbWVudChlbGVtZW50KSB7XG4gICAgICAgIGVsZW1lbnQuc3R5bGUuaGVpZ2h0ID0gZWxlbWVudC5nZXRBdHRyaWJ1dGUoQ0FMQ19IRUlHSFRfQVRUUikgKyBcInB4XCI7XG4gICAgfVxuXG4gICAgLyoqXG4gICAgICogR2V0IHRoZSBjYWxjdWxhdGVkIGhlaWdodCBvZiBhbiBlbGVtZW50IGFuZFxuICAgICAqXG4gICAgICogQHBhcmFtIHtIVE1MRWxlbWVudH0gZWxlbWVudFxuICAgICAqL1xuICAgIGZ1bmN0aW9uIHByZXBhcmVFbGVtZW50KGVsZW1lbnQpIHtcbiAgICAgICAgZWxlbWVudC5jbGFzc0xpc3QuYWRkKElOSVRfQ0xBU1MpO1xuICAgICAgICBlbGVtZW50LnN0eWxlLmhlaWdodCA9IFwiYXV0b1wiO1xuICAgICAgICBjb25zdCBjYWxjZWRIZWlnaHQgPSBlbGVtZW50LmdldEJvdW5kaW5nQ2xpZW50UmVjdCgpLmhlaWdodDtcblxuICAgICAgICAvLyBWaXN1YWwgaGlkZSB0aGUgZWxlbWVudC5cbiAgICAgICAgZWxlbWVudC5zZXRBdHRyaWJ1dGUoQ0FMQ19IRUlHSFRfQVRUUiwgY2FsY2VkSGVpZ2h0LnRvU3RyaW5nKCkpO1xuICAgICAgICBjb2xsYXBzZUVsZW1lbnQoZWxlbWVudCk7XG4gICAgICAgIGVsZW1lbnQuY2xhc3NMaXN0LnJlbW92ZShJTklUX0NMQVNTKTtcbiAgICB9XG59XG4iXSwic291cmNlUm9vdCI6IiJ9\n//# sourceURL=webpack-internal:///./src/js/mobileNavigation.js\n");

/***/ })

/******/ });