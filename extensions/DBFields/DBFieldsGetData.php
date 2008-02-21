<? 
  ini_set("soap.wsdl_cache_enabled", "0");
  ini_set("display_errors", true);

  ob_start('ob_gzhandler'); # Mediawiki will do gzipping, so we have to expect it here
  ob_start();
  chdir('../..');
  require("./includes/WebStart.php");
  chdir(dirname(__FILE__));
  require_once("DBFields.php");
  ob_end_clean();

  $server = new SoapServer(
    "DBFieldsService.wsdl",
    // The classmap here probably doesn't actually do anything
    // since I think it only applies to incoming data (e.g. a request)
    array(
      'classmap' => array(
	'FormData' => 'FormData',
	'FormValues' => 'FormValues',
	'LoginResult' => 'LoginResult',
	'FormDataQuery' => 'FormDataQuery'
      )
    )
  );
  if ($_GET["debug"]) {
    header("Content-type: text/plain");
    $dbfs = new DBFieldsService();
    if (isset($_GET["version"])) { $version = $_GET["version"]; } else { $version = null; }
    if (isset($_GET["form"])) { $form = $_GET["form"]; } else { $form = null; }
    $auth = ($dbfs->getLoginCookie('Validator_Robot', 'vdate_358'));
    global $wgUser;
    $wgUser = new StubUser();
    unset($_SESSION);
    print "Form data:";
    $url = "http://wiki.modencode.org/project/index.php?title=Sequencing&oldid=5358";
    $submission = new FormDataQuery();
    #$submission->name = $form;
    $submission->version = $version;
    $submission->auth = $auth;
    $submission->url = $url;
    print_r($dbfs->getFormData($submission));
  } else {
    $server->setClass("DBFieldsService");
    $server->handle();
  }

  class FormDataQuery {
    public $name;
    public $version;
    public $auth;
    public $url;
  }
  class FormData {
    public $name;
    public $version;
    public $values = array();
    public function __construct($name, $version) {
      $this->name = $name;
      $this->version = $version;
    }
    public function addValue($key, $value, $types=array()) {
      $found = false;
      foreach ($this->values as $existing_value) {
	if ($existing_value->name == $key) {
	  $existing_value->addValue($value);
	  foreach ($types as $type) {
	    $existing_value->addType($type);
	  }
	  $found = true; break;
	}
      }
      if (!$found) {
	$newvalues = new FormValues($key);
	$newvalues->addValue($value);
	foreach ($types as $type) {
	  $newvalues->addType($type);
	}
	array_push($this->values, $newvalues);
      }
    }
  }
  class FormValues {
    public $name;
    public $types = array();
    public $values = array();
    public function __construct($name) {
      $this->name = $name;
    }
    public function addType($type) {
      array_push($this->types, $type);
    }
    public function addValue($value) {
      array_push($this->values, $value);
    }
  }
  class LoginResult {
    public $result = '';
    public $lguserid = '';
    public $lgusername = '';
    public $lgtoken = '';
    public $cookieprefix = '';
    public $sessionid = '';
    public $wait = '';
    public $details = '';
  }

  class DBFieldsService {
    public function getLoginCookie($username, $password, $domain=false) {
      $api = new ApiMain(new FauxRequest(array(
	'action' => 'login',
	'lgname' => $username,
	'lgpassword' => $password,
	'lgdomain' => $domain
      )));
      $api->execute();
      $result = $api->getResultData();
      $loginResult = new LoginResult();
      foreach ($result["login"] as $key => $value) {
	if (isset($loginResult->$key)) {
	  $loginResult->$key = $value;
	}
      }
      global $wgCookiePrefix;
      if (strlen($loginResult->cookieprefix) <= 0) {
	$loginResult->cookieprefix = $wgCookiePrefix;
      }
      return $loginResult;
    }
    public function getFormData($submission) {
      global $modENCODE_DBFields_conf;


      $form = $submission->name;
      $version = $submission->version;
      $auth = $submission->auth;
      $wiki_url = urldecode(html_entity_decode($submission->url));


      # Get a form and revision ID from a URL, if provided
      if (strlen($form) && strlen($wiki_url)) {
	throw new SoapFault("Bad Request", "Both a form name and wiki URL were provided; please only provide one of the two.");
      }
      if (!strlen($form) && strlen($wiki_url)) {
	preg_match('/^\s*http:\/\/wiki.modencode.org\/project\/.*title=([^&]+)&.*oldid=(\d+)/', $wiki_url, $matches);
	$form = str_replace("_", " ", $matches[1]);
	$revisionId = $matches[2];
      }


      global $wgUser;
      if ($wgUser->mId <= 0 && $auth) {
	$wgUser = User::newFromSession();
	$oldCookies = $_COOKIE;
	$_COOKIE[$auth->cookieprefix . "UserID"] = $auth->lguserid;
	$_COOKIE[$auth->cookieprefix . "UserName"] = $auth->lgusername;
	$_COOKIE[$auth->cookieprefix . "Token"] = $auth->lgtoken;
	$wgUser->load();
	$_COOKIE = $oldCookies;
      }

      if ($wgUser->mId <= 0) {
	throw new SoapFault("Bad Authentication", "User " . $auth->lguserid . " not authorized to view this page!");
      }
      
      if (!strlen($form)) {
	throw new SoapFault("Bad Form", "No form was provided, and the URL '$url' did not successfully map to a form page.");
      }
      $db = modENCODE_db_connect(
	$modENCODE_DBFields_conf["form_data"]["host"], 
	$modENCODE_DBFields_conf["form_data"]["dbname"], 
	$modENCODE_DBFields_conf["form_data"]["user"], 
	$modENCODE_DBFields_conf["form_data"]["password"], 
	$modENCODE_DBFields_conf["form_data"]["type"]
      );
      $entry_name = modENCODE_db_escape($form, $db, $modENCODE_DBFields_conf["form_data"]["type"]);
      if (isset($version) && $version !== false) {
	$entry_version = modENCODE_db_escape($version, $db, $modENCODE_DBFields_conf["form_data"]["type"]);
	$res = modENCODE_db_query($db,
	  "SELECT MAX(wiki_revid) AS revisionid FROM data WHERE name = '$entry_name' AND version = $entry_version",
	  $modENCODE_DBFields_conf["form_data"]["type"]
	);
	if ($row = modENCODE_db_fetch_assoc($res, $modENCODE_DBFields_conf["form_data"]["type"])) {
	  $revisionId = $row["revisionId"];
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
      $db_values = array();
      $res = modENCODE_db_query($db, "SELECT name, version, key, value, wiki_revid FROM data WHERE name = '$entry_name' AND version = $version", $modENCODE_DBFields_conf["form_data"]["type"]);
      $formdata = null;
      $revId = 0;
      while ($row = modENCODE_db_fetch_assoc($res, $modENCODE_DBFields_conf["form_data"]["type"])) {
	$revId = $row["wiki_revid"];
	if (is_null($formdata)) {
	  $formdata = new FormData($row["name"], $row["version"]);
	}
	$values = preg_split('/,\s*/', $row["value"], -1, PREG_SPLIT_NO_EMPTY);
	foreach ($values as $value) {
	  $formdata->addValue($row["key"], trim($value));
	}
      }

      modENCODE_db_close($db, $modENCODE_DBFields_conf["form_data"]["type"]);

      // Grab any metadata from the dbfields form
      $wiki_parser = new Parser();
      $newRev = Revision::newFromId($revId);
      if ($newRev) {
	$wikitext = $wiki_parser->preprocess($newRev->revText(), $newRev->getTitle(), new ParserOptions(), $revId);
	
	preg_match('/<dbfields.*<\/dbfields>/ism', $wikitext, $matches);
	$dbfieldsText = $matches[0];
	preg_match_all('/<(input|select|textarea)[^>]*type="cvterm"[^>]*>/ism', $dbfieldsText, $matches);
	$cvtermInputs = $matches[0];
	foreach ($formdata->values as $formvalues) {
	  $key = $formvalues->name;
	  foreach ($cvtermInputs as $cvtermInput) {
	    if (preg_match('/name="' . $key . '"/ism', $cvtermInput)) {
	      preg_match('/cv="([^"]*)"/ism', $cvtermInput, $matches);
	      if (strlen($matches[1])) {
		$types = preg_split('/,\s*/', $matches[1], -1, PREG_SPLIT_NO_EMPTY);
		foreach ($types as $type) {
		  $formvalues->addType($type);
		}
	      }
	    }
	  }
	}
      }


      return $formdata;
    }
  }

?>
