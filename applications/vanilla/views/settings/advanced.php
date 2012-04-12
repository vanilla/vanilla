<?php if (!defined('APPLICATION')) exit(); ?>
<div class="Help Aside">
   <?php
   echo Wrap(T('Need More Help?'), 'h2');
   echo '<ul>';
   echo Wrap(Anchor(T("Video tutorial on advanced settings"), 'settings/tutorials/category-management-and-advanced-settings'), 'li');
   echo '</ul>';
   ?>
</div>
<h1><?php echo T('Advanced'); ?></h1>
<?php
echo $this->Form->Open();
echo $this->Form->Errors();
?>
<ul>
   <li>
      <?php
         $Options = array('10' => '10', '15' => '15', '20' => '20', '25' => '25', '30' => '30', '40' => '40', '50' => '50', '100' => '100');
         $Fields = array('TextField' => 'Code', 'ValueField' => 'Code');
         echo $this->Form->Label('Discussions per Page', 'Vanilla.Discussions.PerPage');
         echo $this->Form->DropDown('Vanilla.Discussions.PerPage', $Options, $Fields);
      ?>
   </li>
   <li>
      <?php
         echo $this->Form->Label('Comments per Page', 'Vanilla.Comments.PerPage');
         echo $this->Form->DropDown('Vanilla.Comments.PerPage', $Options, $Fields);
      ?>
   </li>
   <li>
      <?php
         $Options = array('0' => T('Authors may never edit'),
                        '350' => sprintf(T('Authors may edit for %s'), T('5 minutes')), 
                        '900' => sprintf(T('Authors may edit for %s'), T('15 minutes')), 
                       '3600' => sprintf(T('Authors may edit for %s'), T('1 hour')),
                      '14400' => sprintf(T('Authors may edit for %s'), T('4 hours')),
                      '86400' => sprintf(T('Authors may edit for %s'), T('1 day')),
                     '604800' => sprintf(T('Authors may edit for %s'), T('1 week')),
                    '2592000' => sprintf(T('Authors may edit for %s'), T('1 month')),
                         '-1' => T('Authors may always edit'));
         $Fields = array('TextField' => 'Text', 'ValueField' => 'Code');
         echo $this->Form->Label('Discussion & Comment Editing', 'Garden.EditContentTimeout');
         echo $this->Form->DropDown('Garden.EditContentTimeout', $Options, $Fields);
			echo Wrap(T('EditContentTimeout.Notes', 'If a user is in a role that has permission to edit content, those permissions will override this.'), 'div', array('class' => 'Info'));
      ?>
   </li>
<!--   <li>
      <?php
         $Options2 = array('0' => T('Never - Users Must Refresh Page'), 
                           '5' => T('Every 5 seconds'),
                          '10' => T('Every 10 seconds'),
                          '30' => T('Every 30 seconds'),
                          '60' => T('Every 1 minute'),
                         '300' => T('Every 5 minutes'));
         echo $this->Form->Label('Auto-Fetch New Comments', 'Vanilla.Comments.AutoRefresh');
         echo $this->Form->DropDown('Vanilla.Comments.AutoRefresh', $Options2, $Fields);
      ?>
   </li>-->
   <li>
      <?php
         echo $this->Form->Label('Archive Discussions', 'Vanilla.Archive.Date');
			echo '<div class="Info">',
				T('Vanilla.Archive.Description', 'You can choose to archive forum discussions older than a certain date. Archived discussions are effectively closed, allowing no new posts.'),
				'</div>';
         echo $this->Form->Calendar('Vanilla.Archive.Date');
			echo ' '.T('(YYYY-mm-dd)');
      ?>
   </li>
	<li>
      <?php
         echo $this->Form->CheckBox('Vanilla.Archive.Exclude', 'Exclude archived discussions from the discussions list');
      ?>
   </li>
   <li>
      <?php
         echo $this->Form->CheckBox('Vanilla.AdminCheckboxes.Use', 'Enable admin checkboxes on discussions and comments.');
      ?>
   </li>
</ul>
<?php echo $this->Form->Close('Save');