<?php
/**
 * @copyright 2009-2014 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
 */

/**
 * Class ModController.
 */
class ModController extends DashboardController {

   protected $pageSize = 30;

   protected $queues = array('premoderation', 'reported', 'spam');

   /**
    * Primary endpoint.
    *
    * Handles requests that match:
    * GET mod/
    * POST, GET, PATCH, DELETE mod/{queueName}
    * GET mod/{queueID}
    *
    * Checks if request arguments match queue name or ID.  If matched we check the request type
    * and send to the appropriate method. 404 will returned if invalid arguments are provided.
    *
    * If no arguments are provided then index page is displayed.
    */
   public function index() {

      if (count($this->RequestArgs) > 0) {
         if ($this->isQueueValid($this->RequestArgs[0])) {
            $queueName = $this->RequestArgs[0];
            $this->setData('QueueName', $queueName);
            switch ($this->Request->RequestMethod()) {
               case 'GET':
                  $this->getQueue($queueName);
                  break;
               case 'POST':
                  $this->postQueue($queueName);
                  break;
               case 'PATCH':
                  $this->postQueue($queueName);
                  break;
               case 'DELETE':
                  $this->deleteQueue($queueName);
                  break;
               default:
                  throw new Gdn_UserException('Invalid request method');
            }
         } elseif (is_numeric($this->RequestArgs[0])) {
            $queueID = $this->RequestArgs[0];
            //update and delete items in the queue
            switch ($this->Request->RequestMethod()) {
               case 'GET':
                  $this->getQueueItem($queueID);
                  break;
               case 'POST':
                  $this->postQueueItem($queueID);
                  break;
               default:
                  throw new Gdn_UserException('Invalid request method');
            }
         } else {
            throw new Gdn_UserException('Not Found', 404);
         }
      }
      //default page to display if no request arguments

   }

   /**
    * Display queue totals.
    */
   public function totals() {
      $testing = $this->Request->Get('testing');
      if ($testing) {
         $queueTotals = $this->getQueueTotals(array('testing', 'testingSpam'));
      } else {
         $queueTotals = $this->getQueueTotals($this->queues);
      }
      $this->setData(array(
            'Queues' => $queueTotals,
         ));
      $this->Render();
   }

   /**
    * Get queue totals.
    * 
    * @param array $queues Queue names.
    * 
    * @return array
    */
   protected function getQueueTotals($queues) {

      $queueModel = QueueModel::Instance();
      $totals = array();
      foreach ($queues as $queue) {
         $totals[$queue] = $queueModel->GetQueueCounts($queue);
      }
      return $totals;
   }

   /**
    * Check if queue name is valid.
    * 
    * @param string $queue Queue name.
    * 
    * @return bool
    */
   protected function isQueueValid($queue) {
      if (stristr($queue, 'testing') !== false) {
         return true;
      }
      if (!in_array($queue, $this->queues)) {
         return false;
      }
      return true;
   }

   /**
    * Check if queue ID is valid.
    *
    * @param int $queueID QueueID.
    *
    * @return bool
    */
   protected function isQueueIDValid($queueID) {
      if (!is_numeric($queueID) || $queueID == 0) {
         return false;
      }
      return true;
   }

   /**
    * Get Queue.
    * 
    * @param string $queue Queue name.
    * 
    * @throws Gdn_UserException Queue name provided is invalid.
    */
   protected function getQueue($queue) {
      if (!$this->isQueueValid($queue)) {
         throw new Gdn_UserException('Invalid moderation queue: ' . $queue);
      }

      $page = $this->Request->Get('page', 'p1');
      $status = $this->Request->Get('status');

      $queueModel = QueueModel::Instance();
      $totals = $queueModel->GetQueueCounts($queue, $this->pageSize);
      $where = array(
         'Queue' => $queue,
      );
      if ($status) {
         $where['Status'] = $status;
         $totals['Records'] = $totals['Status'][$status];
         $totals['Pages'] = ceil($totals['Status'][$status]/$totals['PageSize']);
      }
      $queueItems = $queueModel->Get($queue, $page, $this->pageSize, $where);

      $this->setData(
         array(
            'QueueName' => $queue,
            'Queue' => $queueItems,
            'Page' => $page,
            'Totals' => $totals,
         )
      );
      $this->Render();
   }

