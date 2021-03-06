<?php
/**
 * BuddyBoss Performance integration abstract.
 *
 * @package BuddyBoss\Performance
 */

namespace BuddyBoss\Performance\Integration;

use BuddyBoss\Performance\Performance;
use BuddyBoss\Performance\Route_Helper;
use BuddyBoss\Performance\Cache;
use WP_REST_Request;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * This is integration abstract.
 * All Integration written should be extending this abstract.
 * Class Integration_Abstract
 *
 * @package AppBoss\Performance
 */
abstract class Integration_Abstract {

	/**
	 * Class instance.
	 *
	 * @var bool
	 */
	private static $instances = false;

	/**
	 * Cache endpoints.
	 *
	 * @var array
	 */
	public static $cache_endpoints = array();

	/**
	 * Integration Name.
	 *
	 * @var bool
	 */
	private $integration_name = false;

	/**
	 * API cache data.
	 *
	 * @var bool
	 */
	private $api_cache_data = false;

	/**
	 * Generate deep cache.
	 *
	 * @var bool
	 */
	private $generate_deep_cache = false;

	/**
	 * Hold the information for which user ID checked is prepared to render.
	 *
	 * @var bool
	 */
	private $prepared_cached_user_id = false;

	/**
	 * Integration_Abstract constructor.
	 */
	public function __construct() {
		/** Nothing here */
	}

	/**
	 * Class instance called.
	 *
	 * @return mixed
	 */
	public static function instance() {
		$class = get_called_class();
		if ( ! isset( self::$instances[ $class ] ) ) {
			self::$instances[ $class ] = new $class();
			self::$instances[ $class ]->integration_set_up();

		}

		return self::$instances[ $class ];
	}

	/**
	 * Setup method.
	 */
	public function integration_set_up() {
		if ( method_exists( $this, 'set_up' ) ) {
			$this->set_up();
		}

		$this->generate_deep_cache = false;
		$this->prepare_endpoint_cache();

		// Let's check cache is exist or not.
		// phpcs:ignore
		/* add_action( 'init', array( $this, 'prepare_endpoint_cache' ), 99 ); */

		// Let's cache the API Content in real-time.
		add_filter( 'rest_post_dispatch', array( $this, 'do_endpoint_cache' ), 99, 3 );

	}

	/**
	 * Returns the current web location.
	 */
	public function get_current_path() {
		return add_query_arg( null, null );
	}

	/**
	 * Returns the cache key for current endpoint.
	 *
	 * @return string
	 */
	public function get_current_endpoint_cache_key() {
		/**
		 * Get current path.
		 *
		 * @todo
		 * add headers into path key.
		 * order query var as alfa order to keep things clean.
		 */
		return $this->get_current_path();
	}

	/**
	 * Get current endpoint.
	 *
	 * @todo :- research the correct wp way to get current endpoint. I doubt on this.
	 */
	public function get_current_endpoint() {
		$current_path = $this->get_current_path();
		if ( strpos( $current_path, 'wp-json/' ) !== false ) {

			$current_path = explode( 'wp-json/', $current_path );
			$current_path = $current_path[1];

			// remove query vars.
			if ( strpos( $current_path, '?' ) !== false ) {
				$current_path = explode( '?', $current_path );
				$current_path = $current_path[0];
			}

			return trim( $current_path );

		}

		return false;
	}

