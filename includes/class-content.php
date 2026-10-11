<?php
/** Content registration and validated editorial metadata. @package AboutBible */
defined( 'ABSPATH' ) || exit;

final class ABible_Content {
    const TYPE = 'abible_publication';

    public static function books() {
        static $books = null;
        return $books ??= array_combine(
            explode( ' ', 'gen exod lev num deut josh judg ruth 1sam 2sam 1kgs 2kgs 1chr 2chr ezra neh esth job ps prov eccl song isa jer lam ezek dan hos joel amos obad jonah mic nah hab zeph hag zech mal matt mark luke john acts rom 1cor 2cor gal eph phil col 1thess 2thess 1tim 2tim titus phlm heb jas 1pet 2pet 1john 2john 3john jude rev' ),
            explode( '|', 'Pradžios|Išėjimo|Kunigų|Skaičių|Pakartoto Įstatymo|Jozuės|Teisėjų|Rūtos|1 Samuelio|2 Samuelio|1 Karalių|2 Karalių|1 Metraščių|2 Metraščių|Ezros|Nehemijo|Esteros|Jobo|Psalmynas|Patarlių|Mokytojo|Giesmių giesmė|Izaijo|Jeremijo|Raudų|Ezechielio|Danieliaus|Ozėjo|Joelio|Amoso|Abdijo|Jonos|Michėjo|Nahumo|Habakuko|Sofonijo|Agėjo|Zacharijo|Malachijo|Mato|Morkaus|Luko|Jono|Apaštalų darbų|Romiečiams|1 Korintiečiams|2 Korintiečiams|Galatams|Efeziečiams|Filipiečiams|Kolosiečiams|1 Tesalonikiečiams|2 Tesalonikiečiams|1 Timotiejui|2 Timotiejui|Titui|Filemonui|Hebrajams|Jokūbo|1 Petro|2 Petro|1 Jono|2 Jono|3 Jono|Judo|Apreiškimas Jonui' )
        );
    }

    public static function taxonomies() {
        return array( 'abible_topic' => 'Temos', 'abible_book' => 'Biblijos knygos', 'abible_author' => 'Autoriai' );
    }

    public static function topics() {
        return array(
            'istorija' => array( 'Biblijos istorija ir kanonas', 'Kaip atsirado Biblijos knygos ir susiformavo Senasis bei Naujasis Testamentai.' ),
            'rankrasciai' => array( 'Rankraščiai ir teksto perdavimas', 'Papirusai, kodeksai, tekstiniai variantai ir Biblijos kelias per amžius.' ),
            'kalbos' => array( 'Biblijos kalbos', 'Hebrajų, aramėjų ir graikų kalbos: žodžiai, jų reikšmės ir teksto niuansai.' ),
            'vertimai' => array( 'Biblijos vertimai', 'Nuo senųjų vertimų iki lietuviškos Biblijos: istorija, metodai ir palyginimai.' ),
            'metodika' => array( 'Hermeneutika ir tyrinėjimo metodai', 'Kaip suprasti kontekstą, atpažinti žanrą ir nuosekliai tyrinėti Šventąjį Raštą.' ),
            'komentarai' => array( 'Biblijos knygų komentarai', 'Knygų įvadai, ištraukų paaiškinimai ir sudėtingų Biblijos vietų nagrinėjimas.' ),
            'ikvepimas' => array( 'Apreiškimas, įkvėpimas ir autoritetas', 'Biblijos kilmė, dieviškasis įkvėpimas ir Šventojo Rašto vieta tikėjime.' ),
            'irankiai' => array( 'Biblijos tyrinėjimo įrankiai', 'Žodynai, konkordancijos, atlasai ir kitos priemonės savarankiškam tyrinėjimui.' ),
        );
    }

