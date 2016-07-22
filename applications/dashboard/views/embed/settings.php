<?php if (!defined('APPLICATION')) exit();
$AllowEmbed = c('Garden.Embed.Allow');
?>
<div class="header-block">
    <div class="title-block">
        <?php echo anchor(dashboardSymbol('chevron-left'), "/embed/forum", 'btn btn-icon btn-return', ['aria-label' => t('Return')]); ?>
        <h1><?php echo $this->title(); ?></h1>
    </div>
</div>
    <?php
    echo $this->Form->open();
    echo $this->Form->errors();
    ?>
<ul>
    <li class="form-group row">
        <div class="label-wrap">
            <div class="label-title">The url where the forum is embedded:</div>
            <div class="info">Example: http://yourdomain.com/forum/</div>
        </div>
        <div class="input-wrap">
        <?php echo $this->Form->textBox('Garden.Embed.RemoteUrl'); ?>
        </div>
    </li>
    <li class="form-group row">
        <div class="input-wrap"><?php echo $this->Form->CheckBox('Garden.Embed.ForceForum', "Force the forum to only be accessible through this url"); ?></div>
    </li>
    <li class="form-group row">
        <div class="input-wrap"><?php echo $this->Form->Checkbox('Garden.Embed.ForceMobile', "Force the forum to only be accessible through this url when viewed on a mobile device."); ?></div>
    </li>
    <li class="form-group row">
        <div class="input-wrap"><?php echo $this->Form->CheckBox('Garden.Embed.ForceDashboard', "Force the dashboard to only be accessible through this url <em>(not recommended)</em>"); ?></div>
    </li>
</ul>
<h2>Sign In Settings</h2>
    <div class="form-group row">
    <?php echo $this->Form->toggle('Garden.SignIn.Popup', "<div class=\"label-title\">Enable popups for sign in pages.</div><div class=\"info\">If you are using SSO you probably need to disable sign in popups.</div>"); ?>
    </div>
    <h2><?php echo t('Comment Embed Settings'); ?></h2>
    <ul>
        <li class="form-group row">
            <?php
            $Options = array('10' => '10', '15' => '15', '20' => '20', '25' => '25', '30' => '30', '40' => '40', '50' => '50', '100' => '100');
            $Fields = array('TextField' => 'Code', 'ValueField' => 'Code'); ?>
            <div class="label-wrap">
            <?php echo $this->Form->label('Comments per Page', 'Garden.Embed.CommentsPerPage'); ?>
            </div>
            <div class="input-wrap">
            <?php echo $this->Form->DropDown('Garden.Embed.CommentsPerPage', $Options, $Fields); ?>
            </div>
        </li>
        <li class="form-group row">
            <?php
            $Options = array('desc' => 'Most recent first / comment form at top of list', 'asc' => 'Most recent last / comment form at bottom of list');
            $Fields = array('TextField' => 'Text', 'ValueField' => 'Code'); ?>
            <div class="label-wrap">
            <?php echo $this->Form->label('Sort blog comments in the following order:', 'Garden.Embed.SortComments'); ?>
            </div>
            <div class="input-wrap">
            <?php echo $this->Form->DropDown('Garden.Embed.SortComments', $Options, $Fields); ?>
            </div>
        </li>
        <li class="form-group row">
            <div class="label-wrap">
                <div class="label-title">Redirect Users</div>
                <div class="info">
                    <strong>Recommended:</strong> When there is more than one page of comments on a blog post, send users to
                    the forum when they click to see another page of comments. This is a great way of driving users into
                    your community.
                </div>
            </div>
            <div class="input-wrap">
            <?php echo $this->Form->CheckBox('Garden.Embed.PageToForum', "Send users to forum after the first page of comments."); ?>
            </div>
        </li>
    </ul>
<div class="form-footer js-modal-footer">
<?php
echo $this->Form->close('Save'); ?>
</div>

