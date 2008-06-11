#!/usr/bin/perl

use strict;

use Text::CSV;
use LWP::UserAgent;
my $file = $ARGV[0];

my $url = "http://flystocks.bio.indiana.edu/bloomington.csv";
my $ua = new LWP::UserAgent();
$ua->timeout(20);

print STDERR "Downloading $url...\n";
my $response = $ua->get($url);
print STDERR "Parsing...\n";
die "Couldn't get catalog page: $url because " . $response->message unless $response->is_success;
my $content = $response->content . "\n";


open CONT, '<', \$content or die "WFT";

my @allvalues;

my $parser = new Text::CSV();

my $line = <CONT>; # Header line
$parser->parse($line);
my @header = $parser->fields();
while (my $line = <CONT>) {
  $parser->parse($line);
  my @fields = $parser->fields();
  my %strain;
  for (my $i = 0; $i < scalar(@header); $i++) {
    $strain{$header[$i]} = $fields[$i];
  }
  push(@allvalues, \%strain);
}
close CONT;


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
  'remark: $Revision 1 $ Describes strains used in the modENCODE project',
  'idspace: BDSC_STRAIN http://flystocks.bio.indiana.edu/Reports/#.html "Bloomington Drosophila Stock Center at Indiana University"',
);
foreach my $required_header (@required_headers) {
  if (!($header_text =~ m/^\Q$required_header\E/m)) { $header_text .= "\n" . $required_header ; }
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

foreach my $value (@allvalues) {
  my %newterm = (
    'id'   => 'BDSC_STRAIN:' . $value->{'Stk #'},
    'name' => $value->{'Genotype'},
    'def'  => $value->{'Comments'},
  );
  if (!defined($parsed_terms{$newterm{'id'}})) {
    $content .= "[Term]\n";
    foreach my $var (keys(%newterm)) {
      $newterm{$var} =~ s/\\/\\\\/g;
      $newterm{$var} =~ s/!/\\!/g;
      $newterm{$var} =~ s/"/\\"/g;
      if ($var ne "id") {
	$newterm{$var} = '"' . $newterm{$var} . '" [BBOP:modENCODE]';
      }
      $content .= "$var: " . $newterm{$var} . "\n";
    }
    $content .= "\n";
  }
}

$content = $header_text . "\n" . $content;

print $content;

