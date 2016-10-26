<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2014 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden\Exception;

use Garden\Validation;

/**
 * An exception that was built from a {@link Validation} object.
 *
 * The validation object collects errors and is mutable. Once it's ready to be thrown as an exception it gets converted
 * to an instance of the immutable {@link ValidationException} class.
 */
class ValidationException extends ClientException {
    protected $validation;

    /**
     * Initialize an instance of the {@link ValidationException} class.
     *
     * @param Validation $validation The {@link Validation} object for the exception.
     */
    public function __construct(Validation $validation) {
        $this->validation = $validation;
        parent::__construct($validation->getMessage(), (int)$validation->status());
    }

    /**
     * Specify data which should be serialized to JSON.
     *
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     */
    public function jsonSerialize() {
        $errors = $this->validation->getErrorsFlat();
        if (count($errors) === 1) {
            $message = Validation::errorMessage($errors[0]);
        } else {
            $message = t('Validation failed.');
        }

        $result = [
            'message' => $message,
            'status' => $this->getCode(),
            'errors' => $errors
        ];
        return $result;
    }
}
