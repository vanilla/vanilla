<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session();
?>
<style>
   .Row {
      margin: 0;
      padding: 0;
      overflow: hidden;
   }
   .Column {
      margin: 0;
      overflow: hidden;
      float: left;
      display: inline;
   }
   .Grid_50 {
      width: 50%;
   }
   .Buttons {
      margin: 20px;
      text-align: right;
   }
</style>

<div class="Help Aside">
   <?php
   echo '<h2>', T('Need More Help?'), '</h2>';
   echo '<ul>';
   echo Wrap(Anchor(T("Video tutorial on managing appearance"), 'settings/tutorials/appearance'), 'li');
   echo '</ul>';
   ?>
</div>
<h1><?php echo T('Branding'); ?></h1>
<div class="PageInfo">
   <h2><?php echo T('Heads up!');?></h2>
   <p>
   <?php 
   echo T('Spend a little time thinking about how you describe your site here.', 
      'Spend a little time thinking about how you describe your site here. Giving your site a meaningful title and concise description could help your position in search engines.
      Crafting great images and icons will increase your brand&rsquo;s strength.');
   ?>
   </p>
</div>

<?php
echo $this->Form->Open(array('enctype' => 'multipart/form-data'));
echo $this->Form->Errors();
?>
<div class="Row">
   <div class="BrandingText">
      <ul>
         <li>
            <?php
            echo $this->Form->Label('Site Title', 'Garden.Title');
            echo Wrap(
               T("The banner title appears on your site's banner and in your browser's title bar.",
                  "The site title appears in your browser's title bar and at the very top of the page. It should be less than 20 characters. If a banner logo is uploaded, it will replace the banner title on user-facing forum pages. Also, keep in mind some themes may also hide this title."),
               'div',
               array('class' => 'Info')
            );
            echo $this->Form->TextBox('Garden.Title');
            ?>
         </li>
         <li>
            <?php
               echo $this->Form->Label('Homepage Title', 'Garden.HomepageTitle');
               echo Wrap(
                     T('The homepage title is displayed on your home page.', 'The homepage title is displayed on your home page just above the content. Pick a title that you would want to see appear in search engines. It&rsquo;s OK for this to be the same as the Site Title.'),
                     'div',
                     array('class' => 'Info')
                  );
               echo $this->Form->TextBox('Garden.HomepageTitle');
            ?>
         </li>
         <li>
            <?php
               echo $this->Form->Label('Site Description', 'Garden.Description');
               echo Wrap(
                     T("The site description usually appears in search engines.", 'The site description usually appears in search engines. You should try having a description that is 100–150 characters long.'),
                     'div',
                     array('class' => 'Info')
                  );
               echo $this->Form->TextBox('Garden.Description', array('Multiline' => TRUE));
            ?>
         </li>
      </ul>
   </div>
   <div class="BrandingImages">
      <ul>
         <li>
            <?php
               echo $this->Form->Label('Logo', 'Logo');
               echo Wrap(
                     T('LogoDescription', 'Appears at the top of your site. Some themes may not display it.'),
                     'div',
                     array('class' => 'Info')
                  );

               $Logo = $this->Data('Logo');
               if ($Logo) {
                  echo Wrap(
                     Img(Gdn_Upload::Url($Logo)),
                     'div'
                  );
                  echo Wrap(Anchor(T('Remove Logo'), '/dashboard/settings/removelogo/'.$Session->TransientKey(), 'SmallButton'), 'div', array('style' => 'padding: 10px 0;'));
                  echo Wrap(
                     T('LogoBrowse', 'Browse for a new logo if you would like to change it:'),
                     'div',
                     array('class' => 'Info')
                  );
               }

               echo $this->Form->Input('Logo', 'file');
            ?>
         </li>
         <li>
            <?php
               echo $this->Form->Label('Favicon', 'Favicon');
               echo Wrap(
                     T('FaviconDescription', "Your site's favicon appears in your browser's title bar and bookmark menu. It will be scaled to 16x16 pixels."),
                     'div',
                     array('class' => 'Info')
                  );
               $Favicon = $this->Data('Favicon');
               if ($Favicon) {
                  echo Wrap(
                     Img(Gdn_Upload::Url($Favicon)),
                     'div'
                  );
                  echo Wrap(Anchor(T('Remove Favicon'), '/dashboard/settings/removefavicon/'.$Session->TransientKey(), 'SmallButton'), 'div', array('style' => 'padding: 10px 0;'));
                  echo Wrap(
                     T('FaviconBrowse', 'Browse for a new favicon if you would like to change it:'),
                     'div',
                     array('class' => 'Info')
                  );
               }
               echo $this->Form->Input('Favicon', 'file');
            ?>
         </li>
         <li>
            <?php
               echo $this->Form->Label('Share Image', 'ShareImage');
               echo Wrap(
                     T('ShareImageDescription', "When someone shares a link from your site we try and grab an image from the page. If there isn't an image on the page then we'll use this image instead. The image should be at least 50&times;50, but we recommend 200&times;200."),
                     'div',
                     array('class' => 'Info')
                  );
               $ShareImage = $this->Data('ShareImage');
               if ($ShareImage) {
                  echo Wrap(
                     Img(Gdn_Upload::Url($ShareImage), array('style' => 'max-width: 300px')),
                     'div'
                  );
                  echo Wrap(Anchor(T('Remove Image'), '/dashboard/settings/removeshareimage/'.$Session->TransientKey(), 'SmallButton'), 'div', array('style' => 'padding: 10px 0;'));
                  echo Wrap(
                     T('FaviconBrowse', 'Browse for a new Share Image if you would like to change it:'),
                     'div',
                     array('class' => 'Info')
                  );
               }
               echo $this->Form->Input('ShareImage', 'file');
            ?>
         </li>
         <li>
            <?php
            echo $this->Form->Label('Touch Icon', 'TouchIcon');
            echo Wrap(
               T('TouchIconInfo', 'The touch icon appears when you bookmark a website on the homescreen of an iOS device.
                  We recommend a 114x114 pixel 72dpi png image. Do not use gloss, bevel, or shine effects.'),
               'div',
               array('class' => 'Info')
            );
            $TouchIcon = $this->Data('TouchIcon');
            if ($TouchIcon) {
               echo Wrap(
                  Img(Gdn_Upload::Url($TouchIcon), array('style' => 'max-width: 144px')),
                  'div'
               );
               echo Wrap(Anchor(T('Remove Image'), '/dashboard/settings/removetouchicon/'.$Session->TransientKey(), 'SmallButton'), 'div', array('style' => 'padding: 10px 0;'));
               echo Wrap(
                  T('TouchIconBrowse', 'Browse for a new Touch Icon if you would like to change it:'),
                  'div',
                  array('class' => 'Info')
               );
            }
            echo $this->Form->Input('TouchIcon', 'file');
            ?>
         </li>
      </ul>
   </div>
</div>
<?php 

echo '<div class="Buttons">'.$this->Form->Button('Save').'</div>';

echo $this->Form->Close();
