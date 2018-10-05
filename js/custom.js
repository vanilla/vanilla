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
eval("\n\nvar _mobileNavigation = __webpack_require__(/*! ./mobileNavigation */ \"./src/js/mobileNavigation.js\");\n\nvar _overrides = __webpack_require__(/*! ./overrides */ \"./src/js/overrides.js\");\n\n/*!\n * @author Isis (igraziatto) Graziatto <isis.g@vanillaforums.com>\n * @copyright 2009-2018 Vanilla Forums Inc.\n * @license GPL-2.0-only\n */\n\n$(function () {\n  (0, _mobileNavigation.setupMobileNavigation)();\n  (0, _overrides.fixToggleFlyoutBehaviour)();\n\n  $(\"select\").wrap('<div class=\"SelectWrapper\"></div>');\n});//# sourceURL=[module]\n//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJzb3VyY2VzIjpbIndlYnBhY2s6Ly8vLi9zcmMvanMvaW5kZXguanM/N2JhNSJdLCJuYW1lcyI6WyIkIiwid3JhcCJdLCJtYXBwaW5ncyI6Ijs7QUFNQTs7QUFDQTs7QUFQQTs7Ozs7O0FBU0FBLEVBQUUsWUFBTTtBQUNKO0FBQ0E7O0FBRUFBLElBQUUsUUFBRixFQUFZQyxJQUFaLENBQWlCLG1DQUFqQjtBQUNILENBTEQiLCJmaWxlIjoiLi9zcmMvanMvaW5kZXguanMuanMiLCJzb3VyY2VzQ29udGVudCI6WyIvKiFcbiAqIEBhdXRob3IgSXNpcyAoaWdyYXppYXR0bykgR3JhemlhdHRvIDxpc2lzLmdAdmFuaWxsYWZvcnVtcy5jb20+XG4gKiBAY29weXJpZ2h0IDIwMDktMjAxOCBWYW5pbGxhIEZvcnVtcyBJbmMuXG4gKiBAbGljZW5zZSBHUEwtMi4wLW9ubHlcbiAqL1xuXG5pbXBvcnQgeyBzZXR1cE1vYmlsZU5hdmlnYXRpb24gfSBmcm9tIFwiLi9tb2JpbGVOYXZpZ2F0aW9uXCI7XG5pbXBvcnQgeyBmaXhUb2dnbGVGbHlvdXRCZWhhdmlvdXIgfSBmcm9tIFwiLi9vdmVycmlkZXNcIjtcblxuJCgoKSA9PiB7XG4gICAgc2V0dXBNb2JpbGVOYXZpZ2F0aW9uKCk7XG4gICAgZml4VG9nZ2xlRmx5b3V0QmVoYXZpb3VyKCk7XG5cbiAgICAkKFwic2VsZWN0XCIpLndyYXAoJzxkaXYgY2xhc3M9XCJTZWxlY3RXcmFwcGVyXCI+PC9kaXY+Jyk7XG59KTtcbiJdLCJzb3VyY2VSb290IjoiIn0=\n//# sourceURL=webpack-internal:///./src/js/index.js\n");

/***/ }),

