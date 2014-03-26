<?php if (!defined('APPLICATION')) exit(); ?>
<table class="AltColumns">
	<?php
   $Header = array();
   $ImportPaths = $this->Data('ImportPaths');
   if (is_array($ImportPaths))
      $Filename = GetValue($this->Data('ImportPath'), $ImportPaths);
   else
      $Filename = '';
   //$Filename = GetValue('OriginalFilename', $this->Data);
   if($Filename)
      $Header[T('Source')] = $Filename;

   $Header = array_merge($Header, (array)GetValue('Header', $this->Data, array()));
   $Stats = (array)GetValue('Stats', $this->Data, array());
   $Info = array_merge($Header, $Stats);
	foreach($Info as $Name => $Value) {
      if(substr_compare('Time', $Name, 0, 4, TRUE) == 0)
         $Value = Gdn_Timer::FormatElapsed($Value);


		$Name = htmlspecialchars($Name);
		$Value = htmlspecialchars($Value);

		echo "<tr><th>$Name</th><td class=\"Alt\">$Value</td></tr>\n";
	}

   if ($this->Data('GenerateSQL')) {
      echo "<tr><th>".T('Special')."</th><td class=\"Alt\">".T('Generate import SQL only')."</td></tr>\n";
   }
	?>
</table>