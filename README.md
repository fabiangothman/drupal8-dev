# Drupal 8 dev environment

[![Build Status](https://travis-ci.com/async-rs/async-std.svg?branch=master)](https://travis-ci.com/async-rs/async-std)
[![License](https://img.shields.io/badge/license-MIT%2FApache--2.0-blue.svg)](https://github.com/async-rs/async-std)

Fabian's develop enviroment for Drupal 8.

### This project is about:
- Drupal theme customization
- Custom Drupal 8 widget using Paragraph module, Redix template and subtheme component creation with Bootstrap, webpack and Twig!.
- Custom Drupal module to show a new page including it to an admin/structure menu 

## Check before
- Windows (https://www.drupal.org/node/594744):
    - Check Cygwin (To run native Linux apps on Windows) is installed
    - Check composer is installed
    - Check drush 9.* is installed (for Drupal 8.4+)
        - composer global require drush/drush:9.*
    - Check npm & nodejs is installed

## Installation
- Install composer packages

## Credentials
- Default local URL: drupal-8.test
- Username: admin
- Password: 123456

## Database
There is a base database on the root project called drupal_test.sql. Import it directly into your MySQL/MariaDB database.

# Paragraph Process
- composer require drupal/paragraphs
- Create the paragraph content (squeleton) via admin
- composer require drupal/radix
- drush en components
- drush --include="themes/contrib/radix" radix:create radix_paragraphs
- Subtheme created into /themes/custom/radix_paragraphs
- Go to appearance and install as default the new theme
- Compile Bootstrap:
    - cd themes/custom/radix_paragraphs
    - Remove package.json postinstall line (somethimes generates some problems)
    - npm install
    - npm run dev
- Disable CSS and Javascript agreggation
    - Configuration/development/Bandwith optimization
- Order blocks into correct regions
    - https://www.webwash.net/getting-started-with-bootstrap-4-using-radix-in-drupal/#add-blocks-to-the-right-region
- Enable twig debugging
    - https://www.drupal.org/docs/theming-drupal/twig-in-drupal/debugging-twig-templates
- Remove cache
    - drush cr
- Now we'll see comments into the F12 code inspect
- Add a class name to the sub theme template
    - Intercept subtheme preprocessor radix_paragraphs.theme, adding function HOOK e.j.: radix_paragraphs_preprocess_HOOK
        - radix_paragraphs_preprocess_paragraph__card_deck
- Remove cache
    - drush cr
- Now the class is added
- Now we're going to overwrite a twig template from the node_modules paragraph:
    - Copy node_modules/paragraphs/templates/paragraph.html.twig
    - Create a folder into /themes/custom/radix_paragraphs/templates/ and paste the file there.
    - rename the file with the new class name: paragraph--name.html.twig
    - Edit it
- Copy redix template field.html.twig file and paste it into the subtheme field folder, renaming it to: 
    - field--paragraph--field-name.html.twig
- Remove cache
    - drush cr
- Use paragraphs library
    - composer require drupal/entity_usage

# Module process
- Create module folder into /modules/custom/my_module
- Create a my_module.info.yml at root (Contains all module/theme information, compatibility)
- Create a my_module.routing.yml at root (Contains all routing rules and Controllers)
    - https://www.drupal.org/docs/drupal-apis/routing-system/structure-of-routes
    - You can pass arguments to the controller
- Create Controller/s for module
- Erase cache always
- Create a menu into Structure page
    - Create links file at root:
        - my_module.links.menu.yml


https://www.drupal.org/docs/drupal-apis/routing-system/structure-of-routes