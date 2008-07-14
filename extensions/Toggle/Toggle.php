<?php

$wgHooks['OutputPageBeforeHTML'][] = 'addToggleScript';

function addToggleScript(&$out) {
  global $wgScriptPath;
  $path = "${wgScriptPath}/extensions/Toggle";
  $script = <<<END
<script type="text/javascript">var toggleImages = "${path}/images"</script>
<script type="text/javascript" src="${path}/js/toggle.js"></script>
END;

  $out->addScript($script);
  return true;
}

