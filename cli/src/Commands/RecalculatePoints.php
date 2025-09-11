<?php
/**
 * @author Olivier Lamy-Canuel <olamy-canuel@higherlogic.com>
 * @copyright 2009-2024 Higher Logic Inc.
 * @license Proprietary
 */

namespace Vanilla\Cli\Commands;

use Vanilla\Cli\Utils\DatabaseCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Vanilla\Cli\Utils\ScriptLoggerTrait;
use Vanilla\Cli\Utils\ShellUtils;

/**
 * Recalculate the points and badges on a community.
 *
 * This command will remove the current points data and recalculate the points and badges.
 *
 * DO NOT RUN THIS COMMAND ON A LARGE SITE!
 */
class RecalculatePoints extends DatabaseCommand
{
    use ScriptLoggerTrait;

    const FEATURES = "badge,qna,reactions";

    protected int $answerPoints = 0;
    protected int $acceptedAnswerPoints = 0;
    private array $features;

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        parent::configure();
        $this->setName("recalculate-points")->setDescription("Recalculate the points on a community.");
        $definition = $this->getDefinition();

        $definition->addOption(
            new InputOption(
                "feature",
                null,
                InputOption::VALUE_OPTIONAL,
                "A comma separated list of feature recalculate the points (Badge,QnA,Reactions). If not set, all features will be recalculated.",
                self::FEATURES
            )
        );

        $definition->addOption(
            new InputOption("badges", null, InputOption::VALUE_NONE, "Should we calculate the badges as well.")
        );

        $definition->addOption(
            new InputOption(
                "answer",
                null,
                InputOption::VALUE_OPTIONAL,
                "How many points should be given for an answer",
                0
            )
        );

