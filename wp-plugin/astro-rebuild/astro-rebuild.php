<?php
/**
 * Plugin Name:  Astro Rebuild Trigger
 * Plugin URI:   https://github.com/yourrepo/astro-wp-starter
 * Description:  Triggers a Netlify (or any) build hook when WordPress content is published or updated. Used with headless Astro front-ends.
 * Version:      1.0.0
 * Author:       Your Name
 * License:      GPL-2.0+
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// ── Admin Settings Page ───────────────────────────────────────────────────────

add_action( 'admin_menu', 'astro_rebuild_menu' );
function astro_rebuild_menu() {
    add_options_page(
        'Astro Rebuild',
        'Astro Rebuild',
        'manage_options',
        'astro-rebuild',
        'astro_rebuild_settings_page'
    );
}

add_action( 'admin_init', 'astro_rebuild_settings_init' );
function astro_rebuild_settings_init() {
    register_setting( 'astro_rebuild', 'astro_rebuild_hook_url' );
    register_setting( 'astro_rebuild', 'astro_rebuild_enabled' );

    add_settings_section(
        'astro_rebuild_section',
        'Netlify Build Hook',
        '__return_false',
        'astro-rebuild'
    );

    add_settings_field(
        'astro_rebuild_hook_url',
        'Build Hook URL',
        'astro_rebuild_hook_url_field',
        'astro-rebuild',
        'astro_rebuild_section'
    );

    add_settings_field(
        'astro_rebuild_enabled',
        'Auto-Rebuild',
        'astro_rebuild_enabled_field',
        'astro-rebuild',
        'astro_rebuild_section'
    );
}

function astro_rebuild_hook_url_field() {
    $val = esc_attr( get_option( 'astro_rebuild_hook_url', '' ) );
    echo '<input type="url" name="astro_rebuild_hook_url" value="' . $val . '" class="regular-text" placeholder="https://api.netlify.com/build_hooks/xxxxxxxxx" />';
    echo '<p class="description">Paste your Netlify build hook URL. Found in Site Settings → Build & deploy → Build hooks.</p>';
}

function astro_rebuild_enabled_field() {
    $checked = checked( get_option( 'astro_rebuild_enabled', '1' ), '1', false );
    echo '<label><input type="checkbox" name="astro_rebuild_enabled" value="1" ' . $checked . ' /> Automatically trigger a rebuild when content is saved or published</label>';
}

function astro_rebuild_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) return;
    ?>
    <div class="wrap">
        <h1>Astro Rebuild Trigger</h1>
        <p>This plugin fires your Netlify build hook whenever a page, post, or product is published or updated — keeping your Astro static front-end in sync with WordPress.</p>
        <form method="post" action="options.php">
            <?php
            settings_fields( 'astro_rebuild' );
            do_settings_sections( 'astro-rebuild' );
            submit_button( 'Save Settings' );
            ?>
        </form>
        <hr />
        <h2>Manual Trigger</h2>
        <p>Need to force a rebuild now?</p>
        <form method="post">
            <?php wp_nonce_field( 'astro_manual_rebuild', 'astro_nonce' ); ?>
            <input type="hidden" name="astro_manual_rebuild" value="1" />
            <?php submit_button( 'Trigger Rebuild Now', 'secondary' ); ?>
        </form>
        <?php
        // Handle manual trigger
        if ( isset( $_POST['astro_manual_rebuild'] ) && check_admin_referer( 'astro_manual_rebuild', 'astro_nonce' ) ) {
            $result = astro_fire_rebuild( 'manual' );
            if ( $result ) {
                echo '<div class="notice notice-success"><p>✅ Rebuild triggered successfully.</p></div>';
            } else {
                echo '<div class="notice notice-error"><p>❌ Rebuild failed — check the hook URL above.</p></div>';
            }
        }
        ?>
        <hr />
        <h2>Rebuild Log <small style="font-size:.8em;font-weight:normal;color:#666;">(last 10)</small></h2>
        <?php
        $log = get_option( 'astro_rebuild_log', [] );
        if ( empty( $log ) ) {
            echo '<p style="color:#666;">No rebuilds triggered yet.</p>';
        } else {
            echo '<table class="widefat striped"><thead><tr><th>Time</th><th>Trigger</th><th>Result</th></tr></thead><tbody>';
            foreach ( array_reverse( $log ) as $entry ) {
                $status = $entry['success'] ? '✅ OK' : '❌ Failed';
                echo '<tr><td>' . esc_html( $entry['time'] ) . '</td><td>' . esc_html( $entry['trigger'] ) . '</td><td>' . $status . '</td></tr>';
            }
            echo '</tbody></table>';
        }
        ?>
    </div>
    <?php
}

// ── Rebuild Trigger ───────────────────────────────────────────────────────────

/**
 * Fire the build hook. Returns true on success.
 */
function astro_fire_rebuild( string $trigger = 'auto' ): bool {
    if ( get_option( 'astro_rebuild_enabled', '1' ) !== '1' ) return false;

    $hook_url = get_option( 'astro_rebuild_hook_url', '' );
    if ( empty( $hook_url ) ) return false;

    $response = wp_remote_post( $hook_url, [
        'method'  => 'POST',
        'timeout' => 10,
        'body'    => '',
    ] );

    $success = ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) < 400;

    // Log it (keep last 10)
    $log   = get_option( 'astro_rebuild_log', [] );
    $log[] = [
        'time'    => current_time( 'Y-m-d H:i:s' ),
        'trigger' => $trigger,
        'success' => $success,
    ];
    update_option( 'astro_rebuild_log', array_slice( $log, -10 ) );

    return $success;
}

// ── Hooks ─────────────────────────────────────────────────────────────────────

// Deduplicate: track which posts have already triggered a rebuild this request
$astro_triggered_ids = [];

function astro_on_save_post( int $post_id, WP_Post $post, bool $update ): void {
    global $astro_triggered_ids;

    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( wp_is_post_revision( $post_id ) ) return;
    if ( $post->post_status !== 'publish' ) return;
    if ( isset( $astro_triggered_ids[ $post_id ] ) ) return;

    $allowed_types = apply_filters( 'astro_rebuild_post_types', [ 'post', 'page', 'product' ] );
    if ( ! in_array( $post->post_type, $allowed_types, true ) ) return;

    $astro_triggered_ids[ $post_id ] = true;
    astro_fire_rebuild( $post->post_type . ':' . $post_id . ':' . ( $update ? 'update' : 'create' ) );
}
add_action( 'save_post', 'astro_on_save_post', 20, 3 );

// ACF save (fires after ACF saves its fields)
add_action( 'acf/save_post', function( $post_id ) {
    if ( ! is_numeric( $post_id ) ) return;
    $post = get_post( (int) $post_id );
    if ( $post && $post->post_status === 'publish' ) {
        astro_fire_rebuild( 'acf:' . $post_id );
    }
}, 20 );

// WooCommerce product delete
add_action( 'before_delete_post', function( int $post_id ) {
    if ( get_post_type( $post_id ) === 'product' ) {
        astro_fire_rebuild( 'product:delete:' . $post_id );
    }
} );

// Nav menu save
add_action( 'wp_update_nav_menu', function() {
    astro_fire_rebuild( 'nav_menu:update' );
} );

// Taxonomy term changes
add_action( 'created_term', function() { astro_fire_rebuild( 'term:created' ); } );
add_action( 'edited_term',  function() { astro_fire_rebuild( 'term:edited' ); } );
add_action( 'deleted_term', function() { astro_fire_rebuild( 'term:deleted' ); } );
