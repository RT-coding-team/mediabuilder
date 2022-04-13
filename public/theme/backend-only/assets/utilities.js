/**
 * Common utility functions
 */
/**
 * On Ready function
 *
 * @param  {Function} callback The call back to call when ready.
 * @return {void}
 */
function ready(callback) {
  if (document.readyState != 'loading'){
    callback();
  } else {
    document.addEventListener('DOMContentLoaded', callback);
  }
}
