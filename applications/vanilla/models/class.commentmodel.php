<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/
/**
 * Comment Model
 *
 * @package Vanilla
 */
 
/**
 * Manages discussion comments.
 *
 * @since 2.0.0
 * @package Vanilla
 */
class CommentModel extends VanillaModel {
   /**
    * List of fields to order results by.
    * 
    * @var array
    * @access protected
    * @since 2.0.0
    */
   protected $_OrderBy = array(array('c.DateInserted', ''));
   
   protected $_Where = array();
   
   /**
    * Class constructor. Defines the related database table name.
    * 
    * @since 2.0.0
    * @access public
    */
   public function __construct() {
      parent::__construct('Comment');
      $this->FireEvent('AfterConstruct');
   }
   
   public function CachePageWhere($Result, $PageWhere, $DiscussionID, $Page, $Limit = NULL) {
      if (!Gdn::Cache()->ActiveEnabled() || !empty($this->_Where) || $this->_OrderBy[0][0] != 'c.DateInserted' || $this->_OrderBy[0][1] == 'desc')
         return;
      
      if (count($Result) == 0)
         return;
      
      $ConfigLimit = C('Vanilla.Comments.PerPage', 30);
      
      if (!$Limit)
         $Limit = $ConfigLimit;
      
      if ($Limit != $ConfigLimit) {
         return;
      }
      
      if (is_array($PageWhere))
         $Curr = array_values($PageWhere);
      else
         $Curr = FALSE;
      
      $New = array(GetValueR('0.DateInserted', $Result));
      
      if (count($Result) >= $Limit)
         $New[] = GetValueR(($Limit - 1).'.DateInserted', $Result);
      
      if ($Curr != $New) {
         Trace('CommentModel->CachePageWhere()');
         
         $CacheKey = "Comment.Page.$Limit.$DiscussionID.$Page";
         Gdn::Cache()->Store($CacheKey, $New, array(Gdn_Cache::FEATURE_EXPIRY => 86400));
         
         Trace($New, $CacheKey);
//         Gdn::Controller()->SetData('_PageCacheStore', array($CacheKey, $New));
      }
   }
   
   /**
    * Select the data for a single comment.
    * 
    * @since 2.0.0
    * @access public
    * 
    * @param bool $FireEvent Kludge to fix VanillaCommentReplies plugin.
    */
   public function CommentQuery($FireEvent = TRUE, $Join = TRUE) {
      $this->SQL->Select('c.*')
//         ->Select('du.Name', '', 'DeleteName')
//         ->SelectCase('c.DeleteUserID', array('null' => '0', '' => '1'), 'Deleted')
//         ->Join('User du', 'c.DeleteUserID = du.UserID', 'left');
         ->From('Comment c');
      
      if ($Join) {
         $this->SQL
            ->Select('iu.Name', '', 'InsertName')
            ->Select('iu.Photo', '', 'InsertPhoto')
            ->Select('iu.Email', '', 'InsertEmail')
            ->Join('User iu', 'c.InsertUserID = iu.UserID', 'left')
         
            ->Select('uu.Name', '', 'UpdateName')
            ->Select('uu.Photo', '', 'UpdatePhoto')
            ->Select('uu.Email', '', 'UpdateEmail')
            ->Join('User uu', 'c.UpdateUserID = uu.UserID', 'left');
      }
      
      if($FireEvent)
         $this->FireEvent('AfterCommentQuery');
   }
   
   /**
    * Get comments for a discussion.
    * 
    * @since 2.0.0
    * @access public
    * 
    * @param int $DiscussionID Which discussion to get comment from.
    * @param int $Limit Max number to get.
    * @param int $Offset Number to skip.
    * @return object SQL results.
    */
   public function Get($DiscussionID, $Limit, $Offset = 0) {
      $this->CommentQuery(TRUE, FALSE);
      $this->EventArguments['DiscussionID'] =& $DiscussionID;
      $this->EventArguments['Limit'] =& $Limit;
      $this->EventArguments['Offset'] =& $Offset;
      $this->FireEvent('BeforeGet');
      
      $Page = PageNumber($Offset, $Limit);
      $PageWhere = $this->PageWhere($DiscussionID, $Page, $Limit);
      
      if ($PageWhere) {
         $this->SQL
            ->Where('c.DiscussionID', $DiscussionID);
         
         $this->SQL->Where($PageWhere)->Limit($Limit + 10);
         $this->OrderBy($this->SQL);
      } else {
         // Do an inner-query to force late-loading of comments.
         $Sql2 = clone $this->SQL;
         $Sql2->Reset();
         $Sql2->Select('CommentID')
            ->From('Comment c')
            ->Where('c.DiscussionID', $DiscussionID, TRUE, FALSE)
            ->Limit($Limit, $Offset);
         $this->OrderBy($Sql2);
         $Select = $Sql2->GetSelect();
         
         $Px = $this->SQL->Database->DatabasePrefix;
         $this->SQL->Database->DatabasePrefix = '';
         
         $this->SQL->Join("($Select) c2", "c.CommentID = c2.CommentID");
         $this->SQL->Database->DatabasePrefix = $Px;
         
//         $this->SQL->Limit($Limit, $Offset);
      }
      
      $this->Where($this->SQL);

      $Result = $this->SQL->Get();
      
      Gdn::UserModel()->JoinUsers($Result, array('InsertUserID', 'UpdateUserID'));
      
      $this->SetCalculatedFields($Result);
      
      $this->EventArguments['Comments'] =& $Result;
      $this->FireEvent('AfterGet');
      
      $this->CachePageWhere($Result->Result(), $PageWhere, $DiscussionID, $Page, $Limit);
      
      return $Result;
   }
  
