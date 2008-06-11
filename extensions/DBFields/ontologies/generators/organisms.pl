#!/usr/bin/perl

use strict;

use DBI;
use DBD::Pg;
use Date::Format;
use IO::Handle;

my %conf = (
  "dbname" => "wormbase_175",
  "host" => "smaug",
  "username" => "db_public",
  "password" => "ir84#4nm"
);

my $connstr = "dbi:Pg:dbname=" . $conf{"dbname"}; 
$connstr .= ";host=" . $conf{"host"} if length($conf{"host"});
$connstr .= ";port=" . $conf{"port"} if length($conf{"port"});

my $db = DBI->connect($connstr, $conf{"username"}, $conf{"password"}) or die "Couldn't connect to database";

my $get_organisms = $db->prepare("SELECT abbreviation, genus, species, common_name, comment FROM organism");

my $date = time2str("%d:%m:%Y %H:%M", time());

# BEGIN OUTPUT
print <<EOD
format-version: 1.2
date: $date
saved-by: yostinso
auto-generated-by: fly_genes.pl
synonymtypedef: common_name "Common Name" BROAD
synonymtypedef: abbreviation "Abbreviation" EXACT
default-namespace: organisms

EOD
;

$get_organisms->execute();

while (my $row = $get_organisms->fetchrow_hashref()) {
  foreach my $key (keys(%$row)) {
    $row->{$key} =~ s/\\/\\\\/g;
  }
  # Print term stanzas
  print "[Term]\n";
  print "id: Organisms:" . $row->{'genus'} . "_" . $row->{'species'} . "\n";
  print "name: " . $row->{'genus'} . " " . $row->{'species'} . "\n";
  print "namespace: organisms\n";
  print "synonym: \"" . $row->{'abbreviation'} . "\" EXACT abbreviation []\n" if $row->{'abbreviation'};
  print "synonym: \"" . $row->{'common_name'} . "\" BROAD common_name []\n" if $row->{'common_name'};
  print "\"" . $row->{'comment'}  . "\" [WormBase:organisms]\n" if $row->{'comment'};
  print "\n";
}
