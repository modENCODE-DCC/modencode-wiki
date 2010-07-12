<?
  //header("Content-type: text/plain");
  include_once("DBFieldsConf.php");
  include_once('DBFields.php');

  if ($_SERVER["SCRIPT_FILENAME"] == __FILE__) {
    header("Content-type: text/xml");
  }

  $searchTerm = isset($_GET["term"]) ? $_GET["term"] : null;
  $searchCv = isset($_GET["cv"]) ? $_GET["cv"] : null;
  $get_canonical_url = isset($_GET["get_canonical_url"]) && strlen($_GET["get_canonical_url"]) > 0 ? $_GET["get_canonical_url"] : null;
  $validating = isset($_GET["validating"]) && $_GET["validating"] == "validating" ? true : false;
  $delimiter = isset($_GET["delimiter"]) && strlen($_GET["delimiter"]) > 0 ? $_GET["delimiter"] : null;
  $brackets = isset($_GET["brackets"]) && $_GET["brackets"] == "off" ? "off" : "on";

  if ($get_canonical_url) {
    print "<result>\n";
    if (isset($modENCODE_DBFields_conf["cvterms"][$get_canonical_url]) && isset($modENCODE_DBFields_conf["cvterms"][$get_canonical_url]["canonical_url"])) {
      print "<canonical_url>" . $modENCODE_DBFields_conf["cvterms"][$get_canonical_url]["canonical_url"] . "</canonical_url>\n";
    }
    if (isset($modENCODE_DBFields_conf["cvterms"][$get_canonical_url]) && isset($modENCODE_DBFields_conf["cvterms"][$get_canonical_url]["canonical_url_type"])) {
      print "<canonical_url_type>" . $modENCODE_DBFields_conf["cvterms"][$get_canonical_url]["canonical_url_type"] . "</canonical_url_type>";
    }
    print "</result>\n";
    return;
  }
  
  if (!$searchCv || !$searchTerm) { return; }

  if (!$validating) {
    $resultTerms = getTermsFor($searchCv, $searchTerm);
    usort($resultTerms, create_function('$a, $b', 'if (strlen($a["name"]) < strlen($b["name"])) { return -1; } elseif (strlen($a["name"]) > strlen($b["name"])) { return 1; } else { return 0; }'));
    print "<terms>\n" . xmlifyTerms($resultTerms) . "</terms>";
  } else {
    $okayTerms = getExactTermsFor($searchCv, $searchTerm, $delimiter, $brackets);
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
  function getExactTermsFor($searchCv, $searchTerm, $delimiter = null, $brackets = "on") {
    $searchTerms = getTermsArray($searchTerm, $delimiter);
    $okayTerms = array();
    $multipleCvs = ((!is_null($delimiter) && strpos($searchCv, $delimiter) !== false) || ((is_null($delimiter) || $delimiter == "null") && strpos($searchCv, ",") !== false)) ? true : false;
    
    // termsToSearch : lists the terms to run db or file searches on
    // format: elements alternate between params for a search, and the cvterms
    // the results of that search should be matching.
    // The params & results are stored as hashes as follows :
    // [
    //  [ 
    //    "sst" => short search term, "scv" => search CV
    //  ]
    //  [ 
    //    "sts"   => [searchterm1, searchterm2, searchterm3... searchtermN] , 
    //    "names" => [name1, name2, name3... nameN]
    //    (names[x] corresponds to sts[x]; if the searchterm has no name, the name field will be NULL)
    //  ]
    // ]
    $termsToSearch = array();
    
    foreach ($searchTerms as $searchTerm) {
      if ($brackets != "off") {
	preg_match('/([^\[]*)(?:\[([^\]]*)\])?/', $searchTerm, $searchTermAndName);
	$searchTerm = trim($searchTermAndName[1]);
      }
      if (isset($searchTermAndName[2])) {
        $name = trim($searchTermAndName[2]);
      } else {
        unset($name);
      }
      if ($multipleCvs) {
        $CVandTerm = explode(":", trim($searchTerm));
        if (!isset($CVandTerm[1])) { continue; }
        $searchCv = $CVandTerm[0];
        $shortSearchTerm = $CVandTerm[1];
      } else {
        $shortSearchTerm = $searchTerm;
      }

      // Attach searchTerm to the corresponding shortSearchTerm and searchCV
      $paramsForGetTerms = array("sst" => $shortSearchTerm, "scv" => $searchCv);
      $nameToPush = NULL;
      if (isset($name)) { $nameToPush = $name; }
      // If the params already exist, add searchTerm to it; else, add the params
      if (in_array($paramsForGetTerms, $termsToSearch)) {
        $currentKey = array_search($paramsForGetTerms, $termsToSearch);
        // key is the params' location; the array for searchTerms is one location past
        array_push($termsToSearch[$currentKey + 1]["sts"], $searchTerm);
        array_push($termsToSearch[$currentKey + 1]["names"], $nameToPush);
      } else {
        // If the params don't already exist, push them, then push the searchterm + name
        $newTermAndName = array("sts" => array($searchTerm), "names" => array($nameToPush));
        array_push($termsToSearch, $paramsForGetTerms, $newTermAndName);
      }
      // Old & slow version of search
      if (isset($_GET["useoldsearch"])) {
        $resultTerms = getTermsFor($searchCv, $shortSearchTerm, $multipleCvs);
        if (count($resultTerms) < 1) { continue; }
        foreach ($resultTerms as $resultTerm) {
          if ($resultTerm["fullname"] != $searchTerm) { continue; }
          if (isset($name)) {
            $resultTerm["fullname"] .= " [$name]";
          }
          array_push($okayTerms, $resultTerm);
        }
      } 
    }

    // Then, perform the DB/ file search once for each unique set of shortSearchTerm + CV
    if (! isset($_GET["useoldsearch"])) {
    // Because of termsToSearch's format of alternating shortTerms&CVs with searchTerms,
    // shift the elements off two at a time
      while (! empty($termsToSearch) ) {
        $curParams = array_shift($termsToSearch); // curParams is sst and scv
        $curSearchTerms = array_shift($termsToSearch); // contains two arrays, sts and names
        $resultTerms = getTermsFor($curParams["scv"], $curParams["sst"], $multipleCvs);
        if (count($resultTerms) < 1) { continue; }
        // go through the results; if they match a relevant searchTerm, copy them over
        for ($i = 0; $i < count($curSearchTerms["sts"]); $i++) {
          $foundTerm = null;
          foreach ($resultTerms as $resultTerm) {
            if ($resultTerm["fullname"] == $curSearchTerms["sts"][$i]) {
              $foundTerm = $resultTerm;
              break;
            }
          }
          if ($foundTerm) {
            if (isset ($curSearchTerms["names"][$i])) {
              $nameToAdd = $curSearchTerms["names"][$i];
              $foundTerm["fullname"] .= " [$nameToAdd]";
            }
            array_push($okayTerms, $foundTerm);
          }
        }
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
    set_time_limit(90);
    $resultTerms = array();
    $path = dirname(__FILE__) . '/ontologies';

    $obo = fopen("$path/$searchCv.obo", "r");
    $header = "";
    while (($line = fgets($obo)) !== false) {
      if (preg_match('/\[([^\]]*)\]/', $line)) {
	break;
      } else {
	$header .= $line;
      }
    }

    $pattern = '/idspace: (\S+)\s+(\S+)\s+(?:"([^"]*)")?/';
    preg_match_all($pattern, $header, $matches);
    $idspaces = array();
    for ($i = 0; $i < count($matches[0]); $i++) {
      $idspaces[$matches[1][$i]]["url"] = $matches[2][$i];
      $idspaces[$matches[1][$i]]["description"] = $matches[3][$i];
    }

    $pattern = '/^name:\s*(.*' . preg_quote($searchTerm, '/') . '.*)$/im';
    $matches = array();
    $MAX_MATCHES = 5000;
    $section = "[Term]\n";
    while ($line = fgets($obo)) {
      if (preg_match('/^\s*\[([^\]]*)\]/', $line)) {
	if (preg_match($pattern, $section, $match) > 0) {
	  array_push($matches, $section);
	  if ($MAX_MATCHES-- <= 0) { break; }
	}
	$section = "[Term]\n";
      } else {
	$section .= $line;
      }
    }
    # One last section at EOF
    if ($MAX_MATCHES-- > 0) {
      if (preg_match($pattern, $section, $match) > 0) {
	array_push($matches, $section);
      }
    }

    fclose($obo);

    for ($i = 0; $i < count($matches); $i++) {
      $row = array(
	"id" => "",
	"cv" => "",
	"name" => "",
	"accession" => "",
	"definition" => "",
	"def" => "",
        "is_obsolete" => "",
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

      if ($row["is_obsolete"] != "true") {
        array_push($resultTerms, array(
          "fullname" => $multipleCvs ? $row["cv"] . ":" . $row["name"] : $row["name"],
          "cv" => $row["cv"], 
          "name" => $row["name"], 
          "accession" => $row["id"], 
          "definition" => $row["def"], 
          "url" => $row["url"]
        ));
      }
    }
    usort($resultTerms, 'lengthSort');
    set_time_limit(30);
    return array_slice($resultTerms, 0, 50);
  }
  function lengthSort($a, $b) {
    return strlen($a["name"]) - strlen($b["name"]);
  }
  function getDBTermsFor($searchCv, $searchTerm, $multipleCvs=false, $limit=20) {
    global $modENCODE_DBFields_conf;
    $resultTerms = array();

    if (!($modENCODE_DBFields_conf["cvterms"][$searchCv]["dbname"] && $modENCODE_DBFields_conf["cvterms"][$searchCv]["type"])) {
      return array();
    }
    $db = modENCODE_db_connect(
      $modENCODE_DBFields_conf["cvterms"][$searchCv]["host"], 
      $modENCODE_DBFields_conf["cvterms"][$searchCv]["dbname"], 
      $modENCODE_DBFields_conf["cvterms"][$searchCv]["user"], 
      $modENCODE_DBFields_conf["cvterms"][$searchCv]["password"], 
      $modENCODE_DBFields_conf["cvterms"][$searchCv]["type"]
    );
    if (!$db) {
      print "<div style=\"margin-left: 150px\"><br/>Couldn't connect to database " . $modENCODE_DBFields_conf["cvterms"][$searchCv]["dbname"] . " to find $searchCv terms.<br/>Controlled vocabulary terms may show as incorrect...<br/></div>";
      return array();
    }

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
      $row["cv"] = isset($row["cv"]) ? $row["cv"] : null;
        
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


