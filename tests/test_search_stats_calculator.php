<?php

namespace ArabPsychology\NabooDatabase\Admin {
    if (!class_exists('Database_Indexer')) {
        class Database_Indexer {
            const TABLE_NAME = 'naboo_search_index';
        }
    }
}

namespace {
    // Setup constants
    define( 'DB_NAME', 'mock_db' );

    // Mock wp_count_posts
    if (!function_exists('wp_count_posts')) {
        function wp_count_posts( $type = 'post' ) {
            $counts = new stdClass();
            $counts->publish = 10;
            return $counts;
        }
    }

    require_once __DIR__ . '/../includes/admin/search/class-search-stats-calculator.php';

    class Mock_WPDB {
        public $prefix = 'wp_';

        public $mock_table_exists = true;
        public $mock_stats = null;
        public $mock_post_status_stats = null;
        public $posts = 'wp_posts';

        public function prepare( $query, ...$args ) {
            return $query;
        }

        public function get_var( $query ) {
            if ( strpos( $query, 'information_schema.tables' ) !== false ) {
                return $this->mock_table_exists ? 1 : 0;
            }
            return null;
        }

        public function get_row( $query ) {
            return $this->mock_stats;
        }

        public function get_results( $query ) {
            return $this->mock_post_status_stats;
        }
    }

    $GLOBALS['wpdb'] = new Mock_WPDB();

    // Run tests
    $tests_passed = 0;
    $tests_total = 0;

    $calculator = new \ArabPsychology\NabooDatabase\Admin\Search\Search_Stats_Calculator();

    // Test 1: Table does not exist
    $tests_total++;
    $GLOBALS['wpdb']->mock_table_exists = false;
    $stats = $calculator->get_index_stats();
    if (
        $stats['exists'] === false &&
        $stats['total'] === 0 &&
        $stats['with_file'] === 0 &&
        $stats['coverage'] === 0
    ) {
        echo "✅ Test 1 (Table does not exist) passed.\n";
        $tests_passed++;
    } else {
        echo "❌ Test 1 (Table does not exist) failed.\n";
        var_export($stats);
    }

    // Test 2: Table exists, with stats
    $tests_total++;
    $GLOBALS['wpdb']->mock_table_exists = true;
    $mock_stats = new stdClass();
    $mock_stats->total = 5;
    $mock_stats->with_file = 2;
    $mock_stats->min_year = 2000;
    $mock_stats->max_year = 2020;
    $mock_stats->languages = 3;
    $GLOBALS['wpdb']->mock_stats = $mock_stats;

    $stats = $calculator->get_index_stats();
    if (
        $stats['exists'] === true &&
        $stats['total'] === 5 &&
        $stats['with_file'] === 2 &&
        $stats['min_year'] === 2000 &&
        $stats['max_year'] === 2020 &&
        $stats['languages'] === 3 &&
        $stats['published'] === 10 &&
        $stats['coverage'] == 50
    ) {
        echo "✅ Test 2 (Table exists, with stats) passed.\n";
        $tests_passed++;
    } else {
        echo "❌ Test 2 (Table exists, with stats) failed.\n";
        var_export($stats);
    }

    // Test 3: Table exists, but $wpdb->get_row returns null or incomplete stats
    $tests_total++;
    $GLOBALS['wpdb']->mock_table_exists = true;
    $GLOBALS['wpdb']->mock_stats = null;

    $stats = $calculator->get_index_stats();
    if (
        $stats['exists'] === true &&
        $stats['total'] === 0 &&
        $stats['with_file'] === 0 &&
        $stats['min_year'] === null &&
        $stats['max_year'] === null &&
        $stats['languages'] === 0 &&
        $stats['published'] === 10 &&
        $stats['coverage'] == 0
    ) {
        echo "✅ Test 3 (Table exists, null stats) passed.\n";
        $tests_passed++;
    } else {
        echo "❌ Test 3 (Table exists, null stats) failed.\n";
        var_export($stats);
    }

    // Test 4: Diagnostics
    $tests_total++;
    $mock_diag_1 = new stdClass();
    $mock_diag_1->post_status = 'publish';
    $mock_diag_1->cnt = 10;
    $mock_diag_2 = new stdClass();
    $mock_diag_2->post_status = 'draft';
    $mock_diag_2->cnt = 5;
    $GLOBALS['wpdb']->mock_post_status_stats = [$mock_diag_1, $mock_diag_2];

    $diags = $calculator->get_post_status_diagnostics();
    if (count($diags) === 2 && $diags[0]->post_status === 'publish' && $diags[1]->cnt === 5) {
        echo "✅ Test 4 (Diagnostics) passed.\n";
        $tests_passed++;
    } else {
        echo "❌ Test 4 (Diagnostics) failed.\n";
        var_export($diags);
    }

    echo "\nTests completed: $tests_passed/$tests_total passed.\n";
    if ($tests_passed !== $tests_total) {
        exit(1);
    }
}
