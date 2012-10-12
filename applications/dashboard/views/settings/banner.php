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
<h1><?php echo T('Banner'); ?></h1>
<div class="PageInfo">
   <h2><?php echo T('Heads up!');?></h2>
   <p>
   <?php 
   echo T('Spend a little time thinking about how you describe your site here.', 
      'Spend a little time thinking about how you describe your site here. Giving your site a meaningful title and concise description could help your position in search engines.');
   ?>
   </p>
</div>

<?php
echo $this->Form->Open(array('enctype' => 'multipart/form-data'));
echo $this->Form->Errors();
?>
<div class="Row">
   <div class="Column Grid_50">  
      <ul>
         <li>
            <?php
               echo $this->Form->Label('Homepage Title', 'Garden.HomepageTitle');
               echo Wrap(
                     T('The homepage title is displayed on your home page.', 'The homepage title is displayed on your home page. Pick a title that you would want to see appear in search engines.'),
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
         <li>
            <?php
               echo $this->Form->Label('Banner Title', 'Garden.Title');
               echo Wrap(
                     T("The banner title appears on your site's banner and in your browser's title bar.", 
                       "The banner title appears on your site's banner and in your browser's title bar. It should be less than 20 characters. If a banner logo is uploaded, it will replace the banner title on user-facing forum pages. Also, keep in mind some themes may also hide this title."),
                     'div',
                     array('class' => 'Info')
                  );
               echo $this->Form->TextBox('Garden.Title');
            ?>
         </li>
      </ul>
   </div>
   <div class="Column Grid_50">
      <ul>
         <li>
            <?php
               echo $this->Form->Label('Banner Logo', 'Logo');
               echo Wrap(
                     T('LogoDescription', 'The banner logo appears at the top of your site. Some themes may not display this logo.'),
                     'div',
                     array('class' => 'Info')
                  );

               $Logo = $this->Data('Logo');
               if ($Logo) {
                  echo Wrap(
                     Img(Gdn_Upload::Url($Logo)),
                     'div'
                  );
                  echo Wrap(Anchor(T('Remove Banner Logo'), '/dashboard/settings/removelogo/'.$Session->TransientKey(), 'SmallButton'), 'div', array('style' => 'padding: 10px 0;'));
                  echo Wrap(
                     T('LogoBrowse', 'Browse for a new banner logo if you would like to change it:'),
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
                     T('FaviconDescription', "Your site's favicon appears in your browser's title bar. It will be scaled to 16x16 pixels."),
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
               } else {
                  echo Wrap(
                     T('FaviconDescription', "The shortcut icon that shows up in your browser's bookmark menu (16x16 px)."),
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
                  echo Wrap(Anchor(T('Remove Image'), '/dashboard/settings/removeshareimage', 'SmallButton Hijack'), 'div', array('style' => 'padding: 10px 0;'));
                  echo Wrap(
                     T('FaviconBrowse', 'Browse for a new favicon if you would like to change it:'),
                     'div',
                     array('class' => 'Info')
                  );
               }
               echo $this->Form->Input('ShareImage', 'file');
            ?>
         </li>
      </ul>
   </div>
</div>
<?php 

echo '<div class="Buttons">'.$this->Form->Button('Save').'</div>';

echo $this->Form->Close();
