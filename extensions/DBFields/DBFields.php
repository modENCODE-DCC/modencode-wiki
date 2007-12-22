<?
  include_once("DBFieldsConf.php");
  include_once("DBFieldsCVTerm.php");
  $wgExtensionFunctions[] = 'modENCODE_DBFields_setup';
  $wgHooks['ParserAfterTidy'][] = 'modENCODE_dbfields_ParserAfterTidy_MarkerReplacement';
  // Version too old for:
  //$wgHooks['BeforePageDisplay'][] = 'modENCODE_dbfields_BeforePageDisplay_addCSSandJS';
  $wgHooks['OutputPageBeforeHTML'][] = 'modENCODE_dbfields_BeforePageDisplay_addCSSandJS';

  $modENCODE_dbfields_data = array(
    "xml" => "", 
    "open_element" => 0, 
    "stack" => array(), 
    "stack_of_parsed_elements" => array(), 
    "chrdata" => false, 
    "values" => array(),
    "invalidversion" => false
  );
  $modENCODE_dbfields_allowed_tags = array("input", "select", "textarea", "option", "br", "div", "table", "tr", "td", "th", "label");
  $modENCODE_dbfields_allowed_attributes = array("name", "type", "value", "border", "style", "width", "size", "rows", "cols", "checked", "selected", "id", "for", "class", "cv", "multiple", "required");
  $modENCODE_markers_to_data = array();

  function modENCODE_DBFields_setup() {
    global $wgParser;
    $wgParser->setHook('dbfields', 'modENCODE_dbfields_render');
  }
  function after() {
    print "After";
  }
  function before() {
    print "Before";
  }
  function modENCODE_dbfields_startElement($parser, $name, $attribs) {
    global $modENCODE_dbfields_data;
    global $modENCODE_dbfields_allowed_tags;
    global $modENCODE_dbfields_allowed_attributes;

    // Keep only allowed tags
    if (!in_array($name, $modENCODE_dbfields_allowed_tags)) { return; }
    $string_attributes = array();
    /*
    <input type="cvterm" cv="cell type" name="cell type" id="cell type"/>

    <div id="myAutoComplete">
      <div id="myUrl"></div>
      <input type="text" id="myInput">
      <div id="myContainer"></div>
    </div>
    */
    // If there are values in the DB, read them out
    // (this overwrites any default values)
    $orig_attribs = $attribs;
    array_push($modENCODE_dbfields_data["stack"], array("name" => $name, "attribs" => $attribs));
    $extra_content_before = '';
    $extra_content_after = '';
    if ($name == "input") {
      if (!isset($attribs["class"])) { $attribs["class"] = ""; }
      $attribs["class"] .= " dbfields_input ";
      if ($attribs["type"] == "cvterm") {
	$extra_content_before = '<div id="' . $attribs["id"] . '_complete">';
	$attribs["class"] .= "cvterm";
	$attribs["type"] = "text";
      }
      if ($attribs["type"] == "text" || $attribs["type"] == "password") {
        if (isset($modENCODE_dbfields_data["values"][$attribs["name"]])) {
          $attribs["value"] = $modENCODE_dbfields_data["values"][$attribs["name"]];
        } else {
          $attribs["value"] = "";
        }
      }
      if ($attribs["type"] == "checkbox" || $attribs["type"] == "radio") {
	if ($attribs["value"] == $modENCODE_dbfields_data["values"][$attribs["name"]]) {
	  $attribs["checked"] = "checked";
	} elseif ($modENCODE_dbfields_data["values"][$attribs["name"]]) {
	  unset($attribs["checked"]);
	}
      }
    }
    if ($name == "option") {
      $tempstack = $modENCODE_dbfields_data["stack"];
      while ($parent = array_pop($tempstack)) {
	if ($parent["name"] == "select") {
	  $value = (isset($modENCODE_dbfields_data["values"][$parent["attribs"]["name"]])) ? $modENCODE_dbfields_data["values"][$parent["attribs"]["name"]] : "";
	  if ($attribs["value"] == $value) {
	    $attribs["selected"] = "selected";
	  } elseif ($value) {
	    unset($attribs["selected"]);
	  }
	}
      }
    }
    array_push($modENCODE_dbfields_data["stack_of_parsed_elements"], array("name" => $name, "attribs" => $attribs));

    // Make sure to only keep allowed attributes
    foreach ($attribs as $key => $value) {
      if (!in_array($key, $modENCODE_dbfields_allowed_attributes)) { continue; }
      if ($key == "name") { $value = "modENCODE_dbfields[$value]"; }
      array_push($string_attributes, "$key=\"$value\"");
    }
    $attrib_string = join(" ", $string_attributes);
    
    // Write out the filtered tag
    $modENCODE_dbfields_data["xml"] .= $extra_content_before;
    $modENCODE_dbfields_data["xml"] .= $modENCODE_dbfields_data["chrdata"];
    $modENCODE_dbfields_data["xml"] .= "<$name $attrib_string";
    $modENCODE_dbfields_data["xml"] .= ">";
    $modENCODE_dbfields_data["xml"] .= $extra_content_after;
    $modENCODE_dbfields_data["chrdata"] = false;
    //$modENCODE_dbfields_data["open_element"]++;
  }
  function modENCODE_dbfields_endElement($parser, $name) {
    global $modENCODE_dbfields_data;
    global $modENCODE_dbfields_allowed_tags;
    global $renderParser;
    if (!in_array($name, $modENCODE_dbfields_allowed_tags)) { return; }

    $extra_content_before = '';
    $extra_content_after = '';
    if ($name == "textarea") {
      //if ($modENCODE_dbfields_data["values"])
      $tempstack = $modENCODE_dbfields_data["stack"];
      while ($parent = array_pop($tempstack)) {
	if ($parent["name"] == "textarea") {
	  if (strlen($modENCODE_dbfields_data["values"][$parent["attribs"]["name"]])) {
	    $modENCODE_dbfields_data["chrdata"] = ($modENCODE_dbfields_data["values"][$parent["attribs"]["name"]]);
	  }
	}
      }
    }
    if ($name == "input") {
      $input = $modENCODE_dbfields_data["stack"][count($modENCODE_dbfields_data["stack"])-1];

      if ($input["attribs"]["type"] == "cvterm") {
	$attribs = $input["attribs"];
	$extra_content_after .= '<div class="cvterm_url" id="' . $attribs["id"] . '_url">';
	if (isset($modENCODE_dbfields_data["values"][$attribs["name"]]) && strlen($modENCODE_dbfields_data["values"][$attribs["name"]]) > 0) {
	  // Get URLs
	  $delim = ($attribs["multiple"] ? ',' : null);
	  $terms = getExactTermsFor($attribs["cv"], html_entity_decode($modENCODE_dbfields_data["values"][$attribs["name"]]), $delim);
	  foreach ($terms as $term) {
	    if (strlen($term["url"]) > 0) {
	      $linkname = strlen($term["fullname"]) > 25 ? substr($term["fullname"], 0, 25) . "..." : $term["fullname"];
	      $link = '<a href="' . $term["url"] . '">' . $linkname . '</a> ';
	      $extra_content_after .= $link;
	      //$link = $renderParser->parse($link, $renderParser->mTitle, $renderParser->mOptions);
	      //$extra_content_after .= $link->getText();
	    }
	  }
	}
	$extra_content_after .= '</div>';
	$extra_content_after .= '<div id="' . $input["attribs"]["id"] . '_container"></div></div>';
      }
    }
    if ($name == "input" || $name == "select") {
      $input = $modENCODE_dbfields_data["stack"][count($modENCODE_dbfields_data["stack"])-1];
      $attribs = $input["attribs"];
      $item = $modENCODE_dbfields_data["stack_of_parsed_elements"][count($modENCODE_dbfields_data["stack_of_parsed_elements"])-1];
      if (isset($item) && $item && isset($item["attribs"]) && isset($item["attribs"]["required"]) && $item["attribs"]["required"] == "true") {
	$value = isset($modENCODE_dbfields_data["values"][$item["attribs"]["name"]]) ? $modENCODE_dbfields_data["values"][$item["attribs"]["name"]] : "";
        $missingClass = "required";
	if (!strlen($value)) { 
	  $missingClass .= " missing";
	  $extra_content_after .= "  <div class=\"$missingClass\" id=\"" . $attribs["id"] . "_missing\">required field missing</div>";
	  $modENCODE_dbfields_data["invalidversion"] = true;
	}
      }
      if (
	isset($item) && $item && 
	isset($input) && $input && 
	isset($input["attribs"]) && isset($input["attribs"]["type"]) && $input["attribs"]["type"] == "cvterm" && 
	isset($item["attribs"]["name"]) && isset($modENCODE_dbfields_data["values"][$item["attribs"]["name"]]) &&
	strlen($modENCODE_dbfields_data["values"][$item["attribs"]["name"]]) > 0
      ) {
	$terms = getExactTermsFor($attribs["cv"], html_entity_decode($modENCODE_dbfields_data["values"][$attribs["name"]]), $delim);
	$terms = array_map(create_function('$term', 'return $term["fullname"];'), $terms);
	$existingTerms = getTermsArray($modENCODE_dbfields_data["values"][$item["attribs"]["name"]], $delim);
	$diffterms = array_diff($existingTerms, $terms);
	if (count($diffterms) > 0) {
	  $diffterms = implode(", ", $diffterms);
	  $extra_content_after .= "  <div class=\"required missing\" id=\"" . $attribs["id"] . "_missing\">invalid controlled vocabulary term(s): $diffterms</div>";
	  $modENCODE_dbfields_data["invalidversion"] = true;
	}
      }
    }


    $modENCODE_dbfields_data["xml"] .= $extra_content_before;
    $modENCODE_dbfields_data["xml"] .= $modENCODE_dbfields_data["chrdata"] . "</$name>\n";
    $modENCODE_dbfields_data["xml"] .= $extra_content_after;
    $modENCODE_dbfields_data["chrdata"] = false;
    array_pop($modENCODE_dbfields_data["stack"]);
    array_pop($modENCODE_dbfields_data["stack_of_parsed_elements"]);
  }
  function modENCODE_dbfields_characterData($parser, $data) {
    global $modENCODE_dbfields_data;
    $inTextarea = false;
    $tempstack = $modENCODE_dbfields_data["stack"];
    while ($parent = array_pop($tempstack)) {
      if ($parent["name"] == "textarea") {
        $inTextarea = true;
        break;
      }
    }
    if ($inTextarea) {
      $modENCODE_dbfields_data["chrdata"] .= $data;
    } else {
      $modENCODE_dbfields_data["chrdata"] .= rtrim($data, "\r\n");
    }
  }
  function modENCODE_db_connect($host, $dbname, $user, $password, $dbtype) {
    if ($dbtype == "postgres") {
      if (!function_exists("pg_connect")) {
        print "Function pg_connect does not exist, but is needed by modENCODE_db_connect.";
      }
      $connstring = "";
      if (strlen($host) > 0)     { $connstring .= "host=$host "; }
      if (strlen($dbname) > 0)   { $connstring .= "dbname=$dbname "; }
      if (strlen($user) > 0)     { $connstring .= "user=$user "; }
      if (strlen($password) > 0) { $connstring .= "password=$password "; }
      $db = pg_connect($connstring);
    } elseif ($dbtype == "mysql") {
      if (!function_exists("mysql_connect")) {
        print "Function mysql_connect does not exist, but is needed by modENCODE_db_connect.";
      }
      $db = mysql_connect($host, $user, $password);
      mysql_select_db($dbname, $db);
    } else {
      die("Unknown database type $dbtype for modENCODE_DBFields extension! Must be \"postgres\" or \"mysql\"...");
    }
    return $db;
  }
  function modENCODE_db_escape($string, $db, $dbtype) {
    if ($dbtype == "postgres") {
      $string = pg_escape_string($string);
    } elseif ($dbtype == "mysql") {
      $string = mysql_real_escape_string($string, $db);
    } else {
      die("Unknown database type $dbtype for modENCODE_DBFields extension! Must be \"postgres\" or \"mysql\"...");
    }
    return $string;
  }
  function modENCODE_db_query($db, $query, $dbtype) {
    if ($dbtype == "postgres") {
      return pg_query($db, $query);
    } elseif ($dbtype == "mysql") {
      return mysql_query($query, $db);
    } else {
      die("Unknown database type $dbtype for modENCODE_DBFields extension! Must be \"postgres\" or \"mysql\"...");
    }
  }
  function modENCODE_db_fetch_assoc($res, $dbtype) {
    if ($dbtype == "postgres") {
      return pg_fetch_assoc($res);
    } elseif ($dbtype == "mysql") {
      return mysql_fetch_assoc($res);
    } else {
      die("Unknown database type $dbtype for modENCODE_DBFields extension! Must be \"postgres\" or \"mysql\"...");
    }
  }
  function modENCODE_db_close($db, $dbtype) {
    if ($dbtype == "postgres") {
      return pg_close($db);
    } elseif ($dbtype == "mysql") {
      return mysql_close($db);
    } else {
      die("Unknown database type $dbtype for modENCODE_DBFields extension! Must be \"postgres\" or \"mysql\"...");
    }
  }
    

  function modENCODE_dbfields_render($input, $args, $parser) {
    $art = new Article($parser->mTitle);
    $art->loadContent();
    $content = $art->mContent;
    $templates = array_keys($parser->mTemplatePath);
    $current_template = $templates[count($templates)-1];
    preg_match('/{{' . $currentTemplate . '[^}]*}}/', $content, $match);
    preg_match_all('/\|([^=]*)=([^|}]*)/', $match[0], $arg_matches);
    $realargs = array();
    for ($i = 0; $i < count($arg_matches[1]); $i++) {
      $key = $arg_matches[1][$i];
      $value = $arg_matches[2][$i];
      $input = str_replace('{{{' . $key . '}}}', $value, $input);
      foreach ($args as $argkey => $argval) {
	$args[$argkey] = str_replace('{{{' . $key . '}}}', $value, $argval);
      }
    }
    global $modENCODE_dbfields_data;
    global $modENCODE_markers_to_data;
    global $modENCODE_DBFields_conf;
    global $wgOut;
    global $renderParser;
    $renderParser = $parser;
    $parser->disableCache();

    $revisionId = $parser->mRevisionId;
    $permalink = $parser->mTitle->getLocalURL("oldid=$revisionId");

    $db = modENCODE_db_connect(
      $modENCODE_DBFields_conf["form_data"]["host"], 
      $modENCODE_DBFields_conf["form_data"]["dbname"], 
      $modENCODE_DBFields_conf["form_data"]["user"], 
      $modENCODE_DBFields_conf["form_data"]["password"], 
      $modENCODE_DBFields_conf["form_data"]["type"]
    );

    if (!strlen($args["name"])) {
      $args["name"] = $parser->mTitle;
    }

    $entry_name = modENCODE_db_escape($args["name"], $db, $modENCODE_DBFields_conf["form_data"]["type"]);

    if (isset($_GET["version"]) && $_GET["version"]) {
      $version = modENCODE_db_escape($_GET["version"], $db, $modENCODE_DBFields_conf["form_data"]["type"]);
      $res = modENCODE_db_query($db,
        "SELECT MAX(wiki_revid) AS revisionid FROM data WHERE name = '$entry_name' AND version = $version",
        $modENCODE_DBFields_conf["form_data"]["type"]
      );
      if ($row = modENCODE_db_fetch_assoc($res, $modENCODE_DBFields_conf["form_data"]["type"])) {
        $oldRevision = $row["revisionid"];
        $versionUrl = $parser->mTitle->getLocalURL("oldid=$oldRevision");
        $wgOut->redirect($versionUrl);
        $wgOut->output();
      }
    }

    if (!$revisionId) { $revisionId = '(SELECT MAX(wiki_revid) FROM data)'; }


    $res = modENCODE_db_query($db, "
      SELECT
	CASE WHEN (SELECT COUNT(*) FROM data WHERE wiki_revid >= $revisionId AND name = '$entry_name') > 1 THEN
	  (SELECT MIN(version) AS version FROM data WHERE name = '$entry_name' AND wiki_revid >= $revisionId)
	ELSE
	  (SELECT MAX(version) AS version FROM data WHERE name = '$entry_name')
	END AS version",
      $modENCODE_DBFields_conf["form_data"]["type"]
    );

    $version = 0;
    if ($row = modENCODE_db_fetch_assoc($res, $modENCODE_DBFields_conf["form_data"]["type"])) {
      if ($row["version"] > 0) {
	$version = $row["version"];
      }
    }
    if (isset($_POST["modENCODE_dbfields"]) && count($_POST["modENCODE_dbfields"])) {
      $version++;


      $dbw = wfGetDB(DB_MASTER);
      $pageId = $parser->mTitle->getArticleId();

      $old_values = array();
      $res = modENCODE_db_query($db, "SELECT key, value FROM data WHERE name = '$entry_name' AND version = " . ($version-1), $modENCODE_DBFields_conf["form_data"]["type"]);
      while ($row = modENCODE_db_fetch_assoc($res, $modENCODE_DBFields_conf["form_data"]["type"])) {
        $old_values[$row['key']] = $row['value'];
      }

      $left_diff = array_diff_assoc($old_values, $_POST["modENCODE_dbfields"]);
      $right_diff = array_diff_assoc($_POST["modENCODE_dbfields"], $old_values);
      $anyChange = (count($left_diff) > 0 || count($right_diff) > 0) ? true : false;

      if ($anyChange) {
        $newRev = Revision::newNullRevision($dbw, $pageId, "DBFields version update", false);
        $newRevId = $newRev->inserton($dbw);
        $newRev = Revision::newFromId($newRevId);
        $a = new Article($parser->mTitle);
        $a->updateIfNewerOn($dbw, $newRev);

        foreach ($_POST["modENCODE_dbfields"] as $key => $value) {
          $key = modENCODE_db_escape($key, $db, $modENCODE_DBFields_conf["form_data"]["type"]);
          $value = modENCODE_db_escape(htmlentities($value), $db, $modENCODE_DBFields_conf["form_data"]["type"]);
          modENCODE_db_query($db, "INSERT INTO data (name, key, value, version, wiki_revid) VALUES('$entry_name', '$key', '$value', $version, $newRevId)", $modENCODE_DBFields_conf["form_data"]["type"]);
        }
      }

      $url = $parser->mTitle->getLocalURL("action=purge");
      // Reload the page to get the new updated ID
      $wgOut->redirect($url);
      $wgOut->output();

    }

    $nochanges = false;
    $curRev = Revision::newFromId($revisionId);
    if (!$curRev || !$curRev->isCurrent()) {
      $nochanges = true;
    }

    $db_values = array();
    $res = modENCODE_db_query($db, "SELECT key, value FROM data WHERE name = '$entry_name' AND version = $version", $modENCODE_DBFields_conf["form_data"]["type"]);
    while ($row = modENCODE_db_fetch_assoc($res, $modENCODE_DBFields_conf["form_data"]["type"])) {
      $modENCODE_dbfields_data["values"][$row['key']] = $row['value'];
    }
    modENCODE_db_close($db, $modENCODE_DBFields_conf["form_data"]["type"]);

    $input = "<xml>$input</xml>";
    $xml_parser = xml_parser_create();
    xml_parser_set_option($xml_parser, XML_OPTION_CASE_FOLDING, false);
    xml_set_element_handler($xml_parser, "modENCODE_dbfields_startElement", "modENCODE_dbfields_endElement");
    xml_set_character_data_handler($xml_parser, "modENCODE_dbfields_characterData");
    xml_parse($xml_parser, trim($input), true);
    xml_parser_free($xml_parser);

    $thispage = $parser->mTitle->getFullURL("action=purge");

    $parsed_xml = "";
    $parsed_xml .= "<form class=\"modENCODE_dbfields yui-skin-sam\" method=\"POST\" action=\"$thispage\">\n";
    if ($nochanges) {
      $modENCODE_dbfields_data["xml"] = preg_replace("/<(input|select|textarea)/", "<\$1 disabled=\"disabled\"", $modENCODE_dbfields_data["xml"]);
    }
    $parsed_xml .= $modENCODE_dbfields_data["xml"];

    if (!$nochanges) {
      $parsed_xml .= "<br/>\n<input type=\"submit\" value=\"Update\"/> <input type=\"reset\" value=\"Restore\"/>\n";
    }

    $parsed_xml .= "</form>";

    $server_url = "http://" . $_SERVER["SERVER_NAME"];
    $parsed_xml .= "<br/><br/>Please use this page's permanent link when referencing it in data submission:<br/>";
    $parsed_xml .= "<a href=\"$permalink\">${server_url}$permalink</a>";

    // Permalink marker
    //$modENCODE_markers_to_data[] = "<pre>" . htmlentities($parsed_xml) . "</pre>";
    if ($modENCODE_dbfields_data["invalidversion"]) {
      $parsed_xml = "<div class=\"invalid\">This form is incomplete...</div>\n" . $parsed_xml;
    }
    $modENCODE_markers_to_data[] = $parsed_xml;

    $version = ($version == 0) ? "0: no information" : $version;
    $result = "<h2>Protocol \"" . $args["name"] . "\" (Version $version)</h2>\n";
    $result .= htmlspecialchars("modENCODE-marker#" . (count($modENCODE_markers_to_data)-1) . "#");
    return $result;
  }
  function modENCODE_dbfields_ParserAfterTidy_MarkerReplacement(&$parser, &$text) {
    global $modENCODE_markers_to_data;
    for ($i = 0; $i < count($modENCODE_markers_to_data); $i++) {
      $text = preg_replace("/modENCODE-marker#$i#/", $modENCODE_markers_to_data[$i], $text);
    }
    return true;
  }
  function modENCODE_dbfields_BeforePageDisplay_addCSSandJS(&$out, &$text) {
    global $modENCODE_markers_to_data;
    global $wgScriptPath;
    if (count($modENCODE_markers_to_data)) {
      $out->addLink(array(
	'rel' => 'stylesheet',
	'href' => "$wgScriptPath/extensions/DBFields/DBFields.css?diff=" . rand()
      ));
      $out->addLink(array(
	'rel' => 'stylesheet',
	'href' => "$wgScriptPath/extensions/DBFields/yui/build/autocomplete/assets/skins/sam/autocomplete.css"
      ));

      $out->addScript(
	'<script type="text/javascript" src="' . $wgScriptPath . '/extensions/DBFields/yui/build/yahoo-dom-event/yahoo-dom-event.js"></script>' .
	'<script type="text/javascript" src="' . $wgScriptPath . '/extensions/DBFields/yui/build/connection/connection.js"></script>' .
	'<script type="text/javascript" src="' . $wgScriptPath . '/extensions/DBFields/yui/build/logger/logger.js"></script>' .
	'<script type="text/javascript" src="' . $wgScriptPath . '/extensions/DBFields/yui/build/autocomplete/autocomplete.js"></script>' .
	'<script type="text/javascript" src="' . $wgScriptPath . '/extensions/DBFields/behaviour.js"></script>' .
	'<script type="text/javascript" src="' . $wgScriptPath . '/extensions/DBFields/DBFields.js.php?diff=' . rand() . '"></script>'
      );
    }
    return true;
  }
?>
