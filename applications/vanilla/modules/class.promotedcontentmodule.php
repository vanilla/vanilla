<?php if (!defined('APPLICATION')) exit();

/**
 * Renders "Promoted" discussions and comments according to criteria.
 *
 *  - Posted by a certain role
 *  - Reached bestof status
 *  - Latest from a certain category
 *
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
 * @since 2.0.17.9
 * @package Vanilla
 */
class PromotedContentModule extends Gdn_Module {

   /**
    * How should we choose the content?
    *  - role        Author's Role
    *  - rank        Author's Rank
    *  - category    Content's Category
    *  - score       Content's Score
    *  - promoted
    * @var string
    */
   public $Selector;

   /**
    * Parameters for the selector method
    * @var mixed
    */
   public $Selection;

   /**
    * What type of content to fetch.
    * - all
    * - discussions
    * - comments
    * @var string
    */
   public $ContentType = 'all';

   /**
    * How much content should be fetched
    * @var integer
    */
   public $Limit = 9;

   /**
    * How often should we encapsulate content in groups
    * Groups of: n
    * @var integer
    */
   public $Group = 3;

   /**
    * How many chars of Title to return
    * @var integer
    */
   public $TitleLimit = 0;

   /**
    * How many chars of Body to return
    * @var integer
    */
   public $BodyLimit = 0;

   /**
    * How long do we cache?
    * Units: seconds
    * Default: 10 minutes
    * @var integer
    */
   public $Expiry = 60;

   /**
    * @var array Whitelist of accepted parameters.
    */
   public $Properties = array(
      'Selector',
      'Selection',
      'ContentType',
      'Limit',
      'Group',
      'TitleLimit',
      'BodyLimit',
      'Expiry'
   );

   public function __construct() {
      parent::__construct();
      $this->_ApplicationFolder = 'vanilla';
   }

   /**
    * Set class properties.
    *
    * @param array $Parameters Use lowercase key names that map to class properties.
    */
   public function Load($Parameters = array()) {
      $Result = $this->Validate($Parameters);
      if ($Result === true) {
         // Match existing properties to validates parameters.
         foreach ($this->Properties as $Property) {
            $key = strtolower($Property);
            if (isset($Parameters[$key])) {
               $this->$Property = $Parameters[$key];
            }
         }
         return true;
      } else {
         // Error messages.
         return $Result;
      }
   }

   /**
    * Validate data to be used as class properties.
    *
    * @param array $Parameters.
    *
    * @return mixed true on success or string (message) on error.
    */
   public function Validate($Parameters = array()) {
      $validation = new Gdn_Validation();

      // Validate integer properties.
      $validation->applyRule('expiry', 'Integer');
      $validation->applyRule('limit', 'Integer');
      $validation->applyRule('bodylimit', 'Integer');
      $validation->applyRule('titlelimit', 'Integer');
      $validation->applyRule('group', 'Integer');

      // Validate selection.
      $validation->applyRule('selection', 'String');

      // Validate selector.
      $validation->applyRule('selector', 'Required');
      $selectorWhitelist = array('role', 'rank', 'category', 'score', 'promoted');
      if (isset($Parameters['selector']) && !in_array($Parameters['selector'], $selectorWhitelist)) {
         $validation->AddValidationResult('selector', 'Invalid selector.');
      }

      // Validate ContentType.
      $typeWhitelist = array('all', 'discussions', 'comments');
      if (isset($Parameters['contenttype']) && !in_array($Parameters['contenttype'], $typeWhitelist)) {
         $validation->AddValidationResult('contenttype', 'Invalid contenttype.');
      }

      $result = $validation->validate($Parameters);
      return ($result === true) ? true : $validation->resultsText();
   }

   /**
    * Get data based on class properties.
    */
   public function GetData() {
      $this->SetData('Content', FALSE);
      $SelectorMethod = 'SelectBy'.ucfirst($this->Selector);
      if (method_exists($this, $SelectorMethod)) {
         $this->SetData('Content', call_user_func(array($this, $SelectorMethod), $this->Selection));
      } else {
         $this->FireEvent($SelectorMethod);
      }
   }

