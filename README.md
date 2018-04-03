# druman
https://planet.communia.org/content/managing-multiple-drupals-7-and-8-composer-and-drush
Console app to administer multiple drupals in a similar way as drush group alias used to be, 
but integrating also composer in the workflow.

When the number of projects to mantain grow, a strategy is needed to
carry out mass actions such as modules or libraries installation,
upgrades, cleaning of caches or registries to monitor.

Until version 8, drush allowed to create alias and group them together
to perform operations by group. For example, an \@autoup group could be
created that allowed updating the drupals that were known to have had no
reason at all to give bugs in the update.

All this has come under review with Drupal 8, at the time that drupal
goes on to follow the open innovation strategy that allows the inclusion
of code not specifically designed for drupal, also known as innovation
strategy [*Proudly Found Elsewhere*](https://www.drupal.org/8/standards)
(such as symfony, easyrdf, doctrine, twig \...).The orchestration of all
the dependencies is done via
[*composer*](https://getcomposer.org/doc/00-intro.md) , and specifically
from drupal 8 it is recommended to install a [*structure
(scaffolding)*](https://github.com/drupal-composer/drupal-project) .

This gives us these changes in the structure of the project: Where you
really manage the dependencies is at web\'s parent directory, it is in
the root folder of drupal-project where we will run composer to install
dependencies.

All these changes imply a new perspective to manage the project, we can
no longer rely on drush, and in fact the version of drush 9.x [*already
warns us*](https://drushcommands.com/drush-9x/pm/pm:download/) that
installing modules for drush is abandoned.

Since I have not found a tool to manage multiple composer projects, I
have created the [*druman*](https://github.com/aleixq/druman) console
[*application*](https://github.com/aleixq/druman) with the symfony
[*console command*](https://symfony.com/doc/current/console.html)
component .So you can do a project management based on drush, or
composer.

**Druman project manager at drupal**

This program is based on a list defined in yml that will be \~ /
.druman-aliases.yml, where we define some properties for each project:
alias, path, groups, manager.

There is an example of a yml in the examples folder:

-   alias: it will be the name with which we will index and we will be
    able to access the project.
-   Path: this is the path of the project, if we use the structure
    drupal-composer we must give the root of the project (not that of
    the web). If we do not use the drupal-composer structure we have to
    put the web root. If we want a remote alias we have to write \"\"
    there.
-   Groups: list of groups to which the project belongs, for example
    autoup if we want to do a group of self-updating or hacked if we
    want to list projects in which we have to pay special attention when
    updating or installing modules. (you can put the names that you
    want, those that have been put in the example are examples).
-   Manager: Which manager we will use to control dependencies:
    - **drush8** It will be useful for the command projects:update
    (useless in command projects:run), it uses the drush (v8) manager to
    update it following the standard drupal 7 procedure (not recommended
    by drupal 8).
    - **drupal-composer** It will be useful for the command
    projects:update (useless in command projects:run), it uses the
    composer manager to proceed with the update in drupal-composer
    structures.
    - **drush8-alias** It will be useful when updating project process
    (with the projects:update command) and when drush commands are
    called (via command projects:run).Indicate the drush8-alias manager
    is the way to keep a remote (or local) site defined in the drush
    aliases, instead of reinventing the logic of remote drush alias, I
    have chosen to reuse it. Aliases will be treated without changing to
    the project directory (since the path is not defined) or by changing
    the user, it is done just like drush aliases (with possible
    subsequent permissions problems that may arise). When calling, drush
    \@alias will always be added, so the drush and druman alias must
    match, and the drush command should only be said, for example:\
     `druman project: run -a project\_x status`

## Start
place druman-aliases to root home directory:
`cp examples/druman-aliases.yml ~/.druman-aliases`

Edit `~/.druman-aliases` to suit your needs.

**Remote project management (only drupal 7).**

We have to pay special attention to the fact that if we use a
drush8-alias manager in a project to manage remote projects, we must
first define the remote alias as always with drush until version 8. This
is so by not having to rewrite the logic that drush uses to connect to
remote (To add an alias to drush there is information at:
[*https://raw.githubusercontent.com/drush-ops/drush/8. x / examples /
example.aliases.drushrc.php*](https://raw.githubusercontent.com/drush-ops/drush/8.x/examples/example.aliases.drushrc.php)
).Thus, the alias must be defined in \~ / .drush / aliases.drushrc.php
and \~ / .druman-aliases.yml, maybe in the future this changes to be
able to launch remotely composers.

 

If we define a project managed by **drush8-alias** the path must be
blank
```
alias: someexternal\_com

path: \"\"

groups: hostingatx

manager: drush8-alias
```
**List projects**

To dump the **list of projects** we can use:
```
druman projects:list
```
 

In order to **filter** the list by group or by origin (local or remote),
this command allows the following options:
```
druman projects: list -h

Usage:

projects: list \[options\]

 

Options:

-g, \--group \[= GROUP\] List only projects of specified group

-l, \--local List only local projects

-r, \--remote List only remote projects

-f, \--full Show all fields from list: alias, path, management type and
group.
```
 

**Running commands at the root of projects**

From the list of projects defined in .druman-aliases.yml we can perform
operations. We can throw an order at the root of the project with:
```
projects:run
```
We will have the same filtering options as when we list and additionally
the option of selecting a single aliases directly. The options are:
```
druman projects: run -h

Usage:

projects: run \[options\] \[-\] \[\<order\>\]

 

Arguments:

order Command to run.

 

Options:

-g, \--group \[= GROUP\] Run only on these projects which are members of
specified group

-a, \--alias \[= ALIAS\] Run only on this specific alias

-l, \--local List only local projects

-r, \--remote List only remote projects

-all, \--all Run in all alias, except those using drush8-alias manager,
if specified no filters will be used
```
Before executing the command it will **change the user** by who is the
owner of the folder defined in the path of the alias.

**Running project updates**

To update, there is the command druman projects:update, which allows the
same options as the projects:run command.
```
projects:update -h
 

-g, \--group \[= GROUP\] Run only on these projects which are members of
specified group

-a, \--alias \[= ALIAS\] Run only on this specific alias

-l, \--local List only local projects

-r, \--remote List only remote projects

-all, \--all Run in all alias, excluding those using drush8-alias
manager
```
The way you update the projects managed by **drush8-alias** or
**drush8** follow the orders to update a **drupal 7** .The way you
update the projects managed by **drupal-composer** follow the procedure
to update a **drupal 8** .You can see the procedures at
[*https://github.com/aleixq/druman/blob/master/src/Command/ManagerRunnerProjectsCommand.php*](https://github.com/aleixq/druman/blob/master/src/Command/ManagerRunnerProjectsCommand.php)
.

**What\'s left:**

-   Remote, be able to manage remote drupal with composer. Maybe the way
    would be to add ssh properties to .druman-aliases.yml. Or, as with
    those indicated by a drush8-alias manager, define a remote alias as
    explained in
    [*https://raw.githubusercontent.com/drush-ops/drush/master/examples/example.site.yml*](https://raw.githubusercontent.com/drush-ops/drush/master/examples/example.site.yml)
    .
-   also add, remove or update projects from .druman-aliases.yml
    interactively.
    
