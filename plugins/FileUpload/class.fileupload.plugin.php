<?php if (!defined('APPLICATION')) exit();

/**
 * FileUpload Plugin
 *
 * This plugin enables file uploads and attachments to discussions and comments.
 *
 * Changes:
 *  1.5     Add hooks for API uploading. Add docs. Fix constructor to call parent.
 *  1.5.6   Add hook for discussions/download.
 *  1.6     Fix the file upload plugin for external storage.
 *          Add file extensions to the non-image icons.
 *  1.7     Add support for discussions and comments placed in moderation queue (Lincoln, Nov 2012)
 *  1.7.1   Fix for file upload not working now that we have json rendered as application/json.
 *  1.8     Added the ability to restrict file uploads per category.
 *  1.8.1   Remove deprecated jQuery functions.
 *  1.8.3   Modified fileupload.js to handle dependency on jquery.popup better.
 *
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2003 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Addons
 */

// Define the plugin:
$PluginInfo['FileUpload'] = array(
   'Description' => 'Images and files may be attached to discussions and comments.',
   'Version' => '1.8.4',
   'RequiredApplications' => array('Vanilla' => '2.1'),
   'RequiredTheme' => FALSE,
   'RequiredPlugins' => FALSE,
   'HasLocale' => FALSE,
   'MobileFriendly' => TRUE,
   'RegisterPermissions' => array(
      'Plugins.Attachments.Upload.Allow' => 'Garden.Profiles.Edit',
      'Plugins.Attachments.Download.Allow' => 'Garden.Profiles.Edit'
   ),
   //'SettingsUrl' => '/dashboard/plugin/fileupload',
   //'SettingsPermission' => 'Garden.Settings.Manage',
   'Author' => "Tim Gunter",
   'AuthorEmail' => 'tim@vanillaforums.com',
   'AuthorUrl' => 'http://www.vanillaforums.com'
);

include dirname(__FILE__).'/class.mediamodel.php';

class FileUploadPlugin extends Gdn_Plugin {
   /** @var array */
   protected $_MediaCache;

   /**
    * Permission checks & property prep.
    */
   public function __construct() {
      parent::__construct();
      $this->_MediaCache = NULL;
      $this->CanUpload = Gdn::Session()->CheckPermission('Plugins.Attachments.Upload.Allow', FALSE);
      $this->CanDownload = Gdn::Session()->CheckPermission('Plugins.Attachments.Download.Allow', FALSE);

/*
      if ($this->CanUpload) {
         $PermissionCategory = CategoryModel::PermissionCategory(Gdn::Controller()->Data('Category'));
         if (!GetValue('AllowFileUploads', $PermissionCategory, TRUE))
            $this->CanUpload = FALSE;
      }
*/
   }

   public function AssetModel_StyleCss_Handler($Sender) {
      $Sender->AddCssFile('fileupload.css', 'plugins/FileUpload');
   }

   public function MediaCache() {
      if ($this->_MediaCache === NULL) {
         $this->CacheAttachedMedia(Gdn::Controller());
      }
      return $this->_MediaCache;
   }

   /**
    * Get instance of MediaModel.
    *
    * @return object MediaModel
    */
   public function MediaModel() {
      static $MediaModel = NULL;

      if ($MediaModel === NULL) {
         $MediaModel = new MediaModel();
      }
      return $MediaModel;
   }

   /**
    * Adds "Media" menu option to the Forum menu on the dashboard.
    */
   /*public function Base_GetAppSettingsMenuItems_Handler($Sender) {
      $Menu = &$Sender->EventArguments['SideMenu'];
      $Menu->AddItem('Forum', 'Forum');
      $Menu->AddLink('Forum', 'Media', 'plugin/fileupload', 'Garden.Settings.Manage');
   }*/

   public function PluginController_FileUpload_Create($Sender) {
      $Sender->Title('FileUpload');
      $Sender->AddSideMenu('plugin/fileupload');
      Gdn_Theme::Section('Dashboard');
      $Sender->Form = new Gdn_Form();

      $this->EnableSlicing($Sender);
      $this->Dispatch($Sender, $Sender->RequestArgs);
   }

   /*public function Controller_Toggle($Sender) {
      $FileUploadStatus = Gdn::Config('Plugins.FileUpload.Enabled', FALSE);

      $Validation = new Gdn_Validation();
      $ConfigurationModel = new Gdn_ConfigurationModel($Validation);
      $ConfigurationModel->SetField(array('FileUploadStatus'));

      // Set the model on the form.
      $Sender->Form->SetModel($ConfigurationModel);

      if ($Sender->Form->AuthenticatedPostBack()) {
         $FileUploadStatus = ($Sender->Form->GetValue('FileUploadStatus') == 'ON') ? TRUE : FALSE;
         SaveToConfig('Plugins.FileUpload.Enabled', $FileUploadStatus);
      }

      $Sender->SetData('FileUploadStatus', $FileUploadStatus);
      $Sender->Form->SetData(array(
         'FileUploadStatus'  => $FileUploadStatus
      ));
      $Sender->Render($this->GetView('toggle.php'));
   }*/

   /*public function Controller_Index($Sender) {
      $Sender->Render('FileUpload', '', 'plugins/FileUpload');
   }*/

