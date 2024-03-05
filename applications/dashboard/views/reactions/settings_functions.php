<?php if (!defined('APPLICATION')) exit;

function autoDescription($reactionType) {
   $result = [];

   if ($incrementColumn = getValue('IncrementColumn', $reactionType)) {
      $incrementValue = getValue('IncrementValue', $reactionType, 1);
      $incrementString = $incrementValue > 0 ? "Adds $incrementValue to" : "Subtracts ".abs($incrementValue)." from";

      $result[] = sprintf("%s a post's %s.", $incrementString, strtolower($incrementColumn));
   }

   if ($points = getValue('Points', $reactionType)) {
      if ($points > 0)
         $incrementString = "Gives $points ".plural($points,'point','points')." to";
      else
         $incrementString = "Removes ".abs($points)." ".plural($points,'point','points')." from";

      $result[] = sprintf("%s the user.", $incrementString);
   }

   if ($logThreshold = getValue('LogThreshold', $reactionType)) {
      $log = getValue('Log', $reactionType, 'Moderate');
      $logString = $log == 'Spam' ? 'spam queue' : 'moderation queue';

      $result[] = sprintf("Posts with %s reactions will be placed in the %s.", $logThreshold, $logString);
   }

   if ($removeThreshold = getValue('RemoveThreshold', $reactionType)) {
      if ($removeThreshold != $logThreshold) {
         $result[] = sprintf("Posts will be removed when they get %s reactions.", $removeThreshold);
      }
   }

   if ($class = getValue('Class', $reactionType)) {
      $result[] = sprintf(t('ReactionClassRestriction', 'Requires &ldquo;%s&rdquo; reaction permission.'), $class);
   }

   if ($permission = getValue('Permission', $reactionType)) {
      $result[] = sprintf(t('ReactionPermissionRestriction', 'Special restriction: Only users with permission %s may use this reaction.'),$permission);
   }

   return $result;
}

function activateButton($reactionType) {
   $qs = [
       'urlcode' => $reactionType['UrlCode'],
       'active' => !$reactionType['Active']];

   $state = ($reactionType['Active'] ? 'Active' : 'InActive');

   $return = '<span id="reactions-toggle">';
   if ($state === 'Active') {
      $return .= wrap(anchor('<div class="toggle-well"></div><div class="toggle-slider"></div>', '/reactions/toggle?'.http_build_query($qs), 'Hijack'), 'span', ['class' => "toggle-wrap toggle-wrap-on"]);
   } else {
      $return .= wrap(anchor('<div class="toggle-well"></div><div class="toggle-slider"></div>', '/reactions/toggle?'.http_build_query($qs), 'Hijack'), 'span', ['class' => "toggle-wrap toggle-wrap-off"]);
   }

   $return .= '</span>';

   return $return;
}
