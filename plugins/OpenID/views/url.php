<?php if (!defined('APPLICATION')) exit();

echo '<div class="Connect">';
echo '<h1>', $this->data('Title'), '</h1>';

// Post this form back to our current location.
$path = htmlspecialchars(Gdn::request()->path());

echo $this->Form->open(['Action' => url($path), 'Method' => 'get']);
echo $this->Form->errors();
?>
    <div>
        <ul>
            <li>
                <?php
                echo $this->Form->label('Enter Your OpenID Url', 'Url');
                echo $this->Form->textBox('url');
                ?>
            </li>
        </ul>
        <div class="Buttons">
            <?php echo $this->Form->button('Save'); ?>
        </div>
    </div>
<?php
echo $this->Form->close();
echo '</div>';
