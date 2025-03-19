<?php
/**
 * Outbox Controller file.
 *
 * @package Activitypub
 */

namespace Activitypub\Rest;

use Activitypub\Collection\Actors;
use Activitypub\Collection\Outbox;
use function Activitypub\get_masked_wp_version;
use function ActivityPub\get_rest_url_by_path;

/**
 * ActivityPub Outbox Controller.
 *
 * @author Matthias Pfefferle
 *
 * @see https://www.w3.org/TR/activitypub/#outbox
 */
class Outbox_Controller extends \WP_REST_Controller {
	/**
	 * The namespace of this controller's route.
	 *
	 * @var string
	 */
	protected $namespace = ACTIVITYPUB_REST_NAMESPACE;

	/**
	 * The base of this controller's route.
	 *
	 * @var string
	 */
	protected $rest_base = '(?:users|actors)/(?P<user_id>[\w\-\.]+)/outbox';

	/**
	 * Register routes.
	 */
	public function register_routes() {
		\register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				'args'   => array(
					'user_id' => array(
						'description'       => 'The ID of the user or actor.',
						'type'              => 'string',
						'validate_callback' => array( $this, 'validate_user_id' ),
					),
				),
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( 'Activitypub\Rest\Server', 'verify_signature' ),
					'args'                => array(
						'page'     => array(
							'description' => 'Current page of the collection.',
							'type'        => 'integer',
							'default'     => 1,
							'minimum'     => 1,
						),
						'per_page' => array(
							'description' => 'Maximum number of items to be returned in result set.',
							'type'        => 'integer',
							'default'     => 10,
							'minimum'     => 1,
							'maximum'     => 100,
						),
					),
				),
				'schema' => array( $this, 'get_collection_schema' ),
			)
		);
	}

	/**
	 * Validates the user_id parameter.
	 *
	 * @param mixed $user_id The user_id parameter.
	 * @return bool|\WP_Error True if the user_id is valid, WP_Error otherwise.
	 */
	public function validate_user_id( $user_id ) {
		$user = Actors::get_by_various( $user_id );
		if ( \is_wp_error( $user ) ) {
			return $user;
		}

		return true;
	}

	/**
	 * Retrieves a collection of outbox items.
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 * @return \WP_REST_Response|\WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_items( $request ) {
		$user_id = $request->get_param( 'user_id' );
		$page    = $request->get_param( 'page' );
		$user    = Actors::get_by_various( $user_id );

		/**
		 * Action triggered prior to the ActivityPub profile being created and sent to the client.
		 *
		 * @param \WP_REST_Request $request The request object.
		 */
		\do_action( 'activitypub_rest_outbox_pre', $request );

		/**
		 * Filters the list of activity types to include in the outbox.
		 *
		 * @param string[] $activity_types The list of activity types.
		 */
		$activity_types = apply_filters( 'rest_activitypub_outbox_activity_types', array( 'Announce', 'Create', 'Like', 'Update' ) );

		$args = array(
			'posts_per_page' => $request->get_param( 'per_page' ),
			'author'         => $user_id > 0 ? $user_id : null,
			'paged'          => $page,
			'post_type'      => Outbox::POST_TYPE,
			'post_status'    => 'any',

			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			'meta_query'     => array(
				array(
					'key'   => '_activitypub_activity_actor',
					'value' => Actors::get_type_by_id( $user_id ),
				),
			),
		);

		if ( get_current_user_id() !== $user_id && ! current_user_can( 'activitypub' ) ) {
			$args['meta_query'][] = array(
				'key'     => '_activitypub_activity_type',
				'value'   => $activity_types,
				'compare' => 'IN',
			);

			$args['meta_query'][] = array(
				'relation' => 'OR',
				array(
					'key'     => 'activitypub_content_visibility',
					'compare' => 'NOT EXISTS',
				),
				array(
					'key'   => 'activitypub_content_visibility',
					'value' => ACTIVITYPUB_CONTENT_VISIBILITY_PUBLIC,
				),
			);
		}

		/**
		 * Filters WP_Query arguments when querying Outbox items via the REST API.
		 *
		 * Enables adding extra arguments or setting defaults for an outbox collection request.
		 *
		 * @param array            $args    Array of arguments for WP_Query.
		 * @param \WP_REST_Request $request The REST API request.
		 */
		$args = apply_filters( 'rest_activitypub_outbox_query', $args, $request );

		$outbox_query = new \WP_Query();
		$query_result = $outbox_query->query( $args );

		$response = array(
			'@context'     => array( 'https://www.w3.org/ns/activitystreams' ),
			'id'           => get_rest_url_by_path( sprintf( 'actors/%d/outbox', $user_id ) ),
			'generator'    => 'https://wordpress.org/?v=' . get_masked_wp_version(),
			'actor'        => $user->get_id(),
			'type'         => 'OrderedCollectionPage',
			'partOf'       => get_rest_url_by_path( sprintf( 'actors/%d/outbox', $user_id ) ),
			'totalItems'   => $outbox_query->found_posts,
			'orderedItems' => array(),
		);

		\update_postmeta_cache( \wp_list_pluck( $query_result, 'ID' ) );
		foreach ( $query_result as $outbox_item ) {
			$response['orderedItems'][] = $this->prepare_item_for_response( $outbox_item, $request );
		}

		$max_pages         = \ceil( $response['totalItems'] / $request->get_param( 'per_page' ) );
		$response['first'] = \add_query_arg( 'page', 1, $response['partOf'] );
		$response['last']  = \add_query_arg( 'page', \max( $max_pages, 1 ), $response['partOf'] );

		if ( $max_pages > $page ) {
			$response['next'] = \add_query_arg( 'page', $page + 1, $response['partOf'] );
		}

		if ( $page > 1 ) {
			$response['prev'] = \add_query_arg( 'page', $page - 1, $response['partOf'] );
		}

		/**
		 * Filter the ActivityPub outbox array.
		 *
		 * @param array $response The ActivityPub outbox array.
		 * @param \WP_REST_Request $request The request object.
		 */
		$response = \apply_filters( 'activitypub_rest_outbox_array', $response, $request );

		/**
		 * Action triggered after the ActivityPub profile has been created and sent to the client.
		 *
		 * @param \WP_REST_Request $request The request object.
		 */
		\do_action( 'activitypub_outbox_post', $request );

		$response = \rest_ensure_response( $response );
		$response->header( 'Content-Type', 'application/activity+json; charset=' . \get_option( 'blog_charset' ) );

		return $response;
	}

	/**
	 * Prepares the item for the REST response.
	 *
	 * @param mixed            $item    WordPress representation of the item.
	 * @param \WP_REST_Request $request Request object.
	 * @return array Response object on success, or WP_Error object on failure.
	 */
	public function prepare_item_for_response( $item, $request ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		$activity = Outbox::get_activity( $item->ID );

		return $activity->to_array( false );
	}

	/**
	 * Retrieves the outbox schema, conforming to JSON Schema.
	 *
	 * @return array Collection schema data.
	 */
	public function get_collection_schema() {
		if ( $this->schema ) {
			return $this->add_additional_fields_schema( $this->schema );
		}

		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'outbox',
			'type'       => 'object',
			'properties' => array(
				'@context'     => array(
					'description' => 'The JSON-LD context for the collection.',
					'type'        => array( 'string', 'array', 'object' ),
					'required'    => true,
				),
				'id'           => array(
					'description' => 'The unique identifier for the collection.',
					'type'        => 'string',
					'format'      => 'uri',
					'required'    => true,
				),
				'type'         => array(
					'description' => 'The type of the collection.',
					'type'        => 'string',
					'enum'        => array( 'OrderedCollection', 'OrderedCollectionPage' ),
					'required'    => true,
				),
				'actor'        => array(
					'description' => 'The actor who owns this outbox.',
					'type'        => 'string',
					'format'      => 'uri',
					'required'    => true,
				),
				'totalItems'   => array(
					'description' => 'The total number of items in the collection.',
					'type'        => 'integer',
					'minimum'     => 0,
					'required'    => true,
				),
				'orderedItems' => array(
					'description' => 'The items in the collection.',
					'type'        => 'array',
					'items'       => array(
						'type' => 'object',
					),
					'required'    => true,
				),
				'first'        => array(
					'description' => 'The first page of the collection.',
					'type'        => 'string',
					'format'      => 'uri',
				),
				'last'         => array(
					'description' => 'The last page of the collection.',
					'type'        => 'string',
					'format'      => 'uri',
				),
				'next'         => array(
					'description' => 'The next page of the collection.',
					'type'        => 'string',
					'format'      => 'uri',
				),
				'prev'         => array(
					'description' => 'The previous page of the collection.',
					'type'        => 'string',
					'format'      => 'uri',
				),
			),
		);

		$this->schema = $schema;

		return $this->add_additional_fields_schema( $this->schema );
	}
}
