<?php if (!defined("APPLICATION")) exit(); 
/*
 *  Vanoogle vanilla plugin.
 *  Copyright (C) 2011 ddumont@gmail.com
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>. 
 */?>
<h1> <?php echo $this->Data("Title");?> </h1>
<?php
	echo $this->Form->Open();
	echo $this->Form->Errors();
?>
<ul>
	<li>
		<h3><?php echo T("Required Settings"); ?></h3>
		<ul class='CheckBoxList'>
			<li>
				<?php
					echo $this->Form->Label("Enter your Custom Search Engine ID.  If you don't have one, create one here: <a target='_blank' href='http://www.google.com/cse/'>http://www.google.com/cse/", "Plugins.Vanoogle.CSE", array(
						"class" => "CheckBoxLabel",
					));
					echo "<br>"; 
					echo $this->Form->Input("Plugins.Vanoogle.CSE", "input", array(
						"size" => "40",
						"style" => "font-family: Courier, 'Courier New', monospace;", 
					)); 
				?>
			</li>
		</ul>
	</li>
</ul>
<br>

<?php 
   echo $this->Form->Close("Save");

