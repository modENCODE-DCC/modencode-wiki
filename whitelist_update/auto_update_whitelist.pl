#!/usr/bin/perl

# Adds wikipages that have been used in 'released' projects to the
# Anonymous user whitelist.
# -- downloads list.txt from the submit site and finds released projects
# -- gets the wikipages they mention from the submit chado DB.
# -- adds the link to the wiki chado DB.

use DBI;
use LWP::Simple;
use strict;
use warnings;

# get login info
my ($mod_u, $mod_pw, $wik_u, $wik_pw) = @ARGV; # get username & passwords.

my $max_list_age = 60 * 60 * 24 * 6 ; # age in seconds -- say not quite 1 weeks.
#my $max_list_age = 5 ; # age in seconds -- say not quite 2 weeks.
my $list_time = (stat("list.txt"))[9] ;
my $list_age = time() - $list_time ;
if ($list_age > $max_list_age) {
  # Too old ! get it again
  print "Getting list.txt from submit site...\n";
  my $httpstatus = getstore("http://submit.modencode.org/submit/public/list.txt", "list.txt");
#  my $httpstatus = getstore("http://submit.modencode.org/submit/", "list.txt");
  if (grep {$_ eq $httpstatus} (302, 200)) {
    print "Got new list.txt with status $httpstatus!\n";
  } else {
    print "ERROR: Couldn't get new list.txt (status $httpstatus); using existing one.\n";
  }  
} else {
  print "Using existing list.txt; age $list_age less than max $max_list_age.\n";
}

print "Connecting to database...\n";
my $dbh_modencode = DBI->connect("dbi:Pg:dbname=modencode_chado;host=modencode-db.oicr.on.ca", $mod_u, $mod_pw, { AutoCommit => 0 }) or die "Couldn't get modENCODE database $!";
print "Connected to ModEncode database!\n";

 
my @projitems;
my $pid;
my $released;
# my $count = 0 ; # temp TODO Delme
open PROJECTLIST, "list.txt" or die $!;
print "Opened list file.\n";

# set up sth for getting search path & accessions from that project
my $sth_find_sp = $dbh_modencode->prepare("SELECT exists(SELECT schema_name FROM information_schema.schemata 
WHERE schema_name = ?)") or die $dbh_modencode->errstr;

my $sth_search_path = $dbh_modencode->prepare('SET search_path=?') or die "Couldn't prepare searchpath: " . $dbh_modencode->errstr;
my $sth_accession = $dbh_modencode->prepare(" SELECT dbxref.accession FROM dbxref 
  INNER JOIN db ON dbxref.db_id = db.db_id
  WHERE db.description = 'URL_mediawiki_expansion'
  GROUP BY dbxref.accession
  HAVING dbxref.accession != '__ignore'") or die "Couldn't prepare accession: " . $dbh_modencode->errstr;

my @accessions ;
my $sp_exists = 0; # does the schema exists?
my $projcount ; # count of released projects
print "Getting list of accessions:";
$|=1; # please flush buffer.
# For each project, add accessions to whitelist
while (my $projline = <PROJECTLIST>) {
  @projitems = split("\t", $projline );
  $pid = $projitems[0];
  $released = $projitems[9];
  if (!($released eq "released")) {
    #print "$pid not released but $released\n";
    next;
  }
  #print "$pid released, going for it...\n";
  ++$projcount ;
  print "." ;

  my $search_path = "modencode_experiment_$pid" . "_data" ;
  
  $sth_find_sp->execute($search_path) or die $dbh_modencode->errstr;
  ($sp_exists) = $sth_find_sp->fetchrow_array();
  if (! $sp_exists) { next ; } # project is probably not loaded.
  
  $sth_search_path->execute($search_path)  or die $dbh_modencode->errstr;
  $sth_accession->execute() or die "Couldn't execute accessions: " . $dbh_modencode->errstr;

  # Set accession to just the page name
  while (my ($accession) = $sth_accession->fetchrow_array()) {
    $accession =~ s|^\Qhttp://wiki.modencode.org/project/index.php?title=\E||g;
    $accession =~ s|&oldid=\d*\s*$||g;
    $accession = URI::Escape::uri_unescape($accession);
    push @accessions, $accession;
  }

# $count++ ; # rate-limiting for testing only
# last if ($count > 9 );

}
$sth_search_path->finish();
$sth_accession->finish();

# at this point we have all the accessions.
print "\nConnecting to wiki database and adding to whitelist:";

# Then, connect to the wiki database
my $dbh_wiki = DBI->connect("dbi:mysql:database=modencode_wiki;host=localhost", $wik_u, $wik_pw) or die "Couldn't get Wiki database $!";

# Get user IDs for anon and validator robot
my $sth_user_id = $dbh_wiki->prepare("SELECT user_id FROM user WHERE user_name = ?");
$sth_user_id->execute('Anonymous');
my ($anonymous_user_id) = $sth_user_id->fetchrow_array();
$sth_user_id->execute('Validator Robot');
my ($robot_user_id) = $sth_user_id->fetchrow_array();
$sth_user_id->finish();

my $wl_sth = $dbh_wiki->prepare("INSERT INTO whitelist
  (wl_user_id, wl_page_title, wl_allow_edit, wl_updated_by_user_id, wl_expires_on) 
  VALUES(?, ?, 0, ?, '')");

my $find_in_whitelist = $dbh_wiki->prepare("SELECT EXISTS(SELECT * from whitelist where wl_page_title = ?)");
my $found_accession;
my $newcount = 0 ;
my $oldcount = 0 ;

foreach my $accession (@accessions) {
  print "." ;
  # Check if it's already there
  $find_in_whitelist->execute($accession) or die $dbh_wiki->errstr;
  ($found_accession) = $find_in_whitelist->fetchrow_array();
  if ($found_accession) {
    ++$oldcount ;
  } else {
    # if not, stick it in
    ++$newcount ;
    $wl_sth->execute($anonymous_user_id, $accession, $robot_user_id) or die $dbh_wiki->errstr;
  }
}
$wl_sth->finish();

print "\nDone! $projcount released projects; $newcount whitelist entries added; $oldcount already existed.\n";

END { # make sure to disconnect properly if it crashes

  $dbh_modencode->disconnect();
  $dbh_wiki->disconnect() ;
}

