function changePackage(package) {
  var checkboxes = document.getElementsByClassName('include-checkbox');
  for (var i = 0; i < checkboxes.length; i++) {
    var packages = checkboxes[i].getAttribute('data-packages').split('|');
    if (packages.indexOf(package) !== -1) {
      checkboxes[i].checked = true;
    } else {
      checkboxes[i].checked = false;
    }
  }
}
/**
 * Run our code
 */
ready(function() {
  var selector = document.getElementById('package-selector');
  changePackage(selector[0].value);
  selector.addEventListener('change', function(event) {
    changePackage(event.target.value);
  });
  var toggle = document.getElementById('toggle-package');
  toggle.addEventListener('click', function(event) {
    event.preventDefault();
    var payload = {
      slug: 'funny',
      related: {
        content_type: 'collection',
        slug: 'silly-animal-videos',
      },
    };
    var url = toggle.getAttribute('href');
    var onLoad = function() {
      console.log('success');
    };
    var onError = function() {
      console.log('error');
    };
    postRequest(url, payload, onLoad, onError);
  });
});
