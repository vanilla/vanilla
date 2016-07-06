<?php if (!defined('APPLICATION')) exit();
$AllowEmbed = c('Garden.Embed.Allow');
?>
    <h1><?php echo $this->title(); ?></h1>
    <?php
    echo $this->Form->open();
    echo $this->Form->errors();
    ?>

    <p>The url where the forum is embedded:</p>

    <p><?php echo $this->Form->textBox('Garden.Embed.RemoteUrl'); ?> <em>Example: http://yourdomain.com/forum/</em></p>

    <p><?php echo $this->Form->CheckBox('Garden.Embed.ForceForum', "Force the forum to only be accessible through this url"); ?></p>

    <p><?php echo $this->Form->Checkbox('Garden.Embed.ForceMobile', "Force the forum to only be accessible through this url when viewed on a mobile device."); ?></p>

    <p><?php echo $this->Form->CheckBox('Garden.Embed.ForceDashboard', "Force the dashboard to only be accessible through this url <em>(not recommended)</em>"); ?></p>

    <h2>Sign In Settings</h2>

    <p>
        <small>If you are using SSO you probably need to disable sign in popups.</small>
    </p>
    <p><?php echo $this->Form->CheckBox('Garden.SignIn.Popup', "Use popups for sign in pages."); ?></p>

    <h1><?php echo t('Comment Embed Settings'); ?></h1>
    <ul>
        <li>
            <?php
            $Options = array('10' => '10', '15' => '15', '20' => '20', '25' => '25', '30' => '30', '40' => '40', '50' => '50', '100' => '100');
            $Fields = array('TextField' => 'Code', 'ValueField' => 'Code');
            echo $this->Form->label('Comments per Page', 'Garden.Embed.CommentsPerPage');
            echo $this->Form->DropDown('Garden.Embed.CommentsPerPage', $Options, $Fields);
            ?>
        </li>
        <li>
            <?php
            $Options = array('desc' => 'Most recent first / comment form at top of list', 'asc' => 'Most recent last / comment form at bottom of list');
            $Fields = array('TextField' => 'Text', 'ValueField' => 'Code');
            echo $this->Form->label('Sort blog comments in the following order:', 'Garden.Embed.SortComments');
            echo $this->Form->DropDown('Garden.Embed.SortComments', $Options, $Fields);
            ?>
        </li>
        <li>
            <p class="WarningMessage">
                <?php
                echo $this->Form->CheckBox('Garden.Embed.PageToForum', "Send users to forum after the first page of comments.");
                ?>
                <strong>Recommended:</strong> When there is more than one page of comments on a blog post, send users to
                the forum when they click to see another page of comments. This is a great way of driving users into
                your community.
            </p>
        </li>
    </ul>
<?php
echo $this->Form->close('Save');