	/**
	 * Prepare the endpoint cache.
	 */
	public function prepare_endpoint_cache() {

		$user_id = $this->get_loggedin_user_id();

		// Check if we are in WP API.
		if ( strpos( $this->get_current_path(), 'wp-json/' ) !== false ) {

			$current_endpoint = $this->get_current_endpoint();
			if ( ! empty( self::$cache_endpoints[ $this->integration_name ] ) ) {
				// Scan the current register endpoints and cache them.
				foreach ( self::$cache_endpoints[ $this->integration_name ] as $endpoint => $args ) {

					$found_matches = Route_Helper::is_matched_from_route( $endpoint, $current_endpoint );

					// @todo: OLD : if ( $current_endpoint == $endpoint )
					if ( $found_matches ) {
						$param_value = Route_Helper::get_parameter_from_route( $endpoint, $current_endpoint, 'id' );

						if ( $args['deep_cache'] && empty( $param_value ) ) {

							$get_cache = $this->prepare_endpoint_cache_deep( $args, $this->generate_deep_cache );

							if ( false === $get_cache && ! $this->generate_deep_cache ) {
								// As the deep cache need to be generate so we prepare the cache at init level.
								// We are doing this so we can have access to WP Rest Server API.
								$this->generate_deep_cache = true;
								add_action( 'init', array( $this, 'prepare_endpoint_cache' ), 99 );

								return false;
							}
						} else {
							$cache_group = $this->integration_name;
							if ( isset( $param_value ) && ! empty( $param_value ) ) {
								$cache_group = $this->integration_name . '_' . $param_value;
							}
							$get_cache = Cache::instance()->get( $this->get_current_endpoint_cache_key(), $user_id, get_current_blog_id(), $cache_group );
						}

						if ( false !== $get_cache ) {

							/**
							 * Fires when cache is found and initiated for output.
							 */
							do_action( 'rest_cache_response_init', $get_cache );

							$this->api_cache_data = $get_cache;

							/**
							 * Remove WordPress Extra Headaches.
							 */

							// Tell WordPress to Don't Load Theme.
							add_filter(
								'wp_using_themes',
								function ( $wp_use_themes ) {
									$wp_use_themes = false;

									return $wp_use_themes;
								}
							);

							// Disable all plugins for this request as we will fire cache on init hook.
							add_filter(
								'option_active_plugins',
								function ( $plugins ) {
									if ( ! empty( $plugins ) ) {
										foreach ( $plugins as $plugin_key => $plugin_val ) {
											if ( strpos( $plugin_val, 'buddyboss-app.php' ) === false ) {
												unset( $plugins[ $plugin_key ] );
											}
										}
									}

									return $plugins;
								}
							);

							// Disable all plugins for this request as we will fire cache on init hook. Network Mode.
							add_filter(
								'option_active_sitewide_plugins',
								function ( $plugins ) {
									if ( ! empty( $plugins ) ) {
										foreach ( $plugins as $plugin_key => $plugin_val ) {
											if ( strpos( $plugin_val, 'buddyboss-app.php' ) === false ) {
												unset( $plugins[ $plugin_key ] );
											}
										}
									}

									return $plugins;
								}
							);

							// Disable all plugins for this request as we will fire cache on init hook. Network Mode.
							add_filter(
								'site_option_active_sitewide_plugins',
								function ( $plugins ) {
									if ( ! empty( $plugins ) ) {
										foreach ( $plugins as $plugin_key => $plugin_val ) {
											if ( strpos( $plugin_val, 'buddyboss-app.php' ) === false ) {
												unset( $plugins[ $plugin_key ] );
											}
										}
									}

									return $plugins;
								}
							);

							$this->prepared_cached_user_id = $user_id;

							// Output the cache on hook when current user is available by WordPress.
							if ( $this->generate_deep_cache ) {
								$this->endpoint_cache_render();
							} else {
								// phpcs:ignore
								/* add_action( 'set_current_user', array( $this, 'endpoint_cache_render' ), 1 ); */
								add_action( 'init', array( $this, 'endpoint_cache_render' ), 99 );
							}
						}

						break;
					}
				}
			}
		}

	}

