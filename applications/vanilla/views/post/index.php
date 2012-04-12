<?php if (!defined('APPLICATION')) exit(); ?>
<script type="text/javascript">
jQuery(document).ready(function($) {
   $('.FormType a').click(function() {
		$(this).parents('.FormType').find('li').removeClass('Active'); // Make all buttons inactive
		var item = $(this).parents('li'); // Identify the clicked container
		var formType = item.attr('class'); // Identify the clicked form type
		item.addClass('Active'); // Make the clicked form button active
		$('.FormWrap').hide(); // Hide all forms
		$('.'+formType).show(); // Reveal the selected form
      return false;
   });
});
</script>
<?php
$Forms = $this->Data('Forms');
// Loop through the form collection and write out the handles
echo '<ul class="FormType">';
foreach ($Forms as $Form) {
	$Name = GetValue('Name', $Form);
	$Active = strtolower($Name) == strtolower($this->Data('CurrentFormName'));
	$CssClass = 'Type-'.$Name;
	if ($Active)
		$CssClass .= ' Active';
	echo '<li class="'.$CssClass.'">';
		echo Anchor(
			Wrap(GetValue('Label', $Form), 'strong'),
			GetValue('Url', $Form)
		);
	echo '</li>';
}
echo '</ul>';

// Now loop through the form collection and dump the forms
foreach ($Forms as $Form) {
	$Name = GetValue('Name', $Form);
	$Active = strtolower($Name) == strtolower($this->Data('CurrentFormName'));
	$Url = GetValue('Url', $Form);
	echo '<div class="FormWrap Type-'.$Name.' '.($Active ? 'Active' : 'Hidden').'">';
		// echo ProxyRequest(Url($Url.'?DeliveryType=VIEW', TRUE));
		echo '<div class="Popin" rel="'.Url($Url.'?DeliveryType=VIEW', TRUE).'"></div>';
	echo '</div>';
}