        $definition->addOption(
            new InputOption(
                "acceptedAnswer",
                null,
                InputOption::VALUE_OPTIONAL,
                "How many points should be given for an accepted answers",
                0
            )
        );
    }

    /**
     * @inheritdoc
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);
        $this->answerPoints = $input->getOption("answer");
        $this->acceptedAnswerPoints = $input->getOption("acceptedAnswer");
        $this->features = explode(",", strtolower($input->getOption("feature")));
    }

    /**
     * Recalculate the points and badges on a community.
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        ShellUtils::promptYesNo(
            "Recalculating the points will remove the current points data. Are you sure you want to proceed?",
            true
        );

        $totalStartTime = microtime(true);
        $this->resetPoints();

        if ($input->getOption("badges") && in_array("badge", $this->features)) {
            $this->calculateBadges();
        }

        if (in_array("badge", $this->features)) {
            $this->calculateBadgesPoints();
        }

        if (in_array("reactions", $this->features)) {
            $this->calculateReactionPoints();
        }

        if (in_array("qna", $this->features)) {
            $this->recalculateQnA();
        }

        $this->recalculateTotal();
        $stopTime = microtime(true);
        $this->logger()->success("Recalculated points in " . round($stopTime - $totalStartTime, 2) . " seconds.");
        return self::SUCCESS;
    }

    /**
     * Truncate the GDN_UserPoints table and reset users points to 0.
     *
     * @return void
     * @throws \Exception
     */
    protected function resetPoints(): void
    {
        $sql = $this->getDatabase()->createSql();
        $sql->query("TRUNCATE TABLE GDN_UserPoints;");
        $sql->query("update GDN_User u set u.Points = 0");
    }

    /**
     * Calculate the default badges from Vanilla.
     *
     * @return void
     * @throws \Exception
     */
    protected function calculateBadges(): void
    {
        $this->logger()->info("Calculating badges.");
        // UserCount
        $sql = $this->getDatabase()->createSql();
        $results = $sql
            ->query(
                "
            SELECT
                BadgeID,
                JSON_UNQUOTE(json_extract(b. `Attributes`, '$.Column')) AS col,
                b.Threshold
            FROM
                GDN_Badge b
            WHERE
                b.Active = 1
                AND b. `Type` = 'UserCount'
        "
            )
            ->resultArray();

        foreach ($results as $result) {
            $sql->reset();
            $sql->query("
                INSERT IGNORE INTO `GDN_UserBadge` (`UserID`, `BadgeID`, `Reason`, `Status`, `DateCompleted`, `DateInserted`, `InsertUserID`)
                SELECT
                    UserID,
                    {$result["BadgeID"]},
                    'calculated',
                    'given',
                    now(),
                    now(),
                    0
                FROM
                    GDN_User u
                WHERE
                    u. `{$result["col"]}` >= {$result["Threshold"]}
            ");
        }

        // Reactions
        $sql->reset();
        $sql->query("
            INSERT IGNORE INTO `GDN_UserBadge` (`UserID`, `BadgeID`, `Reason`, `Status`, `DateCompleted`, `DateInserted`, `InsertUserID`)
            SELECT
                ut.RecordID,
                b.BadgeID,
                'calculated',
                'given',
                now(),
                now(),
                0
            FROM (
                SELECT
                    BadgeID,
                    `Class`,
                    Threshold
                FROM
                    GDN_Badge b
                WHERE
                    b.Active = 1
                    AND b.Type = 'Reaction') b
                JOIN GDN_Tag t ON t. `Name` = b.Class
                    AND t. `Type` = 'Reaction'
                JOIN GDN_UserTag ut ON ut.RecordType = 'User'
                    AND ut.TagID = t.TagID
                    AND ut.Total >= b.Threshold
        ");

        // Anniversaries
        $sql->reset();
        $sql->query("
            INSERT IGNORE INTO `GDN_UserBadge` (`UserID`, `BadgeID`, `Reason`, `Status`, `DateCompleted`, `DateInserted`, `InsertUserID`)
            SELECT
                UserID,
                BadgeID,
                'calculated',
                'given',
                now(),
                now(),
                0
            FROM
                GDN_User u
            JOIN GDN_Badge b ON b.Class = 'Anniversary' and b.Active = 1
                AND b.Active = 1
            WHERE
                u.`DateInserted` <= DATE_SUB(now(), INTERVAL b.Threshold YEAR)
        ");

        // Photogenic
        $sql->reset();
        $sql->query("
            INSERT IGNORE INTO `GDN_UserBadge` (`UserID`, `BadgeID`, `Reason`, `Status`, `DateCompleted`, `DateInserted`, `InsertUserID`)
            SELECT
                UserID,
                BadgeID,
                'calculated',
                'given',
                now(),
                now(),
                0
            FROM
                GDN_User u
            JOIN GDN_Badge b ON b.Slug = 'Photogenic' and b.Active = 1
                AND b.Active = 1
            WHERE
                u.`Photo` IS NOT NULL and u.Photo <> ''
        ");

        // name-dropper
        $sql->reset();
        $sql->query("
            INSERT IGNORE INTO `GDN_UserBadge` (`UserID`, `BadgeID`, `Reason`, `Status`, `DateCompleted`, `DateInserted`, `InsertUserID`)
            SELECT
                u.UserID,
                BadgeID,
                'calculated',
                'given',
                now(),
                now(),
                0
            FROM (
                SELECT
                    d.InsertUserID AS UserID
                FROM
                    GDN_userMention um
                    JOIN GDN_Discussion d ON d.DiscussionID = um.recordID
                        AND um.recordType = 'discussion'
                    UNION
                    SELECT
                        c.InsertUserID AS UserID
                    FROM
                        GDN_userMention um
                    JOIN GDN_Comment c ON c.CommentID = um.recordID
                        AND um.recordType = 'comment') u
            JOIN GDN_Badge b ON b.Slug = 'name-dropper'
                AND b.Active = 1");

        $sql->reset();
        $sql->query("
            UPDATE
                GDN_User u,
                (
                    SELECT
                        UserID,
                        count(*) AS cou
                    FROM
                        GDN_UserBadge
                    WHERE
                        Status = 'given'
                    GROUP BY
                        UserID) ub SET u.CountBadges = ub.cou
            WHERE
                u.UserID = ub.UserID");

        $sql->reset();
        $sql->query("
            UPDATE
                GDN_Badge b,
                (
                    SELECT
                        BadgeID,
                        count(*) AS cou
                    FROM
                        GDN_UserBadge
                    WHERE
                        Status = 'given'
                    GROUP BY
                        BadgeID) ub SET b.CountRecipients = ub.cou
                WHERE
                    b.BadgeID = ub.BadgeID");

        $this->logger()->success("Calculated badges.");
    }

    /**
     * Calculate the points from the badges.
     *
     * @return void
     * @throws \Exception
     */
    protected function calculateBadgesPoints(): void
    {
        $this->logger()->info("Calculating badges points.");
        $sql = $this->getDatabase()->createSql();
        $sql->query("
            INSERT INTO GDN_UserPoints
            SELECT
                'a',
                '1970-01-01 00:00:00',
                'Badge',
                0,
                UserID,
                sum(b.points) AS Points
            FROM
                GDN_UserBadge ub
                JOIN GDN_Badge b ON ub.BadgeID = b.BadgeID
            GROUP BY
                UserID
       ");
        $this->logger()->success("Calculated badges points.");
    }

    /**
     * Calculate the points from the reactions.
     *
     * @return void
     * @throws \Exception
     */
    protected function calculateReactionPoints(): void
    {
        $this->logger()->info("Calculating reaction points.");
        $sql = $this->getDatabase()->createSql();
        $sql->query("
            INSERT INTO GDN_UserPoints
            SELECT
                'a',
                '1970-01-01 00:00:00',
                'Reactions',
                0,
                ut.RecordID AS UserID,
                sum(ut.total * (ifnull(cast(json_extract(Attributes, '$.Points') as unsigned integer), 0))) AS Points
            FROM
                GDN_UserTag ut
                JOIN GDN_ReactionType t ON t.TagID = ut.TagID
            WHERE
                RecordType = 'User'
                AND ut.Total > 0
            GROUP BY
                ut.RecordID
        ");
        $this->logger()->success("Calculated reaction points.");
    }

    /**
     * Calculate the points from QnA.
     *
     * @return void
     * @throws \Exception
     */
    protected function recalculateQnA(): void
    {
        $this->logger()->info("Recalculating QnA points.");
        $sql = $this->getDatabase()->createSql();
        $sql->query("
            INSERT INTO GDN_UserPoints
            SELECT
                'a',
                '1970-01-01 00:00:00',
                'QnA',
                0,
                InsertUserID,
                sum(Points) AS Points
            FROM (
                 SELECT
                    d.DiscussionID,
                    c.InsertUserID,
                    1 AS Points
                FROM GDN_Comment c

                JOIN GDN_Discussion d ON c.DiscussionID = d.DiscussionID
                WHERE
                    d. `Type` = 'Question'
                GROUP BY d.DiscussionID, c.InsertUserID

                UNION

                SELECT
                    c.CommentID,
                    c.InsertUserID,
                    5 AS Points
                FROM
                    GDN_Comment c
                WHERE
                    c.QnA = 'Accepted') tmp
            GROUP BY
                InsertUserID
        ");
        $this->logger()->success("Recalculated QnA points.");
    }

    /**
     * Recalculate the total points for each user.
     *
     * @return void
     * @throws \Exception
     */
    protected function recalculateTotal(): void
    {
        $this->logger()->info("Recalculating total.");
        $sql = $this->getDatabase()->createSql();
        $sql->query("
            INSERT INTO GDN_UserPoints
            SELECT
                'a',
                '1970-01-01 00:00:00',
                'Total',
                0,
                UserID,
                sum(Points) AS Points
            FROM
                GDN_UserPoints up
            WHERE
                up. `Source` <> 'Total'
            GROUP BY
                UserID
        ");

        //Set user points
        $sql->reset();
        $sql->query("
            UPDATE
                GDN_User u,
                GDN_UserPoints up
            SET
                u.Points = up.Points
            WHERE
                up.UserID = u.UserID
                AND up. `Source` = 'Total'
                AND up.SlotType = 'a'
                AND up.TimeSlot = '1970-01-01 00:00:00'
        ");
        $this->logger()->success("Recalculated total.");
    }
}
