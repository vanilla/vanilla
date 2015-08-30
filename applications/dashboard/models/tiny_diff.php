<?php

/**
 * Tiny diff is, as the name already suggests, a small PHP class that can create a diff without having to rely on any external libraries or utilities. Thanks to Dan Horrigan for helping me out with this class
 *
 * @author Yorick Peterse
 * @link http://www.yorickpeterse.com/
 * @version v1.0
 * @license MIT License
 *
 * Copyright (c) 2010 Yorick Peterse
 *
 * Permission is hereby granted, free of charge, to any person
 * obtaining a copy of this software and associated documentation
 * files (the "Software"), to deal in the Software without
 * restriction, including without limitation the rights to use,
 * copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following
 * conditions:
 *
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
 * OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
 * WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
 * OTHER DEALINGS IN THE SOFTWARE.
 */
class Tiny_diff {
    /**
     * Compare two strings and return the difference
     *
     * @access public
     * @param string $old The first block of data
     * @param string $new The second block of data
     * @param string $mode The mode to use. Possible values are normal, html and mixed
     * @return string
     */
    public function compare($old, $new, $mode = 'normal') {
        // Mixed
        if ($mode === 'mixed') {
            // Insert characters
            $ins_begin = '<ins>+ ';
            $ins_end = '</ins>'.PHP_EOL;

            // Delete characters
            $del_begin = '<del>- ';
            $del_end = '</del>'.PHP_EOL;
        } // HTML mode
        elseif ($mode === 'html') {
            // Insert characters
            $ins_begin = '<ins>';
            $ins_end = '</ins>'.PHP_EOL;

            // Delete characters
            $del_begin = '<del>';
            $del_end = '</del>'.PHP_EOL;
        } // Normal mode
        else {
            // Insert characters
            $ins_begin = '+ ';
            $ins_end = PHP_EOL;

            // Delete characters
            $del_begin = '- ';
            $del_end = PHP_EOL;
        }

        // Turn the strings into an array so it's a bit easier to parse them
        $diff = $this->diff(explode(PHP_EOL, $old), explode(PHP_EOL, $new));
        $result = '';

        if ($mode == 'raw') {
            return $diff;
        }

        foreach ($diff as $line) {
            if (is_array($line)) {
                $result .= !empty($line['del']) ? $del_begin.implode(PHP_EOL, $line['del']).$del_end : '';
                $result .= !empty($line['ins']) ? $ins_begin.implode(PHP_EOL, $line['ins']).$ins_end : '';
            } else {
                $result .= $line.PHP_EOL;
            }
        }

        // Return the result
        return $result;
    }

    /**
     * Diff function. Contributed by Dan Horrigan who again took it from
     * Paul Butler.
     *
     * @author Paul Butler
     * @link http://github.com/paulgb/simplediff/blob/master/simplediff.php
     *
     * @access private
     * @param string $old The old block of data
     * @param string $new The new block of data
     */
    private function diff($old, $new) {
        $maxlen = 0;
        // Go through each old line.
        foreach ($old as $old_line => $old_value) {
            // Get the new lines that match the old line
            $new_lines = array_keys($new, $old_value);

            // Go through each new line number
            foreach ($new_lines as $new_line) {
                $matrix[$old_line][$new_line] = isset($matrix[$old_line - 1][$new_line - 1]) ? $matrix[$old_line - 1][$new_line - 1] + 1 : 1;
                if ($matrix[$old_line][$new_line] > $maxlen) {
                    $maxlen = $matrix[$old_line][$new_line];
                    $old_max = $old_line + 1 - $maxlen;
                    $new_max = $new_line + 1 - $maxlen;
                }
            }
        }
        if ($maxlen == 0) {
            return array(array('del' => $old, 'ins' => $new));
        }
        return array_merge(
            self::diff(array_slice($old, 0, $old_max), array_slice($new, 0, $new_max)),
            array_slice($new, $new_max, $maxlen),
            self::diff(array_slice($old, $old_max + $maxlen), array_slice($new, $new_max + $maxlen))
        );
    }
}
