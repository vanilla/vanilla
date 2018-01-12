<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace Garden\Web\Exception;

/**
 * The base class for all HTTP related exceptions.
 */
abstract class HttpException extends \Exception implements \JsonSerializable {
    private $context;

    /**
     * @var array HTTP response codes and messages.
     */
    protected static $messages = [
        // Informational 1xx
        100 => 'Continue',
        101 => 'Switching Protocols',

        // Successful 2xx
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',

        // Redirection 3xx
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => '(Unused)',
        307 => 'Temporary Redirect',

        // Client Error 4xx
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot',
        422 => 'Unprocessable Entity',
        423 => 'Locked',

        // Server Error 5xx
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported'
    ];

    /**
     * Construct an {@link HttpException}.
     *
     * When constructing a HTTP exception you can pass additional information on the {@link $context} parameter
     * to aid in rendering.
     *
     * - TODO: Keys beginning with **HTTP_** will be added as headers.
     * - **description** will give the exception a longer description.
     *
     * @param int|string $message The error message.
     * @param int $code The http error code.
     * @param array $context An array of context variables that can be used to render a more detailed response.
     */
    public function __construct($message, $code, array $context = []) {
        if (empty($message) && !empty(static::$messages[$code])) {
            $message = static::$messages[$code];
        }
        parent::__construct($message, $code);

        $this->context = $context + ['description' => null];
    }

    /**
     * Create the appropriate exception for an HTTP status code.
     *
     * @param int $code An HTTP status code.
     * @param string $message The error message or an empty string to use the default message for the code.
     * @param array $context An array of context variables that can be used to render a more detailed response.
     */
    public static function createFromStatus($code, $message = '', array $context = []) {
        // Try for a specific error message.
        switch ($code) {
            case 403:
                return new ForbiddenException($message, $context);
            case 404:
                return new NotFoundException($message);
            case 405:
                $method = empty($context['method']) ? '' : $context['method'];
                $allow = empty($context['allow']) ? [] : $context['allow'];
                unset ($context['method'], $context['allow']);

                return new MethodNotAllowedException($method, $allow, $context);
        }

        if ($code >= 500) {
            return new ServerException($message, $code, $context);
        } elseif ($code >= 400) {
            return new ClientException($message, $code, $context);
        } else {
            return new ServerException($message, 500, $context + ['HTTP_X_ERROR_CODE' => $code]);
        }
    }

    /**
     * Gets a longer description for the exception.
     *
     * @return string Returns the description of the exception or an empty string if there isn't one.
     */
    public function getDescription() {
        return $this->context['description'];
    }

    /**
     * Get an item from the context array.
     *
     * @param string $name The name of the context item.
     * @param mixed $default The default to return if the item doesn't exist.
     * @return mixed Returns the context item or **null** if it doesn't exist.
     */
    protected function getContextItem($name, $default = null) {
        return isset($this->context[$name]) ? $this->context[$name] : $default;
    }

    /**
     * Specify data which should be serialized to JSON.
     *
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     */
    public function jsonSerialize() {
        $context = [];
        foreach ($this->context as $key => $value) {
            if (strpos($key, 'HTTP_') !== 0) {
                $context[$key] = $value;
            }
        }

        $result = [
            'message' => $this->getMessage(),
            'status' => $this->getCode()
        ] + $context;

        return $result;
    }
}
