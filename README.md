# druman
Console app to administer multiple drupals in a similar way as drush group alias used to be, 
but integrating also composer in the workflow.

##Start
place druman-aliases to root home directory:
`cp examples/druman-aliases.yml ~/.druman-aliases`

Edit `~/.druman-aliases` to suit your needs.

### Some notes about implemented managers
- **drush8-alias**
Useful when updating and when calling drush commands (via run). Drush8-alias manager is the way to mantain a remote(or local) site via drush+ssh, instead of reinvent the logics of drush remote alias. The alias will be treaten without changing to project directory, nor changing the user. It will prepend the `drush @alias` command(where alias is taken from alias set in yml), just add the drush command like: `druman project:run -a project_x status`. 
- **drupal-composer**
Will be useful to update (useless in run), using composer manager to do update process in drupal-composer scaffoldings.
- **drush8**
Will be useful to update (useless in run), using drush (v8) manager to do update process in standard drupal 7 and 8(not recommended) drupals.
