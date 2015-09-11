<?php
echo '<'.val('tag', $this).' class="ToggleFlyout dropdown '.val('dropdownCssClass', $this).'">';
if ($trigger = val('trigger', $this)) {
    if (val('type', $trigger) == 'button') {
        echo '<button id="'.val('triggerId', $this).'" class="btn dropdown-toggle '.val('triggerCssClass', $trigger).'" type="button" data-toggle="dropdown" aria-haspopup="true" role="button" aria-expanded="false">';
        echo val('text', $trigger);
        if (val('icon', $trigger)) {
            echo icon(val('icon', $trigger));
        }
        echo '</button>';
    } elseif (val('type', $trigger) == 'anchor') {
        echo '<a id="'.val('triggerId', $this).'" class="'.val('triggerCssClass', $trigger).'" data-target="#" href="/" data-toggle="dropdown" aria-haspopup="true" role="button" aria-expanded="false">';
        echo val('text', $trigger);
        if (val('icon', $trigger)) {
            echo icon(val('icon', $trigger));
        }
        echo '</a>';
    }
}
echo '<ul class="Flyout dropdown-menu '.val('listCssClass', $this).'" role="menu" aria-labelledby="'.val('triggerId', $this).'">';
foreach (val('items', $this) as $item) {
    if (val('type', $item) == 'group') {
        echo '<li role="presentation" class="dropdown-header '.val('cssClass', $item).'">';
        if (val('icon', $item)) {
            echo icon(val('icon', $item));
        }
        echo val('text', $item);
        if(val('badge', $item)) {
            echo badge(val('badge', $item));
        }
        echo '</li>';
    }
    if (val('type', $item) == 'link') {
        echo '<li role="presentation" class="'.val('listItemCssClass', $item).'">';
        echo '<a role="menuitem" class="dropdown-menu-link '.val('cssClass', $item).'" tabindex="-1" href="'.val('url', $item).'">';
        if (val('icon', $item)) {
            echo icon(val('icon', $item));
        }
        echo val('text', $item);
        if(val('badge', $item)) {
            echo badge(val('badge', $item));
        }
        echo '</a>';
        echo '</li>';
    }
    if (val('type', $item) == 'divider') {
        echo '<li role="presentation" class="divider '.val('cssClass', $item).'"></li>';
    }
}
echo '</ul>';
echo '</'.val('tag', $this).'>';
