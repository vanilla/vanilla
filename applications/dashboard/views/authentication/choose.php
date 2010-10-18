<?php if (!defined('APPLICATION')) exit(); ?>
<h1><?php echo $this->Data('Title'); ?></h1>
<div class="Info">
   <?php echo T('Choose and configure your forum\'s authentication scheme.'); ?><br/>
   <span class="PasswordForce"><?php echo sprintf(T('You can always use your password at<a href="%1$s">%1$s</a>.', 'If you are ever locked out of your forum you can always log in using your original Vanilla email and password at <a href="%1$s">%1$s</a>'),Url('entry/password', TRUE)); ?></span>
</div>
<div class="AuthenticationChooser">
   <?php 
      echo $this->Form->Open(array(
         'action'  => Url('dashboard/authentication/choose')
      ));
      echo $this->Form->Errors();
   ?>
   <ul>
      <li>
         <?php
            echo $this->Form->Label('Current Authenticator', 'Garden.Authentication.CurrentAuthenticator');
            echo "<span>{$this->ChooserList[$this->CurrentAuthenticationAlias]}</span>";
         ?>
      </li>
      <li>
         <?php
            echo $this->Form->Label('Configure an Authenticator', 'Garden.Authentication.Chooser');
            echo $this->Form->DropDown('Garden.Authentication.Chooser', array_merge(array(NULL=>NULL),$this->ChooserList), array(
               'value'  => $this->Data('PreFocusAuthenticationScheme')
            ));
            echo $this->Form->Button("Activate",array(
               'Class'  => 'SliceSubmit SmallButton'
            ));
         ?>
      </li>
   </ul>
   <?php
      echo $this->Form->Close();
   ?>
</div>
<?php
   if ($this->Data('PreFocusAuthenticationScheme')) {
      $Scheme = $this->Data('PreFocusAuthenticationScheme');
      $Rel = $this->Data('AuthenticationConfigureList.'.$Scheme);
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
   var ConfigureList = <?php echo json_encode($this->Data('AuthenticationConfigureList')); ?>;
   jQuery(document).ready(function(){
      if ($('select#Form_Garden-dot-Authentication-dot-Chooser').attr('bound')) return;

      var ChosenAuthenticator = '<?php echo $this->Data('PreFocusAuthenticationScheme'); ?>';
      if (!ChosenAuthenticator) {
         $('select#Form_Garden-dot-Authentication-dot-Chooser').val('');
      }

      $('select#Form_Garden-dot-Authentication-dot-Chooser').attr('bound',true);
      $('select#Form_Garden-dot-Authentication-dot-Chooser').bind('change',function(e){
         var Chooser = $(e.target);
         var SliceElement = $('div.AuthenticationConfigure');
         var SliceObj = SliceElement.attr('Slice');
         
         var ChooserVal = Chooser.val();
         var ChosenURL = (ConfigureList[ChooserVal]) ? ConfigureList[ChooserVal] : ((ConfigureList[ChooserVal] != 'undefined') ? '/dashboard/authentication/configure/'+ChooserVal : false);
         if (ChosenURL) {
            SliceObj.ReplaceSlice(ChosenURL);
         }
      });
   });
</script>