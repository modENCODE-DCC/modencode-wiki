<?php

# This is a wrapper for FCKeditor.  It adds 'rich edit' tabs and section edit links
# to access the extension with an obligate fck=1 URL parameter.
# It currently understands disabling of FCKeditor in user preferences,
# in which case there will be no visible sign of the rich editor.
# It does not yet recognize the __NORICHEDITOR__ keyword or namespace-specific
# disabling but this is not a serious issue, as the FCKeditor will only load
# if you specifically ask for it by clicking the "rich edit" links.

# To activate: add the line below to LocalSettings.pm
#require_once("$IP/extensions/FCKeditor/RichEditor.php");

# NOTE:
# If you have previously installed FCKeditor, remove the line
# in LocalSettings.php that includes FCKeditor.php

# Load up FCKeditor as usual
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . "FCKeditor.php";

$wgHooks['SkinTemplateContentActions'][]     = 'richEditTab';
$wgHooks['OutputPageBeforeHTML'][]           = 'richEditLink';
$wgHooks['EditPage::showEditForm:initial'][] = 'richEditMessage';

# add a rich edit link for each section
# uses javascript to make sure rich editing is enabled
# after all other HTML processing is done
function richEditLink(&$out, &$text) {
  global $wgUser,$wgOut,$mediaWiki;
  if ($mediaWiki->getVal('Action') == 'edit') {
    return true;
  }
  $script = <<<END
<script type="text/javascript">
addOnloadHook( function() {
  var newRichTitle = 'use FCKeditor, a rich-text wiki editor';
  var richEditLink = function() {
    if (!document.body.innerHTML.match('rich edit')) return false;
    var tags = document.getElementsByTagName('span');
    for (var i = 0; i < tags.length; i++) {
      var c = tags[i];
      if (c.className && c.className.match(/editsection/i)) {
	var links = c.getElementsByTagName('a');
	if (links[0]) {
	  var href = links[0].getAttribute('href');
	  href = href + '&fck=1';
	  var newLink = ' [<a href="'+href+'" title="'+newRichTitle+'">rich edit</a>]';
	  c.innerHTML += newLink;
      	}
      }
    }
  };
  richEditLink();
} );
</script>
END;

  $wgOut->addScript($script);
  return true;
}

function richEditMessage($form) {
  global $wgOut,$mediaWiki;

  if (false !== strpos($form->textbox1, "__NORICHEDITOR__")) {
    return true;
  }

  if (!$_GET{fck}) {
    return true;
  }

  $script = <<<END
<script type="text/javascript">
addOnloadHook( function() {
  var editMessage = function() {
    // do not go on if we have no rich edit
    if (!window.location.href.match('fck=1')) return false;
    if (!document.body.innerHTML.match('rich edit')) return false;

    var newTitle = '<big><b>This is FCKeditor, a rich-text wiki editor</b></big><br>\
                    <b>Alternatives:</b> Click the "Wikitext" button below to edit \
                    the wiki source code; Use the "edit" tab above for the normal editor';
    newTitle = '<div style="background:yellow;border: 1px solid red">'+newTitle+'</div>';
    var sectionEdit = /(<h1.+?>Editing[^\<]+<\/h1>)/i;
    var replacement = "$1"+newTitle;
    var c = document.getElementById('content');
    var cHTML = c.innerHTML.replace(sectionEdit,replacement);
    c.innerHTML = cHTML;
  };
  editMessage();
} );
</script>
END;
  $wgOut->addScript($script);

  return true;
}

# Add a new "rich edit" tab.
function richEditTab(&$content_actions){
  global $wgTitle, $wgUser;
  if ($wgUser->getOption( 'riched_disable')) {
    return true;
  }

  $new_array = array();

  foreach ($content_actions as $key => $value) {
    if ($key == 'edit') {
      $new_array['edit'] = $value;
      $new_array['edit2'] = Array(
				 'text' => "rich edit",
				 'href' => $wgTitle->getFullURL('action=edit').'&fck=1'
				 );
    }
    else {
      $new_array[$key] = $value;
    }
  }
  $content_actions = $new_array;

  return true;
}

# subclass the main editor class in add an obligate 'fck' URL argument
class FCKeditor_Tab extends FCKeditor_MediaWiki {
  public function onEditPageShowEditFormInitial( $form ) {
    global $wgUser, $wgFCKEditorIsCompatible, $wgTitle;

    if (!$wgUser->getOption( 'showtoolbar' ) || $wgUser->getOption( 'riched_disable' ) || !$wgFCKEditorIsCompatible) {
      return true;
    }

    if (in_array($wgTitle->getNamespace(), $this->getExcludedNamespaces())) {
      return true;
    }

    if (false !== strpos($form->textbox1, "__NORICHEDITOR__")) {
      return true;
    }

    if (!$_GET{fck}) {
      return true;
    }

    parent::onEditPageShowEditFormInitial( $form );
    return false;
  }

  private function getExcludedNamespaces() {
    global $wgUser;

    if ( is_null( $this->excludedNamespaces ) ) {
      $this->excludedNamespaces = array();
      foreach ( self::$nsToggles as $toggle ) {
	if ( $wgUser->getOption( $toggle ) ) {
	  $this->excludedNamespaces[] = constant(strtoupper(str_replace("riched_disable_", "", $toggle)));
	}
      }
    }

    return $this->excludedNamespaces;
  }


}

# IMPORTANT: in FCKeditor.php, comment out the last two lines
# that instantiatiate the FCKeditor_MediaWiki object and register its hooks
$oFCKeditorExtension = new FCKeditor_Tab();
$oFCKeditorExtension->registerHooks();
