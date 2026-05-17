<?php
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
define( 'DB_NAME', 'local' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', 'root' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

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
define( 'AUTH_KEY',          'm62(yQDNm(aHRO=/P9xgheso6xq{#;eXd,&;zRKce)BdE(sC5r,>.8aX)U u4Gz7' );
define( 'SECURE_AUTH_KEY',   't_4ziH]+hR/{;o%^1I1sw|:HTndwHg7K_%#Eczkoo+#!Kya,@o30}]8nh52`_AVS' );
define( 'LOGGED_IN_KEY',     '?sE+rerS~.A+{X)))GB!{FR<pIdwuF4wmRCU&}HBgAf+jj+ag+2=1Z0NsG&YYo1Q' );
define( 'NONCE_KEY',         '7KO3h0oV=,>ZkFDind9wt0UFaFW]skZd)bFVgI%m{Y:6XRyOjU6`X)0y?K?yHN*V' );
define( 'AUTH_SALT',         '4N-( -;6R~-;QPV7&|s7{e [:CD]anOxC?&__v4|<~o|C%iI9Hh6nin=K:gC@yK?' );
define( 'SECURE_AUTH_SALT',  'K`(,<V.$T}0]/(ikMBw5;&l(H;xB(Ib%mdsS0dXX>BMF^Ct@^nCr)N2D;71wOUhI' );
define( 'LOGGED_IN_SALT',    'q$_j1Q`4C-B9G?HpUL{G?9d3_!32orqLvPOH$Fdi]us6$:}hhs6/#;%;X{jXMlH{' );
define( 'NONCE_SALT',        'bMqpmIj|5ALAKO|db+Bmc?gbvnk_n{tpgQo2=f9tH|BQ9s&`5qCQZ]SU$1O[|N5>' );
define( 'WP_CACHE_KEY_SALT', 'h5NCTgvY{)Y#RAL9%5Jwpw^|Q28::0v5fZoKplmJz%>91w_]8tL`<IXwrXz:@6:k' );


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

define( 'WP_ENVIRONMENT_TYPE', 'local' );
/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
