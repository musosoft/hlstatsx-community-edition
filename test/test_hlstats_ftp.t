use strict;
use warnings;
use Test::More;
use File::Path qw(make_path);
use File::Temp qw(tempdir);
use FindBin;
use Cwd qw(getcwd);

my $repo = "$FindBin::Bin/..";
my $ftp_script = "$repo/src/scripts/HLStatsFTP/hlstats-ftp.pl";
my $root = tempdir(CLEANUP => 1);
my $mock_lib = "$root/mock-lib";
make_path("$mock_lib/Net");

open my $ftp_mock, '>', "$mock_lib/Net/FTP.pm" or die "Create FTP mock: $!";
print {$ftp_mock} <<'MOCK';
package Net::FTP;
sub new { bless {}, shift }
sub login { 1 }
sub binary { 1 }
sub cwd { 1 }
sub ls { split /,/, $ENV{HLX_FTP_MOCK_FILES} }
sub mdtm { 123 }
sub get {
    my ($self, $name, $path) = @_;
    open my $file, '>', $path or die "Create downloaded log: $!";
    print {$file} "fixture log line\n";
    close $file;
    return 1;
}
sub close { 1 }
1;
MOCK
close $ftp_mock or die "Close FTP mock: $!";

sub run_case {
    my (%args) = @_;
    my $cwd = getcwd();
    chdir $args{dir} or die "chdir: $!";
    local $ENV{PERL5LIB} = $mock_lib;
    local $ENV{HLX_FTP_MOCK_FILES} = $args{files};
    local $ENV{HLX_CAPTURE} = $args{capture};
    local $ENV{HLX_PARSER_EXIT} = $args{parser_exit};
    system $^X, $ftp_script,
        '--gs-ip', $args{ip}, '--gs-port', '27500',
        '--ftp-usr', 'test', '--ftp-pwd', 'test', '--ftp-dir', '/logs', '--quiet';
    my $status = $?;
    chdir $cwd or die "restore cwd: $!";
    return $status >> 8;
}

my $bad_dir = "$root/bad-name";
mkdir $bad_dir or die "mkdir: $!";
is(run_case(dir => $bad_dir, ip => '127.0.0.1', files => '../escape.log,latest.log'), 0,
    'remote path-like log name is ignored');
ok(!-e "$root/escape.log", 'remote path does not escape the temporary directory');
ok(!-e "$bad_dir/hlstats-ftp-127.0.0.1-27500.last", 'ignored file does not advance the checkpoint');

my $safe_dir = "$root/shell-args";
mkdir $safe_dir or die "mkdir: $!";
my $capture = "$root/parser-args.bin";
my $hostile_ip = '127.0.0.1;>PWNED';
open my $parser, '>', "$safe_dir/hlstats.pl" or die "Create parser fixture: $!";
print {$parser} <<'PARSER';
#!/usr/bin/env perl
use strict;
use warnings;
local $/;
my $stdin = <STDIN>;
open my $capture, '>', $ENV{HLX_CAPTURE} or die "Create capture: $!";
print {$capture} join("\0", @ARGV), "\0--STDIN--\0", $stdin;
exit($ENV{HLX_PARSER_EXIT}) if $ENV{HLX_PARSER_EXIT};
PARSER
close $parser or die "Close parser fixture: $!";
chmod 0755, "$safe_dir/hlstats.pl" or die "chmod parser fixture: $!";

isnt(run_case(dir => $safe_dir, ip => $hostile_ip, files => 'valid.log,latest.log', capture => $capture, parser_exit => 7), 0,
    'parser failure is reported');
ok(!-e "$safe_dir/hlstats-ftp-$hostile_ip-27500.last", 'failed parse does not advance the checkpoint');

is(run_case(dir => $safe_dir, ip => $hostile_ip, files => 'valid.log,latest.log', capture => $capture), 0,
    'downloaded log is parsed with list-form system invocation');
ok(!-e "$safe_dir/PWNED", 'shell metacharacters in server address are not executed');
open my $captured, '<', $capture or die "Read parser capture: $!";
binmode $captured;
my $captured_data = do { local $/; <$captured> };
is($captured_data,
	join("\0", '--stdin', '--server-ip', $hostile_ip, '--server-port', '27500', '--STDIN--') . "\0fixture log line\n",
    'parser receives server address as one argument and the log on stdin');
ok(-e "$safe_dir/hlstats-ftp-$hostile_ip-27500.last", 'successful parse advances the checkpoint');

done_testing();