	/**
	 * Get Deep cache and prepare response. cache get by items wise.
	 *
	 * @param array $args           Arguments.
	 * @param bool  $generate_cache Generate cache or not.
	 *
	 * @return array|bool
	 */
	private function prepare_endpoint_cache_deep( $args, $generate_cache = false ) {

		$user_id   = $this->get_loggedin_user_id();
		$results   = false;
		$cache_val = Cache::instance()->get( $this->get_current_endpoint_cache_key(), $user_id, get_current_blog_id(), $this->integration_name );

		$include_param = isset( $args['include_param'] ) ? $args['include_param'] : 'include';
		$unique_id     = isset( $args['unique_id'] ) ? $args['unique_id'] : 'id';

		if ( ! empty( $cache_val ) && isset( $cache_val['data'] ) && ! empty( $cache_val['data'] ) ) {
			$results           = array();
			$results['header'] = $cache_val['header'];
			foreach ( $cache_val['data'] as $item_id ) {
				$get_cache = Cache::instance()->get( $this->get_current_endpoint_cache_key(), $user_id, get_current_blog_id(), $this->integration_name . '_' . $item_id );
				if ( false !== $get_cache ) {
					$results['data'][] = $get_cache;
				} else {

					// Check if generate cache can be performance.
					if ( ! $generate_cache ) {
						return false;
					}

					$query_url = $this->get_current_path();
					$query_url = wp_parse_url( $query_url );
					parse_str( $query_url['query'], $query_args );
					$rest_endpoint = $this->get_current_endpoint();

					if ( isset( $rest_endpoint ) ) {

						/**
						 * Fetch Single item data if any single item cache is cleared
						 */
						$request = new WP_REST_Request( 'Get', $rest_endpoint );
						if ( is_array( $unique_id ) ) {
							$args = explode( '_', $item_id );
							$args = array_combine( $unique_id, $args );
							foreach ( $args as $key => $val ) {
								$key = isset( $include_param[ $key ] ) ? $include_param[ $key ] : $key;
								$request->set_param( $key, $val );
							}
						} else {
							$request->set_param( $include_param, $item_id );
						}
						foreach ( $query_args as $key => $val ) {
							$val = ( 'page' === $key ) ? 1 : $val;
							$request->set_param( $key, $val );
						}
						$server = rest_get_server();
						$retval = $server->dispatch( $request );

						if ( 200 !== (int) $retval->status ) {
							/**
							 * Fetch Parent endpoint if single items data not found with fresh request
							 */
							$results = false;
							break;
						}
						if ( ! empty( $retval->data[0] ) ) {
							/**
							 * Set retrieve items response in cache for future use
							 */
							$purge_deep_events = ! empty( $args['purge_deep_events'] ) ? $args['purge_deep_events'] : $args['purge_events'];
							Cache::instance()->set( $this->get_current_endpoint_cache_key(), $retval->data[0], $args['expire'], $purge_deep_events, $args['event_groups'], $this->integration_name . '_' . $item_id );

							$results['data'][] = $retval->data[0];
						}
					} else {
						/**
						 * Fetch Parent endpoint if endpoint is not found correctly
						 */
						$results = false;
						break;
					}
				}
			}
		}

		return $results;
	}

	/**
	 * Cache Endpoint property.
	 *
	 * @param string $endpoint      Endpoint URL.
	 * @param string $property_name Property name.
	 * @param array  $h             Header.
	 */
	public function endpoint_property_cache( $endpoint, $property_name, $h ) {

	}

	/**
	 * This will render the cache on init when cache is prepare.
	 */
	public function endpoint_cache_render() {
		if ( $this->api_cache_data ) {

			// Security Check.
			// When the cache generated to user is not matched with it's being delivered to output error.
			// Here we avoid passing another user cached instead of logged in.
			if ( get_current_user_id() !== (int) $this->prepared_cached_user_id ) {
				header( 'HTTP/1.0 500 Internal Server Error' );
				header( 'Content-Type: application/json' );
				echo wp_json_encode(
					array(
						'code'    => 'cache_invalid_user',
						'message' => __( 'This error is formed by the API cache probably because of miss matched of user auth provider.', 'buddyboss' ),
						'data'    => array(
							'status' => 500,
						),
					)
				);
				exit;
			}

			$header = apply_filters( 'rest_post_dispatch_header_cache', $this->api_cache_data['header'], $this->get_cached_endpoints() );

			$header['bb-api-cache'] = 'hit';
			if ( ! empty( $header ) ) {
				foreach ( $header as $header_key => $header_value ) {
					header( $header_key . ':' . $header_value );
				}
			}

			$this->api_cache_data = apply_filters( 'rest_post_dispatch_cache', $this->api_cache_data['data'] );
			echo wp_json_encode( $this->api_cache_data );
			exit;
		}
	}

