/**
 * The current package
 *
 * @type {Object}
 */
var currentPackage = {
  slug: '',
  toggleUrl: '',
};
/**
 * Change to a different package
 *
 * @return {void}
 */
function changePackage() {
  $('input.include-checkbox').each(function() {
    var checkbox$ = $(this);
    var packageString = checkbox$.attr('data-packages');
    var packages = (packageString === '') ? [] : packageString.split('|');
    var exists = (packages.indexOf(currentPackage.slug) !== -1);
    checkbox$.prop('checked', exists);
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
      console.log(packages.join('|'));
      element$.attr('data-packages', packages.join('|'));
  });
}
/**
 * Ready function
 */
$(function() {
  MicroModal.init();
  var selector$ = $('#package-selector');
  var selected$ = selector$.find(':selected');
  var slug = selected$.val();
  currentPackage.slug = slug;
  currentPackage.toggleUrl = selector$.attr('data-toggle-url').replace('SLUG', slug);
  changePackage();
  selector$.on('change', function() {
    var selected$ = $(this).find(':selected');
    var slug = selected$.val();
    currentPackage.slug = slug;
    currentPackage.toggleUrl = selector$.attr('data-toggle-url').replace('SLUG', slug);
    changePackage();
  });
  $('input.include-checkbox').on('change', function() {
    togglePackage($(this));
  });
  $('#add-package-button').on('click', function(event) {
    event.stopPropagation();
    MicroModal.show('package-form-modal');
    return false;
  });
  $('#package-form-modal button.trigger-confirm').on('click', function(event) {
    event.stopPropagation();
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
          selector$.append($('<option />').val(data.package.slug).text(data.package.title));
          sortSelector(selector$);
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
    return false;
  });
});
