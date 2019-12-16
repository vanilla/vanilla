<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Web\ContentSecurityPolicy;

use Garden\Schema\ValidationException;

/**
 * A class for dealing with content security policy data.
 */
class Policy {
    const DEFAULT_SRC   = 'default-src';
    const SCRIPT_SRC    = 'script-src';
    const STYLE_SRC     = 'style-src';
    const IMG_SRC       = 'img-src';
    const CONNECT_SRC   = 'connect-src';
    const FONT_SRC      = 'font-src';
    const OBJECT_SRC    = 'object-src';
    const MEDIA_SRC     = 'media-src';
    const FRAME_SRC     = 'frame-src';
    const SANDBOX       = 'sandbox';
    const REPORT_URI    = 'report-uri';
    const CHILD_SRC     = 'child-src';
    const FORM_ACTION   = 'form-action';
    const FRAME_ANCESTORS = 'frame-ancestors';
    const PLUGIN_TYPES  = 'plugin-types';

    const VALID_DIRECTIVES = [
        self::DEFAULT_SRC,
        self::SCRIPT_SRC,
        self::STYLE_SRC,
        self::IMG_SRC,
        self::CONNECT_SRC,
        self::FONT_SRC,
        self::OBJECT_SRC,
        self::MEDIA_SRC,
        self::FRAME_SRC,
        self::SANDBOX,
        self::REPORT_URI,
        self::CHILD_SRC,
        self::FORM_ACTION,
        self::FRAME_ANCESTORS,
        self::PLUGIN_TYPES
    ];

    const FRAME_ANCESTORS_SELF = "'self'";
    const X_FRAME_SAMEORIGIN = "SAMEORIGIN";
    const X_FRAME_ALLOW_FROM = "ALLOW-FROM";
    const X_FRAME_DENY = "DENY";

    /** @var string */
    private $policyDirective;

    /** @var string */
    private $policyArgument;

    /**
     * Policy constructor.
     *
     * @param string $directive
     * @param string $argument
     */
    public function __construct(string $directive, string $argument) {
        if (!in_array($directive, self::VALID_DIRECTIVES)) {
            throw new ValidationException('Invalid content security policy directive: '.$directive);
        }
        $this->policyDirective = $directive;
        $this->policyArgument = $argument;
    }

    /**
     * @return string
     */
    public function getDirective(): string {
        return $this->policyDirective;
    }

    /**
     * @return string
     */
    public function getArgument(): string {
        return $this->policyArgument;
    }
}
