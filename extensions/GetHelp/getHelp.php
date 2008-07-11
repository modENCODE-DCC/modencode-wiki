<?

// Start MediaWiki connection
$wgNoOutputBuffer = true;
$dir = getcwd();
chdir(dirname(__FILE__) . "/../../");
ob_start();
require_once("includes/WebStart.php");
ob_end_clean();
chdir($dir);

if ($_GET["id"] && substr($_SERVER["PHP_SELF"], -11) == "getHelp.php") {
  header("Content-type: text/xml");
  print "<?xml version=\"1.0\"?>\n";
  if ($_GET["id"]) {
    $wh = new WebHelp();
    $help_short = $wh->helpShort($_GET["id"]);
    $help_long = $wh->helpLong($_GET["id"]);
  }
  print "<help>\n";
  print "  <longhelp>" . htmlentities($help_long) . "</longhelp>\n";
  print "  <shorthelp>" . htmlentities($help_short) . "</shorthelp>\n";
  print "</help>\n";
}

class WebHelp {
  private $mw;
  private $help_template = "HelpHeader";
  public function __construct($mw = false) {
    if (!$mw) {
      $mw = new MediaWiki();
    } 
    if (!$mw) {
      throw new Exception("WebHelp was passed an invalid MediaWiki object.");
    }
    $this->mw = $mw;
  }
  public function helpShort($helpname, $section = false) {
    $hash = $this->helpHash($helpname, $section);
    return $hash["summary"];
  }
  public function helpHash($helpname, $section = false) {
    if ($section) {
      $section = strtolower(CoreParserFunctions::anchorencode($parser, $section));
    }
    if (!$helpname) {
      return array("summary" => "No help name.", "title" => "No help name");
    }
    $mw = $this->mw;
    $title = Title::newFromText($helpname);
    $article = $mw->articleFromTitle($title);
    $tooMany = 0;
    while ($redir = $article->followRedirect()) {
      if (is_a($redir, "Title")) {
        $article = $mw->articleFromTitle($redir);
      }
      if ($tooMany++ > 15) { break; }
    }
    $templates = $article->getUsedTemplates();
    $has_summary = false;
    foreach($templates as $title) {
      if ($title->getText() == $this->help_template) {
        $has_summary = true;
        break;
      }
    }
    $summary = "";
    $parser = new Parser();
    $parser->clearState();
    $parser->mTitle = $title;
    $parser->mOptions = new ParserOptions();
    $parser->mArgStack = array();
    $parser->mOutput = new ParserOutput();

    $content = $article->getContent();
    preg_match_all('/{{' . $this->help_template . '\|(.*?)}}/ms', $content, $matches);
    $sections = array();
    foreach ($matches[1] as $match) {
      $parts = explode('|', $match);
      $subsection = strtolower(CoreParserFunctions::anchorencode($parser, $parts[0]));
      $sections[$subsection]['title'] = $parts[0];
      $sections[$subsection]['summary'] = $parts[1];
      if (!$sections['default']) { $sections['default'] = $sections[$subsection]; }
    }
    if (!$sections[$section]) { $section = 'default'; }

    $oldinc = set_include_path(get_include_path() . PATH_SEPARATOR . "sections/Help_MediaWiki/");
    $wgNoOutputBuffer = true;
    $dir = getcwd();
    chdir(dirname(__FILE__) . "/../../");
    $sections[$section]['summary'] = $parser->doBlockLevels($sections[$section]['summary'], true);
    $sections[$section]['summary'] = $parser->internalParse($sections[$section]['summary']);
    chdir($dir);
    $m = array();
    if( preg_match( "~^<p>(.*)\n?</p>$~", $sections[$section]['summary'], $m ) ) {
      $sections[$section]['summary'] = $m[1];
    }
    $title = Title::newFromText($helpname);
    $sections[$section]['url'] = $title->getFullURL() . "#" . $section;
    return $sections[$section];
  }
  public function helpLong($helpname) {
    /*
    $sql = "SELECT long_text FROM web_help WHERE help_name = '" . addslashes($helpname) . "'";
    $res = pg_query($this->db, $sql);
    if (!($row = pg_fetch_assoc($res))) { return "Could not find menu '$helpname' in UI database!"; }
    return $row["long_text"];
    */
  }
}
?>
