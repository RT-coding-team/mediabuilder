/**
 * This Javascript code is used for the exporter.
 */
/**
 * Find a $_GET parameter
 *
 * @param  {string} name The parameter name
 * @return {string}      The value
 */
function findGetParameter(name) {
  var result = '';
  var tmp = [];
  location.search
  .substr(1)
  .split('&')
  .forEach(function(item) {
    tmp = item.split('=');
    if (tmp[0] === name) result = decodeURIComponent(tmp[1]);
  });
  return result;
}
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
 * Notify the viewer
 *
 * @param  {string}  message   The message to display
 * @param  {Boolean} isSuccess is it a success message?
 * @return {void}
 */
function notify(message, isSuccess) {
  var klass = 'message-error';
  var label = 'Error!';
  if (isSuccess) {
    klass = 'message-success';
    label = 'Success!';
  }
  document.getElementById('message-holder').innerHTML = '<div class="message '+klass+'"><h3>'+label+'</h3><p>'+message+'</p></div>';
}
/**
 * Run our code
 */
ready(function() {
  var deleted = findGetParameter('deleted');
  if (deleted !== '') {
    if (deleted === 'true') {
      notify('The file has been deleted!', true);
    } else {
      notify('Sorry, we were unable to delete the file!', false);
    }
  }

  var links = document.querySelectorAll('.delete-link');
  for (var i = 0; i < links.length; i++) {
    links[i].addEventListener('click', function(event) {
      if (confirm('Are you sure you wish to delete this archive?')) {
        var link = event.target;
        var form = link.parentNode;
        form.submit();
      }
      event.preventDefault();
      return false;
    }, false);
  }
});
