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
        add_filter( 'template_include', array( $this, 'archive_template' ) );
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
        if ( is_tax( array_keys( ABible_Content::taxonomies() ) ) ) {
            $term = get_queried_object();
            $tax[] = array( 'taxonomy' => $term->taxonomy, 'field' => 'term_id', 'terms' => $term->term_id );
        }
        foreach ( array( 'abible_topic' => 'Temos', 'abible_book' => 'Biblijos knygos' ) as $key => $label ) {
            $value = sanitize_title( self::param( 'filter_' . $key ) );
            if ( $value ) { $tax[] = array( 'taxonomy' => $key, 'field' => 'slug', 'terms' => $value ); }
        }
        if ( $tax ) { $args['tax_query'] = $tax; }
        return $args;
    }

    public function archive_template( $template ) {
        if ( is_post_type_archive( ABible_Content::TYPE ) || is_tax( array_keys( ABible_Content::taxonomies() ) ) ) {
            $override = locate_template( 'abible/archive.php' );
            return $override ?: ABIBLE_PATH . 'templates/archive.php';
        }
        return $template;
    }

    public function archives( $query ) {
        if ( is_admin() || ! $query->is_main_query() || ! ( $query->is_post_type_archive( ABible_Content::TYPE ) || $query->is_tax( array_keys( ABible_Content::taxonomies() ) ) ) ) { return; }
        $query->set( 'post_type', ABible_Content::TYPE );
        $query->set( 'posts_per_page', 12 );
    }

    private function card( $post ) {
        $authors = get_the_terms( $post, 'abible_author' );
        $credit = $authors && ! is_wp_error( $authors ) ? implode( ', ', wp_list_pluck( $authors, 'name' ) ) : '';
        // Password-protected publications must not leak their excerpt or body through listings.
        $summary = post_password_required( $post ) ? 'Publikacija apsaugota slaptažodžiu.' : wp_trim_words( $post->post_excerpt ?: wp_strip_all_tags( strip_shortcodes( $post->post_content ) ), 32 );
        return '<article class="ab-article-row"><h3><a href="' . esc_url( get_permalink( $post ) ) . '">' . esc_html( get_the_title( $post ) ) . '</a></h3>' . ( $credit ? '<p class="ab-credit">' . esc_html( $credit ) . '</p>' : '' ) . '<p>' . esc_html( $summary ) . '</p></article>';
    }

    public static function topic_image( $term, $slug ) {
        $image_id = $term ? absint( get_term_meta( $term->term_id, '_abible_image_id', true ) ) : 0;
        if ( $image_id && wp_attachment_is_image( $image_id ) ) {
            return wp_get_attachment_image( $image_id, 'large', false, array( 'alt' => '', 'loading' => 'lazy', 'class' => 'ab-topic-image' ) );
        }
        return '<img class="ab-topic-image" src="' . esc_url( plugins_url( 'assets/topics/' . $slug . '.svg', ABIBLE_FILE ) ) . '" alt="" width="800" height="480" loading="lazy">';
    }

    public function home() {
        $archive = get_post_type_archive_link( ABible_Content::TYPE );
        $html = '<section class="ab-library ab-home" aria-label="Biblijos pažinimas"><div class="ab-intro"><p class="ab-eyebrow">BIBLIJOS PAŽINIMAS</p><h2>Pažinkite Bibliją giliau</h2><p>Biblijos istorija, kalbos, tekstai ir tyrinėjimo metodai vienoje vietoje.</p><a class="ab-text-link" href="#ab-temos">Pasirinkite temą <span aria-hidden="true">↓</span></a></div><div class="ab-section-heading" id="ab-temos"><h2>Tyrinėkite pagal temą</h2><p>Nuo Biblijos ištakų iki jos aiškinimo — pasirinkite tai, ką norite pažinti.</p></div><div class="ab-topics">';
        // One query loads every topic term and primes term meta for the image lookups.
        $terms = get_terms( array( 'taxonomy' => 'abible_topic', 'slug' => array_keys( ABible_Content::topics() ), 'hide_empty' => false ) );
        $terms = is_wp_error( $terms ) ? array() : array_column( $terms, null, 'slug' );
        foreach ( ABible_Content::topics() as $slug => $topic ) {
            $term = $terms[ $slug ] ?? null;
            if ( ! $term ) { continue; }
            $link = get_term_link( $term );
            if ( is_wp_error( $link ) ) { continue; }
            $description = $term->description ?: $topic[1];
            $html .= '<a class="ab-topic-card" href="' . esc_url( $link ) . '">' . self::topic_image( $term, $slug ) . '<div class="ab-topic-body"><h3>' . esc_html( $term->name ) . '</h3><p>' . esc_html( $description ) . '</p><span class="ab-topic-link">Pažinti temą <span aria-hidden="true">↗</span></span></div></a>';
        }
        $html .= '</div><div class="ab-browse"><h2>Ieškote konkrečios Biblijos knygos?</h2><p>Raskite turimus įvadus ir ištraukų paaiškinimus.</p><div class="ab-book-links">';
        $books = get_terms( array( 'taxonomy' => 'abible_book', 'hide_empty' => true ) );
        if ( ! is_wp_error( $books ) ) {
            foreach ( $books as $book ) {
                $link = get_term_link( $book );
                if ( ! is_wp_error( $link ) ) { $html .= '<a href="' . esc_url( $link ) . '">' . esc_html( $book->name ) . '</a>'; }
            }
        }
        $html .= '</div><a class="ab-text-link" href="' . esc_url( $archive ) . '">Visos publikacijos ir paieška →</a></div></section>';
        return $html;
    }

    public function library() {
        if ( is_tax( 'abible_topic' ) && 0 === (int) get_queried_object()->count ) {
            return '<section class="ab-library"><p class="ab-empty">Šios temos publikacijos ruošiamos.</p><a class="ab-text-link" href="' . esc_url( get_post_type_archive_link( ABible_Content::TYPE ) ) . '">Peržiūrėti turimas publikacijas →</a></section>';
        }
        $id = wp_unique_id( 'ab-search-' );
        $action = is_page() ? get_permalink( get_queried_object_id() ) : ( is_tax() ? get_term_link( get_queried_object() ) : get_post_type_archive_link( ABible_Content::TYPE ) );
        $html = '<section class="ab-library" aria-label="Publikacijų paieška"><form method="get" action="' . esc_url( $action ) . '" class="ab-filters"><label for="' . esc_attr( $id ) . '">Ieškokite publikacijų</label><input type="search" id="' . esc_attr( $id ) . '" name="ab_q" maxlength="200" value="' . esc_attr( self::param( 'ab_q' ) ) . '"><div class="ab-filter-grid">';
        // Preserve the page route when WordPress uses plain permalinks.
        if ( ! get_option( 'permalink_structure' ) && is_page() ) { $html .= '<input type="hidden" name="page_id" value="' . esc_attr( get_queried_object_id() ) . '">'; }
        if ( ! get_option( 'permalink_structure' ) && is_post_type_archive( ABible_Content::TYPE ) ) { $html .= '<input type="hidden" name="post_type" value="' . esc_attr( ABible_Content::TYPE ) . '">'; }
        foreach ( array( 'abible_topic' => 'Temos', 'abible_book' => 'Biblijos knygos' ) as $key => $label ) {
            if ( is_tax( $key ) ) { continue; }
            $terms = get_terms( array( 'taxonomy' => $key, 'hide_empty' => true ) );
            if ( is_wp_error( $terms ) || ! $terms ) { continue; }
            $html .= '<label>' . esc_html( $label ) . '<select name="' . esc_attr( 'filter_' . $key ) . '"><option value="">Visi</option>';
            foreach ( $terms as $term ) { $html .= '<option value="' . esc_attr( $term->slug ) . '" ' . selected( self::param( 'filter_' . $key ), $term->slug, false ) . '>' . esc_html( $term->name ) . '</option>'; }
            $html .= '</select></label>';
        }
        $html .= '</div><button class="ab-button" type="submit">Ieškoti</button> <a href="' . esc_url( remove_query_arg( array_merge( array_map( static fn( $key ) => 'filter_' . $key, array_keys( ABible_Content::taxonomies() ) ), array( 'ab_q', 'ab_page' ) ) ) ) . '">Išvalyti filtrus</a></form>';
        $query = new WP_Query( $this->query_args() );
        $html .= '<p>' . esc_html( 'Rasta publikacijų: ' . $query->found_posts ) . '</p><div class="ab-article-list">';
        foreach ( $query->posts as $post ) { $html .= $this->card( $post ); }
        $html .= '</div>';
        if ( ! $query->posts ) { $html .= is_tax( 'abible_topic' ) && ! self::param( 'ab_q' ) ? '<p class="ab-empty">Šios temos publikacijos ruošiamos. Galite susipažinti su kitomis Biblijos pažinimo temomis.</p>' : '<p>Publikacijų nerasta. Pabandykite kitą žodį arba išvalykite filtrus.</p>'; }
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
            // Tolerate rows imported or written outside the validated editor.
            if ( ! is_array( $row ) || ! isset( ABible_Content::books()[ $row['book'] ?? '' ] ) ) { continue; }
            $row += array( 'chapter' => null, 'verse' => null, 'end_chapter' => null, 'end_verse' => null, 'relation' => 'discusses' );
            $text = ABible_Content::books()[ $row['book'] ];
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