   public function Controller_Delete($Sender) {
      list($Action, $MediaID) = $Sender->RequestArgs;
      $Sender->DeliveryMethod(DELIVERY_METHOD_JSON);
      $Sender->DeliveryType(DELIVERY_TYPE_VIEW);

      $Delete = array(
         'MediaID'   => $MediaID,
         'Status'    => 'failed'
      );

      $Media = $this->MediaModel()->GetID($MediaID);
      $ForeignTable = GetValue('ForeignTable', $Media);
      $Permission = FALSE;

      // Get the category so we can figure out whether or not the user has permission to delete.
      if ($ForeignTable == 'discussion') {
         $PermissionCategoryID = Gdn::SQL()
            ->Select('c.PermissionCategoryID')
            ->From('Discussion d')
            ->Join('Category c', 'd.CategoryID = c.CategoryID')
            ->Where('d.DiscussionID', GetValue('ForeignID', $Media))
            ->Get()->Value('PermissionCategoryID');
         $Permission = 'Vanilla.Discussions.Edit';
      } elseif ($ForeignTable == 'comment') {
         $PermissionCategoryID = Gdn::SQL()
            ->Select('c.PermissionCategoryID')
            ->From('Comment cm')
            ->Join('Discussion d', 'd.DiscussionID = cm.DiscussionID')
            ->Join('Category c', 'd.CategoryID = c.CategoryID')
            ->Where('cm.CommentID', GetValue('ForeignID', $Media))
            ->Get()->Value('PermissionCategoryID');
         $Permission = 'Vanilla.Comments.Edit';
      }

      if ($Media) {
         $Delete['Media'] = $Media;
         $UserID = GetValue('UserID', Gdn::Session());
         if (GetValue('InsertUserID', $Media, NULL) == $UserID || Gdn::Session()->CheckPermission($Permission, TRUE, 'Category', $PermissionCategoryID)) {
            $this->MediaModel()->Delete($Media, TRUE);
            $Delete['Status'] = 'success';
         } else {
            throw PermissionException();
         }
      } else {
         throw NotFoundException('Media');
      }

      $Sender->SetJSON('Delete', $Delete);
      $Sender->Render($this->GetView('blank.php'));
   }

   /**
    * DiscussionController_Render_Before HOOK
    *
    * Calls FileUploadPlugin::PrepareController
    *
    * @access public
    * @param mixed $Sender The hooked controller
    * @see FileUploadPlugin::PrepareController
    * @return void
    */
   public function DiscussionController_Render_Before($Sender) {
      $this->PrepareController($Sender);
   }

   /**
    * PostController_Render_Before HOOK
    *
    * Calls FileUploadPlugin::PrepareController
    *
    * @access public
    * @param mixed $Sender The hooked controller
    * @see FileUploadPlugin::PrepareController
    * @return void
    */
   public function PostController_Render_Before($Sender) {
      $this->PrepareController($Sender);
   }

   /**
    * MessagesController_Render_Before HOOK
    *
    * Calls FileUploadPlugin::PrepareController
    *
    * @access public
    * @param mixed $Sender The hooked controller
    * @see FileUploadPlugin::PrepareController
    * @return void
    */
   public function MessagesController_Render_Before($Sender) {
      $this->PrepareController($Sender);
   }

   /**
    * PrepareController function.
    *
    * Adds CSS and JS includes to the header of the discussion or post.
    *
    * @access protected
    * @param mixed $Controller The hooked controller
    * @return void
    */
   protected function PrepareController($Controller) {
      if (!$this->IsEnabled()) return;

      $Controller->AddJsFile('fileupload.js', 'plugins/FileUpload');
      $Controller->AddDefinition('apcavailable',self::ApcAvailable());
      $Controller->AddDefinition('uploaderuniq',uniqid(''));

      $PostMaxSize = Gdn_Upload::UnformatFileSize(ini_get('post_max_size'));
      $FileMaxSize = Gdn_Upload::UnformatFileSize(ini_get('upload_max_filesize'));
      $ConfigMaxSize = Gdn_Upload::UnformatFileSize(C('Garden.Upload.MaxFileSize', '1MB'));
      $MaxSize = min($PostMaxSize, $FileMaxSize, $ConfigMaxSize);
      $Controller->AddDefinition('maxuploadsize',$MaxSize);
   }

   /**
    * PostController_BeforeFormButtons_Handler HOOK
    *
    * Calls FileUploadPlugin::DrawAttachFile
    *
    * @access public
    * @param mixed &$Sender
    * @see FileUploadPlugin::DrawAttachFile
    * @return void
    */
   public function PostController_AfterDiscussionFormOptions_Handler($Sender) {
      if (!is_null($Discussion = GetValue('Discussion',$Sender, NULL))) {
         $Sender->EventArguments['Type'] = 'Discussion';
         $Sender->EventArguments['Discussion'] = $Discussion;
         $this->AttachUploadsToComment($Sender, 'discussion');
      }
      $this->DrawAttachFile($Sender);
   }

   public function DiscussionController_BeforeFormButtons_Handler($Sender) {
      $this->DrawAttachFile($Sender);
   }

   public function MessagesController_BeforeBodyBox_Handler($Sender) {
      $this->DrawAttachFile($Sender);
   }

   /**
    * DrawAttachFile function.
    *
    * Helper method that allows the plugin to insert the file uploader UI into the
    * Post Discussion and Post Comment forms.
    *
    * @access public
    * @param mixed $Sender
    * @return void
    */
   public function DrawAttachFile($Sender) {
      if (!$this->IsEnabled()) return;
      if (!$this->CanUpload) return;

      echo $Sender->FetchView('attach_file', '', 'plugins/FileUpload');
   }

