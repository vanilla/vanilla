<div class="Hero Hero-Bans">
   <div class="Message">
      <?php
      echo T($this->Data('Summary'));
      ?>
      <ul>
         <?php foreach ($this->Data('Reasons', array()) as $Reason) {?>
            <li><?php echo $Reason; ?></li>
         <?php } ?>
      </ul>
   </div>
</div>
