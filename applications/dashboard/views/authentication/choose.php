<?php if (!defined('APPLICATION')) exit(); ?>
<h1><?php echo $this->data('Title'); ?></h1>
<div class="Info">
    <?php echo t('Choose and configure your forum\'s authentication scheme.'); ?><br/>
    <span
        class="PasswordForce"><?php echo sprintf(t('You can always use your password at<a href="%1$s">%1$s</a>.', 'If you are ever locked out of your forum you can always log in using your original Vanilla email and password at <a href="%1$s">%1$s</a>'), url('entry/password', true)); ?></span>
</div>
<div class="AuthenticationChooser">
    <?php
    echo $this->Form->open(array(
        'action' => url('dashboard/authentication/choose')
    ));
    echo $this->Form->errors();
    ?>
    <ul>
        <li>
            <?php
            echo $this->Form->label('Current Authenticator', 'Garden.Authentication.CurrentAuthenticator');
            echo "<span>{$this->ChooserList[$this->CurrentAuthenticationAlias]}</span>";
            ?>
        </li>
        <li>
            <?php
            echo $this->Form->label('Configure an Authenticator', 'Garden.Authentication.Chooser');
            echo $this->Form->DropDown('Garden.Authentication.Chooser', array_merge(array(NULL => NULL), $this->ChooserList), array(
                'value' => $this->data('PreFocusAuthenticationScheme')
            ));
            echo $this->Form->button("Activate", array(
                'Class' => 'SliceSubmit SmallButton'
            ));
            ?>
        </li>
    </ul>
    <?php
    echo $this->Form->close();
    ?>
</div>
<?php
if ($this->data('PreFocusAuthenticationScheme')) {
    $Scheme = $this->data('PreFocusAuthenticationScheme');
    $Rel = $this->data('AuthenticationConfigureList.'.$Scheme);
    if (!is_String($Rel))
        $Rel = '/dashboard/authentication/configure/'.$Scheme;
    ?>
    <div class="AuthenticationConfigure Slice Async" rel="<?php echo $Rel; ?>"></div>
<?php
} else {
    echo $this->Slice('configure');
}
?>
<script type="text/javascript">
    var ConfigureList = <?php echo json_encode($this->data('AuthenticationConfigureList')); ?>;
    jQuery(document).ready(function() {
        if ($('select#Form_Garden-dot-Authentication-dot-Chooser').attr('bound')) return;

        var ChosenAuthenticator = '<?php echo $this->data('PreFocusAuthenticationScheme'); ?>';
        if (!ChosenAuthenticator) {
            $('select#Form_Garden-dot-Authentication-dot-Chooser').val('');
        }

        $('select#Form_Garden-dot-Authentication-dot-Chooser').attr('bound', true);
        $('select#Form_Garden-dot-Authentication-dot-Chooser').bind('change', function(e) {
            var Chooser = $(e.target);
            var SliceElement = $('div.AuthenticationConfigure');
            var SliceObj = SliceElement.prop('Slice');

            var ChooserVal = Chooser.val();
            var ChosenURL = (ConfigureList[ChooserVal]) ? ConfigureList[ChooserVal] : ((ConfigureList[ChooserVal] != 'undefined') ? '/dashboard/authentication/configure/' + ChooserVal : false);
            if (ChosenURL) {
                SliceObj.ReplaceSlice(ChosenURL);
            }
        });
    });
</script>