   /**
    * CacheAttachedMedia function.
    *
    * @access protected
    * @param mixed $Sender
    * @return void
    */
   protected function CacheAttachedMedia($Sender) {
      if (!$this->IsEnabled()) return;

      $Comments = $Sender->Data('Comments');
      $CommentIDList = array();

//      decho($Comments);
//      die($Comments);

      if ($Comments instanceof Gdn_DataSet && $Comments->NumRows()) {
         $Comments->DataSeek(-1);
         while ($Comment = $Comments->NextRow())
            $CommentIDList[] = $Comment->CommentID;
      } elseif (isset($Sender->Discussion) && $Sender->Discussion) {
         $CommentIDList[] = $Sender->DiscussionID = $Sender->Discussion->DiscussionID;
      }
      if (isset($Sender->Comment) && isset($Sender->Comment->CommentID)) {
         $CommentIDList[] = $Sender->Comment->CommentID;
      }

      if (count($CommentIDList)) {
         $DiscussionID = $Sender->Data('Discussion.DiscussionID');

         $MediaData = $this->MediaModel()->PreloadDiscussionMedia($DiscussionID, $CommentIDList);
      } else {
         $MediaData = FALSE;
      }

      $MediaArray = array();
      if ($MediaData !== FALSE) {
         $MediaData->DataSeek(-1);
         while ($Media = $MediaData->NextRow()) {
            $MediaArray[$Media->ForeignTable.'/'.$Media->ForeignID][] = $Media;
         }
      }

      $this->_MediaCache = $MediaArray;
   }

   /**
    * DiscussionController_AfterCommentBody_Handler function.
    *
    * @access public
    * @param mixed $Sender
    * @return void
    */
   public function DiscussionController_AfterCommentBody_Handler($Sender, $Args) {
      if (isset($Args['Type']))
         $this->AttachUploadsToComment($Sender, strtolower($Args['Type']));
      else
         $this->AttachUploadsToComment($Sender);
   }

   public function DiscussionController_AfterDiscussionBody_Handler($Sender) {
      $this->AttachUploadsToComment($Sender, 'discussion');
   }

   /**
    * PostController_AfterCommentBody_Handler function.
    *
    * @access public
    * @param mixed $Sender
    * @return void
    */
   public function PostController_AfterCommentBody_Handler($Sender) {
      $this->AttachUploadsToComment($Sender);
   }

   /*
    *
    * @param Gdn_Controller $Sender
    */
   public function SettingsController_AddEditCategory_Handler($Sender) {
      $Sender->Data['_PermissionFields']['AllowFileUploads'] = array('Control' => 'CheckBox');
   }

   /**
    * AttachUploadsToComment function.
    *
    * @access protected
    * @param mixed $Sender
    * @return void
    */
   protected function AttachUploadsToComment($Controller, $Type = 'comment') {
      if (!$this->IsEnabled()) return;

      //$Type = strtolower($RawType = $Controller->EventArguments['Type']);
      $RawType = ucfirst($Type);

      if (StringEndsWith($Controller->RequestMethod, 'Comment', TRUE) && $Type != 'comment') {
         $Type = 'comment';
         $RawType = 'Comment';
         if (!isset($Controller->Comment))
            return;
         $Controller->EventArguments['Comment'] = $Controller->Comment;
      }

      $MediaList = $this->MediaCache();
      if (!is_array($MediaList)) return;

      $Param = (($Type == 'comment') ? 'CommentID' : 'DiscussionID');
      $MediaKey = $Type.'/'.GetValue($Param, GetValue($RawType, $Controller->EventArguments));
      if (array_key_exists($MediaKey, $MediaList)) {
         include_once $Controller->FetchViewLocation('fileupload_functions', '', 'plugins/FileUpload');

         $Controller->SetData('CommentMediaList', $MediaList[$MediaKey]);
         $Controller->SetData('GearImage', $this->GetWebResource('images/gear.png'));
         $Controller->SetData('Garbage', $this->GetWebResource('images/trash.png'));
         $Controller->SetData('CanDownload', $this->CanDownload);
         echo $Controller->FetchView($this->GetView('link_files.php'));
      }
   }

   /**
    * DiscussionController_Download_Create function.
    *
    * @access public
    * @param mixed $Sender
    * @return void
    */
   public function DiscussionController_Download_Create($Sender) {
      if (!$this->IsEnabled()) return;
      if (!$this->CanDownload) throw PermissionException("File could not be streamed: Access is denied");

      list($MediaID) = $Sender->RequestArgs;
      $Media = $this->MediaModel()->GetID($MediaID);

      if (!$Media) return;

      $Filename = Gdn::Request()->Filename();
      if (!$Filename || $Filename == 'default') $Filename = $Media->Name;

      $DownloadPath = CombinePaths(array(MediaModel::PathUploads(),GetValue('Path', $Media)));

      if (in_array(strtolower(pathinfo($Filename, PATHINFO_EXTENSION)), array('bmp', 'gif', 'jpg', 'jpeg', 'png')))
         $ServeMode = 'inline';
      else
         $ServeMode = 'attachment';

      $Served = FALSE;
      $this->EventArguments['DownloadPath'] = $DownloadPath;
      $this->EventArguments['ServeMode'] = $ServeMode;
      $this->EventArguments['Media'] = $Media;
      $this->EventArguments['Served'] = &$Served;
      $this->FireEvent('BeforeDownload');

      if (!$Served) {
         return Gdn_FileSystem::ServeFile($DownloadPath, $Filename, $Media->Type, $ServeMode);
         throw new Exception('File could not be streamed: missing file ('.$DownloadPath.').');
      }

      exit();
   }

   /**
    * Attach files to a comment during save.
    *
    * @access public
    * @param object $Sender
    * @param array $Args
    */
   public function PostController_AfterCommentSave_Handler($Sender, $Args) {
      if (!$Args['Comment']) return;

      $CommentID = $Args['Comment']->CommentID;
      if (!$CommentID) return;

      $AttachedFilesData = Gdn::Request()->GetValue('AttachedUploads');
      $AllFilesData = Gdn::Request()->GetValue('AllUploads');

      $this->AttachAllFiles($AttachedFilesData, $AllFilesData, $CommentID, 'comment');
   }

