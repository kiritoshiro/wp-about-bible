<?php
/** Preserve publications, classifications and metadata on uninstall. @package AboutBible */
defined( 'WP_UNINSTALL_PLUGIN' ) || exit;
delete_site_transient( 'abible_github_latest_release_v2' );
delete_site_transient( 'abible_update_status' );
