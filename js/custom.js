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
/*! no exports provided */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony import */ var _mobileNavigation__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./mobileNavigation */ \"./node_modules/@vanillaforums/theme-boilerplate/src/js/mobileNavigation.js\");\n/* harmony import */ var _overrides__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./overrides */ \"./node_modules/@vanillaforums/theme-boilerplate/src/js/overrides.js\");\n/*!\n * @author Isis (igraziatto) Graziatto <isis.g@vanillaforums.com>\n * @copyright 2009-2018 Vanilla Forums Inc.\n * @license GPL-2.0-only\n */\n\n\n\n\n$(() => {\n    Object(_mobileNavigation__WEBPACK_IMPORTED_MODULE_0__[\"setupMobileNavigation\"])();\n    Object(_overrides__WEBPACK_IMPORTED_MODULE_1__[\"fixToggleFlyoutBehaviour\"])();\n\n    $(\"select\").wrap('<div class=\"SelectWrapper\"></div>');\n});\n//# sourceURL=[module]\n//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJzb3VyY2VzIjpbIndlYnBhY2s6Ly8vLi9ub2RlX21vZHVsZXMvQHZhbmlsbGFmb3J1bXMvdGhlbWUtYm9pbGVycGxhdGUvc3JjL2pzL2luZGV4LmpzP2UzMWIiXSwibmFtZXMiOltdLCJtYXBwaW5ncyI6Ijs7QUFBQTtBQUFBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7O0FBRWdDO0FBQ0c7O0FBRW5DO0FBQ0E7QUFDQTs7QUFFQTtBQUNBLENBQUMiLCJmaWxlIjoiLi9ub2RlX21vZHVsZXMvQHZhbmlsbGFmb3J1bXMvdGhlbWUtYm9pbGVycGxhdGUvc3JjL2pzL2luZGV4LmpzLmpzIiwic291cmNlc0NvbnRlbnQiOlsiLyohXG4gKiBAYXV0aG9yIElzaXMgKGlncmF6aWF0dG8pIEdyYXppYXR0byA8aXNpcy5nQHZhbmlsbGFmb3J1bXMuY29tPlxuICogQGNvcHlyaWdodCAyMDA5LTIwMTggVmFuaWxsYSBGb3J1bXMgSW5jLlxuICogQGxpY2Vuc2UgR1BMLTIuMC1vbmx5XG4gKi9cblxuaW1wb3J0IHsgc2V0dXBNb2JpbGVOYXZpZ2F0aW9uIH0gZnJvbSBcIi4vbW9iaWxlTmF2aWdhdGlvblwiO1xuaW1wb3J0IHsgZml4VG9nZ2xlRmx5b3V0QmVoYXZpb3VyIH0gZnJvbSBcIi4vb3ZlcnJpZGVzXCI7XG5cbiQoKCkgPT4ge1xuICAgIHNldHVwTW9iaWxlTmF2aWdhdGlvbigpO1xuICAgIGZpeFRvZ2dsZUZseW91dEJlaGF2aW91cigpO1xuXG4gICAgJChcInNlbGVjdFwiKS53cmFwKCc8ZGl2IGNsYXNzPVwiU2VsZWN0V3JhcHBlclwiPjwvZGl2PicpO1xufSk7XG4iXSwic291cmNlUm9vdCI6IiJ9\n//# sourceURL=webpack-internal:///./node_modules/@vanillaforums/theme-boilerplate/src/js/index.js\n");

/***/ }),

/***/ "./node_modules/@vanillaforums/theme-boilerplate/src/js/mobileNavigation.js":
/*!**********************************************************************************!*\
  !*** ./node_modules/@vanillaforums/theme-boilerplate/src/js/mobileNavigation.js ***!
  \**********************************************************************************/
