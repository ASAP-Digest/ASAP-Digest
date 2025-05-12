<?php
/**
 * Usage Metrics & Analytics REST API Controller
 *
 * Error Handling & Logging:
 *   - All critical errors and exceptions are logged using the ErrorLogger utility (see \ASAPDigest\Core\ErrorLogger).
 *   - Errors are recorded in the wp_asap_error_log table with context, type, message, data, and severity.
 *   - PHP error_log is used as a fallback and for development/debugging.
 *   - This ensures a unified, queryable error log for admin monitoring and alerting.
 *
 * @see \ASAPDigest\Core\ErrorLogger
 * @package ASAPDigest_Core
 * @created 05.07.25 | 04:00 PM PDT
 * @file-marker Usage_Metrics_Controller
 * @implementation-marker analytics-endpoints
 */

namespace ASAPDigest\API;

if (!defined('ABSPATH')) {
    exit;
}

use ASAPDigest\Core\ErrorLogger;
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
            /**
             * Log DB error using ErrorLogger utility.
             * Context: 'usage_metrics', error_type: 'db_error', severity: 'error'.
             * Includes exception message, SQL, and params for debugging.
             */
            ErrorLogger::log('usage_metrics', 'db_error', $e->getMessage(), [
                'sql' => $sql,
                'params' => $params
            ], 'error');
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
            /**
             * Log DB error using ErrorLogger utility.
             * Context: 'cost_analysis', error_type: 'db_error', severity: 'error'.
             * Includes exception message, SQL, and params for debugging.
             */
            ErrorLogger::log('cost_analysis', 'db_error', $e->getMessage(), [
                'sql' => $sql,
                'params' => $params
            ], 'error');
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
            /**
             * Log missing required fields using ErrorLogger utility.
             * Context: 'service_tracking', error_type: 'missing_param', severity: 'warning'.
             * Includes missing fields and body for debugging.
             */
            ErrorLogger::log('service_tracking', 'missing_param', 'Missing required fields: ' . implode(', ', $missing), [
                'missing_fields' => $missing,
                'body' => $body
            ], 'warning');
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
                    'user_id'   => $fields['user_id'],
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
            /**
             * Log DB error using ErrorLogger utility.
             * Context: 'service_tracking', error_type: 'db_error', severity: 'error'.
             * Includes exception message, fields, and body for debugging.
             */
            ErrorLogger::log('service_tracking', 'db_error', $e->getMessage(), [
                'fields' => $fields,
                'body' => $body
            ], 'error');
            return new \WP_REST_Response([
                'success' => false,
                'data' => null,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

// Instantiate the controller
new Usage_Metrics_Controller(); 