<?php
/** Runs inside disposable WordPress; never shipped in the plugin ZIP. */
if ( ! defined( 'ABSPATH' ) ) { throw new RuntimeException( 'WordPress required.' ); }
$assertions = 0;
function abible_assert( $condition, $message ) {
    global $assertions;
    ++$assertions;
    if ( ! $condition ) { throw new RuntimeException( 'ABible test: ' . $message ); }
}
wp_set_current_user( 1 );
global $wp_version;
abible_assert( str_starts_with( PHP_VERSION, ABIBLE_TEST_PHP . '.' ), 'actual PHP matches requested runtime' );
if ( 'latest' !== ABIBLE_TEST_WP ) { abible_assert( $wp_version === ABIBLE_TEST_WP || str_starts_with( $wp_version, ABIBLE_TEST_WP . '.' ), 'actual WordPress matches declared minimum' ); }
abible_assert( post_type_exists( ABible_Content::TYPE ), 'publication type registered' );
abible_assert( count( ABible_Content::books() ) === 66, '66 canonical book IDs' );
ABible_Content::activate();
ABible_Content::activate();
abible_assert( count( get_terms( array( 'taxonomy' => 'abible_book', 'hide_empty' => false ) ) ) === 66, 'activation is idempotent' );
abible_assert( is_wp_error( ABible_Content::validate( array( 'source_url' => 'javascript:alert(1)' ) ) ), 'unsafe URL rejected' );
abible_assert( is_wp_error( ABible_Content::validate( array( 'source_date' => '2026-02-30' ) ) ), 'invalid date rejected' );
abible_assert( is_wp_error( ABible_Content::validate( array( 'document_status' => 'adopted' ) ) ), 'adopted status requires evidence' );
abible_assert( is_wp_error( ABible_Content::validate( array( 'source_title' => array( 'bad' ) ) ) ), 'array field rejected' );
abible_assert( is_wp_error( ABible_Content::validate( array( 'presentation' => 'translation' ) ) ), 'translation source required' );
abible_assert( ! is_wp_error( ABible_Content::validate( array( 'source_date' => '2024-02-29' ) ) ), 'valid leap date accepted' );
$rows = ABible_Content::passages( array( array( 'book' => 'gen', 'chapter' => '1', 'verse' => '1', 'end_verse' => '5', 'relation' => 'discusses' ) ) );
abible_assert( ! is_wp_error( $rows ) && $rows[0]['end_chapter'] === 1, 'same-chapter endpoint normalized' );
abible_assert( is_wp_error( ABible_Content::passages( array( array( 'book' => 'fake' ) ) ) ), 'unknown book rejected' );
abible_assert( is_wp_error( ABible_Content::passages( array( array( 'book' => 'gen', 'chapter' => '2', 'end_chapter' => '1' ) ) ) ), 'reverse range rejected' );
abible_assert( is_wp_error( ABible_Content::passages( array( array( 'book' => 'gen', 'verse' => '1' ) ) ) ), 'verse without chapter rejected' );
abible_assert( is_wp_error( ABible_Content::passages( array_fill( 0, 21, array() ) ) ), 'bounded references' );
$id = wp_insert_post( array( 'post_type' => ABible_Content::TYPE, 'post_status' => 'publish', 'post_title' => 'Kontekstas ir Biblijos tyrinėjimas', 'post_excerpt' => 'Testinė publikacija apie atsakingą Biblijos skaitymą.', 'post_content' => '<h2>Perskaitykite</h2><p>Testinė medžiaga. Pr 1,1–5.</p><h2>Apmąstykite</h2><p>Patikrinkite kontekstą ir šaltinius.</p>' ) );
$admin = new ABible_Admin();
update_post_meta( $id, '_abible_source_title', 'Original source' );
$_POST = array( 'abible' => array( 'source_title' => 'forged' ) );
$admin->save( $id );
abible_assert( get_post_meta( $id, '_abible_source_title', true ) === 'Original source', 'missing nonce cannot edit metadata' );
$subscriber = wp_insert_user( array( 'user_login' => 'library_reader', 'user_pass' => wp_generate_password(), 'role' => 'subscriber' ) );
wp_set_current_user( $subscriber );
$_POST['abible_nonce'] = wp_create_nonce( 'abible_save' );
$admin->save( $id );
abible_assert( get_post_meta( $id, '_abible_source_title', true ) === 'Original source', 'valid nonce does not bypass capabilities' );
wp_set_current_user( 1 );
$_POST = array( 'abible_nonce' => wp_create_nonce( 'abible_save' ), 'abible' => array( 'source_title' => 'Verified source', 'editorial_notes' => 'PRIVATE-EDITORIAL-FIXTURE', 'permissions' => 'PRIVATE-PERMISSION-FIXTURE', 'presentation' => 'original', 'document_status' => 'editorial' ), 'abible_passages' => array( array( 'book' => 'gen', 'chapter' => '1', 'verse' => '1', 'end_verse' => '5' ) ) );
$admin->save( $id );
abible_assert( get_post_meta( $id, '_abible_source_title', true ) === 'Verified source', 'authorized metadata saved' );
abible_assert( has_term( 'gen', 'abible_book', $id ), 'reference book is discoverable' );
$_POST['abible']['source_title'] = 'Should not save';
$_POST['abible_passages'][0]['end_chapter'] = 'bad';
$admin->save( $id );
abible_assert( get_post_meta( $id, '_abible_source_title', true ) === 'Verified source', 'invalid submission preserves all existing metadata' );
$_POST = array();
delete_transient( 'abible_notice_1' );
wp_set_object_terms( $id, 'metodika', 'abible_topic' );
$draft = wp_insert_post( array( 'post_type' => ABible_Content::TYPE, 'post_status' => 'draft', 'post_title' => 'PRIVATE-DRAFT-FIXTURE' ) );
wp_set_current_user( 0 );
$request = new WP_REST_Request( 'GET', '/wp/v2/abible_publication/' . $id );
$response = rest_do_request( $request );
$json = wp_json_encode( $response->get_data() );
abible_assert( $response->get_status() === 200, 'public REST publication readable' );
abible_assert( ! str_contains( $json, 'PRIVATE-EDITORIAL' ) && ! str_contains( $json, 'PRIVATE-PERMISSION' ), 'private metadata absent from REST' );
$response = rest_do_request( new WP_REST_Request( 'GET', '/wp/v2/abible_publication/' . $draft ) );
abible_assert( $response->get_status() !== 200, 'draft is not public' );
$frontend = new ABible_Frontend();
$home = $frontend->home();
abible_assert( substr_count( $home, 'class="ab-topic-card"' ) === 8, 'all eight topics visible with sparse content' );
abible_assert( ! str_contains( $home, '<form' ) && ! str_contains( $home, '<select' ), 'home leads with topics instead of filters' );
abible_assert( ! taxonomy_exists( 'abible_level' ) && ! taxonomy_exists( 'abible_kind' ), 'unwanted classifications removed' );
$term = get_term_by( 'slug', 'metodika', 'abible_topic' );
wp_set_current_user( 1 );
$image_id = wp_insert_attachment( array( 'post_title' => 'Topic test', 'post_mime_type' => 'image/jpeg', 'post_status' => 'inherit' ) );
update_post_meta( $image_id, '_wp_attached_file', 'topic-test.jpg' );
$_POST = array( 'abible_image_id' => (string) $image_id );
$admin->save_topic_image( $term->term_id );
abible_assert( ! get_term_meta( $term->term_id, '_abible_image_id', true ), 'image update requires nonce' );
$_POST['abible_topic_nonce'] = wp_create_nonce( 'abible_topic_image' );
$admin->save_topic_image( $term->term_id );
abible_assert( (int) get_term_meta( $term->term_id, '_abible_image_id', true ) === $image_id, 'authorized image assignment saved' );
wp_set_current_user( $subscriber );
$_POST = array( 'abible_image_id' => '0', 'abible_topic_nonce' => wp_create_nonce( 'abible_topic_image' ) );
$admin->save_topic_image( $term->term_id );
abible_assert( (int) get_term_meta( $term->term_id, '_abible_image_id', true ) === $image_id, 'subscriber cannot remove image with valid nonce' );
wp_set_current_user( 1 );
$_POST = array( 'abible_image_id' => (string) $id, 'abible_topic_nonce' => wp_create_nonce( 'abible_topic_image' ) );
$admin->save_topic_image( $term->term_id );
abible_assert( (int) get_term_meta( $term->term_id, '_abible_image_id', true ) === $image_id, 'nonimage rejected without replacing image' );
$_POST['abible_image_id'] = '0';
$admin->save_topic_image( $term->term_id );
abible_assert( ! get_term_meta( $term->term_id, '_abible_image_id', true ), 'default illustration restored' );
$_POST = array();
wp_set_current_user( 0 );

