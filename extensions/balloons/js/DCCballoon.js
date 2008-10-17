///////////////////////////////////////////////////
// Constructor for DCCBalloon class, a subclass  //
// of Balloon with DCC mods                     //
///////////////////////////////////////////////////
var DCCBalloon = function() {
  // Location of AJAX handler for HelpHeader templates
  this.helpHeaderUrl = "http://wiki.modencode.org/project/extensions/GetHelp/vb_get_summary.php?";
  this.displayTime = false;
  this.maxWidth = 350;
  return this;
}

// inherit from the Balloon class
DCCBalloon.prototype = new Balloon();

///////////////////////////////////////////////////////
// AJAX widget to fill the balloons
///////////////////////////////////////////////////////
DCCBalloon.prototype.getContents = function(section) {
  if (section.match(/^helpheader:\S+$/i)) {
    var caption = section.slice(11);
    pieces = caption.split("#", 2);
    this.activeUrl = this.helpHeaderUrl + "page=" + pieces[0] + "&section=" + pieces[1];
    caption = '';
  }
  
  // just pass it back if no AJAX handler is required.
  if (!this.helpUrl && !this.activeUrl) return section;

  // or if the contents are already loaded
  if (this.loadedFromElement) return section;

  // inline URL takes precedence
  var url = this.activeUrl || this.helpUrl;
  url    += this.activeUrl ? '' : '?section='+section;

  // activeUrl is meant to be single-use only
  this.activeUrl = null;

  var ajax;
  if (window.XMLHttpRequest) {
    ajax = new XMLHttpRequest();
  } else {
    ajax = new ActiveXObject("Microsoft.XMLHTTP");
  }

  if (ajax) {
    ajax.open("GET", url, false);
    ajax.onreadystatechange=function() {
      //alert(request.readyState);
    };
    try {
      ajax.send(null);
    }
    catch (e) {
      alert(e);
    }

    return ajax.responseText || section;
  }
  else {
    return section;
  }
}


