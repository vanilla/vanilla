<?php if (!defined('APPLICATION')) exit; ?>
<?php
$desc = '<p>'.t('We recommend enabling 1–3 reactions at a time to avoid overwhelming users.').' '
    .t('Most communities stick to positive reactions to encourage participation.')
    .'</p>';

helpAsset(t('Heads up!'), $desc);
helpAsset(t('Need More Help?'), anchor(t("Reactions Documentation"), 'https://success.vanillaforums.com/kb/articles/22-reactions'));
echo heading($this->data('Title'), [
    [
        'text' => dashboardSymbol('settings'),
        'url' => '/reactions/settings',
        'attributes' => [
            'class' => 'btn btn-icon-border js-modal',
            'aria-label' => sprintf(t("%s Settings"), t("Reactions")),
            'data-reload-page-on-save' => false
        ]
    ],
]);
?>
<div class="table-wrap">
<table class="table-data js-tj">
   <thead>
      <tr>
         <th class="NameColumn column-lg"><?php echo t('Reaction'); ?></th>
         <th class="column-xl"><?php echo t("Actions and Permissions"); ?></th>
         <th class="options"></th>
      </tr>
   </thead>
   <tbody>
      <?php foreach ($this->data('ReactionTypes') as $ReactionType): ?>
      <?php
      if (getValue('Hidden', $ReactionType)) continue;
      $UrlCode = $ReactionType['UrlCode'];
      $State = $ReactionType['Active'] ? 'Active' : 'InActive';

      $reactionBlock = new MediaItemModule(t($ReactionType['Name']), '', $ReactionType['Description']);
      $urlImgReaction = isset($ReactionType['Photo']) ? Gdn_Upload::url($ReactionType['Photo']) : ReactionModel::ICON_BASE_URL.strtolower($UrlCode).'.svg';
      $reactionBlock->setView('media-sm')
         ->addCssClass('image-wrap', 'media-image-wrap-no-border')
         ->setImage($urlImgReaction);

      ?>
      <tr id="ReactionType_<?php echo $ReactionType['UrlCode']; ?>" class="<?php echo $State; ?>">
         <td class="NameColumn">
            <?php echo $reactionBlock; ?>
         </td>
         <td class="AutoDescription">
            <?php
            $AutoDescription = implode('</li><li>', autoDescription($ReactionType));
            if ($AutoDescription)
               echo wrap('<li>'.$AutoDescription.'</li>', 'ul');
            ?>
         </td>
         <td class="options">
            <div class="btn-group">
               <?php echo anchor(dashboardSymbol('edit'), "/reactions/edit/{$UrlCode}", 'js-modal btn btn-icon', ['aria-label' => t('Edit'), 'title' => t('Edit')]); ?>
               <?php echo activateButton($ReactionType); ?>
            </div>
         </td>
      </tr>
      <?php endforeach; ?>
   </tbody>
</table>
</div>
