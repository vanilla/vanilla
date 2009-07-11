<?php if (!defined('APPLICATION')) exit();

echo $this->Form->Open();
?>
<div class="Title">
   <h1><span>Vanilla</span></h1>
   <h2><?php echo Gdn::Translate("Vanilla 1 to Vanilla 2 Import"); ?></h2>
</div>
<?php
echo $this->Form->Errors();
?>
<ul>
   <li class="Warning">
      <div>
      <?php
         echo Gdn::Translate('A little more info before we grab your old discussions...');
      ?>
      </div>
   </li>
   <li class="Last">
      <?php
         echo $this->Form->Label('Vanilla 1 Database Prefix', 'SourcePrefix');
         echo $this->Form->Input('SourcePrefix');
      ?>
   </li>
</ul>
<div class="Button">
   <?php echo $this->Form->Button('Start the Data Import'); ?>
</div>
<?php echo $this->Form->Close();