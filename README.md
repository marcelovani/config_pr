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

The module will check user authenticaton on the repo and create the pull request.
It can also notify devs about the Pull Request.

The devs will review, comment, accept or reject the Pull Request.

Pros
====

* Speeds up the process of exporting last minute/urgent changes
* Allows Admin users to tweak the configurations quickly and keep the changes
