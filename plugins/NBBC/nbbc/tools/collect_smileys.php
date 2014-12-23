<?php

	// Copyright (c) 2008, the Phantom Inker.  All rights reserved.
	// 
	// Redistribution and use in source and binary forms, with or without
	// modification, are permitted provided that the following conditions
	// are met:
	// 
	// * Redistributions of source code must retain the above copyright
	//   notice, this list of conditions and the following disclaimer.
	// 
	// * Redistributions in binary form must reproduce the above copyright
	//   notice, this list of conditions and the following disclaimer in
	//   the documentation and/or other materials provided with the
  	// distribution.
	// 
	// THIS SOFTWARE IS PROVIDED BY THE PHANTOM INKER "AS IS" AND ANY EXPRESS
	// OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
	// WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
	// DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE
	// LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
	// CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
	// SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR
	// BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY,
	// WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE
	// OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN
	// IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

	// This script generates a table of smileys from the images found in the
	// smileys/ directory.  It is not intended to be called directly, but is
	// intended for use as part of the editing of "readme.html" as the list
	// of smileys change.

	require_once("../nbbc.php");

	function collect_smileys($file) {
		$lib = new BBCodeLibrary;
		$output = "";
		foreach ($lib->default_smileys as $smiley => $filename) {
			if ($filename == $file) {
				if (strlen($output) > 0) $output .= "  ";
				$output .= $smiley;
			}
		}
		return $output;
	}

	$dir = opendir("../smileys");
	$files = Array();
	while (($file = readdir($dir)) !== false) {
		if (!preg_match("/\\.(?:gif|jpg|jpe|jpeg|png)$/", $file))
			continue;
		$files[] = $file;
	}
	closedir($dir);

	sort($files);
?>
<html>

<head>
<title>Smiley Table</title>
<style><!--
	table.smiley_table { border-collapse: collapse; margin-bottom: 1em; }
	table.smiley_table th, table.smiley_table td { border: 1px solid #999; text-align: left;
		background-color: #EEE; padding: 0.5em 1em; }
	table.smiley_table td { font: 10pt Courier,monospace,mono; white-space: pre; }
	table.smiley_table tbody th { text-align: center; }	
--></style>
<base href="<?php print "http://" . $_SERVER['SERVER_NAME'] . dirname(dirname($_SERVER['REQUEST_URI'])); ?>" />
</head>

<body>

<p>NBBC comes with built-in support for <?php print count($files); ?> commonly-used smileys (emoticons).  They are:</p>

<div align='center'>
<table class='smiley_table'>
<thead>
<tr><th>Image</th><th>Filename</th><th>Smiley BBCode (what you type)</th></tr>
</thead>
<tbody>

<?php

	chdir("../smileys");

	foreach ($files as $file) {
		$smileys = htmlspecialchars(collect_smileys($file));
		$info = @getimagesize($file);
		print <<< EOI
<tr><th><img src='../smileys/$file' width='{$info[0]}' height='{$info[1]}' alt='$file' /></th>
	<td>$file</td>
	<td>$smileys</td></tr>


EOI;
	}

?>

</table>
</div>

</body>
</html>
