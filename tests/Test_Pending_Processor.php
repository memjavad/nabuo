<?php

namespace ArabPsychology\NabooDatabase\Core {
    if (!class_exists('ArabPsychology\NabooDatabase\Core\AI_Extractor')) {
        class AI_Extractor {
            public function refine_published_field($post_id, $field_name, $content) {
                global $mock_ai_refinement;
                if (isset($mock_ai_refinement[$field_name])) {
                    return $mock_ai_refinement[$field_name];
                }
                return 'Refined ' . $field_name;
            }
        }
    }
}

namespace ArabPsychology\NabooDatabase\Tests {
    require_once 'includes/admin/class-pending-processor.php';

    use PHPUnit\Framework\TestCase;
    use ArabPsychology\NabooDatabase\Admin\Pending_Processor;

    class Test_Pending_Processor extends TestCase {

        public function setUp(): void {
            global $mock_updated_posts, $mock_ai_refinement;
            $_POST = [];
            $mock_updated_posts = [];
            $mock_ai_refinement = [];
        }

        public function test_ajax_process_pending_scale_invalid_nonce() {
            $_POST['nonce'] = 'invalid';
            $processor = new Pending_Processor();

            $this->expectException(\Exception::class);
            $this->expectExceptionMessage('Invalid nonce');

            $processor->ajax_process_pending_scale();
        }

        public function test_ajax_process_pending_scale_unauthorized_user() {
            global $mock_user_can;
            $mock_user_can = false;
            $_POST['nonce'] = 'valid_nonce';
            $processor = new Pending_Processor();

            $this->expectException(\Exception::class);
            $this->expectExceptionMessage('wp_send_json_error: {"message":"Unauthorized access."}');

            $processor->ajax_process_pending_scale();
        }

        public function test_ajax_process_pending_scale_missing_post_id() {
            global $mock_user_can;
            $mock_user_can = true;
            $_POST['nonce'] = 'valid_nonce';
            $processor = new Pending_Processor();

            $this->expectException(\Exception::class);
            $this->expectExceptionMessage('wp_send_json_error: {"message":"No scale ID provided."}');

            $processor->ajax_process_pending_scale();
        }

        public function test_ajax_process_pending_scale_invalid_post_type() {
            global $mock_user_can, $mock_posts;
            $mock_user_can = true;
            $mock_posts = [
                123 => (object)['ID' => 123, 'post_type' => 'post']
            ];
            $_POST['nonce'] = 'valid_nonce';
            $_POST['post_id'] = 123;
            $processor = new Pending_Processor();

            $this->expectException(\Exception::class);
            $this->expectExceptionMessage('wp_send_json_error: {"message":"Invalid scale ID."}');

            $processor->ajax_process_pending_scale();
        }

        public function test_ajax_process_pending_scale_successful_processing() {
            global $mock_user_can, $mock_posts, $mock_post_meta, $mock_object_terms, $mock_updated_posts;
            $mock_user_can = true;
            $mock_posts = [
                123 => (object)['ID' => 123, 'post_type' => 'psych_scale']
            ];
            $mock_post_meta = [
                123 => [
                    '_naboo_scale_abstract' => 'Valid abstract',
                    '_naboo_scale_items' => '10 items',
                    '_naboo_scale_author_details' => 'Valid authors',
                    '_naboo_scale_validity' => 'Valid',
                    '_naboo_scale_reliability' => 'Reliable'
                ]
            ];
            $mock_object_terms = [
                123 => [
                    'scale_category' => [1, 2],
                    'scale_author' => [3],
                    'scale_year' => [4]
                ]
            ];
            $_POST['nonce'] = 'valid_nonce';
            $_POST['post_id'] = 123;
            $processor = new Pending_Processor();

            try {
                $processor->ajax_process_pending_scale();
                $this->fail("Expected Exception to be thrown");
            } catch (\Exception $e) {
                $this->assertStringContainsString('wp_send_json_success', $e->getMessage());
                $this->assertStringContainsString('"status":"published"', $e->getMessage());
                $this->assertCount(1, $mock_updated_posts);
                $this->assertEquals('publish', $mock_updated_posts[0]['post_status']);
            }
        }

