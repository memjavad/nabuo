<?php

use PHPUnit\Framework\TestCase;
use ArabPsychology\NabooDatabase\Admin\Import\Import_Processor;

class Test_Import_Processor extends TestCase {

    private $processor;

    protected function setUp(): void {
        parent::setUp();
        $this->processor = new Import_Processor();
    }

    public function test_import_scale_missing_title() {
        // Setup mock $_FILES
        $_FILES['import_file'] = array(
            'name' => 'test.csv',
            'type' => 'text/csv',
            'tmp_name' => '/tmp/phpYzdqkD',
            'error' => 0,
            'size' => 123
        );

        $row = array(
            'description' => 'Test Description',
            'items' => 10,
        );

        $result = $this->processor->import_scale( $row );

        $this->assertFalse( $result['success'] );
        $this->assertEquals( 'Missing title', $result['error'] );

        // Cleanup
        $_FILES = array();
    }

    public function test_import_scale_success() {
        // Setup mock $_FILES
        $_FILES['import_file'] = array(
            'name' => 'test.csv',
            'type' => 'text/csv',
            'tmp_name' => '/tmp/phpYzdqkD',
            'error' => 0,
            'size' => 123
        );

        $row = array(
            'title' => 'Test Scale',
            'description' => 'Test Description',
            'items' => 10,
            'category' => 'Test Category',
        );

        $result = $this->processor->import_scale( $row );

        $this->assertTrue( $result['success'] );
        $this->assertArrayHasKey( 'scale_id', $result );
        $this->assertGreaterThan( 0, $result['scale_id'] );

        // Cleanup
        $_FILES = array();
    }

    public function test_import_scale_empty_file_upload() {
        // Ensure $_FILES['import_file'] is empty
        $_FILES = array();

        $row = array(
            'title' => 'Test Scale',
        );

        $result = $this->processor->import_scale( $row );

        $this->assertFalse( $result['success'] );
        $this->assertEquals( 'No file uploaded.', $result['error'] );
    }
}
