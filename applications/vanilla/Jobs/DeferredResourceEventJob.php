<?php
/**
 * @author Olivier Lamy-Canuel <olamy-canuel@higherlogic.com>
 * @copyright 2009-2023 Higher Logic Inc.
 * @license Proprietary
 */

namespace Vanilla\Forum\Jobs;

use Garden\EventManager;
use Garden\Schema\Schema;
use UserModel;
use Vanilla\Scheduler\Job\JobExecutionStatus;
use Vanilla\Scheduler\Job\LocalJobInterface;

/**
 * Job used to run a ResourceEvent off the queue.
 */
class DeferredResourceEventJob implements LocalJobInterface
{
    protected int $id;
    protected EventManager $eventManager;
    protected UserModel $userModel;
    protected string $action;
    private string $modelClass;

    public function __construct(EventManager $eventManager, UserModel $userModel)
    {
        $this->eventManager = $eventManager;
        $this->userModel = $userModel;
    }

    public function setMessage(array $message)
    {
        $in = new Schema(["id:i", "model:s", "action:s"]);
        $in->validate($message);

        $this->id = $message["id"];
        $this->modelClass = $message["model"];
        $this->action = $message["action"];
    }

    public function run(): JobExecutionStatus
    {
        $model = \Gdn::getContainer()->get($this->modelClass);
        $record = $model->getID($this->id, DATASET_TYPE_ARRAY);
        $event = $model->eventFromRow((array) $record, $this->action, $this->userModel->currentFragment());

        $response = $this->eventManager->dispatch($event);
        return JobExecutionStatus::complete();
    }
}
