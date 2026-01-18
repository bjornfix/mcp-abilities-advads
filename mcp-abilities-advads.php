<?php
/**
 * Plugin Name: MCP Abilities - Advanced Ads
 * Plugin URI: https://github.com/bjornfix/mcp-abilities-advads
 * Description: MCP abilities for Advanced Ads. Manage ads, placements, groups, and settings programmatically.
 * Version: 1.0.2
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

function mcp_advads_normalize_status( string $status ): string {
    $status  = sanitize_key( $status );
    $allowed = array( 'publish', 'draft', 'pending', 'private', 'future', 'any' );
    if ( in_array( $status, $allowed, true ) ) {
        return $status;
    }
    return 'any';
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

                $status = isset( $input['status'] ) ? mcp_advads_normalize_status( $input['status'] ) : 'any';
                $posts = get_posts( array(
                    'post_type'              => 'advanced_ads',
                    'post_status'            => $status,
                    'posts_per_page'         => 100,
                    'no_found_rows'          => true,
                    'update_post_term_cache' => false,
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
        'advads/create-ad',
        array(
            'label'               => 'Create Ad',
            'description'         => 'Create a new Advanced Ads advertisement.',
            'category'            => 'site',
            'input_schema'        => array(
                'type'                 => 'object',
                'required'             => array( 'title', 'content' ),
                'properties'           => array(
                    'title'   => array(
                        'type'        => 'string',
                        'description' => 'Ad title.',
                    ),
                    'content' => array(
                        'type'        => 'string',
                        'description' => 'Ad content (HTML).',
                    ),
                    'status'  => array(
                        'type'        => 'string',
                        'default'     => 'publish',
                        'description' => 'Post status (publish, draft).',
                    ),
                    'type'    => array(
                        'type'        => 'string',
                        'description' => 'Ad type (plain, image, adsense, etc.).',
                    ),
                    'options' => array(
                        'type'        => 'object',
                        'description' => 'Advanced Ads options array.',
                    ),
                ),
                'additionalProperties' => false,
            ),
            'output_schema'       => array(
                'type'       => 'object',
                'properties' => array(
                    'success' => array( 'type' => 'boolean' ),
                    'id'      => array( 'type' => 'integer' ),
                    'message' => array( 'type' => 'string' ),
                ),
            ),
            'execute_callback'    => function ( array $input ): array {
                if ( $error = mcp_advads_require_active() ) {
                    return $error;
                }

                $title   = sanitize_text_field( $input['title'] ?? '' );
                $content = wp_kses_post( $input['content'] ?? '' );

                if ( empty( $title ) || empty( $content ) ) {
                    return array( 'success' => false, 'message' => 'title and content are required.' );
                }

                $post_id = wp_insert_post( array(
                    'post_type'    => 'advanced_ads',
                    'post_title'   => $title,
                    'post_content' => $content,
                    'post_status'  => sanitize_key( $input['status'] ?? 'publish' ),
                ) );

                if ( is_wp_error( $post_id ) ) {
                    return array( 'success' => false, 'message' => $post_id->get_error_message() );
                }

                $options = array();
                if ( ! empty( $input['options'] ) && is_array( $input['options'] ) ) {
                    $options = $input['options'];
                }
                if ( ! empty( $input['type'] ) ) {
                    $options['type'] = sanitize_text_field( $input['type'] );
                }

                if ( ! empty( $options ) ) {
                    update_post_meta( $post_id, 'advanced_ads_ad_options', $options );
                }

                return array( 'success' => true, 'id' => $post_id, 'message' => 'Ad created.' );
            },
            'permission_callback' => 'mcp_advads_permission_callback',
        )
    );

    wp_register_ability(
        'advads/update-ad',
        array(
            'label'               => 'Update Ad',
            'description'         => 'Update an Advanced Ads advertisement.',
            'category'            => 'site',
            'input_schema'        => array(
                'type'                 => 'object',
                'required'             => array( 'id' ),
                'properties'           => array(
                    'id'              => array(
                        'type'        => 'integer',
                        'description' => 'Ad ID.',
                    ),
                    'title'           => array( 'type' => 'string' ),
                    'content'         => array( 'type' => 'string' ),
                    'status'          => array( 'type' => 'string' ),
                    'type'            => array( 'type' => 'string' ),
                    'options'         => array( 'type' => 'object' ),
                    'replace_options' => array(
                        'type'        => 'boolean',
                        'default'     => false,
                        'description' => 'Replace options instead of merge.',
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

                $post_id = (int) ( $input['id'] ?? 0 );
                $post = get_post( $post_id );
                if ( ! $post || 'advanced_ads' !== $post->post_type ) {
                    return array( 'success' => false, 'message' => 'Ad not found.' );
                }

                $update = array( 'ID' => $post_id );
                if ( isset( $input['title'] ) ) {
                    $update['post_title'] = sanitize_text_field( $input['title'] );
                }
                if ( isset( $input['content'] ) ) {
                    $update['post_content'] = wp_kses_post( $input['content'] );
                }
                if ( isset( $input['status'] ) ) {
                    $update['post_status'] = sanitize_key( $input['status'] );
                }

                if ( count( $update ) > 1 ) {
                    $result = wp_update_post( $update, true );
                    if ( is_wp_error( $result ) ) {
                        return array( 'success' => false, 'message' => $result->get_error_message() );
                    }
                }

                if ( isset( $input['options'] ) && is_array( $input['options'] ) ) {
                    $options = $input['options'];
                    if ( ! empty( $input['type'] ) ) {
                        $options['type'] = sanitize_text_field( $input['type'] );
                    }

                    if ( ! empty( $input['replace_options'] ) ) {
                        update_post_meta( $post_id, 'advanced_ads_ad_options', $options );
                    } else {
                        $existing = get_post_meta( $post_id, 'advanced_ads_ad_options', true );
                        if ( ! is_array( $existing ) ) {
                            $existing = array();
                        }
                        update_post_meta( $post_id, 'advanced_ads_ad_options', array_merge( $existing, $options ) );
                    }
                } elseif ( ! empty( $input['type'] ) ) {
                    $existing = get_post_meta( $post_id, 'advanced_ads_ad_options', true );
                    if ( ! is_array( $existing ) ) {
                        $existing = array();
                    }
                    $existing['type'] = sanitize_text_field( $input['type'] );
                    update_post_meta( $post_id, 'advanced_ads_ad_options', $existing );
                }

                return array( 'success' => true, 'message' => 'Ad updated.' );
            },
            'permission_callback' => 'mcp_advads_permission_callback',
        )
    );

    wp_register_ability(
        'advads/delete-ad',
        array(
            'label'               => 'Delete Ad',
            'description'         => 'Delete an Advanced Ads advertisement.',
            'category'            => 'site',
            'input_schema'        => array(
                'type'                 => 'object',
                'required'             => array( 'id' ),
                'properties'           => array(
                    'id'    => array( 'type' => 'integer', 'description' => 'Ad ID.' ),
                    'force' => array( 'type' => 'boolean', 'default' => true ),
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

                $post_id = (int) ( $input['id'] ?? 0 );
                $post = get_post( $post_id );
                if ( ! $post || 'advanced_ads' !== $post->post_type ) {
                    return array( 'success' => false, 'message' => 'Ad not found.' );
                }

                $result = wp_delete_post( $post_id, ! empty( $input['force'] ) );
                if ( ! $result ) {
                    return array( 'success' => false, 'message' => 'Failed to delete ad.' );
                }

                return array( 'success' => true, 'message' => 'Ad deleted.' );
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
                    'post_type'              => 'advanced_ads_plcmnt',
                    'post_status'            => 'any',
                    'posts_per_page'         => 100,
                    'no_found_rows'          => true,
                    'update_post_term_cache' => false,
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
        'advads/get-placement',
        array(
            'label'               => 'Get Placement',
            'description'         => 'Get an ad placement by ID.',
            'category'            => 'site',
            'input_schema'        => array(
                'type'                 => 'object',
                'required'             => array( 'id' ),
                'properties'           => array(
                    'id' => array( 'type' => 'integer', 'description' => 'Placement ID.' ),
                ),
                'additionalProperties' => false,
            ),
            'output_schema'       => array(
                'type'       => 'object',
                'properties' => array(
                    'success'   => array( 'type' => 'boolean' ),
                    'placement' => array( 'type' => 'object' ),
                    'message'   => array( 'type' => 'string' ),
                ),
            ),
            'execute_callback'    => function ( array $input ): array {
                if ( $error = mcp_advads_require_active() ) {
                    return $error;
                }

                $post_id = (int) ( $input['id'] ?? 0 );
                $post = get_post( $post_id );
                if ( ! $post || 'advanced_ads_plcmnt' !== $post->post_type ) {
                    return array( 'success' => false, 'message' => 'Placement not found.' );
                }

                return array(
                    'success'   => true,
                    'placement' => array(
                        'id'      => $post->ID,
                        'slug'    => $post->post_name,
                        'name'    => $post->post_title,
                        'status'  => $post->post_status,
                        'type'    => get_post_meta( $post->ID, 'type', true ),
                        'item'    => get_post_meta( $post->ID, 'item', true ),
                        'options' => get_post_meta( $post->ID, 'options', true ),
                    ),
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
                $existing = get_page_by_path( $slug, OBJECT, 'advanced_ads_plcmnt' );
                if ( $existing ) {
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
        'advads/update-placement',
        array(
            'label'               => 'Update Placement',
            'description'         => 'Update an ad placement.',
            'category'            => 'site',
            'input_schema'        => array(
                'type'                 => 'object',
                'required'             => array( 'id' ),
                'properties'           => array(
                    'id'      => array( 'type' => 'integer', 'description' => 'Placement ID.' ),
                    'slug'    => array( 'type' => 'string' ),
                    'name'    => array( 'type' => 'string' ),
                    'status'  => array( 'type' => 'string' ),
                    'type'    => array( 'type' => 'string' ),
                    'item'    => array( 'type' => 'string' ),
                    'options' => array( 'type' => 'object' ),
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

                $post_id = (int) ( $input['id'] ?? 0 );
                $post = get_post( $post_id );
                if ( ! $post || 'advanced_ads_plcmnt' !== $post->post_type ) {
                    return array( 'success' => false, 'message' => 'Placement not found.' );
                }

                $update = array( 'ID' => $post_id );
                if ( isset( $input['name'] ) ) {
                    $update['post_title'] = sanitize_text_field( $input['name'] );
                }
                if ( isset( $input['slug'] ) ) {
                    $update['post_name'] = sanitize_title( $input['slug'] );
                }
                if ( isset( $input['status'] ) ) {
                    $update['post_status'] = sanitize_key( $input['status'] );
                }

                if ( count( $update ) > 1 ) {
                    $result = wp_update_post( $update, true );
                    if ( is_wp_error( $result ) ) {
                        return array( 'success' => false, 'message' => $result->get_error_message() );
                    }
                }

                if ( isset( $input['type'] ) ) {
                    update_post_meta( $post_id, 'type', sanitize_text_field( $input['type'] ) );
                }
                if ( isset( $input['item'] ) ) {
                    update_post_meta( $post_id, 'item', sanitize_text_field( $input['item'] ) );
                }
                if ( isset( $input['options'] ) && is_array( $input['options'] ) ) {
                    update_post_meta( $post_id, 'options', $input['options'] );
                }

                return array( 'success' => true, 'message' => 'Placement updated.' );
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
        'advads/create-group',
        array(
            'label'               => 'Create Ad Group',
            'description'         => 'Create a new ad group.',
            'category'            => 'site',
            'input_schema'        => array(
                'type'                 => 'object',
                'required'             => array( 'name' ),
                'properties'           => array(
                    'name'    => array( 'type' => 'string', 'description' => 'Group name.' ),
                    'slug'    => array( 'type' => 'string', 'description' => 'Group slug.' ),
                    'options' => array( 'type' => 'object', 'description' => 'Group options.' ),
                ),
                'additionalProperties' => false,
            ),
            'output_schema'       => array(
                'type'       => 'object',
                'properties' => array(
                    'success' => array( 'type' => 'boolean' ),
                    'id'      => array( 'type' => 'integer' ),
                    'message' => array( 'type' => 'string' ),
                ),
            ),
            'execute_callback'    => function ( array $input ): array {
                if ( $error = mcp_advads_require_active() ) {
                    return $error;
                }

                $name = sanitize_text_field( $input['name'] ?? '' );
                if ( empty( $name ) ) {
                    return array( 'success' => false, 'message' => 'name is required.' );
                }

                $args = array();
                if ( ! empty( $input['slug'] ) ) {
                    $args['slug'] = sanitize_title( $input['slug'] );
                }

                $term = wp_insert_term( $name, 'advanced_ads_groups', $args );
                if ( is_wp_error( $term ) ) {
                    return array( 'success' => false, 'message' => $term->get_error_message() );
                }

                if ( ! empty( $input['options'] ) && is_array( $input['options'] ) ) {
                    $group_options = get_option( 'advads-ad-groups', array() );
                    $group_options[ $term['term_id'] ] = $input['options'];
                    update_option( 'advads-ad-groups', $group_options );
                }

                return array( 'success' => true, 'id' => $term['term_id'], 'message' => 'Group created.' );
            },
            'permission_callback' => 'mcp_advads_permission_callback',
        )
    );

    wp_register_ability(
        'advads/update-group',
        array(
            'label'               => 'Update Ad Group',
            'description'         => 'Update an ad group.',
            'category'            => 'site',
            'input_schema'        => array(
                'type'                 => 'object',
                'required'             => array( 'id' ),
                'properties'           => array(
                    'id'      => array( 'type' => 'integer', 'description' => 'Group ID.' ),
                    'name'    => array( 'type' => 'string', 'description' => 'Group name.' ),
                    'slug'    => array( 'type' => 'string', 'description' => 'Group slug.' ),
                    'options' => array( 'type' => 'object', 'description' => 'Group options.' ),
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

                $term_id = (int) ( $input['id'] ?? 0 );
                if ( $term_id <= 0 ) {
                    return array( 'success' => false, 'message' => 'id is required.' );
                }

                $args = array();
                if ( isset( $input['name'] ) ) {
                    $args['name'] = sanitize_text_field( $input['name'] );
                }
                if ( isset( $input['slug'] ) ) {
                    $args['slug'] = sanitize_title( $input['slug'] );
                }

                if ( ! empty( $args ) ) {
                    $result = wp_update_term( $term_id, 'advanced_ads_groups', $args );
                    if ( is_wp_error( $result ) ) {
                        return array( 'success' => false, 'message' => $result->get_error_message() );
                    }
                }

                if ( isset( $input['options'] ) && is_array( $input['options'] ) ) {
                    $group_options = get_option( 'advads-ad-groups', array() );
                    $group_options[ $term_id ] = $input['options'];
                    update_option( 'advads-ad-groups', $group_options );
                }

                return array( 'success' => true, 'message' => 'Group updated.' );
            },
            'permission_callback' => 'mcp_advads_permission_callback',
        )
    );

    wp_register_ability(
        'advads/delete-group',
        array(
            'label'               => 'Delete Ad Group',
            'description'         => 'Delete an ad group.',
            'category'            => 'site',
            'input_schema'        => array(
                'type'                 => 'object',
                'required'             => array( 'id' ),
                'properties'           => array(
                    'id' => array( 'type' => 'integer', 'description' => 'Group ID.' ),
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

                $term_id = (int) ( $input['id'] ?? 0 );
                if ( $term_id <= 0 ) {
                    return array( 'success' => false, 'message' => 'id is required.' );
                }

                $result = wp_delete_term( $term_id, 'advanced_ads_groups' );
                if ( is_wp_error( $result ) ) {
                    return array( 'success' => false, 'message' => $result->get_error_message() );
                }

                $group_options = get_option( 'advads-ad-groups', array() );
                if ( isset( $group_options[ $term_id ] ) ) {
                    unset( $group_options[ $term_id ] );
                    update_option( 'advads-ad-groups', $group_options );
                }

                return array( 'success' => true, 'message' => 'Group deleted.' );
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
                    'post_type'              => 'advanced_ads',
                    'post_status'            => 'publish',
                    'posts_per_page'         => -1,
                    'no_found_rows'          => true,
                    'update_post_term_cache' => false,
                ) );
                $placements = get_posts( array(
                    'post_type'              => 'advanced_ads_plcmnt',
                    'post_status'            => 'publish',
                    'posts_per_page'         => -1,
                    'no_found_rows'          => true,
                    'update_post_term_cache' => false,
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
