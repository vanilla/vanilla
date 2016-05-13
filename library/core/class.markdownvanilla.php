<?php

/**
 * @copyright 2009-2016 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

/**
 * Vanilla Markdown Override
 *
 * This class extends the Markdown vendor library to add some optional
 * customizations to the rendering process.
 *
 * @author Tim Gunter <tim@vanillaforums.com>
 * @package core
 * @since 2.2
 */
class MarkdownVanilla extends \Michelf\MarkdownExtra {

    /**
     * Add all Vanilla customizations to markdown parsing
     *
     * @return void
     */
    public function addAllFlavor() {
        $this->addStrikeout();
        $this->addBreaks();
        $this->addSpoilers();

		// Sort gamuts by their priority.
		asort($this->block_gamut);
		asort($this->span_gamut);
    }

    /**
     * Add soft breaks to markdown parsing
     *
     * @return void
     */
    public function addBreaks() {
        $this->span_gamut = array_replace($this->span_gamut, [
            "doStrikeout" 			 =>  15,
            "doSoftBreaks" => 80
        ]);
    }

    /**
     * Add strikeouts to markdown parsing
     *
     * @return void
     */
    public function addStrikeout() {
        $this->span_gamut = array_replace($this->span_gamut, [
            "doStrikeout" => 15
        ]);
    }

    /**
     * Add spoilers to markdown parsing
     *
     * @return void
     */
    public function addSpoilers() {
        $this->block_gamut = array_replace($this->block_gamut, [
            "doSpoilers" => 55
        ]);
    }

    /**
     * Add Spoilers implementation (3 methods).
     *
     * @param string $text
     * @return string
     */
	protected function doSpoilers($text) {
		$text = preg_replace_callback('/
			  (								# Wrap whole match in $1
				(?>
				  ^[ ]*>![ ]?			# ">" at the start of a line
					.+\n					# rest of the first line
				  \n*						# blanks
				)+
			  )
			/xm',
			array(&$this, '_doSpoilers_callback'), $text);

		return $text;
	}
	protected function _doSpoilers_callback($matches) {
		$bq = $matches[1];
		# trim one level of quoting - trim whitespace-only lines
		$bq = preg_replace('/^[ ]*>![ ]?|^[ ]+$/m', '', $bq);
		$bq = $this->runBlockGamut($bq);		# recurse

		$bq = preg_replace('/^/m', "  ", $bq);
		# These leading spaces cause problem with <pre> content,
		# so we need to fix that:
		$bq = preg_replace_callback('{(\s*<pre>.+?</pre>)}sx',
			array(&$this, '_doSpoilers_callback2'), $bq);

		return "\n". $this->hashBlock(Gdn_Format::spoilerHtml($bq))."\n\n";
	}
	protected function _doSpoilers_callback2($matches) {
		$pre = $matches[1];
		$pre = preg_replace('/^  /m', '', $pre);
		return $pre;
	}

    /**
     * Add Strikeout implementation (2 methods).
     *
     * @param string $text
     * @return string
     */
	protected function doStrikeout($text) {
		$text = preg_replace_callback('/
		~~ # open
		(.+?) # $1 = strike text
		~~ # close
		/xm',
		array($this, '_doStrikeout_callback'), $text);
		return $text;
	}
	protected function _doStrikeout_callback($matches) {
		return $this->hashPart("<s>".$this->runSpanGamut($matches[1])."</s>");
	}

    /**
     * Add soft line breaks implementation (2 methods).
     *
     * @param string $text
     * @return string
     */
	protected function doSoftBreaks($text) {
		# Do soft line breaks for 1 return:
		return preg_replace_callback('/\n{1}/',
			array($this, '_doSoftBreaks_callback'), $text);
	}
	protected function _doSoftBreaks_callback($matches) {
		return $this->hashPart("<br$this->empty_element_suffix\n");
	}

}
