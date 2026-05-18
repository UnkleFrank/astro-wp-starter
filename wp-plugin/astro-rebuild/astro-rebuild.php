<?php
/**
 * Plugin Name:  Astro Rebuild Trigger
 * Plugin URI:   https://github.com/UnkleFrank/astro-wp-starter
 * Description:  Triggers static site rebuilds whenever WordPress content is saved.
 *               Supports Netlify build hooks, Cloudflare Pages deploy hooks, and
 *               GitHub Actions (wget crawl) — configure any or all three.
 * Version:      2.0.0
 * Author:       Your Name
 * License:      GPL-2.0+
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// ── Settings Registration ─────────────────────────────────────────────────────

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

    // ── Section 1: Deploy Hook (Netlify / Cloudflare Pages) ───────────────────
    register_setting( 'astro_rebuild', 'astro_rebuild_hook_url' );
    register_setting( 'astro_rebuild', 'astro_rebuild_enabled' );

    add_settings_section(
        'astro_rebuild_hook_section',
        'Option 1 — Deploy Hook (Netlify / Cloudflare Pages)',
        function() {
            echo '<p style="color:#555;">Paste a Netlify build hook or Cloudflare Pages deploy hook URL. Used for Astro starter kit sites.</p>';
        },
        'astro-rebuild'
    );

    add_settings_field( 'astro_rebuild_hook_url', 'Hook URL',
        'astro_rebuild_hook_url_field', 'astro-rebuild', 'astro_rebuild_hook_section' );

    // ── Section 2: GitHub Actions (wget crawl) ────────────────────────────────
    register_setting( 'astro_rebuild', 'astro_rebuild_github_repo' );
    register_setting( 'astro_rebuild', 'astro_rebuild_github_token' );
    register_setting( 'astro_rebuild', 'astro_rebuild_github_workflow' );
    register_setting( 'astro_rebuild', 'astro_rebuild_github_branch' );
    register_setting( 'astro_rebuild', 'astro_rebuild_crawl_url' );

    add_settings_section(
        'astro_rebuild_github_section',
        'Option 2 — GitHub Actions (Free / Divi / Any page builder)',
        function() {
            echo '<p style="color:#555;">Triggers a GitHub Actions workflow that crawls this WordPress site with wget, '
               . 'commits the static HTML to a GitHub repo, and Cloudflare Pages deploys it. '
               . 'Works with Divi, Elementor, and any page builder. Completely free.</p>';
        },
        'astro-rebuild'
    );

    add_settings_field( 'astro_rebuild_github_repo',     'GitHub Repo',
        'astro_rebuild_github_repo_field',     'astro-rebuild', 'astro_rebuild_github_section' );
    add_settings_field( 'astro_rebuild_github_token',    'Personal Access Token',
        'astro_rebuild_github_token_field',    'astro-rebuild', 'astro_rebuild_github_section' );
    add_settings_field( 'astro_rebuild_github_workflow', 'Workflow File',
        'astro_rebuild_github_workflow_field', 'astro-rebuild', 'astro_rebuild_github_section' );
    add_settings_field( 'astro_rebuild_github_branch',   'Branch',
        'astro_rebuild_github_branch_field',   'astro-rebuild', 'astro_rebuild_github_section' );
    add_settings_field( 'astro_rebuild_crawl_url',       'URL to Crawl',
        'astro_rebuild_crawl_url_field',       'astro-rebuild', 'astro_rebuild_github_section' );

    // ── Section 3: General ────────────────────────────────────────────────────
    add_settings_section( 'astro_rebuild_general_section', 'General', '__return_false', 'astro-rebuild' );

    add_settings_field( 'astro_rebuild_enabled', 'Auto-Rebuild',
        'astro_rebuild_enabled_field', 'astro-rebuild', 'astro_rebuild_general_section' );
}

// ── Field Renderers ───────────────────────────────────────────────────────────

function astro_rebuild_hook_url_field() {
    $val = esc_attr( get_option( 'astro_rebuild_hook_url', '' ) );
    echo '<input type="url" name="astro_rebuild_hook_url" value="' . $val . '" class="regular-text"
          placeholder="https://api.netlify.com/build_hooks/xxx" />';
    echo '<p class="description">
        <strong>Netlify:</strong> Site Settings &rarr; Build &amp; deploy &rarr; Build hooks &rarr; Add build hook<br>
        <strong>Cloudflare Pages:</strong> Pages project &rarr; Settings &rarr; Builds &amp; deployments &rarr; Add deploy hook
    </p>';
}

function astro_rebuild_github_repo_field() {
    $val = esc_attr( get_option( 'astro_rebuild_github_repo', '' ) );
    echo '<input type="text" name="astro_rebuild_github_repo" value="' . $val . '" class="regular-text"
          placeholder="your-username/your-static-repo" />';
    echo '<p class="description">The GitHub repo that stores your static HTML output. Format: <code>owner/repo</code></p>';
}

function astro_rebuild_github_token_field() {
    $val = esc_attr( get_option( 'astro_rebuild_github_token', '' ) );
    echo '<input type="password" name="astro_rebuild_github_token" value="' . $val . '" class="regular-text"
          placeholder="ghp_xxxxxxxxxxxxxxxxxxxx" />';
    echo '<p class="description">
        Personal Access Token with <strong>repo</strong> and <strong>actions</strong> scope.<br>
        Generate at: <a href="https://github.com/settings/tokens" target="_blank">github.com/settings/tokens</a>
    </p>';
}

function astro_rebuild_github_workflow_field() {
    $val = esc_attr( get_option( 'astro_rebuild_github_workflow', 'static-deploy.yml' ) );
    echo '<input type="text" name="astro_rebuild_github_workflow" value="' . $val . '" class="regular-text"
          placeholder="static-deploy.yml" />';
    echo '<p class="description">Workflow filename in <code>.github/workflows/</code> of your static repo.</p>';
}

function astro_rebuild_github_branch_field() {
    $val = esc_attr( get_option( 'astro_rebuild_github_branch', 'main' ) );
    echo '<input type="text" name="astro_rebuild_github_branch" value="' . $val . '" class="regular-text"
          placeholder="main" />';
    echo '<p class="description">Branch to dispatch the workflow on.</p>';
}

function astro_rebuild_crawl_url_field() {
    $val = esc_attr( get_option( 'astro_rebuild_crawl_url', get_site_url() ) );
    echo '<input type="url" name="astro_rebuild_crawl_url" value="' . $val . '" class="regular-text"
          placeholder="' . esc_attr( get_site_url() ) . '" />';
    echo '<p class="description">The URL wget will crawl. Defaults to this WordPress site.</p>';
}

function astro_rebuild_enabled_field() {
    $checked = checked( get_option( 'astro_rebuild_enabled', '1' ), '1', false );
    echo '<label><input type="checkbox" name="astro_rebuild_enabled" value="1" ' . $checked . ' />
          Automatically trigger rebuilds when content is saved or published</label>';
}

// ── Settings Page ─────────────────────────────────────────────────────────────

function astro_rebuild_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) return;
    ?>
    <div class="wrap">
        <h1>Astro Rebuild Trigger</h1>
        <p>Configure one or more deployment triggers below. All configured triggers fire on every content save.</p>

        <form method="post" action="options.php">
            <?php
            settings_fields( 'astro_rebuild' );
            do_settings_sections( 'astro-rebuild' );
            submit_button( 'Save Settings' );
            ?>
        </form>

        <hr />
        <h2>Manual Trigger</h2>
        <p>Fire all configured triggers now.</p>
        <form method="post">
            <?php wp_nonce_field( 'astro_manual_rebuild', 'astro_nonce' ); ?>
            <input type="hidden" name="astro_manual_rebuild" value="1" />
            <?php submit_button( 'Trigger Rebuild Now', 'secondary' ); ?>
        </form>
        <?php
        if ( isset( $_POST['astro_manual_rebuild'] ) && check_admin_referer( 'astro_manual_rebuild', 'astro_nonce' ) ) {
            $results = astro_fire_rebuild( 'manual' );
            foreach ( $results as $method => $ok ) {
                $icon = $ok ? '&#9989;' : '&#10060;';
                $type = $ok ? 'notice-success' : 'notice-error';
                echo "<div class='notice {$type}'><p>{$icon} <strong>{$method}:</strong> " . ( $ok ? 'Triggered successfully.' : 'Failed — check settings above.' ) . '</p></div>';
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
            echo '<table class="widefat striped"><thead><tr><th>Time</th><th>Trigger</th><th>Hook</th><th>GitHub Actions</th></tr></thead><tbody>';
            foreach ( array_reverse( $log ) as $entry ) {
                $hook_status = isset( $entry['hook'] )
                    ? ( $entry['hook'] ? '&#9989; OK' : '&#10060; Failed' )
                    : '&mdash;';
                $gh_status = isset( $entry['github'] )
                    ? ( $entry['github'] ? '&#9989; OK' : '&#10060; Failed' )
                    : '&mdash;';
                echo '<tr>'
                   . '<td>' . esc_html( $entry['time'] )    . '</td>'
                   . '<td>' . esc_html( $entry['trigger'] ) . '</td>'
                   . '<td>' . $hook_status                  . '</td>'
                   . '<td>' . $gh_status                    . '</td>'
                   . '</tr>';
            }
            echo '</tbody></table>';
        }
        ?>
    </div>
    <?php
}

// ── Rebuild Trigger ───────────────────────────────────────────────────────────

/**
 * Fire all configured triggers.
 * Returns an array: [ 'hook' => bool|null, 'github' => bool|null ]
 * null means that trigger is not configured.
 */
