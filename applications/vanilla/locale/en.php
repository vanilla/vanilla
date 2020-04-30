<?php if (!defined('APPLICATION')) exit();
// phpcs:ignoreFile
/**
 * Vanilla's default locale.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 * @package Vanilla
 * @since 2.0
 */

$Definition['Allow links to be transformed'] =
    'Allow links to be transformed into embedded representations in discussions and comments. For example, a YouTube link will transform into an embedded video.';
$Definition['EmbeddedDiscussionFormat'] = '<div class="EmbeddedContent">{Image}<strong>{Title}</strong>
<p>{Excerpt}</p>
<p><a href="{Url}">Read the full story here</a></p><div class="ClearFix"></div></div>';
$Definition['EmbeddedNoBodyFormat'] = '{Url}';
$Definition['CategoryID'] = 'Category';
$Definition['We\'ve received a request to change your password.'] = 'We\'ve received a request to change your password at %s. If you didn\'t make this request, please ignore this email.';

// Deprecated Translations
$Definition['Start a New Discussion'] = 'New Discussion';

