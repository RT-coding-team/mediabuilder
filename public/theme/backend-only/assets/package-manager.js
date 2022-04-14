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
  var checkboxes = document.getElementsByClassName('include-checkbox');
  for (var i = 0; i < checkboxes.length; i++) {
    var packages = checkboxes[i].getAttribute('data-packages').split('|');
    if (packages.indexOf(currentPackage.slug) !== -1) {
      checkboxes[i].checked = true;
    } else {
      checkboxes[i].checked = false;
    }
  }
}
/**
 * Send a request to toggle the package
 *
 * @param  {string}   contentType   The content type: single|collection
 * @param  {string}   contentSlug   The slug of the content
 * @param  {function} onLoad        The onload callback
 * @return {void}
 */
function togglePackage(contentType, contentSlug, onLoad) {
  var payload = {
    slug: currentPackage.slug,
    related: {
      content_type: contentType,
      slug: contentSlug,
    },
  };
  var onLoad = onLoad;
  var onError = function() {
    console.log('error');
  };
  postRequest(currentPackage.toggleUrl, payload, onLoad, onError);
}
/**
 * Run our code
 */
ready(function() {
  var selector = document.getElementById('package-selector');
  var selected = selector.options[selector.selectedIndex];
  currentPackage.slug = selected.value;
  currentPackage.toggleUrl = selected.getAttribute('data-toggle-url');
  changePackage();
  selector.addEventListener('change', function(event) {
    var selected = this.options[this.selectedIndex];
    currentPackage.slug = selected.value;
    currentPackage.toggleUrl = selected.getAttribute('data-toggle-url');
    changePackage();
  });
  var checkboxes = document.getElementsByClassName('include-checkbox');
  for (var i = 0; i < checkboxes.length; i++) {
    var checkbox = checkboxes[i];
    checkbox.addEventListener('change', function(event) {
      var slug = this.getAttribute('data-slug');
      var contentType = this.getAttribute('data-content-type');
      var packageString = this.getAttribute('data-packages');
      var packages = (packageString === '') ? [] : packageString.split('|');
      var element = this;
      var onLoad = function() {
        if (this.status === 200) {
          var data = JSON.parse(this.responseText);
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
          element.setAttribute('data-packages', packages.join('|'));
        }
      };
      togglePackage(contentType, slug, onLoad);
    });
  }
});
