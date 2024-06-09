<?php
/**
 * Defines the endpoints for the plugin.
 */

namespace BulkTermGenerator;

use WP_REST_Request;
use WP_REST_Response;

class Endpoints {

	/**
	 * Registers the REST routes for the plugin.
	 *
	 * @return void
	 */
	public function register_rest_routes() {
		register_rest_route(
			'bulk-term-generator/v1',
			'/job-status',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_job_status' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_categories' );
				},
			)
		);

		// endpoint for getting taxonomies
		register_rest_route(
			'bulk-term-generator/v1',
			'/taxonomies',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_taxonomies' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_categories' );
				},
			)
		);

		// endpoint for getting terms for a specific taxonomy
		register_rest_route(
			'bulk-term-generator/v1',
			'/taxonomy/(?P<taxonomy>[a-zA-Z0-9_-]+)',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_terms' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_categories' );
				},
			)
		);

		// endpoint for deleting terms for a specific taxonomy
		register_rest_route(
			'bulk-term-generator/v1',
			'/terms/(?P<taxonomy>[a-zA-Z0-9_-]+)/(?P<term_id>\d+)',
			array(
				'methods'             => 'DELETE',
				'callback'            => array( $this, 'delete_term' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_categories' );
				},
			)
		);

		//endpoint for adding terms for a specific taxonomy
		register_rest_route(
			'bulk-term-generator/v1',
			'/terms/(?P<taxonomy>[a-zA-Z0-9_-]+)',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'add_terms' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_categories' );
				},
			)
		);
	}

	/**
	 * Gets the status of a job.
	 *
	 * @param WP_REST_Request $request The REST request.
	 *
	 * @return WP_REST_Response
	 */
	public function get_job_status( WP_REST_Request $request ): WP_REST_Response {
		$job_id = $request->get_param( 'job_id' );
		$job    = get_transient( "bulk_term_job_$job_id" );

		if ( ! $job ) {
			return new WP_REST_Response( array( 'status' => 'not_found' ), 404 );
		}

		return new WP_REST_Response( $job, 200 );
	}

	/**
	 * Returns the available taxonomies.
	 *
	 * @return WP_REST_Response
	 */
	public function get_taxonomies(): WP_REST_Response {
		$taxonomies = get_taxonomies( array(), 'objects' );

		return new WP_REST_Response( $taxonomies, 200 );
	}

	/**
	 * Returns all of the terms for a specific taxonomy.
	 *
	 * @param WP_REST_Request $request The REST request.
	 *
	 * @return WP_REST_Response
	 */
	public function get_terms( WP_REST_Request $request ): WP_REST_Response {
		$taxonomy = $request->get_param( 'taxonomy' );
		$terms    = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
			)
		);

		$response = new WP_REST_Response( $terms, 200 );

		$response->header( 'X-Hierarchical', is_taxonomy_hierarchical( $taxonomy ) ? 'true' : 'false' );

		return $response;
	}

	/**
	 * Creates terms for a specific taxonomy.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response
	 */
	public function add_terms( WP_REST_Request $request ): WP_REST_Response {
		$terms    = $request->get_param( 'terms' );
		$taxonomy = $request->get_param( 'taxonomy' );

		$processed_terms = array();
		$errors          = array();
		$id_map          = array();

		foreach ( $terms as $term ) {
			if ( ! empty( $term['parent'] ) && isset( $id_map[ $term['parent'] ] ) ) {
				$term['parent'] = $id_map[ $term['parent'] ];
			}

			$args = array(
				'description' => $term['description'] ?? '',
				'slug'        => $term['slug'] ? $term['slug'] : sanitize_title( $term['name'] ),
				'parent'      => $term['parent'],
			);

			$term_object = wp_insert_term( $term['name'], $taxonomy, $args );

			if ( is_wp_error( $term_object ) ) {
				$errors[] = $term_object->get_error_code();
			} else {
				$term_data = get_term( $term_object['term_id'], $taxonomy );

				$processed_terms[]          = array(
					'old_id'      => $term['term_id'],
					'term_id'     => $term_object['term_id'],
					'name'        => $term_data->name,
					'slug'        => $term_data->slug,
					'description' => $term_data->description,
					'parent'      => $term_data->parent,
				);
				$id_map[ $term['term_id'] ] = $term_object['term_id'];
			}
		}

		if ( ! empty( $errors ) ) {
			return new WP_REST_Response( array( 'errors' => $errors ), 400 );
		}

		return new WP_REST_Response( array( 'processed' => $processed_terms ), 200 );
	}

	/**
	 * Deletes a term and all its child terms for a specific taxonomy.
	 *
	 * @param WP_REST_Request $request The REST request.
	 *
	 * @return WP_REST_Response
	 */
	public function delete_term( WP_REST_Request $request ): WP_REST_Response {
		$taxonomy = $request->get_param( 'taxonomy' );
		$term_id  = $request->get_param( 'term_id' );

		// Recursive function to delete a term and its children.
		function delete_term_with_children( $term_id, $taxonomy ) {
			$children = get_term_children( $term_id, $taxonomy );

			if ( ! empty( $children ) ) {
				foreach ( $children as $child_id ) {
					delete_term_with_children( $child_id, $taxonomy ); // Recursively delete child terms
				}
			}

			wp_delete_term( $term_id, $taxonomy );
		}

		delete_term_with_children( $term_id, $taxonomy );

		return new WP_REST_Response( array( 'status' => 'success' ), 200 );
	}

}