   /**
    * Select content based on author RoleID.
    *
    * @param mixed $Parameters
    *
    * @return array|false
    */
   protected function SelectByRole($Parameters) {
      if (!is_array($Parameters)) {
         $RoleID = $Parameters;
      } else {
         $RoleID = GetValue('RoleID', $Parameters, NULL);
      }

      // Lookup role name -> roleID
      if ($RoleID && is_string($RoleID)) {
         $RoleModel = new RoleModel();
         $Roles = explode(',', $RoleID);
         $RoleID = array();
         foreach ($Roles as $TestRoleID) {
            $TestRoleID = trim($TestRoleID);
            $Role = $RoleModel->GetByName($TestRoleID);
            if (!$Role) {
               continue;
            } else {
               $Role = array_shift($Role);
               $RoleID[] = GetValue('RoleID', $Role);
            }
         }
      }

      if (empty($RoleID) || !sizeof($RoleID)) {
         return false;
      }

      // Check cache
      $SelectorRoleCacheKey = "modules.promotedcontent.role.{$RoleID}";
      $Content = Gdn::Cache()->Get($SelectorRoleCacheKey);

      if ($Content == Gdn_Cache::CACHEOP_FAILURE) {

         // Get everyone with this Role
         $UserIDs = Gdn::SQL()->Select('ur.UserID')
            ->From('UserRole ur')
            ->Where('ur.RoleID', $RoleID)
            ->GroupBy('UserID')
            ->Get()->Result(DATASET_TYPE_ARRAY);
         $UserIDs = ConsolidateArrayValuesByKey($UserIDs, 'UserID');

         // Get matching Discussions
         $Discussions = array();
         if ($this->ShowDiscussions()) {
            $Discussions = Gdn::SQL()->Select('d.*')
               ->From('Discussion d')
               ->WhereIn('d.InsertUserID', $UserIDs)
               ->OrderBy('DateInserted', 'DESC')
               ->Limit($this->Limit)
               ->Get()->Result(DATASET_TYPE_ARRAY);
         }

         // Get matching Comments
         $Comments = array();
         if ($this->ShowComments()) {
            $Comments = Gdn::SQL()->Select('c.*')
               ->From('Comment c')
               ->WhereIn('InsertUserID', $UserIDs)
               ->OrderBy('DateInserted', 'DESC')
               ->Limit($this->Limit)
               ->Get()->Result(DATASET_TYPE_ARRAY);

            $this->JoinCategory($Comments);
         }

         // Interleave
         $Content = $this->Union('DateInserted', array(
            'Discussion'   => $Discussions,
            'Comment'      => $Comments
         ));
         $this->Prepare($Content);

         // Add result to cache
         Gdn::Cache()->Store($SelectorRoleCacheKey, $Content, array(
            Gdn_Cache::FEATURE_EXPIRY  => $this->Expiry
         ));
      }

      $this->Security($Content);
      $this->Condense($Content, $this->Limit);
      return $Content;
   }

