#!/usr/bin/perl

use strict;

use Text::CSV;
use File::Temp;
use File::Copy;
use LWP::UserAgent;

sub contains {
  my ($elem, @array) = @_;
  foreach my $e (@array) {
    return 1 if $e eq $elem;
  }
  return 0;
}
sub hash_equal {
  my ($a, $b) = @_;
  return 0 if length(keys(%$a)) != length(keys(%$b));
  foreach my $k (keys(%$a)) {
    return 0 unless exists($b->{$k});
    return 0 if ($a->{$k} ne $b->{$k});
  }
  return 1;
}

sub parse {
  my ($fh) = @_;
  my @allvalues;
  my $parser = new Text::CSV();
  my $line = <$fh>; # Header line
  $parser->parse($line);
  my @header = $parser->fields();
  while (my $line = <$fh>) {
    $parser->parse($line);
    my @fields = $parser->fields();
    my %strain;
    for (my $i = 0; $i < scalar(@header); $i++) {
      $strain{$header[$i]} = $fields[$i];
    }
    push(@allvalues, \%strain);
  }
  return @allvalues;
}


my $file = $ARGV[0];
my $content;

my $url = "http://flystocks.bio.indiana.edu/bloomington.csv";
my $ua = new LWP::UserAgent();
$ua->timeout(20);

my $cont;
print STDERR "Downloading $url...\n";
my $response = $ua->get($url);
print STDERR "Parsing...\n";
die "Couldn't get catalog page: $url because " . $response->message unless $response->is_success;
$content = $response->content . "\n";


open $cont, '<', \$content or die "WFT";

my @allvalues = parse($cont);
close $cont;



my @required_headers = (
  'format-version: 1.2', 
  'remark: $Revision 1 $ Describes strains used in the modENCODE project',
  'idspace: BDSC_STRAIN http://flystocks.bio.indiana.edu/Reports/#.html "Bloomington Drosophila Stock Center at Indiana University"',
);
$content = "";
my @headers;
my %existing_terms;
if (-e $file) {
  open FH, "<", $file or die "Couldn't open $file for reading";
  {
    local $/;
    $/ = undef;
    $content = <FH>;
  }
  close FH;
  my ($header_text) = ($content =~ /^([^\[]*)\[/s);
  @headers = split(/(\r\n|\r|\n)/, $header_text);
  @headers = grep { $_ !~ /^\s*$/ } @headers;
  foreach my $rh (@required_headers) {
    push @headers, $rh unless contains($rh, @headers);
  }
  my @terms = ($content =~ m/(\[Term\](?:.(?!\[Term\]))*)/sg);
  foreach my $term (@terms) {
    $term =~ s/\[Term\]//g;
    $term =~ s/(^\s*)|(\s*$)//g;

    my @matches = ($term =~ m/^([^:]*):\s*(.*)$/mg);
    my %term_hash;
    for (my $i = 0; $i < scalar(@matches); $i+=2) {
      $term_hash{$matches[$i]} = $matches[$i+1];
    }
    $existing_terms{$term_hash{'id'}} = \%term_hash if $term_hash{'id'};
  }
} else {
  @headers = @required_headers;
}

foreach my $value (@allvalues) {
  next unless $value->{'Stk #'};
  my %newterm = (
    'id'   => 'BDSC_STRAIN:' . $value->{'Stk #'},
    'name' => $value->{'Genotype'},
    'def'  => $value->{'Comments'},
  );
  foreach my $var (keys(%newterm)) {
    $newterm{$var} =~ s/\\/\\\\/g;
    $newterm{$var} =~ s/!/\\!/g;
    $newterm{$var} =~ s/\<.\>//g;
    $newterm{$var} =~ s/\s+$//g; #trailing spaces
    $newterm{$var} =~ s/"/\\"/g;
  }
  $newterm{"def"} = '"' . $newterm{"def"} . '" [BBOP:modENCODE]';
  if (!defined($existing_terms{$newterm{'id'}}) || !hash_equal(\%newterm, $existing_terms{$newterm{'id'}})) {
    $existing_terms{$newterm{'id'}} = \%newterm;
  }
}

my $out = new File::Temp( UNLINK => 1 );
foreach my $header (@headers) {
  print $out $header . "\n";
}
print $out "\n";
foreach my $term (sort { $a->{'id'} cmp $b->{'id'} } values(%existing_terms)) {
  print $out "[Term]\n";
  foreach my $var (keys(%$term)) {
    print $out "$var: " . $term->{$var} . "\n";
  }
  print $out "\n";
}
$out->flush;

move($file, "$file.bak") or die "Couldn't create backup of $file" if (-e $file);
copy($out->filename, $file) or die "Couldn't copy " . $out->filename . " to $file";
close($out);
