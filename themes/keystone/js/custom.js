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

/***/ "../theme-boilerplate/src/js/index.js":
/*!********************************************!*\
  !*** ../theme-boilerplate/src/js/index.js ***!
  \********************************************/
/*! no exports provided */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony import */ var _mobileNavigation__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./mobileNavigation */ \"../theme-boilerplate/src/js/mobileNavigation.js\");\n/*!\n * @author Isis (igraziatto) Graziatto <isis.g@vanillaforums.com>\n * @copyright 2009-2018 Vanilla Forums Inc.\n * @license GPL-2.0-only\n */\n\n\n\n$(() => {\n    Object(_mobileNavigation__WEBPACK_IMPORTED_MODULE_0__[\"setupMobileNavigation\"])();\n\n    $(\"select\").wrap('<div class=\"SelectWrapper\"></div>');\n});\n//# sourceURL=[module]\n//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJzb3VyY2VzIjpbIndlYnBhY2s6Ly8vLi4vdGhlbWUtYm9pbGVycGxhdGUvc3JjL2pzL2luZGV4LmpzP2VkYjkiXSwibmFtZXMiOltdLCJtYXBwaW5ncyI6IjtBQUFBO0FBQUE7QUFDQTtBQUNBO0FBQ0E7QUFDQTs7QUFFZ0M7O0FBRWhDO0FBQ0E7O0FBRUE7QUFDQSxDQUFDIiwiZmlsZSI6Ii4uL3RoZW1lLWJvaWxlcnBsYXRlL3NyYy9qcy9pbmRleC5qcy5qcyIsInNvdXJjZXNDb250ZW50IjpbIi8qIVxuICogQGF1dGhvciBJc2lzIChpZ3JhemlhdHRvKSBHcmF6aWF0dG8gPGlzaXMuZ0B2YW5pbGxhZm9ydW1zLmNvbT5cbiAqIEBjb3B5cmlnaHQgMjAwOS0yMDE4IFZhbmlsbGEgRm9ydW1zIEluYy5cbiAqIEBsaWNlbnNlIEdQTC0yLjAtb25seVxuICovXG5cbmltcG9ydCB7IHNldHVwTW9iaWxlTmF2aWdhdGlvbiB9IGZyb20gXCIuL21vYmlsZU5hdmlnYXRpb25cIjtcblxuJCgoKSA9PiB7XG4gICAgc2V0dXBNb2JpbGVOYXZpZ2F0aW9uKCk7XG5cbiAgICAkKFwic2VsZWN0XCIpLndyYXAoJzxkaXYgY2xhc3M9XCJTZWxlY3RXcmFwcGVyXCI+PC9kaXY+Jyk7XG59KTtcbiJdLCJzb3VyY2VSb290IjoiIn0=\n//# sourceURL=webpack-internal:///../theme-boilerplate/src/js/index.js\n");

/***/ }),

/***/ "../theme-boilerplate/src/js/mobileNavigation.js":
/*!*******************************************************!*\
  !*** ../theme-boilerplate/src/js/mobileNavigation.js ***!
  \*******************************************************/
