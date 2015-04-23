<?php if (!defined('APPLICATION')) exit(); ?>
<div class="FormTitleWrapper">
<?php
   echo $this->Form->Open();

   // Normalize no image config setting
   if (C('Plugins.Signatures.MaxNumberImages') === 0 || C('Plugins.Signatures.MaxNumberImages') === '0') {
      SaveToConfig('Plugins.Signatures.MaxNumberImages', 'None');
   }


?>
<span class="page-title"><h1 class="H"><?php echo T('Signatures'); ?></h1></span>
<h2 class="H self-clearing"><?php echo T('My Signature'); ?></h2>
   <?php echo $this->Form->Errors(); ?>
   <ul>
      <?php
      if (isset($this->Data['Plugin-Signatures-ForceEditing']) && $this->Data['Plugin-Signatures-ForceEditing'] != FALSE) {
         ?>
         <div class="Warning"><?php echo sprintf(T("You are editing %s's signature"),$this->Data['Plugin-Signatures-ForceEditing']); ?></div>
      <?php
      }
      ?>

      <li>
         <?php if (!C('Plugins.Signatures.AllowEmbeds', true)): ?>
            <div class="Info">
               <?php echo T('Video embedding has been disabled.', 'Video embedding has been disabled. URLs will not translate to their embedded equivalent.'); ?>
            </div>
         <?php endif; ?>
      </li>
      <li>
         <?php
            if ($this->Data('CanEdit')) {
               if ($this->Data('SignatureRules')) {
                  ?>
                  <div class="SignatureRules">
                     <?php echo $this->Data('SignatureRules'); ?>
                  </div>
                  <?php
               }

               echo $this->Form->BodyBox('Body');
   //            echo Wrap($this->Form->TextBox('Plugin.Signatures.Sig', array('MultiLine' => TRUE)), 'div', array('class' => 'TextBoxWrapper'));
            } else {
               echo T("You don't have permission to use a signature.");
            } ?>
      </li>
   </ul>


   <?php
      $this->FireEvent('EditMySignatureAfter');
   ?>
</ul>
<h2 class="H self-clearing"><?php echo T('Forum Signature Settings'); ?></h2>
<ul>
   <li>
      <?php
      echo $this->Form->CheckBox('Plugin.Signatures.HideAll','Hide signatures always');
      echo $this->Form->CheckBox('Plugin.Signatures.HideMobile',"Hide signatures on my mobile device");
      echo $this->Form->CheckBox('Plugin.Signatures.HideImages','Strip images out of signatures');
      ?>
   </li>
</ul>
<?php echo $this->Form->Close('Save', '', array('class' => 'button Button Primary')); ?>
</div>
