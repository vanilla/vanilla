<?php if (!defined('APPLICATION')) exit();?>

<?php echo $this->Form->Open(); ?>
<style type="text/css">
   textarea.TextBox { height: 22px; min-height: 22px; width: 600px; }
   ul input.InputBox { width: 600px; }
   #Form_Go { margin: 0 20px !important; }
   .Popular { border: 2px dotted #ccc; border-width: 2px 0; padding: 20px; }
   .Modified { background: #E3FFE6; }
</style>
<script type="text/javascript" language="javascript">
jQuery(document).ready(function($) {
	if ($.autogrow)
      $('textarea.TextBox').autogrow();
		
	$('.Popular a').click(function() {
		$('input[name$=Keywords]').val($(this).attr('term'));
		
		$('form').submit();
		return false;
	});
});
</script>

<h1>Customize Text</h1>
<div class="Info">
   <?php
      echo $this->Form->Errors();
      echo 'Search for the text you want to customize. Partial searches work. For example: "disc" will return "discussion" and "all discussions", etc. ';
		echo 'There are currently '.Wrap($this->Data('CountDefinitions'), 'strong').' definitions available for editing. ';
		echo Anchor('Find More', '/settings/customizetext/rebuild', 'SmallButton');
	?>
</div>
<div class="Popular">
	Popular Searches: <a term="howdy" href="#">Howdy Stranger</a>, <a term="module" href="#">It looks like you're new here...</a>, <a term="disc" href="#">Discussions</a>, <a term="comment" href="#">Comments</a>, <a term="email" href="#">Email</a>, <a term="*" href="#">Everything</a>.
</div>
<div class="Info">
	<?php
      echo $this->Form->TextBox('Keywords');
      echo $this->Form->Button(T('Go'));
   ?>
</div>
<?php
if ($this->Form->GetValue('Keywords', '') != '') {
	echo '<h3>';
	printf(T("%s matches found for '%s'."), $this->Data('CountMatches'), $this->Form->GetValue('Keywords'));
	echo '</h3>';
	echo '<ul>';
   
	foreach ($this->Data('Matches') as $Key => $Definition) {
      $KeyHash = md5($Key);
      
      $DefinitionText = $Definition['def'];
      $DefinitionModified = (bool)$Definition['mod'];
      $ElementName = "def_{$KeyHash}";
      
      $CSSClass = "TextBox Definition";
      if ($DefinitionModified)
         $CSSClass .= " Modified";
      
		echo '<li>';
		echo Wrap(Gdn_Format::Text($Key), 'label', array('for' => "Form_{$ElementName}"));
      
      if ($this->Form->IsPostBack()) {
         $SuppliedDefinition = $this->Form->GetValue($ElementName);
      
         // Changed?
         if ($SuppliedDefinition !== FALSE && $SuppliedDefinition != $DefinitionText)
            if (!$DefinitionModified) $CSSClass .= " Modified";
      }
      
      echo $this->Form->TextBox($ElementName, array('multiline' => TRUE, 'class' => $CSSClass));
		echo '</li>';
	}
	echo '</ul>';
	echo $this->Form->Button('Save All');
}
echo $this->Form->Close();