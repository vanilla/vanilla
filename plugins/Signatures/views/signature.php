<?php if (!defined('APPLICATION')) exit(); ?>
<div class="FormTitleWrapper">
<?php
   echo $this->Form->open();
?>
<h1 class="H"><?php echo t('Signatures'); ?></h1>
<h2 class="H"><?php echo t('My Signature'); ?></h2>
   <?php echo $this->Form->errors(); ?>
   <ul>
      <?php
      if (isset($this->Data['Plugin-Signatures-ForceEditing']) && $this->Data['Plugin-Signatures-ForceEditing'] != false) {
         ?>
         <div class="Warning"><?php echo sprintf(t("You are editing %s's signature"),$this->Data['Plugin-Signatures-ForceEditing']); ?></div>
      <?php
      }
      ?>

      <li>
         <?php if (!c('Signatures.Allow.Embeds', true)): ?>
            <div class="Info">
               <?php echo t('Video embedding has been disabled.', 'Video embedding has been disabled. URLs will not translate to their embedded equivalent.'); ?>
            </div>
         <?php endif; ?>
      </li>
      <li>
         <?php
            if ($this->data('CanEdit')) {
               if ($this->data('SignatureRules')) {
                  ?>
                  <div class="SignatureRules">
                     <?php echo $this->data('SignatureRules'); ?>
                  </div>
                  <?php
               }

               echo $this->Form->bodyBox('Body', ['ImageUpload' => true]);
            } else {
               echo t("You don't have permission to use a signature.");
            } ?>
      </li>
   </ul>


   <?php
      $this->fireEvent('EditMySignatureAfter');
   ?>
</ul>
<h2 class="H"><?php echo t('Forum Signature Settings'); ?></h2>
<ul>
   <li>
      <?php
      echo $this->Form->checkBox('Plugin.Signatures.HideAll','Hide signatures always');
      if (!c('Signatures.Hide.Mobile', true)) {
         echo $this->Form->checkBox('Plugin.Signatures.HideMobile',"Hide signatures on my mobile device");
      }
      echo $this->Form->checkBox('Plugin.Signatures.HideImages','Strip images out of signatures');
      ?>
   </li>
</ul>
<?php echo $this->Form->close('Save', '', ['class' => 'Button Primary']); ?>
</div>
