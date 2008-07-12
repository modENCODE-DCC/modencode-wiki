function assembleName() {
  var enforce = document.createbox.enforce_nomenclature.value;
  var title   = document.createbox.title.value;
  var prefix  = document.createbox.classifier.value;
  var lab     = document.createbox.lab.value;
  var version = document.createbox.version.value;

  if ((prefix || lab || version) && !enforce) {
    enforce = true;
  }
  if (!enforce) {
    return false;
  }

  if (title && title.length > 20) {
    alert('The core name you selected ('+title+') is too long!  Please keep it down to 20 characters or less');
    return false;
  }
  if (prefix) {
    prefix = prefix + ':';
  }
  if (lab) {
    lab = ':' + lab;
  }
  else {
    alert('Error: Select a group name from the menu');
    return false;
  }
  if (version) {
    version = ':' + version;
  }
  else {
    alert('Error: Specify a version (default: 1)');
    document.createbox.submit.disabled = true;
    return false;
  }
  document.createbox.title.value = prefix + title + lab + version;

  return true;
}