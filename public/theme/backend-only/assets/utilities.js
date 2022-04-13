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
/**
 * Make an http get request
 *
 * @param  {string}     url     The url to call
 * @param  {function}   onLoad  Callback for onload
 * @param  {function}   onError Callback for onerror
 * @return {void}
 */
function getRequest(url, onLoad, onError) {
  var request = new XMLHttpRequest();
  request.open('GET', url, true);
  request.onload = onLoad;
  request.onerror = onError;
  request.send();
}
/**
 * Make an http post request
 *
 * @param  {string}     url     The url to call
 * @param  {object}     payload The payload to send
 * @param  {function}   onLoad  Callback for onload
 * @param  {function}   onError Callback for onerror
 * @return {void}
 */
function postRequest(url, payload, onLoad, onError) {
  var request = new XMLHttpRequest();
  request.open('POST', url, true);
  request.onload = onLoad;
  request.onerror = onError;
  request.send(JSON.stringify(payload));
}
