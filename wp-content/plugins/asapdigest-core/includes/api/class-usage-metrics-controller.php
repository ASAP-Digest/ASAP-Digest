<?php
/**
 * Usage Metrics & Analytics REST API Controller
 *
 * @package ASAPDigest_Core
 * @created 05.07.25 | 04:00 PM PDT
 * @file-marker Usage_Metrics_Controller
 * @implementation-marker analytics-endpoints
 */

namespace ASAPDigest\API;

if (!defined('ABSPATH')) {
    exit;
}

use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

class Usage_Metrics_Controller {
    /**
     * Register REST API routes for analytics/metrics.
     */
    public function __construct() {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    /**
     * Register the endpoints.
     */
    public function register_routes() {
        register_rest_route('asap/v1', '/usage-metrics', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_usage_metrics'],
            'permission_callback' => [$this, 'check_auth'],
        ]);
        register_rest_route('asap/v1', '/cost-analysis', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_cost_analysis'],
            'permission_callback' => [$this, 'check_auth'],
        ]);
        register_rest_route('asap/v1', '/service-tracking', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'post_service_tracking'],
            'permission_callback' => [$this, 'check_auth'],
        ]);
    }

    /**
     * Check Better Auth authentication (session/token validation).
     *
     * @param WP_REST_Request $request
     * @return bool
     */
    public function check_auth($request) {
        // TODO: Integrate with Better Auth session/token validation
        // Example: Check for Authorization header, validate token/session
        // $token = $request->get_header('Authorization');
        // return better_auth_validate_token($token);
        return true; // Placeholder: always allow
    }

    /**
     * GET /usage-metrics
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function get_usage_metrics($request) {
        global $wpdb;
        $table = $wpdb->prefix . 'asap_usage_metrics';
        $params = $request->get_params();
        $where = [];
        $values = [];

        // Parse optional filters
        if (!empty($params['start_date'])) {
            $where[] = 'timestamp >= %s';
            $values[] = $params['start_date'];
        }
        if (!empty($params['end_date'])) {
            $where[] = 'timestamp <= %s';
            $values[] = $params['end_date'];
        }
        if (!empty($params['user_id'])) {
            $where[] = 'user_id = %d';
            $values[] = intval($params['user_id']);
        }
        if (!empty($params['service'])) {
            $where[] = 'service = %s';
            $values[] = $params['service'];
        }
        $sql = "SELECT * FROM $table";
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY timestamp DESC LIMIT 100';

        try {
            $results = $wpdb->get_results($wpdb->prepare($sql, ...$values), ARRAY_A);
            return new \WP_REST_Response([
                'success' => true,
                'data' => $results,
                'error' => null
            ], 200);
        } catch (\Exception $e) {
            $this->log_error('usage-metrics', $e->getMessage(), $params);
            return new \WP_REST_Response([
                'success' => false,
                'data' => null,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /cost-analysis
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function get_cost_analysis($request) {
        global $wpdb;
        $table = $wpdb->prefix . 'asap_service_costs';
        $params = $request->get_params();
        $where = [];
        $values = [];

        // Parse optional filters
        if (!empty($params['start_date'])) {
            $where[] = 'timestamp >= %s';
            $values[] = $params['start_date'];
        }
        if (!empty($params['end_date'])) {
            $where[] = 'timestamp <= %s';
            $values[] = $params['end_date'];
        }
        if (!empty($params['service'])) {
            $where[] = 'service = %s';
            $values[] = $params['service'];
        }
        $sql = "SELECT * FROM $table";
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY timestamp DESC LIMIT 100';

        try {
            $results = $wpdb->get_results($wpdb->prepare($sql, ...$values), ARRAY_A);
            return new \WP_REST_Response([
                'success' => true,
                'data' => $results,
                'error' => null
            ], 200);
        } catch (\Exception $e) {
            $this->log_error('cost-analysis', $e->getMessage(), $params);
            return new \WP_REST_Response([
                'success' => false,
                'data' => null,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * POST /service-tracking
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function post_service_tracking($request) {
        global $wpdb;
        $body = $request->get_json_params();
        $required = ['service', 'usage', 'timestamp'];
        $missing = array_filter($required, function($k) use ($body) { return empty($body[$k]); });
        if ($missing) {
            return new \WP_REST_Response([
                'success' => false,
                'data' => null,
                'error' => 'Missing required fields: ' . implode(', ', $missing)
            ], 400);
        }
        $table = $wpdb->prefix . 'asap_usage_metrics';
        $fields = [
            'service'   => sanitize_text_field($body['service']),
            'usage'     => floatval($body['usage']),
            'timestamp' => sanitize_text_field($body['timestamp']),
            'user_id'   => !empty($body['user_id']) ? intval($body['user_id']) : null,
        ];
        // Optionally insert cost if provided
        $cost = isset($body['cost']) ? floatval($body['cost']) : null;
        $inserted = false;
        try {
            $inserted = $wpdb->insert($table, $fields);
            $insert_id = $wpdb->insert_id;
            // If cost provided, insert into service_costs
            if ($cost !== null) {
                $cost_table = $wpdb->prefix . 'asap_service_costs';
                $wpdb->insert($cost_table, [
                    'service'   => $fields['service'],
                    'cost'      => $cost,
                    'timestamp' => $fields['timestamp'],
                ]);
            }
            if ($inserted) {
                return new \WP_REST_Response([
                    'success' => true,
                    'data' => [ 'id' => $insert_id ],
                    'error' => null
                ], 201);
            } else {
                throw new \Exception('Insert failed.');
            }
        } catch (\Exception $e) {
            $this->log_error('service-tracking', $e->getMessage(), $body);
            return new \WP_REST_Response([
                'success' => false,
                'data' => null,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Log errors to the asap_error_log table.
     *
     * @param string $context
     * @param string $message
     * @param array $data
     */
    protected function log_error($context, $message, $data = []) {
        global $wpdb;
        $table = $wpdb->prefix . 'asap_error_log';
        $wpdb->insert($table, [
            'context'   => sanitize_text_field($context),
            'message'   => sanitize_text_field($message),
            'data'      => maybe_serialize($data),
            'timestamp' => current_time('mysql', 1),
        ]);
    }
}

// Instantiate the controller
new Usage_Metrics_Controller(); 