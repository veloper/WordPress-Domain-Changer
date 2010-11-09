# WARNING: THIS IS AN ACTIVE DEVELOPMENT BRANCH!
There is no guarantee that this branch of WordPress Domain Changer will work.

# WordPress Domain Changer

## Purpose

A self-contained script/tool developed to help ease migration of WordPress sites from one domain to another.

## Usage

Please Visit [This Page](http://dan.doezema.com/2010/04/wordpress-domain-change/) for a complete overview.

1. Backup your WordPress database.
2. Seriously, [Back Up Your Database!](http://codex.wordpress.org/Backing_Up_Your_Database)
3. Situation One: The WordPress files and database locations have not changed -- just the domain name.
   1. Skip to step 5.
4. Situation Two: You have a new server where you intend to upload your existing WordPress site files.
   1. Export the current WordPress database data into a sql dump file.
   2. Create a MySQL database on the new server.
   3. Import the WordPress database dump file into the newly created database.
   4. Open up the wp-config.php file and set DB\_HOST, DB\_USER, DB\_PASSWORD, and DB\_NAME to the correct values for the new server.
   5. Upload the WordPress directory contents to the domain directory on the new server.
5. Open up wp-change-domain.php in a text editor and scroll down to the "CONFIG" section.
6. Under "Authentication Password" replace the default password with a VERY secure password of your choice.
7. Upload wp-change-domain.php to the root directory of the WordPress site.
   * Note: The root directory is where the wp-config.php is located.
8. In a web browser go to: http://www.yourNewDomain.com/wp-change-domain.php
9. Type in your password that you set in step 6 at the authentication prompt.
10. You will now be presented with the domain changer form.
   * The script will try and auto-detect all of the settings, but it's up to you to confirm they are all correct.
11. If the script detects that you're running a WordPress Multi-Site install then a checkbox will be visible.
   * If checked, the domain change will be applied to **all** sites.
12. Take one last look at the settings to verify that they are correct... then click the "Change Domain!" button.
13. Go to your site's home page at the new domain -- all should be working!
14. Once the domain has been changed remove this script from the server!

## What Happens During Execution

* The following database table fields are affected.
   * [prefix]options.option\_value
   * [prefix]posts.guid
   * [prefix]posts.post\_content
   * [prefix]usermeta.meta\_value
   * If Multi-Site... 
      * [prefix]blogs.domain
      * [prefix]blogs.path
      * [prefix]site.domain
      * [prefix]site.path
      * [prefix]sitemeta.meta\_value
      * [prefix][int]\_options.option\_value
      * [prefix][int]\_postmeta.meta\_value
      * [prefix][int]\_posts.guid
      * [prefix][int]\_posts.post\_content
      
### The following only applies to Multi-Site Installs

The script will attempt to make a copy (backup) of the wp-config.php file in the wordpress root directory. 

If a copy was successfully made then an attempt is made to edit the wp-config.php file in order to reflect the changes made to the site's domain. Below are values changed in the wp-config.php file...

* Constant: DOMAIN\_CURRENT\_SITE
* Constant: PATH\_CURRENT\_SITE
* Variable: $base
    
The script will attempt to make a copy (backup) of the .htaccess file in the wordpress root directory. 

If a copy was successfully made then an attempt is made to edit the .htaccess in order to reflect the changes made to the site's domain. Below are values changed in the .htaccess file...

* Directive: RewriteBase

## Website

http://dan.doezema.com/2010/04/wordpress-domain-change/

## License

Wordpress Domain Changer is released under the New BSD license.

http://dan.doezema.com/licenses/new-bsd/

## Author

[Daniel Doezema](http://dan.doezema.com)

## Contributors 

[Alon Pe'er](http://alonpeer.com), [Eric Butera](http://ericbutera.us)