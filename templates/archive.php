<?php
/** Default archive; themes may override with abible/archive.php. @package AboutBible */
defined( 'ABSPATH' ) || exit;
$abible_block_theme = wp_is_block_theme();
if ( $abible_block_theme ) : ?>
<!doctype html><html <?php language_attributes(); ?>><head><meta charset="<?php bloginfo( 'charset' ); ?>"><meta name="viewport" content="width=device-width, initial-scale=1"><?php wp_head(); ?></head><body <?php body_class(); ?>>
<?php wp_body_open(); ?><div class="wp-site-blocks"><header><?php block_template_part( 'header' ); ?></header>
<?php else : get_header(); endif; ?>
<main id="abible-main" class="ab-library" style="padding:24px">
<h1><?php echo esc_html( is_tax() ? single_term_title( '', false ) : 'Biblijos biblioteka' ); ?></h1>
<?php if ( is_tax() ) { echo wp_kses_post( term_description() ); } ?>
<?php echo do_shortcode( '[abible_library]' ); ?>
</main>
<?php if ( $abible_block_theme ) : ?>
<footer><?php block_template_part( 'footer' ); ?></footer></div><?php wp_footer(); ?></body></html>
<?php else : get_footer(); endif; ?>
