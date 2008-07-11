<?

  error_reporting(error_reporting() ^ E_NOTICE);
  $cvquery = 
    "SELECT 
      cvt.name AS name, 
      cv.name AS cv, 
      db.name || ':' || dbx.accession AS id, 
      cvt.definition AS def,
      db.urlprefix AS urlprefix
     FROM cvterm cvt
     INNER JOIN cv ON cvt.cv_id = cv.cv_id 
     INNER JOIN dbxref dbx ON cvt.dbxref_id = dbx.dbxref_id
     INNER JOIN db ON dbx.db_id = db.db_id
     WHERE cv.name = '?' AND cvt.name ILIKE '%?%'
     AND cvt.is_obsolete != 1
     AND dbx.accession NOT LIKE '% %'
     ORDER BY LENGTH(cvt.name)
     LIMIT ?";

  $modENCODE_DBFields_conf["form_data"]["host"] = "localhost";
  $modENCODE_DBFields_conf["form_data"]["dbname"] = "wiki_forms";
  $modENCODE_DBFields_conf["form_data"]["user"] = "db_public";
  $modENCODE_DBFields_conf["form_data"]["password"] = "ir84#4nm";
  $modENCODE_DBFields_conf["form_data"]["type"] = "postgres";

  $modENCODE_DBFields_conf["cvterms"]["cell"]["canonical_url"] = "http://obo.cvs.sourceforge.net/*checkout*/obo/obo/ontology/anatomy/cell_type/cell.obo";
  $modENCODE_DBFields_conf["cvterms"]["cell"]["canonical_url_type"] = "OBO";
  $modENCODE_DBFields_conf["cvterms"]["cell"]["host"] = "localhost";
  $modENCODE_DBFields_conf["cvterms"]["cell"]["dbname"] = "fb2007_02";
  $modENCODE_DBFields_conf["cvterms"]["cell"]["user"] = "db_public";
  $modENCODE_DBFields_conf["cvterms"]["cell"]["password"] = "ir84#4nm";
  $modENCODE_DBFields_conf["cvterms"]["cell"]["type"] = "postgres";
  $modENCODE_DBFields_conf["cvterms"]["cell"]["query"] = $cvquery;

  $modENCODE_DBFields_conf["cvterms"]["FlyBase anatomy CV"]["host"] = "localhost";
  $modENCODE_DBFields_conf["cvterms"]["FlyBase anatomy CV"]["dbname"] = "fb2007_02";
  $modENCODE_DBFields_conf["cvterms"]["FlyBase anatomy CV"]["user"] = "db_public";
  $modENCODE_DBFields_conf["cvterms"]["FlyBase anatomy CV"]["password"] = "ir84#4nm";
  $modENCODE_DBFields_conf["cvterms"]["FlyBase anatomy CV"]["type"] = "postgres";
  $modENCODE_DBFields_conf["cvterms"]["FlyBase anatomy CV"]["query"] = $cvquery;

  $modENCODE_DBFields_conf["cvterms"]["FlyBase development CV"]["host"] = "localhost";
  $modENCODE_DBFields_conf["cvterms"]["FlyBase development CV"]["dbname"] = "fb2007_02";
  $modENCODE_DBFields_conf["cvterms"]["FlyBase development CV"]["user"] = "db_public";
  $modENCODE_DBFields_conf["cvterms"]["FlyBase development CV"]["password"] = "ir84#4nm";
  $modENCODE_DBFields_conf["cvterms"]["FlyBase development CV"]["type"] = "postgres";
  $modENCODE_DBFields_conf["cvterms"]["FlyBase development CV"]["query"] = $cvquery;

  $modENCODE_DBFields_conf["cvterms"]["SO"]["canonical_url"] = "http://www.sequenceontology.org/release/2.2/so_2_2.obo";
  $modENCODE_DBFields_conf["cvterms"]["SO"]["canonical_url_type"] = "OBO";
  $modENCODE_DBFields_conf["cvterms"]["SO"]["host"] = "localhost";
  $modENCODE_DBFields_conf["cvterms"]["SO"]["dbname"] = "wormbase_175";
  $modENCODE_DBFields_conf["cvterms"]["SO"]["user"] = "db_public";
  $modENCODE_DBFields_conf["cvterms"]["SO"]["password"] = "ir84#4nm";
  $modENCODE_DBFields_conf["cvterms"]["SO"]["type"] = "postgres";
  $modENCODE_DBFields_conf["cvterms"]["SO"]["query"] = $cvquery;

  $modENCODE_DBFields_conf["cvterms"]["fly_gene"]["host"] = "localhost";
  $modENCODE_DBFields_conf["cvterms"]["fly_gene"]["dbname"] = "fb2007_02";
  $modENCODE_DBFields_conf["cvterms"]["fly_gene"]["user"] = "db_public";
  $modENCODE_DBFields_conf["cvterms"]["fly_gene"]["password"] = "ir84#4nm";
  $modENCODE_DBFields_conf["cvterms"]["fly_gene"]["type"] = "postgres";
  $modENCODE_DBFields_conf["cvterms"]["fly_gene"]["query"] = 
    "SELECT 
      f.name AS name,
      'Fly Genes' AS cv,
      dbx.accession AS id,
      '' AS def,
      'http://www.flybase.org/cgi-bin/fbidq.html\\?#' AS urlprefix
     FROM feature f 
     INNER JOIN cvterm cvt ON f.type_id = cvt.cvterm_id
     INNER JOIN cv ON cvt.cv_id = cv.cv_id
     INNER JOIN dbxref dbx ON f.dbxref_id = dbx.dbxref_id
     -- ? -- throw away 'cv' argument
     WHERE cv.name = 'SO' AND cvt.name = 'gene' AND f.name ILIKE '%?%'
     ORDER BY LENGTH(f.name)
     LIMIT ?";

  $modENCODE_DBFields_conf["cvterms"]["organism"]["host"] = "localhost";
  $modENCODE_DBFields_conf["cvterms"]["organism"]["dbname"] = "wormbase_175";
  $modENCODE_DBFields_conf["cvterms"]["organism"]["user"] = "db_public";
  $modENCODE_DBFields_conf["cvterms"]["organism"]["password"] = "ir84#4nm";
  $modENCODE_DBFields_conf["cvterms"]["organism"]["type"] = "postgres";
  $modENCODE_DBFields_conf["cvterms"]["organism"]["query"] = 
    "SELECT name, id, def, urlprefix FROM 
      (SELECT 
	    'N' AS name,
	    'N' AS id,
	    '' AS def,
	    'http://wiki.modencode.org/project/index.php/organisms/' AS urlprefix
      UNION
      SELECT 
	    genus || ' ' || species AS name, 
	    genus || ' ' || species AS id, 
	    comment AS def,
	    'http://wiki.modencode.org/project/index.php/organisms/' AS urlprefix
	   FROM organism 
	   -- ? -- throw away 'cv' argument
      ) AS organisms
      WHERE name ILIKE '%?%'
      ORDER BY LENGTH(name)
      LIMIT ?";

  $modENCODE_DBFields_conf["cvterms"]["worm_gene"]["host"] = "localhost";
  $modENCODE_DBFields_conf["cvterms"]["worm_gene"]["dbname"] = "wormbase_175";
  $modENCODE_DBFields_conf["cvterms"]["worm_gene"]["user"] = "db_public";
  $modENCODE_DBFields_conf["cvterms"]["worm_gene"]["password"] = "ir84#4nm";
  $modENCODE_DBFields_conf["cvterms"]["worm_gene"]["type"] = "postgres";
  $modENCODE_DBFields_conf["cvterms"]["worm_gene"]["query"] = 
    "SELECT 
      SUBSTRING(f.name FROM 6) AS name,
      'Worm Genes' AS cv,
      SUBSTRING(f.name FROM 6) AS id,
      '' AS def,
      'http://www.wormbase.org/db/gene/gene\\?name=#;class=Gene' AS urlprefix
     FROM feature f 
     INNER JOIN cvterm cvt ON f.type_id = cvt.cvterm_id
     INNER JOIN cv ON cvt.cv_id = cv.cv_id
     -- ? -- throw away 'cv' argument
     WHERE cv.name = 'SO' AND cvt.name = 'gene' AND SUBSTRING(f.name FROM 6) ILIKE '%?%'
     ORDER BY LENGTH(f.name)
     LIMIT ?";
  $modENCODE_DBFields_conf["cvterms"]["go"]["canonical_url"] = "http://www.berkeleybop.org/ontologies/obo-all/gene_ontology/gene_ontology.obo";
  $modENCODE_DBFields_conf["cvterms"]["go"]["canonical_url_type"] = "OBO";
