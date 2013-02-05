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
    * @var string
    */
   public $Selector;
   
   /**
    * Parameters for the selector method
    * @var type 
    */
   public $Selection;
   
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
    * How long do we cache?
    * Units: seconds
    * Default: 10 minutes
    * @var integer
    */
   public $Expiry = 60;
   
   public function __construct() {
      parent::__construct();
   }
   
   public function GetData() {
      $this->SetData('Content', FALSE);
      $SelectorMethod = 'SelectBy'.ucfirst($this->Selector);
      if (method_exists($this, $SelectorMethod)) {
         $this->SetData('Content', call_user_func(array($this, $SelectorMethod), $this->Selection));
      }
   }
   
   /**
    * Select content based on author RoleID
    * 
    * @param array $Parameters
    * @return boolean
    */
   protected function SelectByRole($Parameters) {
      if (!is_array($Parameters)) {
         $RoleID = $Parameters;
      } else {
         $RoleID = GetValue('RoleID', $Parameters, NULL);
      }
      
      // Lookup role name -> roleID
      if (is_string($RoleID)) {
         $RoleModel = new RoleModel();
         $Role = $RoleModel->GetByName($RoleID);
         if (!$Role) {
            $RoleID = NULL;
         } else {
            $Role = array_shift($Role);
            $RoleID = GetValue('RoleID', $Role);
         }
      }
      
      if (!$RoleID) return FALSE;
      
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
         $Discussions = Gdn::SQL()->Select('d.*')
            ->From('Discussion d')
            ->WhereIn('d.InsertUserID', $UserIDs)
            ->OrderBy('DateInserted', 'DESC')
            ->Limit($this->Limit)
            ->Get()->Result(DATASET_TYPE_ARRAY);

         // Get matching Comments
         $Comments = Gdn::SQL()->Select('c.*')
            ->From('Comment c')
            ->WhereIn('InsertUserID', $UserIDs)
            ->OrderBy('DateInserted', 'DESC')
            ->Limit($this->Limit)
            ->Get()->Result(DATASET_TYPE_ARRAY);
         
         $this->JoinCategory($Comments);
         
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
    * Select content based on author RankID
    * 
    * @param array $Parameters
    * @return boolean
    */
   protected function SelectByRank($Parameters) {
      $RankID = GetValue('RankID', $Parameters, NULL);
      if (!$RankID) return FALSE;
      
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
         $Discussions = Gdn::SQL()->Select('d.*')
            ->From('Discussion d')
            ->WhereIn('d.InsertUserID', $UserIDs)
            ->OrderBy('DateInserted', 'DESC')
            ->Limit($this->Limit)
            ->Get()->Result(DATASET_TYPE_ARRAY);

         // Get matching Comments
         $Comments = Gdn::SQL()->Select('c.*')
            ->From('Comment c')
            ->WhereIn('InsertUserID', $UserIDs)
            ->OrderBy('DateInserted', 'DESC')
            ->Limit($this->Limit)
            ->Get()->Result(DATASET_TYPE_ARRAY);
         
         $this->JoinCategory($Comments);
         
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
    * Select content based on its CategoryID
    * 
    * @param integer $CategoryID
    * @return boolean
    */
   protected function SelectByCategory($CategoryID) {
      $CategoryID = GetValue('CategoryID', $Parameters, NULL);
      if (!$CategoryID) return FALSE;
      
      // Check cache
      $SelectorCategoryCacheKey = "modules.promotedcontent.category.{$CategoryID}";
      $Content = Gdn::Cache()->Get($SelectorCategoryCacheKey);
      
      if ($Content == Gdn_Cache::CACHEOP_FAILURE) {
      
         // Get matching Discussions
         $Discussions = Gdn::SQL()->Select('d.*')
            ->From('Discussion d')
            ->Where('d.CategoryID', $CategoryID)
            ->OrderBy('DateInserted', 'DESC')
            ->Limit($this->Limit)
            ->Get()->Result(DATASET_TYPE_ARRAY);

         // Get matching Comments
         $CommentDiscussionIDs = Gdn::SQL()->Select('d.DiscussionID')
            ->From('Discussion d')
            ->Where('d.CategoryID', $CategoryID)
            ->OrderBy('DateLastComment', 'DESC')
            ->Limit($this->Limit)
            ->Get()->Result(DATASET_TYPE_ARRAY);
         
         $Comments = Gdn::SQL()->Select('c.*')
            ->From('Comment c')
            ->WhereIn('DiscussionID', $CommentDiscussionIDs)
            ->OrderBy('DateInserted', 'DESC')
            ->Limit($this->Limit)
            ->Get()->Result(DATASET_TYPE_ARRAY);
         
         $this->JoinCategory($Comments);
         
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
    * Select content based on its Score
    * 
    * @param array $Parameters
    * @todo complete
    * @return boolean
    */
   protected function SelectByScore($Parameters) {
      if (!is_array($Parameters)) {
         $MinScore = $Parameters;
      } else {
         $MinScore = GetValue('Score', $Parameters, NULL);
      }
      
      if (!is_integer($MinScore))
         $MinScore = FALSE;
      
      // Check cache
      $SelectorScoreCacheKey = "modules.promotedcontent.score.{$Score}";
      $Content = Gdn::Cache()->Get($SelectorScoreCacheKey);
      
      if ($Content == Gdn_Cache::CACHEOP_FAILURE) {
         
         // Get matching Discussions
         $Discussions = Gdn::SQL()->Select('d.*')
            ->From('Discussion d')
            ->Where('Score >', $MinScore)
            ->OrderBy('Score', 'DESC')
            ->Limit($this->Limit);
         if ($MinScore !== FALSE) $Discussions->Where('Score >', $MinScore);
         $Discussions = $Discussions->Get()->Result(DATASET_TYPE_ARRAY);

         // Get matching Comments
         $Comments = Gdn::SQL()->Select('c.*')
            ->From('Comment c')
            ->OrderBy('Score', 'DESC')
            ->Limit($this->Limit);
         if ($MinScore !== FALSE) $Comments->Where('Score >', $MinScore);
         $Comments = $Comments->Get()->Result(DATASET_TYPE_ARRAY);
         
         $this->JoinCategory($Comments);
         
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
    * Attach CategoryID to Comments
    * 
    * @param array $Comments
    */
   protected function JoinCategory(&$Comments) {
      $DiscussionIDs = array();
      
      foreach ($Comments as &$Comment)
         $DiscussionIDs[$Comment['DiscussionID']] = TRUE;
      $DiscussionIDs = array_keys($DiscussionIDs);
      
      $Discussions = Gdn::SQL()->Select('d.*')
         ->From('Discussion d')
         ->WhereIn('DiscussionID', $DiscussionIDs)
         ->Get()->Result(DATASET_TYPE_ARRAY);
      
      $DiscussionsByID = array();
      foreach ($Discussions as $Discussion)
         $DiscussionsByID[$Discussion['DiscussionID']] = $Discussion;
      unset($$Discussions);
      
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
            $Interleaved[$ItemField] = array_merge($Item, array('ItemType' => $SectionType));
            
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
         $ContentType = GetValue('ItemType', $ContentItem);
         
         $Replacement = array();
         $Fields = array('DiscussionID', 'CategoryID', 'DateInserted', 'DateUpdated', 'InsertUserID', 'Body', 'Format', 'ItemType');
         
         switch (strtolower($ContentType)) {
            case 'comment':
               $Fields = array_merge($Fields, array('CommentID'));
               
               // Comment specific
               $Replacement['Name'] = GetValueR('Discussion.Name', $ContentItem);
               break;
            
            case 'discussion':
               $Fields = array_merge($Fields, array('Name'));
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
      if (!is_array($Content)) return;
      $Content = array_filter($Content, array($this, 'SecurityFilter'));
   }
   
   protected function SecurityFilter($ContentItem) {
      $CategoryID = GetValue('CategoryID', $ContentItem, NULL);
      if (is_null($CategoryID) || $CategoryID === FALSE)
         return FALSE;

      $Category = CategoryModel::Categories($CategoryID);
      $CanView = GetValue('PermsDiscussionsView', $Category);
      if (!$CanView)
         return FALSE;
      
      return TRUE;
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
   
   public function AssetTarget() {
      return 'Content';
   }

   public function ToString() {
      if ($this->Data('Content', NULL) == NULL)
         $this->GetData();
      
      return parent::ToString();
   }
}