/*! exports provided: setupMobileNavigation */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, \"setupMobileNavigation\", function() { return setupMobileNavigation; });\n/*!\n * @author Isis (igraziatto) Graziatto <isis.g@vanillaforums.com>\n * @copyright 2009-2018 Vanilla Forums Inc.\n * @license GPL-2.0-only\n */\n\nconst INIT_CLASS = \"needsInitialization\";\nconst CALC_HEIGHT_ATTR = \"data-height\";\nconst COLLAPSED_HEIGHT = \"0px\";\n\nfunction setupMobileNavigation() {\n\n    const menuButton = document.querySelector(\"#menu-button\");\n    /** @type {HTMLElement} */\n    const navdrawer = document.querySelector(\".js-nav\");\n    /** @type {HTMLElement} */\n    const mobileMebox = document.querySelector(\".js-mobileMebox\");\n    const mobileMeBoxBtn = document.querySelector(\".mobileMeBox-button\");\n    const mobileMeboxBtnClose = document.querySelector(\".mobileMebox-buttonClose\");\n    const mainHeader = document.querySelector(\"#MainHeader\");\n\n    // Calculate the values initially.\n    prepareElement(mobileMebox);\n    prepareElement(navdrawer);\n\n    // Update the calculated values on resize.\n    window.addEventListener(\"resize\", () => {\n        requestAnimationFrame(() => {\n            prepareElement(mobileMebox);\n            prepareElement(navdrawer);\n        })\n    })\n\n    menuButton.addEventListener(\"click\", () => {\n        menuButton.classList.toggle(\"isToggled\");\n        mainHeader.classList.toggle(\"hasOpenNavigation\");\n        collapseElement(mobileMebox);\n        toggleElement(navdrawer);\n    });\n\n    mobileMeBoxBtn && mobileMeBoxBtn.addEventListener(\"click\", () => {\n        mobileMeBoxBtn.classList.toggle(\"isToggled\");\n        mainHeader.classList.remove(\"hasOpenNavigation\");\n        menuButton.classList.remove(\"isToggled\");\n        collapseElement(navdrawer)\n        toggleElement(mobileMebox);\n    });\n\n    mobileMeboxBtnClose && mobileMeboxBtnClose.addEventListener(\"click\", () => {\n        collapseElement(mobileMebox);\n    });\n\n    /**\n     * @param {HTMLElement} element\n     */\n    function toggleElement(element) {\n        if (element.style.height === COLLAPSED_HEIGHT) {\n            expandElement(element);\n        } else {\n            collapseElement(element);\n        }\n    }\n\n    /**\n     * @param {HTMLElement} element\n     */\n    function collapseElement(element) {\n        element.style.height = COLLAPSED_HEIGHT;\n    }\n\n    /**\n     *\n     * @param {HTMLElement} element\n     */\n    function expandElement(element) {\n        element.style.height = element.getAttribute(CALC_HEIGHT_ATTR) + \"px\";\n    }\n\n    /**\n     * Get the calculated height of an element and\n     *\n     * @param {HTMLElement} element\n     */\n    function prepareElement(element) {\n        element.classList.add(INIT_CLASS);\n        element.style.height = \"auto\";\n        const calcedHeight = element.getBoundingClientRect().height;\n\n        // Visual hide the element.\n        element.setAttribute(CALC_HEIGHT_ATTR, calcedHeight.toString());\n        collapseElement(element);\n        element.classList.remove(INIT_CLASS);\n    }\n}\n//# sourceURL=[module]\n//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJzb3VyY2VzIjpbIndlYnBhY2s6Ly8vLi4vdGhlbWUtYm9pbGVycGxhdGUvc3JjL2pzL21vYmlsZU5hdmlnYXRpb24uanM/MDk0NSJdLCJuYW1lcyI6W10sIm1hcHBpbmdzIjoiO0FBQUE7QUFBQTtBQUNBO0FBQ0E7QUFDQTtBQUNBOztBQUVBO0FBQ0E7QUFDQTs7QUFFQTs7QUFFQTtBQUNBLGVBQWUsWUFBWTtBQUMzQjtBQUNBLGVBQWUsWUFBWTtBQUMzQjtBQUNBO0FBQ0E7QUFDQTs7QUFFQTtBQUNBO0FBQ0E7O0FBRUE7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBLFNBQVM7QUFDVCxLQUFLOztBQUVMO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQSxLQUFLOztBQUVMO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBLEtBQUs7O0FBRUw7QUFDQTtBQUNBLEtBQUs7O0FBRUw7QUFDQSxlQUFlLFlBQVk7QUFDM0I7QUFDQTtBQUNBO0FBQ0E7QUFDQSxTQUFTO0FBQ1Q7QUFDQTtBQUNBOztBQUVBO0FBQ0EsZUFBZSxZQUFZO0FBQzNCO0FBQ0E7QUFDQTtBQUNBOztBQUVBO0FBQ0E7QUFDQSxlQUFlLFlBQVk7QUFDM0I7QUFDQTtBQUNBO0FBQ0E7O0FBRUE7QUFDQTtBQUNBO0FBQ0EsZUFBZSxZQUFZO0FBQzNCO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7O0FBRUE7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBIiwiZmlsZSI6Ii4uL3RoZW1lLWJvaWxlcnBsYXRlL3NyYy9qcy9tb2JpbGVOYXZpZ2F0aW9uLmpzLmpzIiwic291cmNlc0NvbnRlbnQiOlsiLyohXG4gKiBAYXV0aG9yIElzaXMgKGlncmF6aWF0dG8pIEdyYXppYXR0byA8aXNpcy5nQHZhbmlsbGFmb3J1bXMuY29tPlxuICogQGNvcHlyaWdodCAyMDA5LTIwMTggVmFuaWxsYSBGb3J1bXMgSW5jLlxuICogQGxpY2Vuc2UgR1BMLTIuMC1vbmx5XG4gKi9cblxuY29uc3QgSU5JVF9DTEFTUyA9IFwibmVlZHNJbml0aWFsaXphdGlvblwiO1xuY29uc3QgQ0FMQ19IRUlHSFRfQVRUUiA9IFwiZGF0YS1oZWlnaHRcIjtcbmNvbnN0IENPTExBUFNFRF9IRUlHSFQgPSBcIjBweFwiO1xuXG5leHBvcnQgZnVuY3Rpb24gc2V0dXBNb2JpbGVOYXZpZ2F0aW9uKCkge1xuXG4gICAgY29uc3QgbWVudUJ1dHRvbiA9IGRvY3VtZW50LnF1ZXJ5U2VsZWN0b3IoXCIjbWVudS1idXR0b25cIik7XG4gICAgLyoqIEB0eXBlIHtIVE1MRWxlbWVudH0gKi9cbiAgICBjb25zdCBuYXZkcmF3ZXIgPSBkb2N1bWVudC5xdWVyeVNlbGVjdG9yKFwiLmpzLW5hdlwiKTtcbiAgICAvKiogQHR5cGUge0hUTUxFbGVtZW50fSAqL1xuICAgIGNvbnN0IG1vYmlsZU1lYm94ID0gZG9jdW1lbnQucXVlcnlTZWxlY3RvcihcIi5qcy1tb2JpbGVNZWJveFwiKTtcbiAgICBjb25zdCBtb2JpbGVNZUJveEJ0biA9IGRvY3VtZW50LnF1ZXJ5U2VsZWN0b3IoXCIubW9iaWxlTWVCb3gtYnV0dG9uXCIpO1xuICAgIGNvbnN0IG1vYmlsZU1lYm94QnRuQ2xvc2UgPSBkb2N1bWVudC5xdWVyeVNlbGVjdG9yKFwiLm1vYmlsZU1lYm94LWJ1dHRvbkNsb3NlXCIpO1xuICAgIGNvbnN0IG1haW5IZWFkZXIgPSBkb2N1bWVudC5xdWVyeVNlbGVjdG9yKFwiI01haW5IZWFkZXJcIik7XG5cbiAgICAvLyBDYWxjdWxhdGUgdGhlIHZhbHVlcyBpbml0aWFsbHkuXG4gICAgcHJlcGFyZUVsZW1lbnQobW9iaWxlTWVib3gpO1xuICAgIHByZXBhcmVFbGVtZW50KG5hdmRyYXdlcik7XG5cbiAgICAvLyBVcGRhdGUgdGhlIGNhbGN1bGF0ZWQgdmFsdWVzIG9uIHJlc2l6ZS5cbiAgICB3aW5kb3cuYWRkRXZlbnRMaXN0ZW5lcihcInJlc2l6ZVwiLCAoKSA9PiB7XG4gICAgICAgIHJlcXVlc3RBbmltYXRpb25GcmFtZSgoKSA9PiB7XG4gICAgICAgICAgICBwcmVwYXJlRWxlbWVudChtb2JpbGVNZWJveCk7XG4gICAgICAgICAgICBwcmVwYXJlRWxlbWVudChuYXZkcmF3ZXIpO1xuICAgICAgICB9KVxuICAgIH0pXG5cbiAgICBtZW51QnV0dG9uLmFkZEV2ZW50TGlzdGVuZXIoXCJjbGlja1wiLCAoKSA9PiB7XG4gICAgICAgIG1lbnVCdXR0b24uY2xhc3NMaXN0LnRvZ2dsZShcImlzVG9nZ2xlZFwiKTtcbiAgICAgICAgbWFpbkhlYWRlci5jbGFzc0xpc3QudG9nZ2xlKFwiaGFzT3Blbk5hdmlnYXRpb25cIik7XG4gICAgICAgIGNvbGxhcHNlRWxlbWVudChtb2JpbGVNZWJveCk7XG4gICAgICAgIHRvZ2dsZUVsZW1lbnQobmF2ZHJhd2VyKTtcbiAgICB9KTtcblxuICAgIG1vYmlsZU1lQm94QnRuICYmIG1vYmlsZU1lQm94QnRuLmFkZEV2ZW50TGlzdGVuZXIoXCJjbGlja1wiLCAoKSA9PiB7XG4gICAgICAgIG1vYmlsZU1lQm94QnRuLmNsYXNzTGlzdC50b2dnbGUoXCJpc1RvZ2dsZWRcIik7XG4gICAgICAgIG1haW5IZWFkZXIuY2xhc3NMaXN0LnJlbW92ZShcImhhc09wZW5OYXZpZ2F0aW9uXCIpO1xuICAgICAgICBtZW51QnV0dG9uLmNsYXNzTGlzdC5yZW1vdmUoXCJpc1RvZ2dsZWRcIik7XG4gICAgICAgIGNvbGxhcHNlRWxlbWVudChuYXZkcmF3ZXIpXG4gICAgICAgIHRvZ2dsZUVsZW1lbnQobW9iaWxlTWVib3gpO1xuICAgIH0pO1xuXG4gICAgbW9iaWxlTWVib3hCdG5DbG9zZSAmJiBtb2JpbGVNZWJveEJ0bkNsb3NlLmFkZEV2ZW50TGlzdGVuZXIoXCJjbGlja1wiLCAoKSA9PiB7XG4gICAgICAgIGNvbGxhcHNlRWxlbWVudChtb2JpbGVNZWJveCk7XG4gICAgfSk7XG5cbiAgICAvKipcbiAgICAgKiBAcGFyYW0ge0hUTUxFbGVtZW50fSBlbGVtZW50XG4gICAgICovXG4gICAgZnVuY3Rpb24gdG9nZ2xlRWxlbWVudChlbGVtZW50KSB7XG4gICAgICAgIGlmIChlbGVtZW50LnN0eWxlLmhlaWdodCA9PT0gQ09MTEFQU0VEX0hFSUdIVCkge1xuICAgICAgICAgICAgZXhwYW5kRWxlbWVudChlbGVtZW50KTtcbiAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICAgIGNvbGxhcHNlRWxlbWVudChlbGVtZW50KTtcbiAgICAgICAgfVxuICAgIH1cblxuICAgIC8qKlxuICAgICAqIEBwYXJhbSB7SFRNTEVsZW1lbnR9IGVsZW1lbnRcbiAgICAgKi9cbiAgICBmdW5jdGlvbiBjb2xsYXBzZUVsZW1lbnQoZWxlbWVudCkge1xuICAgICAgICBlbGVtZW50LnN0eWxlLmhlaWdodCA9IENPTExBUFNFRF9IRUlHSFQ7XG4gICAgfVxuXG4gICAgLyoqXG4gICAgICpcbiAgICAgKiBAcGFyYW0ge0hUTUxFbGVtZW50fSBlbGVtZW50XG4gICAgICovXG4gICAgZnVuY3Rpb24gZXhwYW5kRWxlbWVudChlbGVtZW50KSB7XG4gICAgICAgIGVsZW1lbnQuc3R5bGUuaGVpZ2h0ID0gZWxlbWVudC5nZXRBdHRyaWJ1dGUoQ0FMQ19IRUlHSFRfQVRUUikgKyBcInB4XCI7XG4gICAgfVxuXG4gICAgLyoqXG4gICAgICogR2V0IHRoZSBjYWxjdWxhdGVkIGhlaWdodCBvZiBhbiBlbGVtZW50IGFuZFxuICAgICAqXG4gICAgICogQHBhcmFtIHtIVE1MRWxlbWVudH0gZWxlbWVudFxuICAgICAqL1xuICAgIGZ1bmN0aW9uIHByZXBhcmVFbGVtZW50KGVsZW1lbnQpIHtcbiAgICAgICAgZWxlbWVudC5jbGFzc0xpc3QuYWRkKElOSVRfQ0xBU1MpO1xuICAgICAgICBlbGVtZW50LnN0eWxlLmhlaWdodCA9IFwiYXV0b1wiO1xuICAgICAgICBjb25zdCBjYWxjZWRIZWlnaHQgPSBlbGVtZW50LmdldEJvdW5kaW5nQ2xpZW50UmVjdCgpLmhlaWdodDtcblxuICAgICAgICAvLyBWaXN1YWwgaGlkZSB0aGUgZWxlbWVudC5cbiAgICAgICAgZWxlbWVudC5zZXRBdHRyaWJ1dGUoQ0FMQ19IRUlHSFRfQVRUUiwgY2FsY2VkSGVpZ2h0LnRvU3RyaW5nKCkpO1xuICAgICAgICBjb2xsYXBzZUVsZW1lbnQoZWxlbWVudCk7XG4gICAgICAgIGVsZW1lbnQuY2xhc3NMaXN0LnJlbW92ZShJTklUX0NMQVNTKTtcbiAgICB9XG59XG4iXSwic291cmNlUm9vdCI6IiJ9\n//# sourceURL=webpack-internal:///../theme-boilerplate/src/js/mobileNavigation.js\n");

