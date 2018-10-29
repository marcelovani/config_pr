Config Pull Request
===================

Allows Admin users to request a pull request of config changes on live environments.
When last minute/urgent changes need to be done on the Admin UI, the user can issue
a Pull request that can be reviewed and merged by the dev team.

How it works
============

After the Admin is done with the changes, they will visit the Configuration Management page,
click on the Pull Request tab, select the configs that they want to keep in the Pull Request.
They will confirm the repo url and give it a title and description, then press the submit button.

The module will check user authentication on the repo and create the pull request.
It can also notify devs about the Pull Request.

The devs will review, comment, accept or reject the Pull Request.

Pros
====

* Speeds up the process of exporting last minute/urgent changes
* Allows Admin users to tweak the configurations quickly and keep the changes

Installation
============
Use composer to make sure you will have all dependencies.
`composer require drupal/config_pr:^1.0`

Dependencies
============
- knplabs/github-api:^2.10
- php-http/guzzle6-adapter:^1.1

Configuration
=============
- Enable the module as usual and configure permissions
- Go to the /user page and add your Authenticatio token. To learn how to create authentication tokens,
  visit https://help.github.com/articles/creating-a-personal-access-token-for-the-command-line/
- Go to the module settings page /admin/config/development/configuration/pull_request/settings and add your
  repo user name and repo name. Normally these are found on the repo Url.
  i.e. https://github.com/marcelovani/captcha_keypad the username = marcelovani and repo name = captcha_keypad

Creating pull requests
======================
- Visit the Config Manager page /admin/config/development/configuration and click on the 'Pull Request' tab
- Select the configs you want to add to the pull request
- Fill in the pull request details and click the button
After the form is submitted, you will see a message with the link to the pull request. At the bottom of the page
you will see a list of open pull requests for the relevant repo.