        public function test_ajax_process_pending_scale_needs_manual_review() {
            global $mock_user_can, $mock_posts, $mock_post_meta, $mock_object_terms, $mock_ai_refinement, $mock_updated_posts;
            $mock_user_can = true;
            $mock_posts = [
                123 => (object)['ID' => 123, 'post_type' => 'psych_scale']
            ];
            $mock_post_meta = [
                123 => [
                    '_naboo_scale_abstract' => 'information not available',
                    '_naboo_scale_items' => '10 items',
                    '_naboo_scale_author_details' => 'Valid authors',
                    '_naboo_scale_validity' => 'Valid',
                    '_naboo_scale_reliability' => 'Reliable'
                ]
            ];
            $mock_object_terms = [
                123 => [
                    'scale_category' => [1, 2],
                    'scale_author' => [3],
                    'scale_year' => [4]
                ]
            ];
            $mock_ai_refinement = [
                'abstract' => 'information not available'
            ];
            $_POST['nonce'] = 'valid_nonce';
            $_POST['post_id'] = 123;
            $processor = new Pending_Processor();

            try {
                $processor->ajax_process_pending_scale();
                $this->fail("Expected Exception to be thrown");
            } catch (\Exception $e) {
                $this->assertStringContainsString('wp_send_json_success', $e->getMessage());
                $this->assertStringContainsString('"status":"manual"', $e->getMessage());
                $this->assertCount(1, $mock_updated_posts);
                $this->assertEquals('naboo_manual', $mock_updated_posts[0]['post_status']);
            }
        }
    }
}

namespace {
    if (!function_exists('check_ajax_referer')) {
        function check_ajax_referer($action, $query_arg) {
            if (!isset($_POST[$query_arg]) || $_POST[$query_arg] !== 'valid_nonce') {
                throw new \Exception("Invalid nonce");
            }
            return true;
        }
    }

    if (!function_exists('current_user_can')) {
        function current_user_can($capability) {
            global $mock_user_can;
            return isset($mock_user_can) ? $mock_user_can : true;
        }
    }

    if (!function_exists('wp_send_json_error')) {
        function wp_send_json_error($data) {
            throw new \Exception("wp_send_json_error: " . json_encode($data));
        }
    }

    if (!function_exists('wp_send_json_success')) {
        function wp_send_json_success($data) {
            throw new \Exception("wp_send_json_success: " . json_encode($data));
        }
    }

    if (!function_exists('absint')) {
        function absint($maybeint) {
            return abs((int)$maybeint);
        }
    }

    if (!function_exists('get_post')) {
        function get_post($post_id) {
            global $mock_posts;
            return isset($mock_posts[$post_id]) ? (object)$mock_posts[$post_id] : null;
        }
    }

    if (!function_exists('get_post_meta')) {
        function get_post_meta($post_id, $key, $single = false) {
            global $mock_post_meta;
            if (isset($mock_post_meta[$post_id][$key])) {
                return $mock_post_meta[$post_id][$key];
            }
            return '';
        }
    }

    if (!function_exists('wp_get_object_terms')) {
        function wp_get_object_terms($object_ids, $taxonomies, $args = []) {
            global $mock_object_terms;
            $tax = (array)$taxonomies;
            $tax = $tax[0];
            if (isset($mock_object_terms[$object_ids][$tax])) {
                return $mock_object_terms[$object_ids][$tax];
            }
            return [];
        }
    }

    if (!function_exists('is_wp_error')) {
        function is_wp_error($thing) {
            return false;
        }
    }

    if (!function_exists('wp_set_object_terms')) {
        function wp_set_object_terms($object_id, $terms, $taxonomy, $append = false) {
            return true;
        }
    }

    if (!function_exists('update_post_meta')) {
        function update_post_meta($post_id, $meta_key, $meta_value, $prev_value = '') {
            global $mock_post_meta;
            $mock_post_meta[$post_id][$meta_key] = $meta_value;
            return true;
        }
    }

    if (!function_exists('wp_update_post')) {
        function wp_update_post($postarr, $wp_error = false, $fire_after_hooks = true) {
            global $mock_updated_posts;
            $mock_updated_posts[] = $postarr;
            return $postarr['ID'];
        }
    }

    if (!function_exists('get_the_title')) {
        function get_the_title($post) {
            return 'Test Post Title';
        }
    }

    if (!function_exists('admin_url')) {
        function admin_url($path = '', $scheme = 'admin') {
            return 'http://example.com/wp-admin/' . $path;
        }
    }

    if (!function_exists('esc_url')) {
        function esc_url($url, $protocols = null, $_context = 'display') {
            return $url;
        }
    }

    if (!function_exists('esc_html')) {
        function esc_html($text) {
            return $text;
        }
    }
}