   /**
    * Get comments for a user.
    * 
    * @since 2.0.17
    * @access public
    * 
    * @param int $UserID Which user to get comments for.
    * @param int $Limit Max number to get.
    * @param int $Offset Number to skip.
    * @return object SQL results.
    */
   public function GetByUser($UserID, $Limit, $Offset = 0) {
      // Get category permissions
      $Perms = DiscussionModel::CategoryPermissions();
      
      // Build main query
      $this->CommentQuery(TRUE, FALSE);
      $this->FireEvent('BeforeGet');
      $this->SQL
			->Select('d.Name', '', 'DiscussionName')
			->Join('Discussion d', 'c.DiscussionID = d.DiscussionID')
         ->Where('c.InsertUserID', $UserID)
			->OrderBy('c.CommentID', 'desc')
         ->Limit($Limit, $Offset);
      
      // Verify permissions (restricting by category if necessary)
      if($Perms !== TRUE) {
         $this->SQL
            ->Join('Category ca', 'd.CategoryID = ca.CategoryID', 'left')
            ->WhereIn('d.CategoryID', $Perms);
      }
      
      //$this->OrderBy($this->SQL);

      $Data = $this->SQL->Get();
      Gdn::UserModel()->JoinUsers($Data, array('InsertUserID', 'UpdateUserID'));
      
      return $Data;
      
   }
   
   /**
    * 
    * Get comments for a user. This is an optimized version of CommentModel->GetByUser().
    * 
    * @since 2.1
    * @access public
    * 
    * @param int $UserID Which user to get comments for.
    * @param int $Limit Max number to get.
    * @param int $Offset Number to skip.
    * @param int $LastCommentID A hint for quicker paging.
    * @return Gdn_DataSet SQL results.
    */
   public function GetByUser2($UserID, $Limit, $Offset, $LastCommentID = FALSE) {
      $Perms = DiscussionModel::CategoryPermissions();
      
      if (is_array($Perms) && empty($Perms)) {
         return new Gdn_DataSet(array());
      }
      
      // The point of this query is to select from one comment table, but filter and sort on another.
      // This puts the paging into an index scan rather than a table scan.
      $this->SQL
         ->Select('c2.*')
         ->Select('d.Name', '', 'DiscussionName')
         ->Select('d.CategoryID')
         ->From('Comment c')
         ->Join('Comment c2', 'c.CommentID = c2.CommentID')
         ->Join('Discussion d', 'c2.DiscussionID = d.DiscussionID')
         ->Where('c.InsertUserID', $UserID)
         ->OrderBy('c.CommentID', 'desc');
      
      if ($LastCommentID) {
         // The last comment id from the last page was given and can be used as a hint to speed up the query.
         $this->SQL
            ->Where('c.CommentID <', $LastCommentID)
            ->Limit($Limit);
      } else {
         $this->SQL->Limit($Limit, $Offset);
      }
      
      $Data = $this->SQL->Get();
      
      
      $Result =& $Data->Result();
      $this->LastCommentCount = $Data->NumRows();
      if (count($Result) > 0) {
         $this->LastCommentID = $Result[count($Result) - 1]->CommentID;
      } else
         $this->LastCommentID = NULL;
      
      // Now that we have th comments we can filter out the ones we don't have permission to.
      if ($Perms !== TRUE) {
         $Remove = array();
         
         foreach ($Data->Result() as $Index => $Row) {
            if (!in_array($Row->CategoryID, $Perms))
               $Remove[] = $Index;
         }
      
         if (count($Remove) > 0) {

            foreach ($Remove as $Index) {
               unset($Result[$Index]);
            }

            $Result = array_values($Result);
         }
      }

      Gdn::UserModel()->JoinUsers($Data, array('InsertUserID', 'UpdateUserID'));
      
      return $Data;
   }
  
   /** 
    * Set the order of the comments or return current order. 
    * 
    * Getter/setter for $this->_OrderBy.
    * 
    * @since 2.0.0
    * @access public
    * 
    * @param mixed Field name(s) to order results by. May be a string or array of strings.
    * @return array $this->_OrderBy (optionally).
    */
   public function OrderBy($Value = NULL) {
      if ($Value === NULL)
         return $this->_OrderBy;

      if (is_string($Value))
         $Value = array($Value);

      if (is_array($Value)) {
         // Set the order of this object.
         $OrderBy = array();

         foreach($Value as $Part) {
            if (StringEndsWith($Part, ' desc', TRUE))
               $OrderBy[] = array(substr($Part, 0, -5), 'desc');
            elseif (StringEndsWith($Part, ' asc', TRUE))
               $OrderBy[] = array(substr($Part, 0, -4), 'asc');
            else
               $OrderBy[] = array($Part, 'asc');
         }
         $this->_OrderBy = $OrderBy;
      } elseif (is_a($Value, 'Gdn_SQLDriver')) {
         // Set the order of the given sql.
         foreach ($this->_OrderBy as $Parts) {
            $Value->OrderBy($Parts[0], $Parts[1]);
         }
      }
   }
   
