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
 * Set whether we are processing.  Handles the buttons correctly.
 *
 * @param {Boolean} isProcessing yes|no
 */
function setIsProcessing(isProcessing) {
  var $trigger = $('#export-starter');
  var $icon = $trigger.children('i').first();
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
    $trigger.addClass('btn-disabled');
    $icon.attr('class', 'fas fa-spinner fa-spin');
  } else {
    currentlyProcessing = isProcessing;
    Cookies.remove('exporter_is_processing');
    $trigger.removeClass('btn-disabled');
    $icon.attr('class', 'fas fa-fw fa-wrench');
  }
}
function startExport() {
  if (currentlyProcessing) {
    return false;
  }
  var $trigger = $('#export-starter');
  var $icon = $trigger.children('i').first();
  var package = $('#package-selector').val();
  var url = $trigger.attr('href');
  if (package !== 'all') {
    // We remove last slash if exists
    url = url.replace(/\/$/, '')+'/'+package;
  }
  setIsProcessing(true);
  $.get(url)
    .done(function(data, textStatus, xhr) {
      if (xhr.status >= 200 && xhr.status < 400) {
        notify('The export process has started!', true);
        setTimeout(function() {
          statusUpdate();
        }, pollRate);
      } else {
        notify('There was a problem with the export process!', false);
        isProcessing = false;
        $trigger.removeClass('btn-disabled');
        $icon.attr('class', 'fas fa-fw fa-wrench');
      }
    })
    .fail(function() {
      notify('There was a problem with the export process!', false);
      isProcessing = false;
      $trigger.removeClass('btn-disabled');
      $icon.attr('class', 'fas fa-fw fa-wrench');
    });
}
/**
 * Check the server for a status update (it will continue to run until finished)
 *
 * @return {void}
 */
function statusUpdate() {
  $.get('/files/exports/export_progress.json')
    .done(function(data, textStatus, xhr) {
      if (xhr.status >= 200 && xhr.status < 400) {
        var latest = data[data.length - 1];
        if (latest.completed) {
          setIsProcessing(false);
          if (latest.counter > 0) {
            notify('The export process has completed! The page will refresh.', true);
            setTimeout(function() {
              location.reload();
            }, 2000);
          } else {
            notify('No packages were created.  Are you sure you added collections or singles to this package?', false);
          }
        } else if (latest.isError) {
          notify(latest.message, false);
          setIsProcessing(false);
        } else {
          var prettyDate = prettyTimestamp(latest.timestamp);
          var html = '<div class="alert alert-info" role="alert"><h4 class="alert-heading">Export Progress</h4><p>'+latest.message+'</p><p class="timestamp">'+prettyDate+'</p></div>';
          $('#message-holder').html(html);
          setTimeout(function() {
            statusUpdate();
          }, pollRate);
        }
      }
    })
    .fail(function() {
      notify('Server failed to respond. Trying again.', false);
      statusUpdate();
    });
}
/**
 * Ready function
 */
$(function() {
  MicroModal.init();
  var deleted = findGetParameter('deleted');
  if (deleted !== '') {
    if (deleted === 'true') {
      notify('The file has been deleted!', true);
    } else {
      notify('Sorry, we were unable to delete the file!', false);
    }
  }
  // Handle deleting of files
  $('.delete-link').on('click', function(event) {
    event.stopPropagation();
    var $form = $(this).closest('form');
    $('#confirm-delete-modal').attr('data-to-delete', $form.attr('data-file'));
    MicroModal.show('confirm-delete-modal');
    return false;
  });
  $('#confirm-delete-modal button.trigger-confirm').on('click', function(event) {
    event.stopPropagation();
    var toDelete = $('#confirm-delete-modal').attr('data-to-delete');
    $('form[data-file="'+toDelete+'"]').submit();
    return false;
  });
  // Handle the exporting
  $('#export-starter').on('click', function() {
    event.stopPropagation();
    MicroModal.show('confirm-export-modal');
    return false;
  });
  $('#confirm-export-modal button.trigger-confirm').on('click', function(event) {
    event.stopPropagation();
    MicroModal.close('confirm-export-modal');
    startExport();
    return false;
  });

  var processingCookie = Cookies.get('exporter_is_processing');
  if (processingCookie === 'true') {
    setIsProcessing(true);
    statusUpdate();
  }
});
