<?php if (!defined('APPLICATION')) exit();

echo $this->Form->Open();
echo $this->Form->Errors();
?>

<h1>Colors</h1>
<div id="CssThemes">
<?php
$LastHue = "";
$R = $G = $B = $Count = 0;
foreach($this->Colors as $Color => $HSV) {
	$Hue = substr($HSV, 0, 4);
	if($Hue != $LastHue) {
		if($LastHue) {
			$R /= $Count;
			$G /= $Count;
			$B /= $Count;
			$AvgColor = sprintf("%02s%02s%02s", dechex($R), dechex($G), dechex($B));
			
			echo "<span style='background: #$AvgColor' class='ColorPicker'>$AvgColor</span>";
			echo "<div style='clear: left'></div></div>";
			
			$R = $G = $B = $Count = 0;
		}
		echo "\n<div class='Box'> \n";
		$LastHue = $Hue;
	}
	$RGB = array(hexdec(substr($Color, 0, 2)), hexdec(substr($Color, 2, 2)), hexdec(substr($Color, 4, 2)));
	$R += $RGB[0];
	$G += $RGB[1];
	$B += $RGB[2];
	$Count ++;
	echo "<span style='background: #$Color' class='ColorPicker'>$HSV<br />$Color</span>";
}
echo "</div>";
?>
</div>