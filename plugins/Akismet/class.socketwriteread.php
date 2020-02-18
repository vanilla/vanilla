<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GNU GPLv2 http://www.opensource.org/licenses/gpl-2.0.php
 */

/**
 * Utility class used by Akismet
 *
 * This class is used by Akismet to do the actual sending and receiving of data.
 * It opens a connection to a remote host, sends some data and the reads the response and makes it available to the calling program.
 *
 * The code that makes up this class originates in the Akismet WordPress plugin,
 * which is {@link http://akismet.com/download/ available on the Akismet website}.
 *
 * N.B. It is not necessary to call this class directly to use the Akismet class.
 * This is included here mainly out of a sense of completeness.
 *
 * @package    akismet
 * @name        SocketWriteRead
 * @version     0.1
 * @author      Alex Potsides
 * @copyright   Alex Potsides, {@link http://www.achingbrain.net http://www.achingbrain.net}
 * @link        http://www.achingbrain.net/
 */

class SocketWriteRead {
    private $host;
    private $port;
    private $request;
    private $response;
    private $responseLength;
    private $errorNumber;
    private $errorString;

    /**
     * Constructor
     *
     * @param string $host The host to send/receive data.
     * @param int $port The port on the remote host.
     * @param string $request The data to send.
     * @param int $responseLength The amount of data to read.  Defaults to 1160 bytes.
     */
    public function __construct($host, $port, $request, $responseLength = 1160) {
        $this->host = $host;
        $this->port = $port;
        $this->request = $request;
        $this->responseLength = $responseLength;
        $this->errorNumber = 0;
        $this->errorString = '';
    }

    /**
     * Send
     *
     * Sends the data to the remote host.
     *
     * @throws Exception Exception is thrown if a connection cannot be made to the remote host.
     */
    public function send() {
        $this->response = '';

        $fs = fsockopen('ssl://' . $this->host, $this->port, $this->errorNumber, $this->errorString, 3);

        if ($this->errorNumber != 0) {
            throw new Exception('Error connecting to host: ' . $this->host . ' Error number: ' . $this->errorNumber . ' Error message: ' . $this->errorString);
        }

        if ($fs !== false) {
            @fwrite($fs, $this->request);

            while (!feof($fs)) {
                $this->response .= fgets($fs, $this->responseLength);
            }

            fclose($fs);
        }
    }

    /**
     *  Returns the server response text
     *
     * @return string
     */
    public function getResponse() {
        return $this->response;
    }

    /**
     * Returns the error number
     *
     * If there was no error, 0 will be returned.
     *
     * @return int
     */
    public function getErrorNumner() {
        return $this->errorNumber;
    }

    /**
     * Returns the error string
     *
     * If there was no error, an empty string will be returned.
     *
     * @return string
     */
    public function getErrorString() {
        return $this->errorString;
    }
}
