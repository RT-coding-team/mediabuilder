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
});
