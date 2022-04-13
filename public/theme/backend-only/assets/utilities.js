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
function getRequest(url, onLoad, onError) {
  var request = new XMLHttpRequest();
  request.open('GET', url, true);
  request.onload = onLoad;
  request.onerror = onError;
  request.send();
}
