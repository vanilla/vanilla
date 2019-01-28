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
eval("\n\nObject.defineProperty(exports, \"__esModule\", {\n    value: true\n});\nexports.setupMobileNavigation = setupMobileNavigation;\n/*!\n * @author Isis (igraziatto) Graziatto <isis.g@vanillaforums.com>\n * @copyright 2009-2018 Vanilla Forums Inc.\n * @license GPL-2.0-only\n */\n\nfunction setupMobileNavigation() {\n\n    var $menuButton = $(\"#menu-button\"),\n        $navdrawer = $(\"#navdrawer\"),\n        $mobileMebox = $(\".js-mobileMebox\"),\n        $mobileMeBoxBtn = $(\".mobileMeBox-button\"),\n        $mobileMeboxBtnClose = $(\".mobileMebox-buttonClose\"),\n        $mainHeader = $(\"#MainHeader\");\n\n    $menuButton.on(\"click\", function () {\n        $menuButton.toggleClass(\"isToggled\");\n        $navdrawer.toggleClass(\"isOpen\");\n        $mainHeader.toggleClass(\"hasOpenNavigation\");\n        $mobileMebox.removeClass(\"isOpen\");\n    });\n\n    $mobileMeBoxBtn.on(\"click\", function () {\n        $mobileMeBoxBtn.toggleClass(\"isToggled\");\n        $mobileMebox.toggleClass(\"isOpen\");\n        $mainHeader.removeClass(\"hasOpenNavigation\");\n        $menuButton.removeClass(\"isToggled\");\n        $navdrawer.removeClass(\"isOpen\");\n    });\n\n    $mobileMeboxBtnClose.on(\"click\", function () {\n        $mobileMebox.removeClass(\"isOpen\");\n    });\n}//# sourceURL=[module]\n//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJzb3VyY2VzIjpbIndlYnBhY2s6Ly8vLi9zcmMvanMvbW9iaWxlTmF2aWdhdGlvbi5qcz9mN2JlIl0sIm5hbWVzIjpbInNldHVwTW9iaWxlTmF2aWdhdGlvbiIsIiRtZW51QnV0dG9uIiwiJCIsIiRuYXZkcmF3ZXIiLCIkbW9iaWxlTWVib3giLCIkbW9iaWxlTWVCb3hCdG4iLCIkbW9iaWxlTWVib3hCdG5DbG9zZSIsIiRtYWluSGVhZGVyIiwib24iLCJ0b2dnbGVDbGFzcyIsInJlbW92ZUNsYXNzIl0sIm1hcHBpbmdzIjoiOzs7OztRQU1nQkEscUIsR0FBQUEscUI7QUFOaEI7Ozs7OztBQU1PLFNBQVNBLHFCQUFULEdBQWlDOztBQUVwQyxRQUFJQyxjQUFjQyxFQUFFLGNBQUYsQ0FBbEI7QUFBQSxRQUNJQyxhQUFhRCxFQUFFLFlBQUYsQ0FEakI7QUFBQSxRQUVJRSxlQUFlRixFQUFFLGlCQUFGLENBRm5CO0FBQUEsUUFHSUcsa0JBQWtCSCxFQUFFLHFCQUFGLENBSHRCO0FBQUEsUUFJSUksdUJBQXVCSixFQUFFLDBCQUFGLENBSjNCO0FBQUEsUUFLSUssY0FBY0wsRUFBRSxhQUFGLENBTGxCOztBQU9BRCxnQkFBWU8sRUFBWixDQUFlLE9BQWYsRUFBd0IsWUFBTTtBQUMxQlAsb0JBQVlRLFdBQVosQ0FBd0IsV0FBeEI7QUFDQU4sbUJBQVdNLFdBQVgsQ0FBdUIsUUFBdkI7QUFDQUYsb0JBQVlFLFdBQVosQ0FBd0IsbUJBQXhCO0FBQ0FMLHFCQUFhTSxXQUFiLENBQXlCLFFBQXpCO0FBQ0gsS0FMRDs7QUFPQUwsb0JBQWdCRyxFQUFoQixDQUFtQixPQUFuQixFQUE0QixZQUFNO0FBQzlCSCx3QkFBZ0JJLFdBQWhCLENBQTRCLFdBQTVCO0FBQ0FMLHFCQUFhSyxXQUFiLENBQXlCLFFBQXpCO0FBQ0FGLG9CQUFZRyxXQUFaLENBQXdCLG1CQUF4QjtBQUNBVCxvQkFBWVMsV0FBWixDQUF3QixXQUF4QjtBQUNBUCxtQkFBV08sV0FBWCxDQUF1QixRQUF2QjtBQUNILEtBTkQ7O0FBUUFKLHlCQUFxQkUsRUFBckIsQ0FBd0IsT0FBeEIsRUFBaUMsWUFBTTtBQUNuQ0oscUJBQWFNLFdBQWIsQ0FBeUIsUUFBekI7QUFDSCxLQUZEO0FBR0giLCJmaWxlIjoiLi9zcmMvanMvbW9iaWxlTmF2aWdhdGlvbi5qcy5qcyIsInNvdXJjZXNDb250ZW50IjpbIi8qIVxuICogQGF1dGhvciBJc2lzIChpZ3JhemlhdHRvKSBHcmF6aWF0dG8gPGlzaXMuZ0B2YW5pbGxhZm9ydW1zLmNvbT5cbiAqIEBjb3B5cmlnaHQgMjAwOS0yMDE4IFZhbmlsbGEgRm9ydW1zIEluYy5cbiAqIEBsaWNlbnNlIEdQTC0yLjAtb25seVxuICovXG5cbmV4cG9ydCBmdW5jdGlvbiBzZXR1cE1vYmlsZU5hdmlnYXRpb24oKSB7XG5cbiAgICB2YXIgJG1lbnVCdXR0b24gPSAkKFwiI21lbnUtYnV0dG9uXCIpLFxuICAgICAgICAkbmF2ZHJhd2VyID0gJChcIiNuYXZkcmF3ZXJcIiksXG4gICAgICAgICRtb2JpbGVNZWJveCA9ICQoXCIuanMtbW9iaWxlTWVib3hcIiksXG4gICAgICAgICRtb2JpbGVNZUJveEJ0biA9ICQoXCIubW9iaWxlTWVCb3gtYnV0dG9uXCIpLFxuICAgICAgICAkbW9iaWxlTWVib3hCdG5DbG9zZSA9ICQoXCIubW9iaWxlTWVib3gtYnV0dG9uQ2xvc2VcIiksXG4gICAgICAgICRtYWluSGVhZGVyID0gJChcIiNNYWluSGVhZGVyXCIpO1xuXG4gICAgJG1lbnVCdXR0b24ub24oXCJjbGlja1wiLCAoKSA9PiB7XG4gICAgICAgICRtZW51QnV0dG9uLnRvZ2dsZUNsYXNzKFwiaXNUb2dnbGVkXCIpO1xuICAgICAgICAkbmF2ZHJhd2VyLnRvZ2dsZUNsYXNzKFwiaXNPcGVuXCIpO1xuICAgICAgICAkbWFpbkhlYWRlci50b2dnbGVDbGFzcyhcImhhc09wZW5OYXZpZ2F0aW9uXCIpO1xuICAgICAgICAkbW9iaWxlTWVib3gucmVtb3ZlQ2xhc3MoXCJpc09wZW5cIik7XG4gICAgfSk7XG5cbiAgICAkbW9iaWxlTWVCb3hCdG4ub24oXCJjbGlja1wiLCAoKSA9PiB7XG4gICAgICAgICRtb2JpbGVNZUJveEJ0bi50b2dnbGVDbGFzcyhcImlzVG9nZ2xlZFwiKTtcbiAgICAgICAgJG1vYmlsZU1lYm94LnRvZ2dsZUNsYXNzKFwiaXNPcGVuXCIpO1xuICAgICAgICAkbWFpbkhlYWRlci5yZW1vdmVDbGFzcyhcImhhc09wZW5OYXZpZ2F0aW9uXCIpO1xuICAgICAgICAkbWVudUJ1dHRvbi5yZW1vdmVDbGFzcyhcImlzVG9nZ2xlZFwiKTtcbiAgICAgICAgJG5hdmRyYXdlci5yZW1vdmVDbGFzcyhcImlzT3BlblwiKTtcbiAgICB9KTtcblxuICAgICRtb2JpbGVNZWJveEJ0bkNsb3NlLm9uKFwiY2xpY2tcIiwgKCkgPT4ge1xuICAgICAgICAkbW9iaWxlTWVib3gucmVtb3ZlQ2xhc3MoXCJpc09wZW5cIik7XG4gICAgfSk7XG59XG4iXSwic291cmNlUm9vdCI6IiJ9\n//# sourceURL=webpack-internal:///./src/js/mobileNavigation.js\n");

/***/ })

/******/ });