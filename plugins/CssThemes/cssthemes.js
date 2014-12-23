jQuery(document).ready(function($) {
	
	$foo = $('.ColorPicker');
	
	$('.ColorPicker').ColorPicker({
		onBeforeShow: function(colorPicker) {
			colorPicker.Target = this;
			$this = $(this);
			
			color = $this.css("backgroundColor");
			// Convert rgb syntaxt to format for color picker.
			rgb = /rgb\s*\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)\s*\)/.exec(color);
			if(rgb) {
				color = {r: rgb[1], g: rgb[2], b: rgb[3]};
			}
			
			$this.ColorPickerSetColor(color);
		},
		onChange: function(hsb, hex, rgb, el) {
			$target = $($(this).attr("Target"));
			$target.css("backgroundColor", "#" + hex);
			$("input:text", $target.parent().parent()).val("#" + hex);
		}
	});
	
	$(".Setting").blur(function(sender) {
		color = $(this).val();
		$(".ColorPicker", $(this).parent().parent()).css("backgroundColor", color);
	});
});