/*! exports provided: setupMobileNavigation */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, \"setupMobileNavigation\", function() { return setupMobileNavigation; });\n/*!\n * @author Isis (igraziatto) Graziatto <isis.g@vanillaforums.com>\n * @copyright 2009-2018 Vanilla Forums Inc.\n * @license GPL-2.0-only\n */\n\nfunction setupMobileNavigation() {\n\n    var $menuButton = $(\"#menu-button\"),\n        $navdrawer = $(\"#navdrawer\");\n\n    $menuButton.on(\"click\", () => {\n        $menuButton.toggleClass(\"isToggled\");\n        $navdrawer.toggleClass(\"isOpen\");\n    });\n}\n//# sourceURL=[module]\n//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJzb3VyY2VzIjpbIndlYnBhY2s6Ly8vLi9ub2RlX21vZHVsZXMvQHZhbmlsbGFmb3J1bXMvdGhlbWUtYm9pbGVycGxhdGUvc3JjL2pzL21vYmlsZU5hdmlnYXRpb24uanM/ZDk1YiJdLCJuYW1lcyI6W10sIm1hcHBpbmdzIjoiO0FBQUE7QUFBQTtBQUNBO0FBQ0E7QUFDQTtBQUNBOztBQUVBOztBQUVBO0FBQ0E7O0FBRUE7QUFDQTtBQUNBO0FBQ0EsS0FBSztBQUNMIiwiZmlsZSI6Ii4vbm9kZV9tb2R1bGVzL0B2YW5pbGxhZm9ydW1zL3RoZW1lLWJvaWxlcnBsYXRlL3NyYy9qcy9tb2JpbGVOYXZpZ2F0aW9uLmpzLmpzIiwic291cmNlc0NvbnRlbnQiOlsiLyohXG4gKiBAYXV0aG9yIElzaXMgKGlncmF6aWF0dG8pIEdyYXppYXR0byA8aXNpcy5nQHZhbmlsbGFmb3J1bXMuY29tPlxuICogQGNvcHlyaWdodCAyMDA5LTIwMTggVmFuaWxsYSBGb3J1bXMgSW5jLlxuICogQGxpY2Vuc2UgR1BMLTIuMC1vbmx5XG4gKi9cblxuZXhwb3J0IGZ1bmN0aW9uIHNldHVwTW9iaWxlTmF2aWdhdGlvbigpIHtcblxuICAgIHZhciAkbWVudUJ1dHRvbiA9ICQoXCIjbWVudS1idXR0b25cIiksXG4gICAgICAgICRuYXZkcmF3ZXIgPSAkKFwiI25hdmRyYXdlclwiKTtcblxuICAgICRtZW51QnV0dG9uLm9uKFwiY2xpY2tcIiwgKCkgPT4ge1xuICAgICAgICAkbWVudUJ1dHRvbi50b2dnbGVDbGFzcyhcImlzVG9nZ2xlZFwiKTtcbiAgICAgICAgJG5hdmRyYXdlci50b2dnbGVDbGFzcyhcImlzT3BlblwiKTtcbiAgICB9KTtcbn1cbiJdLCJzb3VyY2VSb290IjoiIn0=\n//# sourceURL=webpack-internal:///./node_modules/@vanillaforums/theme-boilerplate/src/js/mobileNavigation.js\n");

/***/ }),

/***/ "./node_modules/@vanillaforums/theme-boilerplate/src/js/overrides.js":
/*!***************************************************************************!*\
  !*** ./node_modules/@vanillaforums/theme-boilerplate/src/js/overrides.js ***!
  \***************************************************************************/
