<?php
/**
 * @author Adam Charron <acharron@higherlogic.com>
 * @copyright 2009-2025 Higher Logic LLC
 * @license GPL-2.0-only
 */

namespace Vanilla\Cli\Commands;

use Exception;
use Firebase\JWT\JWT;
use Garden\Http\HttpClient;
use Garden\Http\HttpResponseException;
use Symfony\Component\Console\Helper\SymfonyQuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Vanilla\Cli\Docker\Service\VanillaJobberService;
use Vanilla\CurrentTimeStamp;

/**
 * vnla docker:jobber command
 */
class DockerJobberCommand extends DockerLaravelCommand
{
    const COMMAND_SETUP = "setup";

    /**
     * Constructor.
     *
     * @param VanillaJobberService $jobberService
     */
    public function __construct(private VanillaJobberService $jobberService)
    {
        parent::__construct($jobberService);
    }

    /**
     * @inheritdoc
     */
    protected static function getSubCommands(): array
    {
        return array_merge(parent::getSubCommands(), [self::COMMAND_SETUP]);
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $command = $input->getArgument("sub-command");
        if ($command === self::COMMAND_SETUP) {
            $this->jobberService->ensureRunning();

            /** @var SymfonyQuestionHelper $helper */
            $helper = $this->getHelper("question");

            if (
                !$helper->ask(
                    $input,
                    $output,
                    new ConfirmationQuestion(
                        "This command will setup the jobber service to work with live sites." .
                            "You will be prompted to enter various administrative credentials from Keeper." .
                            "These credentials will be stored in the vnla-jobber/.env file." .
                            "\nDo you want to continue? (y/n) "
                    )
                )
            ) {
                $output->writeln("Exiting.");
                return self::SUCCESS;
            }

            $sshUser = $helper->ask($input, $output, new Question("Data Server SSH User: "));
            if (empty($sshUser)) {
                throw new Exception("Data Server SSH User cannot be empty.");
            }

            $question = new Question("Management Dashboard JWT Private Secret: ");
            $question->setHidden(true);
            $jwtSecret = $helper->ask($input, $output, $question);
            if (empty($jwtSecret)) {
                throw new Exception("Management Dashboard JWT Private Secret cannot be empty.");
            }
            $managementToken = $this->createManagementToken($jwtSecret, $sshUser);
            $this->logger()->info("Validating generated management dashboard token.");
            $this->validateManagementToken($managementToken);

            $question = new Question("Jobber MySQL Password: ");
            $question->setHidden(true);
            $dbPassword = $helper->ask($input, $output, $question);
            if (empty($dbPassword)) {
                throw new Exception("Jobber MySQL Password cannot be empty.");
            }

            // Now write them into the env file of the service.
            $this->jobberService->updateEnvFile([
                "MANAGEMENT_ENABLED" => "true",
                "MANAGEMENT_DB_USER" => "jobber",
                "MANAGEMENT_DB_PASSWORD" => $dbPassword,
                "MANAGEMENT_SSH_USER" => $sshUser,
                "MANAGEMENT_TOKEN" => $managementToken,
            ]);

            $this->logger()->success("Credentials saved to vnla-jobber/.env file.");
            $this->logger()->info("Restart jobber containers");
            $this->jobberService->start();

            return 0;
        } else {
            return parent::execute($input, $output);
        }
    }

    /**
     * Create a management token for the jobber service.
     *
     * @param string $secret
     * @param string $username
     *
     * @return string
     */
    private function createManagementToken(string $secret, string $username): string
    {
        $jwt = JWT::encode(
            [
                "user" => $username,
                "iat" => CurrentTimeStamp::getDateTime()->getTimestamp(),
                "exp" => CurrentTimeStamp::getDateTime()
                    ->modify("+6 months")
                    ->getTimestamp(),
            ],
            $secret,
            "HS512"
        );

        $this->logger()->success("Created management dashboard token. This token will be valid for 6 months.");

        return "sys:$username:$jwt";
    }

    /**
     * Given a management token, validate it against the management dashboard.
     *
     * @param string $token
     * @return void
     * @throws HttpResponseException
     */
    private function validateManagementToken(string $token): void
    {
        $client = new HttpClient("https://management-dashboard.vanilladev.com");
        $client->setThrowExceptions(true);
        $client->get(
            "/api/clusters",
            headers: [
                "Authorization" => "Bearer $token",
            ]
        );
    }
}