$library = $frontend->library();
abible_assert( str_contains( $library, 'Kontekstas' ) && ! str_contains( $library, 'PRIVATE-DRAFT' ), 'library contains only published content' );
$_GET = array( 'filter_abible_book' => 'gen', 'filter_abible_topic' => 'metodika' );
abible_assert( str_contains( $frontend->library(), 'Kontekstas' ), 'combined filters match publication' );
$_GET['filter_abible_book'] = 'rev';
abible_assert( ! str_contains( $frontend->library(), 'Kontekstas' ), 'book filter excludes unrelated publication' );
$_GET = array( 'ab_q' => '<script>alert(1)</script>', 'filter_abible_book' => array( 'gen' ) );
abible_assert( ! str_contains( $frontend->library(), '<script>' ), 'malformed filters are harmless' );
$_GET = array();
$protected = wp_insert_post( array( 'post_type' => ABible_Content::TYPE, 'post_status' => 'publish', 'post_password' => 'fixture', 'post_title' => 'Protected fixture', 'post_excerpt' => 'PROTECTED-EXCERPT-FIXTURE', 'post_content' => 'PROTECTED-BODY-FIXTURE' ) );
$library = $frontend->library();
abible_assert( str_contains( $library, 'Protected fixture' ) && ! str_contains( $library, 'PROTECTED-EXCERPT' ) && ! str_contains( $library, 'PROTECTED-BODY' ), 'listing hides password-protected text' );
wp_delete_post( $protected, true );

