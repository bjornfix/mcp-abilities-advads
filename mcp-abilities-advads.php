<?php
/**
 * Plugin Name: MCP Abilities - Advanced Ads
 * Plugin URI: https://github.com/bjornfix/mcp-abilities-advads
 * Description: MCP abilities for Advanced Ads. Manage ads, placements, groups, and settings programmatically.
 * Version: 1.0.1
 * Author: Devenia
 * Author URI: https://devenia.com
 * License: GPL-2.0+
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Requires Plugins: abilities-api
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function mcp_advads_check_dependencies(): bool {
    if ( function_exists( 'wp_register_ability' ) ) {
        return true;
    }
    add_action( 'admin_notices', function () {
        echo '<div class="notice notice-error"><p><strong>MCP Abilities - Advanced Ads</strong> requires the Abilities API plugin.</p></div>';
    } );
    return false;
}

function mcp_advads_require_active(): ?array {
    if ( defined( 'ADVADS_FILE' ) ) {
        return null;
    }
    return array( 'success' => false, 'message' => 'Advanced Ads not active.' );
}

function mcp_advads_permission_callback(): bool {
    return current_user_can( 'manage_options' );
}

function mcp_register_advads_abilities(): void {
    if ( ! mcp_advads_check_dependencies() ) {
        return;
    }

    wp_register_ability(
        'advads/list-ads',
        array(
            'label'               => 'List Ads',
            'description'         => 'List all Advanced Ads advertisements.',
            'category'            => 'site',
            'input_schema'        => array(
                'type'                 => 'object',
                'properties'           => array(
                    'status' => array(
                        'type'        => 'string',
                        'default'     => 'any',
                        'description' => 'Filter by status (publish, draft, any).',
                    ),
                ),
                'additionalProperties' => false,
            ),
            'output_schema'       => array(
                'type'       => 'object',
                'properties' => array(
                    'success' => array( 'type' => 'boolean' ),
                    'ads'     => array( 'type' => 'array' ),
                    'total'   => array( 'type' => 'integer' ),
                    'message' => array( 'type' => 'string' ),
                ),
            ),
            'execute_callback'    => function ( array $input = array() ): array {
                if ( $error = mcp_advads_require_active() ) {
                    return $error;
                }

                $posts = get_posts( array(
                    'post_type'      => 'advanced_ads',
                    'post_status'    => $input['status'] ?? 'any',
                    'posts_per_page' => 100,
                ) );

                $ads = array();
                foreach ( $posts as $post ) {
                    $options = get_post_meta( $post->ID, 'advanced_ads_ad_options', true );
                    $ads[] = array(
                        'id'     => $post->ID,
                        'title'  => $post->post_title,
                        'status' => $post->post_status,
                        'type'   => $options['type'] ?? 'unknown',
                    );
                }

                return array( 'success' => true, 'ads' => $ads, 'total' => count( $ads ) );
            },
            'permission_callback' => 'mcp_advads_permission_callback',
        )
    );

    wp_register_ability(
        'advads/get-ad',
        array(
            'label'               => 'Get Ad',
            'description'         => 'Get details of a specific ad.',
            'category'            => 'site',
            'input_schema'        => array(
                'type'                 => 'object',
                'required'             => array( 'id' ),
                'properties'           => array(
                    'id' => array(
                        'type'        => 'integer',
                        'description' => 'Ad ID.',
                    ),
                ),
                'additionalProperties' => false,
            ),
            'output_schema'       => array(
                'type'       => 'object',
                'properties' => array(
                    'success' => array( 'type' => 'boolean' ),
                    'ad'      => array( 'type' => 'object' ),
                    'message' => array( 'type' => 'string' ),
                ),
            ),
            'execute_callback'    => function ( array $input ): array {
                if ( $error = mcp_advads_require_active() ) {
                    return $error;
                }

                $post = get_post( (int) $input['id'] );
                if ( ! $post || 'advanced_ads' !== $post->post_type ) {
                    return array( 'success' => false, 'message' => 'Ad not found.' );
                }

                $options = get_post_meta( $post->ID, 'advanced_ads_ad_options', true ) ?: array();

                return array(
                    'success' => true,
                    'ad'      => array(
                        'id'      => $post->ID,
                        'title'   => $post->post_title,
                        'status'  => $post->post_status,
                        'content' => $post->post_content,
                        'type'    => $options['type'] ?? 'unknown',
                        'options' => $options,
                    ),
                );
            },
            'permission_callback' => 'mcp_advads_permission_callback',
        )
    );

    wp_register_ability(
        'advads/list-placements',
        array(
            'label'               => 'List Placements',
            'description'         => 'List all ad placements.',
            'category'            => 'site',
            'input_schema'        => array(
                'type'                 => 'object',
                'properties'           => array(),
                'additionalProperties' => true,
            ),
            'output_schema'       => array(
                'type'       => 'object',
                'properties' => array(
                    'success'    => array( 'type' => 'boolean' ),
                    'placements' => array( 'type' => 'object' ),
                    'total'      => array( 'type' => 'integer' ),
                ),
            ),
            'execute_callback'    => function ( array $input = array() ): array {
                if ( $error = mcp_advads_require_active() ) {
                    return $error;
                }

                // Advanced Ads 2.0+ stores placements as custom post type.
                $posts = get_posts( array(
                    'post_type'      => 'advanced_ads_plcmnt',
                    'post_status'    => 'any',
                    'posts_per_page' => 100,
                ) );

                $placements = array();
                foreach ( $posts as $post ) {
                    $type = get_post_meta( $post->ID, 'type', true );
                    $item = get_post_meta( $post->ID, 'item', true );
                    $options = get_post_meta( $post->ID, 'options', true );
                    $placements[] = array(
                        'id'      => $post->ID,
                        'slug'    => $post->post_name,
                        'name'    => $post->post_title,
                        'status'  => $post->post_status,
                        'type'    => $type ?: 'default',
                        'item'    => $item ?: '',
                        'options' => $options ?: array(),
                    );
                }

                return array(
                    'success'    => true,
                    'placements' => $placements,
                    'total'      => count( $placements ),
                );
            },
            'permission_callback' => 'mcp_advads_permission_callback',
        )
    );

    wp_register_ability(
        'advads/create-placement',
        array(
            'label'               => 'Create Placement',
            'description'         => 'Create a new ad placement.',
            'category'            => 'site',
            'input_schema'        => array(
                'type'                 => 'object',
                'required'             => array( 'slug', 'name', 'type', 'item' ),
                'properties'           => array(
                    'slug' => array(
                        'type'        => 'string',
                        'description' => 'Unique placement slug.',
                    ),
                    'name' => array(
                        'type'        => 'string',
                        'description' => 'Display name.',
                    ),
                    'type' => array(
                        'type'        => 'string',
                        'description' => 'Placement type (post_content, header, footer, etc.).',
                    ),
                    'item' => array(
                        'type'        => 'string',
                        'description' => 'Ad or group (ad_123 or group_123).',
                    ),
                    'options' => array(
                        'type'        => 'object',
                        'description' => 'Additional options.',
                    ),
                ),
                'additionalProperties' => false,
            ),
            'output_schema'       => array(
                'type'       => 'object',
                'properties' => array(
                    'success' => array( 'type' => 'boolean' ),
                    'slug'    => array( 'type' => 'string' ),
                    'message' => array( 'type' => 'string' ),
                ),
            ),
            'execute_callback'    => function ( array $input ): array {
                if ( $error = mcp_advads_require_active() ) {
                    return $error;
                }

                $slug = sanitize_title( $input['slug'] );

                // Check if placement with slug exists.
                $existing = get_posts( array(
                    'post_type'   => 'advanced_ads_plcmnt',
                    'name'        => $slug,
                    'post_status' => 'any',
                    'numberposts' => 1,
                ) );

                if ( ! empty( $existing ) ) {
                    return array( 'success' => false, 'message' => 'Placement slug already exists.' );
                }

                $post_id = wp_insert_post( array(
                    'post_type'   => 'advanced_ads_plcmnt',
                    'post_title'  => sanitize_text_field( $input['name'] ),
                    'post_name'   => $slug,
                    'post_status' => 'publish',
                ) );

                if ( is_wp_error( $post_id ) ) {
                    return array( 'success' => false, 'message' => $post_id->get_error_message() );
                }

                update_post_meta( $post_id, 'type', sanitize_text_field( $input['type'] ) );
                update_post_meta( $post_id, 'item', sanitize_text_field( $input['item'] ) );
                if ( ! empty( $input['options'] ) ) {
                    update_post_meta( $post_id, 'options', $input['options'] );
                }

                return array( 'success' => true, 'slug' => $slug, 'id' => $post_id, 'message' => 'Placement created.' );
            },
            'permission_callback' => 'mcp_advads_permission_callback',
        )
    );

    wp_register_ability(
        'advads/delete-placement',
        array(
            'label'               => 'Delete Placement',
            'description'         => 'Delete an ad placement.',
            'category'            => 'site',
            'input_schema'        => array(
                'type'                 => 'object',
                'required'             => array( 'id' ),
                'properties'           => array(
                    'id' => array(
                        'type'        => 'integer',
                        'description' => 'Placement ID to delete.',
                    ),
                ),
                'additionalProperties' => false,
            ),
            'output_schema'       => array(
                'type'       => 'object',
                'properties' => array(
                    'success' => array( 'type' => 'boolean' ),
                    'message' => array( 'type' => 'string' ),
                ),
            ),
            'execute_callback'    => function ( array $input ): array {
                if ( $error = mcp_advads_require_active() ) {
                    return $error;
                }

                $post_id = (int) $input['id'];

                $post = get_post( $post_id );
                if ( ! $post || 'advanced_ads_plcmnt' !== $post->post_type ) {
                    return array( 'success' => false, 'message' => 'Placement not found.' );
                }

                $result = wp_delete_post( $post_id, true );
                if ( ! $result ) {
                    return array( 'success' => false, 'message' => 'Failed to delete placement.' );
                }

                return array( 'success' => true, 'message' => 'Placement deleted.' );
            },
            'permission_callback' => 'mcp_advads_permission_callback',
        )
    );

    wp_register_ability(
        'advads/list-groups',
        array(
            'label'               => 'List Ad Groups',
            'description'         => 'List all ad groups.',
            'category'            => 'site',
            'input_schema'        => array(
                'type' => 'object',
            ),
            'output_schema'       => array(
                'type'       => 'object',
                'properties' => array(
                    'success' => array( 'type' => 'boolean' ),
                    'groups'  => array( 'type' => 'array' ),
                    'total'   => array( 'type' => 'integer' ),
                ),
            ),
            'execute_callback'    => function ( array $input = array() ): array {
                if ( $error = mcp_advads_require_active() ) {
                    return $error;
                }

                $terms = get_terms( array(
                    'taxonomy'   => 'advanced_ads_groups',
                    'hide_empty' => false,
                ) );

                if ( is_wp_error( $terms ) ) {
                    return array( 'success' => true, 'groups' => array(), 'total' => 0 );
                }

                $group_options = get_option( 'advads-ad-groups', array() );
                $groups = array();

                foreach ( $terms as $term ) {
                    $groups[] = array(
                        'id'      => $term->term_id,
                        'name'    => $term->name,
                        'slug'    => $term->slug,
                        'count'   => $term->count,
                        'options' => $group_options[ $term->term_id ] ?? array(),
                    );
                }

                return array( 'success' => true, 'groups' => $groups, 'total' => count( $groups ) );
            },
            'permission_callback' => 'mcp_advads_permission_callback',
        )
    );

    wp_register_ability(
        'advads/get-settings',
        array(
            'label'               => 'Get Settings',
            'description'         => 'Get Advanced Ads settings.',
            'category'            => 'site',
            'input_schema'        => array(
                'type' => 'object',
            ),
            'output_schema'       => array(
                'type'       => 'object',
                'properties' => array(
                    'success'  => array( 'type' => 'boolean' ),
                    'settings' => array( 'type' => 'object' ),
                ),
            ),
            'execute_callback'    => function ( array $input = array() ): array {
                if ( $error = mcp_advads_require_active() ) {
                    return $error;
                }

                return array(
                    'success'  => true,
                    'settings' => array(
                        'general' => get_option( 'advanced-ads', array() ),
                        'adsense' => get_option( 'advanced-ads-adsense', array() ),
                        'privacy' => get_option( 'advanced-ads-privacy', array() ),
                    ),
                );
            },
            'permission_callback' => 'mcp_advads_permission_callback',
        )
    );

    wp_register_ability(
        'advads/update-settings',
        array(
            'label'               => 'Update Settings',
            'description'         => 'Update Advanced Ads settings.',
            'category'            => 'site',
            'input_schema'        => array(
                'type'                 => 'object',
                'properties'           => array(
                    'general' => array( 'type' => 'object', 'description' => 'General settings to merge.' ),
                    'adsense' => array( 'type' => 'object', 'description' => 'AdSense settings to merge.' ),
                    'privacy' => array( 'type' => 'object', 'description' => 'Privacy settings to merge.' ),
                ),
                'additionalProperties' => false,
            ),
            'output_schema'       => array(
                'type'       => 'object',
                'properties' => array(
                    'success' => array( 'type' => 'boolean' ),
                    'updated' => array( 'type' => 'array' ),
                    'message' => array( 'type' => 'string' ),
                ),
            ),
            'execute_callback'    => function ( array $input ): array {
                if ( $error = mcp_advads_require_active() ) {
                    return $error;
                }

                $updated = array();
                $option_map = array(
                    'general' => 'advanced-ads',
                    'adsense' => 'advanced-ads-adsense',
                    'privacy' => 'advanced-ads-privacy',
                );

                foreach ( $option_map as $key => $option_name ) {
                    if ( isset( $input[ $key ] ) && is_array( $input[ $key ] ) ) {
                        $existing = get_option( $option_name, array() );
                        update_option( $option_name, array_merge( $existing, $input[ $key ] ) );
                        $updated[] = $key;
                    }
                }

                return array( 'success' => true, 'updated' => $updated, 'message' => 'Settings updated.' );
            },
            'permission_callback' => 'mcp_advads_permission_callback',
        )
    );

    wp_register_ability(
        'advads/diagnose',
        array(
            'label'               => 'Diagnose Issues',
            'description'         => 'Check for common Advanced Ads configuration issues.',
            'category'            => 'site',
            'input_schema'        => array(
                'type' => 'object',
            ),
            'output_schema'       => array(
                'type'       => 'object',
                'properties' => array(
                    'success' => array( 'type' => 'boolean' ),
                    'healthy' => array( 'type' => 'boolean' ),
                    'issues'  => array( 'type' => 'array' ),
                    'info'    => array( 'type' => 'object' ),
                ),
            ),
            'execute_callback'    => function ( array $input = array() ): array {
                if ( $error = mcp_advads_require_active() ) {
                    return $error;
                }

                $issues = array();
                $adsense = get_option( 'advanced-ads-adsense', array() );
                $ads = get_posts( array(
                    'post_type'      => 'advanced_ads',
                    'post_status'    => 'publish',
                    'posts_per_page' => -1,
                ) );
                $placements = get_posts( array(
                    'post_type'      => 'advanced_ads_plcmnt',
                    'post_status'    => 'publish',
                    'posts_per_page' => -1,
                ) );

                if ( empty( $adsense['adsense-id'] ) ) {
                    $issues[] = 'AdSense Publisher ID not configured';
                }

                if ( 0 === count( $ads ) ) {
                    $issues[] = 'No published ads found';
                }

                $auto_ads_enabled = ! empty( $adsense['page-level-enabled'] );

                $info = array(
                    'adsense_id'      => $adsense['adsense-id'] ?? 'Not set',
                    'auto_ads'        => $auto_ads_enabled ? 'Enabled' : 'Disabled',
                    'placement_count' => count( $placements ),
                    'published_ads'   => count( $ads ),
                    'version'         => defined( 'ADVADS_VERSION' ) ? ADVADS_VERSION : 'unknown',
                );

                return array(
                    'success' => true,
                    'healthy' => empty( $issues ),
                    'issues'  => $issues,
                    'info'    => $info,
                );
            },
            'permission_callback' => 'mcp_advads_permission_callback',
        )
    );
}
add_action( 'wp_abilities_api_init', 'mcp_register_advads_abilities' );
