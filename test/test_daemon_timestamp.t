use strict;
use warnings;
use Test::More;
use Time::Local;
use FindBin;

my $source_path = $ENV{HLX_DAEMON_SOURCE} || "$FindBin::Bin/../src/scripts/hlstats.pl";
open my $source, '<', $source_path or die "Cannot open daemon source: $!";
my $source_text = do { local $/; <$source> };
my ($parser) = $source_text =~ /(\t\tif \(\$s_output =~ s\/\^\(\?:\.\*\?\)\?L .*?)(?=\n\t\tif \(\$g_debug >= 4\))/s;
die "Could not locate daemon timestamp parser" unless $parser;

our @notices;
sub printEvent { push @notices, [@_]; }

my $run = eval(q~
    sub {
        my ($use_timestamp, $stdin, @packets) = @_;
        my @accepted;
        my ($ev_month, $ev_day, $ev_year, $ev_hour, $ev_min, $ev_sec, $ev_time);
        my $ev_remotetime = 123;
        my $ev_unixtime = 456;
        my $ev_daemontime = 789;
        my $ev_timestamp = 'unchanged';
        my $g_timestamp = $use_timestamp;
        my $g_stdin = $stdin;
        my $g_debug = 0;
        for my $s_output (@packets) {
~ . $parser . q~
            push @accepted, {
                body => $s_output,
                remote => $ev_remotetime,
                timestamp => $ev_timestamp,
                unix => $ev_unixtime,
                daemon => $ev_daemontime,
            };
        }
        return \@accepted;
    }
~);
die $@ unless $run;

my $valid = 'L 10/16/2026 - 06:26:30: World triggered "Round_Start"';
my $expected = timelocal(30, 26, 6, 16, 9, 2026);
my $ordinary = $run->(0, 0, $valid);
is(scalar(@$ordinary), 1, 'valid daemon line is accepted');
is($ordinary->[0]{body}, 'World triggered "Round_Start"', 'valid line loses only its timestamp');
is($ordinary->[0]{remote}, $expected, 'valid local timestamp conversion is preserved');
is($ordinary->[0]{unix}, 456, 'receipt-time mode is unchanged');

my $replay = $run->(1, 1, $valid);
is($replay->[0]{unix}, $expected, 'stdin replay still uses server time');
is($replay->[0]{daemon}, $expected, 'stdin replay daemon time remains aligned');
is($replay->[0]{timestamp}, '2026-10-16 06:26:30', 'timestamp string format is preserved');

my $mixed = $run->(0, 0, $valid, 'L 13/16/2026 - 06:26:30: malformed', $valid);
is(scalar(@$mixed), 2, 'invalid timestamp packet is discarded and parsing continues');
is($mixed->[1]{remote}, $expected, 'discarded packet does not leak/reuse a timestamp');
ok(grep($_->[0] == 998 && $_->[1] =~ /MALFORMED TIMESTAMP/, @notices), 'malformed timestamp is reported');

done_testing();