   public function PageWhere($DiscussionID, $Page, $Limit) {
      if (!Gdn::Cache()->ActiveEnabled() || !empty($this->_Where) || $this->_OrderBy[0][0] != 'c.DateInserted' || $this->_OrderBy[0][1] == 'desc')
         return FALSE;
      
      if ($Limit != C('Vanilla.Comments.PerPage', 30)) {
         return FALSE;
      }
      
      $CacheKey = "Comment.Page.$Limit.$DiscussionID.$Page";
      $Value = Gdn::Cache()->Get($CacheKey);
      Trace('CommentModel->PageWhere()');
      Trace($Value, $CacheKey);
//      Gdn::Controller()->SetData('_PageCache', array($CacheKey, $Value));
      if ($Value === FALSE) {
         return FALSE;
      } elseif (is_array($Value)) {
         $Result = array('DateInserted >=' => $Value[0]);
         if (isset($Value[1])) {
            $Result['DateInserted <='] = $Value[1];
         }
         return $Result;
      }
      return FALSE;
   }
	
	/**
	 * Sets the UserComment Score value. 
	 * 
    * @since 2.0.0
    * @access public
    * 
    * @param int $CommentID Unique ID of comment we're setting the score for.
    * @param int $UserID Unique ID of user scoring the comment.
    * @param int $Score Score being assigned to the comment.
	 * @return int New total score for the comment.
	 */
	public function SetUserScore($CommentID, $UserID, $Score) {
		// Insert or update the UserComment row
		$this->SQL->Replace(
			'UserComment',
			array('Score' => $Score),
			array('CommentID' => $CommentID, 'UserID' => $UserID)
		);
		
		// Get the total new score
		$TotalScore = $this->SQL->Select('Score', 'sum', 'TotalScore')
			->From('UserComment')
			->Where('CommentID', $CommentID)
			->Get()
			->FirstRow()
			->TotalScore;
		
		// Update the comment's cached version
		$this->SQL->Update('Comment')
			->Set('Score', $TotalScore)
			->Where('CommentID', $CommentID)
			->Put();
			
		return $TotalScore;
	}
   
   /**
	 * Gets the UserComment Score value for the specified user.
	 * 
    * @since 2.0.0
    * @access public
    * 
    * @param int $CommentID Unique ID of comment we're getting the score for.
    * @param int $UserID Unique ID of user who scored the comment.
	 * @return int Current score for the comment.
	 */
	public function GetUserScore($CommentID, $UserID) {
		$Data = $this->SQL->Select('Score')
			->From('UserComment')
			->Where('CommentID', $CommentID)
			->Where('UserID', $UserID)
			->Get()
			->FirstRow();
		
		return $Data ? $Data->Score : 0;
	}
   
   /**
	 * Record the user's watch data.
	 * 
    * @since 2.0.0
    * @access public
    * 
    * @param object $Discussion Discussion being watched.
    * @param int $Limit Max number to get.
    * @param int $Offset Number to skip.
    * @param int $TotalComments Total in entire discussion (hard limit).
	 */
   public function SetWatch($Discussion, $Limit, $Offset, $TotalComments) {
      
      $NewComments = FALSE;
      
      $Session = Gdn::Session();
      if ($Session->UserID > 0) {
         
         // Max comments we could have seen
         $CountWatch = $Limit * ($Offset + 1);
         if ($CountWatch > $TotalComments)
            $CountWatch = $TotalComments;
         
         // This dicussion looks familiar...
         if (is_numeric($Discussion->CountCommentWatch)) {
            
            if (isset($Discussion->DateLastViewed))
               $NewComments |= Gdn_Format::ToTimestamp($Discussion->DateLastComment) > Gdn_Format::ToTimestamp($Discussion->DateLastViewed);
            
            if ($TotalComments > $Discussion->CountCommentWatch)
               $NewComments |= TRUE;

            // Update the watch data.
				if ($NewComments) {
               
					// Only update the watch if there are new comments.
					$this->SQL->Put(
						'UserDiscussion',
						array(
							'CountComments' => $CountWatch,
                     'DateLastViewed' => Gdn_Format::ToDateTime()
						),
						array(
							'UserID' => $Session->UserID,
							'DiscussionID' => $Discussion->DiscussionID
						)
					);
				}
            
         } else {
				// Make sure the discussion isn't archived.
				$ArchiveDate = C('Vanilla.Archive.Date', FALSE);
				if (!$ArchiveDate || (Gdn_Format::ToTimestamp($Discussion->DateLastComment) > Gdn_Format::ToTimestamp($ArchiveDate))) {
               
               $NewComments = TRUE;
               
					// Insert watch data.
               $this->SQL->Options('Ignore', TRUE);
					$this->SQL->Insert(
						'UserDiscussion',
						array(
							'UserID' => $Session->UserID,
							'DiscussionID' => $Discussion->DiscussionID,
							'CountComments' => $CountWatch,
                     'DateLastViewed' => Gdn_Format::ToDateTime()
						)
					);
				}
			}
         
         /**
          * Fuzzy way of trying to automatically mark a cateogyr read again
          * if the user reads all the comments on the first few pages.
          */
         
         // If this discussion is in a category that has been marked read, 
         // check if reading this thread causes it to be completely read again
         $CategoryID = GetValue('CategoryID', $Discussion);
         if ($CategoryID) {
            
            $Category = CategoryModel::Categories($CategoryID);
            if ($Category) {
               
               $DateMarkedRead = GetValue('DateMarkedRead', $Category);
               if ($DateMarkedRead) {
                  
                  // Fuzzy way of looking back about 2 pages into the past
                  $LookBackCount = C('Vanilla.Discussions.PerPage', 50) * 2;
                  
                  // Find all discussions with content from after DateMarkedRead
                  $DiscussionModel = new DiscussionModel();
                  $Discussions = $DiscussionModel->Get(0, 101, array(
                     'CategoryID'         => $CategoryID,
                     'DateLastComment>'   => $DateMarkedRead
                  ));
                  unset($DiscussionModel);
                  
                  // Abort if we get back as many as we asked for, meaning a 
                  // lot has happened.
                  $NumDiscussions = $Discussions->NumRows();
                  if ($NumDiscussions <= $LookBackCount) {
                  
                     // Loop over these and see if any are still unread
                     $MarkAsRead = TRUE;
                     while ($Discussion = $Discussions->NextRow(DATASET_TYPE_ARRAY)) {
                        if ($Discussion['Read']) continue;
                        $MarkAsRead = FALSE;
                        break;
                     }

                     // Mark this category read if all the new content is read
                     if ($MarkAsRead) {
                        $CategoryModel = new CategoryModel();
                        $CategoryModel->SaveUserTree($CategoryID, array('DateMarkedRead' => Gdn_Format::ToDateTime()));
                        unset($CategoryModel);
                     }
                     
                  }
               }
            }
         }
         
		}
   }