   /**
    * Select content based on author RankID.
    *
    * @param mixed $Parameters
    *
    * @return array|false
    */
   protected function SelectByRank($Parameters) {
      if (!is_array($Parameters)) {
         $RankID = $Parameters;
      } else {
         $RankID = GetValue('RankID', $Parameters, NULL);
      }

      if (!$RankID) {
         return false;
      }

      // Check cache
      $SelectorRankCacheKey = "modules.promotedcontent.rank.{$RankID}";
      $Content = Gdn::Cache()->Get($SelectorRankCacheKey);

      if ($Content == Gdn_Cache::CACHEOP_FAILURE) {

         // Get everyone with this Role
         $UserIDs = Gdn::SQL()->Select('u.UserID')
            ->From('User u')
            ->Where('u.RankID', $RankID)
            ->GroupBy('UserID')
            ->Get()->Result(DATASET_TYPE_ARRAY);
         $UserIDs = ConsolidateArrayValuesByKey($UserIDs, 'UserID');

         // Get matching Discussions
         $Discussions = array();
         if ($this->ShowDiscussions()) {
            $Discussions = Gdn::SQL()->Select('d.*')
               ->From('Discussion d')
               ->WhereIn('d.InsertUserID', $UserIDs)
               ->OrderBy('DateInserted', 'DESC')
               ->Limit($this->Limit)
               ->Get()->Result(DATASET_TYPE_ARRAY);
         }

         // Get matching Comments
         $Comments = array();
         if ($this->ShowComments()) {
            $Comments = Gdn::SQL()->Select('c.*')
               ->From('Comment c')
               ->WhereIn('InsertUserID', $UserIDs)
               ->OrderBy('DateInserted', 'DESC')
               ->Limit($this->Limit)
               ->Get()->Result(DATASET_TYPE_ARRAY);

            $this->JoinCategory($Comments);
         }

         // Interleave
         $Content = $this->Union('DateInserted', array(
            'Discussion'   => $Discussions,
            'Comment'      => $Comments
         ));
         $this->Prepare($Content);

         // Add result to cache
         Gdn::Cache()->Store($SelectorRankCacheKey, $Content, array(
            Gdn_Cache::FEATURE_EXPIRY  => $this->Expiry
         ));
      }

      $this->Security($Content);
      $this->Condense($Content, $this->Limit);
      return $Content;
   }

   /**
    * Select content based on its CategoryID.
    *
    * @param mixed $Parameters
    *
    * @return array|false
    */
   protected function SelectByCategory($Parameters) {
      if (!is_array($Parameters)) {
         $CategoryID = $Parameters;
      } else {
         $CategoryID = GetValue('CategoryID', $Parameters, NULL);
      }

      if (!$CategoryID) {
         return false;
      }

      // Check cache
      $SelectorCategoryCacheKey = "modules.promotedcontent.category.{$CategoryID}";
      $Content = Gdn::Cache()->Get($SelectorCategoryCacheKey);

      if ($Content == Gdn_Cache::CACHEOP_FAILURE) {

         // Get matching Discussions
         $Discussions = array();
         if ($this->ShowDiscussions()) {
            $Discussions = Gdn::SQL()->Select('d.*')
               ->From('Discussion d')
               ->Where('d.CategoryID', $CategoryID)
               ->OrderBy('DateInserted', 'DESC')
               ->Limit($this->Limit)
               ->Get()->Result(DATASET_TYPE_ARRAY);
         }

         // Get matching Comments
         $Comments = array();
         if ($this->ShowComments()) {
            $CommentDiscussionIDs = Gdn::SQL()->Select('d.DiscussionID')
               ->From('Discussion d')
               ->Where('d.CategoryID', $CategoryID)
               ->OrderBy('DateLastComment', 'DESC')
               ->Limit($this->Limit)
               ->Get()->Result(DATASET_TYPE_ARRAY);
            $CommentDiscussionIDs = array_column($CommentDiscussionIDs, 'DiscussionID');

            $Comments = Gdn::SQL()->Select('c.*')
               ->From('Comment c')
               ->WhereIn('DiscussionID', $CommentDiscussionIDs)
               ->OrderBy('DateInserted', 'DESC')
               ->Limit($this->Limit)
               ->Get()->Result(DATASET_TYPE_ARRAY);

            $this->JoinCategory($Comments);
         }

         // Interleave
         $Content = $this->Union('DateInserted', array(
            'Discussion'   => $Discussions,
            'Comment'      => $Comments
         ));
         $this->Prepare($Content);

         // Add result to cache
         Gdn::Cache()->Store($SelectorCategoryCacheKey, $Content, array(
            Gdn_Cache::FEATURE_EXPIRY  => $this->Expiry
         ));
      }

      $this->Security($Content);
      $this->Condense($Content, $this->Limit);
      return $Content;
   }

