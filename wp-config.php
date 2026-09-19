<?php
define( 'WP_CACHE', true );

/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the web site, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * Localized language
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'u184534870_Adqvw' );

/** Database username */
define( 'DB_USER', 'u184534870_HJ521' );

/** Database password */
define( 'DB_PASSWORD', 'w5AHjwXJgG' );

/** Database hostname */
define( 'DB_HOST', '127.0.0.1' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',          'h8(L6T.a>gYg.z#ERh3{B:pA7HM8 [V6vYSqzwx[O|]Z(oL(h39KOp;Hc2Zxtv9I' );
define( 'SECURE_AUTH_KEY',   'U2`v_(7Q|=+FiR-:{z/,JGnxC7gYv+GZi}o@c~a:-s]gwVli-~JCl6IBa|szP#+t' );
define( 'LOGGED_IN_KEY',     '/y<WP]9FKdY{||~|3uJGfpiRK{jcac8)JoW<Qv<U/~0#aCIs+^st12>`KkZmb4]6' );
define( 'NONCE_KEY',         'h]p]>a=@,%kN<Pz 3Q=0V1/Q=|c=`3k,qE9T-#IdZ/{b^71MOzk!m a@5_yzwby!' );
define( 'AUTH_SALT',         '`gF.J 5LDH4_1M&&KQq)N-0/ #ACc N]O5=>RGkq_@v||7Yi&{Dut1=-Qk3WyY|G' );
define( 'SECURE_AUTH_SALT',  '!.E=pUUij!ShR+PW$/<D10-r%x8+2R8Cj@Fv$>;#ovve,h]QF2E 5`{{BE|.@YkK' );
define( 'LOGGED_IN_SALT',    '<xyB*A,dehc2V9pHrKl.pNca@`>=*$SzdfX9;e<c4^W5qA^&0aCv{mNnM@0flk3S' );
define( 'NONCE_SALT',        'n],tm7+YI|mOE~HW/ez&g6Bq%?SR,Z{2*Npq$|X5ir+py9V={M]*q EbIT?_G6B%' );
define( 'WP_CACHE_KEY_SALT', 'I_b[h,3%q076#/jD^A7jjJ7S#=;+S?g]V-80cLm^oB9GmPRTPta@*O8FC%*:7cp}' );


/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';


/* Add any custom values between this line and the "stop editing" line. */



/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
 */
if ( ! defined( 'WP_DEBUG' ) ) {
	define( 'WP_DEBUG', false );
}

define( 'FS_METHOD', 'direct' );
define( 'COOKIEHASH', 'ce3f862afb1281d186ba79a03a3f8def' );
define( 'WP_AUTO_UPDATE_CORE', 'minor' );
/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