   /**
	 * Count total comments in a discussion specified by ID.
	 *
	 * Events: BeforeGetCount
	 * 
    * @since 2.0.0
    * @access public
    * 
    * @param int $DiscussionID Unique ID of discussion we're counting comments from.
    * @return object SQL result.
	 */
   public function GetCount($DiscussionID) {
      $this->FireEvent('BeforeGetCount');
      
      if (!empty($this->_Where)) {
         return FALSE;
      }
      
      return $this->SQL->Select('CommentID', 'count', 'CountComments')
         ->From('Comment')
         ->Where('DiscussionID', $DiscussionID)
         ->Get()
         ->FirstRow()
         ->CountComments;
   }
   
   /**
	 * Count total comments in a discussion specified by $Where conditions.
	 * 
    * @since 2.0.0
    * @access public
    * 
    * @param array $Where Conditions
    * @return object SQL result.
	 */
   public function GetCountWhere($Where = FALSE) {
      if (is_array($Where))
         $this->SQL->Where($Where);
         
      return $this->SQL->Select('CommentID', 'count', 'CountComments')
         ->From('Comment')
         ->Get()
         ->FirstRow()
         ->CountComments;
   }
   
   /**
	 * Get single comment by ID. Allows you to pick data format of return value.
	 * 
    * @since 2.0.0
    * @access public
    * 
    * @param int $CommentID Unique ID of the comment.
    * @param string $ResultType Format to return comment in.
    * @return mixed SQL result in format specified by $ResultType.
	 */
   public function GetID($CommentID, $ResultType = DATASET_TYPE_OBJECT) {
      $this->CommentQuery(FALSE); // FALSE supresses FireEvent
      $Comment = $this->SQL
         ->Where('c.CommentID', $CommentID)
         ->Get()
         ->FirstRow($ResultType);
      
      $this->Calculate($Comment);
      return $Comment;
   }
   
   /**
	 * Get single comment by ID as SQL result data.
	 * 
    * @since 2.0.0
    * @access public
    * 
    * @param int $CommentID Unique ID of the comment.
    * @return object SQL result.
	 */
   public function GetIDData($CommentID) {
      $this->FireEvent('BeforeGetIDData');
      $this->CommentQuery(FALSE); // FALSE supresses FireEvent
      return $this->SQL
         ->Where('c.CommentID', $CommentID)
         ->Get();
   }
   
   /**
	 * Get comments in a discussion since the specified one.
	 *
	 * Events: BeforeGetNew
	 * 
    * @since 2.0.0
    * @access public
    * 
    * @param int $DiscussionID Unique ID of the discusion.
    * @param int $LastCommentID Unique ID of the comment.
    * @return object SQL result.
	 */
   public function GetNew($DiscussionID, $LastCommentID) {
      $this->CommentQuery();
      $this->FireEvent('BeforeGetNew');
      $this->OrderBy($this->SQL);
      $Comments = $this->SQL
         ->Where('c.DiscussionID', $DiscussionID)
         ->Where('c.CommentID >', $LastCommentID)
         ->Get();
      
      $this->SetCalculatedFields($Comments);
      return $Comments;
   }
   
   /**
    * Gets the offset of the specified comment in its related discussion.
    * 
    * Events: BeforeGetOffset
    * 
    * @since 2.0.0
    * @access public
    *
    * @param mixed $Comment Unique ID or or a comment object for which the offset is being defined.
    * @return object SQL result.
    */
   public function GetOffset($Comment) {
      $this->FireEvent('BeforeGetOffset');
      
      if (is_numeric($Comment)) {
         $Comment = $this->GetID($Comment);
      }

      $this->SQL
         ->Select('c.CommentID', 'count', 'CountComments')
         ->From('Comment c')
         ->Where('c.DiscussionID', GetValue('DiscussionID', $Comment));

      $this->SQL->BeginWhereGroup();

      // Figure out the where clause based on the sort.
      foreach ($this->_OrderBy as $Part) {
         //$Op = count($this->_OrderBy) == 1 || isset($PrevWhere) ? '=' : '';
         list($Expr, $Value) = $this->_WhereFromOrderBy($Part, $Comment, '');

         if (!isset($PrevWhere)) {
            $this->SQL->Where($Expr, $Value);
         } else {
            $this->SQL->BeginWhereGroup();
            $this->SQL->OrWhere($PrevWhere[0], $PrevWhere[1]);
            $this->SQL->Where($Expr, $Value);
            $this->SQL->EndWhereGroup();
         }

         $PrevWhere = $this->_WhereFromOrderBy($Part, $Comment, '==');
      }

      $this->SQL->EndWhereGroup();

      return $this->SQL
         ->Get()
         ->FirstRow()
         ->CountComments;
   }

