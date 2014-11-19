# WordPress Domain Changer [![Build Status](https://travis-ci.org/veloper/WordPress-Domain-Changer.svg?branch=master)](https://travis-ci.org/veloper/WordPress-Domain-Changer)

A stand-alone tool that helps ease the migration of WordPress sites from one domain/URL to another.

## Instructions

1. Backup your WordPress site's database.
2. Seriously, [Back Up Your Database!](http://codex.wordpress.org/Backing_Up_Your_Database)
3. Situation One: The WordPress files and database have not moved -- only the domain/URL has changed.
    * Skip to step 5.
4. Situation Two: You intend to move an existing WordPress site to a new server.
    1. Export the current WordPress database to a SQL dump file.
    2. Create a MySQL database on the new server and import the SQL dump file.
    3. Open up the WordPress `wp-config.php` file and set the `DB_HOST`, `DB_USER`, `DB_PASSWORD`, and `DB_NAME` constants to the correct values for the new server.
    4. Upload the WordPress files to the appropriate directory on the new server.
5. Open up the `wpdc/config.php` file and replace the default password with a **VERY** secure password of your choice.
6. Upload the `wpdc/` directory itself to the root directory of your WordPress site.
    * _Note:_ The root directory is where the `wp-config.php` file is located.
7. Open a web browser and navigate to: `http://whatever-your-new-domain-is.com`**`/wpdc`**
8. Login with the password you entered in Step 6.
9. Follow the on-screen instructions & steps.
10. Remove the `wpdc/` directory from the server when you're finished.

## Website

[Blog Post & Overview](http://dan.doezema.com/2014/11/wordpress-domain-changer-version-2.0/)

## License

Wordpress Domain Changer is released under the New BSD license.

http://dan.doezema.com/licenses/new-bsd/

## Author

[Daniel Doezema](http://dan.doezema.com)

## Contributors

* [Kevin deLeon](http://www.kevin-deleon.com/)
* [mike-rsi](https://github.com/mike-rsi)
* [Salko Dmytro](http://salko.org.ua/)