function astro_fire_rebuild( string $trigger = 'auto' ): array {
    if ( get_option( 'astro_rebuild_enabled', '1' ) !== '1' ) return [];

    $results = [];

    // ── Trigger 1: Deploy hook (Netlify / Cloudflare Pages) ───────────────────
    $hook_url = get_option( 'astro_rebuild_hook_url', '' );
    if ( ! empty( $hook_url ) ) {
        $response       = wp_remote_post( $hook_url, [ 'timeout' => 10, 'body' => '' ] );
        $results['hook'] = ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) < 400;
    }

    // ── Trigger 2: GitHub Actions workflow dispatch ───────────────────────────
    $gh_repo     = get_option( 'astro_rebuild_github_repo',     '' );
    $gh_token    = get_option( 'astro_rebuild_github_token',    '' );
    $gh_workflow = get_option( 'astro_rebuild_github_workflow', 'static-deploy.yml' );
    $gh_branch   = get_option( 'astro_rebuild_github_branch',   'main' );
    $crawl_url   = get_option( 'astro_rebuild_crawl_url',       get_site_url() );

    if ( ! empty( $gh_repo ) && ! empty( $gh_token ) ) {
        $api_url  = "https://api.github.com/repos/{$gh_repo}/actions/workflows/{$gh_workflow}/dispatches";
        $response = wp_remote_post( $api_url, [
            'timeout' => 15,
            'headers' => [
                'Authorization'        => 'Bearer ' . $gh_token,
                'Accept'               => 'application/vnd.github+json',
                'Content-Type'         => 'application/json',
                'X-GitHub-Api-Version' => '2022-11-28',
                'User-Agent'           => 'WordPress/' . get_bloginfo('version') . '; ' . get_site_url(),
            ],
            'body' => wp_json_encode([
                'ref'    => $gh_branch,
                'inputs' => [ 'wp_url' => $crawl_url ],
            ]),
        ] );
        // GitHub returns 204 No Content on success
        $code = is_wp_error( $response ) ? 0 : wp_remote_retrieve_response_code( $response );
        $results['github'] = ( $code === 204 );
    }

    // ── Log ───────────────────────────────────────────────────────────────────
    if ( ! empty( $results ) ) {
        $log   = get_option( 'astro_rebuild_log', [] );
        $entry = array_merge( [ 'time' => current_time( 'Y-m-d H:i:s' ), 'trigger' => $trigger ], $results );
        $log[] = $entry;
        update_option( 'astro_rebuild_log', array_slice( $log, -10 ) );
    }

    return $results;
}

