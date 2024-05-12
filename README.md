<h1 align="center">DatabaseConstructor</h1>

*DatabaseConstructor* is a web-based platform for collaborative database construction, aiming particularly at enhancing data gathering by hand for empirical research, and is still under heavy development. It emerges from the [Germany, Inc.](https://www.jura.fu-berlin.de/en/forschung/fuels/Projects/Germany-Inc/index.html) project at [Freie Universität Empirical Legal Studies Center (FUELS)](https://www.jura.fu-berlin.de/en/forschung/fuels/) in Berlin. The user interface is German-only at the moment.

## Installation

**System Requirements:**

* Apache HTTPD server
  * PHP ≥ 7.0 installed
  * `mod_rewrite` enabled
  * Configured to interpret `.htaccess` files
* MySQL/MariaDB server (tested with MySQL 5.7.17, MariaDB 10.1.48, 10.4.8)

Download a package package from the [releases page](https://github.com/SyntaxCacao/DBConstructor/releases/) on GitHub or make a build (see below). Move the files on the web server. Rename `tmp/config.default.php` to `tmp/config.php` and adjust the settings to your environment. Then call `<base URL>/migrate/?key=<key>` using the migration key in your configuration file to perform a database scheme migration. To sign in for the first time, use "admin" as username and password. Change the credentials afterwards.

To **update** your installation, delete all files except for (!) the `tmp` directory and move the files of the new build on the server. Then perform a database scheme migration as described before.

## Build

To make a build, make sure to have installed [Composer](https://getcomposer.org/download/) globally (version 2.2.X, as Composer requires PHP ≥ 7.2.5 from version 2.3 onwards (see [here](https://github.com/composer/composer/issues/10340)), and we need support back to PHP 7.0), as well as the [Node.js Package Manager](https://docs.npmjs.com/downloading-and-installing-node-js-and-npm/) (NPM) and the `grunt-cli` package (`npm install -g grunt-cli`). First, clone the repository. To build a specific version, check out the corresponding [tag](https://github.com/SyntaxCacao/DBConstructor/tags/) (`git checkout <tag name>`). Then run `composer install` and `npm install` to load dependencies, followed by `npm run-script build` or `grunt` to make a build. The `dist` directory will contain all build files.

```
git clone git@github.com:SyntaxCacao/DBConstructor.git dbc
cd dbc
composer install
npm install
npm run-script build
```

Add the following for preparing a release:

```
mv dist dbc
zip dbc-v0.4.2.zip -r dbc
```

When making changes to the codebase, you need to run `grunt` again every time you make a change. Use `grunt watch` to make Grunt apply changes continously by watching the file system for updated files.

## Contributing

Feel free to report issues or suggest changes using the [Issue Tracker](https://github.com/SyntaxCacao/DBConstructor/issues/) or open a [Pull Request](https://github.com/SyntaxCacao/DBConstructor/pulls/) right away.

## License

This project is made available under the conditions of the MIT License (see [LICENSE file](https://github.com/SyntaxCacao/DBConstructor/blob/main/LICENSE/)). The repository contains parts of the [Inter](https://rsms.me/inter/) typeface, licensed under the [SIL Open Font License](https://github.com/rsms/inter/blob/master/LICENSE.txt), Version 1.1, parts of the [Primer](https://primer.style/) design system, licensed under the [MIT License](https://github.com/primer/css/blob/main/LICENSE), parts of [plotly.js](https://github.com/plotly/plotly.js/), licensed under the [MIT License](https://github.com/plotly/plotly.js/blob/master/LICENSE), and the "cubes" icon from [Font Awesome 5](https://fontawesome.com/), licensed as [CC BY 4.0](https://fontawesome.com/license/free). Builds also contain [Parsedown](https://github.com/erusev/parsedown/), licensed under the [MIT License](https://github.com/erusev/parsedown/blob/master/LICENSE.txt), and [Bootstrap Icons](https://github.com/twbs/icons/), licensed under the [MIT License](https://github.com/twbs/icons/blob/main/LICENSE.md).
