<?php
/**
 * Test script for Scale_Recommendation_Engine
 *
 * Tests the get_trending_recommendations API endpoint.
 */

// Enable assertions
ini_set('assert.exception', 1);

// Mocks for WP classes and functions
class WP_REST_Request {
    private $params;
    public function __construct($params = []) { $this->params = $params; }
    public function get_param($key) { return $this->params[$key] ?? null; }
    public function set_param($key, $value) { $this->params[$key] = $value; }
}

class WP_REST_Response {
    private $data;
    private $status;
    public function __construct($data = null, $status = 200) {
        $this->data = $data;
        $this->status = $status;
    }
    public function get_data() { return $this->data; }
    public function get_status() { return $this->status; }
}

class WP_Post {
    public $ID;
    public $post_title;
    public $post_status = 'publish';
    public $post_type = 'psych_scale';
    public $post_author = 1;
}

$mock_posts = [];
function get_post($id) {
    global $mock_posts;
    return isset($mock_posts[$id]) ? $mock_posts[$id] : null;
}

class Mock_WPDB {
    public $prefix = 'wp_';
    private $pdo;

    public function __construct() {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public function get_pdo() {
        return $this->pdo;
    }

    public function prepare($query, ...$args) {
        if (isset($args[0]) && is_array($args[0])) {
            $args = $args[0];
        }
        $query = str_replace(array("'%s'", '"%s"'), '%s', $query);
        $query = preg_replace('/%[dfs]/', '%s', $query);
        return vsprintf($query, $args);
    }

    public function get_results($query) {
        $stmt = $this->pdo->query($query);
        return $stmt ? $stmt->fetchAll(PDO::FETCH_OBJ) : [];
    }

    public function get_var($query) {
        if (strpos($query, 'SHOW TABLES LIKE') !== false) {
            return 'wp_naboo_recommendations';
        }
        return null;
    }

    public function get_charset_collate() { return ''; }
}

global $wpdb;
$wpdb = new Mock_WPDB();

// Initialize the table and mock data
$wpdb->get_pdo()->exec("CREATE TABLE wp_naboo_popularity_analytics (
    scale_id INTEGER,
    views INTEGER,
    downloads INTEGER,
    favorites INTEGER,
    avg_rating REAL
)");

// Insert mock data
// Expected scores (views + downloads * 2 + favorites * 3)
// Scale 1: 10 + 5*2 + 2*3 = 26
// Scale 2: 50 + 0 + 0 = 50
// Scale 3: 0 + 100*2 + 100*3 = 0 (Should not be returned since views is 0)
// Scale 4: 20 + 20*2 + 10*3 = 90
// Scale 5: 5 + 1*2 + 0 = 7
// Scale 6: 1 + 1*2 + 1*3 = 6
// Scale 7: 15 + 5*2 + 5*3 = 40
$wpdb->get_pdo()->exec("INSERT INTO wp_naboo_popularity_analytics (scale_id, views, downloads, favorites, avg_rating) VALUES
    (1, 10, 5, 2, 4.0),
    (2, 50, 0, 0, 3.5),
    (3, 0, 100, 100, 5.0),
    (4, 20, 20, 10, 4.8),
    (5, 5, 1, 0, 2.0),
    (6, 1, 1, 1, 3.0),
    (7, 15, 5, 5, 4.2)
");

// Mock posts
foreach ([1, 2, 3, 4, 5, 6, 7] as $id) {
    $post = new WP_Post();
    $post->ID = $id;
    $post->post_title = "Scale $id";
    $mock_posts[$id] = $post;
}

// Minimal mock functions needed by the class
if (!function_exists('wp_list_pluck')) { function wp_list_pluck($list, $field, $index_key = null) { return []; } }
if (!function_exists('get_post_meta')) { function get_post_meta($id, $key, $single) { return ''; } }
if (!function_exists('wp_get_post_terms')) { function wp_get_post_terms($id, $tax, $args) { return []; } }
if (!function_exists('plugin_dir_url')) { function plugin_dir_url($file) { return ''; } }
if (!function_exists('wp_enqueue_style')) { function wp_enqueue_style() {} }
if (!function_exists('is_singular')) { function is_singular($type) { return true; } }
if (!function_exists('get_the_ID')) { function get_the_ID() { return 1; } }
if (!function_exists('get_users')) { function get_users() { return []; } }
if (!function_exists('get_permalink')) { function get_permalink($id) { return "https://example.com/scale/$id"; } }
if (!function_exists('esc_url')) { function esc_url($url) { return $url; } }
if (!function_exists('esc_html')) { function esc_html($html) { return $html; } }
if (!function_exists('esc_html_e')) { function esc_html_e($text, $domain) { echo $text; } }

require_once __DIR__ . '/../includes/public/class-scale-recommendation-engine.php';

$engine = new ArabPsychology\NabooDatabase\Public\Scale_Recommendation_Engine('naboodatabase', '1.0.0');

echo "Running tests...\n";

// Test Case 1: Default limit (should be 5)
$request = new WP_REST_Request([]);
$response = $engine->get_trending_recommendations($request);
$data = $response->get_data();
assert(isset($data['trending']), "Response should contain 'trending' key");
assert(count($data['trending']) === 5, "Default limit should be 5, but got " . count($data['trending']));
echo "✅ Test Case 1 Passed: Default limit\n";

// Test Case 2: Custom limit
$request = new WP_REST_Request(['limit' => 2]);
$response = $engine->get_trending_recommendations($request);
$data = $response->get_data();
assert(count($data['trending']) === 2, "Custom limit 2 should return 2 items, but got " . count($data['trending']));
echo "✅ Test Case 2 Passed: Custom limit\n";

// Test Case 3: Verify sorting logic
// Ordered by (views + downloads * 2 + favorites * 3) DESC
// Expectation:
// 1. Scale 4 (score: 90)
// 2. Scale 2 (score: 50)
// 3. Scale 7 (score: 40)
// 4. Scale 1 (score: 26)
// 5. Scale 5 (score: 7)
// 6. Scale 6 (score: 6)
$request = new WP_REST_Request(['limit' => 6]);
$response = $engine->get_trending_recommendations($request);
$data = $response->get_data();
$trending = $data['trending'];

assert($trending[0]['id'] == 4, "First item should be Scale 4");
assert($trending[0]['score'] == 90, "Scale 4 score should be 90");

assert($trending[1]['id'] == 2, "Second item should be Scale 2");
assert($trending[1]['score'] == 50, "Scale 2 score should be 50");

assert($trending[2]['id'] == 7, "Third item should be Scale 7");
assert($trending[2]['score'] == 40, "Scale 7 score should be 40");

assert($trending[3]['id'] == 1, "Fourth item should be Scale 1");
assert($trending[3]['score'] == 26, "Scale 1 score should be 26");

echo "✅ Test Case 3 Passed: Verify sorting logic\n";

// Test Case 4: Verify items with 0 views are excluded
// Scale 3 has 0 views but very high downloads/favorites, which would give it the highest score if included.
$found_scale_3 = false;
foreach ($trending as $item) {
    if ($item['id'] == 3) {
        $found_scale_3 = true;
    }
}
assert($found_scale_3 === false, "Scale 3 (0 views) should be excluded");
echo "✅ Test Case 4 Passed: Verify items with 0 views are excluded\n";

echo "\nAll tests passed successfully! 🎉\n";
