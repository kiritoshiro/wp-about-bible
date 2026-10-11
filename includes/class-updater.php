<?php
/**
 * Validates GitHub release packages and integrates with WordPress updates.
 * Adapted from kiritoshiro/wp-bible (GPL); hardened for mandatory SHA-256.
 * @package AboutBible
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ABible_Updater {
    const REPOSITORY = 'kiritoshiro/wp-about-bible';
    const API_VERSION = '2026-03-10';
    // Bump the suffix whenever the cached release shape changes.
    const RELEASE_CACHE_KEY = 'abible_github_latest_release_v3';
    const MAX_PACKAGE_SIZE = 52428800;

    public function __construct() {
        add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'filter_plugin_updates' ) );
        add_filter( 'plugins_api', array( $this, 'filter_plugin_information' ), 20, 3 );
        add_filter( 'upgrader_pre_download', array( $this, 'download_private_release' ), 10, 4 );
        add_action( 'load-update-core.php', array( $this, 'force_check' ), 9 );
    }

    /**
     * "Check again" on Dashboard → Updates (force-check=1) only forces the core
     * check. Drop the release cache and WordPress' plugin update data before
     * wp_update_plugins runs (priority 10), so a new release shows at once.
     */
    public function force_check() {
        if ( empty( $_GET['force-check'] ) || ! current_user_can( 'update_plugins' ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only cache refresh.
            return;
        }
        delete_site_transient( self::RELEASE_CACHE_KEY );
        delete_site_transient( 'update_plugins' );
    }

    private function status( $code, $message ) {
        set_site_transient( 'abible_update_status', array( 'code' => $code, 'message' => $message, 'checked_at' => time() ), DAY_IN_SECONDS );
    }

    /** Reject a release and cache the failure, so a bad release cannot trigger a GitHub request on every check. */
    private function reject( $code = 'unavailable', $message = 'No verified release available. Check GitHub or retry from Dashboard → Updates.' ) {
        set_site_transient( self::RELEASE_CACHE_KEY, array( 'error' => true ), 5 * MINUTE_IN_SECONDS );
        $this->status( $code, $message );
        return null;
    }

    private function token() {
        if ( ! defined( 'ABIBLE_GITHUB_TOKEN' ) || ! is_string( ABIBLE_GITHUB_TOKEN ) ) {
            return '';
        }

        return trim( ABIBLE_GITHUB_TOKEN );
    }

    /**
     * GitHub API headers. The repository is public, so the token is optional:
     * it only raises the API rate limit (or restores access if the repository
     * becomes private) and is only ever sent to api.github.com.
     */
    private function api_headers( $accept ) {
        $headers = array(
            'Accept'               => $accept,
            'X-GitHub-Api-Version' => self::API_VERSION,
        );
        $token = $this->token();
        if ( '' !== $token ) {
            $headers['Authorization'] = 'Bearer ' . $token;
        }

        return $headers;
    }

    private function latest_release() {
        $cached = get_site_transient( self::RELEASE_CACHE_KEY );
        if ( is_array( $cached ) ) {
            return empty( $cached['error'] ) ? $cached : null;
        }

        $response = wp_remote_get(
            'https://api.github.com/repos/' . self::REPOSITORY . '/releases/latest',
            array(
                'timeout'             => 15,
                'redirection'         => 0,
                'limit_response_size' => 1048576,
                'reject_unsafe_urls'  => true,
                'headers'             => $this->api_headers( 'application/vnd.github+json' ),
            )
        );

        if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
            return $this->reject();
        }

        $release = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( ! is_array( $release ) || ! is_string( $release['tag_name'] ?? null ) || ! empty( $release['draft'] ) || ! empty( $release['prerelease'] ) ) {
            return $this->reject();
        }

        // package.py writes this marker as the last line of the release notes.
        $marker = '/<!-- abible-requirements:(\{[^\r\n]+\}) -->/';
        $requirements = is_string( $release['body'] ?? null ) && preg_match( $marker, $release['body'], $matches ) ? json_decode( $matches[1], true ) : null;
        foreach ( array( 'wordpress', 'php' ) as $requirement ) {
            if ( ! is_array( $requirements ) || ! is_string( $requirements[ $requirement ] ?? null ) || ! preg_match( '/^[0-9]+\.[0-9]+(?:\.[0-9]+)?$/D', $requirements[ $requirement ] ) ) {
                return $this->reject( 'invalid', 'Release requirements are missing. No update offered.' );
            }
        }
        $version = preg_replace( '/^v/i', '', (string) $release['tag_name'] );
        if ( ! preg_match( '/^[0-9]+\.[0-9]+\.[0-9]+$/D', $version ) ) {
            return $this->reject();
        }

        $asset = null;
        if ( ! empty( $release['assets'] ) && is_array( $release['assets'] ) ) {
            foreach ( $release['assets'] as $candidate ) {
                if ( ! is_array( $candidate ) || 'wp-about-bible.zip' !== ( $candidate['name'] ?? '' ) ) {
                    continue;
                }

                $asset_id = is_int( $candidate['id'] ?? null ) ? $candidate['id'] : 0;
                $expected_api_url = 'https://api.github.com/repos/' . self::REPOSITORY . '/releases/assets/' . $asset_id;
                $browser_url = is_string( $candidate['browser_download_url'] ?? null ) ? $candidate['browser_download_url'] : '';
                $browser_parts = wp_parse_url( $browser_url );
                $size = is_int( $candidate['size'] ?? null ) ? $candidate['size'] : 0;

                if (
                    $asset_id > 0
                    && isset( $candidate['url'] )
                    && $expected_api_url === $candidate['url']
                    && is_array( $browser_parts )
                    && 'https' === ( $browser_parts['scheme'] ?? '' )
                    && 'github.com' === ( $browser_parts['host'] ?? '' )
                    && '/kiritoshiro/wp-about-bible/releases/download/' . rawurlencode( $release['tag_name'] ) . '/wp-about-bible.zip' === ( $browser_parts['path'] ?? '' )
                    && $browser_url === 'https://github.com/' . self::REPOSITORY . '/releases/download/' . rawurlencode( $release['tag_name'] ) . '/wp-about-bible.zip'
                    && is_string( $candidate['digest'] ?? null )
                    && preg_match( '/^sha256:[a-f0-9]{64}$/D', $candidate['digest'] )
                    && $size > 0
                    && $size <= self::MAX_PACKAGE_SIZE
                ) {
                    $asset = $candidate;
                    break;
                }
            }
        }

        if ( ! $asset || ( $release['html_url'] ?? '' ) !== 'https://github.com/' . self::REPOSITORY . '/releases/tag/' . rawurlencode( $release['tag_name'] ) ) {
            return $this->reject();
        }

        $this->status( 'ok', 'Verified release: ' . $version );
        // Cache only the verified fields this class reads, not the full API payload.
        // The marker is removed before the character-safe cut, so it can never be split.
        $release = array(
            'version'      => $version,
            'html_url'     => $release['html_url'],
            'notes'        => mb_substr( trim( preg_replace( $marker, '', $release['body'] ) ), 0, 20000 ),
            'published_at' => is_string( $release['published_at'] ?? null ) ? $release['published_at'] : '',
            'requires'     => $requirements['wordpress'],
            'requires_php' => $requirements['php'],
            'asset'        => array(
                'id'                   => $asset['id'],
                'size'                 => $asset['size'],
                'digest'               => $asset['digest'],
                'browser_download_url' => $asset['browser_download_url'],
            ),
        );
        set_site_transient( self::RELEASE_CACHE_KEY, $release, 10 * MINUTE_IN_SECONDS );

        return $release;
    }

    public function filter_plugin_updates( $transient ) {
        if ( ! is_object( $transient ) ) {
            return $transient;
        }

        $plugin_file = plugin_basename( ABIBLE_FILE );
        if ( ! isset( $transient->response ) || ! is_array( $transient->response ) ) {
            $transient->response = array();
        }
        if ( ! isset( $transient->no_update ) || ! is_array( $transient->no_update ) ) {
            $transient->no_update = array();
        }

        // Keep an unrelated WordPress.org plugin with the generic "bible" slug from replacing this plugin.
        unset( $transient->response[ $plugin_file ], $transient->no_update[ $plugin_file ] );

        $release = $this->latest_release();
        if ( ! $release ) {
            return $transient;
        }

        $update = (object) array(
            'id'           => 'https://github.com/' . self::REPOSITORY,
            'slug'         => dirname( $plugin_file ),
            'plugin'       => $plugin_file,
            'new_version'  => $release['version'],
            'url'          => $release['html_url'],
            'package'      => $release['asset']['browser_download_url'],
            'requires'     => $release['requires'],
            'requires_php' => $release['requires_php'],
        );

        if ( version_compare( $release['version'], ABIBLE_VERSION, '>' ) ) {
            $transient->response[ $plugin_file ] = $update;
        } else {
            $transient->no_update[ $plugin_file ] = $update;
        }

        return $transient;
    }

    public function filter_plugin_information( $result, $action, $args ) {
        if (
            'plugin_information' !== $action
            || ! is_object( $args )
            || empty( $args->slug )
            || dirname( plugin_basename( ABIBLE_FILE ) ) !== $args->slug
        ) {
            return $result;
        }

        $release = $this->latest_release();
        if ( ! $release ) {
            return $result;
        }

        $description = 'A Lithuanian Bible research and study library with source attribution and structured references.';
        $changelog = '' !== $release['notes'] ? wpautop( esc_html( $release['notes'] ) ) : '<p>No release notes were provided.</p>';

        return (object) array(
            'name'          => 'Biblijos pažinimas',
            'slug'          => dirname( plugin_basename( ABIBLE_FILE ) ),
            'version'       => $release['version'],
            'author'        => 'Adventistai',
            'homepage'      => 'https://github.com/' . self::REPOSITORY,
            'requires'      => $release['requires'],
            'requires_php'  => $release['requires_php'],
            'last_updated'  => $release['published_at'],
            'download_link' => $release['asset']['browser_download_url'],
            'sections'      => array(
                'description' => '<p>' . esc_html( $description ) . '</p>',
                'changelog'   => $changelog,
            ),
        );
    }

    public function download_private_release( $reply, $package, $upgrader, $hook_extra ) {
        if ( false !== $reply || ! is_string( $package ) || 0 !== strpos( $package, 'https://github.com/' . self::REPOSITORY . '/releases/download/' ) ) {
            return $reply;
        }

        $release = $this->latest_release();
        if ( ! $release || $package !== $release['asset']['browser_download_url'] ) {
            return new WP_Error(
                'abible_github_release_unavailable',
                __( 'The Bible plugin release could not be verified with GitHub. Please check the update again later.', 'wp-about-bible' )
            );
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        $temporary_file = wp_tempnam( 'abible-update.zip' );
        if ( ! $temporary_file ) {
            return new WP_Error( 'abible_github_tempfile_failed', 'Could not create an update temporary file.' );
        }
        $keep_file = false;
        try {
            $asset = $release['asset'];
            $options = array(
                'timeout' => 60, 'redirection' => 0, 'reject_unsafe_urls' => true,
                'stream' => true, 'filename' => $temporary_file,
                'limit_response_size' => min( self::MAX_PACKAGE_SIZE, intval( $asset['size'] ) ) + 1,
                'headers' => $this->api_headers( 'application/octet-stream' ),
            );
            $response = wp_remote_get(
                'https://api.github.com/repos/' . self::REPOSITORY . '/releases/assets/' . absint( $asset['id'] ),
                $options
            );
            if ( ! is_wp_error( $response ) && 302 === wp_remote_retrieve_response_code( $response ) ) {
                $url = wp_remote_retrieve_header( $response, 'location' );
                if ( ! is_string( $url ) ) { return new WP_Error( 'abible_redirect', 'Invalid redirect.' ); }
                $parts = wp_parse_url( $url );
                if ( ! is_array( $parts ) || ( $parts['scheme'] ?? '' ) !== 'https' ||
                    ! in_array( strtolower( $parts['host'] ?? '' ), array( 'release-assets.githubusercontent.com', 'objects.githubusercontent.com' ), true ) ||
                    isset( $parts['user'] ) || isset( $parts['pass'] ) || isset( $parts['port'] ) ||
                    ! wp_http_validate_url( $url ) ) {
                    return new WP_Error( 'abible_github_redirect_invalid', 'GitHub returned an unexpected download host.' );
                }
                // No token and no further redirects on the signed CDN request.
                unset( $options['headers'] );
                $response = wp_remote_get( $url, $options );
            }
            if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
                return new WP_Error( 'abible_github_download_failed', 'Could not download the GitHub release asset.' );
            }
            clearstatcache( true, $temporary_file );
            // The digest is mandatory: an absent or malformed digest fails closed.
            $digest = $asset['digest'] ?? '';
            if ( filesize( $temporary_file ) !== intval( $asset['size'] ) ||
                file_get_contents( $temporary_file, false, null, 0, 4 ) !== "PK\x03\x04" ||
                ! is_string( $digest ) || ! preg_match( '/^sha256:[a-f0-9]{64}$/D', $digest ) ||
                ! hash_equals( substr( $digest, 7 ), hash_file( 'sha256', $temporary_file ) ) ) {
                return new WP_Error( 'abible_github_package_invalid', 'The release package size, ZIP header, or SHA-256 checksum is invalid.' );
            }
            // WordPress core validates and unpacks the archive after this hook.
            $keep_file = true;
            return $temporary_file;
        } finally {
            if ( ! $keep_file ) wp_delete_file( $temporary_file );
        }
    }
}
