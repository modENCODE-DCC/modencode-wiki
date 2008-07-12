<?php
/*
 
 CreateBox v1.5 -- Specialized Inputbox for page creation
 
 Author: Ross McClure
 http://www.mediawiki.org/wiki/User:Algorithm
 
 Inputbox written by Erik Moeller <moeller@scireview.de>
 
 This program is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.
 
 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 GNU General Public License for more details.
 
 You should have received a copy of the GNU General Public License along
 with this program; if not, write to the Free Software Foundation, Inc.,
 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 http://www.gnu.org/copyleft/gpl.html
 
 To install, add following to LocalSettings.php
   include("extensions/create.php");
 
*/
 
$wgExtensionFunctions[] = "wfCreateBox";
$wgHooks['UnknownAction'][] = 'actionCreate';
$wgHooks['OutputPageBeforeHTML'][] = 'addCreateScript';

$wgExtensionCredits['parserhook'][] = array(
'name' => 'CreateBox',
'url' => 'http://www.mediawiki.org/wiki/Extension:CreateBox',
'description' => 'Specialized Inputbox for page creation',
'author' => 'Ross McClure',
'version' => '1.5'
);


function addCreateScript(&$out) {
  global $wgScriptPath;
  $script = '<script type="text/javascript" src="'.$wgScriptPath.'/extensions/inputbox/createbox.js"></script>';
  $out->addScript($script);
  return true;
}
 
function wfCreateBox()
{
    global $wgParser, $wgMessageCache;
 
    $wgMessageCache->addMessages( array(
        'create' => "Create",
        'create_exists' => "Sorry, \"'''{{FULLPAGENAME}}'''\" already " .
               "exists.\n\nYou cannot create this page, but you can " .
               "[{{fullurl:{{FULLPAGENAME}}|action=edit}} edit it], " .
               "[{{fullurl:{{FULLPAGENAME}}}} read it], or choose to " .
               "create a different page using the box below.\n\n" .
               "<createbox>break=no</createbox>"
    ));
 
    $wgParser->setHook( "createbox", "acMakeBox" );
}
 
function actionCreate($action, $article)
{
    if($action != 'create') return true;
 
    global $wgRequest;
    $prefix = $wgRequest->getVal('prefix');
    $text   = $wgRequest->getVal('title');

    if($prefix && strpos($text, $prefix)!==0)
    {
        $title = Title::newFromText( $prefix . $title );
        if(is_null($title))
        {
            global $wgTitle;
            $wgTitle = Title::makeTitle( NS_SPECIAL, 'Badtitle' );
            throw new ErrorPageError( 'badtitle', 'badtitletext' );
        }
        else if($title->getArticleID() == 0) acRedirect($title, 'edit');
        else acRedirect($title, 'create');
    }
    else if($wgRequest->getVal('section')=='new' || $article->getID() == 0)
    {
        acRedirect($article->getTitle(), 'edit');
    }
    else
    {
        global $wgOut;
        $text = $article->getTitle()->getPrefixedText();
        $wgOut->setPageTitle($text);
        $wgOut->setHTMLTitle(wfMsg('pagetitle', $text.' - '.wfMsg('create')));
        $wgOut->addWikiText(wfMsg('create_exists'));
    }
    return false;
}
 
function acGetOption(&$input,$name,$value=NULL)
{
    if(preg_match("/^\s*$name\s*=\s*(.*)/mi",$input,$matches))
    {
        if(is_int($value)) return intval($matches[1]);
        else return htmlspecialchars($matches[1]);
    }
    return $value;
}
 
function acMakeBox($input, $argv, &$parser)
{
  global $wgRequest, $wgScript,$createbox;
    if($wgRequest->getVal('action')=='create')
    {
        $prefix = $wgRequest->getVal('prefix');
        $preload = $wgRequest->getVal('preload');
        $editintro = $wgRequest->getVal('editintro');
        $text = $parser->getTitle()->getPrefixedText();
        if($prefix && strpos($text, $prefix)===0) {
            $text = substr($text, strlen($prefix));
	}
    }
    else
    {
        $prefix = acGetOption($input,'prefix');
        $preload = acGetOption($input,'preload');
        $editintro = acGetOption($input,'editintro');
        $text = acGetOption($input,'default');
    }
    $submit = htmlspecialchars($wgScript);
    $width  = acGetOption($input,'width',0);
    $align  = acGetOption($input,'align','center');
    $br     = ((acGetOption($input,'break','no')=='no') ? '' : '<br />');
    $label  = acGetOption($input,'buttonlabel',wfMsgHtml("createarticle"));
    $addlab = acGetOption($input,'lab');
    $type   = acGetOption($input,'classifier');
    $enforce = acGetOption($input,'enforce');
    if ($addlab || $type) {
      $version = " Version: <input name=\"version\" value=\"1\" size=\"2\">";
    }
    if ($type) {
      $type = "Type: <input name=\"classifier\" value=\"${type}\" disabled=\"1\" size=\"5\"> ";
    }
    else {
      $type = "<input type=\"hidden\" name=\"classifier\" value=\"\">";
    }
    if ($addlab) {
      if ($addlab == 'all') {
      $labmenu = <<<END
&nbsp;Group: <select name="lab">
<option value=""></option>
<option value="RW">Waterston</option>
<option value="SH">Henikof</option>
<option value="JL">Lie</option>
<option value="KW">White</option>
<option value="GK">Karpen</option>
<option value="SC">Celniker</option>
<option value="MS">Snyder</option>
<option value="EL">Lai</option>
<option value="FP">Piano</option>
<option value="DM">MacAlpine</option>
<option value="LS">Stein</option>
</select>
END;
      }
      else {
	$labmenu = " Group: <input name=\"lab\" value=\"${addlab}\" disabled=\"1\" size=\"3\">";
      }
    }
    else {
      $labmenu = "<input type=\"hidden\" name=\"lab\" value=\"\">";
    }

    $output=<<<ENDFORM
<div class="createbox" align="{$align}">
<form name="createbox" action="{$submit}" method="get" class="createboxForm" onsubmit="return assembleName();">
$type<input type='hidden' name="action" value="create">
<input type="hidden" name="preload" value="{$preload}" />
<input type="hidden" name="editintro" value="{$editintro}" />
<input type="hidden" name="editintro" value="{$prefix}" />
<input type="hidden" name="enforce_nomenclature" value="{$enforce}" />
Name: <input class="createboxInput" name="title" type="text" value="{$text}" size="{$width}"/> $labmenu $version
<input type='submit' name="create" class="createboxButton" value="{$label}"/>
</form></div>
ENDFORM;
    $createBox = true;
    return $parser->replaceVariables($output);
}
 
function acRedirect($title, $action)
{
    global $wgRequest, $wgOut;
    $query = "action={$action}&prefix=" . $wgRequest->getVal('prefix') .
        "&preload=" . $wgRequest->getVal('preload') .
        "&editintro=" . $wgRequest->getVal('editintro') .
        "&section=" . $wgRequest->getVal('section');
    $wgOut->setSquidMaxage( 1200 );
    $wgOut->redirect($title->getFullURL( $query ), '301');
}
 
?>

