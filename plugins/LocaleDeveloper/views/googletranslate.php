<div class="Info" "id="google_translate_element"></div><script>
function googleTranslateElementInit() {
  new google.translate.TranslateElement({
    pageLanguage: 'en',
    autoDisplay: false,
    multilanguagePage: true,
    layout: google.translate.TranslateElement.InlineLayout.HORIZONTAL
  }, 'google_translate_element');
}
</script><script src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>
<?php

$Definitions = (array)$this->Data('Definitions', array());

foreach ($Definitions as $Code => $Translation) {
   echo '<div class="Definition" Code="'.urlencode($Code).'">',
      htmlspecialchars($Translation),
      "</div>\n";
}