    public static function register() {
        register_post_type( self::TYPE, array(
            'labels' => array( 'name' => 'Biblijos biblioteka', 'singular_name' => 'Publikacija', 'add_new_item' => 'Pridėti publikaciją', 'edit_item' => 'Redaguoti publikaciją' ),
            'public' => true, 'show_in_rest' => true, 'menu_icon' => 'dashicons-book-alt',
            'supports' => array( 'title', 'editor', 'excerpt', 'thumbnail', 'author', 'revisions', 'custom-fields' ),
            'has_archive' => 'biblija/biblioteka',
            'rewrite' => array( 'slug' => 'biblija/straipsniai', 'with_front' => false ),
        ) );
        $slugs = array( 'abible_topic' => 'temos', 'abible_book' => 'knygos', 'abible_author' => 'autoriai' );
        foreach ( self::taxonomies() as $key => $label ) {
            register_taxonomy( $key, self::TYPE, array(
                'label' => $label, 'public' => true, 'show_in_rest' => true, 'show_admin_column' => true,
                'hierarchical' => 'abible_topic' === $key,
                'rewrite' => array( 'slug' => 'biblija/' . $slugs[ $key ], 'with_front' => false ),
            ) );
        }
        foreach ( self::fields() as $key => $label ) {
            register_post_meta( self::TYPE, '_abible_' . $key, array(
                'type' => 'string', 'single' => true, 'show_in_rest' => false,
                'revisions_enabled' => true,
                'sanitize_callback' => 'sanitize_textarea_field',
                'auth_callback' => static function ( $allowed, $meta_key, $post_id ) { return current_user_can( 'edit_post', $post_id ); },
            ) );
        }
        register_post_meta( self::TYPE, '_abible_passages', array(
            'type' => 'array', 'single' => true, 'show_in_rest' => false, 'revisions_enabled' => true,
            'auth_callback' => static function ( $allowed, $key, $id ) { return current_user_can( 'edit_post', $id ); },
        ) );
        if ( function_exists( 'register_block_pattern' ) ) {
            register_block_pattern( 'abible/study-guide', array(
                'title' => 'Biblijos tyrinėjimo vadovas', 'categories' => array( 'text' ),
                'content' => '<!-- wp:heading --><h2 class="wp-block-heading">Perskaitykite ištrauką</h2><!-- /wp:heading --><!-- wp:paragraph --><p>Nurodykite ištrauką ir skaitymo užduotį.</p><!-- /wp:paragraph --><!-- wp:heading --><h2 class="wp-block-heading">Stebėkite ir klauskite</h2><!-- /wp:heading --><!-- wp:paragraph --><p>Ką pastebite tekste?</p><!-- /wp:paragraph --><!-- wp:heading --><h2 class="wp-block-heading">Kontekstas ir aiškinimas</h2><!-- /wp:heading --><!-- wp:paragraph --><p>Paaiškinkite argumentus ir nurodykite šaltinius.</p><!-- /wp:paragraph --><!-- wp:heading --><h2 class="wp-block-heading">Apmąstykite</h2><!-- /wp:heading --><!-- wp:paragraph --><p>Ką ištrauka padeda suprasti ir pritaikyti?</p><!-- /wp:paragraph -->',
            ) );
        }
    }

    public static function fields() {
        return array(
            'institution' => 'Institucija', 'translator' => 'Vertėjas', 'reviewer' => 'Recenzentas / peržiūrėjo',
            'source_title' => 'Originalo pavadinimas', 'source_url' => 'Originalo URL (https)',
            'source_date' => 'Originalo data (YYYY-MM-DD)', 'source_details' => 'Leidinys, numeris, puslapiai',
            'language' => 'Originalo kalba', 'document_status' => 'Dokumento statusas',
            'presentation' => 'Pateikimo forma', 'approval_evidence' => 'Priėmimo įrodymas (vieša nuoroda)',
            'editorial_notes' => 'Vidinės redakcinės pastabos (nevieša)', 'permissions' => 'Naudojimo leidimo įrašas (nevieša)',
        );
    }

    public static function choices( $key ) {
        if ( 'document_status' === $key ) {
            return array( '' => 'Nenurodyta', 'institutional' => 'Institucijos išteklius', 'scholar' => 'Autoriaus interpretacija', 'editorial' => 'Redakcinis vadovas', 'adopted' => 'Priimtas Bažnyčios dokumentas' );
        }
        return array( '' => 'Nenurodyta', 'original' => 'Originalus tekstas', 'translation' => 'Vertimas', 'summary' => 'Santrauka', 'adaptation' => 'Adaptacija' );
    }

