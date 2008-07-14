
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


// If there is a div of class 'protected' in an document
// loaded in an iframe, replace the page's content with
// the div's content only
addOnloadHook( function() {
  var protected = document.getElementById('protected');
  if (protected && window.parent.frames.length > 0) {
    document.body.innerHTML = protected.innerHTML;
  }
} );

