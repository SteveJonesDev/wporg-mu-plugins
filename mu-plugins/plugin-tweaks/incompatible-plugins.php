<?php

namespace WordPressdotorg\MU_Plugins\Plugin_Tweaks\IncompatiblePlugins;

defined( 'WPINC' ) || die();

// Don't run this on the plugins.php page, as we don't want to operate on filtered options.
if ( defined( 'WP_ADMIN' ) && WP_ADMIN && str_contains( $_SERVER['REQUEST_URI'] ?? '', '/plugins.php' ) ) {
	return;
}

/**
 * Plugin config.
 *
 * Each item in the array contains a plugin to check (`check`), and a plugin
 * that should be deactivated (`from`) in favor of another plugin (`to`).
 */
const PLUGINS = [
	[
		// Blocks Everywhere: Uses private/unstable APIs,
		// which are blocked after GB 16.8.
		'check' => 'blocks-everywhere/blocks-everywhere.php',
		'from'  => 'gutenberg/gutenberg.php',
		'to'    => 'gutenberg-16.8/gutenberg.php',
	],
	[
		// Pattern Creator: Uses private/unstable APIs,
		// which are blocked after GB 16.8.
		'check' => 'pattern-creator/pattern-creator.php',
		'from'  => 'gutenberg/gutenberg.php',
		'to'    => 'gutenberg-16.8/gutenberg.php',
	],
];

/**
 * Check the above list of plugins, and filter the appropriate option.
 *
 * This needs to be done on plugin inclusion, as network-wide plugins are included immediately after mu-plugins.
 */
function filter_the_filters() {
	$active_plugins          = (array) get_option( 'active_plugins', [] );
	$active_sitewide_plugins = is_multisite() ? get_site_option( 'active_sitewide_plugins', [] ) : [];

	foreach ( PLUGINS as $incompatible_plugin ) {
		$check = $incompatible_plugin['check'];
		$from  = $incompatible_plugin['from'];
		$to    = $incompatible_plugin['to'];

		// Check to see if the incompatible plugin is active first.
		// Not using the functions that do this, as they're only loaded in wp-admin.
		if (
			! in_array( $check, $active_plugins, true ) &&
			! isset( $active_sitewide_plugins[ $check ] )
		) {
			continue;
		}

		if ( in_array( $from, $active_plugins, true ) ) {
			add_filter(
				'option_active_plugins',
				function( $plugins ) use ( $from, $to ) {
					// Splice to retain load order, if it's important.
					array_splice(
						$plugins,
						array_search( $from, $plugins, true ),
						1,
						$to
					);
					return $plugins;
				}
			);
		}

		if ( isset( $active_sitewide_plugins[ $from ] ) ) {
			add_filter(
				'site_option_active_sitewide_plugins',
				function( $plugins ) use ( $from, $to ) {
					$plugins[ $to ] = $plugins[ $from ];
					unset( $plugins[ $from ] );

					return $plugins;
				}
			);
		}
	}
}

filter_the_filters();