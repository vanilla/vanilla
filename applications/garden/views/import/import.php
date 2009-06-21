<?php if (!defined('APPLICATION')) exit();

echo $this->Form->Open();
echo $this->Form->Errors();
?>
<ul>
   <li>
      <?php
         echo $this->Form->Label('Vanilla 1 DB Prefix', 'SourcePrefix');
         echo $this->Form->Input('SourcePrefix');
      ?>
   </li>
</ul>
<?php echo $this->Form->Close('Start the Data Import');