/*! exports provided: fixToggleFlyoutBehaviour */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, \"fixToggleFlyoutBehaviour\", function() { return fixToggleFlyoutBehaviour; });\n/* harmony import */ var _utility__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./utility */ \"./node_modules/@vanillaforums/theme-boilerplate/src/js/utility.js\");\n/*!\n * @author Isis (igraziatto) Graziatto <isis.g@vanillaforums.com>\n * @copyright 2009-2018 Vanilla Forums Inc.\n * @license GPL-2.0-only\n */\n\n\n\n/**\n * Resets this listener\n * https://github.com/vanilla/vanilla/blob/f751e382da325e05784ba918016b1af2902f3c3a/js/global.js#L790\n * in order to work visibility:hidden instead of display:none\n *\n * The main js file should not rely on certain CSS styles!!!\n */\nfunction fixToggleFlyoutBehaviour() {\n    $(document).undelegate(\".ToggleFlyout\", \"click\");\n    var lastOpen = null;\n\n    $(document).delegate(\".ToggleFlyout\", \"click\", function(e) {\n        var $toggleFlyout = $(this);\n        var $flyout = $(\".Flyout\", this);\n        var isHandle = false;\n\n        if ($(e.target).closest(\".Flyout\").length === 0) {\n            e.stopPropagation();\n            isHandle = true;\n        } else if (\n            $(e.target).hasClass(\"Hijack\") ||\n            $(e.target).closest(\"a\").hasClass(\"Hijack\")\n        ) {\n            return;\n        }\n        e.stopPropagation();\n\n        // Dynamically fill the flyout.\n        var rel = $(this).attr(\"rel\");\n        if (rel) {\n            $(this).attr(\"rel\", \"\");\n            $flyout.html('<div class=\"InProgress\" style=\"height: 30px\"></div>');\n\n            $.ajax({\n                url: gdn.url(rel),\n                data: { DeliveryType: \"VIEW\" },\n                success: function(data) {\n                    $flyout.html(data);\n                },\n                error: function(xhr) {\n                    $flyout.html(\"\");\n                    gdn.informError(xhr, true);\n                }\n            });\n        }\n\n        if ($flyout.css(\"visibility\") === \"hidden\") {\n            if (lastOpen !== null) {\n                $(\".Flyout\", lastOpen).hide();\n                $(lastOpen)\n                    .removeClass(\"Open\")\n                    .closest(\".Item\")\n                    .removeClass(\"Open\");\n                $toggleFlyout.setFlyoutAttributes();\n            }\n\n            $(this).addClass(\"Open\").closest(\".Item\").addClass(\"Open\");\n            $flyout.show();\n            Object(_utility__WEBPACK_IMPORTED_MODULE_0__[\"disableScroll\"])();\n            lastOpen = this;\n            $toggleFlyout.setFlyoutAttributes();\n        } else {\n            $flyout.hide();\n            $(this).removeClass(\"Open\").closest(\".Item\").removeClass(\"Open\");\n            Object(_utility__WEBPACK_IMPORTED_MODULE_0__[\"enableScroll\"])();\n            $toggleFlyout.setFlyoutAttributes();\n        }\n\n        if (isHandle) return false;\n    });\n\n    // Close ToggleFlyout menu even if their links are hijacked\n    $(document).delegate(\".ToggleFlyout a\", \"mouseup\", function() {\n        if ($(this).hasClass(\"FlyoutButton\"))\n            return;\n\n        $(\".ToggleFlyout\").removeClass(\"Open\").closest(\".Item\").removeClass(\"Open\");\n        $(\".Flyout\").hide();\n        $(this).closest(\".ToggleFlyout\").setFlyoutAttributes();\n    });\n\n    $(document).delegate(document, \"click\", function(e) {\n        if (lastOpen) {\n            $(\".Flyout\", lastOpen).hide();\n            $(lastOpen)\n                .removeClass(\"Open\")\n                .closest(\".Item\")\n                .removeClass(\"Open\");\n        }\n        $(\".ButtonGroup\").removeClass(\"Open\");\n        Object(_utility__WEBPACK_IMPORTED_MODULE_0__[\"enableScroll\"])();\n    });\n\n    $(\".Button.Primary.Handle\").on(\"click\", event => {\n        Object(_utility__WEBPACK_IMPORTED_MODULE_0__[\"toggleScroll\"])();\n    });\n\n    $(\".Options .Flyout\").on(\"click\", () => {\n        Object(_utility__WEBPACK_IMPORTED_MODULE_0__[\"enableScroll\"])();\n    });\n}\n//# sourceURL=[module]\n//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJzb3VyY2VzIjpbIndlYnBhY2s6Ly8vLi9ub2RlX21vZHVsZXMvQHZhbmlsbGFmb3J1bXMvdGhlbWUtYm9pbGVycGxhdGUvc3JjL2pzL292ZXJyaWRlcy5qcz9jNWI3Il0sIm5hbWVzIjpbXSwibWFwcGluZ3MiOiI7O0FBQUE7QUFBQTtBQUNBO0FBQ0E7QUFDQTtBQUNBOztBQUVrRDs7QUFFbEQ7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7O0FBRUE7QUFDQTtBQUNBO0FBQ0E7O0FBRUE7QUFDQTtBQUNBO0FBQ0EsU0FBUztBQUNUO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTs7QUFFQTtBQUNBO0FBQ0E7QUFDQTtBQUNBOztBQUVBO0FBQ0E7QUFDQSx1QkFBdUIsdUJBQXVCO0FBQzlDO0FBQ0E7QUFDQSxpQkFBaUI7QUFDakI7QUFDQTtBQUNBO0FBQ0E7QUFDQSxhQUFhO0FBQ2I7O0FBRUE7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBOztBQUVBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQSxTQUFTO0FBQ1Q7QUFDQTtBQUNBO0FBQ0E7QUFDQTs7QUFFQTtBQUNBLEtBQUs7O0FBRUw7QUFDQTtBQUNBO0FBQ0E7O0FBRUE7QUFDQTtBQUNBO0FBQ0EsS0FBSzs7QUFFTDtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBLEtBQUs7O0FBRUw7QUFDQTtBQUNBLEtBQUs7O0FBRUw7QUFDQTtBQUNBLEtBQUs7QUFDTCIsImZpbGUiOiIuL25vZGVfbW9kdWxlcy9AdmFuaWxsYWZvcnVtcy90aGVtZS1ib2lsZXJwbGF0ZS9zcmMvanMvb3ZlcnJpZGVzLmpzLmpzIiwic291cmNlc0NvbnRlbnQiOlsiLyohXG4gKiBAYXV0aG9yIElzaXMgKGlncmF6aWF0dG8pIEdyYXppYXR0byA8aXNpcy5nQHZhbmlsbGFmb3J1bXMuY29tPlxuICogQGNvcHlyaWdodCAyMDA5LTIwMTggVmFuaWxsYSBGb3J1bXMgSW5jLlxuICogQGxpY2Vuc2UgR1BMLTIuMC1vbmx5XG4gKi9cblxuaW1wb3J0IHtkaXNhYmxlU2Nyb2xsLCBlbmFibGVTY3JvbGwsIHRvZ2dsZVNjcm9sbH0gZnJvbSAnLi91dGlsaXR5JztcblxuLyoqXG4gKiBSZXNldHMgdGhpcyBsaXN0ZW5lclxuICogaHR0cHM6Ly9naXRodWIuY29tL3ZhbmlsbGEvdmFuaWxsYS9ibG9iL2Y3NTFlMzgyZGEzMjVlMDU3ODRiYTkxODAxNmIxYWYyOTAyZjNjM2EvanMvZ2xvYmFsLmpzI0w3OTBcbiAqIGluIG9yZGVyIHRvIHdvcmsgdmlzaWJpbGl0eTpoaWRkZW4gaW5zdGVhZCBvZiBkaXNwbGF5Om5vbmVcbiAqXG4gKiBUaGUgbWFpbiBqcyBmaWxlIHNob3VsZCBub3QgcmVseSBvbiBjZXJ0YWluIENTUyBzdHlsZXMhISFcbiAqL1xuZXhwb3J0IGZ1bmN0aW9uIGZpeFRvZ2dsZUZseW91dEJlaGF2aW91cigpIHtcbiAgICAkKGRvY3VtZW50KS51bmRlbGVnYXRlKFwiLlRvZ2dsZUZseW91dFwiLCBcImNsaWNrXCIpO1xuICAgIHZhciBsYXN0T3BlbiA9IG51bGw7XG5cbiAgICAkKGRvY3VtZW50KS5kZWxlZ2F0ZShcIi5Ub2dnbGVGbHlvdXRcIiwgXCJjbGlja1wiLCBmdW5jdGlvbihlKSB7XG4gICAgICAgIHZhciAkdG9nZ2xlRmx5b3V0ID0gJCh0aGlzKTtcbiAgICAgICAgdmFyICRmbHlvdXQgPSAkKFwiLkZseW91dFwiLCB0aGlzKTtcbiAgICAgICAgdmFyIGlzSGFuZGxlID0gZmFsc2U7XG5cbiAgICAgICAgaWYgKCQoZS50YXJnZXQpLmNsb3Nlc3QoXCIuRmx5b3V0XCIpLmxlbmd0aCA9PT0gMCkge1xuICAgICAgICAgICAgZS5zdG9wUHJvcGFnYXRpb24oKTtcbiAgICAgICAgICAgIGlzSGFuZGxlID0gdHJ1ZTtcbiAgICAgICAgfSBlbHNlIGlmIChcbiAgICAgICAgICAgICQoZS50YXJnZXQpLmhhc0NsYXNzKFwiSGlqYWNrXCIpIHx8XG4gICAgICAgICAgICAkKGUudGFyZ2V0KS5jbG9zZXN0KFwiYVwiKS5oYXNDbGFzcyhcIkhpamFja1wiKVxuICAgICAgICApIHtcbiAgICAgICAgICAgIHJldHVybjtcbiAgICAgICAgfVxuICAgICAgICBlLnN0b3BQcm9wYWdhdGlvbigpO1xuXG4gICAgICAgIC8vIER5bmFtaWNhbGx5IGZpbGwgdGhlIGZseW91dC5cbiAgICAgICAgdmFyIHJlbCA9ICQodGhpcykuYXR0cihcInJlbFwiKTtcbiAgICAgICAgaWYgKHJlbCkge1xuICAgICAgICAgICAgJCh0aGlzKS5hdHRyKFwicmVsXCIsIFwiXCIpO1xuICAgICAgICAgICAgJGZseW91dC5odG1sKCc8ZGl2IGNsYXNzPVwiSW5Qcm9ncmVzc1wiIHN0eWxlPVwiaGVpZ2h0OiAzMHB4XCI+PC9kaXY+Jyk7XG5cbiAgICAgICAgICAgICQuYWpheCh7XG4gICAgICAgICAgICAgICAgdXJsOiBnZG4udXJsKHJlbCksXG4gICAgICAgICAgICAgICAgZGF0YTogeyBEZWxpdmVyeVR5cGU6IFwiVklFV1wiIH0sXG4gICAgICAgICAgICAgICAgc3VjY2VzczogZnVuY3Rpb24oZGF0YSkge1xuICAgICAgICAgICAgICAgICAgICAkZmx5b3V0Lmh0bWwoZGF0YSk7XG4gICAgICAgICAgICAgICAgfSxcbiAgICAgICAgICAgICAgICBlcnJvcjogZnVuY3Rpb24oeGhyKSB7XG4gICAgICAgICAgICAgICAgICAgICRmbHlvdXQuaHRtbChcIlwiKTtcbiAgICAgICAgICAgICAgICAgICAgZ2RuLmluZm9ybUVycm9yKHhociwgdHJ1ZSk7XG4gICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgfSk7XG4gICAgICAgIH1cblxuICAgICAgICBpZiAoJGZseW91dC5jc3MoXCJ2aXNpYmlsaXR5XCIpID09PSBcImhpZGRlblwiKSB7XG4gICAgICAgICAgICBpZiAobGFzdE9wZW4gIT09IG51bGwpIHtcbiAgICAgICAgICAgICAgICAkKFwiLkZseW91dFwiLCBsYXN0T3BlbikuaGlkZSgpO1xuICAgICAgICAgICAgICAgICQobGFzdE9wZW4pXG4gICAgICAgICAgICAgICAgICAgIC5yZW1vdmVDbGFzcyhcIk9wZW5cIilcbiAgICAgICAgICAgICAgICAgICAgLmNsb3Nlc3QoXCIuSXRlbVwiKVxuICAgICAgICAgICAgICAgICAgICAucmVtb3ZlQ2xhc3MoXCJPcGVuXCIpO1xuICAgICAgICAgICAgICAgICR0b2dnbGVGbHlvdXQuc2V0Rmx5b3V0QXR0cmlidXRlcygpO1xuICAgICAgICAgICAgfVxuXG4gICAgICAgICAgICAkKHRoaXMpLmFkZENsYXNzKFwiT3BlblwiKS5jbG9zZXN0KFwiLkl0ZW1cIikuYWRkQ2xhc3MoXCJPcGVuXCIpO1xuICAgICAgICAgICAgJGZseW91dC5zaG93KCk7XG4gICAgICAgICAgICBkaXNhYmxlU2Nyb2xsKCk7XG4gICAgICAgICAgICBsYXN0T3BlbiA9IHRoaXM7XG4gICAgICAgICAgICAkdG9nZ2xlRmx5b3V0LnNldEZseW91dEF0dHJpYnV0ZXMoKTtcbiAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICAgICRmbHlvdXQuaGlkZSgpO1xuICAgICAgICAgICAgJCh0aGlzKS5yZW1vdmVDbGFzcyhcIk9wZW5cIikuY2xvc2VzdChcIi5JdGVtXCIpLnJlbW92ZUNsYXNzKFwiT3BlblwiKTtcbiAgICAgICAgICAgIGVuYWJsZVNjcm9sbCgpO1xuICAgICAgICAgICAgJHRvZ2dsZUZseW91dC5zZXRGbHlvdXRBdHRyaWJ1dGVzKCk7XG4gICAgICAgIH1cblxuICAgICAgICBpZiAoaXNIYW5kbGUpIHJldHVybiBmYWxzZTtcbiAgICB9KTtcblxuICAgIC8vIENsb3NlIFRvZ2dsZUZseW91dCBtZW51IGV2ZW4gaWYgdGhlaXIgbGlua3MgYXJlIGhpamFja2VkXG4gICAgJChkb2N1bWVudCkuZGVsZWdhdGUoXCIuVG9nZ2xlRmx5b3V0IGFcIiwgXCJtb3VzZXVwXCIsIGZ1bmN0aW9uKCkge1xuICAgICAgICBpZiAoJCh0aGlzKS5oYXNDbGFzcyhcIkZseW91dEJ1dHRvblwiKSlcbiAgICAgICAgICAgIHJldHVybjtcblxuICAgICAgICAkKFwiLlRvZ2dsZUZseW91dFwiKS5yZW1vdmVDbGFzcyhcIk9wZW5cIikuY2xvc2VzdChcIi5JdGVtXCIpLnJlbW92ZUNsYXNzKFwiT3BlblwiKTtcbiAgICAgICAgJChcIi5GbHlvdXRcIikuaGlkZSgpO1xuICAgICAgICAkKHRoaXMpLmNsb3Nlc3QoXCIuVG9nZ2xlRmx5b3V0XCIpLnNldEZseW91dEF0dHJpYnV0ZXMoKTtcbiAgICB9KTtcblxuICAgICQoZG9jdW1lbnQpLmRlbGVnYXRlKGRvY3VtZW50LCBcImNsaWNrXCIsIGZ1bmN0aW9uKGUpIHtcbiAgICAgICAgaWYgKGxhc3RPcGVuKSB7XG4gICAgICAgICAgICAkKFwiLkZseW91dFwiLCBsYXN0T3BlbikuaGlkZSgpO1xuICAgICAgICAgICAgJChsYXN0T3BlbilcbiAgICAgICAgICAgICAgICAucmVtb3ZlQ2xhc3MoXCJPcGVuXCIpXG4gICAgICAgICAgICAgICAgLmNsb3Nlc3QoXCIuSXRlbVwiKVxuICAgICAgICAgICAgICAgIC5yZW1vdmVDbGFzcyhcIk9wZW5cIik7XG4gICAgICAgIH1cbiAgICAgICAgJChcIi5CdXR0b25Hcm91cFwiKS5yZW1vdmVDbGFzcyhcIk9wZW5cIik7XG4gICAgICAgIGVuYWJsZVNjcm9sbCgpO1xuICAgIH0pO1xuXG4gICAgJChcIi5CdXR0b24uUHJpbWFyeS5IYW5kbGVcIikub24oXCJjbGlja1wiLCBldmVudCA9PiB7XG4gICAgICAgIHRvZ2dsZVNjcm9sbCgpO1xuICAgIH0pO1xuXG4gICAgJChcIi5PcHRpb25zIC5GbHlvdXRcIikub24oXCJjbGlja1wiLCAoKSA9PiB7XG4gICAgICAgIGVuYWJsZVNjcm9sbCgpO1xuICAgIH0pO1xufVxuIl0sInNvdXJjZVJvb3QiOiIifQ==\n//# sourceURL=webpack-internal:///./node_modules/@vanillaforums/theme-boilerplate/src/js/overrides.js\n");

