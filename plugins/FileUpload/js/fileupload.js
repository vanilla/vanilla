
/**
 * This was modified May 2, 2014 as the FileUpload plugin was erratically
 * throwing `undefined` errors when trying to use the .popup function.
 *
 * Full explanation:
 *
 * the call for .popup() is sometimes undefined when used in the fileupload
 * plugin--this is because of execution order--the jquery popup plugin is
 * wrapped and defined in a self-executing anonymous function--it's not
 * available until its wrapper function has been executed. sometimes it's not
 * executed before the fileupload plugin calls it--so the fileupload plugin is
 * calling a function that has not been defined yet.
 *
 * The best solution would be one that can guarantee execution order,
 * which would be something like throwing an event in the popup code when it's
 * completely initialized, and then running all this fileupload code when the
 * event has been caught. This is for lack of any JavaScript execution ordering
 * code. A good candidate for this would be starting to use AMD JavaScript,
 * which makes managing dependency and execution control order easier, but it's
 * still a fairly new pattern, and would require a lot of overhaul to fully
 * migrate JS to such a pattern.
 *
 * - https://github.com/amdjs/amdjs-api/wiki/AMD
 * - http://addyosmani.com/resources/essentialjsdesignpatterns/book/#detailamd).
 *
 * For the meantime, this file will check for the availability of the popup
 * function before trying to use it. In addition, it was wrapped in an IIFE,
 * as a way to coerce (creating an execution context) this into the proper
 * execution stack, so it will always execute after the jQuery popup plugin.
 * Read: http://studiokoi.com/blog/article/execution_order_of_functions_and_variables_in_javascript_and_actionscript
 *
 */

var GdnUploaders = null;

