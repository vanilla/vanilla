<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2014 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden\Exception;

use Garden\Response;

/**
 * Represents a 400 series exception.
 */
class ClientException extends \Exception implements \JsonSerializable {
    protected $context;

    /**
     * Initialize an instance of the {@link ClientException} class.
     *
     * The 4xx class of status code is intended for cases in which the client seems to have erred.
     * When constructing a client exception you can pass additional information on the {@link $context} parameter
     * to aid in rendering.
     *
     * - Keys beginning with **HTTP_** will be added as headers.
     * - **description** will give the exception a longer description.
     *
     * @param string $message The error message.
     * @param int $code The http error code.
     * @param array $context An array of context variables that can be used to render a more detailed response.
     */
    public function __construct($message = '', $code = 400, array $context = []) {
        parent::__construct($message, $code);
        $this->context = $context;
    }

    /**
     * Gets the response headers that were set on the exception.
     *
     * @return array Returns the headers for this exception.
     */
    public function getHeaders() {
        $result = [];
        foreach ($this->context as $key => $value) {
            if (stripos($key, 'http_') === 0) {
                $key = Response::normalizeHeader(ltrim_substr($key, 'http_'));
                $result[$key] = $value;
            }
        }
        return $result;
    }

    /**
     * Gets a longer description for the exception.
     *
     * @return string Returns the description of the exception or an empty string if there isn't one.
     */
    public function getDescription() {
        return val('description', $this->context, '');
    }

    /**
     * Specify data which should be serialized to JSON.
     *
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     */
    public function jsonSerialize() {
        $result = [
            'message' => $this->getMessage(),
            'status' => $this->getCode()
        ];
        if ($this->getDescription()) {
            $result['description'] = $this->getDescription();
        }
        return $result;
    }
}