   /**
    * Attach files to a discussion during save.
    *
    * @access public
    * @param object $Sender
    * @param array $Args
    */
   public function PostController_AfterDiscussionSave_Handler($Sender, $Args) {
      if (!$Args['Discussion']) return;

      $DiscussionID = $Args['Discussion']->DiscussionID;
      if (!$DiscussionID) return;

      $AttachedFilesData = Gdn::Request()->GetValue('AttachedUploads');
      $AllFilesData = Gdn::Request()->GetValue('AllUploads');

      $this->AttachAllFiles($AttachedFilesData, $AllFilesData, $DiscussionID, 'discussion');
   }

   /**
    * Attach files to a log entry; used when new content is sent to moderation queue.
    *
    * @access public
    * @param object $Sender
    * @param array $Args
    */
   public function LogModel_AfterInsert_Handler($Sender, $Args) {
      // Only trigger if logging unapproved discussion or comment
      $Log = GetValue('Log', $Args);
      $Type = strtolower(GetValue('RecordType', $Log));
      $Operation = GetValue('Operation', $Log);
      if (!in_array($Type, array('discussion', 'comment')) || $Operation != 'Pending')
         return;

      // Attach file to the log entry
      $LogID = GetValue('LogID', $Args);
      $AttachedFilesData = Gdn::Request()->GetValue('AttachedUploads');
      $AllFilesData = Gdn::Request()->GetValue('AllUploads');

      $this->AttachAllFiles($AttachedFilesData, $AllFilesData, $LogID, 'log');
   }

   /**
    * Attach files to record created by restoring a log entry.
    *
    * This happens when a discussion or comment is approved.
    *
    * @access public
    * @param object $Sender
    * @param array $Args
    */
   public function LogModel_AfterRestore_Handler($Sender, $Args) {
      $Log = GetValue('Log', $Args);

      // Only trigger if restoring discussion or comment
      $Type = strtolower(GetValue('RecordType', $Log));
      if (!in_array($Type, array('discussion', 'comment')))
         return;

      // Reassign media records from log entry to newly inserted content
      $this->MediaModel()->Reassign(GetValue('LogID', $Log), 'log', GetValue('InsertID', $Args), $Type);
   }

   /**
    * AttachAllFiles function.
    *
    * @access protected
    * @param mixed $AttachedFilesData
    * @param mixed $AllFilesData
    * @param mixed $ForeignID
    * @param mixed $ForeignTable
    * @return void
    */
   protected function AttachAllFiles($AttachedFilesData, $AllFilesData, $ForeignID, $ForeignTable) {
      if (!$this->IsEnabled()) return;

      // No files attached
      if (!$AttachedFilesData) return;

      $SuccessFiles = array();
      foreach ($AttachedFilesData as $FileID) {
         $Attached = $this->AttachFile($FileID, $ForeignID, $ForeignTable);
         if ($Attached)
            $SuccessFiles[] = $FileID;
      }

      // clean up failed and unattached files
      $DeleteIDs = array_diff($AllFilesData, $SuccessFiles);
      foreach ($DeleteIDs as $DeleteID) {
         $this->TrashFile($DeleteID);
      }
   }

   /**
    * Create and display a thumbnail of an uploaded file.
    */
   public function UtilityController_Thumbnail_Create($Sender, $Args = array()) {
      $MediaID = array_shift($Args);
      if (!is_numeric($MediaID))
         array_unshift($Args, $MediaID);
      $SubPath = implode('/', $Args);
      $Name = $SubPath;
      $Parsed = Gdn_Upload::Parse($Name);

      // Get actual path to the file.
      $Path = Gdn_Upload::CopyLocal($SubPath);
      if (!file_exists($Path))
         throw NotFoundException('File');

      // Figure out the dimensions of the upload.
      $ImageSize = getimagesize($Path);
      $SHeight = $ImageSize[1];
      $SWidth = $ImageSize[0];

      if (!in_array($ImageSize[2], array(IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG))) {
         if (is_numeric($MediaID)) {
            // Fix the thumbnail information so this isn't requested again and again.
            $Model = new MediaModel();
            $Media = array('MediaID' => $MediaID, 'ImageWidth' => 0, 'ImageHeight' => 0, 'ThumbPath' => NULL);
            $Model->Save($Media);
         }

         $Url = Asset('/plugins/FileUpload/images/file.png');
         Redirect($Url, 301);
      }

      $Options = array();

      $ThumbHeight = MediaModel::ThumbnailHeight();
      $ThumbWidth = MediaModel::ThumbnailWidth();

      if (!$ThumbHeight || $SHeight < $ThumbHeight) {
         $Height = $SHeight;
         $Width = $SWidth;
      } else {
         $Height = $ThumbHeight;
         $Width = round($Height * $SWidth / $SHeight);
      }

      if ($ThumbWidth && $Width > $ThumbWidth) {
         $Width = $ThumbWidth;

         if (!$ThumbHeight) {
            $Height = round($Width * $SHeight / $SWidth);
         } else {
            $Options['Crop'] = TRUE;
         }
      }

      $TargetPath = "thumbnails/{$Parsed['Name']}";
      $ThumbParsed = Gdn_UploadImage::SaveImageAs($Path, $TargetPath, $Height, $Width, $Options);
      // Cleanup if we're using a scratch copy
      if ($ThumbParsed['Type'] != '' || $Path != MediaModel::PathUploads().'/'.$SubPath)
         @unlink($Path);

      if (is_numeric($MediaID)) {
         // Save the thumbnail information.
         $Model = new MediaModel();
         $Media = array('MediaID' => $MediaID, 'ThumbWidth' => $Width, 'ThumbHeight' => $Height, 'ThumbPath' => $ThumbParsed['SaveName']);
         $Model->Save($Media);
      }

      $Url = $ThumbParsed['Url'];
      Redirect($Url, 301);
//      Gdn_FileSystem::ServeFile($TargetPath, basename($Path), '', 'inline');
   }