/***/ }),

/***/ "./node_modules/@vanillaforums/theme-boilerplate/src/js/utility.js":
/*!*************************************************************************!*\
  !*** ./node_modules/@vanillaforums/theme-boilerplate/src/js/utility.js ***!
  \*************************************************************************/
/*! exports provided: fireEvent, toggleScroll, disableScroll, enableScroll */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, \"fireEvent\", function() { return fireEvent; });\n/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, \"toggleScroll\", function() { return toggleScroll; });\n/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, \"disableScroll\", function() { return disableScroll; });\n/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, \"enableScroll\", function() { return enableScroll; });\n/*!\n * @author Isis (igraziatto) Graziatto <isis.g@vanillaforums.com>\n * @copyright 2009-2018 Vanilla Forums Inc.\n * @license GPL-2.0-only\n */\n\nfunction fireEvent(element, eventName, options) {\n    var event = document.createEvent(\"CustomEvent\");\n    event.initCustomEvent(eventName, true, true, options);\n    element.dispatchEvent(event);\n}\n\nfunction toggleScroll() {\n    if ($(document.body)[0].style.overflow) {\n        enableScroll();\n    } else {\n        disableScroll();\n    }\n}\n\nfunction disableScroll() {\n    $(document.body).addClass(\"NoScroll\");\n}\n\nfunction enableScroll() {\n    $(document.body).removeClass(\"NoScroll\");\n}\n\n/**\n * Provides requestAnimationFrame in a cross browser way.\n */\n\nif (!window.requestAnimationFrame) {\n    window.requestAnimationFrame = (function() {\n        return (\n            window.webkitRequestAnimationFrame ||\n            window.mozRequestAnimationFrame ||\n            window.oRequestAnimationFrame ||\n            window.msRequestAnimationFrame ||\n            function(\n                /* function FrameRequestCallback */ callback,\n                /* DOMElement Element */ element\n            ) {\n                window.setTimeout(callback, 1000 / 60);\n            }\n        );\n    })();\n}\n//# sourceURL=[module]\n//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJzb3VyY2VzIjpbIndlYnBhY2s6Ly8vLi9ub2RlX21vZHVsZXMvQHZhbmlsbGFmb3J1bXMvdGhlbWUtYm9pbGVycGxhdGUvc3JjL2pzL3V0aWxpdHkuanM/NGRmNCJdLCJuYW1lcyI6W10sIm1hcHBpbmdzIjoiOzs7O0FBQUE7QUFBQTtBQUNBO0FBQ0E7QUFDQTtBQUNBOztBQUVBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7O0FBRUE7QUFDQTtBQUNBO0FBQ0EsS0FBSztBQUNMO0FBQ0E7QUFDQTs7QUFFQTtBQUNBO0FBQ0E7O0FBRUE7QUFDQTtBQUNBOztBQUVBO0FBQ0E7QUFDQTs7QUFFQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0EsS0FBSztBQUNMIiwiZmlsZSI6Ii4vbm9kZV9tb2R1bGVzL0B2YW5pbGxhZm9ydW1zL3RoZW1lLWJvaWxlcnBsYXRlL3NyYy9qcy91dGlsaXR5LmpzLmpzIiwic291cmNlc0NvbnRlbnQiOlsiLyohXG4gKiBAYXV0aG9yIElzaXMgKGlncmF6aWF0dG8pIEdyYXppYXR0byA8aXNpcy5nQHZhbmlsbGFmb3J1bXMuY29tPlxuICogQGNvcHlyaWdodCAyMDA5LTIwMTggVmFuaWxsYSBGb3J1bXMgSW5jLlxuICogQGxpY2Vuc2UgR1BMLTIuMC1vbmx5XG4gKi9cblxuZXhwb3J0IGZ1bmN0aW9uIGZpcmVFdmVudChlbGVtZW50LCBldmVudE5hbWUsIG9wdGlvbnMpIHtcbiAgICB2YXIgZXZlbnQgPSBkb2N1bWVudC5jcmVhdGVFdmVudChcIkN1c3RvbUV2ZW50XCIpO1xuICAgIGV2ZW50LmluaXRDdXN0b21FdmVudChldmVudE5hbWUsIHRydWUsIHRydWUsIG9wdGlvbnMpO1xuICAgIGVsZW1lbnQuZGlzcGF0Y2hFdmVudChldmVudCk7XG59XG5cbmV4cG9ydCBmdW5jdGlvbiB0b2dnbGVTY3JvbGwoKSB7XG4gICAgaWYgKCQoZG9jdW1lbnQuYm9keSlbMF0uc3R5bGUub3ZlcmZsb3cpIHtcbiAgICAgICAgZW5hYmxlU2Nyb2xsKCk7XG4gICAgfSBlbHNlIHtcbiAgICAgICAgZGlzYWJsZVNjcm9sbCgpO1xuICAgIH1cbn1cblxuZXhwb3J0IGZ1bmN0aW9uIGRpc2FibGVTY3JvbGwoKSB7XG4gICAgJChkb2N1bWVudC5ib2R5KS5hZGRDbGFzcyhcIk5vU2Nyb2xsXCIpO1xufVxuXG5leHBvcnQgZnVuY3Rpb24gZW5hYmxlU2Nyb2xsKCkge1xuICAgICQoZG9jdW1lbnQuYm9keSkucmVtb3ZlQ2xhc3MoXCJOb1Njcm9sbFwiKTtcbn1cblxuLyoqXG4gKiBQcm92aWRlcyByZXF1ZXN0QW5pbWF0aW9uRnJhbWUgaW4gYSBjcm9zcyBicm93c2VyIHdheS5cbiAqL1xuXG5pZiAoIXdpbmRvdy5yZXF1ZXN0QW5pbWF0aW9uRnJhbWUpIHtcbiAgICB3aW5kb3cucmVxdWVzdEFuaW1hdGlvbkZyYW1lID0gKGZ1bmN0aW9uKCkge1xuICAgICAgICByZXR1cm4gKFxuICAgICAgICAgICAgd2luZG93LndlYmtpdFJlcXVlc3RBbmltYXRpb25GcmFtZSB8fFxuICAgICAgICAgICAgd2luZG93Lm1velJlcXVlc3RBbmltYXRpb25GcmFtZSB8fFxuICAgICAgICAgICAgd2luZG93Lm9SZXF1ZXN0QW5pbWF0aW9uRnJhbWUgfHxcbiAgICAgICAgICAgIHdpbmRvdy5tc1JlcXVlc3RBbmltYXRpb25GcmFtZSB8fFxuICAgICAgICAgICAgZnVuY3Rpb24oXG4gICAgICAgICAgICAgICAgLyogZnVuY3Rpb24gRnJhbWVSZXF1ZXN0Q2FsbGJhY2sgKi8gY2FsbGJhY2ssXG4gICAgICAgICAgICAgICAgLyogRE9NRWxlbWVudCBFbGVtZW50ICovIGVsZW1lbnRcbiAgICAgICAgICAgICkge1xuICAgICAgICAgICAgICAgIHdpbmRvdy5zZXRUaW1lb3V0KGNhbGxiYWNrLCAxMDAwIC8gNjApO1xuICAgICAgICAgICAgfVxuICAgICAgICApO1xuICAgIH0pKCk7XG59XG4iXSwic291cmNlUm9vdCI6IiJ9\n//# sourceURL=webpack-internal:///./node_modules/@vanillaforums/theme-boilerplate/src/js/utility.js\n");