/***/ "./src/js/mobileNavigation.js":
/*!************************************!*\
  !*** ./src/js/mobileNavigation.js ***!
  \************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";
eval("\n\nObject.defineProperty(exports, \"__esModule\", {\n    value: true\n});\nexports.setupMobileNavigation = setupMobileNavigation;\n/*!\n * @author Isis (igraziatto) Graziatto <isis.g@vanillaforums.com>\n * @copyright 2009-2018 Vanilla Forums Inc.\n * @license GPL-2.0-only\n */\n\nfunction setupMobileNavigation() {\n\n    var $menuButton = $(\"#menu-button\"),\n        $navdrawer = $(\"#navdrawer\");\n\n    $menuButton.on(\"click\", function () {\n        $menuButton.toggleClass(\"isToggled\");\n        $navdrawer.toggleClass(\"isOpen\");\n    });\n}//# sourceURL=[module]\n//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJzb3VyY2VzIjpbIndlYnBhY2s6Ly8vLi9zcmMvanMvbW9iaWxlTmF2aWdhdGlvbi5qcz9mN2JlIl0sIm5hbWVzIjpbInNldHVwTW9iaWxlTmF2aWdhdGlvbiIsIiRtZW51QnV0dG9uIiwiJCIsIiRuYXZkcmF3ZXIiLCJvbiIsInRvZ2dsZUNsYXNzIl0sIm1hcHBpbmdzIjoiOzs7OztRQU1nQkEscUIsR0FBQUEscUI7QUFOaEI7Ozs7OztBQU1PLFNBQVNBLHFCQUFULEdBQWlDOztBQUVwQyxRQUFJQyxjQUFjQyxFQUFFLGNBQUYsQ0FBbEI7QUFBQSxRQUNJQyxhQUFhRCxFQUFFLFlBQUYsQ0FEakI7O0FBR0FELGdCQUFZRyxFQUFaLENBQWUsT0FBZixFQUF3QixZQUFNO0FBQzFCSCxvQkFBWUksV0FBWixDQUF3QixXQUF4QjtBQUNBRixtQkFBV0UsV0FBWCxDQUF1QixRQUF2QjtBQUNILEtBSEQ7QUFJSCIsImZpbGUiOiIuL3NyYy9qcy9tb2JpbGVOYXZpZ2F0aW9uLmpzLmpzIiwic291cmNlc0NvbnRlbnQiOlsiLyohXG4gKiBAYXV0aG9yIElzaXMgKGlncmF6aWF0dG8pIEdyYXppYXR0byA8aXNpcy5nQHZhbmlsbGFmb3J1bXMuY29tPlxuICogQGNvcHlyaWdodCAyMDA5LTIwMTggVmFuaWxsYSBGb3J1bXMgSW5jLlxuICogQGxpY2Vuc2UgR1BMLTIuMC1vbmx5XG4gKi9cblxuZXhwb3J0IGZ1bmN0aW9uIHNldHVwTW9iaWxlTmF2aWdhdGlvbigpIHtcblxuICAgIHZhciAkbWVudUJ1dHRvbiA9ICQoXCIjbWVudS1idXR0b25cIiksXG4gICAgICAgICRuYXZkcmF3ZXIgPSAkKFwiI25hdmRyYXdlclwiKTtcblxuICAgICRtZW51QnV0dG9uLm9uKFwiY2xpY2tcIiwgKCkgPT4ge1xuICAgICAgICAkbWVudUJ1dHRvbi50b2dnbGVDbGFzcyhcImlzVG9nZ2xlZFwiKTtcbiAgICAgICAgJG5hdmRyYXdlci50b2dnbGVDbGFzcyhcImlzT3BlblwiKTtcbiAgICB9KTtcbn1cbiJdLCJzb3VyY2VSb290IjoiIn0=\n//# sourceURL=webpack-internal:///./src/js/mobileNavigation.js\n");

/***/ }),

