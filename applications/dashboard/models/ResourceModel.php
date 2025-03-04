<?php
/**
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Forum\Models;

use Exception;
use Garden\Web\Exception\ClientException;
use Gdn;
use Gdn_Cache;
use Gdn_Database;
use Gdn_Session;
use Vanilla\Database\Operation\CurrentDateFieldProcessor;
use Vanilla\Database\Operation\CurrentUserFieldProcessor;
use Vanilla\Exception\Database\NoResultsException;
use Vanilla\Models\FullRecordCacheModel;

/**
 * ResourceModel
 */
class ResourceModel extends FullRecordCacheModel
{
    private Gdn_Session $session;

    /**
     * ResourceModel constructor.
     *
     * @param Gdn_Cache $cache
     */
    public function __construct(Gdn_Cache $cache)
    {
        parent::__construct("resource", $cache);
        $this->session = Gdn::session();

        $dateProcessor = new CurrentDateFieldProcessor();
        $dateProcessor->setInsertFields(["dateInserted", "dateUpdated"])->setUpdateFields(["dateUpdated"]);
        $this->addPipelineProcessor($dateProcessor);

        $userProcessor = new CurrentUserFieldProcessor($this->session);
        $userProcessor->setInsertFields(["insertUserID", "updateUserID"])->setUpdateFields(["updateUserID"]);
        $this->addPipelineProcessor($userProcessor);
    }

    /**
     * Ensure that a resource exists.
     *
     * @param string $resource
     * @param bool $throw
     * @return bool
     * @throws ClientException
     */
    public function ensureResourceExists(string $resource, bool $throw = true): bool
    {
        $sql = $this->createSql();
        (bool) ($resourceExist = $sql->getCount("resource", ["urlCode" => $resource]));

        if ($resourceExist == 0 && $throw) {
            throw new ClientException("The '" . $resource . "' resource doesn't exist");
        }

        return $resourceExist > 0;
    }

    /**
     * Structure our database schema.
     *
     * @param Gdn_Database $database
     * @param bool $explicit
     * @param bool $drop
     * @return void
     * @throws Exception
     */
    public static function structure(Gdn_Database $database, bool $explicit = false, bool $drop = false): void
    {
        $database
            ->structure()
            ->table("resource")
            ->primaryKey("resourceID")
            ->column("name", "varchar(64)", false)
            ->column("sourceLocale", "varchar(10)", false)
            ->column("urlCode", "varchar(32)", false, ["index", "unique.resourecUrlCode"])
            ->column("dateInserted", "datetime")
            ->column("dateUpdated", "datetime")
            ->set($explicit, $drop);
    }
}