/***/ }),

/***/ "./src/js/index.js":
/*!*************************!*\
  !*** ./src/js/index.js ***!
  \*************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";
eval("\n\n__webpack_require__(/*! ../../node_modules/@vanillaforums/theme-boilerplate/src/js/index */ \"./node_modules/@vanillaforums/theme-boilerplate/src/js/index.js\");//# sourceURL=[module]\n//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJzb3VyY2VzIjpbIndlYnBhY2s6Ly8vLi9zcmMvanMvaW5kZXguanM/N2JhNSJdLCJuYW1lcyI6W10sIm1hcHBpbmdzIjoiOztBQU1BIiwiZmlsZSI6Ii4vc3JjL2pzL2luZGV4LmpzLmpzIiwic291cmNlc0NvbnRlbnQiOlsiLyohXG4gKiBAYXV0aG9yIElzaXMgKGlncmF6aWF0dG8pIEdyYXppYXR0byA8aXNpcy5nQHZhbmlsbGFmb3J1bXMuY29tPlxuICogQGNvcHlyaWdodCAyMDA5LTIwMTggVmFuaWxsYSBGb3J1bXMgSW5jLlxuICogQGxpY2Vuc2UgR1BMLTIuMC1vbmx5XG4gKi9cblxuaW1wb3J0IFwiLi4vLi4vbm9kZV9tb2R1bGVzL0B2YW5pbGxhZm9ydW1zL3RoZW1lLWJvaWxlcnBsYXRlL3NyYy9qcy9pbmRleFwiO1xuIl0sInNvdXJjZVJvb3QiOiIifQ==\n//# sourceURL=webpack-internal:///./src/js/index.js\n");

/***/ })

/******/ });