   /**
    * Select content based on its Score.
    *
    * @param mixed $Parameters
    *
    * @return array|false
    */
   protected function SelectByScore($Parameters) {
      if (!is_array($Parameters)) {
         $MinScore = $Parameters;
      } else {
         $MinScore = GetValue('Score', $Parameters, null);
      }

      if (!is_integer($MinScore)) {
         $MinScore = false;
      }

      // Check cache
      $SelectorScoreCacheKey = "modules.promotedcontent.score.{$MinScore}";
      $Content = Gdn::Cache()->Get($SelectorScoreCacheKey);

      if ($Content == Gdn_Cache::CACHEOP_FAILURE) {

         // Get matching Discussions
         $Discussions = array();
         if ($this->ShowDiscussions()) {
            $Discussions = Gdn::SQL()->Select('d.*')
               ->From('Discussion d')
               ->OrderBy('DateInserted', 'DESC')
               ->Limit($this->Limit);
            if ($MinScore !== FALSE) {
               $Discussions->Where('Score >', $MinScore);
            }
            $Discussions = $Discussions->Get()->Result(DATASET_TYPE_ARRAY);
         }

         // Get matching Comments
         $Comments = array();
         if ($this->ShowComments()) {
            $Comments = Gdn::SQL()->Select('c.*')
               ->From('Comment c')
               ->OrderBy('DateInserted', 'DESC')
               ->Limit($this->Limit);
            if ($MinScore !== FALSE) {
               $Comments->Where('Score >', $MinScore);
            }
            $Comments = $Comments->Get()->Result(DATASET_TYPE_ARRAY);

            $this->JoinCategory($Comments);
         }

         // Interleave
         $Content = $this->Union('DateInserted', array(
            'Discussion'   => $Discussions,
            'Comment'      => $Comments
         ));
         $this->Prepare($Content);

         // Add result to cache
         Gdn::Cache()->Store($SelectorScoreCacheKey, $Content, array(
            Gdn_Cache::FEATURE_EXPIRY  => $this->Expiry
         ));
      }

      $this->Security($Content);
      $this->Condense($Content, $this->Limit);
      return $Content;
   }

   /**
    * Selected content that passed the Promoted threshold.
    *
    * This uses the Reactions caching system & options.
    *
    * @param array $Parameters Not used.
    * @return array|false $Content
    */
   protected function SelectByPromoted($Parameters) {
      if (!class_exists('ReactionModel')) {
         return;
      }

      $RecordTypes = array();
      if ($this->ShowDiscussions()) {
         $RecordTypes[] = 'Discussion';
      }
      if ($this->ShowComments()) {
         $RecordTypes[] = 'Comment';
      }

      $ReactionModel = new ReactionModel();
      $PromotedTagID = $ReactionModel->DefineTag('Promoted', 'BestOf');
      $Content = $ReactionModel->GetRecordsWhere(
          array('TagID' => $PromotedTagID, 'RecordType' => $RecordTypes),
          'DateInserted',
          'desc',
          $this->Limit);

      $this->Prepare($Content);

      return $Content;
   }

   /**
    * Attach CategoryID to Comments
    *
    * @param array $Comments
    */
   protected function JoinCategory(&$Comments) {
      $DiscussionIDs = array();

      foreach ($Comments as &$Comment) {
         $DiscussionIDs[$Comment['DiscussionID']] = TRUE;
      }
      $DiscussionIDs = array_keys($DiscussionIDs);

      $Discussions = Gdn::SQL()->Select('d.*')
         ->From('Discussion d')
         ->WhereIn('DiscussionID', $DiscussionIDs)
         ->Get()->Result(DATASET_TYPE_ARRAY);

      $DiscussionsByID = array();
      foreach ($Discussions as $Discussion) {
         $DiscussionsByID[$Discussion['DiscussionID']] = $Discussion;
      }
      unset($Discussions);

      foreach ($Comments as &$Comment) {
         $Comment['Discussion'] = $DiscussionsByID[$Comment['DiscussionID']];
         $Comment['CategoryID'] = GetValueR('Discussion.CategoryID', $Comment);
      }
   }