   public function GetUnreadOffset($DiscussionID, $UserID = NULL) {
      if ($UserID == NULL) {
         $UserID = Gdn::Session()->UserID;
      }
      if ($UserID == 0)
         return 0;

      // See of the user has read the discussion.
      $UserDiscussion = $this->SQL->GetWhere('UserDiscussion', array('DiscussionID' => $DiscussionID, 'UserID' => $UserID))->FirstRow(DATASET_TYPE_ARRAY);
      if (empty($UserDiscussion))
         return 0;

      return $UserDiscussion['CountComments'];
   }
   
   /**
    * Builds Where statements for GetOffset method.
    * 
    * @since 2.0.0
    * @access protected
    * @see CommentModel::GetOffset()
    *
    * @param array $Part Value from $this->_OrderBy.
    * @param object $Comment
    * @param string $Op Comparison operator.
    * @return array Expression and value.
    */
   protected function _WhereFromOrderBy($Part, $Comment, $Op = '') {
      if (!$Op || $Op == '=')
         $Op = ($Part[1] == 'desc' ? '>' : '<').$Op;
      elseif ($Op == '==')
         $Op = '=';
      
      $Expr = $Part[0].' '.$Op;
      if (preg_match('/c\.(\w*\b)/', $Part[0], $Matches))
         $Field = $Matches[1];
      else
         $Field = $Part[0];
      $Value = GetValue($Field, $Comment);
      if (!$Value)
         $Value = 0;

      return array($Expr, $Value);
   }
   
   /**
    * Insert or update core data about the comment.
    * 
    * Events: BeforeSaveComment, AfterSaveComment.
    * 
    * @since 2.0.0
    * @access public
    *
    * @param array $FormPostValues Data from the form model.
    * @return int $CommentID
    */
   public function Save($FormPostValues) {
      $Session = Gdn::Session();
      
      // Define the primary key in this model's table.
      $this->DefineSchema();
      
      // Add & apply any extra validation rules:      
      $this->Validation->ApplyRule('Body', 'Required');
      $this->Validation->AddRule('MeAction', 'function:ValidateMeAction');
      $this->Validation->ApplyRule('Body', 'MeAction');
      $MaxCommentLength = Gdn::Config('Vanilla.Comment.MaxLength');
      if (is_numeric($MaxCommentLength) && $MaxCommentLength > 0) {
         $this->Validation->SetSchemaProperty('Body', 'Length', $MaxCommentLength);
         $this->Validation->ApplyRule('Body', 'Length');
      }
      
      // Validate $CommentID and whether this is an insert
      $CommentID = ArrayValue('CommentID', $FormPostValues);
      $CommentID = is_numeric($CommentID) && $CommentID > 0 ? $CommentID : FALSE;
      $Insert = $CommentID === FALSE;
      if ($Insert)
         $this->AddInsertFields($FormPostValues);
      else
         $this->AddUpdateFields($FormPostValues);
      
      // Prep and fire event
      $this->EventArguments['FormPostValues'] = &$FormPostValues;
      $this->EventArguments['CommentID'] = $CommentID;
      $this->FireEvent('BeforeSaveComment');
      
      // Validate the form posted values
      if ($this->Validate($FormPostValues, $Insert)) {
         // If the post is new and it validates, check for spam
         if (!$Insert || !$this->CheckForSpam('Comment')) {
            $Fields = $this->Validation->SchemaValidationFields();
            $Fields = RemoveKeyFromArray($Fields, $this->PrimaryKey);
            
            if ($Insert === FALSE) {
               // Log the save.
               LogModel::LogChange('Edit', 'Comment', array_merge($Fields, array('CommentID' => $CommentID)));
               // Save the new value.
               $this->SQL->Put($this->Name, $Fields, array('CommentID' => $CommentID));
            } else {
               // Make sure that the comments get formatted in the method defined by Garden.
               if (!GetValue('Format', $Fields) || C('Garden.ForceInputFormatter'))
                  $Fields['Format'] = Gdn::Config('Garden.InputFormatter', '');

               // Check for spam
               $Spam = SpamModel::IsSpam('Comment', $Fields);
               if ($Spam)
                  return SPAM;
               
               // Check for approval
               $ApprovalRequired = CheckRestriction('Vanilla.Approval.Require');
               if ($ApprovalRequired && !GetValue('Verified', Gdn::Session()->User)) {
                  $DiscussionModel = new DiscussionModel();
                  $Discussion = $DiscussionModel->GetID(GetValue('DiscussionID', $Fields));
                  $Fields['CategoryID'] = GetValue('CategoryID', $Discussion);
               	LogModel::Insert('Pending', 'Comment', $Fields);
               	return UNAPPROVED;
               }

               // Create comment
               $CommentID = $this->SQL->Insert($this->Name, $Fields);
	            $this->EventArguments['CommentID'] = $CommentID;
	               
	            // IsNewDiscussion is passed when the first comment for new discussions are created.
	            $this->EventArguments['IsNewDiscussion'] = GetValue('IsNewDiscussion', $FormPostValues);
               $this->FireEvent('AfterSaveComment');
            }
         }
      }
      
      // Update discussion's comment count
      $DiscussionID = GetValue('DiscussionID', $FormPostValues);
      $this->UpdateCommentCount($DiscussionID);

      return $CommentID;
   }

