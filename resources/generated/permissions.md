# Vanilla Permissions Reference

<!-- AIDEV-NOTE: Generated permissions table with descriptions from codebase analysis -->
<!-- This table maps permission keys to their descriptions and usage contexts -->

| Permission | Description |
|------------|-------------|
| `advancedNotifications.allow` | Allow access to advanced notification features and settings |
| `applicants.manage` | Manage user role applications and approval processes |
| `articles.add` | Create new articles in knowledge base or content management system |
| `articles.manage` | Full management of articles including editing and deletion |
| `badges.manage` | Administrative management of badges system including creation and configuration |
| `badges.moderate` | Moderate badge requests and manage badge assignments |
| `badges.request` | Request badges from the community or moderators |
| `badges.view` | View badges and badge information |
| `comments.add` | Post comments on discussions and other content |
| `comments.delete` | Delete comments (own or others based on context) |
| `comments.edit` | Edit comments (own or others based on moderation level) |
| `community.manage` | High-level community management including structure and policy |
| `community.moderate` | Moderate community content, users, and enforce community guidelines |
| `conversations.add` | Start private conversations with other users |
| `conversations.moderate` | Moderate private conversations (admin/moderator access) |
| `curation.manage` | Manage content curation features and curated content lists |
| `customUpload.allow` | Upload custom files beyond standard restrictions |
| `dashboards.manage` | Manage dashboard configurations and layouts |
| `data.view` | View analytics data and reporting information |
| `discussions.add` | Create new discussions/topics in categories |
| `discussions.announce` | Mark discussions as announcements |
| `discussions.close` | Close discussions to prevent further comments |
| `discussions.delete` | Delete discussions |
| `discussions.edit` | Edit discussion titles, content, and properties |
| `discussions.manage` | Full discussion management including all discussion operations |
| `discussions.sink` | Sink discussions (reduce visibility/priority) |
| `discussions.view` | View discussions (may be restricted in private categories) |
| `email.view` | View email addresses and email-related information |
| `emailInvitations.add` | Send email invitations to join the community |
| `events.manage` | Manage events including creation, editing, and configuration |
| `events.view` | View events and event details |
| `exports.manage` | Manage data exports and export operations |
| `flag.add` | Flag content for moderator review |
| `groups.add` | Create new groups |
| `groups.moderate` | Moderate groups and group content |
| `internalInfo.view` | View internal system information and debugging data |
| `kb.view` | View knowledge base content |
| `noAds.use` | Browse without advertisements |
| `personalInfo.view` | View personal information of users (PII access) |
| `pockets.manage` | Manage pockets (custom content insertion areas) |
| `polls.add` | Create polls in discussions |
| `posts.moderate` | Moderate posts across the platform |
| `profile.editusernames` | Edit usernames (distinct from general profile editing) |
| `profilePicture.edit` | Edit profile pictures |
| `profiles.edit` | Edit user profiles (own or others based on context) |
| `profiles.view` | View user profiles |
| `reactions.negative.add` | Add negative reactions to content |
| `reactions.positive.add` | Add positive reactions to content |
| `reactions.view` | View reactions on content |
| `schedule.allow` | Access to scheduling features and scheduled content |
| `session.valid` | Maintain valid user sessions |
| `settings.view` | View system settings and configuration |
| `signatures.edit` | Edit user signatures |
| `site.manage` | Full site administration and configuration management |
| `staff.allow` | Staff-level access to administrative features |
| `tags.add` | Add tags to content |
| `tokens.add` | Create API tokens and access tokens |
| `uploads.add` | Upload files and attachments |
| `users.add` | Create new user accounts |
| `users.delete` | Delete user accounts |
| `users.edit` | Edit user account information and properties |
| `zendeskBasicTicket.view` | View basic Zendesk ticket information |
| `zendeskCreateArticle.allow` | Create articles in Zendesk integration |
| `zendeskEscalateOwnContent.allow` | Escalate own content to Zendesk support |

## Automatic Permission Mappings

<!-- AIDEV-NOTE: These mappings are automatically handled by PermissionsTranslationTrait.php -->
<!-- Do not manually convert these - the system handles translation automatically -->

### Legacy to Modern Permission Mappings

The following legacy permissions are automatically translated to modern equivalents:

| Legacy Permission | Modern Permission |
|-------------------|-------------------|
| `Conversations.Moderation.Manage` | `conversations.moderate` |
| `Email.Comments.Add` | `comments.email` |
| `Email.Conversations.Add` | `conversations.email` |
| `Email.Discussions.Add` | `discussions.email` |
| `Garden.Moderation.Manage` | `community.moderate` |
| `Garden.NoAds.Allow` | `noAds.use` |
| `Garden.Settings.Manage` | `site.manage` |
| `Garden.SignIn.Allow` | `session.valid` |
| `Garden.Username.Edit` | `profile.editusernames` |
| `Garden.Users.Approve` | `applicants.manage` |
| `Groups.Group.Add` | `groups.add` |
| `Groups.Moderation.Manage` | `groups.moderate` |
| `Reputation.Badges.Give` | `badges.moderate` |
| `Vanilla.Tagging.Add` | `tags.add` |

### Consolidated Permissions

These permissions are automatically consolidated into broader permissions:

| Consolidated Permission | Includes These Permissions |
|------------------------|----------------------------|
| `discussions.moderate` | `discussions.announce`, `discussions.close`, `discussions.sink` |
| `discussions.manage` | `discussions.delete`, `discussions.edit` |

### Deprecated Permissions

The following permissions are deprecated and should not be used:
- `Garden.Activity.Delete`
- `Garden.Activity.View`
- `Vanilla.Comments.Me`

### Fixed Permissions

These permissions maintain their original naming format and are not automatically translated:
- `Reactions.Negative.Add`
- `Reactions.Positive.Add`

### Usage Notes

- Legacy permission names are automatically translated by the system - do not manually convert them
- When checking permissions in code, use the modern permission names
- The system maintains backward compatibility by automatically handling legacy names
- In new code, always use the modern permission naming convention
