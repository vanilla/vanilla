<?php
/**
 * @author Sooraj Francis <sfrancis@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Dashboard\Models;

use Vanilla\Database\Operation\CurrentDateFieldProcessor;
use Vanilla\Database\Operation\CurrentUserFieldProcessor;
use Vanilla\Database\Operation\JsonFieldProcessor;
use Vanilla\Models\PipelineModel;
use Gdn_Session;

/**
 * AutomationRuleRevisionModel
 */
class AutomationRuleRevisionModel extends PipelineModel
{
    private AutomationRuleModel $automationRuleModel;

    private Gdn_Session $session;

    /**
     * AutomationRuleRevisionModel constructor.
     *
     * @param AutomationRuleModel $automationRuleModel
     * @param Gdn_Session $session
     */
    public function __construct(AutomationRuleModel $automationRuleModel, Gdn_Session $session)
    {
        parent::__construct("automationRuleRevision");

        $this->automationRuleModel = $automationRuleModel;
        $this->session = $session;

        $dateProcessor = new CurrentDateFieldProcessor();
        $dateProcessor->setInsertFields(["dateInserted"])->setUpdateFields([]);
        $this->addPipelineProcessor($dateProcessor);

        $userProcessor = new CurrentUserFieldProcessor($this->session);
        $userProcessor->setInsertFields(["insertUserID"])->setUpdateFields([]);
        $this->addPipelineProcessor($userProcessor);

        $this->addPipelineProcessor(new JsonFieldProcessor(["triggerValue", "actionValue"]));
    }

    /**
     * Structure for the automationRuleRevision table.
     *
     * @param \Gdn_Database $database
     * @param bool $explicit
     * @param bool $drop If true, and the table specified with $this->table() already exists,
     *  this method will drop the table before attempting to re-create it.
     * @return void
     * @throws \Exception
     */
    public static function structure(\Gdn_Database $database, bool $explicit = false, bool $drop = false): void
    {
        $database
            ->structure()
            ->table("automationRuleRevision")
            ->primaryKey("automationRuleRevisionID")
            ->column("automationRuleID", "int", false, "index")
            ->column("triggerType", "varchar(255)")
            ->column("triggerValue", "mediumtext") // JSON
            ->column("actionType", "varchar(255)")
            ->column("actionValue", "mediumtext") // JSON
            ->column("dateInserted", "datetime")
            ->column("insertUserID", "int")
            ->set($explicit, $drop);
    }
}
