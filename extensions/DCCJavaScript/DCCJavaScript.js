
/* DCC javascript --- will be added to the head elemant */


// TEMP!!!
/* NOT NEEDED RIGHT NOW
addOnloadHook (
function() {
  if (document.body.innerHTML.match('mirror')) {return false}
  if (!window.location.href.match('action=edit')) { 
    document.body.innerHTML = '<div style="position:absolute;left:10px;top:2px;z-index:10;color:red;font-size:14pt;background:yellow"\
	>This is a mirror site.</div>'+document.body.innerHTML;
	return false;
  }
  document.body.innerHTML = '<span style="z-index:10;color:red;font-size:14pt;background:yellow">This site is a mirror;\
	 edits made here will be lost.</span><br>'+document.body.innerHTML;
} 
);
*/

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


// If there is a div of class 'protected' in an document
// loaded in an iframe, replace the page's content with
// the div's content only
addOnloadHook( function() {
  var protected = document.getElementById('protected');
  if (protected && window.parent.frames.length > 0) {
    document.body.innerHTML = protected.innerHTML;
  }
} );

