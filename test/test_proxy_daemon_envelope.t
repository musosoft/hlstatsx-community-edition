use strict;
use warnings;
use Test::More;
use FindBin;

my $source_path = "$FindBin::Bin/../src/scripts/proxy-daemon.pl";
open my $source, '<', $source_path or die "Cannot open proxy source: $!";
my $source_text = do { local $/; <$source> };
my ($envelope_regex) = $source_text =~ m{\$datagram =~ (/\^PROXY Key=.*?/s)};
die "Could not locate proxy envelope parser" unless $envelope_regex;

my $parse = eval "sub { my \$datagram = shift; return unless \$datagram =~ $envelope_regex; return (\$1, \$2, \$3); }";
die $@ unless $parse;

my $payload = 'L 10/31/2026 - 12:00:00: say "PROXY Key=chat text"';
my @parsed = $parse->("PROXY Key=shared-secret 192.0.2.10:27015PROXY $payload");
is_deeply(\@parsed, ['shared-secret', '192.0.2.10:27015', $payload],
    'payload text containing PROXY does not corrupt the envelope fields');

ok(!scalar($parse->("L 10/31/2026 - 12:00:00: PROXY Key=not-an-envelope")),
    'proxy marker must start the datagram');
ok(!scalar($parse->('PROXY Key=shared-secret bad addressPROXY payload')),
    'remote address cannot contain whitespace');

done_testing();
