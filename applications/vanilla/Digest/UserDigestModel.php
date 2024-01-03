<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Digest;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Database\Operation\CurrentDateFieldProcessor;
use Vanilla\Database\Operation\PruneProcessor;
use Vanilla\Models\PipelineModel;

class UserDigestModel extends PipelineModel implements LoggerAwareInterface
{
    use LoggerAwareTrait;
    public const STATUS_PENDING = "pending";

    public const STATUS_SKIPPED = "skipped";

    public const STATUS_SENT = "sent";
    public const STATUS_FAILED = "failed";
    public const STATUSES = [self::STATUS_PENDING, self::STATUS_SENT, self::STATUS_FAILED, self::STATUS_SKIPPED];

    private ConfigurationInterface $config;

    public function __construct(ConfigurationInterface $config)
    {
        $this->config = $config;
        parent::__construct("userDigest");
        $dateProcessor = new CurrentDateFieldProcessor();
        $dateProcessor->setInsertFields(["dateInserted", "dateUpdated"])->setUpdateFields(["dateUpdated"]);
        $this->addPipelineProcessor($dateProcessor);
        $this->addPipelineProcessor(new PruneProcessor("dateInserted", "2 weeks"));
    }

    public static function structure(\Gdn_DatabaseStructure $structure)
    {
        if ($structure->table("userDigest")->columnExists("userMeta")) {
            // The old DB structure was quite different but never shipped. Just drop it.
            $structure->table("userDigest")->drop();
        }

        $structure
            ->table("userDigest")
            ->column("digestID", "int", false, "primary")
            ->column("digestContentID", "int", false, "primary")
            ->column("userID", "int", false, "primary")
            ->column("status", self::STATUSES)
            ->column("forcedEmail", "varchar(255)", true)
            ->column("dateInserted", "datetime", false, "index")
            ->column("dateUpdated", "datetime", false, "index")
            ->set();

        $structure
            ->table("userDigest")
            ->createIndexIfNotExists("IX_userDigest_digestID_status_userID", ["digestID", "status", "userID"]);
    }

    /**
     * Get the total count of digest.
     *
     * @return mixed
     */
    public function getCountUsersInDigest(int $digestID): int
    {
        return $this->createSql()->getCount($this->getTable(), ["digestID" => $digestID]);
    }

    /**
     * @param int $digestID
     * @return \Iterator<int, array{
     *          digestID: int,
     *          userID: int,
     *          user: array,
     *          digestContentHash: string,
     *          digestContent: array,
     *          digestAttributes: array,
     *     } | false>
     */
    public function iterateUnsentDigests(int $digestID, int $chunkSize = 500): iterable
    {
        $lastUserID = 0;
        while (true) {
            $rows = $this->createSql()
                ->from("userDigest ud")
                ->select(["ud.userID", "ud.digestID", "ud.status", "ud.forcedEmail"])
                ->leftJoin("User u", "u.UserID = ud.userID")
                ->select(["u.Name", "u.Email", "u.Photo", "u.Deleted"])
                ->join("digestContent dc", "dc.digestContentID = ud.digestContentID")
                ->select(["dc.attributes", "dc.digestContent", "dc.digestContentHash"])
                ->where([
                    "ud.digestID" => $digestID,
                    "ud.status" => self::STATUS_PENDING,
                    "ud.userID >" => $lastUserID,
                ])
                ->orderBy("ud.digestID, ud.status, ud.userID", "ASC")
                ->limit($chunkSize)
                ->get()
                ->resultArray();

            foreach ($rows as $row) {
                $userID = $row["userID"];

                // If we don't have actual user data then the user was deleted.
                $username = $row["Name"] ?? null;
                $userPhoto = $row["Photo"] ?? null;
                $userEmail = $row["forcedEmail"] ?? ($row["Email"] ?? null);

                if (is_null($username) || is_null($userEmail) || ($userEmail["Deleted"] ?? false)) {
                    // Mark the user as skipped. They've been deleted since we started generating the digest.
                    yield $userID => false;
                    continue;
                }
                $userRecord = [
                    "UserID" => $userID,
                    "Name" => $username,
                    "Email" => $userEmail,
                    "Photo" => $userPhoto,
                ];
                $userRecord["PhotoUrl"] = userPhotoUrl($userRecord);
                unset($userRecord["Photo"]);

                yield $userID => [
                    "digestID" => $digestID,
                    "userID" => $userID,
                    "user" => $userRecord,
                    "digestContentHash" => $row["digestContentHash"],
                    "digestContent" => json_decode($row["digestContent"], true),
                    "digestAttributes" => json_decode($row["attributes"], true),
                ];

                $lastUserID = $userID;
            }

            if (empty($rows) || count($rows) < $chunkSize) {
                //No more results to process
                return;
            }
        }
    }
}