$modENCODE_DBFields_conf["cvterms"]["go_cc"]["canonical_url"] = "http://www.berkeleybop.org/ontologies/obo-all/gene_ontology/gene_ontology.obo";
  $modENCODE_DBFields_conf["cvterms"]["go_cc"]["canonical_url_type"] = "OBO";

  $modENCODE_DBFields_conf["cvterms"]["modencode-helper"]["canonical_url"] = "http://wiki.modencode.org/project/extensions/DBFields/ontologies/modencode-helper.obo";
  $modENCODE_DBFields_conf["cvterms"]["modencode-helper"]["canonical_url_type"] = "OBO";

  $modENCODE_DBFields_conf["cvterms"]["cell_lines"]["canonical_url"] = "http://wiki.modencode.org/project/extensions/DBFields/ontologies/cell_lines.obo";
  $modENCODE_DBFields_conf["cvterms"]["cell_lines"]["canonical_url_type"] = "OBO";

  $modENCODE_DBFields_conf["cvterms"]["mged"]["canonical_url"] = "http://www.berkeleybop.org/ontologies/obo-all/mged/mged.obo";
  $modENCODE_DBFields_conf["cvterms"]["mged"]["canonical_url_type"] = "OBO";
  $modENCODE_DBFields_conf["cvterms"]["mged-material"]["canonical_url"] = "http://www.berkeleybop.org/ontologies/obo-all/mged/mged.obo";
  $modENCODE_DBFields_conf["cvterms"]["mged-material"]["canonical_url_type"] = "OBO";
  $modENCODE_DBFields_conf["cvterms"]["mged-char"]["canonical_url"] = "http://www.berkeleybop.org/ontologies/obo-all/mged/mged.obo";
  $modENCODE_DBFields_conf["cvterms"]["mged-char"]["canonical_url_type"] = "OBO";
  $modENCODE_DBFields_conf["cvterms"]["mged-protocol"]["canonical_url"] = "http://www.berkeleybop.org/ontologies/obo-all/mged/mged.obo";
  $modENCODE_DBFields_conf["cvterms"]["mged-protocol"]["canonical_url_type"] = "OBO";

  $modENCODE_DBFields_conf["cvterms"]["obi-process"]["canonical_url"] = "http://www.berkeleybop.org/ontologies/obo-all/obi/obi.obo";
  $modENCODE_DBFields_conf["cvterms"]["obi-process"]["canonical_url_type"] = "OBO";
  $modENCODE_DBFields_conf["cvterms"]["obi-biomaterial"]["canonical_url"] = "http://www.berkeleybop.org/ontologies/obo-all/obi/obi.obo";
  $modENCODE_DBFields_conf["cvterms"]["obi-biomaterial"]["canonical_url_type"] = "OBO";
  $modENCODE_DBFields_conf["cvterms"]["obi-data"]["canonical_url"] = "http://www.berkeleybop.org/ontologies/obo-all/obi/obi.obo";
  $modENCODE_DBFields_conf["cvterms"]["obi-data"]["canonical_url_type"] = "OBO";
  $modENCODE_DBFields_conf["cvterms"]["obi-digitalentity"]["canonical_url"] = "http://www.berkeleybop.org/ontologies/obo-all/obi/obi.obo";
  $modENCODE_DBFields_conf["cvterms"]["obi-digitalentity"]["canonical_url_type"] = "OBO";


  $modENCODE_DBFields_conf["cvterms"]["ModencodeWiki"]["canonical_url"] = "http://wiki.modencode.org/project/index.php?title=";
  $modENCODE_DBFields_conf["cvterms"]["ModencodeWiki"]["canonical_url_type"] = "URL";
  $modENCODE_DBFields_conf["cvterms"]["organism"]["canonical_url"] = "http://wiki.modencode.org/project/extensions/DBFields/DBFieldsCVTerm.php?cv=organism&validating=validating&term=";
  $modENCODE_DBFields_conf["cvterms"]["organism"]["canonical_url_type"] = "URL_DBFields";
  $modENCODE_DBFields_conf["cvterms"]["CARO"]["canonical_url"] = "http://obo.cvs.sourceforge.net/*checkout*/obo/obo/ontology/anatomy/caro/caro.obo";
  $modENCODE_DBFields_conf["cvterms"]["CARO"]["canonical_url_type"] = "OBO";
?>
