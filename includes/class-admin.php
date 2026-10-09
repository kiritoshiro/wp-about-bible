<?php
/** Editorial controls. @package AboutBible */
defined( 'ABSPATH' ) || exit;

final class ABible_Admin {
    public function __construct() {
        add_action( 'add_meta_boxes', array( $this, 'boxes' ) );
        add_action( 'save_post_' . ABible_Content::TYPE, array( $this, 'save' ) );
        add_action( 'admin_notices', array( $this, 'notice' ) );
        add_action( 'admin_menu', array( $this, 'menu' ) );
        add_action( 'admin_post_abible_setup', array( $this, 'setup' ) );
    }

    public function boxes() {
        add_meta_box( 'abible-editorial', 'Šaltiniai ir redakcinė informacija', array( $this, 'editorial' ), ABible_Content::TYPE, 'normal' );
        add_meta_box( 'abible-passages', 'Biblijos ištraukos', array( $this, 'references' ), ABible_Content::TYPE, 'normal' );
    }

    public function editorial( $post ) {
        wp_nonce_field( 'abible_save', 'abible_nonce' );
        echo '<p>Įrašo paskyra nėra originalo autorius. Autorius pridėkite skiltyje „Autoriai“. Vidinės pastabos ir leidimai nebus rodomi skaitytojams.</p><table class="form-table"><tbody>';
        foreach ( ABible_Content::fields() as $key => $label ) {
            $value = get_post_meta( $post->ID, '_abible_' . $key, true );
            echo '<tr><th><label for="abible-' . esc_attr( $key ) . '">' . esc_html( $label ) . '</label></th><td>';
            if ( in_array( $key, array( 'document_status', 'presentation' ), true ) ) {
                echo '<select id="abible-' . esc_attr( $key ) . '" name="abible[' . esc_attr( $key ) . ']">';
                foreach ( ABible_Content::choices( $key ) as $option => $text ) {
                    echo '<option value="' . esc_attr( $option ) . '" ' . selected( $value, $option, false ) . '>' . esc_html( $text ) . '</option>';
                }
                echo '</select>';
            } else {
                echo '<textarea class="large-text" rows="2" maxlength="8000" id="abible-' . esc_attr( $key ) . '" name="abible[' . esc_attr( $key ) . ']">' . esc_textarea( $value ) . '</textarea>';
            }
            echo '</td></tr>';
        }
        echo '</tbody></table>';
    }

    public function references( $post ) {
        $rows = get_post_meta( $post->ID, '_abible_passages', true );
        $rows = is_array( $rows ) ? $rows : array();
        $count = min( 20, count( $rows ) + 2 );
        echo '<p>Palikite skyrius tuščius visai knygai arba eilutes tuščias visam skyriui. Išsaugojus atsiras papildomos tuščios eilutės (iki 20). Knygos filtras papildomas pagal šiuos įrašus; numeraciją tikrinkite pagal naudojamą vertimą.</p>';
        for ( $i = 0; $i < $count; $i++ ) {
            $row = $rows[ $i ] ?? array();
            echo '<fieldset style="border:1px solid #ccc;padding:12px;margin-bottom:12px"><legend>' . esc_html( 'Ištrauka ' . ( $i + 1 ) ) . '</legend><label>Knyga <select name="abible_passages[' . esc_attr( $i ) . '][book]"><option value="">—</option>';
            foreach ( ABible_Content::books() as $slug => $name ) { echo '<option value="' . esc_attr( $slug ) . '" ' . selected( $row['book'] ?? '', $slug, false ) . '>' . esc_html( $name ) . '</option>'; }
            echo '</select></label> ';
            foreach ( array( 'chapter' => 'Nuo skyriaus', 'verse' => 'Nuo eilutės', 'end_chapter' => 'Iki skyriaus', 'end_verse' => 'Iki eilutės' ) as $key => $label ) {
                echo '<label style="display:inline-block;margin:6px">' . esc_html( $label ) . ' <input style="width:5em" type="number" min="1" max="176" name="abible_passages[' . esc_attr( $i ) . '][' . esc_attr( $key ) . ']" value="' . esc_attr( $row[ $key ] ?? '' ) . '"></label>';
            }
            echo '<label>Ryšys <select name="abible_passages[' . esc_attr( $i ) . '][relation]"><option value="discusses">Nagrinėjama</option><option value="cites" ' . selected( $row['relation'] ?? '', 'cites', false ) . '>Cituojama</option></select></label></fieldset>';
        }
    }

