<?php if (!defined('APPLICATION')) exit();
echo '<div class="Connect">';
echo '<h1>', $this->Data('Title'), '</h1>';
$Form = $this->Form; //new Gdn_Form();
//$Form->Method = 'get';
echo $Form->Open(array('Action' => Url(Gdn::Request()->Path()), 'Method' => 'get'));
echo $Form->Errors();
?>
    <div>
        <ul>
            <li>
                <?php
                echo $Form->Label('Enter Your OpenID Url', 'Url');
                echo $Form->TextBox('url');
                ?>
            </li>
        </ul>
        <div class="Buttons">
            <?php echo $Form->Button('Go'); ?>
        </div>
    </div>
<?php
echo $Form->Close();
echo '</div>';
