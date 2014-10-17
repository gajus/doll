# Development Policy

Before making a pull request, ensure that your branch is passing the [test cases](https://github.com/gajus/doll/tree/master/tests).

Please follow the current coding style.

If you would like to help take a look at the [list of issues](https://github.com/gajus/doll/issues).

# Setup Your Development Environment

Install the dependencies:

```sh
composer update --dev
```

Setup an empty database with a name of your choice (e.g. `doll`).

Make a copy of `phpunit.xml.dist`:

```sh
cp phpunit.xml.dist phpunit.xml
```

Update the database connection settings in the `<php>` section of the `phpunit.xml`.

Run tests

```
phpunit
```