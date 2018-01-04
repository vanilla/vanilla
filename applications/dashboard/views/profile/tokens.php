<?php if (!defined('APPLICATION')) exit(); ?>
<h1 class="H"><?=t('Personal Access Tokens')?></h1>

<div class="PageControls Top">
    <a href="<?=url(userUrl($this->User, '', 'token'))?>" class="Button Action Popup Primary"><?=t('Generate New Token')?></a>
</div>
<div class="DataListWrap">
    <ul class="DataList DataList-Tokens">
    <?php
    foreach ($this->data('Tokens') as $token) {
        ?><li id="Token_<?=$token['accessTokenID']?>" class="Item Item-Token">
            <b><?=htmlspecialchars($token['name'])?></b>&nbsp;
            <div class="Meta Options">
                <a href="<?=url('/profile/tokenReveal?accessTokenID='.$token['accessTokenID'])?>" class="OptionsLink Hijack" tabindex="0"><?=t('Reveal')?></a>
                <span class="Bullet">·</span>
                <a href="<?=url('/profile/tokenDelete?accessTokenID='.$token['accessTokenID'])?>" class="OptionsLink Popup" tabindex="0"><?=t('Delete')?></a>
            </div>
        </li><?php
    }
    ?>
    </ul>
</div>