	/**
	 * Does endpoint cache here.
	 *
	 * @param WP_HTTP_Response $result  Result to send to the client. Usually a `WP_REST_Response`.
	 * @param WP_REST_Server   $server  Server instance.
	 * @param WP_REST_Request  $request Request used to generate the response.
	 *
	 * @return mixed
	 */
	public function do_endpoint_cache( $result, $server, $request ) {

		$server->send_header( 'bb-api-cache', 'miss' );

		// Check if we are in WP API.
		if ( strpos( $this->get_current_path(), 'wp-json/' ) !== false ) {

			$current_endpoint = $this->get_current_endpoint();

			if ( ! empty( self::$cache_endpoints[ $this->integration_name ] ) ) {
				// Scan the current register endpoints and cache them.
				foreach ( self::$cache_endpoints[ $this->integration_name ] as $endpoint => $args ) {

					/**
					 * As this function also called for embed data so we need to ignore that as embed data added with items response
					 */
					$resultendpoint = $request->get_route();
					if ( '/' . $current_endpoint !== $resultendpoint ) {
						return $result;
					}

					$by_pass_cache = $request->get_header( 'by_pass_cache' );
					$found_matches = Route_Helper::is_matched_from_route( $endpoint, $current_endpoint );

					// @todo OLD : if ( $current_endpoint == $endpoint )
					if ( $found_matches && 1 !== $by_pass_cache && 'GET' === $request->get_method() ) {

						$param_value = Route_Helper::get_parameter_from_route( $endpoint, $current_endpoint, 'id' );

						if ( $args['deep_cache'] && empty( $param_value ) ) {
							$this->do_endpoint_cache_deep( $result, $args, $server );
						} else {
							if ( 200 === $result->status ) {
								$cache_group = $this->integration_name;

								// Prepare Embed links inside the request.
								// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.NonceVerification.Recommended
								$embed       = isset( $_GET['_embed'] ) ? rest_parse_embed_param( $_GET['_embed'] ) : false;
								$result_data = $server->response_to_data( $result, $embed );
								if ( ! empty( $result_data ) ) {
									$cache_val = array(
										'data'   => $result_data,
										'header' => $this->prepare_header( $result ),
									);

									if ( ! empty( $args['unique_id'] ) && is_array( $args['unique_id'] ) ) {
										$data        = array_intersect_key( $result_data, array_flip( $args['unique_id'] ) );
										$data        = array_merge( array_flip( $args['unique_id'] ), $data );
										$cache_group = $cache_group . '_' . implode( '_', $data );
									} elseif ( isset( $param_value ) && ! empty( $param_value ) ) {
										$cache_group = $cache_group . '_' . $param_value;
									}

									Cache::instance()->set( $this->get_current_endpoint_cache_key(), $cache_val, $args['expire'], $args['purge_events'], $args['event_groups'], $cache_group );
								}
							}
						}
						break;
					}
				}
			}
		}

		return $result;

	}

	/**
	 * Store deep cache. cache store by items wise
	 *
	 * @param WP_HTTP_Response $results Result to send to the client. Usually a `WP_REST_Response`.
	 * @param array            $args    Arguments.
	 * @param WP_REST_Server   $server  Server instance.
	 */
	private function do_endpoint_cache_deep( $results, $args, $server ) {
		$unique_key = isset( $args['unique_id'] ) ? $args['unique_id'] : 'id';
		if ( is_array( $unique_key ) ) {
			$item_ids = array_map(
				function ( $el ) use ( $unique_key ) {
					$o = array();
					foreach ( $unique_key as $key ) {
						$o[ $key ] = isset( $el[ $key ] ) ? $el[ $key ] : false;
					}

					return implode( '_', $o );
				},
				$results->data
			);
		} else {
			$item_ids = array_column( $results->data, $unique_key );
		}

		$ids = $item_ids;
		if ( ! empty( $item_ids ) && 200 === $results->status ) {
			$cache_val = array(
				'data'   => $item_ids,
				'header' => $this->prepare_header( $results ),
			);
			Cache::instance()->set( $this->get_current_endpoint_cache_key(), $cache_val, $args['expire'], $args['purge_events'], $args['event_groups'], $this->integration_name );

			// Prepare Embed links inside the request.
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.NonceVerification.Recommended
			$embed       = isset( $_GET['_embed'] ) ? rest_parse_embed_param( $_GET['_embed'] ) : false;
			$result_data = $server->response_to_data( $results, $embed );

			if ( ! empty( $result_data ) ) {
				foreach ( $result_data as $item ) {
					if ( ! empty( $item ) ) {
						$purge_deep_events = ! empty( $args['purge_deep_events'] ) ? $args['purge_deep_events'] : $args['purge_events'];
						if ( is_array( $unique_key ) ) {
							$data       = array_intersect_key( $item, array_flip( $unique_key ) );
							$data       = array_merge( array_flip( $unique_key ), $data );
							$group_name = implode( '_', $data );
						} else {
							$group_name = $item[ $unique_key ];
						}
						Cache::instance()->set( $this->get_current_endpoint_cache_key(), $item, $args['expire'], $purge_deep_events, $args['event_groups'], $this->integration_name . '_' . $group_name );
					}
				}
			}
		}
	}

