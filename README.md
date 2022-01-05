<h1 align="center">DatabaseConstructor</h1>

*DatabaseConstructor* is a web-based platform for collaborative database construction, aiming particularly at enhancing data gathering by hand for empirical research, and is still under heavy development. It emerges from the [Germany, Inc.](https://www.jura.fu-berlin.de/en/forschung/fuels/Projects/Germany-Inc/index.html) project at [Freie Universität Empirical Legal Studies Center (FUELS)](https://www.jura.fu-berlin.de/en/forschung/fuels/) in Berlin. The user interface is German-only at the moment.

## Installation

**System Requirements:**

* Apache HTTPD server
  * PHP ≥ 7.0 installed
  * `mod_rewrite` enabled
  * Configured to interpret `.htaccess` files
* MySQL/MariaDB server (tested with MariaDB 10.1.48, 10.4.8)

Download a package from GitHub or make a build (see below). Move the files on the web server. Rename `tmp/config.default.php` to `tmp/config.php` and adjust the settings to your environment. Then call `<base URL>/migrate/?key=<key>` using the migration key in your configuration file to perform a database scheme migration.

To **update** your installation, delete all files except for (!) the `tmp` directory and move the files of the new build on the server. Then perform a database scheme migration as described before.

## Build

To make a build, make sure to have installed Node.js Package Manager (NPM) and the `grunt-cli` package globally (`npm install -g grunt-cli`). First, clone the repository. To build a specific version, check out the corresponding [tag](https://github.com/SyntaxCacao/DBConstructor/tags/) (`git checkout <tag name>`). Then run `npm install` and `npm run-script build` *or* `grunt` to make a build. The `dist` directory contains all build files.

```
git clone git@github.com:SyntaxCacao/DBConstructor.git
npm install
npm run-script build
```

In a development environment, use `grunt watch` to make changes apply continously.

## Contributing

Feel free to report issues or suggest changes using the [Issue Tracker](https://github.com/SyntaxCacao/DBConstructor/issues/) or open a [Pull Request](https://github.com/SyntaxCacao/DBConstructor/pulls/) right away.

## License

This project is made available under the conditions of the MIT License (see [LICENSE file](https://github.com/SyntaxCacao/DBConstructor/blob/main/LICENSE/)). The repository contains parts of the [Inter](https://rsms.me/inter/) typeface, licensed under the [SIL Open Font License](https://github.com/rsms/inter/blob/master/LICENSE.txt), Version 1.1, and the [Primer](https://primer.style/) design system, licensed under the [MIT License](https://github.com/primer/css/blob/main/LICENSE). Builds contain [Bootstrap Icons](https://github.com/twbs/icons/), licensed under the [MIT License](https://github.com/twbs/icons/blob/main/LICENSE.md).
