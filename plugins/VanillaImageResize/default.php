<?php if (!defined('APPLICATION')) die();

$PluginInfo['VanillaImageResize'] = array(
    'Description' => 'Resize large images to fit browser widths.',
    'Version' => '1.0',
    'Author' => 'Vanilla',
    'AuthorEmail' => 'vanilla@vanillaforums.org',
    'AuthorUrl' => 'http://vanillaforums.org'
);

class vanillaImageResizePlugin extends Gdn_Plugin
{
  public function Base_Render_Before(&$Sender)
  {
    $Sender->AddJsFile($this->GetResource('js/VanillaImageResize.js', FALSE, FALSE));
  }

  public function Setup()
  {
    // Intentionally left blank
  }
}

