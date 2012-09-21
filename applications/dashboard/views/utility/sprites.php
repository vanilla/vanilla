<h1>Sprite Sheet</h1>

<style>
   .But {
      display: inline-block;
      font-size: 11px;
      line-height: 16px;
      min-width: 125px;
   }
   
   .But .Label {
      
   }
</style>

<div class="SpriteSheet">
<?php
$Rows = 7;
$Cols = 18;

for ($r = 0; $r < $Rows; $r++) {
   for($c = 0; $c < $Cols; $c++) {
      $x = ($c + 1) * 20;
      $y = ($r + 1) * 20;
      
      $Pos = "-{$x}px -{$y}px";
      $Style = "background-position: $Pos";
      
      echo ' <a class="But">';
      
      echo '<span class="Sprite16" style="'.$Style.'"></span> ';
      
      echo '<span class="Label">';
      echo $Pos;
      echo '</span>';
      
      echo '</a> ';
   }
}

?>
</div>
