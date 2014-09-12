<?php
/**
 * @copyright 2009-2014 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
 */

/**
 * Class ModController.
 */
class ModController extends DashboardController {

   /**
    * @var int Default Number of results per page.
    */
   public $pageSize = 30;

   /**
    * @var array Valid queue names.  Any queue that starts with 'testing' will be allowed.
    */
   public $queues = array('premoderation', 'reported', 'spam');

   /**
    * @var Gdn_Form
    */
   public $form;

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

      $this->Permission('Garden.Moderation.Manage');

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
               case 'DELETE':
                  $this->deleteQueueItem($queueID);
                  break;
               case 'PATCH':
                  $this->postQueueItem($queueID);
                  break;
               default:
                  throw new Gdn_UserException('Invalid request method');
            }
         } else {
            throw new Gdn_UserException('Not Found', 404);
         }
      }
   }

   /**
    * Display queue totals.
    */
   public function totals() {

      $this->Permission('Garden.Moderation.Manage');

      $testing = $this->Request->Get('testing');
      if ($testing) {
         $queueTotals = $this->getQueueTotals(array('testing', 'testingSpam'));
      } else {
         $queueTotals = $this->getQueueTotals($this->queues);
      }
      $this->setData(array(
            'Queues' => $queueTotals,
         ));
      $this->Render('blank', 'utility', 'dashboard');
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

      $page = $this->Request->Get('Page', 'p1');
      $status = $this->Request->Get('Status');
      $categoryID = $this->Request->Get('CategoryID');
      $order = $this->Request->Get('Order');

      $queueModel = QueueModel::Instance();
      $where = array(
         'Queue' => $queue,
      );
      if ($status) {
         $where['Status'] = $status;
      }
      if ($categoryID) {
         $where['CategoryID'] = $categoryID;
      }
      if (strcasecmp($order, 'ASC') && strcasecmp($order, 'DESC')) {
         $order = 'desc';
      }
      $totals = $queueModel->GetQueueCounts($queue, $this->pageSize, $where);
      $queueItems = $queueModel->Get($queue, $page, $this->pageSize, $where, 'DateInserted', $order);
      $this->setData(
         array(
            'QueueName' => $queue,
            'Queue' => $queueItems,
            'Page' => $page,
            'Totals' => $totals,
         )
      );
      $this->Render('blank', 'utility', 'dashboard');
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
         'body' => 'Body is a required field.',
         'format' => 'Format is a required field.',
         'foreigntype' => 'Foreign Type is a required field.',
         'foreignid' => 'Foreign ID is a required field.',
         'foreignuserid' => 'Foreign User ID is a required field.',
      );
      $post = array_change_key_case($this->Request->Post());
      if ($post['foreigntype'] == 'discussion') {
         $requiredFields['name'] = 'Name is a required field.';
      }
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
         $errorMsg .= $queueModel->Validation->ResultsText();
         throw new Gdn_UserException($errorMsg, 400);
      }
      if (!$queueID) {
         throw new Gdn_UserException('Error saving record to queue.');
      }
      $this->SetData('QueueID', $queueID);
      $this->Render('blank','utility','dashboard');

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
         throw new Gdn_UserException('Missing parameter: QueueID');
      }
      $queueID = $this->RequestArgs[1];
      if (!$this->isQueueIDValid($queueID)) {
         throw new Gdn_UserException('Invalid QueueID: ' . $queueID);
      }
      //cast to int
      $queueID = (int)$queueID;

      $queueModel = QueueModel::Instance();
      $queueModel->Delete($queueID);

      $this->SetData('QueueID', $queueID);
      $this->Render('blank','utility','dashboard');

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

      if ($item == false) {
         throw new Gdn_UserException('Not Found', 404);
      }

      $this->SetData('Item', $item);
      $this->Render('blank','utility','dashboard');
   }


   /**
    * Get queue items by relations
    *
    * @throws Gdn_UserException
    *
    */
   public function relation() {

      $this->Permission('Garden.Moderation.Manage');

      if ($this->RequestMethod != 'GET') {
         throw new Gdn_UserException('Invalid request method.  Only GET is accepted.');
      }

      $this->Render('blank','utility','dashboard');


   }

   /**
    * Update item in the queue.
    *
    * @param int $queueID QueueID.
    *
    * @throws Gdn_UserException QueueID or any other input is invalid.
    */
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
         $this->form->SetValidationResults($validationResults);
      }
      $this->SetData('QueueID', $queueID);
      $this->Render('blank','utility','dashboard');
   }

   /**
    * Delete an item from the queue.
    *
    * @param int $queueID QueueID
    *
    * @throws Gdn_UserException If queueID is invalid.
    */
   protected function deleteQueueItem($queueID) {
      if (!$this->isQueueIDValid($queueID)) {
         throw new Gdn_UserException('Invalid QueueID: ' . $queueID);
      }
      //cast to int
      $queueID = (int)$queueID;
      $data = $this->Request->Post();
      $data['QueueID'] = $queueID;

      $queueModel = QueueModel::Instance();
      $queueModel->Delete($queueID);

      $this->SetData('QueueID', $queueID);
      $this->Render('blank','utility','dashboard');
   }



   /**
    * Approve an item in the queue.
    *
    * @param $queueID
    * @throws Gdn_UserException
    */
   public function approve($queueID) {

      $this->Permission('Garden.Moderation.Manage');

      if (Gdn::Request()->RequestMethod() !== 'POST') {
         throw new Gdn_UserException("This resource only accepts POST.", 405);
      }

      $queueModel = QueueModel::Instance();
      $approved = $queueModel->approve($queueID);

      $this->SetData('Approved', $approved);
      $this->Render('blank','utility','dashboard');

   }

   /**
    * Deny an item in the queue.
    *
    * @param $queueID
    * @throws Gdn_UserException
    */
   public function deny($queueID) {

      $this->Permission('Garden.Moderation.Manage');
      if (Gdn::Request()->RequestMethod() !== 'POST') {
         throw new Gdn_UserException("This resource only accepts POST.", 405);
      }


      $queueModel = QueueModel::Instance();
      $denied = $queueModel->deny($queueID);

      $this->SetData('Denied', $denied);
      $this->Render('blank','utility','dashboard');

   }

   /**
    * Reporting endpoint.
    *
    * Example:
    *    URL: POST http://localhost/api/v1/mod.json/report/?access_token=d7db8b7f0034c13228e4761bf1bfd434
    *
    * {
    *    "ForeignID" : "148",
    *    "ForeignType" : "Discussion",
    *    "Reason" : "Reason",
    *    "ReportUserID" : 3
    * }
    *
    * @throws Gdn_UserException
    */
   public function report() {

      if (Gdn::Request()->RequestMethod() !== 'POST') {
         throw new Gdn_UserException("This resource only accepts POST.", 405);
      }

      $queueModel = QueueModel::Instance();
      // Validate request
      if (Gdn::Request()->RequestMethod() != 'POST') {
         throw new Gdn_UserException('Invalid Resuest Method: ' . Gdn::Request()->RequestMethod());
      }
      $post = array_change_key_case($this->Request->Post());
      $queue = 'reported';
      if (GetValue('queue', $post)) {
         $queue = $post['queue'];
      }
      if (!$this->isQueueValid($queue)) {
         throw new Gdn_UserException('Invalid moderation queue: ' . $queue);
      }
      //validate fields
      $requiredFields = array(
         'foreigntype' => 'Foreign Type is a required field.',
         'foreignid' => 'Foreign ID is a required field.',
      );
      foreach ($requiredFields as $field => $errorMsg) {
         if (!$v = val($field, $post)) {
            throw new Gdn_UserException($errorMsg);
         }
      }

      $report['ReportUserID'] = GetValue('reportuserid', $post, Gdn::Session()->UserID);
      $report['Reason'] = GetValue('reason', $post, null);

      $queueID = $queueModel->report($post['foreigntype'], $post['foreignid'], $report);
      $this->SetData('Reported', false);
      if ($queueID !=0 && is_numeric($queueID)) {
         $this->SetData('Reported', true);
         $this->SetData('QueueID', $queueID);
      }
      $this->Render('blank','utility','dashboard');
   }

}