	/**
	 * Addded Pre Header.
	 *
	 * @param WP_HTTP_Response $results Result to send to the client. Usually a `WP_REST_Response`.
	 *
	 * @return array
	 */
	private function prepare_header( $results ) {
		$headers          = array();
		$disallow_headers = array(
			'appboss-logged-in',
			'ab-unread-notifications',
			'bbp-unread-notifications',
			'Expires',
			'Cache-Control',
		);
		// To add filter for this you need to execute code form mu level.
		$disallow_headers = apply_filters( 'rest_post_disprepare_header_cache', $disallow_headers );
		foreach ( headers_list() as $header ) {
			$header = explode( ':', $header );
			if ( ! in_array( $header[0], $disallow_headers, true ) && is_array( $header ) ) {
				$headers[ $header[0] ] = $header[1];
			}
		}
		$headers = array_merge( $headers, $results->get_headers() );

		return $headers;
	}


	/**
	 * Do all register and initiate from this function when writing extend class.
	 *
	 * @return mixed
	 */
	abstract public function set_up();

	/**
	 * Register Integration Name.
	 *
	 * @param string $integration_name Integration Name.
	 */
	public function register( $integration_name ) {
		$this->integration_name = $integration_name;
	}

	/**
	 * Register API endpoint which needs to be cached.
	 * Note:- Only GET Endpoint can be registered.
	 *
	 * @param string  $endpoint     Endpoints Path without query vars.
	 * @param string  $expire       When should cache be expired, by default endpoint cache will expire in sec from now.
	 * @param string  $purge_events Purge on specific event.
	 * @param string  $event_groups Purge by group.
	 * @param array   $args         Argument passed.
	 * @param boolean $deep_cache   Checked for deep cache.
	 *
	 * @todo : we should add a setting in args which allow it to cache based on headers or not.
	 */
	public function cache_endpoint( $endpoint, $expire, $purge_events = '', $event_groups = '', $args = array(), $deep_cache = false ) {
		$args['expire'] = $expire;

		if ( ! empty( $purge_events ) ) {
			$args['purge_events'] = $purge_events;
		}

		if ( ! empty( $event_groups ) ) {
			$args['event_groups'] = $event_groups;
		}

		$args['deep_cache'] = $deep_cache;

		self::$cache_endpoints[ $this->integration_name ][ $endpoint ] = $args;
	}


	/**
	 * Create cache for endpoint.
	 *
	 * @param string $endpoint      Endpoinyt URL.
	 * @param string $property_name Endpoint property name.
	 * @param string $cache_name    Cache name.
	 * @param array  $args          Array of arguments.
	 */
	public function cache_endpoint_property( $endpoint, $property_name, $cache_name, $args ) {
	}

	/**
	 * Returns the registered endpoint cache.
	 */
	public function get_cached_endpoints() {
		if ( ! is_array( self::$cache_endpoints[ $this->integration_name ] ) ) {
			self::$cache_endpoints[ $this->integration_name ] = array();
		}

		return self::$cache_endpoints[ $this->integration_name ];
	}

	/**
	 * Check if user logged in or not.
	 */
	public function get_loggedin_user_id() {

		if ( Performance::instance()->is_current_user_available() ) {
			return get_current_user_id();
		} else {
			$guessed_user_id = Performance::instance()->get_guessed_user_id();
			if ( ! $guessed_user_id ) {
				return 0;
			}

			return $guessed_user_id;
		}
	}

	/**
	 * This function will be override from child class.
	 * This function will be called by background jobs on cycle terms.
	 *
	 * @param int    $user_id     User ID.
	 * @param object $user_object User Object.
	 */
	public function user_specific_job_cycle( $user_id, $user_object ) {

	}

	/**
	 * This function execute purge event.
	 *
	 * @param string $group_name   group name.
	 * @param array  $purge_events component events hooks.
	 */
	protected function purge_event( $group_name, $purge_events ) {
		if ( ! empty( $purge_events ) ) {
			foreach ( $purge_events as $event ) {
				add_action(
					$event,
					function () use ( $group_name ) {
						Cache::instance()->purge_by_group( $group_name );
					}
				);
			}
		}
	}

	/**
	 * This function execute single purge event.
	 *
	 * @param string $action              action name.
	 * @param array  $purge_single_events component event hooks.
	 */
	protected function purge_single_events( $action, $purge_single_events ) {
		if ( ! empty( $purge_single_events ) ) {
			foreach ( $purge_single_events as $event => $args ) {
				if ( method_exists( $this, 'event_' . str_replace( '-', '_', $event ) ) ) {
					add_action( $event, array( $this, 'event_' . str_replace( '-', '_', $event ) ), 99, $args );
				} else {

					/**
					 * Action for handling purge on custom event.
					 */
					do_action( $action, $event, $args );
				}
			}
		}
	}
}
