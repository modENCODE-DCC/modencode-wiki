<?
  header("Content-type: text/xml");
  //header("Content-type: text/plain");
  include_once("DBFieldsConf.php");
  include_once('DBFields.php');

  $searchTerm = $_GET["term"];
  $searchCv = $_GET["cv"];

  $validating = $_GET["validating"] == "validating" ? true : false;
  $delimiter = strlen($_GET["delimiter"]) > 0 ? $_GET["delimiter"] : null;
  
  if (!$searchCv || !$searchTerm) { return; }

  if (!$validating) {
    $resultTerms = getTermsFor($searchCv, $searchTerm);
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
    foreach ($searchTerms as $searchTerm) {
      $searchTerm = trim($searchTerm);
      $resultTerms = getTermsFor($searchCv, $searchTerm);
      if (count($resultTerms) < 1) { continue; }
      foreach ($resultTerms as $resultTerm) {
	if ($resultTerm["name"] != $searchTerm) { continue; }
	array_push($okayTerms, $resultTerm);
      }
    }
    return $okayTerms;
  }
  function getTermsFor($searchCv, $searchTerm) {
    $path = dirname(__FILE__) . '/ontologies';
    if (strpos($searchCv, ',') !== false) {
      $searchCvs = explode(",", $searchCv);
    } else {
      $searchCvs = array($searchCv);
    }
    $allResultTerms = array();
    foreach ($searchCvs as $searchCv) {
      if (file_exists("$path/$searchCv.obo")) {
	$resultTerms = getFileTermsFor($searchCv, $searchTerm);
      } else {
	$resultTerms = getDBTermsFor($searchCv, $searchTerm);
      }
      $allResultTerms = array_merge($allResultTerms, $resultTerms);
    }
    return $allResultTerms;
  }
  function getFileTermsFor($searchCv, $searchTerm, $limit=20) {
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

    $offset = 0;
    $pattern = '/^\[Term\](?:.(?!\[Term\]))*name:([^\r\n]*' . preg_quote($searchTerm) . '[^\r\n]*)(?:.(?!\[Term\]))*/ism';

    $matches = array();
    $MAX_MATCHES = 500;
    while (preg_match($pattern, $obo, $match, PREG_OFFSET_CAPTURE, $offset) > 0) {
      $offset = $match[0][1] + strlen($match[0][0]);
      array_push($matches, $match[0][0]);
      if ($MAX_MATCHES-- <= 0) { break; }
    }

    for ($i = 0; $i < count($matches); $i++) {
      $row = array("cv" => $searchCv);
      preg_match_all('/^(?!\[)([^:]*):[ \t]*(.*?)$/m', $matches[$i], $tags);
      for ($j = 0; $j < count($tags[1]); $j++) {
	$row[$tags[1][$j]] = $tags[2][$j];
      }

      if ($row["id"] && strpos($row["id"], ':') > 0) {
	$id = explode(':', $row["id"], 2);
	$row["url"] = str_replace('#', $id[1], $idspaces[$id[0]]["url"]);
      }

      array_push($resultTerms, array(
	"cv" => $row["cv"], 
	"name" => $row["name"], 
	"accession" => $row["id"], 
	"definition" => $row["def"], 
	"url" => $row["url"]
      ));
    }
    usort($resultTerms, create_function('$a, $b', 'if (strlen($a["name"]) < strlen($b["name"])) { return -1; } elseif (strlen($a["name"]) > strlen($b["name"])) { return 1; } else { return 0; }'));
    return array_slice($resultTerms, 0, 50);
  }
  function getDBTermsFor($searchCv, $searchTerm, $limit=20) {
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
      }
      array_push($resultTerms, array(
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


