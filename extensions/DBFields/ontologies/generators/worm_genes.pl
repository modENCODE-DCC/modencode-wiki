#!/usr/bin/perl

use strict;

use Date::Format;

open FH, $ARGV[0] or die "Couldn't open " . $ARGV[0];


my @cols = split(/[\t\r\n]/, <FH>);
use Data::Dumper;

my @expected_cols = ("Gene WB ID", "Gene Public Name", "Gene CGC Name", "Sequence Name (Gene)", "NCBI RefSeq mRNA", "Gene Description (Concise)");
foreach my $col (@expected_cols) {
  if (!in_array("Gene WB ID", @cols)) {
    die "Can't parse BioMart export without \"$col\" column.";
  }
}


my $date = time2str("%d:%m:%Y %H:%M", time());
print <<EOD
format-version: 1.2
date: $date
saved-by: yostinso
auto-generated-by: worm_genes.pl
synonymtypedef: cgc_name "Gene CGC Name" EXACT
synonymtypedef: sequence_name "Sequence Name (Gene)" EXACT
synonymtypedef: ncbi_refseq_mrna "NCBI RefSeq mRNA ID" EXACT
default-namespace: worm_genes

EOD
;

my $prev_gene_id;
my %cur_gene = ("synonyms" => []);
while (<FH>) {
  my @vals = split(/[\t\r\n]/, $_);
  my %cur_row;
  for (my $i = 0; $i < scalar(@vals); $i++) {
    $cur_row{$cols[$i]} = $vals[$i];
    $cur_row{"Gene WB ID"} =~ s/!/\\!/g;
    $cur_row{"Gene Public Name"} =~ s/!/\\!/g;
    $cur_row{"Gene Description (Concise)"} =~ s/"/\\"/g;
  }
  foreach my $key (keys(%cur_row)) {
    $cur_row{$key} =~ s/\\/\\\\/g;
    $cur_row{$key} =~ s/"/\\"/g;
  }

  if ($prev_gene_id && $prev_gene_id ne $cur_row{"Gene WB ID"}) {
    # Moving on to a new gene; print out current gene
    print "[Term]\n";
    print "id: WormBase:" . $cur_gene{"Gene WB ID"} . "\n";
    print "name: " . $cur_gene{"Gene Public Name"} . "\n" if length($cur_gene{"Gene Public Name"});
    print "def: \"" . $cur_gene{"Gene Description (Concise)"} . "\" [WormBase:BioMart]\n" if
    print join("\n", @{$cur_gene{"synonyms"}}) . "\n";
    print "\n";

    # Move on to next gene
    %cur_gene = ("synonyms" => []);
  }
  $prev_gene_id = $cur_row{"Gene WB ID"};
  $cur_gene{"Gene WB ID"} = $cur_row{"Gene WB ID"};
  $cur_gene{"Gene Public Name"} = $cur_row{"Gene Public Name"};
  $cur_gene{"Gene Description (Concise)"} = $cur_row{"Gene Description (Concise)"};

  my $synonym = "synonym: \"" . $cur_row{"Gene CGC Name"} . "\" EXACT cgc_name []";
  push @{$cur_gene{"synonyms"}}, $synonym if $cur_row{"Gene CGC Name"} && !in_array($synonym, $cur_gene{"synonyms"});

  $synonym = "synonym: \"" . $cur_row{"Sequence Name (Gene)"} . "\" EXACT sequence_name []";
  push @{$cur_gene{"synonyms"}}, $synonym if $cur_row{"Sequence Name (Gene)"} && !in_array($synonym, $cur_gene{"synonyms"});

  $synonym = "synonym: \"" . $cur_row{"NCBI RefSeq mRNA"} . "\" EXACT ncbi_refseq_mrna []";
  push @{$cur_gene{"synonyms"}}, $synonym if $cur_row{"NCBI RefSeq mRNA"} && !in_array($synonym, $cur_gene{"synonyms"});
}


sub in_array {
  my ($elem, @array) = @_;
  if (scalar(@array) == 1 && ref($array[0] eq "ARRAY")) {
    @array = @{$array[0]};
  }
  my $exists = scalar(grep { $_ eq $elem } @array);
  return $exists;
}


close FH;