    /** Validate the complete submission before changing any metadata. */
    public static function validate( $input ) {
        if ( ! is_array( $input ) ) { return new WP_Error( 'abible_input', 'Netinkamas metaduomenų formatas.' ); }
        $clean = array();
        foreach ( self::fields() as $key => $label ) {
            $value = $input[ $key ] ?? '';
            if ( ! is_string( $value ) || strlen( $value ) > 8000 ) { return new WP_Error( 'abible_field', 'Laukas netinkamas arba per ilgas: ' . $label ); }
            $clean[ $key ] = sanitize_textarea_field( $value );
            if ( in_array( $key, array( 'source_url', 'approval_evidence' ), true ) && '' !== $value ) {
                $url = esc_url_raw( $value, array( 'https' ) );
                if ( ! $url || ! wp_http_validate_url( $url ) ) { return new WP_Error( 'abible_url', 'Įveskite galiojančią viešą HTTPS šaltinio nuorodą.' ); }
                $clean[ $key ] = $url;
            }
            if ( in_array( $key, array( 'document_status', 'presentation' ), true ) && ! array_key_exists( $value, self::choices( $key ) ) ) { return new WP_Error( 'abible_choice', 'Pasirinkite galiojantį statusą ir pateikimo formą.' ); }
        }
        if ( $clean['source_date'] ) {
            $date = DateTimeImmutable::createFromFormat( '!Y-m-d', $clean['source_date'] );
            if ( ! $date || $date->format( 'Y-m-d' ) !== $clean['source_date'] ) { return new WP_Error( 'abible_date', 'Patikrinkite originalo datą.' ); }
        }
        if ( 'adopted' === $clean['document_status'] && ! $clean['approval_evidence'] ) { return new WP_Error( 'abible_evidence', 'Priimtam dokumentui nurodykite priėmimo įrodymo nuorodą.' ); }
        if ( in_array( $clean['presentation'], array( 'translation', 'summary', 'adaptation' ), true ) && ! $clean['source_url'] ) { return new WP_Error( 'abible_source', 'Vertimui, santraukai arba adaptacijai reikia originalo nuorodos.' ); }
        return $clean;
    }

    /** Canonical book IDs; structural bounds, not edition-specific verse counts. */
    public static function passages( $rows ) {
        if ( ! is_array( $rows ) || count( $rows ) > 20 ) { return new WP_Error( 'abible_passages', 'Galima nurodyti iki 20 ištraukų.' ); }
        $result = array();
        foreach ( $rows as $row ) {
            if ( ! is_array( $row ) ) { return new WP_Error( 'abible_passage', 'Netinkamas ištraukos formatas.' ); }
            $book = $row['book'] ?? '';
            if ( '' === $book ) { continue; }
            if ( ! is_string( $book ) || ! isset( self::books()[ $book ] ) ) { return new WP_Error( 'abible_book', 'Pasirinkite Biblijos knygą.' ); }
            $item = array( 'book' => $book );
            foreach ( array( 'chapter', 'verse', 'end_chapter', 'end_verse' ) as $key ) {
                $value = $row[ $key ] ?? '';
                if ( ! is_scalar( $value ) || ( '' !== (string) $value && ( ! ctype_digit( (string) $value ) || (int) $value < 1 || (int) $value > 176 ) ) ) { return new WP_Error( 'abible_range', 'Skyrius ir eilutė turi būti teigiami skaičiai iki 176.' ); }
                $item[ $key ] = '' === (string) $value ? null : (int) $value;
            }
            if ( ( $item['verse'] && ! $item['chapter'] ) || ( $item['end_chapter'] && ! $item['chapter'] ) || ( $item['end_verse'] && ! $item['verse'] ) ) { return new WP_Error( 'abible_range', 'Trūksta ištraukos pradžios arba skyriaus.' ); }
            if ( $item['end_verse'] && ! $item['end_chapter'] ) { $item['end_chapter'] = $item['chapter']; }
            if ( $item['end_chapter'] && ( $item['end_chapter'] < $item['chapter'] || ( $item['end_chapter'] === $item['chapter'] && $item['end_verse'] && $item['end_verse'] < $item['verse'] ) ) ) { return new WP_Error( 'abible_range', 'Ištraukos pabaiga negali būti ankstesnė už pradžią.' ); }
            $item['relation'] = $row['relation'] ?? 'discusses';
            if ( ! in_array( $item['relation'], array( 'discusses', 'cites' ), true ) ) { return new WP_Error( 'abible_relation', 'Netinkamas ryšys su ištrauka.' ); }
            $result[] = $item;
        }
        return $result;
    }

    public static function activate() {
        self::register();
        $terms = array(
            'abible_book' => self::books(),
            'abible_topic' => array_map( static fn( $topic ) => $topic[0], self::topics() ),
        );
        foreach ( $terms as $taxonomy => $values ) {
            foreach ( $values as $slug => $name ) {
                if ( ! term_exists( $slug, $taxonomy ) ) { wp_insert_term( $name, $taxonomy, array( 'slug' => $slug, 'description' => 'abible_topic' === $taxonomy ? self::topics()[ $slug ][1] : '' ) ); }
            }
        }
        flush_rewrite_rules();
    }
}
