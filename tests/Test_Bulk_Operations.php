<?php
require_once 'tests/WP_Mock_Classes.php';
require_once 'includes/admin/class-bulk-operations.php';

use PHPUnit\Framework\TestCase;

function wp_update_post() {
    return true;
}

function wp_set_post_terms() {
    return true;
}

function is_wp_error() {
    return false;
}

class Mock_WP_REST_Request {
    private $params = [];
    public function __construct($params = []) {
        $this->params = $params;
    }
    public function get_param($key) {
        return isset($this->params[$key]) ? $this->params[$key] : null;
    }
}

class Test_Bulk_Operations extends TestCase {
    public function test_change_status_with_non_array_scale_ids() {
        $bulk_ops = new \ArabPsychology\NabooDatabase\Admin\Bulk_Operations('naboodatabase', '1.0.0');
        $request = new Mock_WP_REST_Request([
            'scale_ids' => 'not_an_array',
            'status' => 'publish'
        ]);

        $response = $bulk_ops->change_status($request);
        $this->assertEquals(400, $response->status);
        $this->assertArrayHasKey('error', $response->data);
    }

    public function test_add_taxonomy_with_non_array_scale_ids() {
        $bulk_ops = new \ArabPsychology\NabooDatabase\Admin\Bulk_Operations('naboodatabase', '1.0.0');
        $request = new Mock_WP_REST_Request([
            'scale_ids' => 'not_an_array',
            'taxonomy' => 'scale_category',
            'term_ids' => [1, 2]
        ]);

        $response = $bulk_ops->add_taxonomy($request);
        $this->assertEquals(400, $response->status);
        $this->assertArrayHasKey('error', $response->data);
    }
}
