# Q&A

Users may designate a discussion as a Question and then officially accept one or more of the comments as the answer.

### Notes:

- You can set  `NewDiscussionModule.Types.Question.AsOwnButton = true` in config to separate "New Discussion" and "Ask Question" into "separate" forms each with own big button in Panel.

### Features:

`Feature.DiscussionQnATag.Enabled`:  Displays a Q&A meta tag in the discussion header.

`Feature.QnAFollowUp.Enabled`: Enables the "Question Follow Up Notifications".



## Question Follow-Up Notifications:

Sends an email to all authors of a question that has answers, but hasn't been accepted after a certain amount of time has passed. The goal is to re-enforce the Q&A workflow and increase the number of accepted answers or allow staff to go in and make sure the leftovers have adequate answers. This feature is enabled per category.

This feature enables one field on the dashboard:

- **Follow-up Interval:** Number of days the system should wait before triggering another batch of notifications.

To activate this feature on a category, activate the toggle "Enable Q&A follow-up notifications". This toggle is only available on categories of type "Discussions".



Follow-up notifications can be triggered in two ways:

- Automatically: Once the feature flag is enabled, a service starts to run triggering notifications in a defined period of time.
- Manually: Users with `Garden.Community.Manage` permission or higher can trigger this feature manually by clicking the discussion option "Send Q&A follow-up notification" on the discussion thread page.



### Troubleshooting:

**The "Send Q&A follow-up notification" option is not displaying:**

This can happen for a number of reasons. Remember this feature is activated per category, so the category where the question was posted needs to have the "Enable Q&A follow-up notifications" toggle activated. Next, make sure the question where you're trying to trigger this option is in fact a question, and if it is, make sure that it has answers and that none of the answers are accepted.

**The author is not receiving follow-up notifications:**

- Make sure the author has the preference "Send me a follow-up for my answered questions." (`Notification.Email.QuestionFollowUp`) enabled. If you're triggering this notification manually, the pop-up will display a message with more information.
