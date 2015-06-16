<?php
$sender = $this->Data('sender');

echo '<'.$sender->tag.' class="dropdown '.$sender->dropdownCssClass.'">';
if ($trigger = $sender->trigger) {
    if (val('isButton', $trigger)) {
        echo '<button id="'.$sender->triggerId.'" class="btn dropdown-toggle '.val('triggerCssClass', $trigger).'" type="button" data-toggle="dropdown" aria-haspopup="true" role="button" aria-expanded="false">';
        echo val('triggerText', $trigger);
        if (val('triggerIcon', $trigger)) {
            echo $sender->icon(val('triggerIcon', $trigger));
        }
        echo '</button>';
    }
    if (val('isAnchor', $trigger)) {
        echo '<a id="'.$sender->triggerId.'" class="'.$trigger->triggerCssClass.'" data-target="#" href="/" data-toggle="dropdown" aria-haspopup="true" role="button" aria-expanded="false">';
        echo val('triggerText', $trigger);
        if (val('triggerIcon', $trigger)) {
            echo $sender->icon(val('triggerIcon', $trigger));
        }
        echo '</a>';
    }
}
echo '<ul class="dropdown-menu '.$sender->listCssClass.'" role="menu" aria-labelledby="'.$sender->triggerId.'">';
foreach ($sender->items as $item) {
    if (val('type', $item) == 'group') {
        echo '<li role="presentation" class="dropdown-header '.val('headerCssClass', $item).'">';
        if (val('headerIcon', $item)) {
            echo $sender->icon(val('headerIcon', $item));
        }
        echo val('headerText', $item);
        if(val('headerBadge', $item)) {
            echo $sender->badge(val('headerBadge', $item));
        }
        echo '</li>';
    }
    if (val('type', $item) == 'link') {
        echo '<li role="presentation" class="'.val('listItemCssClass', $item).'">';
        echo '<a role="menuitem" class="dropdown-menu-link '.val('linkCssClass', $item).'" tabindex="-1" href="'.val('linkUrl', $item).'">';
        if (val('linkIcon', $item)) {
            echo $sender->icon(val('linkIcon', $item));
        }
        echo val('linkText', $item);
        if(val('linkBadge', $item)) {
            echo $sender->badge(val('linkBadge', $item));
        }
        echo '</a>';
        echo '</li>';
    }
    if (val('type', $item) == 'divider') {
        echo '<li role="presentation" class="divider '.val('dividerCssClass', $item).'"></li>';
    }
}
echo '</ul>';
echo '</'.$sender->tag.'>';
