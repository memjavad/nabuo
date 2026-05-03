<?php
namespace ArabPsychology\NabooDatabase\Admin {
	class Database_Indexer {
		const TABLE_NAME = 'naboo_search_index';
	}
}

namespace {
	define('DAY_IN_SECONDS', 86400);

	require_once __DIR__ . '/mock-wp-classes.php';

	global $transient_store;
	$transient_store = [];

	function get_transient($key) {
		global $transient_store;
		return isset($transient_store[$key]) ? $transient_store[$key] : false;
	}

	function set_transient($key, $value, $expiration) {
		global $transient_store;
		$transient_store[$key] = $value;
	}

	function rest_ensure_response($response) {
		return new WP_REST_Response($response);
	}

	global $wpdb;
	$wpdb = new class {
		public $prefix = 'wp_';
		public function get_row($query) {
			$row = new stdClass();
			$row->min_year = 2000;
			$row->max_year = 2024;
			$row->min_items = 5;
			$row->max_items = 50;
			return $row;
		}
		public function get_results($query) {
			$item = new stdClass();
			$item->value = 'en';
			$item->cc = 10;
			return [$item];
		}
	};

	function get_terms($args) {
		$term = new stdClass();
		$term->term_id = 1;
		$term->name = 'Test Term';
		$term->count = 5;
		return [$term];
	}

	require_once __DIR__ . '/../includes/public/class-advanced-search.php';

	$search = new \ArabPsychology\NabooDatabase\Public\Advanced_Search('naboo-database', '1.0.0');
	$request = new WP_REST_Request();

	echo "Testing Cache Miss...\n";
	$transient_store = [];
	$response = $search->get_search_filters($request);
	if ($response->status === 200 && $response->data['success'] === true && !empty($transient_store['naboo_search_filters_cache'])) {
		echo "PASS: test_advanced_search_cache_miss\n";
	} else {
		echo "FAIL: test_advanced_search_cache_miss\n";
		var_dump($response);
		exit(1);
	}

	echo "Testing Cache Hit...\n";
	$transient_store['naboo_search_filters_cache'] = ['success' => true, 'cached' => true];
	$response = $search->get_search_filters($request);
	if ($response->status === 200 && $response->data['success'] === true && isset($response->data['cached']) && $response->data['cached'] === true) {
		echo "PASS: test_advanced_search_cache_hit\n";
	} else {
		echo "FAIL: test_advanced_search_cache_hit\n";
		var_dump($response);
		exit(1);
	}
}
