/**
 * The current package
 *
 * @type {Object}
 */
var currentPackage = {
  name: '',
  slug: '',
  toggleUrl: '',
};
/**
 * Add the package
 *
 * @return {void}
 */
function addPackage() {
  var $selector = $('#package-selector');
  var $input = $('#package-form #form-package-name');
  var name = $input.val();
  $.ajax({
    type: 'POST',
    url: $('#package-form').attr('action'),
    data: JSON.stringify({'name': name}),
    dataType: 'json',
  })
    .done(function(data, textStatus, xhr) {
      if (xhr.status === 201) {
        notify('The package has been created.', true);
        var slug = data.package.slug;
        currentPackage.name = data.package.name;
        currentPackage.slug = slug;
        currentPackage.toggleUrl = $selector.attr('data-toggle-url').replace('SLUG', slug);
        $selector.append($('<option />').val(slug).text(data.package.name));
        sortSelector($selector);
        $selector.val(slug);
        changePackage();
      } else {
        notify('There was a problem creating the package. Please try again later.', false);
      }
      MicroModal.close('package-form-modal');
      $input.val('');
    })
    .fail(function() {
      notify('There was a problem creating the package. Please try again later.', false);
      MicroModal.close('package-form-modal');
      $input.val('');
    });
}
/**
 * Change to a different package
 *
 * @return {void}
 */
function changePackage() {
  $('input.include-checkbox').each(function() {
    var $checkbox = $(this);
    var packageString = $checkbox.attr('data-packages');
    var packages = (packageString === '') ? [] : packageString.split('|');
    var exists = (packages.indexOf(currentPackage.slug) !== -1);
    $checkbox.prop('checked', exists);
  });
}
/**
 * Delete a package
 *
 * @return {void}
 */
function deletePackage() {
  var $selector = $('#package-selector');
  var deleteUrl = $('#delete-package-button')
    .attr('data-delete-url')
    .replace('SLUG', currentPackage.slug);
  var prevName = currentPackage.name;
  $.ajax({
    type: 'POST',
    url: deleteUrl+'?_method=DELETE',
    dataType: 'json',
  })
    .done(function(data, textStatus, xhr) {
      if (xhr.status === 204) {
        $selector.children('[value="'+currentPackage.slug+'"]').remove();
        var $first = $selector.prop('selectedIndex',0);;
        var slug = $first.val();
        currentPackage.name = $first.text();
        currentPackage.slug = slug;
        currentPackage.toggleUrl = $selector.attr('data-toggle-url').replace('SLUG', slug);
        MicroModal.close('confirm-delete-modal');
        notify('The package '+prevName+' has been deleted.', true);
      } else {
        MicroModal.close('confirm-delete-modal');
        notify('The package '+prevName+' could not be deleted.', false);
      }
    })
    .fail(function() {
      MicroModal.close('confirm-delete-modal');
      notify('The package '+prevName+' could not be deleted.', false);
    });
}
/**
 * Send a request to toggle the package
 *
 * @param  {object}   element$  The option element selected
 * @return {void}
 */
