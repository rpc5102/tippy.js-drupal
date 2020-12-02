/**
 * @file
 * Auto-init script ported from tippy.js v4
 * 
 * @requires popper.js
 * @requires tippy.js
 */
(function () {
  document.addEventListener("DOMContentLoaded", function () {
    [].slice.call(document.querySelectorAll('[data-tippy-content]')).forEach(function (el) {
      var content = el.getAttribute('data-tippy-content');
      if (content) {
        tippy(el, {
          content: content
        });
      }
    });
  });
})();
