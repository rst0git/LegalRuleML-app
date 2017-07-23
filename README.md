# LRML Search

## Overview

LRML Search is a web application for storing, viewing and searching LegalRuleML documents, developed as part of the LegalRuleMLParl project. Under the hood, it is a PHP 7.0 Laravel web app which uses BaseX for XML storage and querying, and PostgreSQL for all other data storage.

## Authorship and copyright

LRML Search was written by [Radostin Stoyanov](https://github.com/rst0git) and [Andrea Faulds](https://github.com/hikari-no-yume). Copyright © 2017 University of Aberdeen.

The `BaseXClient.php` is sourced from [the BaseX repository](https://github.com/BaseXdb/basex/blob/master/basex-api/src/main/php/BaseXClient.php), and is licensed under the BSD license. Copyright © BaseX Team 2005-15.

## Getting started
#### Install [Docker](https://docs.docker.com/engine/installation/)
- [Debian](https://docs.docker.com/v1.12/engine/installation/linux/debian/)
- [Ubuntu](https://www.digitalocean.com/community/tutorials/how-to-install-and-use-docker-on-ubuntu-16-04#step-1-—-installing-docker)
- [Linux Mint](http://linuxbsdos.com/2016/12/13/how-to-install-docker-and-run-docker-containers-on-linux-mint-1818-1/)
- [Arch linux](https://wiki.archlinux.org/index.php/Docker#Installation)

#### Install [docker-compose](https://docs.docker.com/compose/install/)
- For Ubuntu
```sh
apt-get install -y docker-compose
```

- Using pip
```sh
pip install docker-compose
```
#### Install the php extensions: `php-zip`, `php-mbstring`, `php-xml`
- For Ubuntu
```sh
apt-get install -y php7.0 php7.0-zip php7.0-mbstring php7.0-xml
```

#### Copy `.env.example` to `.env`
```sh
cp ./src/.env.example ./src.env
```

#### Run `composer install`
```sh
cd ./src
curl -sS https://getcomposer.org/installer | php
php composer.phar install
```

#### Execute `run.sh`
```sh
./run.sh
```

#### Finally set file permissions and generate session key
```
chmod -R 755 src/
chmod -R o+w src/storage/
chmod -R o+w src/bootstrap/cache/
chmod -R o+w src/public/uploads/
docker-compose exec web bash -c "php artisan key:generate"
```

#### User account

By default (see `src/database/seeds/UsersTableSeeder.php`), a user account with the email address `user@localhost` and the password `lrmlsearch` is created. With this account, you can log in and upload or delete documents, as well as create other user accounts.

For security reasons it is not a good idea to keep this initial account around; delete it or change its password. (Unfortunately, neither can be done directly through the interface right now.)

## File structure

Routes
- `src/routes/web.php` This file contains the routes of the application

Views
- `src/resources/views/index.blade.php` Home Page
- `src/resources/views/dashboard.blade.php` This view appears after user has logged in successfully.
- `src/resources/views/search.blade.php` This view is used for the search functionality.

- `src/resources/views/documents/index.blade.php` Show a list of all documents.
- `src/resources/views/documents/show.blade.php` Show a HTML content of a document.
- `src/resources/views/documents/upload.blade.php` Upload new document.

- `src/resources/views/layouts/app.blade.php` The layout file - contains the code which appears on every page

- `src/resources/views/inc/navbar.blade.php` Navigation bar
- `src/resources/views/inc/messages.blade.php` Success/Error messages shown on the top of a page

- `src/resources/views/auth/login.blade.php` Login page
- `src/resources/views/auth/register.blade.php` Registration page
- `src/resources/views/auth/passwords/email.blade.php` Request reset password
- `src/resources/views/auth/passwords/email.blade.php` Confirm reset password

Controllers
- `src/app/Http/Controllers/Auth/ForgotPasswordController.php` This controller is responsible for handling password reset emails.
- `src/app/Http/Controllers/Auth/LoginController.php` This controller handles authenticating users.
- `src/app/Http/Controllers/Auth/RegisterController.php` This controller handles the registration of new users.
- `src/app/Http/Controllers/Auth/ResetPasswordController.php` This controller is responsible for handling password reset requests.
- `src/app/Http/Controllers/BaseXClient.php` PHP client for BaseX.
- `src/app/Http/Controllers/BaseXController.php` This controller is responsible for the interaction with BaseX.
- `src/app/Http/Controllers/Converter.php` XML to HTML converter
- `src/app/Http/Controllers/DashboardController.php` Controller for the Dashboard shown after successful user login.
- `src/app/Http/Controllers/DocumentsController.php` This controller is responsible Upload/Delete/Show/Download of documents.
- `src/app/Http/Controllers/PagesController.php` This controller handles requests to the Home Page.
- `src/app/Http/Controllers/SearchController.php` This controller handles search requests.

Models
- `src/app/Document.php` Store Title, File name and HTML version of documents.
- `src/app/User.php` Store user credentials used for authentication.

Assets
- `src/public/css/app.css` Compiled CSS which includes JQuery and Bootstrap.
- `src/public/css/custom.css` Custom CSS rules. They overwrite `app.css`.
- `src/public/css/lrml.css` Style for LegalRuleML elements
- `src/public/js/app.js` Compacted JavaScript which includes JQuery and Bootstrap.