/***/ "./src/js/overrides.js":
/*!*****************************!*\
  !*** ./src/js/overrides.js ***!
  \*****************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";
eval("\n\nObject.defineProperty(exports, \"__esModule\", {\n    value: true\n});\nexports.fixToggleFlyoutBehaviour = fixToggleFlyoutBehaviour;\n\nvar _utility = __webpack_require__(/*! ./utility */ \"./src/js/utility.js\");\n\n/**\n * Resets this listener\n * https://github.com/vanilla/vanilla/blob/f751e382da325e05784ba918016b1af2902f3c3a/js/global.js#L790\n * in order to work visibility:hidden instead of display:none\n *\n * The main js file should not rely on certain CSS styles!!!\n */\nfunction fixToggleFlyoutBehaviour() {\n    $(document).undelegate(\".ToggleFlyout\", \"click\");\n    var lastOpen = null;\n\n    $(document).delegate(\".ToggleFlyout\", \"click\", function (e) {\n        var $toggleFlyout = $(this);\n        var $flyout = $(\".Flyout\", this);\n        var isHandle = false;\n\n        if ($(e.target).closest(\".Flyout\").length === 0) {\n            e.stopPropagation();\n            isHandle = true;\n        } else if ($(e.target).hasClass(\"Hijack\") || $(e.target).closest(\"a\").hasClass(\"Hijack\")) {\n            return;\n        }\n        e.stopPropagation();\n\n        // Dynamically fill the flyout.\n        var rel = $(this).attr(\"rel\");\n        if (rel) {\n            $(this).attr(\"rel\", \"\");\n            $flyout.html('<div class=\"InProgress\" style=\"height: 30px\"></div>');\n\n            $.ajax({\n                url: gdn.url(rel),\n                data: { DeliveryType: \"VIEW\" },\n                success: function success(data) {\n                    $flyout.html(data);\n                },\n                error: function error(xhr) {\n                    $flyout.html(\"\");\n                    gdn.informError(xhr, true);\n                }\n            });\n        }\n\n        if ($flyout.css(\"visibility\") === \"hidden\") {\n            if (lastOpen !== null) {\n                $(\".Flyout\", lastOpen).hide();\n                $(lastOpen).removeClass(\"Open\").closest(\".Item\").removeClass(\"Open\");\n                $toggleFlyout.setFlyoutAttributes();\n            }\n\n            $(this).addClass(\"Open\").closest(\".Item\").addClass(\"Open\");\n            $flyout.show();\n            (0, _utility.disableScroll)();\n            lastOpen = this;\n            $toggleFlyout.setFlyoutAttributes();\n        } else {\n            $flyout.hide();\n            $(this).removeClass(\"Open\").closest(\".Item\").removeClass(\"Open\");\n            (0, _utility.enableScroll)();\n            $toggleFlyout.setFlyoutAttributes();\n        }\n\n        if (isHandle) return false;\n    });\n\n    // Close ToggleFlyout menu even if their links are hijacked\n    $(document).delegate(\".ToggleFlyout a\", \"mouseup\", function () {\n        if ($(this).hasClass(\"FlyoutButton\")) return;\n\n        $(\".ToggleFlyout\").removeClass(\"Open\").closest(\".Item\").removeClass(\"Open\");\n        $(\".Flyout\").hide();\n        $(this).closest(\".ToggleFlyout\").setFlyoutAttributes();\n    });\n\n    $(document).delegate(document, \"click\", function (e) {\n        if (lastOpen) {\n            $(\".Flyout\", lastOpen).hide();\n            $(lastOpen).removeClass(\"Open\").closest(\".Item\").removeClass(\"Open\");\n        }\n        $(\".ButtonGroup\").removeClass(\"Open\");\n        (0, _utility.enableScroll)();\n    });\n\n    $(\".Button.Primary.Handle\").on(\"click\", function (event) {\n        (0, _utility.toggleScroll)();\n    });\n\n    $(\".Options .Flyout\").on(\"click\", function () {\n        (0, _utility.enableScroll)();\n    });\n} /*!\n   * @author Isis (igraziatto) Graziatto <isis.g@vanillaforums.com>\n   * @copyright 2009-2018 Vanilla Forums Inc.\n   * @license GPL-2.0-only\n   *///# sourceURL=[module]\n//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJzb3VyY2VzIjpbIndlYnBhY2s6Ly8vLi9zcmMvanMvb3ZlcnJpZGVzLmpzPzlmMzQiXSwibmFtZXMiOlsiZml4VG9nZ2xlRmx5b3V0QmVoYXZpb3VyIiwiJCIsImRvY3VtZW50IiwidW5kZWxlZ2F0ZSIsImxhc3RPcGVuIiwiZGVsZWdhdGUiLCJlIiwiJHRvZ2dsZUZseW91dCIsIiRmbHlvdXQiLCJpc0hhbmRsZSIsInRhcmdldCIsImNsb3Nlc3QiLCJsZW5ndGgiLCJzdG9wUHJvcGFnYXRpb24iLCJoYXNDbGFzcyIsInJlbCIsImF0dHIiLCJodG1sIiwiYWpheCIsInVybCIsImdkbiIsImRhdGEiLCJEZWxpdmVyeVR5cGUiLCJzdWNjZXNzIiwiZXJyb3IiLCJ4aHIiLCJpbmZvcm1FcnJvciIsImNzcyIsImhpZGUiLCJyZW1vdmVDbGFzcyIsInNldEZseW91dEF0dHJpYnV0ZXMiLCJhZGRDbGFzcyIsInNob3ciLCJvbiJdLCJtYXBwaW5ncyI6Ijs7Ozs7UUFlZ0JBLHdCLEdBQUFBLHdCOztBQVRoQjs7QUFFQTs7Ozs7OztBQU9PLFNBQVNBLHdCQUFULEdBQW9DO0FBQ3ZDQyxNQUFFQyxRQUFGLEVBQVlDLFVBQVosQ0FBdUIsZUFBdkIsRUFBd0MsT0FBeEM7QUFDQSxRQUFJQyxXQUFXLElBQWY7O0FBRUFILE1BQUVDLFFBQUYsRUFBWUcsUUFBWixDQUFxQixlQUFyQixFQUFzQyxPQUF0QyxFQUErQyxVQUFTQyxDQUFULEVBQVk7QUFDdkQsWUFBSUMsZ0JBQWdCTixFQUFFLElBQUYsQ0FBcEI7QUFDQSxZQUFJTyxVQUFVUCxFQUFFLFNBQUYsRUFBYSxJQUFiLENBQWQ7QUFDQSxZQUFJUSxXQUFXLEtBQWY7O0FBRUEsWUFBSVIsRUFBRUssRUFBRUksTUFBSixFQUFZQyxPQUFaLENBQW9CLFNBQXBCLEVBQStCQyxNQUEvQixLQUEwQyxDQUE5QyxFQUFpRDtBQUM3Q04sY0FBRU8sZUFBRjtBQUNBSix1QkFBVyxJQUFYO0FBQ0gsU0FIRCxNQUdPLElBQ0hSLEVBQUVLLEVBQUVJLE1B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QUFFLHdCQUFGLEVBQTRCZ0MsRUFBNUIsQ0FBK0IsT0FBL0IsRUFBd0MsaUJBQVM7QUFDN0M7QUFDSCxLQUZEOztBQUlBaEMsTUFBRSxrQkFBRixFQUFzQmdDLEVBQXRCLENBQXlCLE9BQXpCLEVBQWtDLFlBQU07QUFDcEM7QUFDSCxLQUZEO0FBR0gsQyxDQTVHRCIsImZpbGUiOiIuL3NyYy9qcy9vdmVycmlkZXMuanMuanMiLCJzb3VyY2VzQ29udGVudCI6WyIvKiFcbiAqIEBhdXRob3IgSXNpcyAoaWdyYXppYXR0bykgR3JhemlhdHRvIDxpc2lzLmdAdmFuaWxsYWZvcnVtcy5jb20+XG4gKiBAY29weXJpZ2h0IDIwMDktMjAxOCBWYW5pbGxhIEZvcnVtcyBJbmMuXG4gKiBAbGljZW5zZSBHUEwtMi4wLW9ubHlcbiAqL1xuXG5pbXBvcnQge2Rpc2FibGVTY3JvbGwsIGVuYWJsZVNjcm9sbCwgdG9nZ2xlU2Nyb2xsfSBmcm9tICcuL3V0aWxpdHknO1xuXG4vKipcbiAqIFJlc2V0cyB0aGlzIGxpc3RlbmVyXG4gKiBodHRwczovL2dpdGh1Yi5jb20vdmFuaWxsYS92YW5pbGxhL2Jsb2IvZjc1MWUzODJkYTMyNWUwNTc4NGJhOTE4MDE2YjFhZjI5MDJmM2MzYS9qcy9nbG9iYWwuanMjTDc5MFxuICogaW4gb3JkZXIgdG8gd29yayB2aXNpYmlsaXR5OmhpZGRlbiBpbnN0ZWFkIG9mIGRpc3BsYXk6bm9uZVxuICpcbiAqIFRoZSBtYWluIGpzIGZpbGUgc2hvdWxkIG5vdCByZWx5IG9uIGNlcnRhaW4gQ1NTIHN0eWxlcyEhIVxuICovXG5leHBvcnQgZnVuY3Rpb24gZml4VG9nZ2xlRmx5b3V0QmVoYXZpb3VyKCkge1xuICAgICQoZG9jdW1lbnQpLnVuZGVsZWdhdGUoXCIuVG9nZ2xlRmx5b3V0XCIsIFwiY2xpY2tcIik7XG4gICAgdmFyIGxhc3RPcGVuID0gbnVsbDtcblxuICAgICQoZG9jdW1lbnQpLmRlbGVnYXRlKFwiLlRvZ2dsZUZseW91dFwiLCBcImNsaWNrXCIsIGZ1bmN0aW9uKGUpIHtcbiAgICAgICAgdmFyICR0b2dnbGVGbHlvdXQgPSAkKHRoaXMpO1xuICAgICAgICB2YXIgJGZseW91dCA9ICQoXCIuRmx5b3V0XCIsIHRoaXMpO1xuICAgICAgICB2YXIgaXNIYW5kbGUgPSBmYWxzZTtcblxuICAgICAgICBpZiAoJChlLnRhcmdldCkuY2xvc2VzdChcIi5GbHlvdXRcIikubGVuZ3RoID09PSAwKSB7XG4gICAgICAgICAgICBlLnN0b3BQcm9wYWdhdGlvbigpO1xuICAgICAgICAgICAgaXNIYW5kbGUgPSB0cnVlO1xuICAgICAgICB9IGVsc2UgaWYgKFxuICAgICAgICAgICAgJChlLnRhcmdldCkuaGFzQ2xhc3MoXCJIaWphY2tcIikgfHxcbiAgICAgICAgICAgICQoZS50YXJnZXQpLmNsb3Nlc3QoXCJhXCIpLmhhc0NsYXNzKFwiSGlqYWNrXCIpXG4gICAgICAgICkge1xuICAgICAgICAgICAgcmV0dXJuO1xuICAgICAgICB9XG4gICAgICAgIGUuc3RvcFByb3BhZ2F0aW9uKCk7XG5cbiAgICAgICAgLy8gRHluYW1pY2FsbHkgZmlsbCB0aGUgZmx5b3V0LlxuICAgICAgICB2YXIgcmVsID0gJCh0aGlzKS5hdHRyKFwicmVsXCIpO1xuICAgICAgICBpZiAocmVsKSB7XG4gICAgICAgICAgICAkKHRoaXMpLmF0dHIoXCJyZWxcIiwgXCJcIik7XG4gICAgICAgICAgICAkZmx5b3V0Lmh0bWwoJzxkaXYgY2xhc3M9XCJJblByb2dyZXNzXCIgc3R5bGU9XCJoZWlnaHQ6IDMwcHhcIj48L2Rpdj4nKTtcblxuICAgICAgICAgICAgJC5hamF4KHtcbiAgICAgICAgICAgICAgICB1cmw6IGdkbi51cmwocmVsKSxcbiAgICAgICAgICAgICAgICBkYXRhOiB7IERlbGl2ZXJ5VHlwZTogXCJWSUVXXCIgfSxcbiAgICAgICAgICAgICAgICBzdWNjZXNzOiBmdW5jdGlvbihkYXRhKSB7XG4gICAgICAgICAgICAgICAgICAgICRmbHlvdXQuaHRtbChkYXRhKTtcbiAgICAgICAgICAgICAgICB9LFxuICAgICAgICAgICAgICAgIGVycm9yOiBmdW5jdGlvbih4aHIpIHtcbiAgICAgICAgICAgICAgICAgICAgJGZseW91dC5odG1sKFwiXCIpO1xuICAgICAgICAgICAgICAgICAgICBnZG4uaW5mb3JtRXJyb3IoeGhyLCB0cnVlKTtcbiAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICB9KTtcbiAgICAgICAgfVxuXG4gICAgICAgIGlmICgkZmx5b3V0LmNzcyhcInZpc2liaWxpdHlcIikgPT09IFwiaGlkZGVuXCIpIHtcbiAgICAgICAgICAgIGlmIChsYXN0T3BlbiAhPT0gbnVsbCkge1xuICAgICAgICAgICAgICAgICQoXCIuRmx5b3V0XCIsIGxhc3RPcGVuKS5oaWRlKCk7XG4gICAgICAgICAgICAgICAgJChsYXN0T3BlbilcbiAgICAgICAgICAgICAgICAgICAgLnJlbW92ZUNsYXNzKFwiT3BlblwiKVxuICAgICAgICAgICAgICAgICAgICAuY2xvc2VzdChcIi5JdGVtXCIpXG4gICAgICAgICAgICAgICAgICAgIC5yZW1vdmVDbGFzcyhcIk9wZW5cIik7XG4gICAgICAgICAgICAgICAgJHRvZ2dsZUZseW91dC5zZXRGbHlvdXRBdHRyaWJ1dGVzKCk7XG4gICAgICAgICAgICB9XG5cbiAgICAgICAgICAgICQodGhpcykuYWRkQ2xhc3MoXCJPcGVuXCIpLmNsb3Nlc3QoXCIuSXRlbVwiKS5hZGRDbGFzcyhcIk9wZW5cIik7XG4gICAgICAgICAgICAkZmx5b3V0LnNob3coKTtcbiAgICAgICAgICAgIGRpc2FibGVTY3JvbGwoKTtcbiAgICAgICAgICAgIGxhc3RPcGVuID0gdGhpcztcbiAgICAgICAgICAgICR0b2dnbGVGbHlvdXQuc2V0Rmx5b3V0QXR0cmlidXRlcygpO1xuICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICAgJGZseW91dC5oaWRlKCk7XG4gICAgICAgICAgICAkKHRoaXMpLnJlbW92ZUNsYXNzKFwiT3BlblwiKS5jbG9zZXN0KFwiLkl0ZW1cIikucmVtb3ZlQ2xhc3MoXCJPcGVuXCIpO1xuICAgICAgICAgICAgZW5hYmxlU2Nyb2xsKCk7XG4gICAgICAgICAgICAkdG9nZ2xlRmx5b3V0LnNldEZseW91dEF0dHJpYnV0ZXMoKTtcbiAgICAgICAgfVxuXG4gICAgICAgIGlmIChpc0hhbmRsZSkgcmV0dXJuIGZhbHNlO1xuICAgIH0pO1xuXG4gICAgLy8gQ2xvc2UgVG9nZ2xlRmx5b3V0IG1lbnUgZXZlbiBpZiB0aGVpciBsaW5rcyBhcmUgaGlqYWNrZWRcbiAgICAkKGRvY3VtZW50KS5kZWxlZ2F0ZShcIi5Ub2dnbGVGbHlvdXQgYVwiLCBcIm1vdXNldXBcIiwgZnVuY3Rpb24oKSB7XG4gICAgICAgIGlmICgkKHRoaXMpLmhhc0NsYXNzKFwiRmx5b3V0QnV0dG9uXCIpKVxuICAgICAgICAgICAgcmV0dXJuO1xuXG4gICAgICAgICQoXCIuVG9nZ2xlRmx5b3V0XCIpLnJlbW92ZUNsYXNzKFwiT3BlblwiKS5jbG9zZXN0KFwiLkl0ZW1cIikucmVtb3ZlQ2xhc3MoXCJPcGVuXCIpO1xuICAgICAgICAkKFwiLkZseW91dFwiKS5oaWRlKCk7XG4gICAgICAgICQodGhpcykuY2xvc2VzdChcIi5Ub2dnbGVGbHlvdXRcIikuc2V0Rmx5b3V0QXR0cmlidXRlcygpO1xuICAgIH0pO1xuXG4gICAgJChkb2N1bWVudCkuZGVsZWdhdGUoZG9jdW1lbnQsIFwiY2xpY2tcIiwgZnVuY3Rpb24oZSkge1xuICAgICAgICBpZiAobGFzdE9wZW4pIHtcbiAgICAgICAgICAgICQoXCIuRmx5b3V0XCIsIGxhc3RPcGVuKS5oaWRlKCk7XG4gICAgICAgICAgICAkKGxhc3RPcGVuKVxuICAgICAgICAgICAgICAgIC5yZW1vdmVDbGFzcyhcIk9wZW5cIilcbiAgICAgICAgICAgICAgICAuY2xvc2VzdChcIi5JdGVtXCIpXG4gICAgICAgICAgICAgICAgLnJlbW92ZUNsYXNzKFwiT3BlblwiKTtcbiAgICAgICAgfVxuICAgICAgICAkKFwiLkJ1dHRvbkdyb3VwXCIpLnJlbW92ZUNsYXNzKFwiT3BlblwiKTtcbiAgICAgICAgZW5hYmxlU2Nyb2xsKCk7XG4gICAgfSk7XG5cbiAgICAkKFwiLkJ1dHRvbi5QcmltYXJ5LkhhbmRsZVwiKS5vbihcImNsaWNrXCIsIGV2ZW50ID0+IHtcbiAgICAgICAgdG9nZ2xlU2Nyb2xsKCk7XG4gICAgfSk7XG5cbiAgICAkKFwiLk9wdGlvbnMgLkZseW91dFwiKS5vbihcImNsaWNrXCIsICgpID0+IHtcbiAgICAgICAgZW5hYmxlU2Nyb2xsKCk7XG4gICAgfSk7XG59XG4iXSwic291cmNlUm9vdCI6IiJ9\n//# sourceURL=webpack-internal:///./src/js/overrides.js\n");