   /**
    * Add items to the queue.
    * 
    * @param string $queue Queue name.
    *
    * @throws Gdn_UserException Queue name provided is invalid.
    */
   protected function postQueue($queue) {

      if (!$this->isQueueValid($queue)) {
         throw new Gdn_UserException('Invalid moderation queue: ' . $queue);
      }
      //validate fields
      $requiredFields = array(
         'name' => 'Name is a required field.',
         'body' => 'Body is a required field.',
         'foreigntype' => 'Foreign Type is a required field.',
         'foreignid' => 'Foreign ID is a required field.',
         'foreignuserid' => 'Foreign User ID is a required field.',
      );

      $post = array_change_key_case($this->Request->Post());
      foreach ($requiredFields as $field => $errorMsg) {
         if (!$v = val($field, $post)) {
            throw new Gdn_UserException($errorMsg);
         }
      }

      $data = $this->Request->Post();
      $data['Queue'] = $queue;

      $queueModel = QueueModel::Instance();
      $queueID = $queueModel->Save($data);
      $validationResults = $queueModel->ValidationResults();
      if ($validationResults) {
         $errorMsg = 'Validation Error: ';
         foreach ($validationResults as $field => $errors) {
            $errorMsg .= $field;
            foreach ($errors as $error) {
               $errorMsg .= ' - ' . $error;
            }
            $errorMsg .= "\n";
         }
         throw new Gdn_UserException($errorMsg);
      }
      if (!$queueID) {
         throw new Gdn_UserException('Error saving record to queue.');
      }
      $this->SetData('QueueID', $queueID);
      $this->Render();

   }

   /**
    * Remove items from the queue.
    *
    * @param string $queue Queue name.
    *
    * @throws Gdn_UserException Queue ID or name provided is invalid.
    */
   protected function deleteQueue($queue) {
      if (!$this->isQueueValid($queue)) {
         throw new Gdn_UserException('Invalid moderation queue: ' . $queue);
      }
      if (count($this->RequestArgs) != 2) {
         throw new Gdn_UserException('Missing Parameter: QueueID');
      }
      $queueID = $this->RequestArgs[1];
      if (!$this->isQueueIDValid($queueID)) {
         throw new Gdn_UserException('Invalid QueueID: ' . $queueID);
      }
      //cast to int
      $queueID = (int)$queueID;

      //soft delete from queue?

      $this->SetData('QueueID', $queueID);
      $this->Render();

   }

   /**
    * Display queue item.
    *
    * @param int $queueID QueueID.
    *
    * @throws Gdn_UserException Queue ID is invalid.
    */
   protected function getQueueItem($queueID) {

      if (!$this->isQueueIDValid($queueID)) {
         throw new Gdn_UserException('Invalid QueueID: ' . $queueID);
      }
      $queueModel = QueueModel::Instance();
      $item = $queueModel->GetID($queueID);

      $this->SetData('Item', $item);
      $this->Render();
   }

   protected function postQueueItem($queueID) {
      if (!$this->isQueueIDValid($queueID)) {
         throw new Gdn_UserException('Invalid QueueID: ' . $queueID);
      }
      //cast to int
      $queueID = (int)$queueID;
      $queueModel = QueueModel::Instance();
      $data = $this->Request->Post();
      $data['QueueID'] = $queueID;
      $queueModel->Save($data);
      $validationResults = $queueModel->ValidationResults();
      if (count($validationResults) > 0) {
         $this->SetData('Data', $data);
         throw new Gdn_UserException('Invalid request, validation failed.');
      }
      $this->SetData('QueueID', $queueID);
      $this->Render();
   }
}
