---
name: Deploy new release
about: Perform a scheduled deploy of a new release
title: Staging|Public|Enterprise deploy of 202x.000 (Month Day Year)
labels: 'Priority: High, Type: Deploy'
assignees: ''

---

**Report any unexpected state or behavior before proceeding with any deploy. This includes, but is not limited to, broken symlinks.**

## Release Checklist

### Prepare for the Deploy
- [ ] Once a month, deploy 60011 cluster. (Put the cluster on the previous release).
- [ ] [Read the current rules before deploying.](https://staff.vanillaforums.com/kb/articles/129-deploying-infrastructure#rules-about-deploying)
- [ ] Prepare release notes. Ensure all teams have had the opportunity to add their notes.
- [ ] Announce the release date in the release channel at least 48 hours before the deploy to staging.
- [ ] [Verify the machine you will be deploying from has all necessary tools and utilities.](https://staff.vanillaforums.com/kb/articles/129-deploying-infrastructure#tools)
- [ ] Check for any database changes (pull requests tagged with "Release Db update").
  - [ ] If there are any changes, request a jobber to determine what customer databases might lead to alter-table threshold errors.
  - [ ] Schedule a maintenance window with customers having affected database tables exceeding one million rows. Deploy and manually apply the database updates during this window.
- [ ] Check for any risky code changes (pull requests tagged with "Release BC Break").
- [ ] Set the version in environment.php
  - [ ] Increment and remove SNAPSHOT in the release branch.
  - [ ] Increment and add SNAPSHOT in the master branch.
  
You may use the following filter as a guide for querying PRs from [GitHub's pull requests](https://github.com/pulls) page. 
```
is:merged repo:vanilla/addons repo:vanilla/vanilla-patches repo:vanilla/addons-patches repo:vanilla/vanilla-cloud base:master merged:>2020-08-01 label:"Release: DB Update"
```

### Day of the Deploy

- [ ] Confirm someone from the operations team is aware of the deploy and will be available to assist with any issues related to infrastructure.

### Dev Clusters

[10013,10014]

### Staging Clusters

[20011,20013,20014,20024,20082] (Stage deploy should include Dev clusters)

### Public Clusters

[20012,30011,40011,40012,40013,40014]

### Deploy

- [ ] Pull /var/www/vanilla-sphinx-configs (should have the same version as the release).
- [ ] Checkout frontend, addons, locales, vanilla-sphinx-configs (under /var/www) to the deploy release.
- [ ] Run this script ./cloud/scripts/symlink-addons-inf
- [ ] [Perform the deploy for each cluster receiving the release.](https://staff.vanillaforums.com/kb/articles/129-deploying-infrastructure#doing-the-deploy-(manually))
- [ ] [Verify the deploy state.](https://staff.vanillaforums.com/kb/articles/129-deploying-infrastructure#checking-deploy-state)
- [ ] Review Kibana logs for the deployed clusters for errors.
- [ ] After a push update, check every cluster terminal window, make sure the updates ran properly.
- [ ] Monitor #stagnated-queries and #dev-deploys channels in Slack.
- [ ] In the event of an enterprise deploy, spot check all enterprise sites.

### After the Deploy

- [ ] For the next 24 hrs, check the #dev-support channel for any client reported issues that might have been caused by the deploy.

### Notes
- Some database updates may fail due to alter-table thresholds. If this occurs, you should receive an email alert from Papertrail or a notification in the #dev-deploys channel. **These updates will need to be manually applied.** If the table is too big to manually perform the updates in a production environment without adversely affecting performance of the site or cluster (more than one million rows, generally speaking), **immediately bring this to the attention of the team**.
