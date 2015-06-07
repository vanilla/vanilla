<?php if (!defined('APPLICATION')) exit();
echo '<div class="Connect">';
echo '<h1>', $this->data('Title'), '</h1>';
$Form = $this->Form; //new Gdn_Form();
//$Form->Method = 'get';
echo $Form->open(array('Action' => url(Gdn::request()->Path()), 'Method' => 'get'));
echo $Form->errors();
?>
    <div>
        <ul>
            <li>
                <?php
                echo $Form->label('Enter Your OpenID Url', 'Url');
                echo $Form->textBox('url');
                ?>
            </li>
        </ul>
        <div class="Buttons">
            <?php echo $Form->button('Go'); ?>
        </div>
    </div>
<?php
echo $Form->close();
echo '</div>';
