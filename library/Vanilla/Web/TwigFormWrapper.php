<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Web;

use \Gdn_Form;

/**
 * Wrap a Gdn_Form object and it's method for Twig.
 *
 * - Certain methods output will be marked as "pre-escaped" for twig.
 * - Other methods will be raw strings, and twig will escape them itself.
 */
class TwigFormWrapper {

    // List of form methods that are alreayd sanitized and are exptected to contain HMTL.
    const PRE_ESCAPED_OUTPUT_METHODS = [
        // structure
        'open',
        'close',
        'errors',
        'label',
        'inputWrap',
        'inlineError',

        // Groups
        'simple',

        // Inputs
        'dropDown',
        'categoryDropdown',
        'toggle',
        'input',
        'hidden',
        'textBox',
        'bodyBox',
        'button',
        'linkButton',
        'color',
        'calendar',
        'captcha',
        'checkBox',
        'checkBoxList',
        'checkBoxGrid',
        'checkBoxGridGroups',
        'checkBoxGridGroup',
        'radio',
        'radioList',
        'imageUploadPreview',
        'imageUploadReact',
        'date',
        'currentImage',
        'imageUpload',
        'fileUpload',
    ];

    /**
     * @var Gdn_Form
     */
    private $form;

    /**
     * Constructor.
     *
     * @param Gdn_Form $form The form object to wrap/proxy to.
     */
    public function __construct(Gdn_Form $form) {
        $this->form = $form;
    }

    /**
     * Magic method to proxy over to form instance.
     *
     * @param string $method
     * @param array $args
     *
     * @return mixed
     */
    public function __call(string $method, array $args) {
        $result = call_user_func_array([$this->form, $method], $args);

        if (inArrayI(strtolower($method), self::PRE_ESCAPED_OUTPUT_METHODS)) {
            $result = new \Twig\Markup($result, 'utf-8');
        }

        return $result;
    }
}
