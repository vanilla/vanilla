<?php if (!defined('APPLICATION')) exit();

// Define the plugin:
$PluginInfo['GettingStarted'] = array(
   'Name' => 'Getting Started',
   'Description' => 'Adds a welcome message to the dashboard showing new administrators things they can do to get started using their forum. Checks off each item as it is completed.',
   'Version' => '1',
   'Author' => "Mark O'Sullivan",
   'AuthorEmail' => 'mark@vanillaforums.com',
   'AuthorUrl' => 'http://vanillaforums.com',
   'Hidden' => TRUE
);

class GettingStartedPlugin extends Gdn_Plugin {

/*
   This plugin should:
   
   1. Display 5 tips for getting started on the dashboard
   2. Check off each item as it is completed
   3. Disable itself when "dismiss" is clicked
*/    
    
   // Adds a "My Forums" menu option to the dashboard area
   public function SettingsController_Render_Before(&$Sender) {
      // Have they visited their dashboard?
      if (strtolower($Sender->RequestMethod) != 'index')
         $this->SaveStep('Plugins.GettingStarted.Dashboard');
         
      // Save the action if editing registration settings
      if (strcasecmp($Sender->RequestMethod, 'registration') == 0 && $Sender->Form->AuthenticatedPostBack() === TRUE)
         $this->SaveStep('Plugins.GettingStarted.Registration');

      // Save the action if they reviewed plugins
      if (strcasecmp($Sender->RequestMethod, 'plugins') == 0)
         $this->SaveStep('Plugins.GettingStarted.Plugins');

      // Save the action if they reviewed plugins
      if (strcasecmp($Sender->RequestMethod, 'managecategories') == 0)
         $this->SaveStep('Plugins.GettingStarted.Categories');

      // Add messages & their css on dashboard
      if (strcasecmp($Sender->RequestMethod, 'index') == 0) {
         $Sender->AddCssFile('plugins/GettingStarted/style.css');
         
         $Session = Gdn::Session();
         $WelcomeMessage = '<div class="GettingStarted">'
            .Anchor('×', '/dashboard/plugin/dismissgettingstarted/'.$Session->TransientKey(), 'Dismiss')
   ."<h1>さあ、はじめよう:</h1>"
   .'<ul>
      <li class="One'.(C('Plugins.GettingStarted.Dashboard', '0') == '1' ? ' Done' : '').'">
         <strong>'.Anchor('ダッシュボードへ ようこそ', 'settings').'</strong>
         <p>これは、コミュニティを管理・運用するためのダッシュボードです。
         左側の設定オプションを試してみてください: ここで、このコミュニティが
         どのように機能するかを設定できます。<b>「Administrator」ロールに属するユーザーだけが、
         ダッシュボードにアクセスすることができます。</b></p>
      </li>
      <li class="Two'.(C('Plugins.GettingStarted.Discussions', '0') == '1' ? ' Done' : '').'">
         <strong>'.Anchor("コミュニティ フォーラムはどこにある？", '/').'</strong>
         <p>このページの左上にある "サイトへ移動" リンクをクリックして、
         コミュニティ フォーラムにアクセスしてください。'.Anchor('ここをクリック', '/').
         'してもいいです。コミュニティ フォーラムは、ユーザや顧客が '
         .Anchor(Gdn::Request()->Url('/', TRUE), Gdn::Request()->Url('/', TRUE)).
         ' を訪れたときに見る場所です。</p>
      </li>
      <li class="Three'.(C('Plugins.GettingStarted.Categories', '0') == '1' ? ' Done' : '').'">
         <strong>'.Anchor(T('Organize your Categories'), 'vanilla/settings/managecategories').'</strong>
         <p>スレッド カテゴリは、ユーザーがコミュニティにとって意味のある方法で
         スレッドを分類する手助けになります。</p>
      </li>
      <li class="Four'.(C('Plugins.GettingStarted.Profile', '0') == '1' ? ' Done' : '').'">
         <strong>'.Anchor(T('Customize your Public Profile'), 'profile').'</strong>
         <p>コミュニティに参加する人には誰でも、公開プロフィール ページが用意されます。
         そこでは、彼ら自身の画像をアップロードしたり、個人設定を管理したり、
         コミュニティで起きている興味のある事柄をフォローすることができます。さあ
         '.Anchor('今すぐプロフィールをカスタマイズ', 'profile').'してみましょう。</p>
      </li>
      <li class="Five'.(C('Plugins.GettingStarted.Discussion', '0') == '1' ? ' Done' : '').'">
         <strong>'.Anchor(T('Start your First Discussion'), 'post/discussion').'</strong>
         <p>今すぐ'.Anchor('最初のスレッドを立てて', 'post/discussion').'、
         コミュニティを始動させましょう。</p>
      </li>
      <li class="Six'.(C('Plugins.GettingStarted.Plugins', '0') == '1' ? ' Done' : '').'">
         <strong>'.Anchor(T('Manage your Plugins'), 'settings/plugins').'</strong>
         <p>プラグインを使って、コミュニティの機能を拡張できます。人気のあるプラグインは、
         すでに同梱されています。また'.Anchor('オンラインからもっと入手', 'http://vanillaforums.org/addon/browse/plugins/recent/2').'できます。</p>
      </li>
   </ul>
</div>';
         $Sender->AddAsset('Messages', $WelcomeMessage, 'WelcomeMessage');
      }
   }
   
   // Record when the various actions are taken
   // 1. If the user edits the registration settings
   public function SaveStep($Step) {
      if (Gdn::Config($Step, '') != '1')
         SaveToConfig($Step, '1');
         
      // If all of the steps are now completed, disable this plugin
      if (
         Gdn::Config('Plugins.GettingStarted.Registration', '0') == '1'
         && Gdn::Config('Plugins.GettingStarted.Plugins', '0') == '1'
         && Gdn::Config('Plugins.GettingStarted.Categories', '0') == '1'
         && Gdn::Config('Plugins.GettingStarted.Profile', '0') == '1'
         && Gdn::Config('Plugins.GettingStarted.Discussion', '0') == '1'
      ) {
         Gdn::PluginManager()->DisablePlugin('GettingStarted');
      }
   }
   
   // If the user posts back any forms to their profile, they've completed step 4: profile customization
   public function ProfileController_Render_Before(&$Sender) {
      if (property_exists($Sender, 'Form') && $Sender->Form->AuthenticatedPostBack() === TRUE)
         $this->SaveStep('Plugins.GettingStarted.Profile');
   }

   // If the user starts a discussion, they've completed step 5: profile customization
   public function PostController_Render_Before(&$Sender) {
      if (strcasecmp($Sender->RequestMethod, 'discussion') == 0 && $Sender->Form->AuthenticatedPostBack() === TRUE)
         $this->SaveStep('Plugins.GettingStarted.Discussion');
   }
   
   public function PluginController_DismissGettingStarted_Create(&$Sender) {
      Gdn::PluginManager()->DisablePlugin('GettingStarted');
      echo 'TRUE';
   }
   
   public function Setup() {
      // No setup required.
   }
}