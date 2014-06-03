<?php
/**
 * @copyright 2009-2014 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
 */

if (!function_exists('WriteAttachment')) {
   /**
    * Renders attachments.  Checks for error key and if present will display error using WriteErrorAttachment.
    *
    * @param array $Attachment Attachment
    * @return string
    */
   function WriteAttachment($Attachment) {

      $customMethod = AttachmentModel::GetWriteAttachmentMethodName($Attachment['Type']);
      if (function_exists($customMethod)) {
         if (GetValue('Error', $Attachment)) {
            WriteErrorAttachment($Attachment);
            return;
         }
         $customMethod($Attachment);
      } else {
         Trace($customMethod, 'Write Attachment method not found');
         Trace($Attachment, 'Attachment');
      }
      return;
   }

}

if (!function_exists('WriteAttachments')) {
   function WriteAttachments($Attachments) {
      foreach ($Attachments as $Attachment) {
         ?>
         <div class="item-attachments">
            <?php WriteAttachment($Attachment); ?>
         </div>
      <?php
      }
   }
}

if (!function_exists('WriteSkeletonAttachment')) {
      function WriteSkeletonAttachment($Attachment) {
      ?>
      <div class="item-attachment">
      <div class="alert">
         <div class="media item">
            <div class="pull-left">
               <div class="media-object">
                  <i class="icon icon-tag"></i>
               </div>
            </div>
            <div class="media-body">

               <div class="item-header">
                  <h4 class="media-heading item-heading">Heading
                     <div class="item-meta">
                        <span>heading</span>
                     </div>
                  </h4></div>



               <div class="item-body">

                  <dl class="dl-columns">
                     <dt>Name 1</dt>
                     <dd>Value 1</dd>
                     <dt>Name 2</dt>
                     <dd>Value 2</dd>
                     <dt>Name 3</dt>
                     <dd>Value 3</dd>
                     <dt>Name 4</dt>
                     <dd>Value 4</dd>

                  </dl>
               </div>

            </div>
         </div>
      </div>
   </div>
   <?php
   }
}

if (!function_exists('WriteErrorAttachment')) {
   /**
    * Given a parsed attachment, render it in HTML
    *
    * @param array $Attachment
    * @return string
    */
   function WriteErrorAttachment($Attachment) {
      WriteGenericAttachment(array(
            'Type' => 'Warning',
            'Icon' => 'warning-sign',
            'Body' => $Attachment['Error']
         ));
   }
}

if (!function_exists('WriteGenericAttachment')) {
   /**
    * Given a parsed attachment, render it in HTML
    *
    * @param array $Attachment
    * @return string
    */
   function WriteGenericAttachment($Attachment) {
      $Type = GetValue('Type', $Attachment);
      $Icon = GetValue('Icon', $Attachment, 'sign-blank');
      $Title = GetValue('Title', $Attachment);
      $Meta = GetValue('Meta', $Attachment);
      $Body = GetValue('Body', $Attachment);
      $Fields = GetValue('Fields', $Attachment);

      ?>
      <div class="item-attachment">
         <div class="alert<?php if ($Type) echo ' alert-'.strtolower($Type); ?>">
            <div class="media item">
               <div class="pull-left">
                  <div class="media-object">
                     <i class="icon icon-<?php echo $Icon; ?>"></i>
                  </div>
               </div>
               <div class="media-body">
                  <?php if ($Title || $Meta): ?>

                     <div class="item-header">
                        <?php if ($Title): ?>
                        <h4 class="media-heading item-heading"><?php echo Gdn_Format::Html($Title); ?>
                           <?php endif; ?>

                           <?php if ($Meta): ?>
                              <div class="item-meta">
                                 <?php foreach ($Meta as $Item): ?>
                                    <span><?php echo Gdn_Format::Html($Item); ?></span>
                                 <?php endforeach; ?>
                              </div>
                           <?php endif; ?>
                     </div>

                  <?php endif; ?>

                  <?php if ($Body || $Fields): ?>

                     <div class="item-body">
                        <?php if ($Body): ?>
                           <?php echo Gdn_Format::Html($Body); ?>
                        <?php endif; ?>

                        <?php if ($Fields): ?>
                           <dl class="dl-columns">
                              <?php foreach ($Fields as $Title => $Field): ?>
                                 <dt><?php echo T($Title); ?></dt>
                                 <dd><?php echo Gdn_Format::Html($Field); ?></dd>
                              <?php endforeach; ?>
                           </dl>
                        <?php endif; ?>
                     </div>

                  <?php endif; ?>
               </div>
            </div>
         </div>
      </div>
   <?php
   }
}