/***/ }),

/***/ "./src/js/index.js":
/*!*************************!*\
  !*** ./src/js/index.js ***!
  \*************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";
eval("\n\n__webpack_require__(/*! ../../../theme-boilerplate/src/js/index */ \"../theme-boilerplate/src/js/index.js\");//# sourceURL=[module]\n//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJzb3VyY2VzIjpbIndlYnBhY2s6Ly8vLi9zcmMvanMvaW5kZXguanM/N2JhNSJdLCJuYW1lcyI6W10sIm1hcHBpbmdzIjoiOztBQU1BIiwiZmlsZSI6Ii4vc3JjL2pzL2luZGV4LmpzLmpzIiwic291cmNlc0NvbnRlbnQiOlsiLypcbiAqIEBhdXRob3IgSXNpcyAoaWdyYXppYXR0bykgR3JhemlhdHRvIDxpc2lzLmdAdmFuaWxsYWZvcnVtcy5jb20+XG4gKiBAY29weXJpZ2h0IDIwMDktMjAxOCBWYW5pbGxhIEZvcnVtcyBJbmMuXG4gKiBAbGljZW5zZSBHUEwtMi4wLW9ubHlcbiAqL1xuXG5pbXBvcnQgXCIuLi8uLi8uLi90aGVtZS1ib2lsZXJwbGF0ZS9zcmMvanMvaW5kZXhcIjtcbiJdLCJzb3VyY2VSb290IjoiIn0=\n//# sourceURL=webpack-internal:///./src/js/index.js\n");

/***/ })

/******/ });