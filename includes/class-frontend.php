<?php
/** Theme-compatible, server-rendered library. @package AboutBible */
defined( 'ABSPATH' ) || exit;

final class ABible_Frontend {
    public function __construct() {
        add_shortcode( 'abible_home', array( $this, 'home' ) );
        add_shortcode( 'abible_library', array( $this, 'library' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'assets' ) );
        add_filter( 'the_content', array( $this, 'publication' ), 20 );
        add_action( 'pre_get_posts', array( $this, 'archives' ) );
    }

    public function assets() {
        $post = get_post();
        if ( is_singular( ABible_Content::TYPE ) || is_post_type_archive( ABible_Content::TYPE ) || is_tax( array_keys( ABible_Content::taxonomies() ) ) || ( $post && ( has_shortcode( $post->post_content, 'abible_home' ) || has_shortcode( $post->post_content, 'abible_library' ) ) ) ) {
            wp_enqueue_style( 'abible', plugins_url( 'assets/library.css', ABIBLE_FILE ), array(), ABIBLE_VERSION );
        }
    }

    private static function param( $name ) {
        // Read-only library filters, so no nonce is required for shareable URLs.
        return isset( $_GET[ $name ] ) && is_string( $_GET[ $name ] ) ? substr( sanitize_text_field( wp_unslash( $_GET[ $name ] ) ), 0, 200 ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    }

    private function query_args() {
        $args = array( 'post_type' => ABible_Content::TYPE, 'post_status' => 'publish', 'posts_per_page' => 12, 'paged' => min( 10000, max( 1, absint( self::param( 'ab_page' ) ) ) ), 's' => self::param( 'ab_q' ) );
        $tax = array();
        foreach ( ABible_Content::taxonomies() as $key => $label ) {
            $value = sanitize_title( self::param( $key ) );
            if ( $value ) { $tax[] = array( 'taxonomy' => $key, 'field' => 'slug', 'terms' => $value ); }
        }
        if ( $tax ) { $args['tax_query'] = $tax; }
        return $args;
    }

    public function archives( $query ) {
        if ( is_admin() || ! $query->is_main_query() || ! ( $query->is_post_type_archive( ABible_Content::TYPE ) || $query->is_tax( array_keys( ABible_Content::taxonomies() ) ) ) ) { return; }
        $query->set( 'post_type', ABible_Content::TYPE );
        $query->set( 'posts_per_page', 12 );
    }

    private function card( $post ) {
        $terms = get_the_terms( $post, 'abible_kind' );
        $label = $terms && ! is_wp_error( $terms ) ? $terms[0]->name : 'Publikacija';
        return '<article class="ab-card"><p class="ab-eyebrow">' . esc_html( $label ) . '</p><h3><a href="' . esc_url( get_permalink( $post ) ) . '">' . esc_html( get_the_title( $post ) ) . '</a></h3><p>' . esc_html( wp_trim_words( $post->post_excerpt ?: wp_strip_all_tags( strip_shortcodes( $post->post_content ) ), 32 ) ) . '</p></article>';
    }

    public function home() {
        $archive = get_post_type_archive_link( ABible_Content::TYPE );
        $html = '<section class="ab-library" aria-label="Biblijos pažinimas"><div class="ab-intro"><p class="ab-eyebrow">BIBLIJOS PAŽINIMAS</p><h2>Pažinkite Bibliją giliau</h2><p>Jos istorija, kalbos, tekstai ir atsakingas aiškinimas.</p><a class="ab-button" href="' . esc_url( $archive ) . '">Visos publikacijos</a></div><h2>Nuo ko pradėti?</h2><div class="ab-grid">';
        foreach ( array( 'istorija' => array( 'Susipažinkite su Biblija', 'Kilmė, kanonas ir teksto perdavimas.' ), 'metodika' => array( 'Mokykitės tyrinėti', 'Kontekstas, žanrai ir praktiniai žingsniai.' ), 'komentarai' => array( 'Supraskite ištrauką', 'Komentarai ir sudėtingų vietų paaiškinimai.' ) ) as $slug => $text ) {
            $term = get_term_by( 'slug', $slug, 'abible_topic' );
            if ( $term && $term->count ) { $html .= '<article class="ab-card"><h3><a href="' . esc_url( get_term_link( $term ) ) . '">' . esc_html( $text[0] ) . '</a></h3><p>' . esc_html( $text[1] ) . '</p></article>'; }
        }
        $html .= '</div><h2>Tyrinėkite biblioteką</h2>' . $this->library();
        return $html . '</section>';
    }

    public function library() {
        $id = wp_unique_id( 'ab-search-' );
        $html = '<section class="ab-library" aria-label="Publikacijų paieška"><form method="get" class="ab-filters"><label for="' . esc_attr( $id ) . '">Ieškokite publikacijų</label><input type="search" id="' . esc_attr( $id ) . '" name="ab_q" maxlength="200" value="' . esc_attr( self::param( 'ab_q' ) ) . '"><div class="ab-filter-grid">';
        // Preserve the page route when WordPress uses plain permalinks.
        if ( ! get_option( 'permalink_structure' ) && is_page() ) { $html .= '<input type="hidden" name="page_id" value="' . esc_attr( get_queried_object_id() ) . '">'; }
        foreach ( ABible_Content::taxonomies() as $key => $label ) {
            $terms = get_terms( array( 'taxonomy' => $key, 'hide_empty' => true ) );
            if ( is_wp_error( $terms ) || ! $terms ) { continue; }
            $html .= '<label>' . esc_html( $label ) . '<select name="' . esc_attr( $key ) . '"><option value="">Visi</option>';
            foreach ( $terms as $term ) { $html .= '<option value="' . esc_attr( $term->slug ) . '" ' . selected( self::param( $key ), $term->slug, false ) . '>' . esc_html( $term->name ) . '</option>'; }
            $html .= '</select></label>';
        }
        $html .= '</div><button class="ab-button" type="submit">Ieškoti</button> <a href="' . esc_url( remove_query_arg( array_merge( array_keys( ABible_Content::taxonomies() ), array( 'ab_q', 'ab_page' ) ) ) ) . '">Išvalyti filtrus</a></form>';
        $query = new WP_Query( $this->query_args() );
        $html .= '<p>' . esc_html( 'Rasta publikacijų: ' . $query->found_posts ) . '</p><div class="ab-grid">';
        foreach ( $query->posts as $post ) { $html .= $this->card( $post ); }
        $html .= '</div>';
        if ( ! $query->posts ) { $html .= '<p>Publikacijų nerasta. Pabandykite kitą žodį arba išvalykite filtrus.</p>'; }
        $links = paginate_links( array( 'base' => add_query_arg( 'ab_page', '%#%' ), 'format' => '', 'current' => $query->query_vars['paged'], 'total' => $query->max_num_pages, 'prev_text' => 'Ankstesnis', 'next_text' => 'Kitas' ) );
        if ( $links ) { $html .= '<nav class="ab-pagination" aria-label="Rezultatų puslapiai">' . wp_kses_post( $links ) . '</nav>'; }
        return $html . '</section>';
    }

    public function publication( $content ) {
        if ( ! is_singular( ABible_Content::TYPE ) || ! in_the_loop() || ! is_main_query() || post_password_required() || is_feed() ) { return $content; }
        $id = get_the_ID();
        $html = '<div class="ab-reading"><div class="ab-meta">';
        $authors = get_the_term_list( $id, 'abible_author', '', ', ' );
        if ( $authors && ! is_wp_error( $authors ) ) { $html .= '<p>Autorius: ' . wp_kses_post( $authors ) . '</p>'; }
        foreach ( array( 'institution', 'translator', 'reviewer', 'source_date', 'document_status', 'presentation' ) as $key ) {
            $value = get_post_meta( $id, '_abible_' . $key, true );
            if ( in_array( $key, array( 'document_status', 'presentation' ), true ) ) { $value = $value ? ( ABible_Content::choices( $key )[ $value ] ?? '' ) : ''; }
            if ( $value ) { $html .= '<p>' . esc_html( ABible_Content::fields()[ $key ] . ': ' . $value ) . '</p>'; }
        }
        $rows = get_post_meta( $id, '_abible_passages', true );
        foreach ( is_array( $rows ) ? $rows : array() as $row ) {
            $text = ABible_Content::books()[ $row['book'] ] ?? '';
            $text .= $row['chapter'] ? ' ' . $row['chapter'] : '';
            $text .= $row['verse'] ? ',' . $row['verse'] : '';
            $text .= $row['end_chapter'] ? '–' . $row['end_chapter'] . ( $row['end_verse'] ? ',' . $row['end_verse'] : '' ) : '';
            $html .= '<p>' . esc_html( ( 'discusses' === $row['relation'] ? 'Nagrinėjama: ' : 'Cituojama: ' ) . $text ) . '</p>';
        }
        $html .= '</div>';
        if ( has_excerpt() ) { $html .= '<p class="ab-summary">' . esc_html( get_post_field( 'post_excerpt', $id ) ) . '</p>'; }
        // Core HTML processor respects existing block anchors and avoids regex mutation of HTML.
        $processor = new WP_HTML_Tag_Processor( $content );
        $anchors = array();
        while ( $processor->next_tag() ) { $anchor = $processor->get_attribute( 'id' ); if ( is_string( $anchor ) ) { $anchors[ $anchor ] = true; } }
        $processor = new WP_HTML_Tag_Processor( $content );
        $toc = array();
        $n = 0;
        while ( $processor->next_tag( 'H2' ) ) {
            ++$n;
            $anchor = $processor->get_attribute( 'id' );
            if ( ! is_string( $anchor ) || '' === $anchor ) {
                $anchor = 'ab-section-' . $n;
                while ( isset( $anchors[ $anchor ] ) ) { $anchor .= '-section'; }
                $anchors[ $anchor ] = true;
                $processor->set_attribute( 'id', $anchor );
            }
            $toc[] = $anchor;
        }
        $content = $processor->get_updated_html();
        preg_match_all( '/<h2\b[^>]*>(.*?)<\/h2>/is', $content, $headings );
        if ( count( $toc ) > 1 ) {
            $html .= '<details class="ab-toc"><summary>Straipsnio turinys</summary><ol>';
            foreach ( $toc as $i => $anchor ) { $html .= '<li><a href="#' . esc_attr( $anchor ) . '">' . esc_html( wp_strip_all_tags( $headings[1][ $i ] ?? (string) ( $i + 1 ) ) ) . '</a></li>'; }
            $html .= '</ol></details>';
        }
        $html .= $content;
        $source = get_post_meta( $id, '_abible_source_url', true );
        if ( $source ) { $html .= '<aside class="ab-source"><h2>Originalo šaltinis</h2><a href="' . esc_url( $source ) . '">' . esc_html( get_post_meta( $id, '_abible_source_title', true ) ?: 'Skaityti originalą' ) . '</a><p>' . esc_html( get_post_meta( $id, '_abible_source_details', true ) ) . '</p></aside>'; }
        $evidence = get_post_meta( $id, '_abible_approval_evidence', true );
        if ( $evidence ) { $html .= '<p><a href="' . esc_url( $evidence ) . '">Dokumento priėmimo šaltinis</a></p>'; }
        return $html . '</div>';
    }
}