   /**
    * Attach a file to a foreign table and ID.
    *
    * @access protected
    * @param int $FileID
    * @param int $ForeignID
    * @param string $ForeignType Lowercase.
    * @return bool Whether attach was successful.
    */
   protected function AttachFile($FileID, $ForeignID, $ForeignType) {
      $Media = $this->MediaModel()->GetID($FileID);
      if ($Media) {
         $Media->ForeignID = $ForeignID;
         $Media->ForeignTable = $ForeignType;
         try {
//            $PlacementStatus = $this->PlaceMedia($Media, Gdn::Session()->UserID);
            $this->MediaModel()->Save($Media);
         } catch (Exception $e) {
            die($e->getMessage());
            return FALSE;
         }
         return TRUE;
      }
      return FALSE;
   }

   /**
    * PlaceMedia function.
    *
    * @access protected
    * @param mixed &$Media
    * @param mixed $UserID
    * @return void
    */
   protected function PlaceMedia(&$Media, $UserID) {
      $NewFolder = FileUploadPlugin::FindLocalMediaFolder($Media->MediaID, $UserID, TRUE, FALSE);
      $CurrentPath = array();
      foreach ($NewFolder as $FolderPart) {
         array_push($CurrentPath, $FolderPart);
         $TestFolder = CombinePaths($CurrentPath);

         if (!is_dir($TestFolder) && !@mkdir($TestFolder, 0777, TRUE))
            throw new Exception("Failed creating folder '{$TestFolder}' during PlaceMedia verification loop");
      }

      $FileParts = pathinfo($Media->Name);
      $SourceFilePath = CombinePaths(array($this->PathUploads(),$Media->Path));
      $NewFilePath = CombinePaths(array($TestFolder,$Media->MediaID.'.'.$FileParts['extension']));
      $Success = rename($SourceFilePath, $NewFilePath);
      if (!$Success)
         throw new Exception("Failed renaming '{$SourceFilePath}' -> '{$NewFilePath}'");

      $NewFilePath = FileUploadPlugin::FindLocalMedia($Media, FALSE, TRUE);
      $Media->Path = $NewFilePath;

      return TRUE;
   }

   /**
    * FindLocalMediaFolder function.
    *
    * @access public
    * @static
    * @param mixed $MediaID
    * @param mixed $UserID
    * @param mixed $Absolute. (default: FALSE)
    * @param mixed $ReturnString. (default: FALSE)
    * @return void
    */
   public static function FindLocalMediaFolder($MediaID, $UserID, $Absolute = FALSE, $ReturnString = FALSE) {
      $DispersionFactor = C('Plugin.FileUpload.DispersionFactor',20);
      $FolderID = $MediaID % $DispersionFactor;
      $ReturnArray = array('FileUpload',$FolderID);

      if ($Absolute)
         array_unshift($ReturnArray, MediaModel::PathUploads());

      return ($ReturnString) ? implode(DS,$ReturnArray) : $ReturnArray;
   }

   /**
    * FindLocalMedia function.
    *
    * @access public
    * @static
    * @param mixed $Media
    * @param mixed $Absolute. (default: FALSE)
    * @param mixed $ReturnString. (default: FALSE)
    * @return void
    */
   public static function FindLocalMedia($Media, $Absolute = FALSE, $ReturnString = FALSE) {
      $ArrayPath = FileUploadPlugin::FindLocalMediaFolder($Media->MediaID, $Media->InsertUserID, $Absolute, FALSE);

      $FileParts = pathinfo($Media->Name);
      $RealFileName = $Media->MediaID.'.'.$FileParts['extension'];
      array_push($ArrayPath, $RealFileName);

      return ($ReturnString) ? implode(DS, $ArrayPath) : $ArrayPath;
   }

