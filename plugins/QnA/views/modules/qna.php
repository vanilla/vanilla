<?php if (!defined('APPLICATION')) exit();

require_once PATH_APPLICATIONS . '/vanilla/views/discussions/helper_functions.php';

$discussions = $this->data('discussions');

$title = $this->data('title');
if ($title) {
    echo "<h2>" . htmlspecialchars($title) .  "</h2>";
}

if (count($discussions) > 0 ) {
    echo '<ul class="DataList Discussions">';
    foreach ($discussions as $discussion) {
        writeDiscussion($discussion, Gdn::controller(), Gdn::session());
    }
    echo '</ul>';
} else {
    echo '<div class="Empty">'.t('No discussions were found.').'</div>';
}
