<?php
/**
* This balloon tooltip package and associated files not otherwise copyrighted are distributed under the MIT-style license:
* 
* http://opensource.org/licenses/mit-license.php
* 
* Copyright 2007, 2008 Sheldon McKay, Cold Spring Harbor Laboratory
* Permission is hereby granted, free of charge, to any person obtaining a copy
* of this software and associated documentation files (the "Software"), to deal
* in the Software without restriction, including without limitation the rights
* to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
* copies of the Software, and to permit persons to whom the Software is
* furnished to do so, subject to the following conditions:
* The above copyright notice and this permission notice shall be included in
* all copies or substantial portions of the Software.
* THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
* IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
* FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
* AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
* LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
* OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
* THE SOFTWARE.
* 
* This is a tag extension that uses the reserved tag <balloon> to add JavaScript ajax
* popup balloons.  See http://mckay.cshl.edu/wiki/index.php/MediaWiki_Balloon_Extension
* for documentation.
* 
* @ingroup Extensions
* @author Sheldon Mckay (mckays@cshl.edu)
* @version 0.1
* @link http://www.mediawiki.org/wiki/Extension:Balloons
*/
 
# To activate the extension, include it at the end from your LocalSettings.php
# with: require_once("extensions/balloons.php");
 
//Avoid unstubbing $wgParser on setHook() too early on modern (1.12+) MW versions, as per r35980
if ( defined( 'MW_SUPPORTS_PARSERFIRSTCALLINIT' ) ) {
        $wgHooks['ParserFirstCallInit'][] = 'wfBalloonTooltips';
} else {
        $wgExtensionFunctions[] = 'wfBalloonTooltips';
}
 
$wgHooks['OutputPageBeforeHTML'][] = 'addBalloonJavascript';
 
$wgExtensionCredits['parserhook'][] = array(
        'name'        => 'Balloons',
        'version'     => '0.1',
        'author'      => 'Sheldon McKay',
        'description' => 'Balloon tooltips for wiki pages',
        'url'         => 'http://www.mediawiki.org/wiki/Extension:Balloons'
);
 
function wfBalloonTooltips() {
        global $wgParser;
        $wgParser->setHook( 'balloon', 'renderBalloonSpan' );
        return true;
}
 
# render span element with
function renderBalloonSpan( $input, $args ) {
        $text   = $args['title'];
 
        # escape quotes
        $text   = preg_replace('/\"/','\"',$text);
        $text   = preg_replace('/\'/',"\'",$text);
 
        $link   = $args['link'];
        $target = $args['target'];
        $sticky = $args['sticky'] ? 1 : 0;
        $width  = $args['width']  ? 1 : 0;
 
        if ($width) {
                $width = "," . preg_replace('/[^0-9]+/',$width);
        }
 
        $event  = $args['click'] && !$link ? 'onclick' : 'onmouseover';
        $event  = "$event=\"balloon.showTooltip(event,'${text}',${sticky}${width})\"";
 
        if (preg_match('/onclick/',$event) && $args['hover']) {
                $event2 = " onmouseover=\"balloon.showTooltip(event,'" . $args['hover'] . "',0,${width})\"";
        }
 
        $style  = "style=\"" . ($args['style'] ? $args['style'] . ";cursor:pointer\"" : "cursor:pointer\"");
        $target = $target ? "target=${target}" : '';
        $output = "<span ${event} ${event2} ${style}>${input}</span>";
 
        if ($link) {
                $output = "<a href=\"${link}\" ${target}>${output}</a>";
        }
 
        return $output;
}
 
function addBalloonJavascript(&$out) {
        global $wgScriptPath;
        $jsPath = "${wgScriptPath}/extensions/balloons/js";
        $out->addScript("\n".
                  "<script type=\"text/javascript\" src=\"${jsPath}/yahoo-dom-event.js\"></script>\n" .
                  "<script type=\"text/javascript\" src=\"${jsPath}/balloon.js\"></script>\n" .
                  "<script type=\"text/javascript\" src=\"${jsPath}/DCCballoon.js\"></script>\n" .			
                  "<script type=\"text/javascript\">\n" .
                  "var balloon = new DCCBalloon;\n" .
                  "balloon.images   = '${wgScriptPath}/extensions/balloons/images';\n" .
                  # Some skins need document.body as the parent, others use the 'content' layer
                  # Custom skin users/developers may need to edit the regular expression below
                  "balloon.parentID = skin.match(/simple|myskin|modern/) ? null : 'content';\n" .
                  "</script>\n"
        );
 
        return true;
}

