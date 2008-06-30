#!/usr/bin/perl

use strict;

use LWP::UserAgent;

my $url = "https://dgrc.cgb.indiana.edu/cells/store/catalog.html";
my $ua = new LWP::UserAgent();
$ua->timeout(10);

my $response = $ua->get($url);
die "Couldn't get catalog page: $url because " . $response->message unless $response->is_success;
my $content = $response->content . "\n";

my $file = $ARGV[0];

my ($catalog_table) = ($content =~ m/<div[^>]*>Cell Line Catalog<\/div>.*?<table[^>]*>((?:.(?!<\/table))*)/sm);
my @rows = ($catalog_table =~ m/<tr>(.*?)<\/tr>/gsm);

my $header_row = shift(@rows);
my @headers = ($header_row =~ m/<th[^>]*>(.*?)<\/th>/gsm);

my @allvalues;
foreach my $row (@rows) {
  my @values = ($row =~ m/<td[^>]*>(.*?)<\/td>/gsm);
  my %values_hash;
  for (my $i = 0; $i < scalar(@values); $i++ ) {
    $values[$i] =~ s/^\s*//;
    $values[$i] =~ s/\s*$//;
    $values[$i] =~ s/<[^>]*>//g;
    $values[$i] =~ s/\s*\n+\s*/, /g;
    $values_hash{$headers[$i]} = $values[$i];
  }
  push(@allvalues, \%values_hash);
}


my $mode;
if (! -e $file) { 
  $mode = ">"; 
} else {
  $mode = "+<";
}
open FH, $mode, $file or die "Couldn't open $file for $mode";
{
  local $/;
  $/ = undef;
  $content = <FH>;
}
close FH;

my ($header_text) = ($content =~ m/((?:.(?!\[))*)/s);
$content =~ s/\Q$header_text\E//;
my @required_headers = (
  'format-version: 1.2', 
  'remark: $Revision 1 $ Describes cell lines used in the modENCODE project',
  'idspace: DGRC_INDIANA https://dgrc.cgb.indiana.edu/product/View?product=# "Indiana U. Dros. Genomics Resource Center Cell Line Catalog"',
);
foreach my $required_header (@required_headers) {
  if (!($header_text =~ m/^\Q$required_header\E/m)) { $header_text .= "\n" . $required_header; }
}

my @terms = ($content =~ m/(\[Term\](?:.(?!\[Term\]))*)/sg);
my %parsed_terms;
foreach my $term (@terms) {
  $term =~ s/\[Term\]//g;
  $term =~ s/(^\s*)|(\s*$)//g;
  my @matches = ($term =~ m/^([^:]*):\s*(.*)$/mg);
  my %term_hash;
  for (my $i = 0; $i < scalar(@matches); $i+=2) {
    $term_hash{$matches[$i]} = $matches[$i+1];
  }
  $parsed_terms{$term_hash{'id'} || $term_hash{'name'}} = \%term_hash;
}
#          {
#            'Tissue Source' => 'hemocyte',
#            'Species' => 'melanogaster',
#            'Stock Number' => '147',
#            'Special Features' => 'tumorous blood cells',
#            'Cell Line' => 'mbn2'
#          }
foreach my $value (@allvalues) {
  my %newterm = (
    'id'   => 'DGRC_INDIANA:' . $value->{'Stock Number'},
    'name' => $value->{'Cell Line'},
    'def'  => '',
  );
  foreach my $var (keys(%$value)) { $newterm{'def'} .= "$var: " . $value->{$var} . '. ' if length($value->{$var}); }
  chop($newterm{'def'});

  if (!defined($parsed_terms{$newterm{'id'}})) {
    $content .= "[Term]\n";
    foreach my $var (keys(%newterm)) {
      $newterm{$var} =~ s/\\/\\\\/g;
      $newterm{$var} =~ s/!/\\!/g;
      $newterm{$var} =~ s/"/\\"/g;
      if ($var eq "def") {
	$newterm{$var} = '"' . $newterm{$var} . '" [BBOP:modENCODE]';
      }
      $content .= "$var: " . $newterm{$var} . "\n";
    }
    $content .= "\n";
  }
  
}

$content = $header_text . "\n" . $content;

print $content;







