<?php if (!defined('APPLICATION')) exit();
require_once Gdn::controller()->fetchViewLocation('helper_functions', 'modules', 'vanilla');

?>
    <h1 class="H"><?php echo $this->data('Title'); ?></h1>
<?php

if ($data = $this->data('Content')) {
    if ($view = $this->data('View') == 'table') {
        writePromotedContentTable($data);
    } else {
        writePromotedContentList($data);
    }
} else {
    echo $this->data('EmptyMessage');
}