    public function save( $post_id ) {
        if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) { return; }
        if ( ! isset( $_POST['abible_nonce'] ) || ! is_string( $_POST['abible_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['abible_nonce'] ) ), 'abible_save' ) ) { return; }
        // Validate the entire form before persisting any custom fields. Invalid input leaves old metadata intact.
        $data = ABible_Content::validate( isset( $_POST['abible'] ) ? wp_unslash( $_POST['abible'] ) : array() ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Strict per-field validation follows.
        $passages = ABible_Content::passages( isset( $_POST['abible_passages'] ) ? wp_unslash( $_POST['abible_passages'] ) : array() ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Strict structural validation follows.
        $error = is_wp_error( $data ) ? $data : ( is_wp_error( $passages ) ? $passages : null );
        if ( $error ) {
            set_transient( 'abible_notice_' . get_current_user_id(), $error->get_error_message(), 120 );
            return;
        }
        foreach ( $data as $key => $value ) { update_post_meta( $post_id, '_abible_' . $key, $value ); }
        update_post_meta( $post_id, '_abible_passages', $passages );
        // Append reference-derived books without removing independently curated book classifications.
        foreach ( array_unique( array_column( $passages, 'book' ) ) as $book ) {
            $term = get_term_by( 'slug', $book, 'abible_book' );
            if ( $term ) { wp_set_object_terms( $post_id, array( (int) $term->term_id ), 'abible_book', true ); }
        }
    }

    public function notice() {
        $key = 'abible_notice_' . get_current_user_id();
        $message = get_transient( $key );
        if ( $message && current_user_can( 'edit_posts' ) ) {
            echo '<div class="notice notice-error"><p>' . esc_html( 'Metaduomenys nepakeisti. ' . $message . ' Patikrinkite laukus ir išsaugokite dar kartą.' ) . '</p></div>';
            delete_transient( $key );
        }
    }

    public function menu() {
        add_submenu_page( 'edit.php?post_type=' . ABible_Content::TYPE, 'Bibliotekos nustatymai', 'Nustatymai', 'manage_options', 'abible-settings', array( $this, 'settings' ) );
    }

    public function settings() {
        if ( ! current_user_can( 'manage_options' ) ) { return; }
        echo '<div class="wrap"><h1>Biblijos pažinimas</h1><p>Į puslapį įdėkite <code>[abible_home]</code> pradžiai arba <code>[abible_library]</code> bibliotekai. Mokymosi kelius ir žodynėlį kurkite įprastais puslapiais.</p><p>Šis mygtukas sukurs tik juodraštį „Biblijos pažinimas“. Esami puslapiai ir meniu nebus keičiami.</p><form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="abible_setup">';
        wp_nonce_field( 'abible_setup' );
        submit_button( 'Sukurti pradžios puslapio juodraštį' );
        echo '</form><h2>Atnaujinimai</h2><p>Atnaujinimai gaunami iš GitHub leidimų. Eikite į „Skydelis → Atnaujinimai → Patikrinti dar kartą“.</p>';
        $status = get_site_transient( 'abible_update_status' );
        if ( is_array( $status ) ) { echo '<p>' . esc_html( $status['message'] ?? '' ) . '</p>'; }
        echo '<p><a href="' . esc_url( 'https://github.com/kiritoshiro/wp-about-bible' ) . '">Projekto dokumentacija</a></p></div>';
    }

    public function setup() {
        if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Nepakanka teisių.', '', array( 'response' => 403 ) ); }
        check_admin_referer( 'abible_setup' );
        $existing = get_page_by_path( 'biblija' );
        if ( $existing ) { wp_safe_redirect( get_edit_post_link( $existing->ID, 'raw' ) ); exit; }
        $id = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'draft', 'post_title' => 'Biblijos pažinimas', 'post_name' => 'biblija', 'post_content' => '[abible_home]' ), true );
        if ( is_wp_error( $id ) ) { wp_die( 'Nepavyko sukurti puslapio. Bandykite dar kartą.', '', array( 'response' => 500 ) ); }
        wp_safe_redirect( get_edit_post_link( $id, 'raw' ) );
        exit;
    }
}
