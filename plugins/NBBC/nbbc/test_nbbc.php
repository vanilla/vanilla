<?php

	//-----------------------------------------------------------------------------
	//
	//  test_nbbc.php
	//
	//  This file is part of NBBC, the New BBCode Parser.
	//
	//  NBBC implements a fully-validating, high-speed, extensible parser for the
	//  BBCode document language.  Its output is XHTML 1.0 Strict conformant no
	//  matter what its input is.  NBBC supports the full standard BBCode language,
	//  as well as comments, columns, enhanced quotes, spoilers, acronyms, wiki
	//  links, several list styles, justification, indentation, and smileys, among
	//  other advanced features.
	//
	//-----------------------------------------------------------------------------
	//
	//  Copyright (c) 2008-9, the Phantom Inker.  All rights reserved.
	//
	//  Redistribution and use in source and binary forms, with or without
	//  modification, are permitted provided that the following conditions
	//  are met:
	//
	//    * Redistributions of source code must retain the above copyright
	//       notice, this list of conditions and the following disclaimer.
	//
	//    * Redistributions in binary form must reproduce the above copyright
	//       notice, this list of conditions and the following disclaimer in
	//       the documentation and/or other materials provided with the
	//       distribution.
	//
	//  THIS SOFTWARE IS PROVIDED BY THE PHANTOM INKER "AS IS" AND ANY EXPRESS
	//  OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
	//  WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
	//  DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE
	//  LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
	//  CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
	//  SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR
	//  BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY,
	//  WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE
	//  OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN
	//  IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
	//
	//-----------------------------------------------------------------------------
	//
	//  This script gives the BBCode parser a good solid run for its money, testing
	//  most of the major features, the full standard-BBCode library, and testing
	//  various anti-features and possible security holes as well.  If all of the
	//  tests defined here pass, it's fairly likely that the BBCode parser works as
	//  designed.
	//
	//-----------------------------------------------------------------------------
	
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">

<head>

<title>New BBCode Parser (NBBC) Tests</title>

