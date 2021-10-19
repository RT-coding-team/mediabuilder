/**
 * This Javascript code is used for the exporter.
 */
/**
 * Are we processing an export?
 *
 * @type {Boolean}
 */
var currentlyProcessing = false;
/**
 * The link that handles exporting
 *
 * @type {Element}
 */
var exportLink = null;
/**
 * The icon of the exportLink
 *
 * @type {Element}
 */
var exportIcon = null;
/**
 * Number of seconds to poll server
 *
 * @type {Number}
 */
var pollRate = 10000;
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
  var klass = 'message-error';
  var label = 'Error!';
  if (isSuccess) {
    klass = 'message-success';
    label = 'Success!';
  }
  document.getElementById('message-holder').innerHTML = '<div class="message '+klass+'"><h3>'+label+'</h3><p>'+message+'</p></div>';
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
 * Set whether we are processing.  Handles the buttons correctly.
 *
 * @param {Boolean} isProcessing yes|no
 */
function setIsProcessing(isProcessing) {
  if (isProcessing) {
    currentlyProcessing = isProcessing;
    Cookies.set(
      'exporter_is_processing',
      'true',
      {
        samesite: 'Strict',
        secure: isSiteSecure(),
      },
    );
    exportLink.classList.add('btn-disabled');
    exportIcon.setAttribute('class', 'fas fa-spinner fa-spin');
  } else {
    currentlyProcessing = isProcessing;
    Cookies.remove('exporter_is_processing');
    exportLink.classList.remove('btn-disabled');
    exportIcon.setAttribute('class', 'fas fa-fw fa-wrench');
  }
}
/**
 * Check the server for a status update (it will continue to run until finished)
 *
 * @return {void}
 */
function statusUpdate() {
  var request = new XMLHttpRequest();
  request.open('GET', '/files/exports/export_progress.json', true);

  request.onload = function() {
    if (this.status >= 200 && this.status < 400) {
      var data = JSON.parse(this.response);
      // Sorting wont work because time can be a microsecond
      var latest = data[data.length - 1];
      if (latest.completed) {
        notify('The export process has completed! The page will refresh.', true);
        setIsProcessing(false);
        setTimeout(function() {
          location.reload();
        }, 2000);
      } else if (latest.isError) {
        notify(latest.message, false);
        setIsProcessing(false);
      } else {
        var prettyDate = prettyTimestamp(latest.timestamp);
        document.getElementById('message-holder').innerHTML = '<div class="message message-progress"><h3>Export Progress</h3><p>'+latest.message+'</p><p class="timestamp">'+prettyDate+'</p></div>';
        setTimeout(function() {
          statusUpdate();
        }, pollRate);
      }
    } else {
    }
  }

  request.onerror = function() {
    document.getElementById('message-holder').innerHTML = '<div class="message message-progress"><h3>Export Progress</h3><p>Server failed to respond. Trying again.</p></div>';
    statusUpdate();
  };

  request.send();
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

  exportLink = document.getElementById('export-starter');
  exportIcon = exportLink.querySelectorAll('i')[0];
  exportLink.addEventListener('click', function(event) {
    event.preventDefault();
    if (currentlyProcessing) {
      return false;
    }
    if (confirm('This may take a while to build. You can close the window and come back later. Do you want to continue?')) {
      setIsProcessing(true);
      var request = new XMLHttpRequest();
      request.open('GET', exportLink.getAttribute('href'), true);

      request.onload = function() {
        if (this.status >= 200 && this.status < 400) {
          notify('The export process has started!', true);
        } else {
          notify('There was a problem with the export process!', false);
          isProcessing = false;
          exportLink.classList.remove('btn-disabled');
          exportIcon.setAttribute('class', 'fas fa-fw fa-wrench');
        }
      }
      request.onerror = function() {
        notify('There was a problem with the export process!', false);
        isProcessing = false;
        exportLink.classList.remove('btn-disabled');
        exportIcon.setAttribute('class', 'fas fa-fw fa-wrench');
      };

      request.send();
    }
    return false;
  });
  var processingCookie = Cookies.get('exporter_is_processing');
  if (processingCookie === 'true') {
    setIsProcessing(true);
    statusUpdate();
  }
});
