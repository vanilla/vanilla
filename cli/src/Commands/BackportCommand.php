<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Cli\Commands;

use Vanilla\Cli\Utils\ShellUtils;
use Vanilla\Cli\Utils\SimpleScriptLogger;

/**
 * Backport command.
 */
class BackportCommand {

    /** @var int */
    private $pr;

    /** @var string */
    private $targetBranch;

    /** @var string */
    private $targetSlug;

    /** @var SimpleScriptLogger */
    private $logger;

    /**
     * Constructor.
     */
    public function __construct() {
        $this->logger = new SimpleScriptLogger();
    }

    /**
     * Backport a pull request to a target branch and open a pull request targeting that branch.
     *
     * Used to apply a PR targeting 1 branch to another branch target.
     * Only the commits specific to that branch will be applied.
     */
    public function backport() {
        $this->logger->title("Fetching remote branches");
        ShellUtils::shellOrThrow("git fetch --all");

        $this->logger->title("Checking out latest version of $this->targetBranch");
        ShellUtils::shellOrThrow("git checkout {$this->targetBranch}");
        ShellUtils::shellOrThrow("git pull");

        $this->logger->title("Copying commits of #{$this->pr} to a backport branch.");
        ShellUtils::shellOrThrow("git checkout -b \"backport/{$this->targetSlug}/$this->pr\"");

        $repo = $this->fetchRepositoryPath();

        // Try to backport.
        ShellUtils::shellOrCallback(
            "hub am -3 https://github.com/$repo/pull/{$this->pr}",
            [$this, 'handleMergeConflict']
        );

        // Get the PR info
        $this->logger->title("Creating the backport PR on github.");
        [$title, $body] = $this->fetchTitleAndBody($repo);

        $ghLinks = $this->parseGithubUrls($body);
        $messages = [
            $title,
            "Backporting #{$this->pr} to {$this->targetBranch}"
        ];
        foreach ($ghLinks as $link) {
            $messages[] = "Related {$link}";
        }
        $this->pushPr($messages);
    }

    /**
     * The numeric ID of the PR you are trying to backport.
     *
     * @param int $pr
     */
    public function setPr(int $pr): void {
        $this->pr = $pr;
    }

    /**
     * The target branch to backport to. Ex. release/2020.021
     *
     * @param string $targetBranch
     */
    public function setTarget(string $targetBranch): void {
        $this->targetBranch = $targetBranch;
        $this->targetSlug = str_replace(["release/", "feature/", "fix/"], "", $this->targetBranch);
    }

    /**
     * Prompt the user to handle a merger conflict.
     */
    public function handleMergeConflict() {
        $this->logger->title("Error Resolution");
        $this->logger->error('There was an error applying your commits.');
        $this->logger->info("You will need to resolve conflicts manually. Then come back to continue.\n");
        ShellUtils::promptYesNo("Have you finished manually resolving the backport? Type 'y' to continue to make the PR.", true);
    }

    /**
     * Push the PR up to github and open the PR in a browser window.
     *
     * @param array $messages Messages for the PR. Each is a newline. First newline is the title.
     */
    private function pushPr(array $messages) {
        // Push up the PR.
        $pullRequestScript = "hub pull-request --base {$this->targetBranch} --browse";
        foreach ($messages as $message) {
            // Lines are applied 1 at a time.
            // The first on is used as the PR title.
            $pullRequestScript .= " --message=\"$message\"";
        }

        $pullRequestScript .= " --push";
        ShellUtils::shellOrThrow($pullRequestScript);
    }

    /**
     * Parse github issue and PR urls out of some markdown.
     *
     * @param string $markdownBody
     *
     * @return string[]
     */
    private function parseGithubUrls(string $markdownBody): array {
        $regex = '/(?<links>https?:\/\/github.com.*(issues|pulls).*)\s/i';
        preg_match_all($regex, $markdownBody, $matches);
        $links = $matches['links'] ?? [];
        return array_unique($links);
    }

    /**
     * Returns the path of the current repository on GitHub. E.g. "vanilla/vanilla-cloud"
     *
     * @return string|string[]|null
     */
    public function fetchRepositoryPath() {
        $url = ShellUtils::command("git remote get-url origin")[0];
        preg_match("/[a-z\-]+\/[a-z\-]+(?=\.git|$)/i", $url, $matches);
        return $matches[0];
    }

    /**
     * Fetch info about the existing PR.
     *
     * @param string $repo Path of the repository on GitHub. E.g. "vanilla/vanilla-cloud"
     * @return string[] Tuple[string $title, string $body].
     */
    private function fetchTitleAndBody($repo): array {
        $prJson = shell_exec("hub api '/repos/$repo/pulls/{$this->pr}'");
        $prData = json_decode($prJson, true);
        $title = $prData['title'] ?? "#{$this->pr} to `{$this->targetBranch}`";
        $title = "[Backport $this->targetSlug] $title";
        $body = $prData['body'] ?? '';
        return [$title, $body];
    }
}
