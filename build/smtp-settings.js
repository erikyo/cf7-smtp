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
  /**
   * This is the code that saves the settings when the user presses ctrl-s.
   */
  if (document.querySelector('#cf7-smtp-settings')) {
    // Save on ctrl-s keypress
    document.addEventListener('keydown', e => {
      if (e.ctrlKey && e.key === 's') {
        e.preventDefault();
        document.querySelector('#cf7-smtp-settings #submit').click();
      }
    });
  }

  /**
   * Toggle enabled advanced settings row
   *
   * @param {Array}       elements - an array of "nth" input
   * @param {HTMLElement} formElem - the form row
   * @param {boolean}     enabled  - show or hide the form row
   */
  function enableAdvanced(elements, formElem, enabled) {
    if (formElem) {
      elements.forEach(el => {
        formElem.querySelector(`tr:nth-child(${el})`).style.display = enabled ? 'table-row' : 'none';
      });
    } else {
      console.log(`Cannot find form element ${elements} of ${toString(formElem)}`);
    }
  }

  /**
   * Enables the SMTP settings
   */
  const smtpEnabled = document.querySelector('#cf7_smtp_enabled');
  const formSmtpSection = document.querySelector('#cf7-smtp-settings .form-table:first-of-type');
  enableAdvanced([2, 3, 4, 5, 6, 7], formSmtpSection, smtpEnabled.checked);
  smtpEnabled.addEventListener('click', () => {
    enableAdvanced([2, 3, 4, 5, 6, 7], formSmtpSection, smtpEnabled.checked);
  });

  /**
   *  Email Response box
   *
   *  @member {HTMLElement} formElem - the Email form used to test email functionalities
   *  @member {HTMLElement} responseBox - the wrapper for the smtp server messages
   */
  const formElem = document.querySelector('#sendmail-testform form');
  const responseBox = document.querySelector('#sendmail-response pre');
  responseBox.classList.add('enabled');

  /* Initialize the response box and show a welcome message */
  cleanOutput(responseBox, '<code>Mail Server initialization completed!</code>');

  /**
   * It takes a DOM element and a message, and sets the DOM element's innerHTML to the message, with a timestamp
   *
   * @param {HTMLElement} logWrap   - The element that will contain the log messages.
   * @param {?string}     [message] - The message to be displayed in the log.
   */
  function cleanOutput(logWrap) {
    let message = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : '';
    const date = new Date();
    logWrap.innerHTML = `<code class="logdate alignright">Log start ${date}</code>` + message;
  }
  function extractData(line) {
    const regex = /(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}) (.*)/g;
    return regex.exec(line);
  }

  /**
   * It takes a message, splits it into lines, and then adds each line to the output container with a random delay
   *
   * @param {HTMLElement}  outputContainer - the element where the output will be displayed
   * @param {string}       msg             - the message to be displayed
   * @param {boolean|null} mailSent        if the mail was sent or not
   */
  function OutputMessage(outputContainer, msg) {
    let mailSent = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : null;
    msg = msg.split(/\n/);
    let lastTimestamp = 0;
    if (msg.length) {
      msg.forEach((line, index) => {
        // TODO: regex here to search for errors

        const [string, date, text] = extractData(line);

        /* will add the lines "softly" */
        setTimeout(() => {
          if (date === lastTimestamp) {
            outputContainer.insertAdjacentHTML('beforeend', `<code>${text}</code>`);
          } else {
            // refresh the timestamp
            lastTimestamp = date;
            outputContainer.insertAdjacentHTML('beforeend', `<span class="timestamp">${date}</span><code>${text}</code>`);
          }
        }, 50 * index + Math.random() * 200);
      });
    }
    // if the mailSent flag is set (true or false) update the container class
    if (mailSent) {
      outputContainer.classList.remove('error');
      outputContainer.classList.add('ok');
    } else if (mailSent === false) {
      outputContainer.classList.remove('ok');
      outputContainer.classList.add('error');
    }
  }

  /**
   *  Send a mail with the rest api /cf7-smtp/v1/sendmail endpoint
   */
  formElem.addEventListener('submit', e => {
    e.preventDefault();
    const formData = new FormData(e.target);
    const data = {};
    data.nonce = window.smtp_settings.nonce;
    for (const [key, value] of formData.entries()) {
      data[key] = value;
    }

    /* clean the previous results*/
    cleanOutput(responseBox);
    _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_1___default()({
      path: '/cf7-smtp/v1/sendmail',
      method: 'POST',
      data
    }).then(r => {
      responseBox.insertAdjacentHTML('beforeend', `<code>${'Waiting for server response... ⏰'}</code>`);
      OutputMessage(responseBox, r.message + '\r\n** END **\r\n', r.status === 'sent');
    }).catch(err => {
      setTimeout(function () {
        _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_1___default()({
          path: '/cf7-smtp/v1/get_errors',
          method: 'POST',
          data: {
            nonce: err.nonce
          }
        }).then(resp => {
          console.log(resp.message);
          if (resp.message.errors) {
            const errorMessage = resp.message.errors.wp_mail_failed.join('\r\n');
            OutputMessage(responseBox, errorMessage + '\r\n' + err.message + '\r\n** END **', false);
          }
        }).catch(errMsg => console.log(errMsg));
      }, 2000);
    });
  });

  /**
   *  Variables needed to set SMTP connetion
   *
   *  @member {HTMLElement} formSelectDefault - cf7_smtp_preset
   *  @member {HTMLElement} formSelectHost - cf7_smtp_host
   *  @member {HTMLElement} formSelectPort - cf7_smtp_port
   */
  const formSelectDefault = document.getElementById('cf7_smtp_preset');
  const formSelectHost = document.getElementById('cf7_smtp_host');
  const formSelectPort = document.getElementById('cf7_smtp_port');

  /**
   * Sets the values of the SMTP connection according to the value selected by the user.
   */
  formSelectDefault.addEventListener('change', e => {
    const selectedEl = e.target[e.target.selectedIndex];
    if (selectedEl) {
      const authRadio = document.querySelector('.auth-' + selectedEl.dataset.auth);
      authRadio.checked = true;
      formSelectHost.value = selectedEl.dataset.host;
      formSelectPort.value = selectedEl.dataset.port;
    }
  });
};
window.onload = smtpAdmin();
}();
/******/ })()
;
//# sourceMappingURL=smtp-settings.js.map