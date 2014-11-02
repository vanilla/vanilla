<?php
/**
 * @copyright 2014 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
 * @package Garden
 * @since 2.2
 */

// Include vendor library PHP Markdown.
require_once PATH_LIBRARY.'/vendors/PHPMarkdown/Michelf/MarkdownExtra.inc.php';

/**
 * Our Markdown customizations as an extension of PHP Markdown.
 *
 * Vendor library has 2 edits:
 *    - class `Markdown` must extend \Gdn_Pluggable.
 *    - class `Markdown` __construct() must call parent::__construct().
 */
class MarkdownVanilla extends Michelf\MarkdownExtra {

   public function __construct() {
      parent::__construct();

      // Hook in strikeout by default.
      $this->span_gamut += array(
			"doStrikeout" => 15,
      );
   }

   /**
    * Instructions to handle strike tag: ~~text~~
    *
    * @param $text
    * @return string HTML.
    */
   protected function doStrikeout($text) {
		$text = preg_replace_callback('/
				~~	   # open
				(.+?)	# $1 = strike text
				~~		# close
			/xm',
			array($this, '_doStrikeout_callback'), $text);

		return $text;
	}

	/**
    * Strikeout implementation. Chained from doStrikeout().
    *
    * @param $matches
    * @return string HTML.
    */
	protected function _doStrikeout_callback($matches) {
		return "<s>".$this->runSpanGamut($matches[1])."</s>";
	}

   /**
    * Instructions to handle spoiler tag: >!
    *
    * @param $text
    * @return string HTML.
    */
   protected function doSpoilers($text) {
      $text = preg_replace_callback('/
           (                     # Wrap whole match in $1
            (?>
              ^[ ]*>![ ]?        # ">!" at the start of a line
               .+\n              # rest of the first line
              (.+\n)*            # subsequent consecutive lines
              \n*                # blanks
            )+
           )
         /xm',
         array(&$this, '_doSpoilers_callback'), $text);

      return $text;
   }

   /**
    * Spoilers implementation. Chained from doSpoilers().
    *
    * @param $matches
    * @return string HTML.
    */
   protected function _doSpoilers_callback($matches) {
      $bq = $matches[1];
      // Trim one level of quoting - trim whitespace-only lines
      $bq = preg_replace('/^[ ]*>![ ]?|^[ ]+$/m', '', $bq);

      // Recurse
      $bq = $this->runBlockGamut($bq);

      $bq = preg_replace('/^/m', "  ", $bq);

      // These leading spaces cause problem with <pre> content, so we need to fix that:
      $bq = preg_replace_callback('{(\s*<pre>.+?</pre>)}sx',
         array(&$this, '_doSpoilers_callback2'), $bq);

      if (function_exists('FormatSpoiler')) {
         // If an addon like Spoilers is ready for work.
         $SpoilerText = FormatSpoiler($bq);
      } else {
         // Fallback simple format.
         $SpoilerText = "<div class=\"Spoiler\">\n$bq\n</div>";
      }

      return "\n". $this->hashBlock($SpoilerText)."\n\n";
   }

   /**
    * Spoilers implementation. Chained from _doSpoilers_callback().
    *
    * @param $matches
    * @return string HTML.
    */
   protected function _doSpoilers_callback2($matches) {
      $pre = $matches[1];
      $pre = preg_replace('/^  /m', '', $pre);
      return $pre;
   }

   /**
    * Quotes: Same as parent, but we added class="Quote".
    *
    * @param $matches
    * @return string HTML.
    */
   protected function _doBlockQuotes_callback($matches) {
      $bq = $matches[1];

      // Trim one level of quoting - trim whitespace-only lines
      $bq = preg_replace('/^[ ]*>[ ]?|^[ ]+$/m', '', $bq);

      // Recurse
      $bq = $this->runBlockGamut($bq);

      $bq = preg_replace('/^/m', "  ", $bq);

      // These leading spaces cause problem with <pre> content, so we need to fix that:
      $bq = preg_replace_callback('{(\s*<pre>.+?</pre>)}sx', 
         array($this, '_doBlockQuotes_callback2'), $bq);

      return "\n". $this->hashBlock("<blockquote class=\"Quote\">\n$bq\n</blockquote>")."\n\n";
   }

   /**
    * Code: Same as parent, but do <pre><code> if there's newlines.
    *
    * @param $code
    * @return string HTML.
    */
   protected function makeCodeSpan($code) {
      $code = htmlspecialchars(trim($code), ENT_NOQUOTES);
      if (strpos($code, "\n")) {
         return $this->hashPart("<pre><code>$code</code></pre>");
      }
      return $this->hashPart("<code>$code</code>");
   }
}