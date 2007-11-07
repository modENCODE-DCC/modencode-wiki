#!/usr/bin/perl

use strict;

use LWP::UserAgent;
my $file = $ARGV[0];

my $url = "http://biosci.umn.edu/CGC/strains/gophstrnt.txt";
my $ua = new LWP::UserAgent();
$ua->timeout(20);

print STDERR "Downloading $url...\n";
my $response = $ua->get($url);
print STDERR "Parsing...\n";
die "Couldn't get catalog page: $url because " . $response->message unless $response->is_success;
my $content = $response->content . "\n";

open CONT, '<', \$content or die "WFT";

my $current_strain = {};
my @allvalues;
my $prevkey;
while (my $line = <CONT>) {
  $line =~ s/^[\r\n]+|[\r\n]+$//;
  $line =~ s/^\s+|\s+$//;
  if ($line =~ m/^[=-]+$/) {
    if (length($current_strain->{'Strain'})) {
      push(@allvalues, $current_strain);
    }
    $current_strain = {};
    next;
  }
  my ($key, $val) = ($line =~ m/^\s*(\S[^:]*):\s*(.*$)/);
  if (length($key)) { 
    $prevkey = $key; 
  } else {
    $val = $line;
  }
  if (length($prevkey)) {
    $current_strain->{$prevkey} .= (!length($key)) ? ' ' : '';
    $current_strain->{$prevkey} .= $val;
  }
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
  'remark: $Revision 1 $ Describes worm strains used in the modENCODE project',
  'idspace: CGC_STRAIN http://wormbase.org/db/gene/strain?class=Strain;name=# "Caenorhabditis Genetics Center Strain"',
);
foreach my $required_header (@required_headers) {
  if (!($header_text =~ m/^\Q$required_header\E/m)) { $header_text .= $required_header . "\n"; }
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
    'id'   => 'CGC_STRAIN:' . $value->{'Strain'},
    'name' => $value->{'Strain'},
    'def'  => $value->{'Description'},
  );
  if (!defined($parsed_terms{$newterm{'id'}})) {
    $content .= "[Term]\n";
    foreach my $var (keys(%newterm)) {
      $content .= "$var: " . $newterm{$var} . "\n";
    }
    $content .= "\n";
  }
}

$content = $header_text . $content;

print $content;

