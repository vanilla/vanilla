#!/usr/bin/perl

# Copyright (c) 2008-9, the Phantom Inker.  All rights reserved.
# 
# Redistribution and use in source and binary forms, with or without
# modification, are permitted provided that the following conditions
# are met:
# 
# * Redistributions of source code must retain the above copyright
#   notice, this list of conditions and the following disclaimer.
# 
# * Redistributions in binary form must reproduce the above copyright
#   notice, this list of conditions and the following disclaimer in
#   the documentation and/or other materials provided with the
#   distribution.
# 
# THIS SOFTWARE IS PROVIDED BY THE PHANTOM INKER "AS IS" AND ANY EXPRESS
# OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
# WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
# DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE
# LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
# CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
# SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR
# BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY,
# WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE
# OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN
# IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

# This script takes the four NBBC files and merges them together, producing
# a single file that's smaller and that has no comments.

sub pack_contents {
	my $filename = $_[0];
	open FILE, "<../src/$filename" or die "Error: Cannot open $filename for reading.\n";
	my $output = "";
	while (<FILE>) {
		chomp;
		if (/<skip-when-compressing>/) {
			# Remove any sections we're supposed to leave out of the compressed version.
			while (<FILE>) {
				last if (/<\/skip-when-compressing>/);
			}
		}
		s/\/\/.*$//;
		s/[\x00-\x20]+/ /g;
		s/^ //;
		s/ $//;
		if (/^if \(\$this->debug\)$/) {
			# Absorb lines until we find one ending with a semicolon.
			while (<FILE>) {
				last if (/\;/);
			}
		}
		elsif (/^if \(\$this->debug\) \{$/) {
			# Absorb lines until we find one that's a closing curly brace.
			while (<FILE>) {
				last if (/\}/);
			}
		}
		elsif (/BBCode_Profiler/) {
			# Remove all profiling lines.
		}
		elsif (length $_ && !/require_once|(^\?>$)|(^<\?php$)/) {
			$output .= $_ . "\n";
		}
	}
	close FILE;

	return $output;
}

@files = ('nbbc_main.php', 'nbbc_lex.php', 'nbbc_lib.php', 'nbbc_email.php', 'nbbc_parse.php');

$output = <<EOI;
<?php
/*
This is a compressed copy of NBBC. Do not edit!

Copyright (c) 2008-9, the Phantom Inker.  All rights reserved.
Portions Copyright (c) 2004-2008 AddedBytes.com

Redistribution and use in source and binary forms, with or without
modification, are permitted provided that the following conditions
are met:

* Redistributions of source code must retain the above copyright
  notice, this list of conditions and the following disclaimer.

* Redistributions in binary form must reproduce the above copyright
  notice, this list of conditions and the following disclaimer in
  the documentation and/or other materials provided with the
  distribution.

THIS SOFTWARE IS PROVIDED BY THE PHANTOM INKER "AS IS" AND ANY EXPRESS
OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE
LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR
BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY,
WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE
OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN
IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/

EOI

foreach $file (@files) {
	$output .= pack_contents($file);
	$output .= "\n";
}

print $output;

