<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Cli\Docker;

use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;
use Vanilla\Cli\Utils\ScriptLoggerTrait;

/**
 * Class for validating a user's host file has all necessary entries for vnla docker.
 */
class HostValidator
{
    use ScriptLoggerTrait;

    const EXPECTED_HOSTS = [
        // Internal services
        "database" => "127.0.0.1",
        "memcached" => "127.0.0.1",
        "queue.vanilla.localhost" => "127.0.0.1",
        "advanced-embed.vanilla.localhost" => "127.0.0.1",
        "dev.vanilla.localhost" => "127.0.0.1",
        "embed.vanilla.localhost" => "127.0.0.1",
        "modern-embed.vanilla.localhost" => "127.0.0.1",
        "modern-embed-hub.vanilla.localhost" => "127.0.0.1",
        "vanilla.localhost" => "127.0.0.1",
        "sso.vanilla.localhost" => "127.0.0.1",
        "e2e-tests.vanilla.localhost" => "127.0.0.1",
        "webpack.vanilla.localhost" => "127.0.0.1",
        "logs.vanilla.localhost" => "127.0.0.1",
        "mail.vanilla.localhost" => "127.0.0.1",
    ];

    const CERTS = [
        "*.vanilla.localhost" => PATH_ROOT . "/docker/images/nginx/certs/wildcard.vanilla.localhost.crt",
        "vanilla.localhost" => PATH_ROOT . "/docker/images/nginx/certs/vanilla.localhost.crt",
    ];

    /**
     * Ensure that our SSL certs are installed.
     * May prompt for an admin password.
     */
    public function ensureCerts(): void
    {
        $this->logger()->title("Validating SSL certs.");
        $executableFinder = new ExecutableFinder();
        if ($executableFinder->find("security")) {
            $this->ensureCertsMacOS();
        } elseif ($executableFinder->find("update-ca-certificates")) {
            $this->ensureCertsLinux();
        } else {
            // Windows not supported at this time.
            throw new \Exception(
                "Could not find either `security` (macOS) or `update-ca-certificates` (linux) to add certs with."
            );
        }
    }

    /**
     * Ensure that our SSL certs are installed on linux.
     * May prompt for an admin password.
     */
    public function ensureCertsLinux(): void
    {
        foreach (self::CERTS as $name => $certFile) {
            $certRoot = "/usr/local/share/ca-certificates";
            $basename = basename($certFile);
            if (file_exists($certRoot . "/" . $basename)) {
                $this->logger()->info("<green>{$name}</green> ✅");
            } else {
                if (!is_writable($certRoot)) {
                    throw new \Exception(
                        "Unable to write to `$certRoot`. Please re-run this script with permission to write to this directory."
                    );
                }

                copy($certFile, $certRoot . "/" . $basename);
            }

            $process = new Process(["sudo", "update-ca-certificates"]);
            $process->setTty(stream_isatty(STDOUT));
            $process->mustRun();
        }
    }

    /**
     * Ensure our SSLcerts are added to the local keychain on Mac.
     * May prompt for an admin password.
     */
    public function ensureCertsMacOS(): void
    {
        foreach (self::CERTS as $name => $certFile) {
            $process = new Process(["security", "find-certificate", "-c", "*.vanilla.localhost"]);
            $process->mustRun();
            if (str_contains($process->getOutput(), "attributes")) {
                $this->logger()->info("<green>{$name}</green> ✅");
            } else {
                $this->logger()->info("<yellow>Adding cert: {$name}</yellow>");
                $this->logger()->info("You may be asked for your password to add the certificate.");
                $process = new Process([
                    "sudo",
                    "security",
                    "add-trusted-cert",
                    "-d",
                    "-r",
                    "trustRoot",
                    "-k",
                    "/Library/Keychains/System.keychain",
                    $certFile,
                ]);
                $process->setTty(true);
                $process->mustRun();
            }
        }
    }

    /**
     * Ensure all hosts are added to /etc/hosts.
     * May prompt for an admin password.
     */
    public function ensureHosts(): void
    {
        $this->logger()->title("Validating /etc/hosts.");

        $missing = [];

        foreach (self::EXPECTED_HOSTS as $HOST => $IP) {
            $actualIP = gethostbyname($HOST);
            if ($actualIP === $IP) {
                $this->logger()->info("{$HOST} - <green>Success</green>");
            } else {
                $this->logger()->info("{$HOST} - <red>Not found</red> - Resolved to <yellow>$actualIP</yellow>");
                $missing[$HOST] = $IP;
            }
        }

        if (!empty($missing)) {
            $this->logger()->info("");
            $newHostLines = [];
            foreach ($missing as $host => $ip) {
                $newHostLines[] = "$ip $host # Added by vnla docker";
            }

            $newHostLines = implode("\n", $newHostLines);
            $this->logger()->info("<yellow>The following will be added to your hosts file</yellow>\n$newHostLines");
            $this->logger()->info("You may need to enter your root password to modify the /etc/hosts file.");
            $this->appendHosts($newHostLines);
        } else {
            $this->logger()->success("All hosts were found.");
        }
    }

    /**
     * Append some lines to the /etc/hosts file.
     *
     * @param string $toAppend
     */
    private function appendHosts(string $toAppend)
    {
        if (!file_exists("/etc/hosts")) {
            throw new \Exception("Could not find /etc/hosts file");
        }

        $existingHosts = file_get_contents("/etc/hosts");
        $newHosts = $existingHosts . "\n" . $toAppend;

        $tmpFile = tmpfile();
        $tmpFilePath = stream_get_meta_data($tmpFile)["uri"];
        file_put_contents($tmpFilePath, $newHosts);

        $echoProcess = Process::fromShellCommandline("sudo cp $tmpFilePath /etc/hosts");
        $echoProcess->setTty(stream_isatty(STDOUT));
        $echoProcess->mustRun();
    }
}
