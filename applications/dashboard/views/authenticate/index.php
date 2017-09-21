<?php
/**
 * @var AuthenticateController $this
 */

/**
 * @var Exception $exception
 */
$exception = $this->data('Exception');
if ($exception) {
    echo $exception->getMessage();
}