   /**
    * Allows plugin to handle ajax file uploads.
    *
    * @access public
    * @param object $Sender
    */
   public function PostController_Upload_Create($Sender) {
      if (!$this->IsEnabled()) return;

      list($FieldName) = $Sender->RequestArgs;

      $Sender->DeliveryMethod(DELIVERY_METHOD_JSON);
      $Sender->DeliveryType(DELIVERY_TYPE_VIEW);
      include_once $Sender->FetchViewLocation('fileupload_functions', '', 'plugins/FileUpload');

      $Sender->FieldName = $FieldName;
      $Sender->ApcKey = Gdn::Request()->GetValueFrom(Gdn_Request::INPUT_POST,'APC_UPLOAD_PROGRESS');

      // this will hold the IDs and filenames of the items we were sent. booyahkashaa.
      $MediaResponse = array();

      $FileData = Gdn::Request()->GetValueFrom(Gdn_Request::INPUT_FILES, $FieldName, FALSE);
      try {
         if (!$this->CanUpload)
            throw new FileUploadPluginUploadErrorException("You do not have permission to upload files",11,'???');

         if (!$Sender->Form->IsPostBack()) {
            $PostMaxSize = ini_get('post_max_size');
            throw new FileUploadPluginUploadErrorException("The post data was too big (max {$PostMaxSize})",10,'???');
         }

         if (!$FileData) {
            //$PostMaxSize = ini_get('post_max_size');
            $MaxUploadSize = ini_get('upload_max_filesize');
            //throw new FileUploadPluginUploadErrorException("The uploaded file was too big (max {$MaxUploadSize})",10,'???');
            throw new FileUploadPluginUploadErrorException("No file data could be found in your post",10,'???');
         }

         // Validate the file upload now.
         $FileErr  = $FileData['error'];
         $FileType = $FileData['type'];
         $FileName = $FileData['name'];
         $FileTemp = $FileData['tmp_name'];
         $FileSize = $FileData['size'];
         $FileKey  = ($Sender->ApcKey ? $Sender->ApcKey : '');

         if ($FileErr != UPLOAD_ERR_OK) {
            $ErrorString = '';
            switch ($FileErr) {
               case UPLOAD_ERR_INI_SIZE:
                  $MaxUploadSize = ini_get('upload_max_filesize');
                  $ErrorString = sprintf(T('The uploaded file was too big (max %s).'), $MaxUploadSize);
                  break;
               case UPLOAD_ERR_FORM_SIZE:
                  $ErrorString = 'The uploaded file was too big';
                  break;
               case UPLOAD_ERR_PARTIAL:
                  $ErrorString = 'The uploaded file was only partially uploaded';
                  break;
               case UPLOAD_ERR_NO_FILE:
                  $ErrorString = 'No file was uploaded';
                  break;
               case UPLOAD_ERR_NO_TMP_DIR:
                  $ErrorString = 'Missing a temporary folder';
                  break;
               case UPLOAD_ERR_CANT_WRITE:
                  $ErrorString = 'Failed to write file to disk';
                  break;
               case UPLOAD_ERR_EXTENSION:
                  $ErrorString = 'A PHP extension stopped the file upload';
                  break;
            }

            throw new FileUploadPluginUploadErrorException($ErrorString, $FileErr, $FileName, $FileKey);
         }

         // Analyze file extension
         $FileNameParts = pathinfo($FileName);
         $Extension = strtolower($FileNameParts['extension']);
         $AllowedExtensions = C('Garden.Upload.AllowedFileExtensions', array("*"));
         if (!in_array($Extension, $AllowedExtensions) && !in_array('*',$AllowedExtensions))
            throw new FileUploadPluginUploadErrorException("Uploaded file type is not allowed.", 11, $FileName, $FileKey);

         // Check upload size
         $MaxUploadSize = Gdn_Upload::UnformatFileSize(C('Garden.Upload.MaxFileSize', '1G'));
         if ($FileSize > $MaxUploadSize) {
            $Message = sprintf(T('The uploaded file was too big (max %s).'), Gdn_Upload::FormatFileSize($MaxUploadSize));
            throw new FileUploadPluginUploadErrorException($Message, 11, $FileName, $FileKey);
         }

         // Build filename
         $SaveFilename = md5(microtime()).'.'.strtolower($Extension);
         $SaveFilename = '/FileUpload/'.substr($SaveFilename, 0, 2).'/'.substr($SaveFilename, 2);

         // Get the image size before doing anything.
         list($ImageWidth, $ImageHeight) = Gdn_UploadImage::ImageSize($FileTemp, $FileName);

         // Fire event for hooking save location
         $this->EventArguments['Path'] = $FileTemp;
         $Parsed = Gdn_Upload::Parse($SaveFilename);
         $this->EventArguments['Parsed'] =& $Parsed;
         $this->EventArguments['OriginalFilename'] = $FileName;
         $Handled = FALSE;
         $this->EventArguments['Handled'] =& $Handled;
         $this->FireAs('Gdn_Upload')->FireEvent('SaveAs');
         $SavePath = $Parsed['Name'];

         if (!$Handled) {
            // Build save location
            $SavePath = MediaModel::PathUploads().$SaveFilename;
            if (!is_dir(dirname($SavePath)))
               mkdir(dirname($SavePath), 0777, TRUE);
            if (!is_dir(dirname($SavePath)))
               throw new FileUploadPluginUploadErrorException("Internal error, could not save the file.", 9, $FileName);

            // Move to permanent location
            $MoveSuccess = move_uploaded_file($FileTemp, $SavePath);
            if (!$MoveSuccess)
               throw new FileUploadPluginUploadErrorException("Internal error, could not move the file.", 9, $FileName);
         } else {
            $SaveFilename = $Parsed['SaveName'];
         }

         // Save Media data
         $Media = array(
            'Name'            => $FileName,
            'Type'            => $FileType,
            'Size'            => $FileSize,
            'ImageWidth'      => $ImageWidth,
            'ImageHeight'     => $ImageHeight,
            'InsertUserID'    => Gdn::Session()->UserID,
            'DateInserted'    => date('Y-m-d H:i:s'),
            'StorageMethod'   => 'local',
            'Path'            => $SaveFilename
         );
         $MediaID = $this->MediaModel()->Save($Media);
         $Media['MediaID'] = $MediaID;

         $FinalImageLocation = '';
         $PreviewImageLocation = MediaModel::ThumbnailUrl($Media);

//

         $MediaResponse = array(
            'Status'             => 'success',
            'MediaID'            => $MediaID,
            'Filename'           => $FileName,
            'Filesize'           => $FileSize,
            'FormatFilesize'     => Gdn_Format::Bytes($FileSize,1),
            'ProgressKey'        => $Sender->ApcKey ? $Sender->ApcKey : '',
//            'PreviewImageLocation' => Url($PreviewImageLocation),
            'Thumbnail' => base64_encode(MediaThumbnail($Media)),
            'FinalImageLocation' => Url(MediaModel::Url($Media)),
            'Parsed' => $Parsed
         );

      } catch (FileUploadPluginUploadErrorException $e) {

         $MediaResponse = array(
            'Status'          => 'failed',
            'ErrorCode'       => $e->getCode(),
            'Filename'        => $e->getFilename(),
            'StrError'        => $e->getMessage()
         );
         if (!is_null($e->getApcKey()))
            $MediaResponse['ProgressKey'] = $e->getApcKey();

         if ($e->getFilename() != '???')
            $MediaResponse['StrError'] = '('.$e->getFilename().') '.$MediaResponse['StrError'];
      } catch (Exception $Ex) {
         $MediaResponse = array(
            'Status'          => 'failed',
            'ErrorCode'       => $Ex->getCode(),
            'StrError'        => $Ex->getMessage()
         );
      }

      $Sender->SetJSON('MediaResponse', $MediaResponse);

      // Kludge: This needs to have a content type of text/* because it's in an iframe.
      ob_clean();
      header('Content-Type: text/html');
      echo json_encode($Sender->GetJson());
      die();

      $Sender->Render($this->GetView('blank.php'));
   }