(function($){

   function Gdn_Uploaders() {

      this.Uploaders = [];
      this.UploaderID = 0;
      this.MaxUploadSize = 1;
      this.UploaderIndex = 0;
      this.MaxUploadSize = gdn.definition('maxuploadsize');

      Gdn_Uploaders.prototype.Prepare = function () {

         this.isOpera = false;
         this.isIE = false;
         this.ieVersion = 0;
         this.CompatibilityMode = false;

         if (typeof(window.opera) != 'undefined')
            this.isOpera = true;

         if (!this.isOpera && (navigator.userAgent.indexOf('Internet Explorer') >= 0 || navigator.userAgent.indexOf('MSIE')) >= 0)
            this.isIE = true;

         if (this.isIE) {
            this.ieVersion = this.InternetExplorerVersion();
            if (this.ieVersion >=7 && this.ieVersion <= 8)
               this.CompatibilityMode = true;
         }

         var Our = this;
         jQuery('div.AttachmentWindow').each(function(i,AttachmentWindow){
            Our.Spawn(jQuery(AttachmentWindow));
         });
      }

      Gdn_Uploaders.prototype.Spawn = function(AttachmentWindow) {
         if (AttachmentWindow.attr('spawned')) return;

         AttachmentWindow.attr('spawned', true);
         this.Uploaders[this.UploaderIndex] = new Gdn_MultiFileUpload(AttachmentWindow, 'UploadAttachment', this);
         this.Uploaders[this.UploaderIndex].isOpera = this.isOpera;
         this.Uploaders[this.UploaderIndex].isIE = this.isIE;
         this.Uploaders[this.UploaderIndex].ieVersion = this.ieVersion;
         this.Uploaders[this.UploaderIndex].CompatibilityMode = this.CompatibilityMode;
         this.Uploaders[this.UploaderIndex].Apc((gdn.definition('apcavailable')) ? true : false);
         this.Uploaders[this.UploaderIndex].Ready();
         this.UploaderIndex++;
      }

      Gdn_Uploaders.prototype.GetFreshID = function() {
         return ++this.UploaderID;
      }

      Gdn_Uploaders.prototype.GetUniqID = function() {
         var NewDate = new Date();
         return NewDate.getTime();
      }

      // Returns the version of Internet Explorer or a -1
      // (indicating the use of another browser).
      Gdn_Uploaders.prototype.InternetExplorerVersion = function() {
         var rv = -1; // Return value assumes failure.
         if (navigator.appName == 'Microsoft Internet Explorer') {
            var ua = navigator.userAgent;
            var re  = new RegExp("MSIE ([0-9]{1,}[\.0-9]{0,})");
            if (re.exec(ua) != null)
               rv = parseFloat( RegExp.$1 );
         }
         return rv;
      }

   }

   function Gdn_MultiFileUpload(AttachmentWindow, AttachFileRootName, Uploaders) {

      this.Master = Uploaders;
      this.AttachmentWindow = jQuery(AttachmentWindow);
      this.AttachmentWindowHTML = this.AttachmentWindow.html();

      this.AttachFileRootName = AttachFileRootName;

      this.UploaderContainer = null;
      this.IFrameContainer = null;
      this.IFrames = {};
      this.TID = 0;

      this.APC = false;

      Gdn_MultiFileUpload.prototype.Apc = function(ApcStatus) {
         this.APC = ApcStatus;
      }

      /**
       * Prepare the form
       *
       * Create an uploader, create the focus() link
       */
      Gdn_MultiFileUpload.prototype.Ready = function() {

         this.MaxUploadSize = this.Master.MaxUploadSize;
         this.UniqID = this.Master.GetUniqID();

         // Create uploader container
         var UploaderContainer = document.createElement('div');
         var UploaderContainerID = 'ctnr' + Math.floor(Math.random() * 99999);
         UploaderContainer.id = UploaderContainerID;
         jQuery(document.body).append(UploaderContainer);
         this.UploaderContainer = jQuery('#'+UploaderContainerID);

         // Create iframe container
         var IFrameContainer = document.createElement('div');
         var IFrameContainerID = 'frmz' + Math.floor(Math.random() * 99999);
         IFrameContainer.id = IFrameContainerID;
         jQuery(document.body).append(IFrameContainer);
         this.IFrameContainer = jQuery('#'+IFrameContainerID);
         this.IFrameContainer.hide();

         // Allow deletes
         if (jQuery.fn.popup) {
            jQuery('div.Attachments a.DeleteFile').popup({
               confirm: true,
               followConfirm: false,
               deliveryType: 'VIEW',
               afterConfirm: function(json, sender) {
                  var MediaData = json.Delete;
                  var FileRow = jQuery(sender).closest('.Attachment');
                  if (MediaData.Status == 'success') {
                     FileRow.remove();
                  }
               }
            });
         }

         this.Reset();
         jQuery('#'+this.CurrentInput).css('opacity',0);
      }

      Gdn_MultiFileUpload.prototype.Reset = function() {
         this.AttachmentWindow.html(this.AttachmentWindowHTML);

         this.AttachFileLink = this.AttachmentWindow.find('div.AttachFileLink a').first();
         this.FileContainer = this.AttachmentWindow.find('div.AttachFileContainer').first();
         this.CurrentUploader = this.AttachmentWindow.find('div.AttachFileLink div.CurrentUploader').first();

         if (this.CurrentInput) {
            this.ShowUploader(true);
            this.RemoveUploader(this.CurrentInput);
         }

         this.MyFiles = [];
         this.ProgressBars = {};
         this.CurrentInput = null;

         // Create a new uploader
         var UploaderID = this.NewUploader();

         // Attach onClick event to the Attach File button
         var Click = jQuery.proxy(this.ShowUploader, this);
         this.AttachFileLink.click(function(){
            Click();
            return false;
         });
         this.AttachFileLink.parents('form').bind('complete',jQuery.proxy(this.Reset,this));
      }

      Gdn_MultiFileUpload.prototype.CreateElement = function (ElementType, SetOptions) {
         if (this.CompatibilityMode) {
            var ElementString = '<'+ElementType+' ';
            if (SetOptions.name != undefined) ElementString += 'name="'+SetOptions.name+'"';
            if (SetOptions.id != undefined) ElementString += 'id="'+SetOptions.id+'"';
            ElementString += '>';

            var Element = document.createElement(ElementString);
         } else {
            var Element = document.createElement(ElementType);
         }

         for (var prop in SetOptions) {
            var propval = SetOptions[prop];
            Element.setAttribute(prop, propval);

            //if (this.CompatibilityMode) {

            //} else {
            //   Element[prop] = propval;
            //}
         }

         if (ElementType == 'form' && this.CompatibilityMode) {
            if (SetOptions.enctype) {
               encType = Element.getAttributeNode("enctype");
               encType.value = SetOptions.enctype;
            }

            if (SetOptions.method) {
               formMethod = Element.getAttributeNode("method");
               formMethod.value = SetOptions.method;
            }
         }

         return Element;
      }

      Gdn_MultiFileUpload.prototype.NewUploader = function() {
         var NewUploaderID = null; var AutoShow = true;
         if (this.CurrentInput == null)
            AutoShow = false;

         NewUploaderID = this.Master.GetFreshID();
         NewUploaderID = [this.AttachFileRootName,NewUploaderID].join('_');

         var Action = ['post','upload',NewUploaderID];
         var IFrameName = this.NewFrame(NewUploaderID);
         var FormName = IFrameName+'_form';

         var UploaderForm = this.CreateElement('form', {
            'name': FormName,
            'id': FormName,
            'target': IFrameName,
            'enctype': 'multipart/form-data',
            'className': 'FileUpload',
            'method': 'POST',
            'action': gdn.url(Action.join('/'))
         });

         if (this.APC) {
            var APCNotifier = this.CreateElement('input', {
               'type': 'hidden',
               'name': 'APC_UPLOAD_PROGRESS',
               'id': NewUploaderID+'_apckey',
               'value': this.UniqID + '_' + NewUploaderID
            });
            jQuery(UploaderForm).append(APCNotifier);
         }

         var NewUploader = this.CreateElement('input', {
            'type': 'file',
            'name': NewUploaderID,
            'id': NewUploaderID,
            'rel': FormName
         });
         jQuery(UploaderForm).append(NewUploader);

         var MaxUploadSize = this.CreateElement('input', {
            'type': 'hidden',
            'name': 'MAX_UPLOAD_SIZE',
            'value': this.MaxUploadSize
         });
         jQuery(UploaderForm).append(MaxUploadSize);

         this.CurrentUploader.append(UploaderForm);

         this.CurrentInput = NewUploaderID;
         this.ProgressBars[NewUploaderID] = {
            'Target':   IFrameName,
            'Filename': '',
            'TimerID':  0,
            'ApcKey':   this.UniqID + '_' + NewUploaderID,
            'Progress': 0,
            'Size':     0,
            'Complete': false
         };

         if (AutoShow)
            this.ShowUploader(true);
         jQuery('#'+this.CurrentInput).change(jQuery.proxy(this.DispatchCurrentUploader,this));

         return NewUploaderID;
      }


      Gdn_MultiFileUpload.prototype.ShowUploader = function(NoAnimate) {
         var UploaderElement = this.CurrentUploader;

         if (typeof(NoAnimate) == 'object') {
            UploaderElement.animate({
               'height': '24px'
            },300,jQuery.proxy(function(){
               UploaderElement.find('form input[type=file]').css('display', 'block');
               UploaderElement.find('form input[type=file]').animate({
                  'opacity': 1
               });
            },this));
         } else {
            UploaderElement.animate({
               'height': '24px'
            },0,function(){
               UploaderElement.find('form input[type=file]').css('display','block');
               UploaderElement.find('form input[type=file]').css('opacity',1);
            });
         }
      }

      // Create a new named iframe to which our uploads can be submitted
      Gdn_MultiFileUpload.prototype.NewFrame = function(TargetUploaderID) {
         var IFrameName = 'frm'+Math.floor(Math.random() * 99999);
         var ContainerDiv = document.createElement('div');

         var IFrame = this.CreateElement('iframe', {
            'name': IFrameName,
            'id': IFrameName,
            'src': 'about:blank'
         });
         jQuery(IFrame).css('display','none');

         jQuery(ContainerDiv).append(IFrame);
         jQuery(this.IFrameContainer).append(ContainerDiv);

         this.IFrames[IFrameName] = {ready:'no'};

         // Re-target just to be safe
         jQuery('#'+IFrameName).load(jQuery.proxy(function(){ this.UploadComplete(IFrameName,TargetUploaderID); }, this));

         return IFrameName;
      }

      // Submit the form parent of the current uploader and hide the current uploader's input
      Gdn_MultiFileUpload.prototype.DispatchCurrentUploader = function(ChangeEvent) {
         this.UploaderContainer.append(jQuery('form#'+jQuery('#'+this.CurrentInput).attr('rel')));

         var Target = jQuery(ChangeEvent.target);
         jQuery('#'+Target.attr('rel')).append(Target);
         var UploaderID = Target.attr('id');
         this.RememberFile(Target);
         var IFrameName = Target.parent().attr('target');
         this.IFrames[IFrameName].ready = 'yes';

         Target.parent().submit();
         this.NewUploader();
      }

      Gdn_MultiFileUpload.prototype.RememberFile = function(FileInput) {
         var FileName = FileInput.val();

         FileName = FileName.split('/').pop();
         FileName = FileName.split('\\').pop();

         var UploaderID = FileInput.attr('id');
         this.ProgressBars[UploaderID].Filename = FileName

         // Handle the control (remove style, hide)
         FileInput.attr('style','');
         FileInput.hide();

         // Handle the file list UI
         var PrototypeFileAttachment = jQuery(this.FileContainer.find('div.PrototypicalAttachment')[0]).clone();
         var FileNameDiv = jQuery(PrototypeFileAttachment).find('.FileName');
         var FileSizeDiv = jQuery(PrototypeFileAttachment).find('.FileSize');
         var ProgressDiv = jQuery(PrototypeFileAttachment).find('.UploadProgress');
         jQuery(FileNameDiv).html(FileName);
         jQuery(FileSizeDiv).html('');
         jQuery(jQuery(ProgressDiv).find('div.Background')).css('width','0px');

         var FileListingID = [FileInput.attr('id'),'listing'].join('_');
         PrototypeFileAttachment.attr('id', FileListingID);
         PrototypeFileAttachment.css('display', 'inline-block');
         PrototypeFileAttachment.appendTo(this.FileContainer);
         // PrototypeFileAttachment.css('display','table-row');

         this.Progress(FileInput.attr('id'));

         // Return the old ID
         return UploaderID;
      }

      Gdn_MultiFileUpload.prototype.Progress = function(Data, ResponseStatus, XMLResponse) {
         if (!this.APC) return;
         var ExecuteApcLookup = this.APC;

         if (this.ProgressBars[Data]) {
            var ApcKey = this.ProgressBars[Data].ApcKey;
            var Progress = this.ProgressBars[Data].Progress;

            var UploaderID = Data;
         } else {
            if (!Data) return;

            var JData = jQuery.parseJSON(Data);
            if (JData && JData.Progress) {

               var JProgress = JData.Progress;
               var UploaderID = JProgress.uploader;

               if (!this.ProgressBars[UploaderID]) return;
               if (this.ProgressBars[UploaderID].Complete == true) return;

               if (JProgress.apc == 'no') {
                  return;
               }

               var Progress = JProgress.progress;
               this.ProgressBars[UploaderID].Progress = Progress;
               this.ProgressBars[UploaderID].Total = JProgress.total;

               var FileListing = jQuery('#'+UploaderID+'_listing');

               // Update the filesize
               if (JProgress.total != null && JProgress.total != -1) {
                  jQuery(FileListing.find('.FileSize')).html(JProgress.format_total);
               }

               // Update progress bar
               if (!this.ProgressBars[UploaderID].Complete) {
                  var UploadProgress = FileListing.find('div.UploadProgress');
                  var ProgressForeground = FileListing.find('div.UploadProgress div.Foreground');
                  var ProgressBackground = FileListing.find('div.UploadProgress div.Background');
                  ProgressForeground.html('<strong>Uploading:</strong> ' + Math.ceil(Progress)+'%');
                  ProgressBackground.css('width', ((Progress * jQuery(UploadProgress).width()) / 100)+'px');
                  // if (Progress >= 15)
                  //    ProgressBar.html(Math.ceil(Progress)+'%');
               }

            }

            if (ExecuteApcLookup) {
               // Wait 100 MS and then trigger another request
               Progress = parseInt(Progress);
               if ((!this.ProgressBars[UploaderID].Complete && Progress < 100) || (this.ProgressBars[UploaderID].Complete && Progress <= 0)) {
                  this.TID = this.ProgressBars[UploaderID].TimerID = setTimeout(jQuery.proxy(function(){ this.Progress(UploaderID); }, this), 100);
               }
            }

            return;
         }

         if (ExecuteApcLookup) {
            var Action = ['post','checkupload',ApcKey,this.ProgressBars[UploaderID].Progress];
            var FinalURL = gdn.url(Action.join('/')+'?randval='+Math.random());

            jQuery.ajax({
               url:FinalURL,
               type:'GET',
               async:true,
               //data:{'Previous':Progress},
               success:jQuery.proxy(this.Progress, this)
            });
         }
      }

      Gdn_MultiFileUpload.prototype.UploadComplete = function(IFrameName, TargetUploaderID) {
         if (this.IFrames[IFrameName].ready != 'yes') {
            this.IFrames[IFrameName].ready = 'yes';
            return;
         }

         var IFR = document.getElementById(IFrameName);
         var Response = IFR.contentWindow.document.body.innerHTML;

         var UploadResultStatus = 'fail';
         var FailReason = 'An unknown error occurred.';

         var JResponse = jQuery.parseJSON(Response);
         if (JResponse && JResponse.MediaResponse) {

            if (JResponse.MediaResponse.Status == 'success') {
               UploadResultStatus = 'success';
               // SUCCESS

               if (!this.ProgressBars[TargetUploaderID]) return;

               var Filename = JResponse.MediaResponse.Filename;

               var StoredFilename = this.ProgressBars[TargetUploaderID].Filename;
               if (StoredFilename != Filename) return;

               var MediaID = JResponse.MediaResponse.MediaID;
               this.ProgressBars[TargetUploaderID].Complete = true;
               this.MyFiles[MediaID] = Filename;
               this.RemoveUploader(TargetUploaderID);

               var EnableMe = this.CreateElement('input', {
                  'type': 'hidden',
                  'name': 'AttachedUploads[]',
                  'value': MediaID
               });

               var TrackAll = this.CreateElement('input', {
                  'type': 'hidden',
                  'name': 'AllUploads[]',
                  'value': MediaID
               });

               var FileListing = jQuery('#'+[TargetUploaderID,'listing'].join('_'));

               // Update the filesize
               if (JResponse.MediaResponse.Filesize != null) {
                  jQuery(FileListing.find('.FileSize')).html(JResponse.MediaResponse.FormatFilesize);
               }

               jQuery(FileListing.find('.FileOptions')).append(EnableMe);

               // FIX IE
               if (this.isIE)
                  EnableMe.checked = 'true';

               // Make the file a link
               jQuery(FileListing.find('.FileName')).text(Filename);

               jQuery(FileListing.find('.FileOptions')).append(TrackAll);
               jQuery(FileListing.find('div.UploadProgress')).remove();

               // Add image preview.
               var thumbnailHtml = jQuery.base64Decode(JResponse.MediaResponse.Thumbnail);

               jQuery(FileListing.find('div.FilePreview')).append(thumbnailHtml);
               jQuery(FileListing.find('div.FileHover')).prepend(thumbnailHtml);

               // Add "insert image" button
               if (JResponse.MediaResponse.FinalImageLocation != '') {
                  var ImageAnchor = jQuery(FileListing.find('a.InsertImage'));
                  ImageAnchor.attr('href', JResponse.MediaResponse.FinalImageLocation);
                  ImageAnchor.show();
                  $(document).on('click', ImageAnchor.selector, function() {
                     var insertimg = '<img src="'+ImageAnchor.attr('href')+'" />';
                     // Test if we're working with CLEditor
                     var wysiwyg = jQuery(FileListing.parents('form').find('iframe'));
                     if (wysiwyg) {
                        // Insert into WYSIWYG iframe
                        var editorbox = wysiwyg.contents().find('body');
                        editorbox.html(function(index, oldhtml) { return oldhtml+insertimg });
                     }
                     else {
                        // Normal textarea (no WYSIWYG)
                        var txtbox = jQuery(FileListing.parents('form').find('textarea'));
                        txtbox.val(txtbox.val()+insertimg);
                     }
                     return false;
                  });
               }

               // Add delete button
               var DeleteAnchor = jQuery(FileListing.find('a.DeleteFile'));
               var DeleteHref = gdn.definition('WebRoot') + 'plugin/fileupload/delete/' + MediaID;
               $(document).on('click', DeleteAnchor.selector, function() {
                  // Delete file
                  jQuery.ajax({url: DeleteHref, type: 'GET'});
                  // Remove image
                  jQuery(FileListing).remove();
                  // Stay here
                  return false;
               });

            } else {
               // FAILURE
               FailReason = JResponse.MediaResponse.StrError;
               this.ProgressBars[TargetUploaderID].Complete = true;

            }
         }

         if (UploadResultStatus == 'fail') {
            clearTimeout(this.ProgressBars[TargetUploaderID].TimerID);
            this.RemoveUploader(TargetUploaderID);

            gdn.informError(FailReason);

            var FileListing = jQuery('#'+[TargetUploaderID,'listing'].join('_'));
            FileListing.remove();

            delete this.ProgressBars[TargetUploaderID];
         }
      }

      Gdn_MultiFileUpload.prototype.RemoveUploader = function(UploaderID) {
         var TargetFrame = jQuery('#'+this.ProgressBars[UploaderID].Target);
         var TargetForm = jQuery('#'+this.ProgressBars[UploaderID].Target+'_form');

         TargetFrame.parent().remove();
         TargetForm.remove();

         // If a progress request is pending, cancel it
         //clearTimeout(this.ProgressBars[UploaderID].TimerID);
      }

      Gdn_MultiFileUpload.prototype.Stop = function() {
         clearTimeout(this.TID);
      }

   }

   jQuery(document).ready(function($){
      GdnUploaders = new Gdn_Uploaders();
      GdnUploaders.Prepare();

      $(document).on('click', '.Attachment .InsertImage', function() {
         var txtbox = jQuery(this).closest('form').find('textarea');
         txtbox.val(txtbox.val()+'<img src="'+jQuery(this).attr('href')+'" />');
         return false;
      });

      $('.Comment a.EditComment').click(function() {
         $('.Comment > .Attachments', $(this).closest('.Item')).remove();
      });
   });
}(jQuery));