// ── WordPress Hooks ───────────────────────────────────────────────────────────

$astro_triggered_ids = [];

function astro_on_save_post( int $post_id, WP_Post $post, bool $update ): void {
    global $astro_triggered_ids;
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )      return;
    if ( wp_is_post_revision( $post_id ) )                     return;
    if ( $post->post_status !== 'publish' )                    return;
    if ( isset( $astro_triggered_ids[ $post_id ] ) )           return;

    $allowed_types = apply_filters( 'astro_rebuild_post_types', [ 'post', 'page', 'product' ] );
    if ( ! in_array( $post->post_type, $allowed_types, true ) ) return;

    $astro_triggered_ids[ $post_id ] = true;
    astro_fire_rebuild( $post->post_type . ':' . $post_id . ':' . ( $update ? 'update' : 'create' ) );
}
add_action( 'save_post', 'astro_on_save_post', 20, 3 );

add_action( 'acf/save_post', function( $post_id ) {
    if ( ! is_numeric( $post_id ) ) return;
    $post = get_post( (int) $post_id );
    if ( $post && $post->post_status === 'publish' ) {
        astro_fire_rebuild( 'acf:' . $post_id );
    }
}, 20 );

add_action( 'before_delete_post', function( int $post_id ) {
    if ( get_post_type( $post_id ) === 'product' ) {
        astro_fire_rebuild( 'product:delete:' . $post_id );
    }
} );

add_action( 'wp_update_nav_menu', function() { astro_fire_rebuild( 'nav_menu:update' ); } );

add_action( 'created_term', function() { astro_fire_rebuild( 'term:created' ); } );
add_action( 'edited_term',  function() { astro_fire_rebuild( 'term:edited' );  } );
add_action( 'deleted_term', function() { astro_fire_rebuild( 'term:deleted' ); } );
