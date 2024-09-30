<?php if (!defined("APPLICATION")) {
    exit();
}

use Vanilla\Web\TwigStaticRenderer;

echo TwigStaticRenderer::renderReactModule("FollowedContent", [
    "userID" => $this->data("userID"),
    "renderAdditionalFollowedContent" =>
        Gdn::config("Feature.GroupsFollowing.Enabled") && Gdn::config("EnabledApplications.Groups") === "groups",
]);
