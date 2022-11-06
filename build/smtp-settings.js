/******/ (function() { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./src/styles/settings.scss":
/*!**********************************!*\
  !*** ./src/styles/settings.scss ***!
  \**********************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ }),

/***/ "@wordpress/api-fetch":
/*!**********************************!*\
  !*** external ["wp","apiFetch"] ***!
  \**********************************/
/***/ (function(module) {

module.exports = window["wp"]["apiFetch"];

/***/ })

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/compat get default export */
/******/ 	!function() {
/******/ 		// getDefaultExport function for compatibility with non-harmony modules
/******/ 		__webpack_require__.n = function(module) {
/******/ 			var getter = module && module.__esModule ?
/******/ 				function() { return module['default']; } :
/******/ 				function() { return module; };
/******/ 			__webpack_require__.d(getter, { a: getter });
/******/ 			return getter;
/******/ 		};
/******/ 	}();
/******/ 	
/******/ 	/* webpack/runtime/define property getters */
/******/ 	!function() {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = function(exports, definition) {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	}();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	!function() {
/******/ 		__webpack_require__.o = function(obj, prop) { return Object.prototype.hasOwnProperty.call(obj, prop); }
/******/ 	}();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	!function() {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = function(exports) {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	}();
/******/ 	
/************************************************************************/
var __webpack_exports__ = {};
// This entry need to be wrapped in an IIFE because it need to be isolated against other modules in the chunk.
!function() {
/*!**********************************!*\
  !*** ./src/cf7-smtp-settings.js ***!
  \**********************************/
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _styles_settings_scss__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./styles/settings.scss */ "./src/styles/settings.scss");
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/api-fetch */ "@wordpress/api-fetch");
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_1__);


_wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_1___default().use(_wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_1___default().createNonceMiddleware(window.smtp_settings.nonce));
const smtpAdmin = () => {
  console.log('smtp-mail init');

  /* This is the code that saves the settings when the user presses ctrl-s. */
  if (document.querySelector('#cf7-smtp-settings')) {
    // save on ctrl-s keypress
    document.addEventListener('keydown', e => {
      if (e.ctrlKey && e.key === 's') {
        e.preventDefault();
        document.querySelector('#cf7-smtp-settings #submit').click();
      }
    });
  }
  function enableAdvanced(formElem, enabled) {
    formElem.querySelector('tr:nth-child(3)').style.display = enabled ? 'table-row' : 'none';
    formElem.querySelector('tr:nth-child(4)').style.display = enabled ? 'table-row' : 'none';
  }
  const smtpAdvancedOptions = document.querySelector('#cf7_smtp_advanced');
  const formAdvancedSection = document.querySelector('#cf7-smtp-settings .form-table:last-of-type');
  enableAdvanced(formAdvancedSection, smtpAdvancedOptions.checked);
  smtpAdvancedOptions.addEventListener('click', () => {
    enableAdvanced(formAdvancedSection, smtpAdvancedOptions.checked);
  });
  const formElem = document.querySelector('#sendmail-testform form');
  const responseBox = document.querySelector('#sendmail-response code');
  formElem.addEventListener('submit', e => {
    e.preventDefault();
    const formData = new FormData(e.target);
    const data = {};
    data.nonce = window.smtp_settings.nonce;
    for (const [key, value] of formData.entries()) {
      data[key] = value;
    }
    _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_1___default()({
      path: '/cf7-smtp/v1/sendmail',
      method: 'POST',
      data
    }).then(r => {
      if (r.message) {
        console.log(r.message);
        responseBox.innerHTML = r.message;
      } else {
        console.log(r);
      }
    });
  });
};
window.onload = smtpAdmin();
}();
/******/ })()
;
//# sourceMappingURL=smtp-settings.js.map