<?
  header("Content-type: text/xml");
  //header("Content-type: text/plain");
  include_once("DBFieldsConf.php");
  include_once('DBFields.php');

  $searchTerm = isset($_GET["term"]) ? $_GET["term"] : null;
  $searchCv = isset($_GET["cv"]) ? $_GET["cv"] : null;

  $validating = isset($_GET["validating"]) && $_GET["validating"] == "validating" ? true : false;
  $delimiter = isset($_GET["delimiter"]) && strlen($_GET["delimiter"]) > 0 ? $_GET["delimiter"] : null;
  
  if (!$searchCv || !$searchTerm) { return; }

  if (!$validating) {
    $resultTerms = getTermsFor($searchCv, $searchTerm);
    usort($resultTerms, create_function('$a, $b', 'if (strlen($a["name"]) < strlen($b["name"])) { return -1; } elseif (strlen($a["name"]) > strlen($b["name"])) { return 1; } else { return 0; }'));
    print "<terms>\n" . xmlifyTerms($resultTerms) . "</terms>";
  } else {
    $okayTerms = getExactTermsFor($searchCv, $searchTerm, $delimiter);
    print "<terms>\n" . xmlifyTerms($okayTerms) . "</terms>";
  }
  function getTermsArray($searchTerm, $delimiter = null) {
    if (is_null($delimiter)) { 
      $searchTerms = array($searchTerm); 
    } else {
      $searchTerms = explode($delimiter, $searchTerm);
    }
    return array_map(create_function('$term', 'return trim($term);'), $searchTerms);
  }
  function getExactTermsFor($searchCv, $searchTerm, $delimiter = null) {
    $searchTerms = getTermsArray($searchTerm, $delimiter);
    $okayTerms = array();
    $multipleCvs = (!is_null($delimiter) && strpos($searchCv, $delimiter) !== false) ? true : false;
    foreach ($searchTerms as $searchTerm) {
      if ($multipleCvs) {
        $CVandTerm = explode(":", trim($searchTerm));
        if (!isset($CVandTerm[1])) { continue; }
        $searchCv = $CVandTerm[0];
        $shortSearchTerm = $CVandTerm[1];
      } else {
        $shortSearchTerm = $searchTerm;
      }
      $resultTerms = getTermsFor($searchCv, $shortSearchTerm, $multipleCvs);
      if (count($resultTerms) < 1) { continue; }
      foreach ($resultTerms as $resultTerm) {
	if ($resultTerm["fullname"] != $searchTerm) { continue; }
	array_push($okayTerms, $resultTerm);
      }
    }
    return $okayTerms;
  }
  function getTermsFor($searchCv, $searchTerm, $multipleCvs=false) {
    $path = dirname(__FILE__) . '/ontologies';
    if (strpos($searchCv, ',') !== false) {
      $searchCvs = explode(",", $searchCv);
    } else {
      $searchCvs = array($searchCv);
    }
    if (count($searchCvs) > 1) {
      $multipleCvs = true;
    }
    $allResultTerms = array();
    foreach ($searchCvs as $searchCv) {
      if (file_exists("$path/$searchCv.obo")) {
	$resultTerms = getFileTermsFor($searchCv, $searchTerm, $multipleCvs);
      } else {
	$resultTerms = getDBTermsFor($searchCv, $searchTerm, $multipleCvs);
      }
      $allResultTerms = array_merge($allResultTerms, $resultTerms);
    }
    return $allResultTerms;
  }
  function getFileTermsFor($searchCv, $searchTerm, $multipleCvs=false, $limit=20) {
    $resultTerms = array();
    $path = dirname(__FILE__) . '/ontologies';
    $obo = file_get_contents("$path/$searchCv.obo");
    $pattern = '/idspace: (\S+)\s+(\S+)\s+(?:"([^"]*)")?/';
    preg_match_all($pattern, $obo, $matches);
    $idspaces = array();
    for ($i = 0; $i < count($matches[0]); $i++) {
      $idspaces[$matches[1][$i]]["url"] = $matches[2][$i];
      $idspaces[$matches[1][$i]]["description"] = $matches[3][$i];
    }

    $sections = preg_split('/\[([^\]]*)\]/', $obo, -1, PREG_SPLIT_DELIM_CAPTURE);

    $matches = array();
    $MAX_MATCHES = 500;
    for ($i = 0; $i < count($sections); $i++) {
      $section = $sections[$i];
      if ($section != "Term") { continue; }
      $section = $sections[++$i];
      $pattern = '/^name:\s*(.*' . preg_quote($searchTerm) . '.*)$/im';
      if (preg_match($pattern, $section, $match) > 0) {
	array_push($matches, $section);
	if ($MAX_MATCHES-- <= 0) { break; }
      }
    }

    for ($i = 0; $i < count($matches); $i++) {
      $row = array(
	"id" => "",
	"cv" => "",
	"name" => "",
	"accession" => "",
	"definition" => "",
	"def" => "",
	"url" => ""
      );
      $row["cv"] = $searchCv;
      preg_match_all('/^(?!\[)([^:]*):[ \t]*(.*?)$/m', $matches[$i], $tags);
      for ($j = 0; $j < count($tags[1]); $j++) {
	$row[trim($tags[1][$j])] = $tags[2][$j];
      }

      $row["url"] = "";
      if ($row["id"] && strpos($row["id"], ':') > 0) {
	$id = explode(':', $row["id"], 2);
        if (isset($idspaces[$id[0]])) {
          $row["url"] = str_replace('#', $id[1], $idspaces[$id[0]]["url"]);
        }
      }

      array_push($resultTerms, array(
        "fullname" => $multipleCvs ? $row["cv"] . ":" . $row["name"] : $row["name"],
	"cv" => $row["cv"], 
	"name" => $row["name"], 
	"accession" => $row["id"], 
	"definition" => $row["def"], 
	"url" => $row["url"]
      ));
    }
    return array_slice($resultTerms, 0, 50);
  }
  function getDBTermsFor($searchCv, $searchTerm, $multipleCvs=false, $limit=20) {
    global $modENCODE_DBFields_conf;
    $resultTerms = array();

    $db = modENCODE_db_connect(
      $modENCODE_DBFields_conf["cvterms"][$searchCv]["host"], 
      $modENCODE_DBFields_conf["cvterms"][$searchCv]["dbname"], 
      $modENCODE_DBFields_conf["cvterms"][$searchCv]["user"], 
      $modENCODE_DBFields_conf["cvterms"][$searchCv]["password"], 
      $modENCODE_DBFields_conf["cvterms"][$searchCv]["type"]
    );

    $searchCv = modENCODE_db_escape($searchCv, $db, $modENCODE_DBFields_conf["cvterms"][$searchCv]["type"]);
    $searchTerm = modENCODE_db_escape($searchTerm, $db, $modENCODE_DBFields_conf["cvterms"][$searchCv]["type"]);
    $limit = (int) $limit;

    $query = $modENCODE_DBFields_conf["cvterms"][$searchCv]["query"];
    $query = preg_replace('/(?<!\\\\)\?/', $searchCv, $query, 1);
    $query = preg_replace('/(?<!\\\\)\?/', $searchTerm, $query, 1);
    $query = preg_replace('/(?<!\\\\)\?/', $limit, $query, 1);
    $query = preg_replace('/\\\\\?/', '?', $query, 1);

    $res = modENCODE_db_query($db, $query, $modENCODE_DBFields_conf["form_data"]["type"]);
    while ($row = modENCODE_db_fetch_assoc($res, $modENCODE_DBFields_conf["form_data"]["type"])) {
      if ($row["urlprefix"]) {
	$row["url"] = $row["urlprefix"] . $row["id"];
	$row["url"] = str_replace('#', $row["id"], $row["urlprefix"]);
      } elseif (!isset($row["url"])) {
        $row["url"] = "";
      }
        
      array_push($resultTerms, array(
        "fullname" => $multipleCvs ? $row["cv"] . ":" . $row["name"] : $row["name"],
	"cv" => $row["cv"], 
	"name" => $row["name"], 
	"accession" => $row["id"], 
	"definition" => $row["def"], 
	"url" => $row["url"]
      ));
    }

    modENCODE_db_close(
      $db,
      $modENCODE_DBFields_conf["cvterms"][$searchCv]["type"]
    );
    return $resultTerms;
  }
  function xmlifyTerms($terms) {
    $string = "";
    foreach ($terms as $term_hash) {
      $string .= "  <term>\n";
      foreach ($term_hash as $term => $value) {
	$string .= "    <$term>" . htmlentities($value) . "</$term>\n";
      }
      $string .= "  </term>\n";
    }
    return $string;
  }
?>


