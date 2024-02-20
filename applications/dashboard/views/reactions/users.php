<?php if (!defined('APPLICATION')) exit();

echo '<ul>';

foreach($this->data('Users') as $User) {
    echo '<li>'.userPhoto($User, ['Size' => 'Small']).userAnchor($User).'</li>';
}

echo '</ul>';