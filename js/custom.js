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

/***/ "./node_modules/@vanillaforums/theme-boilerplate/src/js/index.js":
/*!***********************************************************************!*\
  !*** ./node_modules/@vanillaforums/theme-boilerplate/src/js/index.js ***!
  \***********************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";
eval("\n\nvar _mobileNavigation = __webpack_require__(/*! ./mobileNavigation */ \"./node_modules/@vanillaforums/theme-boilerplate/src/js/mobileNavigation.js\");\n\n$(function () {\n  (0, _mobileNavigation.setupMobileNavigation)();\n\n  $(\"select\").wrap('<div class=\"SelectWrapper\"></div>');\n}); /*!\n     * @author Isis (igraziatto) Graziatto <isis.g@vanillaforums.com>\n     * @copyright 2009-2018 Vanilla Forums Inc.\n     * @license GPL-2.0-only\n     *///# sourceURL=[module]\n//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJzb3VyY2VzIjpbIndlYnBhY2s6Ly8vLi9ub2RlX21vZHVsZXMvQHZhbmlsbGFmb3J1bXMvdGhlbWUtYm9pbGVycGxhdGUvc3JjL2pzL2luZGV4LmpzP2UzMWIiXSwibmFtZXMiOlsiJCIsIndyYXAiXSwibWFwcGluZ3MiOiI7O0FBTUE7O0FBRUFBLEVBQUUsWUFBTTtBQUNKOztBQUVBQSxJQUFFLFFBQUYsRUFBWUMsSUFBWixDQUFpQixtQ0FBakI7QUFDSCxDQUpELEUsQ0FSQSIsImZpbGUiOiIuL25vZGVfbW9kdWxlcy9AdmFuaWxsYWZvcnVtcy90aGVtZS1ib2lsZXJwbGF0ZS9zcmMvanMvaW5kZXguanMuanMiLCJzb3VyY2VzQ29udGVudCI6WyIvKiFcbiAqIEBhdXRob3IgSXNpcyAoaWdyYXppYXR0bykgR3JhemlhdHRvIDxpc2lzLmdAdmFuaWxsYWZvcnVtcy5jb20+XG4gKiBAY29weXJpZ2h0IDIwMDktMjAxOCBWYW5pbGxhIEZvcnVtcyBJbmMuXG4gKiBAbGljZW5zZSBHUEwtMi4wLW9ubHlcbiAqL1xuXG5pbXBvcnQgeyBzZXR1cE1vYmlsZU5hdmlnYXRpb24gfSBmcm9tIFwiLi9tb2JpbGVOYXZpZ2F0aW9uXCI7XG5cbiQoKCkgPT4ge1xuICAgIHNldHVwTW9iaWxlTmF2aWdhdGlvbigpO1xuXG4gICAgJChcInNlbGVjdFwiKS53cmFwKCc8ZGl2IGNsYXNzPVwiU2VsZWN0V3JhcHBlclwiPjwvZGl2PicpO1xufSk7XG4iXSwic291cmNlUm9vdCI6IiJ9\n//# sourceURL=webpack-internal:///./node_modules/@vanillaforums/theme-boilerplate/src/js/index.js\n");

/***/ }),

