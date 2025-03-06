<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Tests\Controllers;

/**
 * Like {@link NewPostsAndDraftsControllerTest} but runs with the new drafts feature flag enabled.
 */
class NewPostsAndDraftsControllerTest extends PostAndDraftsControllerTest
{
    protected bool $useFeatureFlag = true;
}
