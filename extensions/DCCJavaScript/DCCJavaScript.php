<?php

$wgHooks['OutputPageBeforeHTML'][] = 'addExtraScripts';


function addExtraScripts(&$out) {
  global $wgScriptPath;
  $path = '<script type="text/javascript" src="'.$wgScriptPath.'/extensions/';
  $out->addScript(
      $path . 'DBFields/yui/build/yahoo-dom-event/yahoo-dom-event.js"></script>' . 
      $path . 'DCCJavaScript/DCCJavaScript.js"></script>' 
		  );
  return true;
}

