/**
 * Common utility functions
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
 * Are we using https?
 *
 * @return {Boolean} yes|no
 */
function isSiteSecure() {
  return (window.location.protocol === 'https:');
}
/**
 * Notify the viewer
 *
 * @param  {string}  message   The message to display
 * @param  {Boolean} isSuccess is it a success message?
 * @return {void}
 */
function notify(message, isSuccess) {
  var klass = 'alert-danger';
  var label = 'Error!';
  if (isSuccess) {
    klass = 'alert-success';
    label = 'Success!';
  }
  var html = '<div class="alert '+klass+'" role="alert"><h4 class="alert-heading">'+label+'</h4><p>'+message+'</p></div>';
  $('#message-holder').html(html);
}
/**
 * Pad a value with a zero.
 *
 * @param  {Number} var The number to zero pad
 * @return {string}     The zero padded value
 */
function pad(value) {
    if(value < 10) {
        return '0' + value;
    } else {
        return value.toString();
    }
}
/**
 * Convert a timestamp into a pretty format
 *
 * @param  {Number} timestamp The timestamp
 * @return {string}           The result
 * @link https://stackoverflow.com/a/6078873/4638563
 */
function prettyTimestamp(timestamp) {
  var date = new Date(timestamp*1000);
  var months = ['Jan.','Feb.','Mar.','Apr.','May','June','July','Aug.','Sept.','Oct.','Nov.','Dec.'];
  var year = date.getFullYear();
  var month = months[date.getMonth()];
  var day = date.getDate();
  var hour = date.getHours();
  var meridian = 'AM';
  if (hour > 12) {
    hour -= 12;
    meridian = 'PM';
  }
  var min = pad(date.getMinutes());
  var sec = pad(date.getSeconds());
  return month + ' ' + day + ', ' + year + ' @ ' + hour + ':' + min + ':' + sec + ' ' + meridian;
}
/**
 * Sort the selector in alphabetical order
 *
 * @param  {object} $selector The JQuery object of the selector
 * @return {void}
 */
function sortSelector($selector) {
  var selected = $selector.val();
  var $options = $selector.children('option');
  $options.detach().sort(function(a,b) {
    var at = $(a).text();
    var bt = $(b).text();
    return (at > bt)?1:((at < bt)?-1:0);
  });
  $selector.append($options);
  $selector.val(selected);
}
