<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Cli\Commands;

use Symfony\Component\Console;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Vanilla\Cli\Utils\InstallDataTrait;
use Vanilla\Cli\Utils\ScriptLoggerTrait;
use Vanilla\Cli\Utils\ShellUtils;
use Webmozart\PathUtil\Path;

/**
 * Build frontend assets.
 */
class BuildCommand extends Console\Command\Command
{
    use ScriptLoggerTrait;
    use InstallDataTrait;

    public const VALID_SECTIONS = ["admin", "admin-new", "forum", "knowledge", "layouts"];

    /** @var string[] */
    private array $sections = [];

    private bool $isDev = false;
    private bool $isAnalyze = false;
    private bool $isVerbose = false;

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        parent::configure();
        $sectionCsv = implode(", ", self::VALID_SECTIONS);
        $this->setName("build")
            ->setDescription("Perform a frontend build.")
            ->setDefinition(
                new Console\Input\InputDefinition([
                    new Console\Input\InputOption(
                        "section",
                        "s",
                        Console\Input\InputOption::VALUE_REQUIRED,
                        "A CSV of sections to to build. Valid values are: {$sectionCsv}."
                    ),
                    new Console\Input\InputOption(
                        "analyze",
                        null,
                        Console\Input\InputOption::VALUE_NONE,
                        "Launch a graph visualization of production chunk sizes."
                    ),
                    new Console\Input\InputOption(
                        "dev",
                        null,
                        Console\Input\InputOption::VALUE_NONE,
                        "Run a fast and unoptimized dev build with hot-reload support."
                    ),
                ])
            );
    }

    /**
     * @inheritdoc
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $sectionCsv = implode(", ", self::VALID_SECTIONS);

        $sections = $input->getOption("section") ?: $sectionCsv;
        $sections = explode(",", $sections);
        $sections = array_map("trim", $sections);
        $badSections = implode(", ", array_diff($sections, self::VALID_SECTIONS));
        if (!empty($badSections)) {
            throw new \Exception("Invalid build sections: {$badSections}. Valid sections are: {$sectionCsv}");
        }
        $this->sections = $sections;

        $this->isDev = (bool) $input->getOption("dev");
        $this->isAnalyze = (bool) $input->getOption("analyze");
        $this->isVerbose = (bool) $input->getOption("verbose");
        if ($this->isDev && $this->isAnalyze) {
            throw new \Exception("The --dev and --analyze flags cannot be used together.");
        }
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $env = [
            "BUILD_SECTIONS" => implode(",", $this->sections),
        ];

        if ($this->isVerbose) {
            $env["BUILD_VERBOSE"] = "true";
        }

        $command = [];
        if ($this->isDev) {
            // Dev build.
            $command[] = "yarn vite --config ./build/vite.devConfig.ts";
            if ($this->isVerbose) {
                $command[] = "--debug";
            }
        } else {
            // Prod build
            $command[] = "node -r esbuild-register ./build/vite.buildProd.ts";
            if ($this->isAnalyze) {
                $env["BUILD_ANALYZE"] = "true";
            }
        }

        $finalCommand = "";
        foreach ($env as $key => $val) {
            $finalCommand .= $key . "=" . $val . " ";
        }
        foreach ($command as $val) {
            $finalCommand .= $val . " ";
        }
        $finalCommand = trim($finalCommand);
        $this->logger()->info($finalCommand);
        return self::SUCCESS;
    }
}