   /**
    * Insert or update meta data about the comment.
    * 
    * Updates unread comment totals, bookmarks, and activity. Sends notifications.
    * 
    * @since 2.0.0
    * @access public
    *
    * @param array $CommentID Unique ID for this comment.
    * @param int $Insert Used as a boolean for whether this is a new comment.
    * @param bool $CheckExisting Not used.
    * @param bool $IncUser Whether or not to just increment the user's comment count rather than recalculate it.
    */
   public function Save2($CommentID, $Insert, $CheckExisting = TRUE, $IncUser = FALSE) {
      $Session = Gdn::Session();
      $UserModel = Gdn::UserModel();
      
      // Load comment data
      $Fields = $this->GetID($CommentID, DATASET_TYPE_ARRAY);
      
      // Clear any session stashes related to this discussion
      $DiscussionModel = new DiscussionModel();
      $DiscussionID = GetValue('DiscussionID', $Fields);
      $Discussion = $DiscussionModel->GetID($DiscussionID);
      $Session->Stash('CommentForForeignID_'.GetValue('ForeignID', $Discussion));

      // Make a quick check so that only the user making the comment can make the notification.
      // This check may be used in the future so should not be depended on later in the method.
      if ($Fields['InsertUserID'] != $Session->UserID)
         return;

      // Update the discussion author's CountUnreadDiscussions (ie.
      // the number of discussions created by the user that s/he has
      // unread messages in) if this comment was not added by the
      // discussion author.
      $this->UpdateUser($Session->UserID, $IncUser && $Insert);

      if ($Insert) {
			// UPDATE COUNT AND LAST COMMENT ON CATEGORY TABLE
			if ($Discussion->CategoryID > 0) {
				$CountComments = $this->SQL
					->Select('CountComments', 'sum', 'CountComments')
					->From('Discussion')
					->Where('CategoryID', $Discussion->CategoryID)
					->Get()
					->FirstRow()
					->CountComments;
            
            $CategoryModel = new CategoryModel();
            
            $CategoryModel->SetField($Discussion->CategoryID, array(
               'LastDiscussionID'   => $DiscussionID,
               'LastCommentID'      => $CommentID,
               'CountComments'      => $CountComments,
               'LastDateInserted'   => $Fields['DateInserted']
            ));
            
            // Update the cache.
            if ($DiscussionID && Gdn::Cache()->ActiveEnabled()) {
               $CategoryCache = array(
                   'LastTitle'         => $Discussion->Name, // kluge so JoinUsers doesn't wipe this out.
                   'LastUserID'        => $Fields['InsertUserID'],
                   'LastUrl'           => DiscussionUrl($Discussion).'#latest'
               );
               CategoryModel::SetCache($Discussion->CategoryID, $CategoryCache);
            }
			}
			
			// Prepare the notification queue.
         $ActivityModel = new ActivityModel();
         $HeadlineFormat = T('HeadlineFormat.Comment', '{ActivityUserID,user} commented on <a href="{Url,html}">{Data.Name,text}</a>');
         $Category = CategoryModel::Categories($Discussion->CategoryID);
         $Activity = array(
            'ActivityType' => 'Comment',
            'ActivityUserID' => $Fields['InsertUserID'],
            'HeadlineFormat' => $HeadlineFormat,
            'RecordType' => 'Comment',
            'RecordID' => $CommentID,
            'Route' => "/discussion/comment/$CommentID#Comment_$CommentID",
            'Data' => array(
               'Name' => $Discussion->Name,
               'Category' => GetValue('Name', $Category)
            )
         );
         
         // Allow simple fulltext notifications
         if (C('Vanilla.Activity.ShowCommentBody', FALSE)) {
            $Activity['Story'] = GetValue('Body', $Fields);
            $Activity['Format'] = GetValue('Format', $Fields);
         }
         
         // Notify users who have bookmarked the discussion.
         $BookmarkData = $DiscussionModel->GetBookmarkUsers($DiscussionID);
         foreach ($BookmarkData->Result() as $Bookmark) {
            // Check user can still see the discussion.
            if (!$UserModel->GetCategoryViewPermission($Bookmark->UserID, $Discussion->CategoryID))
               continue;
            
            $Activity['NotifyUserID'] = $Bookmark->UserID;
            $ActivityModel->Queue($Activity, 'BookmarkComment', array('CheckRecord' => TRUE));
         }

         // Check user can still see the discussion.
         if (($Discussion != FALSE) && ($UserModel->GetCategoryViewPermission(GetValue('InsertUserID', $Discussion), $Discussion->CategoryID))) {
            // Record user-comment activity.
            $Activity['NotifyUserID'] = GetValue('InsertUserID', $Discussion);
            $ActivityModel->Queue($Activity, 'DiscussionComment');
            
	    // Record advanced notifications 
            $this->RecordAdvancedNotications($ActivityModel, $Activity, $Discussion);
         }
         
         // Notify any users who were mentioned in the comment.
         $Usernames = GetMentions($Fields['Body']);
         $NotifiedUsers = array();
         foreach ($Usernames as $i => $Username) {
            $User = $UserModel->GetByUsername($Username);
            if (!$User) {
               unset($Usernames[$i]);
               continue;
            }
            
            // Check user can still see the discussion.
            if (!$UserModel->GetCategoryViewPermission($User->UserID, $Discussion->CategoryID))
               continue;
            
            $HeadlineFormatBak = $Activity['HeadlineFormat'];
            $Activity['HeadlineFormat'] = T('HeadlineFormat.Mention', '{ActivityUserID,user} mentioned you in <a href="{Url,html}">{Data.Name,text}</a>');
            
            $Activity['NotifyUserID'] = $User->UserID;
            $ActivityModel->Queue($Activity, 'Mention');
            $Activity['HeadlineFormat'] = $HeadlineFormatBak;
         }

         // Throw an event for users to add their own events.
         $this->EventArguments['Comment'] = $Fields;
         $this->EventArguments['Discussion'] = $Discussion;
         $this->EventArguments['NotifiedUsers'] = array_keys(ActivityModel::$Queue);
         $this->EventArguments['MentionedUsers'] = $Usernames;
         $this->EventArguments['ActivityModel'] = $ActivityModel;
         $this->FireEvent('BeforeNotification');
				
			// Send all notifications.
			$ActivityModel->SaveQueue();
      }
   }
   
