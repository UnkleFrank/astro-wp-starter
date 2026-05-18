<?php
/**
 * Plugin Name:  Divi REST Renderer
 * Description:  Adds a divi_rendered field to WordPress REST API responses containing
 *               fully processed Divi HTML. Use page.divi_rendered instead of
 *               page.content.rendered in your Astro templates on Divi-powered sites.
 * Version:      1.0.0
 * License:      GPL-2.0+
 */

if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'rest_api_init', function () {

    foreach ( [ 'post', 'page' ] as $post_type ) {

        register_rest_field( $post_type, 'divi_rendered', [

            'get_callback' => function ( $post_arr ) {
                $post_id = $post_arr['id'];
                $post    = get_post( $post_id );

                if ( ! $post ) return null;

                // Only run if Divi is active and used on this post
                if ( ! function_exists( 'et_pb_is_pagebuilder_used' ) ) return null;
                if ( ! et_pb_is_pagebuilder_used( $post_id ) )          return null;

                // Set up post data so shortcodes have full context
                $orig = isset( $GLOBALS['post'] ) ? $GLOBALS['post'] : null;
                $GLOBALS['post'] = $post;
                setup_postdata( $post );

                // Run through the full WordPress content pipeline (processes all shortcodes)
                $rendered = apply_filters( 'the_content', $post->post_content );

                // Restore original post context
                if ( $orig ) {
                    $GLOBALS['post'] = $orig;
                    setup_postdata( $orig );
                } else {
                    wp_reset_postdata();
                }

                return $rendered;
            },

            'schema' => [
                'description' => 'Divi page builder content rendered to HTML',
                'type'        => 'string',
                'context'     => [ 'view' ],
            ],
        ] );
    }

} );
