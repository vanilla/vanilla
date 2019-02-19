<?php

/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Job;

use Garden\QueueInterop\AbstractJob;
use Psr\Log\LogLevel;
use Vanilla\VanillaMailer;

/**
 * Vanilla Job: SendMailJob
 *
 * @author Eduardo Garcia Julia <eduardo.garciajulia@vanillaforums.com>
 */
class SendMailJob extends AbstractJob {

    /**
     * Run job
     *
     * @throws \Exception
     */
    public function run() {

        $this->log(LogLevel::NOTICE, "SendMail Job running");

        /** @var PHPMailer */
        $phpMailer = $this->get('phpMailer');

        if ($phpMailer == null || !($phpMailer instanceof VanillaMailer)) {
            $msg = "Vanilla\Job\SendMailJob: invalid phpMailer payload";
            $this->log(LogLevel::ERROR, $msg);
            throw new \Exception($msg);
        }

        error_log("Vanilla\Job\SendMailJob: '".$phpMailer->Subject."', to: ".var_export($phpMailer->getToAddresses(), true));

        $phpMailer->setThrowExceptions(true);
        if (!$phpMailer->send()) {
            throw new \Exception($phpMailer->ErrorInfo);
        }
    }

}
