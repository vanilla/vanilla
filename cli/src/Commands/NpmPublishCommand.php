<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Cli\Commands;

use Symfony\Component\Console;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Vanilla\Cli\Utils\InstallDataTrait;
use Vanilla\Cli\Utils\ScriptLoggerTrait;

/**
 * Command to Publish base docker images.
 */
class NpmPublishCommand extends Console\Command\Command
{
    use ScriptLoggerTrait;
    use InstallDataTrait;

    public const PACKAGES = [
        "@vanilla/utils",
        "@vanilla/icons",
        "@vanilla/i18n",
        "@vanilla/dom-utils",
        "@vanilla/react-utils",
        "@vanilla/ui-library",
    ];

    protected function configure()
    {
        parent::configure();

        $this->setName("npm-publish")
            ->setDescription("Publish Vanilla npm packages.")
            ->setDefinition(
                new Console\Input\InputDefinition([
                    new Console\Input\InputOption(
                        "no-build",
                        null,
                        Console\Input\InputOption::VALUE_NONE,
                        "Skip building of packages.",
                        null
                    ),
                ])
            );
    }

    /**
     * Main command entrypoint.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->validateNpmLogin();
        $this->listCurrentVersions();

        $newVersion = $this->promptBumpVersion();
        if ($newVersion !== null) {
            $this->logger()->info("");
            $this->logger()->info("Bumping package versions to <yellow>$newVersion</yellow>");
            foreach (self::PACKAGES as $PACKAGE) {
                $this->setPackageVersion($PACKAGE, $newVersion);
            }
        }

        $this->logger()->title("Building Packages");
        if (!$input->getOption("no-build")) {
            $process = new Process(["yarn", "run", "build:packages"]);
            $process->setTty(true);
            $process->setWorkingDirectory(PATH_ROOT);
            $process->setTimeout(null);
            $process->setIdleTimeout(null);
            $process->mustRun();
            $this->logger()->info("Building packages complete.");
        } else {
            $this->logger()->info("Building of packages skipped.");
        }

        $this->logger()->title("Publishing Packages");
        $otp = $this->promptOtp();
        $failedPackages = [];
        foreach (self::PACKAGES as $PACKAGE) {
            try {
                $tag = match (true) {
                    str_contains($newVersion, "alpha") => "alpha",
                    str_contains($newVersion, "beta") => "beta",
                    default => "latest",
                };
                $this->logger()->info("\nPublishing <yellow>{$PACKAGE}</yellow> with the tag <yellow>{$tag}</yellow>");
                $process = new Process(["npm", "publish", "--otp", $otp, "--tag", $tag]);
                $process->setTty(true);
                $process->setWorkingDirectory(
                    PATH_ROOT . "/packages/" . str_replace("@vanilla/", "vanilla-", $PACKAGE)
                );
                $process->setTimeout(null);
                $process->setIdleTimeout(null);
                try {
                    $process->mustRun();
                    $this->logger()->success("Published <yellow>{$PACKAGE}</yellow>");
                } catch (ProcessFailedException $e) {
                    $this->logger()->error("Failed to publish <yellow>{$PACKAGE}</yellow>: " . $e->getMessage());
                    return self::FAILURE;
                }
            } catch (\Throwable $ex) {
                $failedPackages[] = $PACKAGE;
            }
        }

        foreach ($failedPackages as $failedPackage) {
            $this->logger()->error("Failed to publish <yellow>{$failedPackage}</yellow>");
        }

        return count($failedPackages) === 0 ? self::SUCCESS : self::FAILURE;
    }

    private function promptOtp(): string
    {
        $result = readline("Enter OTP for NPM: ");
        if (empty($result)) {
            $this->logger()->error("OTP is required.");
            return $this->promptOtp();
        }

        return $result;
    }

    private function promptBumpVersion(): string|null
    {
        $result = readline("Would you like to bump the version numbers? (y/n): ");
        if (strtolower($result) === "y") {
            $result = readline("Enter the new version number (or leave blank to skip): ");
            $newVersion = trim($result);
            if ($newVersion === "") {
                $this->logger()->info("Skipping version bump.");
                return null;
            }

            return $newVersion;
        }

        return null;
    }

    /**
     * @return void
     */
    public function validateNpmLogin(): void
    {
        $this->logger()->title("Checking NPM Login");
        $process = new Process(["npm", "whoami"]);
        $process->setTty(true);
        $process->mustRun();
    }

    private function listCurrentVersions(): array
    {
        $existingVersions = [];
        $this->logger()->title("Package Versions");
        $maxPackageLength = max(array_map("strlen", self::PACKAGES));
        foreach (self::PACKAGES as $PACKAGE) {
            $version = $this->checkPackageVersion($PACKAGE);
            $existingVersions[$PACKAGE] = $version;
            $prettyPackageName = str_pad($PACKAGE . ": ", $maxPackageLength + 2);
            $this->logger()->info("$prettyPackageName<yellow>{$version}</yellow>");
        }
        return $existingVersions;
    }

    private function setPackageVersion(string $packageName, string $newVersion): void
    {
        $packageDirName = str_replace("@vanilla/", "vanilla-", $packageName);
        $packageDir = PATH_ROOT . "/packages/" . $packageDirName;
        $packageJsonPath = $packageDir . "/package.json";
        if (!file_exists($packageJsonPath)) {
            throw new \RuntimeException("Package.json not found for package: " . $packageName);
        }

        $packageJson = json_decode(file_get_contents($packageJsonPath));
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException("Failed to parse package.json for package: " . $packageName);
        }

        if (!isset($packageJson->version)) {
            throw new \RuntimeException("Version not found in package.json for package: " . $packageName);
        }

        $packageJson->version = $newVersion;

        $depFields = ["dependencies", "devDependencies", "peerDependencies"];
        foreach ($depFields as $depField) {
            if (isset($packageJson->{$depField})) {
                foreach ($packageJson->{$depField} as $dep => $version) {
                    if (in_array($dep, self::PACKAGES)) {
                        $packageJson->{$depField}->{$dep} = ">=" . $newVersion;
                    }
                }
            }
        }

        file_put_contents($packageJsonPath, json_encode($packageJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $this->logger()->info("Updated version to <yellow>{$newVersion}</yellow> in {$packageName}");

        // Format the file with prettier.
        $process = new Process(["yarn", "prettier", "--write", $packageJsonPath]);
        $process->mustRun();
    }

    private function checkPackageVersion(string $packageName): string
    {
        $packageDirName = str_replace("@vanilla/", "vanilla-", $packageName);
        $packageDir = PATH_ROOT . "/packages/" . $packageDirName;
        $packageJsonPath = $packageDir . "/package.json";
        if (!file_exists($packageJsonPath)) {
            throw new \RuntimeException("Package.json not found for package: " . $packageName);
        }

        $packageJson = json_decode(file_get_contents($packageJsonPath));
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException("Failed to parse package.json for package: " . $packageName);
        }

        if (!isset($packageJson->version)) {
            throw new \RuntimeException("Version not found in package.json for package: " . $packageName);
        }

        $version = $packageJson->version;
        return trim($version);
    }
}