<style><!--
body { font: 10pt Arial,Helv,Helvetica; background-color: #FFF; color: #000; }
table.test_table { border-collapse: collapse; empty-cells: show; margin: 1em auto; }
table.test_table th, table.test_table td { padding: 2px 6px; font: 10pt Arial,Helv,Helvetica; text-align: left; }
table.test_table thead th { border: 1px solid #FFF; background-color: #339; color: #FFF; font-weight: bold; }
table.test_table tr.spacer th { height: 1.5em; }
table.test_table tr.group th { border: 1px solid #9AC; background-color: #CDF; font-weight: bold; }
table.test_table tr.test td { border: 1px solid #9AC; background-color: #F0F5FF; }
table.test_table tr.test td.good { background-color: #E0FFE0; color: #060; }
table.test_table tr.test td.bad { background-color: #CC0000; color: #FF0; font-weight: bold; text-align:center; }
table.test_table tr.error td { border: 1px solid #9AC; background-color: #FFD; color: #000; }
h1 { text-align: center; }
--></style>

</head>

<body>

<h1>New BBCode Parser (NBBC) Conformance Tests</h1>

<?php

	require_once("src/nbbc_main.php");		// Expanded version.
	//require_once("nbbc.php");				// Condensed version.

	$BBCodeTestSuite = Array(

		//-----------------------------------------------------------------------------------------

		"Input Validation Tests",
		Array(
			'descr' => "Unknown tags like [foo] get ignored.",
			'bbcode' => "This is [foo]a tag[/foo].",
			'html' => "This is [foo]a tag[/foo].",
		),
		Array(
			'descr' => "Broken tags like [foo get ignored.",
			'bbcode' => "This is [foo a tag.",
			'html' => "This is [foo a tag.",
		),
		Array(
			'descr' => "Broken tags like [/foo get ignored.",
			'bbcode' => "This is [/foo a tag.",
			'html' => "This is [/foo a tag.",
		),
		Array(
			'descr' => "Broken tags like [] get ignored.",
			'bbcode' => "This is [] a tag.",
			'html' => "This is [] a tag.",
		),
		Array(
			'descr' => "Broken tags like [/  ] get ignored.",
			'bbcode' => "This is [/  ] a tag.",
			'html' => "This is [/  ] a tag.",
		),
		Array(
			'descr' => "Broken tags like [/ get ignored.",
			'bbcode' => "This is [/ a tag.",
			'html' => "This is [/ a tag.",
		),
		Array(
			'descr' => "Broken [ tags before [b]real tags[/b] don't break the real tags.",
			'bbcode' => "Broken [ tags before [b]real tags[/b] don't break the real tags.",
			'html' => "Broken [ tags before <b>real tags</b> don't break the real tags.",
		),
		Array(
			'descr' => "Broken [tags before [b]real tags[/b] don't break the real tags.",
			'bbcode' => "Broken [tags before [b]real tags[/b] don't break the real tags.",
			'html' => "Broken [tags before <b>real tags</b> don't break the real tags.",
		),
		Array(
			'descr' => "[i][b]Mis-ordered nesting[/i][/b] gets fixed.",
			'bbcode' => "[i][b]Mis-ordered nesting[/i][/b] gets fixed.",
			'html' => "<i><b>Mis-ordered nesting</b></i> gets fixed.",
		),
		Array(
			'descr' => "[url=][b]Mis-ordered nesting[/url][/b] gets fixed.",
			'bbcode' => "[url=http://www.google.com][b]Mis-ordered nesting[/url][/b] gets fixed.",
			'html' => "<a href=\"http://www.google.com\" class=\"bbcode_url\"><b>Mis-ordered nesting</b></a> gets fixed.",
		),
		Array(
			'descr' => "[i]Unended blocks are automatically ended.",
			'bbcode' => "[i]Unended blocks are automatically ended.",
			'html' => "<i>Unended blocks are automatically ended.</i>",
		),
		Array(
			'descr' => "Unstarted blocks[/i] have their end tags ignored.",
			'bbcode' => "Unstarted blocks[/i] have their end tags ignored.",
			'html' => "Unstarted blocks[/i] have their end tags ignored.",
		),
		Array(
			'descr' => "[b]Mismatched tags[/i] are not matched to each other.",
			'bbcode' => "[b]Mismatched tags[/i] are not matched to each other.",
			'html' => "<b>Mismatched tags[/i] are not matched to each other.</b>",
		),
		Array(
			'descr' => "[center]Inlines and [b]blocks get[/b] nested correctly[/center].",
			'bbcode' => "[center]Inlines and [b]blocks get[/b] nested correctly[/center].",
			'html' => "\n<div class=\"bbcode_center\" style=\"text-align:center\">\nInlines and <b>blocks get</b> nested correctly\n</div>\n.",
		),
		Array(
			'descr' => "[b]Inlines and [center]blocks get[/center] nested correctly[/b].",
			'bbcode' => "[b]Inlines and [center]blocks get[/center] nested correctly[/b].",
			'html' => "<b>Inlines and </b>\n<div class=\"bbcode_center\" style=\"text-align:center\">\nblocks get\n</div>\nnested correctly.",
		),
		Array(
			'descr' => "BBCode is [B]case-insensitive[/b].",
			'bbcode' => "[cEnTeR][b]This[/B] is a [I]test[/i].[/CeNteR]",
			'html' => "\n<div class=\"bbcode_center\" style=\"text-align:center\">\n<b>This</b> is a <i>test</i>.\n</div>\n",
		),
		Array(
			'descr' => "Plain text gets passed through unchanged.",
			'bbcode' => "Plain text gets passed through unchanged.  b is not a tag and i is not a tag and neither is /i and neither is (b).",
			'html' => "Plain text gets passed through unchanged.  b is not a tag and i is not a tag and neither is /i and neither is (b).",
		),

		//-----------------------------------------------------------------------------------------

		"Special-Character Tests",
		Array(
			'descr' => "& and < and > and \" get replaced with HTML-safe equivalents.",
			'bbcode' => "This <woo!> &\"yeah!\" 'sizzle'",
			'html' => "This &lt;woo!&gt; &amp;&quot;yeah!&quot; 'sizzle'",
		),
		Array(
			'descr' => ":-) produces a smiley <img> element.",
			'bbcode' => "This is a test of the emergency broadcasting system :-)",
			'regex' => "/This is a test of the emergency broadcasting system <img src=\\\"smileys\\/smile.gif\\\" width=\\\"[0-9]*\\\" height=\\\"[0-9]*\\\" alt=\\\":-\\)\\\" title=\\\":-\\)\\\" class=\\\"bbcode_smiley\\\" \\/>/",
		),
		Array(
			'descr' => "--- does *not* produce a [rule] tag.",
			'bbcode' => "This is a test of the --- emergency broadcasting system.",
			'html' => "This is a test of the --- emergency broadcasting system.",
		),
		Array(
			'descr' => "---- does *not* produce a [rule] tag.",
			'bbcode' => "This is a test of the ---- emergency broadcasting system.",
			'html' => "This is a test of the ---- emergency broadcasting system.",
		),
		Array(
			'descr' => "----- produces a [rule] tag.",
			'bbcode' => "This is a test of the ----- emergency broadcasting system.",
			'html' => "This is a test of the\n<hr class=\"bbcode_rule\" />\nemergency broadcasting system.",
		),
		Array(
			'descr' => "--------- produces a [rule] tag.",
			'bbcode' => "This is a test of the --------- emergency broadcasting system.",
			'html' => "This is a test of the\n<hr class=\"bbcode_rule\" />\nemergency broadcasting system.",
		),
		Array(
			'descr' => "[-] does *not* produce a comment.",
			'bbcode' => "This is a test of the [- emergency broadcasting] system.",
			'html' => "This is a test of the [- emergency broadcasting] system.",
		),
		Array(
			'descr' => "[--] produces a comment.",
			'bbcode' => "This is a test of the [-- emergency broadcasting] system.",
			'html' => "This is a test of the  system.",
		),
		Array(
			'descr' => "[----] produces a comment.",
			'bbcode' => "This is a test of the [---- emergency broadcasting] system.",
			'html' => "This is a test of the  system.",
		),
		Array(
			'descr' => "[--] comments may contain - and [ and \" and ' characters.",
			'bbcode' => "This is a test of the [-- emergency - [ \" ' broadcasting] system.",
			'html' => "This is a test of the  system.",
		),
		Array(
			'descr' => "[--] comments may *not* contain newlines.",
			'bbcode' => "This is a test of the [-- emergency\n\rbroadcasting] system.",
			'html' => "This is a test of the [-- emergency<br />\nbroadcasting] system.",
		),
		Array(
			'descr' => "['] produces a comment.",
			'bbcode' => "This is a test of the ['emergency broadcasting] system.",
			'html' => "This is a test of the  system.",
		),
		Array(
			'descr' => "['] comments may contain [ and \" and ' characters.",
			'bbcode' => "This is a test of the ['emergency [ \" ' broadcasting] system.",
			'html' => "This is a test of the  system.",
		),
		Array(
			'descr' => "['] comments may *not* contain newlines.",
			'bbcode' => "This is a test of the [' emergency\n\rbroadcasting] system.",
			'html' => "This is a test of the [' emergency<br />\nbroadcasting] system.",
		),
		Array(
			'descr' => "[!-- --] produces a comment.",
			'bbcode' => "This is a test of the [!-- emergency broadcasting --] system.",
			'html' => "This is a test of the  system.",
		),
		Array(
			'descr' => "[!-- ] does *not* produce a viable comment.",
			'bbcode' => "This is a test of the [!-- emergency broadcasting ] system.",
			'html' => "This is a test of the [!-- emergency broadcasting ] system.",
		),
		Array(
			'descr' => "[!-- - -- ] [ --] produces a comment.",
			'bbcode' => "This is a test of the [!-- emergency - broadcasting -- system ] thingy --].",
			'html' => "This is a test of the .",
		),
		Array(
			'descr' => "[!-- - -- ] [ --] --] produces a comment with a --] left over.",
			'bbcode' => "This is a test of the [!-- emergency - broadcasting -- system ] thingy --] and other --] stuff.",
			'html' => "This is a test of the  and other --] stuff.",
		),
		Array(
			'descr' => "[!-- --] does not break any following tags outside it.",
			'bbcode' => "The [!-- quick brown --]fox jumps over the [b]lazy[/b] [i]dog[/i].",
			'html' => "The fox jumps over the <b>lazy</b> <i>dog</i>.",
		),
		Array(
			'descr' => "Tag marker mode '<' works correctly.",
			'bbcode' => "This is <b>a <i>test</b></i>.",
			'html' => "This is <b>a <i>test</i></b>.",
			'tag_marker' => '<',
		),
		Array(
			'descr' => "Tag marker mode '{' works correctly.",
			'bbcode' => "This is {b}a {i}test{/b}{/i}.",
			'html' => "This is <b>a <i>test</i></b>.",
			'tag_marker' => '{',
		),
		Array(
			'descr' => "Tag marker mode '(' works correctly.",
			'bbcode' => "This is (b)a (i)test(/b)(/i).",
			'html' => "This is <b>a <i>test</i></b>.",
			'tag_marker' => '(',
		),
		Array(
			'descr' => "Ampersand pass-through mode works correctly.",
			'bbcode' => "This is <b>a <i>test</b></i> &amp; some junk.",
			'html' => "This is <b>a <i>test</i></b> &amp; some junk.",
			'tag_marker' => '<',
		),

		//-----------------------------------------------------------------------------------------

		"Whitespace Tests",
		Array(
			'descr' => "Newlines get replaced with <br /> tags.",
			'bbcode' => "This\nis\r\na\n\rtest.",
			'html' => "This<br />\nis<br />\na<br />\ntest.",
		),
		Array(
			'descr' => "Newlines *don't* get replaced with <br /> tags in ignore-newline mode.",
			'bbcode' => "This\nis\r\na\n\rtest.",
			'html' => "This\nis\na\ntest.",
			'newline_ignore' => true,
		),
		Array(
			'descr' => "Space before and after newlines gets removed.",
			'bbcode' => "This \n \t is \na\n \x08test.",
			'html' => "This<br />\nis<br />\na<br />\ntest.",
		),
		Array(
			'descr' => "Whitespace doesn't matter inside tags after the tag name.",
			'bbcode' => "This [size = 4  ]is a test[/size ].",
			'html' => "This <span style=\"font-size:1.17em\">is a test</span>.",
		),
		Array(
			'descr' => "Whitespace does matter inside \"quotes\" in tags.",
			'bbcode' => "This [wstest=\"  Courier   New  \"]is a test[/wstest].",
			'html' => "This <span style=\"wstest:  Courier   New  \">is a test</span>.",
		),
		Array(
			'descr' => "Whitespace does matter inside 'quotes' in tags.",
			'bbcode' => "This [wstest='  Courier   New  ']is a test[/wstest].",
			'html' => "This <span style=\"wstest:  Courier   New  \">is a test</span>.",
		),
		Array(
			'descr' => "Whitespace is properly collapsed near block tags like [center].",
			'bbcode' => "Not centered.    \n    \n    [center]    \n    \n    A bold stone gathers no italics.    \n    \n    [/center]    \n    \n    Not centered.",
			'html' => "Not centered.<br />\n"
				. "\n<div class=\"bbcode_center\" style=\"text-align:center\">\n"
				. "<br />\n"
				. "A bold stone gathers no italics.<br />\n"
				. "\n</div>\n"
				. "<br />\n"
				. "Not centered.",
		),
		Array(
			'descr' => "[code]...[/code] should strip whitespace outside it but not inside it.",
			'bbcode' => "Not\ncode.\n"
				. "[code]    \n\n    This is a test.    \n\n    [/code]\n"
				. "Also not code.\n",
			'html' => "Not<br />\ncode.\n"
				. "<div class=\"bbcode_code\">\n"
				. "<div class=\"bbcode_code_head\">Code:</div>\n"
				. "<div class=\"bbcode_code_body\" style=\"white-space:pre\">\n    This is a test.    \n</div>\n"
				. "</div>\n"
				. "Also not code.<br />\n",
		),
		Array(
			'descr' => "[list] and [*] must consume correct quantities of whitespace.",
			'bbcode' => "[list]\n\n\t[*] One Box\n\n\t[*] Two Boxes\n\t[*] \n Three Boxes\n\n[/list]\n",
			'html' => "\n<ul class=\"bbcode_list\">\n<br />\n<li>One Box<br />\n</li>\n<li>Two Boxes</li>\n<li><br />\nThree Boxes<br />\n</li>\n</ul>\n",
		),

		//-----------------------------------------------------------------------------------------

		"Inline Tag Conversion Tests",
		Array(
			'descr' => "[i] gets correctly converted.",
			'bbcode' => "This is a test of the [i]emergency broadcasting system[/i].",
			'html' => "This is a test of the <i>emergency broadcasting system</i>.",
		),
		Array(
			'descr' => "[b] gets correctly converted.",
			'bbcode' => "This is a test of the [b]emergency broadcasting system[/b].",
			'html' => "This is a test of the <b>emergency broadcasting system</b>.",
		),
		Array(
			'descr' => "[u] gets correctly converted.",
			'bbcode' => "This is a test of the [u]emergency broadcasting system[/u].",
			'html' => "This is a test of the <u>emergency broadcasting system</u>.",
		),
		Array(
			'descr' => "[s] gets correctly converted.",
			'bbcode' => "This is a test of the [s]emergency broadcasting system[/s].",
			'html' => "This is a test of the <strike>emergency broadcasting system</strike>.",
		),
		Array(
			'descr' => "[sup] gets correctly converted.",
			'bbcode' => "This is a test of the [sup]emergency broadcasting system[/sup].",
			'html' => "This is a test of the <sup>emergency broadcasting system</sup>.",
		),
		Array(
			'descr' => "[sub] gets correctly converted.",
			'bbcode' => "This is a test of the [sub]emergency broadcasting system[/sub].",
			'html' => "This is a test of the <sub>emergency broadcasting system</sub>.",
		),
		Array(
			'descr' => "[font=Arial] gets correctly converted (simple font name).",
			'bbcode' => "This is a test of the [font=Arial]emergency broadcasting system[/font].",
			'html' => "This is a test of the <span style=\"font-family:'Arial'\">emergency broadcasting system</span>.",
		),
		Array(
			'descr' => "[font=Times New Roman] gets correctly converted (unquoted default value).",
			'bbcode' => "This is a test of the [font=Times New Roman]emergency broadcasting system[/font].",
			'html' => "This is a test of the <span style=\"font-family:'Times New Roman'\">emergency broadcasting system</span>.",
		),
		Array(
			'descr' => "[font=Times New Roman size=1] gets converted (trailing parameter identified).",
			'bbcode' => "This is a test of the [font=Times New Roman size=1]emergency broadcasting system[/font].",
			'html' => "This is a test of the <span style=\"font-family:'Times New Roman'\">emergency broadcasting system</span>.",
		),
		Array(
			'descr' => "[font=\"Courier New\"] gets correctly converted (quoted default value).",
			'bbcode' => "This is a test of the [font=\"Courier New\"]emergency broadcasting system[/font].",
			'html' => "This is a test of the <span style=\"font-family:'Courier New'\">emergency broadcasting system</span>.",
		),
		Array(
			'descr' => "[font=\"Courier New\" blarg size=1] gets converted (floating parameter ignored).",
			'bbcode' => "This is a test of the [font=\"Courier New\" blarg size=1]emergency broadcasting system[/font].",
			'html' => "This is a test of the <span style=\"font-family:'Courier New'\">emergency broadcasting system</span>.",
		),
		Array(
			'descr' => "[size=6] gets correctly converted.",
			'bbcode' => "This is a test of the [size=6]emergency broadcasting system[/size].",
			'html' => "This is a test of the <span style=\"font-size:2.0em\">emergency broadcasting system</span>.",
		),
		Array(
			'descr' => "[size=10] gets correctly converted.",
			'bbcode' => "This is a test of the [size=10]emergency broadcasting system[/size].",
			'html' => "This is a test of the <span style=\"font-size:1.0em\">emergency broadcasting system</span>.",
		),
		Array(
			'descr' => "[size=blah] gets ignored.",
			'bbcode' => "This is a test of the [size=blah]emergency broadcasting system[/size].",
			'html' => "This is a test of the [size=blah]emergency broadcasting system[/size].",
		),
		Array(
			'descr' => "[color=red] gets correctly converted.",
			'bbcode' => "This is a test of the [color=red]emergency broadcasting system[/color].",
			'html' => "This is a test of the <span style=\"color:red\">emergency broadcasting system</span>.",
		),
		Array(
			'descr' => "[color=gronk] gets correctly converted.",
			'bbcode' => "This is a test of the [color=gronk]emergency broadcasting system[/color].",
			'html' => "This is a test of the <span style=\"color:gronk\">emergency broadcasting system</span>.",
		),
		Array(
			'descr' => "[color=#FFF] gets correctly converted.",
			'bbcode' => "This is a test of the [color=#FFF]emergency broadcasting system[/color].",
			'html' => "This is a test of the <span style=\"color:#FFF\">emergency broadcasting system</span>.",
		),
		Array(
			'descr' => "[color=*#\$] is prohibited.",
			'bbcode' => "This is a test of the [color=*#\$]emergency broadcasting system[/color].",
			'html' => "This is a test of the [color=*#\$]emergency broadcasting system[/color].",
		),
		Array(
			'descr' => "[spoiler] gets converted.",
			'bbcode' => "Ssh, don't tell, but [spoiler]Darth is Luke's father[/spoiler]!",
			'html' => "Ssh, don't tell, but <span class=\"bbcode_spoiler\">Darth is Luke's father</span>!",
		),
		Array(
			'descr' => "[acronym] gets converted.",
			'bbcode' => "The [acronym=\"British Broadcasting Company\"]BBC[/acronym] airs [i]Doctor Who[/i] on Saturdays.",
			'html' => "The <span class=\"bbcode_acronym\" title=\"British Broadcasting Company\">BBC</span> airs <i>Doctor Who</i> on Saturdays.",
		),
		Array(
			'descr' => "[acronym] safely encodes its content.",
			'bbcode' => "The [acronym=_\"><script>alert(/XSS/.source)</script><x]Foo[/acronym] is safe.",
			'html' => "The <span class=\"bbcode_acronym\" title=\"_&quot;&gt;&lt;script&gt;alert(/XSS/.source)&lt;/script&gt;&lt;x\">Foo</span> is safe.",
		),

		//-----------------------------------------------------------------------------------------

		"URL Tests",
		Array(
			'descr' => "[url=...] (with no protocol given) gets converted.",
			'bbcode' => "This is a test of the [url=fleeb.html]emergency broadcasting system[/url].",
			'html' => "This is a test of the <a href=\"fleeb.html\" class=\"bbcode_url\">emergency broadcasting system</a>.",
		),
		Array(
			'descr' => "[url=http:...] gets converted.",
			'bbcode' => "This is a test of the [url=http://www.google.com]emergency broadcasting system[/url].",
			'html' => "This is a test of the <a href=\"http://www.google.com\" class=\"bbcode_url\">emergency broadcasting system</a>.",
		),
		Array(
			'descr' => "[url=http:...] gets converted correctly in plain mode.",
			'bbcode' => "This is a test of the [url=http://www.google.com]emergency broadcasting system[/url].",
			'html' => "This is a test of the <a href=\"http://www.google.com\">emergency broadcasting system</a>.",
			'plainmode' => true,
		),
		Array(
			'descr' => "Unquoted [url=http:...] with parameters gets converted.",
			'bbcode' => "This is a test of the [url=http://www.google.com?q=broadcasting&y=foo&x=bar]emergency broadcasting system[/url].",
			'html' => "This is a test of the <a href=\"http://www.google.com?q=broadcasting&amp;y=foo&amp;x=bar\" class=\"bbcode_url\">emergency broadcasting system</a>.",
		),
		Array(
			'descr' => "[url=https:...] gets converted.",
			'bbcode' => "This is a test of the [url=https://www.google.com]emergency broadcasting system[/url].",
			'html' => "This is a test of the <a href=\"https://www.google.com\" class=\"bbcode_url\">emergency broadcasting system</a>.",
		),
		Array(
			'descr' => "[url=ftp:...] gets converted.",
			'bbcode' => "This is a test of the [url=ftp://www.google.com]emergency broadcasting system[/url].",
			'html' => "This is a test of the <a href=\"ftp://www.google.com\" class=\"bbcode_url\">emergency broadcasting system</a>.",
		),
		Array(
			'descr' => "[url=mailto:...] gets converted.",
			'bbcode' => "This is a test of the [url=mailto:john@example.com]emergency broadcasting system[/url].",
			'html' => "This is a test of the <a href=\"mailto:john@example.com\" class=\"bbcode_url\">emergency broadcasting system</a>.",
		),
		Array(
			'descr' => "[url=javascript:...] is prohibited.",
			'bbcode' => "This is a test of the [url=javascript:alert()]emergency broadcasting system[/url].",
			'html' => "This is a test of the [url=javascript:alert()]emergency broadcasting system[/url].",
		),
		Array(
			'descr' => "[url=(unknown protocol):...] is prohibited.",
			'bbcode' => "This is a test of the [url=flooble:blarble]emergency broadcasting system[/url].",
			'html' => "This is a test of the [url=flooble:blarble]emergency broadcasting system[/url].",
		),
		Array(
			'descr' => "The [url]http://...[/url] form works correctly.",
			'bbcode' => "The [url]http://www.google.com[/url] form works correctly.",
			'html' => "The <a href=\"http://www.google.com\" class=\"bbcode_url\">http://www.google.com</a> form works correctly.",
		),
		Array(
			'descr' => "The [url]http://...[/url] form works correctly in plain mode.",
			'bbcode' => "The [url]http://www.google.com[/url] form works correctly.",
			'html' => "The <a href=\"http://www.google.com\">http://www.google.com</a> form works correctly.",
			'plainmode' => true,
		),
		Array(
			'descr' => "The [url]malformed...url...[/url] form is fully unprocessed.",
			'bbcode' => "The [url]a.imagehost.org/view/egdgdo[/url] form is fully unprocessed.",
			'html' => "The <a href=\"a.imagehost.org/view/egdgdo\" class=\"bbcode_url\">a.imagehost.org/view/egdgdo</a> form is fully unprocessed.",
		),
		Array(
			'descr' => "[url=\"...=...\"] contains an embedded equal sign (quotes work correctly).",
			'bbcode' => "The [url=\"http://www.google.com/?foo=bar&baz=frob\" bar=foo]link[/url] works correctly.",
			'html' => "The <a href=\"http://www.google.com/?foo=bar&amp;baz=frob\" class=\"bbcode_url\">link</a> works correctly.",
		),
		Array(
			'descr' => "[url=\"...=...\"] contains an embedded equal sign (test #2).",
			'bbcode' => "The [url=\"http://www.demourl.com/opinion.php?idopinion=234\"]Opinion[/url] is funny.",
			'html' => "The <a href=\"http://www.demourl.com/opinion.php?idopinion=234\" class=\"bbcode_url\">Opinion</a> is funny.",
		),
		Array(
			'descr' => "[url=\"...\" target=\"...\"] has its target ignored by default.",
			'bbcode' => "The [url=\"http://www.demourl.com/opinion.php?idopinion=234\" target=_blank]Opinion[/url] is funny.",
			'html' => "The <a href=\"http://www.demourl.com/opinion.php?idopinion=234\" class=\"bbcode_url\">Opinion</a> is funny.",
		),
		Array(
			'descr' => "[url=\"...\" target=\"...\"] has its target used when URL targeting is enabled.",
			'bbcode' => "The [url=\"http://www.demourl.com/opinion.php?idopinion=234\" target=_blank]Opinion[/url] is funny.",
			'html' => "The <a href=\"http://www.demourl.com/opinion.php?idopinion=234\" class=\"bbcode_url\" target=\"_blank\">Opinion</a> is funny.",
			'urltarget' => true,
		),
		Array(
			'descr' => "[url] has a target applied when forced URL targeting is enabled.",
			'bbcode' => "The [url=\"http://www.demourl.com/opinion.php?idopinion=234\"]Opinion[/url] is funny.",
			'html' => "The <a href=\"http://www.demourl.com/opinion.php?idopinion=234\" class=\"bbcode_url\" target=\"somewhere\">Opinion</a> is funny.",
			'urlforcetarget' => "somewhere",
		),
		Array(
			'descr' => "[url target=\"...\"] has its target ignored when forced URL targeting is enabled.",
			'bbcode' => "The [url=\"http://www.demourl.com/opinion.php?idopinion=234\" target=\"_blank\"]Opinion[/url] is funny.",
			'html' => "The <a href=\"http://www.demourl.com/opinion.php?idopinion=234\" class=\"bbcode_url\" target=\"somewhere\">Opinion</a> is funny.",
			'urlforcetarget' => "somewhere",
		),
		Array(
			'descr' => "[url] has a target applied even with URL target overriding.",
			'bbcode' => "The [url=\"http://www.demourl.com/opinion.php?idopinion=234\"]Opinion[/url] is funny.",
			'html' => "The <a href=\"http://www.demourl.com/opinion.php?idopinion=234\" class=\"bbcode_url\" target=\"somewhere\">Opinion</a> is funny.",
			'urlforcetarget' => "somewhere",
			'urltarget' => 'override',
		),
		Array(
			'descr' => "[url target=\"...\"] has its target applied with URL target overriding.",
			'bbcode' => "The [url=\"http://www.demourl.com/opinion.php?idopinion=234\" target=\"_blank\"]Opinion[/url] is funny.",
			'html' => "The <a href=\"http://www.demourl.com/opinion.php?idopinion=234\" class=\"bbcode_url\" target=\"_blank\">Opinion</a> is funny.",
			'urlforcetarget' => "somewhere",
			'urltarget' => 'override',
		),
		Array(
			'descr' => "[url=(includes a smiley)] is not converted into a smiley.",
			'bbcode' => "This is a test of the [url=http://www.google.com/foo:-P]emergency broadcasting system[/url].",
			'html' => "This is a test of the <a href=\"http://www.google.com/foo:-P\" class=\"bbcode_url\">emergency broadcasting system</a>.",
		),

		//-----------------------------------------------------------------------------------------

		"Embedded URL Tests",
		Array(
			'descr' => "Embedded URLs get detected and converted.",
			'bbcode' => "Go to http://www.google.com for your search needs!",
			'html' => "Go to <a href=\"http://www.google.com/\">http://www.google.com</a> for your search needs!",
			'detect_urls' => true,
		),
		Array(
			'descr' => "Embedded HTTPS URLs get detected and converted.",
			'bbcode' => "Go to https://www.google.com for your search needs!",
			'html' => "Go to <a href=\"https://www.google.com/\">https://www.google.com</a> for your search needs!",
			'detect_urls' => true,
		),
		Array(
			'descr' => "Embedded FTP URLs get detected and converted.",
			'bbcode' => "Go to ftp://www.google.com for your search needs!",
			'html' => "Go to <a href=\"ftp://www.google.com/\">ftp://www.google.com</a> for your search needs!",
			'detect_urls' => true,
		),
		Array(
			'descr' => "Embedded Javascript URLs are properly ignored.",
			'bbcode' => "Go to javascript:foo.com;alert(); for your search needs!",
			'html' => "Go to javascript:<a href=\"http://foo.com/\">foo.com</a>;alert(); for your search needs!",
			'detect_urls' => true,
		),
		Array(
			'descr' => "Embedded domain names get detected and converted.",
			'bbcode' => "Go to www.google.com for your search needs!",
			'html' => "Go to <a href=\"http://www.google.com/\">www.google.com</a> for your search needs!",
			'detect_urls' => true,
		),
		Array(
			'descr' => "Embedded IPs get detected and converted.",
			'bbcode' => "Go to 127.0.0.1:667/flarb for your own computer!",
			'html' => "Go to <a href=\"http://127.0.0.1:667/flarb\">127.0.0.1:667/flarb</a> for your own computer!",
			'detect_urls' => true,
		),
		Array(
			'descr' => "Embedded addresses are smart about being inside parentheses.",
			'bbcode' => "I love Google! (google.com)",
			'html' => "I love Google! (<a href=\"http://google.com/\">google.com</a>)",
			'detect_urls' => true,
		),
		Array(
			'descr' => "Embedded-URL detector disallows junk that only seems like a URL.",
			'bbcode' => "I browse alt.net.screw-you:80/flarb all the time.",
			'html' => "I browse alt.net.screw-you:80/flarb all the time.",
			'detect_urls' => true,
		),
		Array(
			'descr' => "Embedded-URL detector also detects e-mail addresses.",
			'bbcode' => "Send complaints to complaints@whitehouse.gov .",
			'html' => "Send complaints to <a href=\"mailto:complaints@whitehouse.gov\">complaints@whitehouse.gov</a> .",
			'detect_urls' => true,
		),
		Array(
			'descr' => "Embedded-URL detector takes precedence over the smiley detector.",
			'bbcode' => "This is a good dictionary:  http://www.amazon.com/Oxford-Dictionary-American-Usage-Style/dp/0195135083/ref=pd_bbs_sr_1?ie=UTF8&s=books&qid=1217890161&sr=8-1&x=p",
			'html' => "This is a good dictionary:  <a href=\"http://www.amazon.com/Oxford-Dictionary-American-Usage-Style/dp/0195135083/ref=pd_bbs_sr_1?ie=UTF8&amp;s=books&amp;qid=1217890161&amp;sr=8-1&amp;x=p\">http://www.amazon.com/Oxford-Dictionary-American-Usage-Style/dp/0195135083/ref=pd_bbs_sr_1?ie=UTF8&amp;s=books&amp;qid=1217890161&amp;sr=8-1&amp;x=p</a>",
			'detect_urls' => true,
		),

		//-----------------------------------------------------------------------------------------

		"Special URL-Like-Tag Tests",
		Array(
			'descr' => "[email] gets converted.",
			'bbcode' => "Send complaints to [email]john@example.com[/email].",
			'html' => "Send complaints to <a href=\"mailto:john@example.com\" class=\"bbcode_email\">john@example.com</a>.",
		),
		Array(
			'descr' => "[email] supports both forms.",
			'bbcode' => "Send complaints to [email=john@example.com]John Smith[/email].",
			'html' => "Send complaints to <a href=\"mailto:john@example.com\" class=\"bbcode_email\">John Smith</a>.",
		),
		Array(
			'descr' => "Bad addresses in [email] are ignored.",
			'bbcode' => "Send complaints to [email]jo\"hn@@@exa:mple.com[/email].",
			'html' => "Send complaints to [email]jo&quot;hn@@@exa:mple.com[/email].",
		),
/*
		Array(
			'descr' => "[video=youtube] gets converted.",
			'bbcode' => "Watch this cute doggy!!! [video=youtube]dQw4w9WgXcQ[/video]",
			'html' => "Watch this cute doggy!!! <object width=\"480\" height=\"385\"><param name=\"movie\" value=\"http://www.youtube.com/v/dQw4w9WgXcQ&hl=en_US&fs=1&\"></param><param name=\"allowFullScreen\" value=\"true\"></param><param name=\"allowscriptaccess\" value=\"always\"></param><embed src=\"http://www.youtube.com/v/dQw4w9WgXcQ&hl=en_US&fs=1&\" type=\"application/x-shockwave-flash\" allowscriptaccess=\"always\" allowfullscreen=\"true\" width=\"480\" height=\"385\"></embed></object>",
		),
		Array(
			'descr' => "[video=hulu] gets converted.",
			'bbcode' => "Gleeks: [video=hulu]yuo37ilvL7pUlsKJmA6R0g[/video]",
			'html' => "Gleeks: <object width=\"512\" height=\"288\"><param name=\"movie\" value=\"http://www.hulu.com/embed/yuo37ilvL7pUlsKJmA6R0g\"></param><param name=\"allowFullScreen\" value=\"true\"></param><embed src=\"http://www.hulu.com/embed/yuo37ilvL7pUlsKJmA6R0g\" type=\"application/x-shockwave-flash\"  width=\"512\" height=\"288\" allowFullScreen=\"true\"></embed></object>",
		),
		Array(
			'descr' => "[video] ignores unknown video services.",
			'bbcode' => "Watch this cute doggy!!! [video=flarb]abcdefg[/video]",
			'html' => "Watch this cute doggy!!! [video=flarb]abcdefg[/video]",
		),
		Array(
			'descr' => "[video] ignores bad video IDs.",
			'bbcode' => "Watch this cute doggy!!! [video=youtube]b!:=9_?[/video]",
			'html' => "Watch this cute doggy!!! [video=youtube]b!:=9_?[/video]",
		),
		Array(
			'descr' => "[video] correctly supports width= and height= modifiers.",
			'bbcode' => "Watch this cute doggy!!! [video=youtube width=320 height=240]dQw4w9WgXcQ[/video]",
			'html' => "Watch this cute doggy!!! <object width=\"320\" height=\"240\"><param name=\"movie\" value=\"http://www.youtube.com/v/dQw4w9WgXcQ&hl=en_US&fs=1&\"></param><param name=\"allowFullScreen\" value=\"true\"></param><param name=\"allowscriptaccess\" value=\"always\"></param><embed src=\"http://www.youtube.com/v/dQw4w9WgXcQ&hl=en_US&fs=1&\" type=\"application/x-shockwave-flash\" allowscriptaccess=\"always\" allowfullscreen=\"true\" width=\"320\" height=\"240\"></embed></object>",
		),
*/
		Array(
			'descr' => "The [[wiki]] special tag produces a wiki link.",
			'bbcode' => "This is a test of the [[wiki]] tag.",
			'html' => "This is a test of the <a href=\"/?page=wiki\" class=\"bbcode_wiki\">wiki</a> tag.",
		),
		Array(
			'descr' => "The [[wiki]] special tag does not convert [a-zA-Z0-9'\".:_-].",
			'bbcode' => "This is a test of the [[\"Ab1cd'Ef2gh_Ij3kl.,Mn4op:Qr9st-Uv0wx\"]] tag.",
			'html' => "This is a test of the <a href=\"/?page=%22Ab1cd%27Ef2gh_Ij3kl.%2CMn4op%3AQr9st_Uv0wx%22\" class=\"bbcode_wiki\">&quot;Ab1cd'Ef2gh_Ij3kl.,Mn4op:Qr9st-Uv0wx&quot;</a> tag.",
		),
		Array(
			'descr' => "The [[wiki]] special tag can contain spaces.",
			'bbcode' => "This is a test of the [[northwestern salmon]].",
			'html' => "This is a test of the <a href=\"/?page=northwestern_salmon\" class=\"bbcode_wiki\">northwestern salmon</a>.",
		),
		Array(
			'descr' => "The [[wiki]] special tag cannot contain newlines.",
			'bbcode' => "This is a test of the [[northwestern\nsalmon]].",
			'html' => "This is a test of the [[northwestern<br />\nsalmon]].",
		),
		Array(
			'descr' => "The [[wiki]] special tag can contain a title after a | character.",
			'bbcode' => "This is a test of the [[northwestern salmon|Northwestern salmon are yummy!]].",
			'html' => "This is a test of the <a href=\"/?page=northwestern_salmon\" class=\"bbcode_wiki\">Northwestern salmon are yummy!</a>.",
		),
		Array(
			'descr' => "The [[wiki]] special tag doesn't damage anything outside it.",
			'bbcode' => "I really loved reading [[arc 1|the first story arc]] because it was more entertaining than [[arc 2|the second story arc]] was.",
			'html' => "I really loved reading <a href=\"/?page=arc_1\" class=\"bbcode_wiki\">the first story arc</a> because it was more entertaining than <a href=\"/?page=arc_2\" class=\"bbcode_wiki\">the second story arc</a> was.",
		),
		Array(
			'descr' => "The [[wiki]] special tag condenses and trims internal whitespace.",
			'bbcode' => "This is a test of the [[  northwestern \t salmon   |   Northwestern   salmon are   yummy!  ]].",
			'html' => "This is a test of the <a href=\"/?page=northwestern_salmon\" class=\"bbcode_wiki\">Northwestern   salmon are   yummy!</a>.",
		),

		//-----------------------------------------------------------------------------------------

		"Images and Replaced-Tag Conversion Tests",
		Array(
			'descr' => "[img] produces an image.",
			'bbcode' => "This is Google's logo: [img]http://www.google.com/intl/en_ALL/images/logo.gif[/img].",
			'html' => "This is Google's logo: <img src=\"http://www.google.com/intl/en_ALL/images/logo.gif\" alt=\"logo.gif\" class=\"bbcode_img\" />.",
		),
		Array(
			'descr' => "[img] disallows a javascript: URL.",
			'bbcode' => "This is Google's logo: [img]javascript:alert()[/img].",
			'html' => "This is Google's logo: [img]javascript:alert()[/img].",
		),
		Array(
			'descr' => "[img] disallows a URL with an unknown protocol type.",
			'bbcode' => "This is Google's logo: [img]foobar:bar.jpg[/img].",
			'html' => "This is Google's logo: [img]foobar:bar.jpg[/img].",
		),
		Array(
			'descr' => "[img] disallows HTML content.",
			'bbcode' => "This is Google's logo: [img]<a href='javascript:alert(\"foo\")'>click me</a>[/img].",
			'html' => "This is Google's logo: [img]&lt;a href='javascript:alert(&quot;foo&quot;)'&gt;click me&lt;/a&gt;[/img].",
		),
		Array(
			'descr' => "[img] can produce a local image.",
			'bbcode' => "This is a smiley: [img]smile.gif[/img].",
			'html' => "This is a smiley: <img src=\"smileys/smile.gif\" alt=\"smile.gif\" width=\"16\" height=\"16\" class=\"bbcode_img\" />.",
		),
		Array(
			'descr' => "[img] can produce a local rooted URL.",
			'bbcode' => "This is a smiley: [img]/smile.gif[/img].",
			'html' => "This is a smiley: <img src=\"/smile.gif\" alt=\"smile.gif\" class=\"bbcode_img\" />.",
		),
		Array(
			'descr' => "[img] can produce a local relative URL.",
			'bbcode' => "This is a smiley: [img]../smile.gif[/img].",
			'html' => "This is a smiley: <img src=\"../smile.gif\" alt=\"smile.gif\" class=\"bbcode_img\" />.",
		),
		Array(
			'descr' => "[img] will skip nonexistent local images.",
			'bbcode' => "This is a smiley: [img]flarb.gif[/img].",
			'html' => "This is a smiley: [img]flarb.gif[/img].",
		),
		Array(
			'descr' => "[rule] produces a horizontal rule.",
			'bbcode' => "This is a test of the [rule] emergency broadcasting system.",
			'html' => "This is a test of the\n<hr class=\"bbcode_rule\" />\nemergency broadcasting system.",
		),
		Array(
			'descr' => "[br] is equivalent to a newline.",
			'bbcode' => "This is a newline.    [br]    And here we are!    \n  And more!",
			'html' => "This is a newline.<br />\nAnd here we are!<br />\nAnd more!",
		),

		//-----------------------------------------------------------------------------------------

		"Block Tag Conversion Tests",
		Array(
			'descr' => "[center]...[/center] should produce centered alignment.",
			'bbcode' => "Not centered.[center]A [b]bold[/b] stone gathers no italics.[/center]Not centered.",
			'html' => "Not centered.\n"
				. "<div class=\"bbcode_center\" style=\"text-align:center\">\n"
				. "A <b>bold</b> stone gathers no italics.\n"
				. "</div>\n"
				. "Not centered.",
		),
		Array(
			'descr' => "[left]...[/left] should produce left alignment.",
			'bbcode' => "Not left.[left]A [b]bold[/b] stone gathers no italics.[/left]Not left.",
			'html' => "Not left.\n"
				. "<div class=\"bbcode_left\" style=\"text-align:left\">\n"
				. "A <b>bold</b> stone gathers no italics.\n"
				. "</div>\n"
				. "Not left.",
		),
		Array(
			'descr' => "[right]...[/right] should produce right alignment.",
			'bbcode' => "Not right.[right]A [b]bold[/b] stone gathers no italics.[/right]Not right.",
			'html' => "Not right.\n"
				. "<div class=\"bbcode_right\" style=\"text-align:right\">\n"
				. "A <b>bold</b> stone gathers no italics.\n"
				. "</div>\n"
				. "Not right.",
		),
		Array(
			'descr' => "[indent]...[/indent] should produce indented content.",
			'bbcode' => "Not indented.[indent]A [b]bold[/b] stone gathers no italics.[/indent]Not indented.",
			'html' => "Not indented.\n"
				. "<div class=\"bbcode_indent\" style=\"margin-left:4em\">\n"
				. "A <b>bold</b> stone gathers no italics.\n"
				. "</div>\n"
				. "Not indented.",
		),
		Array(
			'descr' => "[code]...[/code] should reproduce its contents exactly as they're given.",
			'bbcode' => "Not code."
				. "[code]A [b]and[/b] & <woo>!\n\tAnd a ['hey'] and a [/nonny] and a ho ho ho![/code]"
				. "Also not code.",
			'html' => "Not code."
				. "\n<div class=\"bbcode_code\">\n"
				. "<div class=\"bbcode_code_head\">Code:</div>\n"
				. "<div class=\"bbcode_code_body\" style=\"white-space:pre\">A [b]and[/b] &amp; &lt;woo&gt;!\n"
					. "\tAnd a ['hey'] and a [/nonny] and a ho ho ho!</div>\n"
				. "</div>\n"
				. "Also not code.",
		),
		Array(
			'descr' => "[code]...[/code] should reproduce PHP source code undamaged.",
			'bbcode' => "Not code.\n"
				. "[code]\n"
				. "\$foo['bar'] = 42;\n"
				. "if (\$foo[\"bar\"] < 42) \$foo[] = 0;\n"
				. "[/code]\n"
				. "Also not code.\n",
			'html' => "Not code."
				. "\n<div class=\"bbcode_code\">\n"
				. "<div class=\"bbcode_code_head\">Code:</div>\n"
				. "<div class=\"bbcode_code_body\" style=\"white-space:pre\">\$foo['bar'] = 42;\n"
					. "if (\$foo[&quot;bar&quot;] &lt; 42) \$foo[] = 0;</div>\n"
				. "</div>\n"
				. "Also not code.<br />\n",
		),
		Array(
			'descr' => "<code>...</code> should not misbehave in '<' tag marker mode.",
			'bbcode' => "Not code."
				. "<code>A <b>and</b> & <woo>!\n\tAnd a [hey] and a [/nonny] and a ho ho ho!</code>"
				. "Also not code.",
			'html' => "Not code."
				. "\n<div class=\"bbcode_code\">\n"
				. "<div class=\"bbcode_code_head\">Code:</div>\n"
				. "<div class=\"bbcode_code_body\" style=\"white-space:pre\">A &lt;b&gt;and&lt;/b&gt; &amp; &lt;woo&gt;!\n"
					. "\tAnd a [hey] and a [/nonny] and a ho ho ho!</div>\n"
				. "</div>\n"
				. "Also not code.",
			'tag_marker' => '<',
		),
		Array(
			'descr' => "[quote]...[/quote] should produce a plain quote.",
			'bbcode' => "Outside the quote."
				. "[quote]A [b]and[/b] & <woo>!\n\tAnd a [hey] and a [/nonny] and a ho ho ho![/quote]"
				. "Also outside the quote.",
			'html' => "Outside the quote."
				. "\n<div class=\"bbcode_quote\">\n"
				. "<div class=\"bbcode_quote_head\">Quote:</div>\n"
				. "<div class=\"bbcode_quote_body\">A <b>and</b> &amp; &lt;woo&gt;!<br />\n"
					. "And a [hey] and a [/nonny] and a ho ho ho!</div>\n"
				. "</div>\n"
				. "Also outside the quote.",
		),
		Array(
			'descr' => "Multiple nested [quote]...[/quote] tags should produce nested quotes.",
			'bbcode' => "text0\n[quote]\n[quote]\n[quote]text1[/quote]\ntext2[/quote]\ntext3[/quote]\ntext4",
			'html' => "text0"
				. "\n<div class=\"bbcode_quote\">\n"
				. "<div class=\"bbcode_quote_head\">Quote:</div>\n"
				. "<div class=\"bbcode_quote_body\">"
				. "\n<div class=\"bbcode_quote\">\n"
				. "<div class=\"bbcode_quote_head\">Quote:</div>\n"
				. "<div class=\"bbcode_quote_body\">"
				. "\n<div class=\"bbcode_quote\">\n"
				. "<div class=\"bbcode_quote_head\">Quote:</div>\n"
				. "<div class=\"bbcode_quote_body\">"
				. "text1"
				. "</div>\n"
				. "</div>\n"
				. "text2"
				. "</div>\n"
				. "</div>\n"
				. "text3"
				. "</div>\n"
				. "</div>\n"
				. "text4",
		),
		Array(
			'descr' => "Multiple nested [quote]...[/quote] tags should produce nested quotes.",
			'bbcode' => "[quote]\n[quote]\n[quote]text1[/quote]\ntext2[/quote]\ntext3[/quote]\ntext4 :) text5 :o text6 :o",
			'html' => "\n<div class=\"bbcode_quote\">\n"
				. "<div class=\"bbcode_quote_head\">Quote:</div>\n"
				. "<div class=\"bbcode_quote_body\">"
				. "\n<div class=\"bbcode_quote\">\n"
				. "<div class=\"bbcode_quote_head\">Quote:</div>\n"
				. "<div class=\"bbcode_quote_body\">"
				. "\n<div class=\"bbcode_quote\">\n"
				. "<div class=\"bbcode_quote_head\">Quote:</div>\n"
				. "<div class=\"bbcode_quote_body\">"
				. "text1"
				. "</div>\n"
				. "</div>\n"
				. "text2"
				. "</div>\n"
				. "</div>\n"
				. "text3"
				. "</div>\n"
				. "</div>\n"
				. "text4 <img src=\"smileys/smile.gif\" width=\"16\" height=\"16\" alt=\":)\" title=\":)\" class=\"bbcode_smiley\" /> text5 :o text6 :o",
		),
		Array(
			'descr' => "[quote=John]...[/quote] should produce a quote from John.",
			'bbcode' => "Outside the quote."
				. "[quote=John]A [b]and[/b] & <woo>!\n\tAnd a [hey] and a [/nonny] and a ho ho ho![/quote]"
				. "Also outside the quote.",
			'html' => "Outside the quote."
				. "\n<div class=\"bbcode_quote\">\n"
				. "<div class=\"bbcode_quote_head\">John wrote:</div>\n"
				. "<div class=\"bbcode_quote_body\">A <b>and</b> &amp; &lt;woo&gt;!<br />\n"
					. "And a [hey] and a [/nonny] and a ho ho ho!</div>\n"
				. "</div>\n"
				. "Also outside the quote.",
		),
		Array(
			'descr' => "[quote=\"John Smith\"]...[/quote] should produce a quote from John Smith.",
			'bbcode' => "Outside the quote."
				. "[quote=\"John Smith\"]A [b]and[/b] & <woo>!\n\tAnd a [hey] and a [/nonny] and a ho ho ho![/quote]"
				. "Also outside the quote.",
			'html' => "Outside the quote."
				. "\n<div class=\"bbcode_quote\">\n"
				. "<div class=\"bbcode_quote_head\">John Smith wrote:</div>\n"
				. "<div class=\"bbcode_quote_body\">A <b>and</b> &amp; &lt;woo&gt;!<br />\n"
					. "And a [hey] and a [/nonny] and a ho ho ho!</div>\n"
				. "</div>\n"
				. "Also outside the quote.",
		),
		Array(
			'descr' => "[quote name= date= url=]...[/quote] should produce a detailed quote.",
			'bbcode' => "Outside the quote."
				. "[quote name=\"John Smith\" date=\"July 4, 1776\" url=\"http://www.constitution.gov\"]We hold these truths to be self-evident...[/quote]"
				. "Also outside the quote.",
			'html' => "Outside the quote."
				. "\n<div class=\"bbcode_quote\">\n"
				. "<div class=\"bbcode_quote_head\"><a href=\"http://www.constitution.gov\">John Smith wrote on July 4, 1776:</a></div>\n"
				. "<div class=\"bbcode_quote_body\">We hold these truths to be self-evident...</div>\n"
				. "</div>\n"
				. "Also outside the quote.",
		),
		Array(
			'descr' => "[quote name= date= url=]...[/quote] should disallow bad URLs.",
			'bbcode' => "Outside the quote."
				. "[quote name=\"John Smith\" date=\"July 4, 1776\" url=\"javascript:alert()\"]We hold these truths to be self-evident...[/quote]"
				. "Also outside the quote.",
			'html' => "Outside the quote."
				. "\n<div class=\"bbcode_quote\">\n"
				. "<div class=\"bbcode_quote_head\">John Smith wrote on July 4, 1776:</div>\n"
				. "<div class=\"bbcode_quote_body\">We hold these truths to be self-evident...</div>\n"
				. "</div>\n"
				. "Also outside the quote.",
		),
		Array(
			'descr' => "[quote=\"<script>javascript:alert()</script>\"] should not produce Javascript.",
			'bbcode' => "Outside the quote."
				. "[quote=\"<script>javascript:alert()</script>\"]A [b]and[/b] & <woo>!\n\tAnd a [hey] and a [/nonny] and a ho ho ho![/quote]"
				. "Also outside the quote.",
			'html' => "Outside the quote."
				. "\n<div class=\"bbcode_quote\">\n"
				. "<div class=\"bbcode_quote_head\">&lt;script&gt;javascript:alert()&lt;/script&gt; wrote:</div>\n"
				. "<div class=\"bbcode_quote_body\">A <b>and</b> &amp; &lt;woo&gt;!<br />\n"
					. "And a [hey] and a [/nonny] and a ho ho ho!</div>\n"
				. "</div>\n"
				. "Also outside the quote.",
		),
		Array(
			'descr' => "[columns] should produce columns.",
			'bbcode' => "Before the columns."
				. "[columns]This is a test.[nextcol]This is [b]beside[/b] it.[nextcol]This is [i]also[/i] beside it.[/columns]"
				. "After the columns.",
			'html' => "Before the columns."
				. "\n<table class=\"bbcode_columns\"><tbody><tr><td class=\"bbcode_column bbcode_firstcolumn\">\n"
				. "This is a test."
				. "\n</td><td class=\"bbcode_column\">\n"
				. "This is <b>beside</b> it."
				. "\n</td><td class=\"bbcode_column\">\n"
				. "This is <i>also</i> beside it."
				. "\n</td></tr></tbody></table>\n"
				. "After the columns.",
		),
		Array(
			'descr' => "[nextcol] doesn't do anything outside a [columns] block.",
			'bbcode' => "Here's some text.[nextcol]\nHere's some more.\n",
			'html' => "Here's some text.[nextcol]<br />\nHere's some more.<br />\n",
		),
		Array(
			'descr' => "Bad column misuse doesn't break layouts.",
			'bbcode' => "[center][columns]This is a test.[nextcol]This is also a [b]test[/b].[/center][/columns]",
			'html' => "\n<div class=\"bbcode_center\" style=\"text-align:center\">\n"
				. "\n<table class=\"bbcode_columns\"><tbody><tr><td class=\"bbcode_column bbcode_firstcolumn\">\n"
				. "This is a test."
				. "\n</td><td class=\"bbcode_column\">\n"
				. "This is also a <b>test</b>."
				. "\n</td></tr></tbody></table>\n"
				. "\n</div>\n",
		),
		
		"Lists and List Items",
		Array(
			'descr' => "[list] and [*] should produce an unordered list.",
			'bbcode' => "[list][*]One Box[*]Two Boxes[*]Three Boxes[/list]",
			'html' => "\n<ul class=\"bbcode_list\">\n<li>One Box</li>\n<li>Two Boxes</li>\n<li>Three Boxes</li>\n</ul>\n",
		),
		Array(
			'descr' => "[list] and [*] should produce an unordered list even without [/list].",
			'bbcode' => "[list][*]One Box[*]Two Boxes[*]Three Boxes",
			'html' => "\n<ul class=\"bbcode_list\">\n<li>One Box</li>\n<li>Two Boxes</li>\n<li>Three Boxes</li>\n</ul>\n",
		),
		Array(
			'descr' => "[list=circle] should produce an unordered list.",
			'bbcode' => "[list=circle][*]One Box[*]Two Boxes[*]Three Boxes[/list]",
			'html' => "\n<ul class=\"bbcode_list\" style=\"list-style-type:circle\">\n<li>One Box</li>\n<li>Two Boxes</li>\n<li>Three Boxes</li>\n</ul>\n",
		),
		Array(
			'descr' => "[list=disc] should produce an unordered list.",
			'bbcode' => "[list=disc][*]One Box[*]Two Boxes[*]Three Boxes[/list]",
			'html' => "\n<ul class=\"bbcode_list\" style=\"list-style-type:disc\">\n<li>One Box</li>\n<li>Two Boxes</li>\n<li>Three Boxes</li>\n</ul>\n",
		),
		Array(
			'descr' => "[list=square] should produce an unordered list.",
			'bbcode' => "[list=square][*]One Box[*]Two Boxes[*]Three Boxes[/list]",
			'html' => "\n<ul class=\"bbcode_list\" style=\"list-style-type:square\">\n<li>One Box</li>\n<li>Two Boxes</li>\n<li>Three Boxes</li>\n</ul>\n",
		),
		Array(
			'descr' => "[list=1] should produce an ordered list.",
			'bbcode' => "[list=1][*]One Box[*]Two Boxes[*]Three Boxes[/list]",
			'html' => "\n<ol class=\"bbcode_list\">\n<li>One Box</li>\n<li>Two Boxes</li>\n<li>Three Boxes</li>\n</ol>\n",
		),
		Array(
			'descr' => "[list=A] should produce an ordered list.",
			'bbcode' => "[list=A][*]One Box[*]Two Boxes[*]Three Boxes[/list]",
			'html' => "\n<ol class=\"bbcode_list\" style=\"list-style-type:upper-alpha\">\n<li>One Box</li>\n<li>Two Boxes</li>\n<li>Three Boxes</li>\n</ol>\n",
		),
		Array(
			'descr' => "[list=a] should produce an ordered list.",
			'bbcode' => "[list=a][*]One Box[*]Two Boxes[*]Three Boxes[/list]",
			'html' => "\n<ol class=\"bbcode_list\" style=\"list-style-type:lower-alpha\">\n<li>One Box</li>\n<li>Two Boxes</li>\n<li>Three Boxes</li>\n</ol>\n",
		),
		Array(
			'descr' => "[list=I] should produce an ordered list.",
			'bbcode' => "[list=I][*]One Box[*]Two Boxes[*]Three Boxes[/list]",
			'html' => "\n<ol class=\"bbcode_list\" style=\"list-style-type:upper-roman\">\n<li>One Box</li>\n<li>Two Boxes</li>\n<li>Three Boxes</li>\n</ol>\n",
		),
		Array(
			'descr' => "[list=i] should produce an ordered list.",
			'bbcode' => "[list=i][*]One Box[*]Two Boxes[*]Three Boxes[/list]",
			'html' => "\n<ol class=\"bbcode_list\" style=\"list-style-type:lower-roman\">\n<li>One Box</li>\n<li>Two Boxes</li>\n<li>Three Boxes</li>\n</ol>\n",
		),
		Array(
			'descr' => "[list=greek] should produce an ordered list.",
			'bbcode' => "[list=greek][*]One Box[*]Two Boxes[*]Three Boxes[/list]",
			'html' => "\n<ol class=\"bbcode_list\" style=\"list-style-type:lower-greek\">\n<li>One Box</li>\n<li>Two Boxes</li>\n<li>Three Boxes</li>\n</ol>\n",
		),
		Array(
			'descr' => "[list=georgian] should produce an ordered list.",
			'bbcode' => "[list=georgian][*]One Box[*]Two Boxes[*]Three Boxes[/list]",
			'html' => "\n<ol class=\"bbcode_list\" style=\"list-style-type:georgian\">\n<li>One Box</li>\n<li>Two Boxes</li>\n<li>Three Boxes</li>\n</ol>\n",
		),
		Array(
			'descr' => "[list=armenian] should produce an ordered list.",
			'bbcode' => "[list=armenian][*]One Box[*]Two Boxes[*]Three Boxes[/list]",
			'html' => "\n<ol class=\"bbcode_list\" style=\"list-style-type:armenian\">\n<li>One Box</li>\n<li>Two Boxes</li>\n<li>Three Boxes</li>\n</ol>\n",
		),
	);

	function MicroNow() {
		list($usec, $sec) = explode(" ", microtime());
		return ((float)$usec + (float)$sec);
	}

	function FormatTime($time) {
		return sprintf("%0.2f ms", $time * 1000);
	}

	$bbcode = new BBCode;

	$bbcode->AddRule('wstest', Array(
		'mode' => BBCODE_MODE_ENHANCED,
		'allow' => Array('_default' => '/^[a-zA-Z0-9._ -]+$/'),
		'template' => '<span style="wstest:{$_default}">{$_content}</span>',
		'class' => 'inline',
		'allow_in' => Array('listitem', 'block', 'columns', 'inline', 'link'),
	));	
	$bbcode->SetLocalImgDir("smileys");
	$bbcode->SetLocalImgURL("smileys");

	print "<table class='test_table' align='center'>\n"
		. "<thead><tr><th>Description</th><th>Result</th><th>Avg. Time</th></thead>\n"
		. "<tbody>\n";
		
	$numpassed = 0;
	$numfailed = 0;
	$numtested = 0;

	foreach ($BBCodeTestSuite as $test) {
		if (is_string($test)) {
			print "<tr class='spacer'><th colspan='3'></th></tr>\n"
				. "<tr class='group'><th colspan='3'>" . htmlspecialchars($test) . "</th></tr>\n";
		}
		else {
			$output = "<tr class='test'><td class='descr'>" . htmlspecialchars($test['descr']) . "</td>";

			if (@$test['debug'] == true)
				$bbcode->SetDebug(true);
			else $bbcode->SetDebug(false);
			$bbcode->SetTagMarker('[');
			$bbcode->SetAllowAmpersand(false);
			if (@$test['newline_ignore'] == true) $bbcode->SetIgnoreNewlines(true);
			else $bbcode->SetIgnoreNewlines(false);
			if (@$test['detect_urls'] == true) $bbcode->SetDetectURLs(true);
			else $bbcode->SetDetectURLs(false);
			if (@$test['urltarget'] == true) $bbcode->SetURLTargetable(true);
			else $bbcode->SetURLTargetable(false);
			if (is_string(@$test['urlforcetarget']))
				$bbcode->SetURLTarget($test['urlforcetarget']);
			else $bbcode->SetURLTarget(false);
			if (isset($test['plainmode']))
				$bbcode->SetPlainMode($test['plainmode']);
			else $bbcode->SetPlainMode(false);
			if (@$test['tag_marker'] == '<') {
				$bbcode->SetTagMarker('<');
				$bbcode->SetAllowAmpersand(true);
			}
			else if (isset($test['tag_marker']))
				$bbcode->SetTagMarker($test['tag_marker']);

			$result = $bbcode->Parse($test['bbcode']);

			$numtested++;

			if (isset($test['regex'])) {
				$pattern = $test['regex'];
				if (!preg_match($test['regex'], $result))
					$failed = true;
			}
			else {
				$pattern = $test['html'];
				$failed = ($result != $test['html']);
			}
			if (!$failed) {

				// If we didn't fail, run the same test twenty times so we can see how long
				// it takes, on average.
				$bbcode->SetDebug(false);
				$start = MicroNow();
				for ($i = 0; $i < 20; $i++)
					$result = $bbcode->Parse($test['bbcode']);
				$time = (MicroNow() - $start) / 20;

				$output .= "<td class='good'>Pass</td>"
					. "<td class='good' style='text-align:right;'>" . FormatTime($time) . "</td></tr>\n";
				$numpassed++;
			}
			else {
				$numfailed++;
				$output .= "<td class='bad' colspan='2'>FAILED</td></tr>\n"
					. "<tr class='error'><td colspan='3'><b>Tried:</b><br />\n"
					. "<tt style='white-space:pre-wrap;background-color:#FDB;'>"
						. htmlspecialchars(addcslashes($test['bbcode'], "\x00..\x09\x0B..\x1F\\\x7F..\xFF"))
						. "</tt><br /><br />\n"
					. "<b>Expected:</b> (length " . strlen($pattern) . ")<br />\n"
					. "<tt style='white-space:pre-wrap;background-color:#FDB;'>"
						. htmlspecialchars(addcslashes($pattern, "\x00..\x09\x0B..\x1F\\\x7F..\xFF"))
						. "</tt><br /><br />\n"
					. "<b>Got:</b> (length " . strlen($result) . ")<br />\n"
					. "<tt style='white-space:pre-wrap;background-color:#FDB;'>"
						. htmlspecialchars(addcslashes($result, "\x00..\x09\x0B..\x1F\\\x7F..\xFF"))
						. "</tt>\n"
					. "</td></tr>\n";
			}
			print $output;
		}
	}

	print "</tbody>\n"
		. "</table>\n";

	print "<div style='text-align:center; font-size:14pt; margin-bottom: 2em;'><b>Test Results:  $numtested tests performed.  $numpassed passed successfully, $numfailed failed.</b></div>\n";

?>

</body>

</html>