   /**
    * Interleave two or more result arrays by a common field
    *
    * @param string $Field
    * @param array $Sections Array of result arrays
    * @return array
    */
   protected function Union($Field, $Sections) {
      if (!is_array($Sections)) return;

      $Interleaved = array();
      foreach ($Sections as $SectionType => $Section) {
         if (!is_array($Section)) continue;

         foreach ($Section as $Item) {
            $ItemField = GetValue($Field, $Item);
            $Interleaved[$ItemField] = array_merge($Item, array('RecordType' => $SectionType));

            ksort($Interleaved);
         }
      }

      $Interleaved = array_reverse($Interleaved);
      return $Interleaved;
   }

   /**
    * Pre-process content into a uniform format for output
    *
    * @param Array $Content By reference
    */
   protected function Prepare(&$Content) {

      foreach ($Content as &$ContentItem) {
         $ContentType = val('RecordType', $ContentItem);

         $Replacement = array();
         $Fields = array('DiscussionID', 'CategoryID', 'DateInserted', 'DateUpdated', 'InsertUserID', 'Body', 'Format', 'RecordType');

         switch (strtolower($ContentType)) {
            case 'comment':
               $Fields = array_merge($Fields, array('CommentID'));

               // Comment specific
               $Replacement['Name'] = GetValueR('Discussion.Name', $ContentItem, val('Name', $ContentItem));
               break;

            case 'discussion':
               $Fields = array_merge($Fields, array('Name', 'Type'));
               break;
         }

         $Fields = array_fill_keys($Fields, TRUE);
         $Common = array_intersect_key($ContentItem, $Fields);
         $Replacement = array_merge($Replacement, $Common);
         $ContentItem = $Replacement;

         // Attach User
         $UserID = GetValue('InsertUserID', $ContentItem);
         $User = Gdn::UserModel()->GetID($UserID);
         $ContentItem['Author'] = $User;
      }
   }

   /**
    * Strip out content that this user is not allowed to see
    *
    * @param array $Content Content array, by reference
    */
   protected function Security(&$Content) {
      if (!is_array($Content)) {
         return;
      }
      $Content = array_filter($Content, array($this, 'SecurityFilter'));
   }

   /**
    * Determine if we have permission to view this content.
    *
    * @param $ContentItem
    * @return bool
    */
   protected function SecurityFilter($ContentItem) {
      $CategoryID = GetValue('CategoryID', $ContentItem, NULL);
      if (is_null($CategoryID) || $CategoryID === FALSE) {
         return false;
      }

      $Category = CategoryModel::Categories($CategoryID);
      $CanView = GetValue('PermsDiscussionsView', $Category);
      if (!$CanView) {
         return false;
      }

      return true;
   }

   /**
    * Condense an interleaved content list down to the required size
    *
    * @param array $Content
    * @param array $Limit
    */
   protected function Condense(&$Content, $Limit) {
      $Content = array_slice($Content, 0, $Limit);
   }

   /**
    * Whether to display promoted comments.
    *
    * @return bool
    */
   public function ShowComments() {
      return ($this->ContentType == 'all' || $this->ContentType == 'comments') ? true : false;
   }

   /**
    * Whether to display promoted discussions.
    *
    * @return bool
    */
   public function ShowDiscussions() {
      return ($this->ContentType == 'all' || $this->ContentType == 'discussions') ? true : false;
   }

   /**
    * Default asset target for this module.
    *
    * @return string
    */
   public function AssetTarget() {
      return 'Content';
   }

   /**
    * Render.
    *
    * @return string
    */
   public function ToString() {
      if ($this->Data('Content', NULL) == NULL) {
         $this->GetData();
      }

      return parent::ToString();
   }
}
