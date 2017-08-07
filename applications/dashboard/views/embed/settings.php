<?php if (!defined('APPLICATION')) exit();
$AllowEmbed = c('Garden.Embed.Allow');
echo heading($this->title(), '', '', [], '/embed/forum');
echo $this->Form->open();
echo $this->Form->errors();
?>
<ul>
    <li class="form-group">
        <div class="label-wrap">
            <div class="label">The url where the forum is embedded:</div>
            <div class="info">Example: http://yourdomain.com/forum/</div>
        </div>
        <div class="input-wrap">
        <?php echo $this->Form->textBox('Garden.Embed.RemoteUrl'); ?>
        </div>
    </li>
    <li class="form-group">
        <?php echo $this->Form->toggle('Garden.Embed.ForceForum', "Force the forum to only be accessible through the above url"); ?>
    </li>
    <li class="form-group">
        <?php echo $this->Form->toggle('Garden.Embed.ForceMobile', "Force the forum to only be accessible through the above url when viewed on a mobile device"); ?>
    </li>
    <li class="form-group">
        <?php echo $this->Form->toggle('Garden.Embed.ForceDashboard', "Force the dashboard to only be accessible through this url <em>(not recommended)</em>"); ?>
    </li>
</ul>
<section>
    <?php echo subheading(t('Comment Embed Settings')); ?>
    <ul>
        <li class="form-group">
            <?php
            $Options = ['10' => '10', '15' => '15', '20' => '20', '25' => '25', '30' => '30', '40' => '40', '50' => '50', '100' => '100'];
            $Fields = ['TextField' => 'Code', 'ValueField' => 'Code']; ?>
            <div class="label-wrap">
            <?php echo $this->Form->label('Comments per Page', 'Garden.Embed.CommentsPerPage'); ?>
            </div>
            <div class="input-wrap">
            <?php echo $this->Form->dropDown('Garden.Embed.CommentsPerPage', $Options, $Fields); ?>
            </div>
        </li>
        <li class="form-group">
            <?php
            $Options = ['desc' => 'Most recent first / comment form at top of list', 'asc' => 'Most recent last / comment form at bottom of list'];
            $Fields = ['TextField' => 'Text', 'ValueField' => 'Code']; ?>
            <div class="label-wrap">
            <?php echo $this->Form->label('Sort blog comments in the following order:', 'Garden.Embed.SortComments'); ?>
            </div>
            <div class="input-wrap">
            <?php echo $this->Form->dropDown('Garden.Embed.SortComments', $Options, $Fields); ?>
            </div>
        </li>
        <li class="form-group">
            <div class="label-wrap">
                <div class="label">Redirect Users</div>
                <div class="info">
                    <strong>Recommended:</strong> When there is more than one page of comments on a blog post, send users to
                    the forum when they click to see another page of comments. This is a great way of driving users into
                    your community.
                </div>
            </div>
            <div class="input-wrap">
            <?php echo $this->Form->checkBox('Garden.Embed.PageToForum', "Send users to forum after the first page of comments."); ?>
            </div>
        </li>
    </ul>
</section>
<section>
    <?php echo subheading(t('Sign In Settings')); ?>
    <div class="form-group">
        <?php echo $this->Form->toggle('Garden.SignIn.Popup', 'Enable popups for sign in pages.', [], 'If you are using SSO, you probably want to leave this disabled'); ?>
    </div>
</section>
<?php echo $this->Form->close('Save'); ?>