// Mock only HTTP transport; execute actual WordPress metadata, transients and filesystem APIs.
define( 'ABIBLE_GITHUB_TOKEN', 'fixture-only-not-a-real-credential' );
$updater = new ABible_Updater();
$payload = "PK\x03\x04" . str_repeat( 'a', 40 );
$asset_url = 'https://github.com/kiritoshiro/wp-about-bible/releases/download/v0.2.0/wp-about-bible.zip';
$release = array( 'tag_name' => 'v0.2.0', 'html_url' => 'https://github.com/kiritoshiro/wp-about-bible/releases/tag/v0.2.0', 'draft' => false, 'prerelease' => false, 'body' => '<!-- abible-requirements:{"wordpress":"6.6","php":"8.3"} -->', 'assets' => array( array( 'name' => 'wp-about-bible.zip', 'id' => 42, 'size' => strlen( $payload ), 'digest' => 'sha256:' . hash( 'sha256', $payload ), 'url' => 'https://api.github.com/repos/kiritoshiro/wp-about-bible/releases/assets/42', 'browser_download_url' => $asset_url ) ) );
$mode = 'good';
$requests = array();
$mock = static function ( $pre, $args, $url ) use ( &$release, &$mode, &$requests, $payload ) {
    $requests[] = array( $url, $args );
    if ( str_ends_with( $url, '/releases/latest' ) ) {
        if ( 'offline' === $mode ) { return new WP_Error( 'offline', 'fixture' ); }
        return array( 'response' => array( 'code' => 200 ), 'headers' => array(), 'body' => wp_json_encode( $release ) );
    }
    if ( str_contains( $url, '/releases/assets/' ) ) {
        return array( 'response' => array( 'code' => 302 ), 'headers' => array( 'location' => 'evil' === $mode ? 'https://evil.example/package' : 'https://release-assets.githubusercontent.com/fixture.zip' ), 'body' => '' );
    }
    if ( str_contains( $url, 'release-assets.githubusercontent.com' ) ) {
        file_put_contents( $args['filename'], 'corrupt' === $mode ? str_repeat( 'x', strlen( $payload ) ) : $payload );
        return array( 'response' => array( 'code' => 200 ), 'headers' => array(), 'body' => '' );
    }
    return $pre;
};
add_filter( 'pre_http_request', $mock, 10, 3 );
delete_site_transient( ABible_Updater::RELEASE_CACHE_KEY );
$check = $updater->filter_plugin_updates( new stdClass() );
abible_assert( isset( $check->response[ plugin_basename( ABIBLE_FILE ) ] ), 'verified release offered' );
$cached = get_site_transient( ABible_Updater::RELEASE_CACHE_KEY );
abible_assert( ! isset( $cached['assets'] ) && 42 === $cached['asset']['id'], 'cache holds only verified fields' );
$info = $updater->filter_plugin_information( false, 'plugin_information', (object) array( 'slug' => dirname( plugin_basename( ABIBLE_FILE ) ) ) );
abible_assert( ! str_contains( $info->sections['changelog'], 'abible-requirements' ), 'requirements marker hidden from details' );
$file = $updater->download_private_release( false, $asset_url, null, array() );
abible_assert( is_string( $file ) && file_exists( $file ), 'verified download returned' );
wp_delete_file( $file );
$cdn = end( $requests );
abible_assert( empty( $cdn[1]['headers']['Authorization'] ), 'token never sent to CDN' );
abible_assert( $cdn[1]['redirection'] === 0, 'CDN cannot redirect credentials or package' );
$mode = 'evil';
abible_assert( is_wp_error( $updater->download_private_release( false, $asset_url, null, array() ) ), 'untrusted redirect rejected' );
$mode = 'corrupt';
abible_assert( is_wp_error( $updater->download_private_release( false, $asset_url, null, array() ) ), 'corrupted package rejected' );
$mode = 'good';
$release['assets'][0]['digest'] = '';
delete_site_transient( ABible_Updater::RELEASE_CACHE_KEY );
abible_assert( empty( $updater->filter_plugin_updates( new stdClass() )->response ), 'missing digest fails closed' );
$release['assets'][0]['digest'] = 'sha256:' . hash( 'sha256', $payload );
$release['body'] = 'No requirements marker';
delete_site_transient( ABible_Updater::RELEASE_CACHE_KEY );
abible_assert( empty( $updater->filter_plugin_updates( new stdClass() )->response ), 'missing requirements fail closed' );
$before = count( $requests );
$updater->filter_plugin_updates( new stdClass() );
abible_assert( count( $requests ) === $before, 'invalid release is cached, not refetched' );
$release['tag_name'] = array( 'bad' );
delete_site_transient( ABible_Updater::RELEASE_CACHE_KEY );
abible_assert( empty( $updater->filter_plugin_updates( new stdClass() )->response ), 'malformed tag fails safely' );
$mode = 'offline';
delete_site_transient( ABible_Updater::RELEASE_CACHE_KEY );
abible_assert( empty( $updater->filter_plugin_updates( new stdClass() )->response ), 'network failure leaves installed plugin intact' );
$_GET['force-check'] = '1';
$updater->force_check();
abible_assert( false !== get_site_transient( ABible_Updater::RELEASE_CACHE_KEY ), 'anonymous cannot force update requests' );
wp_set_current_user( 1 );
$updater->force_check();
abible_assert( false === get_site_transient( ABible_Updater::RELEASE_CACHE_KEY ), 'admin Check again clears cache' );
remove_filter( 'pre_http_request', $mock, 10 );
$_GET = array();
wp_set_current_user( 0 );
echo "ABible integration: {$assertions} assertions passed.\n";
file_put_contents( '/logs/fixture.json', wp_json_encode( array( 'publication' => $id, 'draft' => $draft, 'assertions' => $assertions, 'wordpress' => $wp_version, 'php' => PHP_VERSION ) ) );
