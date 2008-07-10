
/* DCC javascript --- will be added to the head elemant */



// support for links that will open in a new tab/window
addOnloadHook( function() {
     var pops = function( elems ) {
         for (var i=0; i<elems.length; i++) {
           if (elems[i].className == 'pops') {
             var anchs = elems[i].getElementsByTagName('a');
             for (var j=0; j<anchs.length; j++) anchs[j].target = '_blank';
	   }
         }
     };
     pops( document.getElementsByTagName( 'span' ) );
 } );


// support for toggle sections
var doToggle = function (el) {
  var id = el.getAttribute('id');
  var base = id.match(/([^:]+)/);
  var vis  = id.match(/hide/);
  var on   = vis ? base[0]+':visible' : base[0]+':hidden';
  var off  = vis ? base[0]+':hidden'  : base[0]+':visible';
  YAHOO.util.Dom.setStyle(on,'display','block');
  YAHOO.util.Dom.setStyle(off,'display','none');	
}

var doToggleAll = function (vis) {
    var divs = document.getElementsByTagName('div');
    for (var i=0; i<divs.length; i++) {
	var id = divs[i].getAttribute('id') || '';
	if (vis && id.match(/:hidden/)) {
	  YAHOO.util.Dom.setStyle(divs[i],'display','block');
        }
	else if (vis && id.match(/:visible/)) {
	  YAHOO.util.Dom.setStyle(divs[i],'display','none');
        }
        else if (!vis && id.match(/:hidden/)) {
          YAHOO.util.Dom.setStyle(divs[i],'display','none');
        }
        else if (!vis && id.match(/:visible/)) {
          YAHOO.util.Dom.setStyle(divs[i],'display','block');
        }
    }
}

addOnloadHook( function() {
  var plus = '<img src="http://wiki.modencode.org/project/uploads/2/2f/Plus.png"> ';
  var minus = '<img src="http://wiki.modencode.org/project/uploads/e/ee/Minus.png"> ';

  var toggle = function( el ) {
      var elClass = el.className;
      if (elClass == 'switch') {
        var id     = el.getAttribute('id');
        var elName = el.getAttribute('title') || id;
	var html   = el.innerHTML;
	var indent = el.getAttribute('indent');

 	el.innerHTML = '';

	var hide   = document.createElement('div');
	var show   = document.createElement('div');
	var doShow = document.createElement('span');
	var doHide = document.createElement('span');
	hide.setAttribute('id',id+':hidden');
        show.setAttribute('id',id+':visible');
	doHide.setAttribute('id',id+':hide');
	doShow.setAttribute('id',id+':show');

	indent = indent ? '&nbsp;&nbsp;&nbsp;&nbsp;' : '';
	doHide.innerHTML = indent + minus + elName;
	doShow.innerHTML = indent + plus  + elName;
	
        doHide.setAttribute('onclick','doToggle(this)');
	doShow.setAttribute('onclick','doToggle(this)');

        YAHOO.util.Dom.setStyle(doShow,'cursor','pointer');
        YAHOO.util.Dom.setStyle(doShow,'color','blue');
        YAHOO.util.Dom.setStyle(doHide,'cursor','pointer');
        YAHOO.util.Dom.setStyle(doHide,'color','blue');
        YAHOO.util.Dom.setStyle(show,'display','block');
        YAHOO.util.Dom.setStyle(hide,'display','none');

        show.appendChild(doShow);
        hide.appendChild(doHide);
        el.appendChild(show);
        el.appendChild(hide);
        hide.innerHTML = hide.innerHTML + '<br>' + html;
      }               
      else if (elClass == 'switch_global') {
        var openMsg  = el.getAttribute('open_title') || 'Open all sections';
	var closeMsg = el.getAttribute('close_title')|| 'Close all sections';

        var hide   = document.createElement('div');
        var show   = document.createElement('div');        
        var doShow = document.createElement('span');
        var doHide = document.createElement('span');
        hide.setAttribute('id',id+':hidden');
        show.setAttribute('id',id+':visible');
        doHide.setAttribute('id',id+':hide');
        doShow.setAttribute('id',id+':show');
        
        doHide.innerHTML = minus + closeMsg;
        doShow.innerHTML = plus  + openMsg;

        doHide.setAttribute('onclick','doToggleAll(0)');
        doShow.setAttribute('onclick','doToggleAll(1)');

        YAHOO.util.Dom.setStyle(doShow,'cursor','pointer');
        YAHOO.util.Dom.setStyle(doShow,'color','blue');
        YAHOO.util.Dom.setStyle(doHide,'cursor','pointer');
        YAHOO.util.Dom.setStyle(doHide,'color','blue');
        YAHOO.util.Dom.setStyle(show,'display','block');
        YAHOO.util.Dom.setStyle(hide,'display','none');

        show.appendChild(doShow);
        hide.appendChild(doHide);
        el.appendChild(show);
        el.appendChild(hide);
      }
    }
    
    var divs = document.getElementsByTagName('div');
    for (var i=0; i<divs.length; i++) toggle(divs[i]);
}
);


// Id there is a div of class 'protected' in an document
// loaded in an iframe, replce the pages content with
// the div's content only
addOnloadHook( function() {
  var protected = document.getElementById('protected');
  if (protected && window.parent.frames.length > 0) {
    document.body.innerHTML = protected.innerHTML;
  }
} );