   /**
    * Record advanced notifications for users.
    * 
    * @param ActivityModel $ActivityModel
    * @param array $Activity
    * @param array $Discussion
    * @param array $NotifiedUsers 
    */
   public function RecordAdvancedNotications($ActivityModel, $Activity, $Discussion) {
      if (is_numeric($Discussion)) {
         $Discussion = $this->GetID($Discussion);
      }
      
      $CategoryID = GetValue('CategoryID', $Discussion);
      
      // Figure out the category that governs this notification preference.
      $i = 0;
      $Category = CategoryModel::Categories($CategoryID);
      if (!$Category)
         return;
      
      while ($Category['Depth'] > 2 && $i < 20) {
         if (!$Category || $Category['Archived'])
            return;
         $i++;
         $Category = CategoryModel::Categories($Category['ParentCategoryID']);
      } 

      // Grab all of the users that need to be notified.
      $Data = $this->SQL
         ->WhereIn('Name', array('Preferences.Email.NewComment.'.$Category['CategoryID'], 'Preferences.Popup.NewComment.'.$Category['CategoryID']))
         ->Get('UserMeta')->ResultArray();
      
      $NotifyUsers = array();
      foreach ($Data as $Row) {
         if (!$Row['Value'])
            continue;
         
         $UserID = $Row['UserID'];
         // Check user can still see the discussion.
         if (!Gdn::UserModel()->GetCategoryViewPermission($UserID, $Category['CategoryID']))
            continue;

         $Name = $Row['Name'];
         if (strpos($Name, '.Email.') !== FALSE) {
            $NotifyUsers[$UserID]['Emailed'] = ActivityModel::SENT_PENDING;
         } elseif (strpos($Name, '.Popup.') !== FALSE) {
            $NotifyUsers[$UserID]['Notified'] = ActivityModel::SENT_PENDING;
         }
      }
      
      foreach ($NotifyUsers as $UserID => $Prefs) {
         $Activity['NotifyUserID'] = $UserID;
         $Activity['Emailed'] = GetValue('Emailed', $Prefs, FALSE);
         $Activity['Notified'] = GetValue('Notified', $Prefs, FALSE);
         $ActivityModel->Queue($Activity);
      }
   }
   
   public function RemovePageCache($DiscussionID, $From = 1) {
      if (!Gdn::Cache()->ActiveEnabled())
         return;
      
      $CountComments = $this->SQL->GetWhere('Discussion', array('DiscussionID' => $DiscussionID))->Value('CountComments');
      $Limit = C('Vanilla.Comments.PerPage', 30);
      $PageCount = PageNumber($CountComments, $Limit) + 1;
      
      for ($Page = $From; $Page <= $PageCount; $Page++) {
         $CacheKey = "Comment.Page.$Limit.$DiscussionID.$Page";
         Gdn::Cache()->Remove($CacheKey);
      }
   }
   
   /**
    * Updates the CountComments value on the discussion based on the CommentID being saved. 
    *
    * Events: BeforeUpdateCommentCount.
    * 
    * @since 2.0.0
    * @access public
    *
    * @param int $DiscussionID Unique ID of the discussion we are updating.
    */
   public function UpdateCommentCount($Discussion) {
      // Get the discussion.
      if (is_numeric($Discussion))
         $Discussion = $this->SQL->GetWhere('Discussion', array('DiscussionID' => $Discussion))->FirstRow(DATASET_TYPE_ARRAY);
      $DiscussionID = $Discussion['DiscussionID'];

      $this->FireEvent('BeforeUpdateCommentCountQuery');
      
      $Data = $this->SQL
         ->Select('c.CommentID', 'min', 'FirstCommentID')
         ->Select('c.CommentID', 'max', 'LastCommentID')
         ->Select('c.DateInserted', 'max', 'DateLastComment')
         ->Select('c.CommentID', 'count', 'CountComments')
         ->From('Comment c')
         ->Where('c.DiscussionID', $DiscussionID)
         ->Get()->FirstRow(DATASET_TYPE_ARRAY);

      $this->EventArguments['Discussion'] =& $Discussion;
      $this->EventArguments['Counts'] =& $Data;
      $this->FireEvent('BeforeUpdateCommentCount');
      
      if ($Discussion) {
         if ($Data) {
            $this->SQL->Update('Discussion');
            if (!$Discussion['Sink'] && $Data['DateLastComment'])
               $this->SQL->Set('DateLastComment', $Data['DateLastComment']);
            elseif (!$Data['DateLastComment'])
               $this->SQL->Set('DateLastComment', $Discussion['DateInserted']);

            $this->SQL
               ->Set('FirstCommentID', $Data['FirstCommentID'])
               ->Set('LastCommentID', $Data['LastCommentID'])
               ->Set('CountComments', $Data['CountComments'])
               ->Where('DiscussionID', $DiscussionID)
               ->Put();

            // Update the last comment's user ID.
            $this->SQL
               ->Update('Discussion d')
               ->Update('Comment c')
               ->Set('d.LastCommentUserID', 'c.InsertUserID', FALSE)
               ->Where('d.DiscussionID', $DiscussionID)
               ->Where('c.CommentID', 'd.LastCommentID', FALSE, FALSE)
               ->Put();
         } else {
            // Update the discussion with null counts.
            $this->SQL
               ->Update('Discussion')
               ->Set('CountComments', 0)
               ->Set('FirstCommentID', NULL)
               ->Set('LastCommentID', NULL)
               ->Set('DateLastComment', 'DateInserted', FALSE, FALSE)
               ->Set('LastCommentUserID', NULL)
               ->Where('DiscussionID', $DiscussionID);
         }
      }
   }
   