   /**
    * Controller method that allows an AJAX call to check the progress of a file
    * upload that is currently in progress.
    *
    * @access public
    * @param object $Sender
    */
   public function PostController_CheckUpload_Create($Sender) {
      list($ApcKey) = $Sender->RequestArgs;

      $Sender->DeliveryMethod(DELIVERY_METHOD_JSON);
      $Sender->DeliveryType(DELIVERY_TYPE_VIEW);

      $KeyData = explode('_',$ApcKey);
      array_shift($KeyData);
      $UploaderID = implode('_',$KeyData);

      $ApcAvailable = self::ApcAvailable();

      $Progress = array(
         'key'          => $ApcKey,
         'uploader'     => $UploaderID,
         'apc'          => ($ApcAvailable) ? 'yes' : 'no'
      );

      if ($ApcAvailable) {
         $UploadStatus = apc_fetch('upload_'.$ApcKey, $Success);

         if (!$Success)
            $UploadStatus = array(
               'current'   => 0,
               'total'     => -1
            );

         $Progress['progress'] = ($UploadStatus['current'] / $UploadStatus['total']) * 100;
         $Progress['total'] = $UploadStatus['total'];
         $Progress['format_total'] = Gdn_Format::Bytes($Progress['total'],1);
         $Progress['cache'] = $UploadStatus;
      }

      $Sender->SetJSON('Progress', $Progress);
      $Sender->Render($this->GetView('blank.php'));
   }

   public static function ApcAvailable() {
      $ApcAvailable = TRUE;
      if ($ApcAvailable && !ini_get('apc.enabled')) $ApcAvailable = FALSE;
      if ($ApcAvailable && !ini_get('apc.rfc1867')) $ApcAvailable = FALSE;

      return $ApcAvailable;
   }

   /**
    * Delete an uploaded file & its media record.
    *
    * @access protected
    * @param int $MediaID Unique ID on Media table.
    */
   protected function TrashFile($MediaID) {
      $Media = $this->MediaModel()->GetID($MediaID);

      if ($Media) {
         $this->MediaModel()->Delete($Media);
         $Deleted = FALSE;

         // Allow interception
         $this->EventArguments['Parsed'] = Gdn_Upload::Parse($Media->Path);
         $this->EventArguments['Handled'] =& $Deleted; // Allow skipping steps below
         $this->FireEvent('TrashFile');

         if (!$Deleted) {
            $DirectPath = MediaModel::PathUploads().DS.$Media->Path;
            if (file_exists($DirectPath))
               $Deleted = @unlink($DirectPath);
         }

         if (!$Deleted) {
            $CalcPath = FileUploadPlugin::FindLocalMedia($Media, TRUE, TRUE);
            if (file_exists($CalcPath))
               $Deleted = @unlink($CalcPath);
         }

      }
   }

   public function DiscussionModel_DeleteDiscussion_Handler($Sender) {
      $DiscussionID = $Sender->EventArguments['DiscussionID'];
      $this->MediaModel()->DeleteParent('Discussion', $DiscussionID);
   }

   public function CommentModel_DeleteComment_Handler($Sender) {
      $CommentID = $Sender->EventArguments['CommentID'];
      $this->MediaModel()->DeleteParent('Comment', $CommentID);
   }

   public function Setup() {
      $this->Structure();
      SaveToConfig('Plugins.FileUpload.Enabled', TRUE);
   }

   public function Structure() {
      $Structure = Gdn::Structure();
      $Structure
         ->Table('Media')
         ->PrimaryKey('MediaID')
         ->Column('Name', 'varchar(255)')
         ->Column('Type', 'varchar(128)')
         ->Column('Size', 'int(11)')
         ->Column('ImageWidth', 'usmallint', NULL)
         ->Column('ImageHeight', 'usmallint', NULL)
         ->Column('StorageMethod', 'varchar(24)', 'local')
         ->Column('Path', 'varchar(255)')

         ->Column('ThumbWidth', 'usmallint', NULL)
         ->Column('ThumbHeight', 'usmallint', NULL)
         ->Column('ThumbPath', 'varchar(255)', NULL)

         ->Column('InsertUserID', 'int(11)')
         ->Column('DateInserted', 'datetime')
         ->Column('ForeignID', 'int(11)', TRUE)
         ->Column('ForeignTable', 'varchar(24)', TRUE)
         ->Set(FALSE, FALSE);

      $Structure
         ->Table('Category')
         ->Column('AllowFileUploads', 'tinyint(1)', '1')
         ->Set();
   }

   public function OnDisable() {
      RemoveFromConfig('Plugins.FileUpload.Enabled');
   }


