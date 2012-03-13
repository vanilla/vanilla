<?php echo '<?xml version="1.0" encoding="utf-8"?>'; ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en-ca">
<head>
   <?php $this->RenderAsset('Head'); ?>
</head>
<body id="<?php echo $BodyIdentifier; ?>" class="<?php echo $this->CssClass; ?>">
   <div class="Frame" id="Frame">
      <div class="Head" id="Head">
         <div class="WidthWrapper">
            <div class="Menu">
               <h1><a class="Title" href="<?php echo Url('/'); ?>"><span><?php echo Gdn_Theme::Logo(); ?></span></a></h1>
               <?php
                  $Session = Gdn::Session();
                  if ($this->Menu) {
                     $this->Menu->AddLink('Dashboard', T('Dashboard'), '/dashboard/settings', array('Garden.Settings.Manage'));
                     $this->Menu->AddLink('Activity', T('Activity'), '/activity');
                     if ($Session->IsValid()) {
                        $Name = $Session->User->Name;
                        $CountNotifications = $Session->User->CountNotifications;
                        if (is_numeric($CountNotifications) && $CountNotifications > 0)
                           $Name .= ' <span class="Alert">'.$CountNotifications.'</span>';

                        if (urlencode($Session->User->Name) == $Session->User->Name)
                           $ProfileSlug = $Session->User->Name;
                        else
                           $ProfileSlug = $Session->UserID.'/'.urlencode($Session->User->Name);
                        $this->Menu->AddLink('User', $Name, '/profile/'.$ProfileSlug, array('Garden.SignIn.Allow'), array('class' => 'UserNotifications'));
                        $this->Menu->AddLink('SignOut', T('Sign Out'), SignOutUrl(), FALSE, array('class' => 'NonTab SignOut'));
                     } else {
                        $Attribs = array();
                        if (SignInPopup() && strpos(Gdn::Request()->Url(), 'entry') === FALSE)
                           $Attribs['class'] = 'SignInPopup';

                        $this->Menu->AddLink('Entry', T('Sign In'), SignInUrl($this->SelfUrl), FALSE, array('class' => 'NonTab'), $Attribs);
                     }
                     echo $this->Menu->ToString();
                  }
               ?>
               <div class="Search"><?php
                  $Form = Gdn::Factory('Form');
                  $Form->InputPrefix = '';
                  echo 
                     $Form->Open(array('action' => Url('/search'), 'method' => 'get')),
                     $Form->TextBox('Search'),
                     $Form->Button('Go', array('Name' => '')),
                     $Form->Close();
               ?></div>
            </div>
         </div>
      </div>
      <div class="Body" id="Body">
         <div class="WidthWrapper">
            <div class="Content" id="Content"><?php $this->RenderAsset('Content'); ?></div>
            <div class="Panel" id="Panel"><?php $this->RenderAsset('Panel'); ?></div>
      
         </div>
      </div>
      <div class="Foot" id="Foot">
         <div class="WidthWrapper">
            <?php $this->RenderAsset('Foot'); ?>
            <div class="PoweredByVanilla"><?php echo Anchor(T('Powered by Vanilla'), C('Garden.VanillaUrl')); ?></div>
         </div>
		</div>
   </div>
	<?php $this->FireEvent('AfterBody'); ?>
</body>
</html>
