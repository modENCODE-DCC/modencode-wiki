<?
chdir(dirname(__FILE__) . "/../..");
//ob_start();
require_once(dirname(__FILE__) . '/getHelp.php');
//ob_end_clean();

header("Content-type: text/html");
$mw = new MediaWiki();
$title = Title::newFromText($_GET["page"]);
$wh = new WebHelp($mw);
$hash = $wh->helpHash($_GET["page"], $_GET["section"]);
$summary = $hash["summary"];
$url = $hash["url"];
$title = $hash["title"];
print <<<END
<html>
 <body>
  <table cellspacing="0" cellpadding="1" style="background: #ffffff;padding: 4px; width: 100%;font-size: 80%">
   <tr valign="middle" style="background: #ccf; padding: 2px;">
    <th style="width:20px">
     <img src="http://wiki.modencode.org/project/extensions/DBFields/question.png">
    </th>
    <th align=left>
     $title
    </th>
   </tr>
   <tr>
    <td colspan=2>
     $summary
    </td>
   </tr>
   <tr>
    <td colspan=2>
     <i>Click for more information...</i>
    </td>
   </tr>
  </table>
 </body>
</html>
END;
?>