   /**
    * ConversationsController_BeforeConversationRender_Handler function.
    *
    * @access public
    * @param mixed $Sender
    * @return void
    */
   public function ConversationsController_BeforeConversationRender_Handler($Sender) {
      // Cache the list of media. Don't want to do multiple queries!
      $this->CacheAttachedConversationMedia($Sender);
   }


   /**
    * CacheAttachedConversationMedia function.
    *
    * @access protected
    * @param mixed $Sender
    * @return void
    */
   protected function CacheAttachedConversationMedia($Sender) {
      if ( ! $this->IsEnabled( )) return;

      $Conversation = $Sender->Data('ConversationData');
      $ConversationIDList = array( );

      if ($Conversation && $Conversation instanceof Gdn_DataSet) {
         $Conversation->DataSeek(-1);

         while ($Conversation = $Conversation->NextRow( ))
            $ConversationIDList[] = $Conversation->ConversationID;
      }
      elseif ($Sender->Conversation) {
         $ConversationIDList[] = $Sender->ConversationID = $Sender->Conversation->ConversationID;
      }

      if (isset($Sender->Conversation) && isset($Sender->Conversation->ConversationID)) {
         $ConversationIDList[] = $Sender->Conversation->ConversationID;
      }

      $MediaData = $this->MediaModel( )->PreloadConversationMedia($Sender->ConversationID, $ConversationIDList);

      $MediaArray = array( );
      if ($MediaData !== FALSE) {
         $MediaData->DataSeek(-1);

         while ($Media = $MediaData->NextRow( )) {
            $MediaArray[$Media->ForeignTable.'/'.$Media->ForeignID][] = $Media;
         }
      }

      $this->MediaCache = $MediaArray;
   }


   /*
	* ConversationsController_AfterConversationBody_Handler function.
	*
	* @access public
	* @param mixed $Sender
	* @return void
	*/
   public function ConversationsController_AfterConversationBody_Handler($Sender) {
      $this->AttachUploadsToConversation($Sender);
   }


   /*
	* AttachUploadsToConversation function.
	*
	* @access protected
	* @param mixed $Sender
	* @return void
	*/
   protected function AttachUploadsToConversations($Controller) {
      if ( ! $this->IsEnabled( )) return;

      $Type = strtolower($RawType = $Controller->EventArguments['Type']);

      if (StringEndsWith($Controller->RequestMethod, 'Conversation', TRUE) && $Type != 'Conversation') {
         $Type = 'conversation';
         $RawType = 'Conversation';

         if ( ! isset($Controller->Conversations)) return;

         $Controller->EventArguments['Conversation'] = $Controller->Conversations;
      }

      $MediaList = $this->MediaCache;

      if ( ! is_array($MediaList)) return;

      $Param = (($Type == 'conversation') ? 'ConversationID' : 'ConversationID');
      $MediaKey = $Type.'/'.$Controller->EventArguments[$RawType]->$Param;

      if (array_key_exists($MediaKey, $MediaList)) {
         $Controller->SetData('ConversationMediaList', $MediaList[$MediaKey]);
         $Controller->SetData('GearImage', $this->GetWebResource('images/gear.png'));
         $Controller->SetData('Garbage', $this->GetWebResource('images/trash.png'));
         $Controller->SetData('CanDownload', $this->CanDownload);
         echo $Controller->FetchView($this->GetView('link_files.php'));
      }
   }


   /* ConversationsController_Download_Create function.
	*
	* @access public
	* @param mixed $Sender
	* @return void
	*/
   public function ConversationsController_Download_Create($Sender) {
      if ( ! $this->IsEnabled( )) return;
      if ( ! $this->CanDownload) throw new PermissionException("File could not be streamed: Access is denied");

      list($MediaID) = $Sender->RequestArgs;
      $Media = $this->MediaModel( )->GetID($MediaID);

      if ( ! $Media) return;

      $Filename = Gdn::Request( )->Filename( );
      if ( ! $Filename) $Filename = $Media->Name;

      $DownloadPath = CombinePaths(array(MediaModel::PathUploads( ), GetValue('Path', $Media)));

      if (in_array(strtolower(pathinfo($Filename, PATHINFO_EXTENSION)), array('bmp', 'gif', 'jpg', 'jpeg', 'png')))
         $ServeMode = 'inline';
      else
         $ServeMode = 'attachment';

      $this->EventArguments['Media'] = $Media;
      $this->FireEvent('BeforeDownload');

      Gdn_FileSystem::ServeFile($DownloadPath, $Filename, '', $ServeMode);
   }


   /**
    * ConversationsController_AfterConversationSave_Handler function.
    *
    * @access public
    * @param mixed $Sender
    * @return void
    */
   public function ConversationsController_AfterConversationSave_Handler($Sender) {
      if ( ! $Sender->EventArguments['Conversation']) return;

      $ConversationID = $Sender->EventArguments['Conversation']->ConversationID;
      $AttachedFilesData = Gdn::Request()->GetValue('AttachedUploads');
      $AllFilesData = Gdn::Request()->GetValue('AllUploads');

      $this->AttachAllFiles($AttachedFilesData, $AllFilesData, $ConversationID, 'conversation');
   }

}

class FileUploadPluginUploadErrorException extends Exception {

   protected $Filename;
   protected $ApcKey;

   public function __construct($Message, $Code, $Filename, $ApcKey = NULL) {
      parent::__construct($Message, $Code);
      $this->Filename = $Filename;
      $this->ApcKey = $ApcKey;
   }

   public function getFilename() {
      return $this->Filename;
   }

   public function getApcKey() {
      return $this->ApcKey;
   }

}
