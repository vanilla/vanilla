<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Utility;

/**
 * Utilities related to HTML formatting.
 *
 * DO NOT ADD PROPERTIES OR NON-STATIC METHODS TO THIS CLASS.
 */
final class HtmlUtils {

    /** @var string[] Keep track of dom IDs */
    private static $domIDs = [];

    /**
     * Takes an array of attributes and formats them in attribute="value" format.
     *
     * @param array $attributes The attribute array to format.
     * @return string Returns a string in ` attribute="value" attribute="value"` format.
     */
    public static function attributes(array $attributes): string {
        $result = '';

        foreach ($attributes as $name => $val) {
            if (is_numeric($name) || in_array($val, [false, null], true)) {
                continue;
            }

            if (is_array($val) && strpos($name, 'data-') === 0) {
                $val = json_encode($val, JSON_UNESCAPED_UNICODE);
            }

            if ($val === true) {
                $result .= ' '.$name;
            } else {
                $result .= ' '.$name.'="'.htmlspecialchars($val, ENT_COMPAT, 'UTF-8').'"';
            }
        }
        return $result;
    }

    /**
     * Get all unique classes from dom node.
     *
     * @param \DOMElement $domNode The dom node.
     *
     * @return array
     */
    public static function getClasses(\DOMElement $domNode): array {
        $result = [];
        if ($classes = $domNode->getAttribute('class')) {
            $result = explode(" ", $classes);
        }
        return array_unique($result);
    }

    /**
     * Check if class exists in class array
     *
     * @param \DOMElement $domElement The domNode to check.
     * @param string $className The class name to look for.
     * @return bool
     */
    public static function hasClass(\DOMElement $domElement, string $className): bool {
        $classes = self::getClasses($domElement);
        return in_array($className, $classes);
    }

    /**
     * Append class to dom node.
     *
     * @param \DOMElement $domNode
     * @param string $className
     */
    public static function appendClass(\DOMElement $domNode, string $className) {
        $classes = self::getClasses($domNode);
        $classes[] = $className;
        $classes = array_unique($classes);
        $domNode->setAttribute('class', implode(" ", $classes));
    }

    /**
     * Join some group of CSS class names.
     *
     * @param mixed[] $args Multiple CSS class names to join together. Items may be string or null.
     *
     * @return string The CSS classes joined together.
     */
    public static function classNames(...$args): string {
        $args = array_filter($args, function ($item) {
            return is_string($item) && $item !== '';
        });
        return implode(' ', $args);
    }

    /**
     * Similar to `sprintf()`, but uses numbered HTML tags for replacement instead of `%s`.
     *
     * Can accept source strings with interpolated translation components in a form such as:
     * - "Published on <0/> by <1 />."
     * - "For more information, please see our <0>public documentation</0>."
     *
     * About the placeholders:
     * - Self closing placeholders will be replace with the result from the argument that has a corresponding index.
     *   Eg. "<0/>" will be replaced by the first argument.
     *   "<3 />" will be replaced by the fourth argument.
     * - Placeholders content will have their translated content passed as an argument to their callback prop.
     *
     * Limitations
     * - These tag's CANNOT be nested currently.
     *
     * Examples
     *
     * ```php
     * StringUtils::formatTags('test');
     * // returns 'test'
     *
     * StringUtils::formatTags('Hello <0/>');
     * // error, no argument provided
     *
     * StringUtils::formatTags('This is <0>important</0>', 'strong');
     * // returns 'This is <strong>important</strong>'
     *
     * StringUtils::formatTags('Hello <0/> world!', ['img', 'src' => '//example.com/foo.png']);
     * // returns 'Hello <img src="//example.com/foo.png" /> world!'
     *
     * StringUtils::formatTags('Visit <0>our site</0> for help.', ['a', 'href' => 'http://site.com']);
     * // returns 'Visit <a href="http://site.com">our site</a> for help.'
     *
     * // You can replace a string value using a self-closing tag with a string argument.
     * StringUtils::formatTags('Hello <0 />', 'world');
     * // returns 'Hello world'
     * ```
     *
     * @param string $format The string to format.
     * @param mixed $args Arguments that will replace the tags in the string.
     * @return string Returns the formatted string.
     */
    public static function formatTags(string $format, ...$args): string {
        $r = preg_replace_callback('`<(/)?([\d]+)(\s*/?)>`', function ($m) use ($args, $format) {
            $index = $m[2];

            if (!isset($args[$index])) {
                trigger_error("Invalid tag: ".$m[0], E_USER_NOTICE);
                return '';
            } else {
                $arg = (array)$args[$index];
            }

            if (!empty($m[1])) {
                // This is a closing tag.
                return "</{$arg[0]}>";
            } elseif (!empty($m[3]) && is_string($args[$index])) {
                // This is a self-closing tag with a string literal.
                return $args[$index];
            } else {
                // This is an opening tag or a self-closing tag.
                return '<'.$arg[0].self::attributes($arg).$m[3].'>';
            }
        }, $format);

        return $r;
    }


    /**
     * Provides a unique id
     *
     * @param string $prefix ID prefix

     * @return string
     */
    public static function uniqueElementID($prefix): string {
        if (empty(self::$domIDs[$prefix])) {
            self::$domIDs[$prefix] = 0;
        }
        return $prefix . ++self::$domIDs[$prefix];
    }


    /**
     * Provides an accessible context for clickable items, so they can make sense out of context.
     *
     * @param string $template The text template
     * @param array $data The placeholder data
     * @return string
     */
    public static function accessibleLabel($template, $data): string {
        return htmlspecialchars(sprintf(t($template), ...$data));
    }
}
