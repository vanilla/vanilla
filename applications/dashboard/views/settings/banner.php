<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::session();

echo heading($this->title());
$desc = t('Spend a little time thinking about how you describe your site here.',
    'Spend a little time thinking about how you describe your site here. Giving your site a meaningful title and concise description could help your position in search engines.');
helpAsset(t('Heads up!'), $desc);
helpAsset(t('Need More Help?'), anchor(t("Video tutorial on managing appearance"), 'settings/tutorials/appearance'));
$this->data('ConfigurationModule')->render();