/***/ }),

/***/ "./src/js/utility.js":
/*!***************************!*\
  !*** ./src/js/utility.js ***!
  \***************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";
eval("\n\nObject.defineProperty(exports, \"__esModule\", {\n    value: true\n});\nexports.fireEvent = fireEvent;\nexports.toggleScroll = toggleScroll;\nexports.disableScroll = disableScroll;\nexports.enableScroll = enableScroll;\n/*!\n * @author Isis (igraziatto) Graziatto <isis.g@vanillaforums.com>\n * @copyright 2009-2018 Vanilla Forums Inc.\n * @license GPL-2.0-only\n */\n\nfunction fireEvent(element, eventName, options) {\n    var event = document.createEvent(\"CustomEvent\");\n    event.initCustomEvent(eventName, true, true, options);\n    element.dispatchEvent(event);\n}\n\nfunction toggleScroll() {\n    if ($(document.body)[0].style.overflow) {\n        enableScroll();\n    } else {\n        disableScroll();\n    }\n}\n\nfunction disableScroll() {\n    $(document.body).addClass(\"NoScroll\");\n}\n\nfunction enableScroll() {\n    $(document.body).removeClass(\"NoScroll\");\n}\n\n/**\n * Provides requestAnimationFrame in a cross browser way.\n */\n\nif (!window.requestAnimationFrame) {\n    window.requestAnimationFrame = function () {\n        return window.webkitRequestAnimationFrame || window.mozRequestAnimationFrame || window.oRequestAnimationFrame || window.msRequestAnimationFrame || function (\n        /* function FrameRequestCallback */callback,\n        /* DOMElement Element */element) {\n            window.setTimeout(callback, 1000 / 60);\n        };\n    }();\n}//# sourceURL=[module]\n//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJzb3VyY2VzIjpbIndlYnBhY2s6Ly8vLi9zcmMvanMvdXRpbGl0eS5qcz8yZjY4Il0sIm5hbWVzIjpbImZpcmVFdmVudCIsInRvZ2dsZVNjcm9sbCIsImRpc2FibGVTY3JvbGwiLCJlbmFibGVTY3JvbGwiLCJlbGVtZW50IiwiZXZlbnROYW1lIiwib3B0aW9ucyIsImV2ZW50IiwiZG9jdW1lbnQiLCJjcmVhdGVFdmVudCIsImluaXRDdXN0b21FdmVudCIsImRpc3BhdGNoRXZlbnQiLCIkIiwiYm9keSIsInN0eWxlIiwib3ZlcmZsb3ciLCJhZGRDbGFzcyIsInJlbW92ZUNsYXNzIiwid2luZG93IiwicmVxdWVzdEFuaW1hdGlvbkZyYW1lIiwid2Via2l0UmVxdWVzdEFuaW1hdGlvbkZyYW1lIiwibW96UmVxdWVzdEFuaW1hdGlvbkZyYW1lIiwib1JlcXVlc3RBbmltYXRpb25GcmFtZSIsIm1zUmVxdWVzdEFuaW1hdGlvbkZyYW1lIiwiY2FsbGJhY2siLCJzZXRUaW1lb3V0Il0sIm1hcHBpbmdzIjoiOzs7OztRQU1nQkEsUyxHQUFBQSxTO1FBTUFDLFksR0FBQUEsWTtRQVFBQyxhLEdBQUFBLGE7UUFJQUMsWSxHQUFBQSxZO0FBeEJoQjs7Ozs7O0FBTU8sU0FBU0gsU0FBVCxDQUFtQkksT0FBbkIsRUFBNEJDLFNBQTVCLEVBQXVDQyxPQUF2QyxFQUFnRDtBQUNuRCxRQUFJQyxRQUFRQyxTQUFTQyxXQUFULENBQXFCLGFBQXJCLENBQVo7QUFDQUYsVUFBTUcsZUFBTixDQUFzQkwsU0FBdEIsRUFBaUMsSUFBakMsRUFBdUMsSUFBdkMsRUFBNkNDLE9BQTdDO0FBQ0FGLFlBQVFPLGFBQVIsQ0FBc0JKLEtBQXRCO0FBQ0g7O0FBRU0sU0FBU04sWUFBVCxHQUF3QjtBQUMzQixRQUFJVyxFQUFFSixTQUFTSyxJQUFYLEVBQWlCLENBQWpCLEVBQW9CQyxLQUFwQixDQUEwQkMsUUFBOUIsRUFBd0M7QUFDcENaO0FBQ0gsS0FGRCxNQUVPO0FBQ0hEO0FBQ0g7QUFDSjs7QUFFTSxTQUFTQSxhQUFULEdBQXlCO0FBQzVCVSxNQUFFSixTQUFTSyxJQUFYLEVBQWlCRyxRQUFqQixDQUEwQixVQUExQjtBQUNIOztBQUVNLFNBQVNiLFlBQVQsR0FBd0I7QUFDM0JTLE1BQUVKLFNBQVNLLElBQVgsRUFBaUJJLFdBQWpCLENBQTZCLFVBQTdCO0FBQ0g7O0FBRUQ7Ozs7QUFJQSxJQUFJLENBQUNDLE9BQU9DLHFCQUFaLEVBQW1DO0FBQy9CRCxXQUFPQyxxQkFBUCxHQUFnQyxZQUFXO0FBQ3ZDLGVBQ0lELE9BQU9FLDJCQUFQLElBQ0FGLE9BQU9HLHdCQURQLElBRUFILE9BQU9JLHNCQUZQLElBR0FKLE9BQU9LLHVCQUhQLElBSUE7QUFDSSwyQ0FBb0NDLFFBRHhDO0FBRUksZ0NBQXlCcEIsT0FGN0IsRUFHRTtBQUNFYyxtQkFBT08sVUFBUCxDQUFrQkQsUUFBbEIsRUFBNEIsT0FBTyxFQUFuQztBQUNILFNBVkw7QUFZSCxLQWI4QixFQUEvQjtBQWNIIiwiZmlsZSI6Ii4vc3JjL2pzL3V0aWxpdHkuanMuanMiLCJzb3VyY2VzQ29udGVudCI6WyIvKiFcbiAqIEBhdXRob3IgSXNpcyAoaWdyYXppYXR0bykgR3JhemlhdHRvIDxpc2lzLmdAdmFuaWxsYWZvcnVtcy5jb20+XG4gKiBAY29weXJpZ2h0IDIwMDktMjAxOCBWYW5pbGxhIEZvcnVtcyBJbmMuXG4gKiBAbGljZW5zZSBHUEwtMi4wLW9ubHlcbiAqL1xuXG5leHBvcnQgZnVuY3Rpb24gZmlyZUV2ZW50KGVsZW1lbnQsIGV2ZW50TmFtZSwgb3B0aW9ucykge1xuICAgIHZhciBldmVudCA9IGRvY3VtZW50LmNyZWF0ZUV2ZW50KFwiQ3VzdG9tRXZlbnRcIik7XG4gICAgZXZlbnQuaW5pdEN1c3RvbUV2ZW50KGV2ZW50TmFtZSwgdHJ1ZSwgdHJ1ZSwgb3B0aW9ucyk7XG4gICAgZWxlbWVudC5kaXNwYXRjaEV2ZW50KGV2ZW50KTtcbn1cblxuZXhwb3J0IGZ1bmN0aW9uIHRvZ2dsZVNjcm9sbCgpIHtcbiAgICBpZiAoJChkb2N1bWVudC5ib2R5KVswXS5zdHlsZS5vdmVyZmxvdykge1xuICAgICAgICBlbmFibGVTY3JvbGwoKTtcbiAgICB9IGVsc2Uge1xuICAgICAgICBkaXNhYmxlU2Nyb2xsKCk7XG4gICAgfVxufVxuXG5leHBvcnQgZnVuY3Rpb24gZGlzYWJsZVNjcm9sbCgpIHtcbiAgICAkKGRvY3VtZW50LmJvZHkpLmFkZENsYXNzKFwiTm9TY3JvbGxcIik7XG59XG5cbmV4cG9ydCBmdW5jdGlvbiBlbmFibGVTY3JvbGwoKSB7XG4gICAgJChkb2N1bWVudC5ib2R5KS5yZW1vdmVDbGFzcyhcIk5vU2Nyb2xsXCIpO1xufVxuXG4vKipcbiAqIFByb3ZpZGVzIHJlcXVlc3RBbmltYXRpb25GcmFtZSBpbiBhIGNyb3NzIGJyb3dzZXIgd2F5LlxuICovXG5cbmlmICghd2luZG93LnJlcXVlc3RBbmltYXRpb25GcmFtZSkge1xuICAgIHdpbmRvdy5yZXF1ZXN0QW5pbWF0aW9uRnJhbWUgPSAoZnVuY3Rpb24oKSB7XG4gICAgICAgIHJldHVybiAoXG4gICAgICAgICAgICB3aW5kb3cud2Via2l0UmVxdWVzdEFuaW1hdGlvbkZyYW1lIHx8XG4gICAgICAgICAgICB3aW5kb3cubW96UmVxdWVzdEFuaW1hdGlvbkZyYW1lIHx8XG4gICAgICAgICAgICB3aW5kb3cub1JlcXVlc3RBbmltYXRpb25GcmFtZSB8fFxuICAgICAgICAgICAgd2luZG93Lm1zUmVxdWVzdEFuaW1hdGlvbkZyYW1lIHx8XG4gICAgICAgICAgICBmdW5jdGlvbihcbiAgICAgICAgICAgICAgICAvKiBmdW5jdGlvbiBGcmFtZVJlcXVlc3RDYWxsYmFjayAqLyBjYWxsYmFjayxcbiAgICAgICAgICAgICAgICAvKiBET01FbGVtZW50IEVsZW1lbnQgKi8gZWxlbWVudFxuICAgICAgICAgICAgKSB7XG4gICAgICAgICAgICAgICAgd2luZG93LnNldFRpbWVvdXQoY2FsbGJhY2ssIDEwMDAgLyA2MCk7XG4gICAgICAgICAgICB9XG4gICAgICAgICk7XG4gICAgfSkoKTtcbn1cbiJdLCJzb3VyY2VSb290IjoiIn0=\n//# sourceURL=webpack-internal:///./src/js/utility.js\n");

/***/ })

/******/ });