/***/ "./node_modules/@vanillaforums/theme-boilerplate/src/js/mobileNavigation.js":
/*!**********************************************************************************!*\
  !*** ./node_modules/@vanillaforums/theme-boilerplate/src/js/mobileNavigation.js ***!
  \**********************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";
eval("\n\nObject.defineProperty(exports, \"__esModule\", {\n    value: true\n});\nexports.setupMobileNavigation = setupMobileNavigation;\n/*!\n * @author Isis (igraziatto) Graziatto <isis.g@vanillaforums.com>\n * @copyright 2009-2018 Vanilla Forums Inc.\n * @license GPL-2.0-only\n */\n\nfunction setupMobileNavigation() {\n\n    var $menuButton = $(\"#menu-button\"),\n        $navdrawer = $(\"#navdrawer\");\n\n    $menuButton.on(\"click\", function () {\n        $menuButton.toggleClass(\"isToggled\");\n        $navdrawer.toggleClass(\"isOpen\");\n    });\n}//# sourceURL=[module]\n//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJzb3VyY2VzIjpbIndlYnBhY2s6Ly8vLi9ub2RlX21vZHVsZXMvQHZhbmlsbGFmb3J1bXMvdGhlbWUtYm9pbGVycGxhdGUvc3JjL2pzL21vYmlsZU5hdmlnYXRpb24uanM/ZDk1YiJdLCJuYW1lcyI6WyJzZXR1cE1vYmlsZU5hdmlnYXRpb24iLCIkbWVudUJ1dHRvbiIsIiQiLCIkbmF2ZHJhd2VyIiwib24iLCJ0b2dnbGVDbGFzcyJdLCJtYXBwaW5ncyI6Ijs7Ozs7UUFNZ0JBLHFCLEdBQUFBLHFCO0FBTmhCOzs7Ozs7QUFNTyxTQUFTQSxxQkFBVCxHQUFpQzs7QUFFcEMsUUFBSUMsY0FBY0MsRUFBRSxjQUFGLENBQWxCO0FBQUEsUUFDSUMsYUFBYUQsRUFBRSxZQUFGLENBRGpCOztBQUdBRCxnQkFBWUcsRUFBWixDQUFlLE9BQWYsRUFBd0IsWUFBTTtBQUMxQkgsb0JBQVlJLFdBQVosQ0FBd0IsV0FBeEI7QUFDQUYsbUJBQVdFLFdBQVgsQ0FBdUIsUUFBdkI7QUFDSCxLQUhEO0FBSUgiLCJmaWxlIjoiLi9ub2RlX21vZHVsZXMvQHZhbmlsbGFmb3J1bXMvdGhlbWUtYm9pbGVycGxhdGUvc3JjL2pzL21vYmlsZU5hdmlnYXRpb24uanMuanMiLCJzb3VyY2VzQ29udGVudCI6WyIvKiFcbiAqIEBhdXRob3IgSXNpcyAoaWdyYXppYXR0bykgR3JhemlhdHRvIDxpc2lzLmdAdmFuaWxsYWZvcnVtcy5jb20+XG4gKiBAY29weXJpZ2h0IDIwMDktMjAxOCBWYW5pbGxhIEZvcnVtcyBJbmMuXG4gKiBAbGljZW5zZSBHUEwtMi4wLW9ubHlcbiAqL1xuXG5leHBvcnQgZnVuY3Rpb24gc2V0dXBNb2JpbGVOYXZpZ2F0aW9uKCkge1xuXG4gICAgdmFyICRtZW51QnV0dG9uID0gJChcIiNtZW51LWJ1dHRvblwiKSxcbiAgICAgICAgJG5hdmRyYXdlciA9ICQoXCIjbmF2ZHJhd2VyXCIpO1xuXG4gICAgJG1lbnVCdXR0b24ub24oXCJjbGlja1wiLCAoKSA9PiB7XG4gICAgICAgICRtZW51QnV0dG9uLnRvZ2dsZUNsYXNzKFwiaXNUb2dnbGVkXCIpO1xuICAgICAgICAkbmF2ZHJhd2VyLnRvZ2dsZUNsYXNzKFwiaXNPcGVuXCIpO1xuICAgIH0pO1xufVxuIl0sInNvdXJjZVJvb3QiOiIifQ==\n//# sourceURL=webpack-internal:///./node_modules/@vanillaforums/theme-boilerplate/src/js/mobileNavigation.js\n");

/***/ }),

/***/ "./src/js/index.js":
/*!*************************!*\
  !*** ./src/js/index.js ***!
  \*************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";
eval("\n\n__webpack_require__(/*! ../../node_modules/@vanillaforums/theme-boilerplate/src/js/index */ \"./node_modules/@vanillaforums/theme-boilerplate/src/js/index.js\");//# sourceURL=[module]\n//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJzb3VyY2VzIjpbIndlYnBhY2s6Ly8vLi9zcmMvanMvaW5kZXguanM/N2JhNSJdLCJuYW1lcyI6W10sIm1hcHBpbmdzIjoiOztBQU1BIiwiZmlsZSI6Ii4vc3JjL2pzL2luZGV4LmpzLmpzIiwic291cmNlc0NvbnRlbnQiOlsiLypcbiAqIEBhdXRob3IgSXNpcyAoaWdyYXppYXR0bykgR3JhemlhdHRvIDxpc2lzLmdAdmFuaWxsYWZvcnVtcy5jb20+XG4gKiBAY29weXJpZ2h0IDIwMDktMjAxOCBWYW5pbGxhIEZvcnVtcyBJbmMuXG4gKiBAbGljZW5zZSBHUEwtMi4wLW9ubHlcbiAqL1xuXG5pbXBvcnQgXCIuLi8uLi9ub2RlX21vZHVsZXMvQHZhbmlsbGFmb3J1bXMvdGhlbWUtYm9pbGVycGxhdGUvc3JjL2pzL2luZGV4XCI7XG4iXSwic291cmNlUm9vdCI6IiJ9\n//# sourceURL=webpack-internal:///./src/js/index.js\n");

/***/ })

/******/ });