# WordPress Domain Changer

A dependency-free tool developed to help ease migration of WordPress sites from one domain to another.

## Usage

1. Backup your WordPress database.
2. Seriously, [Back Up Your Database!](http://codex.wordpress.org/Backing_Up_Your_Database)
3. Situation One: You have a new server where you intend to upload your existing WordPress site files.
    1. Export the current WordPress database data into a SQL dump file.
    2. Create a MySQL database on the new server.
    3. Import the WordPress database dump file into the newly created database.
    4. Open up the wp-config.php file and set the `DB_HOST`, `DB_USER`, `DB_PASSWORD`, and `DB_NAME` constants to the correct values for the new server.
    5. Upload the WordPress directory contents to the domain directory on the new server.
4. Situation Two: The WordPress files and database locations have not changed — just the domain name.
    * Coninue on to Step 5...
5. Open up `wpdc/config.php` file in a text editor and replace the default password with a **VERY** secure password of your choice.
6. Upload the entire `wpdc/` directory to the root directory of your WordPress site.
    * _Note:_ The root directory is where the wp-config.php is located.
7. In a web browser go to: `http://www.your-new-domain.com/wpdc`
8. Type in the password that you set in step 6 at the authentication prompt.
9. You will now be presented with the domain changer form.
    1. The script will try to auto-detect all of the settings, but it’s up to you to confirm they are all correct.
10. Take one last look at the settings and verify that they are all correct
11. Click the "Change Domain!" button.
12. Go to your site’s home page at the new domain — all should be working!
13. Once the domain has been changed remove this `wpdc/` directory from the server!


## Testing

### OS X

1. [Install Homebrew](https://github.com/Homebrew/homebrew/wiki/Installation)
2. Install Composer

        brew update
        brew tap homebrew/homebrew-php
        brew tap homebrew/dupes
        brew tap homebrew/versions
        brew install php55-intl
        brew install homebrew/php/composer
3. Run `./test.sh` from the command line.



## Website

[Blog Post Overview](http://dan.doezema.com/2010/04/wordpress-domain-change/)

## License

Wordpress Domain Changer is released under the New BSD license.

http://dan.doezema.com/licenses/new-bsd/

## Author

[Daniel Doezema](http://dan.doezema.com)

## Contributors

* [Kevin deLeon](http://www.kevin-deleon.com/)
* [mike-rsi](https://github.com/mike-rsi)