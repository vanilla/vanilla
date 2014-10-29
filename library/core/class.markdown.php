<?php
/* This is an extension of the MarkdownExtra class from PHP Markdown.
 * It includes our Markdown customizations.
 */

require_once PATH_LIBRARY.'/vendors/PHPMarkdown/Michelf/MarkdownExtra.inc.php';

class MarkdownVanilla extends Michelf\MarkdownExtra {

	/* Add spoiler tag: >! */
	public function __construct() {
		$this->block_gamut += array(
			"doSpoilers"        => 55,
			);
		parent::__construct();
	}
	function doSpoilers($text) {
		$text = preg_replace_callback('/
			  (								# Wrap whole match in $1
				(?>
				  ^[ ]*>![ ]?			# ">" at the start of a line
					.+\n					# rest of the first line
				  (.+\n)*					# subsequent consecutive lines
				  \n*						# blanks
				)+
			  )
			/xm',
			array(&$this, '_doSpoilers_callback'), $text);

		return $text;
	}
	function _doSpoilers_callback($matches) {
		$bq = $matches[1];
		# trim one level of quoting - trim whitespace-only lines
		$bq = preg_replace('/^[ ]*>![ ]?|^[ ]+$/m', '', $bq);
		$bq = $this->runBlockGamut($bq);		# recurse

		$bq = preg_replace('/^/m', "  ", $bq);
		# These leading spaces cause problem with <pre> content, 
		# so we need to fix that:
		$bq = preg_replace_callback('{(\s*<pre>.+?</pre>)}sx', 
			array(&$this, '_doSpoilers_callback2'), $bq);

		return "\n". $this->hashBlock("<div class=\"Spoiler\">\n$bq\n</div>")."\n\n";
	}
	function _doSpoilers_callback2($matches) {
		$pre = $matches[1];
		$pre = preg_replace('/^  /m', '', $pre);
		return $pre;
	}

	/* Same as parent, but we added class="Quote" */
	protected function _doBlockQuotes_callback($matches) {
		$bq = $matches[1];
		# trim one level of quoting - trim whitespace-only lines
		$bq = preg_replace('/^[ ]*>[ ]?|^[ ]+$/m', '', $bq);
		$bq = $this->runBlockGamut($bq);		# recurse

		$bq = preg_replace('/^/m', "  ", $bq);
		# These leading spaces cause problem with <pre> content, 
		# so we need to fix that:
		$bq = preg_replace_callback('{(\s*<pre>.+?</pre>)}sx', 
			array($this, '_doBlockQuotes_callback2'), $bq);

		return "\n". $this->hashBlock("<blockquote class=\"Quote\">\n$bq\n</blockquote>")."\n\n";
	}

	/* Same as parent, but do <pre><code> if there's newlines */
	protected function makeCodeSpan($code) {
		$code = htmlspecialchars(trim($code), ENT_NOQUOTES);
		if (strpos($code, "\n"))
			return $this->hashPart("<pre><code>$code</code></pre>");
		return $this->hashPart("<code>$code</code>");
	}
}
