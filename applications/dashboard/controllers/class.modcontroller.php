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

//   protected $queues = array('premoderation', 'reported', 'spam');
   protected $queues = array('premoderation', 'reported', 'spam', 'testing');

   /**
    * Index.
    */
   public function index() {
      echo 'index';
   }

   /**
    * Pre moderation endpoint.
    */
   public function preModeration() {
      $this->setData('QueueName', 'premoderation');
      switch ($this->Request->RequestMethod()) {
         case 'GET':
            $this->getQueue('premoderation');
            break;
         case 'POST':
            $this->postQueue('premoderation');
            break;
         case 'PATCH':
            $this->postQueue('premoderation');
            break;
         case 'DELETE':
            $this->deleteQueue('premoderation');
            break;
         default:
            throw new Gdn_UserException('Invalid request method');
      }

   }

   /**
    * Unit testing moderation endpoint.
    */
   public function testing() {
      $queue = 'testing';
      $this->setData('QueueName', $queue);

      switch ($this->Request->RequestMethod()) {
         case 'GET':
            $this->getQueue($queue);
            break;
         case 'POST':
            $this->postQueue($queue);
            break;
         case 'PATCH':
            $this->postQueue($queue);
            break;
         case 'DELETE':
            $this->deleteQueue($queue);
            break;
         default:
            throw new Gdn_UserException('Invalid request method');
      }

   }

   protected function isQueueValid($queue) {
      if (!in_array($queue, $this->queues)) {
         throw new Gdn_UserException('Invalid moderation queue: ' . $queue);
      }
   }

   protected function getQueue($queue) {
      $this->isQueueValid($queue);
      $page = $this->Request->Get('page', 'p1');
      $status = $this->Request->Get('status');

      $queueModel = QueueModel::Instance();
      $where = array(
         'Queue' => $queue,
      );
      if ($status) {
         $where['Status'] = $status;
      }
      $queueItems = $queueModel->Get($queue, $page, $this->pageSize, $where);

      $this->setData(
         array(
            'QueueName' => $queue,
            'Queue' => $queueItems,
            'Page' => $page,
            'Totals' => $queueModel->GetQueueCounts($queue, $this->pageSize),
         )
      );
      $this->Render();
   }

   protected function postQueue($queue) {

      $this->isQueueValid($queue);

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

   protected function deleteQueue($queue) {
      $this->isQueueValid($queue);
      if (count($this->RequestArgs) != 1) {
         throw new Gdn_UserException('Missing Parameter: QueueID');
      }
      $queueID = $this->RequestArgs[0];
      if (!is_numeric($queueID) || $queueID == 0) {
         throw new Gdn_UserException('Invalid QueueID: ' . $queueID);
      }
      //cast to int
      $queueID = (int)$queueID;


      //soft delete from queue
      //now what...

      $this->SetData('QueueID', $queueID);
      $this->Render('mod', '', '');

   }

   /**
    * List items in the queue.
    */
   public function reported() {
   }

   /**
    * List items in the queue.
    */
   public function spam() {
   }

   public function set($property, $value) {
      $this->$property = $value;
   }


}