   /**
    * Update UserDiscussion so users don't have incorrect counts. 
    * 
    * @since 2.0.18
    * @access public
    *
    * @param int $DiscussionID Unique ID of the discussion we are updating.
    */
   public function UpdateUserCommentCounts($DiscussionID) {
      $Sql = "update ".$this->Database->DatabasePrefix."UserDiscussion ud
         set CountComments = (
            select count(c.CommentID)+1 
            from ".$this->Database->DatabasePrefix."Comment c 
            where c.DateInserted < ud.DateLastViewed
         )
         where DiscussionID = $DiscussionID";
      $this->SQL->Query($Sql);
   }
   
   /**
    * Update user's total comment count.
    * 
    * @since 2.0.0
    * @access public
    *
    * @param int $UserID Unique ID of the user to be updated.
    */
   public function UpdateUser($UserID, $Inc = FALSE) {
      if ($Inc) {
         // Just increment the comment count.
         $this->SQL
            ->Update('User')
            ->Set('CountComments', 'CountComments + 1', FALSE)
            ->Where('UserID', $UserID)
            ->Put();
      } else {
         // Retrieve a comment count
         $CountComments = $this->SQL
            ->Select('c.CommentID', 'count', 'CountComments')
            ->From('Comment c')
            ->Where('c.InsertUserID', $UserID)
            ->Get()
            ->FirstRow()
            ->CountComments;

         // Save to the attributes column of the user table for this user.
         Gdn::UserModel()->SetField($UserID, 'CountComments', $CountComments);
      }
   }
   
   /**
    * Delete a comment.
    *
    * This is a hard delete that completely removes it from the database.
    * Events: DeleteComment.
    * 
    * @since 2.0.0
    * @access public
    *
    * @param int $CommentID Unique ID of the comment to be deleted.
    * @param array $Options Additional options for the delete.
    * @param bool Always returns TRUE.
    */
   public function Delete($CommentID, $Options = array()) {
      $this->EventArguments['CommentID'] = $CommentID;
      
      $Comment = $this->GetID($CommentID, DATASET_TYPE_ARRAY);
      if (!$Comment)
         return FALSE;
      $Discussion = $this->SQL->GetWhere('Discussion', array('DiscussionID' => $Comment['DiscussionID']))->FirstRow(DATASET_TYPE_ARRAY);
         
      // Decrement the UserDiscussion comment count if the user has seen this comment
      $Offset = $this->GetOffset($CommentID);
      $this->SQL->Update('UserDiscussion')
         ->Set('CountComments', 'CountComments - 1', FALSE)
         ->Where('DiscussionID', $Comment['DiscussionID'])
         ->Where('CountComments >', $Offset)
         ->Put();

      $this->FireEvent('DeleteComment');

      // Log the deletion.
      $Log = GetValue('Log', $Options, 'Delete');
      LogModel::Insert($Log, 'Comment', $Comment, GetValue('LogOptions', $Options, array()));

      // Delete the comment.
      $this->SQL->Delete('Comment', array('CommentID' => $CommentID));

      // Update the comment count
      $this->UpdateCommentCount($Discussion);

      // Update the user's comment count
      $this->UpdateUser($Comment['InsertUserID']);
      
      // Update the category.
      $Category = CategoryModel::Categories(GetValue('CategoryID', $Discussion));
      if ($Category && $Category['LastCommentID'] == $CommentID) {
         $CategoryModel = new CategoryModel();
         $CategoryModel->SetRecentPost($Category['CategoryID']);
      }

      // Clear the page cache.
      $this->RemovePageCache($Comment['DiscussionID']);
      return TRUE;
   }
   
   /**
    * Modifies comment data before it is returned.
    * 
    * @since 2.1a32
    * @access public
    *
    * @param object $Data SQL result.
    */
	public function SetCalculatedFields(&$Data) {
		$Result = &$Data->Result();
		foreach($Result as &$Comment) {
         $this->Calculate($Comment);
      }
   }
   
   /**
    * Modifies comment data before it is returned.
    * 
    * @since 2.1a32
    * @access public
    *
    * @param object $Data SQL result.
    */
   public function Calculate($Comment) {
      
      // Do nothing yet
      
      $this->EventArguments['Comment'] = $Comment;
      $this->FireEvent('SetCalculatedFields');
   }
   
   public function Where($Value = NULL) {
      if ($Value === NULL)
         return $this->_Where;
      elseif (!$Value)
         $this->_Where = array();
      elseif (is_a($Value, 'Gdn_SQLDriver')) {
         if (!empty($this->_Where))
            $Value->Where($this->_Where);
      } else
         $this->_Where = $Value;
   }
}
