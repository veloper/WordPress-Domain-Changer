# WordPress Domain Changer

## Purpose

A self-contained script/tool developed to help ease migration of WordPress sites from one domain to another.

## Usage

Please Visit [This Page](http://dan.doezema.com/2010/04/wordpress-domain-change/) for a complete overview.

1. Backup your WordPress database.
2. Seriously, [Back Up Your Database!](http://codex.wordpress.org/Backing_Up_Your_Database)
3. Situation One: You have a new server where you intend to upload your existing WordPress site files.
   1. Export the current WordPress database data into a sql dump file.
   2. Create a MySQL database on the new server.
   3. Import the WordPress database dump file into the newly created database.
   4. Open up the wp-config.php file and set DB\_HOST, DB\_USER, DB\_PASSWORD, and DB\_NAME to the correct values for the new server.
   5. Upload the WordPress directory contents to the domain directory on the new server.
4. Situation Two: The WordPress files and database locations have not changed — just the domain name.
   1. Skip to step 5.
5. Open up wp-change-domain.php in a text editor and scroll down to the “CONFIG” section.
6. Under “Authentication Password” replace the default password with a VERY secure password of your choice.
7. Upload wp-change-domain.php to the root directory of the WordPress site.
   1. the root directory is where the wp-config.php is located.
8. In a web browser go to: http://www.yourNewDomain.com/wp-change-domain.php
9. Type in your password that you set in step 6 at the authentication prompt.
10. You will now be presented with the domain changer form.
   1. The script will try and auto-detect all of the settings, but it’s up to you to confirm they are all correct.
11. Take one last look at the settings to verify that they are correct… then click the “Submit!” button.
12. Go to your site’s home page at the new domain — all should be working!
13. Once the domain has been changed remove this script from the server!

## Website

http://dan.doezema.com/2010/04/wordpress-domain-change/

## License

Wordpress Domain Changer is released under the New BSD license.
http://dan.doezema.com/licenses/new-bsd/

## Author

[Daniel Doezema](http://dan.doezema.com)

## Contributors 

[Kevin deLeon](http://www.kevin-deleon.com/)