function togglePackage(element$) {
  var payload = {
    slug: currentPackage.slug,
    related: {
      content_type: element$.attr('data-content-type'),
      slug: element$.attr('data-slug'),
    },
  };
  $.ajax({
    type: 'POST',
    url: currentPackage.toggleUrl,
    data: JSON.stringify(payload),
    dataType: 'json',
  }).done(function(data) {
    var packageString = element$.attr('data-packages');
    var packages = (packageString === '') ? [] : packageString.split('|');
    if (data.state === 'removed') {
        //remove the package
        var index = packages.indexOf(currentPackage.slug);
        if (index !== -1) {
          packages.splice(index, 1);
        }
      } else {
        //add the package
        packages.push(currentPackage.slug);
      }
      element$.attr('data-packages', packages.join('|'));
  });
}
function updatePackage() {
  var $selector = $('#package-selector');
  var $input = $('#package-form #form-package-name');
  var name = $input.val();
  $.ajax({
    type: 'POST',
    url: $('#package-form').attr('action')+'?_method=PATCH',
    data: JSON.stringify({'name': name}),
    dataType: 'json',
  })
    .done(function(data, textStatus, xhr) {
      if (xhr.status === 201) {
        var current = $selector.children('option[value="'+currentPackage.slug+'"]').first();
        current.replaceWith($('<option/>').text(data.package.name).val(data.package.slug));
        $selector.val(data.package.slug);
        // update packages on the page
        $('input.include-checkbox').each(function() {
          var $checkbox = $(this);
          var packageString = $checkbox.attr('data-packages');
          var packages = (packageString === '') ? [] : packageString.split('|');
          var index = packages.indexOf(currentPackage.slug);
          if (index !== -1) {
            packages[index] = data.package.slug;
          }
          $checkbox.attr('data-packages', packages.join('|'));
        });
        notify('The package <strong>'+currentPackage.name+'</strong> has been renamed to <strong>'+data.package.name+'</strong>.', true);
        currentPackage.name = data.package.name;
        currentPackage.slug = data.package.slug;
        currentPackage.toggleUrl = $selector.attr('data-toggle-url').replace('SLUG', data.package.slug);
        changePackage();
      } else {
        notify('There was a problem updating the package. Please try again later.', false);
      }
      MicroModal.close('package-form-modal');
    })
    .fail(function() {
      notify('There was a problem updating the package. Please try again later.', false);
      MicroModal.close('package-form-modal');
    });
}
/**
 * Ready function
 */
$(function() {
  MicroModal.init();
  var $selector = $('#package-selector');
  var $selected = $selector.find(':selected');
  var slug = $selected.val();
  currentPackage.name = $selected.text();
  currentPackage.slug = slug;
  currentPackage.toggleUrl = $selector.attr('data-toggle-url').replace('SLUG', slug);
  changePackage();
  $selector.on('change', function() {
    var $selected = $(this).find(':selected');
    var slug = $selected.val();
    currentPackage.name = $selected.text();
    currentPackage.slug = slug;
    currentPackage.toggleUrl = $selector.attr('data-toggle-url').replace('SLUG', slug);
    changePackage();
  });
  $('input.include-checkbox').on('change', function() {
    togglePackage($(this));
  });
  // handle the add button
  $('#add-package-button').on('click', function(event) {
    event.stopPropagation();
    $('#package-form').attr('action', $(this).attr('data-add-url')).attr('data-task', 'add-package');
    $('#package-form-modal').find('.modal__title').first().text('Add Package');
    $('#form-package-name').val('');
    MicroModal.show('package-form-modal');
    return false;
  });
  $('#package-form-modal button.trigger-confirm').on('click', function(event) {
    event.stopPropagation();
    var task = $('#package-form').attr('data-task');
    if (task === 'add-package') {
      addPackage();
    } else if (task === 'update-package') {
      updatePackage();
    }
    return false;
  });
  // Handle the delete button
  $('#delete-package-button').on('click', function(event) {
    event.stopPropagation();
    $('#confirm-delete-modal')
      .find('.modal__content')
      .first()
      .html('<p>Are you sure you wish to delete the package <strong>'+currentPackage.name+'</strong>?</p><p class="note">* This will not delete the Collections or Singles.</p>');
    MicroModal.show('confirm-delete-modal');
    return false;
  });
  $('#confirm-delete-modal button.trigger-confirm').on('click', function(event) {
    event.stopPropagation();
    deletePackage();
    return false;
  });
  // handle the edit button
  $('#update-package-button').on('click', function(event) {
    event.stopPropagation();
    $('#package-form')
      .attr('action', $(this).attr('data-update-url').replace('SLUG', currentPackage.slug))
      .attr('data-task', 'update-package');
    $('#package-form-modal').find('.modal__title').first().text('Edit Package - '+currentPackage.name);
    $('#form-package-name').val(currentPackage.name);
    MicroModal.show('package-form-modal');
    return false;
  });
});
