<?php
/**
 * Export Analytics Reports
 *
 * @package ArabPsychology\NabooDatabase\Admin
 */

namespace ArabPsychology\NabooDatabase\Admin;

/**
 * Export_Analytics_Reports class - Export reports in various formats.
 */
class Export_Analytics_Reports {

	/**
	 * Plugin name
	 *
	 * @var string
	 */
	private $plugin_name;

	/**
	 * Plugin version
	 *
	 * @var string
	 */
	private $version;

	/**
	 * Constructor
	 *
	 * @param string $plugin_name The plugin name.
	 * @param string $version     The plugin version.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Register REST API endpoints
	 */
	public function register_endpoints() {
		register_rest_route(
			'apa/v1',
			'/reports/export/csv',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'export_csv' ),
				'permission_callback' => function() {
					return current_user_can( 'manage_options' );
				},
			)
		);

		register_rest_route(
			'apa/v1',
			'/reports/export/pdf',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'export_pdf' ),
				'permission_callback' => function() {
					return current_user_can( 'manage_options' );
				},
			)
		);

		register_rest_route(
			'apa/v1',
			'/reports/export/json',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'export_json' ),
				'permission_callback' => function() {
					return current_user_can( 'manage_options' );
				},
			)
		);

		register_rest_route(
			'apa/v1',
			'/reports/export/excel',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'export_excel' ),
				'permission_callback' => function() {
					return current_user_can( 'manage_options' );
				},
			)
		);
	}

	/**
	 * Export report as CSV
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response
	 */
	public function export_csv( $request ) {
		$data = $request->get_param( 'data' );
		$filename = sanitize_file_name( $request->get_param( 'filename' ) . '.csv' );

		if ( ! is_array( $data ) || empty( $data ) ) {
			return new \WP_REST_Response(
				array( 'error' => 'No data to export' ),
				400
			);
		}

		$csv_data = $this->array_to_csv( $data );

		return new \WP_REST_Response(
			array(
				'data'     => $csv_data,
				'filename' => $filename,
				'mimetype' => 'text/csv',
			),
			200
		);
	}

	/**
	 * Export report as JSON
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response
	 */
	public function export_json( $request ) {
		$data = $request->get_param( 'data' );
		$filename = sanitize_file_name( $request->get_param( 'filename' ) . '.json' );

		if ( ! is_array( $data ) || empty( $data ) ) {
			return new \WP_REST_Response(
				array( 'error' => 'No data to export' ),
				400
			);
		}

		$json_data = wp_json_encode( $data, JSON_PRETTY_PRINT );

		return new \WP_REST_Response(
			array(
				'data'     => $json_data,
				'filename' => $filename,
				'mimetype' => 'application/json',
			),
			200
		);
	}

	/**
	 * Export report as PDF
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response
	 */
	public function export_pdf( $request ) {
		$data = $request->get_param( 'data' );
		$title = sanitize_text_field( $request->get_param( 'title' ) ?? 'Report' );
		$filename = sanitize_file_name( $request->get_param( 'filename' ) . '.pdf' );

		if ( ! is_array( $data ) || empty( $data ) ) {
			return new \WP_REST_Response(
				array( 'error' => 'No data to export' ),
				400
			);
		}

		$html = $this->generate_pdf_html( $title, $data );

		return new \WP_REST_Response(
			array(
				'html'     => $html,
				'filename' => $filename,
				'mimetype' => 'application/pdf',
				'message'  => 'PDF will be generated client-side using html2pdf library',
			),
			200
		);
	}

	/**
	 * Export report as Excel
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response
	 */
	public function export_excel( $request ) {
		$data = $request->get_param( 'data' );
		$filename = sanitize_file_name( $request->get_param( 'filename' ) . '.xlsx' );

		if ( ! is_array( $data ) || empty( $data ) ) {
			return new \WP_REST_Response(
				array( 'error' => 'No data to export' ),
				400
			);
		}

		// Convert to CSV format which Excel can open
		$csv_data = $this->array_to_csv( $data );

		return new \WP_REST_Response(
			array(
				'data'     => $csv_data,
				'filename' => str_replace( '.xlsx', '.csv', $filename ),
				'mimetype' => 'application/vnd.ms-excel',
				'note'     => 'Exported as CSV for Excel compatibility',
			),
			200
		);
	}

	/**
	 * Convert array to CSV
	 *
	 * @param array $data The data to convert.
	 * @return string
	 */
	private function array_to_csv( $data ) {
		$csv = '';

		if ( is_array( $data ) && ! empty( $data ) ) {
			// If first element is an array, we have multiple rows
			if ( is_array( $data[0] ) ) {
				// Get headers
				$headers = array_keys( $data[0] );
				$csv .= implode( ',', array_map( array( $this, 'escape_csv_value' ), $headers ) ) . "\n";

				// Get rows
				foreach ( $data as $row ) {
					$values = array_map( array( $this, 'escape_csv_value' ), array_values( $row ) );
					$csv .= implode( ',', $values ) . "\n";
				}
			} else {
				// Single row
				$values = array_map( array( $this, 'escape_csv_value' ), array_values( $data ) );
				$csv = implode( ',', $values );
			}
		}

		return $csv;
	}

	/**
	 * Escape CSV value
	 *
	 * @param mixed $value The value to escape.
	 * @return string
	 */
	private function escape_csv_value( $value ) {
		if ( is_array( $value ) ) {
			$value = implode( '; ', $value );
		}

		$value = (string) $value;

		// Escape quotes and wrap in quotes if contains comma, quote, or newline
		if ( strpos( $value, ',' ) !== false || strpos( $value, '"' ) !== false || strpos( $value, "\n" ) !== false ) {
			$value = '"' . str_replace( '"', '""', $value ) . '"';
		}

		return $value;
	}

	/**
	 * Generate PDF HTML
	 *
	 * @param string $title The report title.
	 * @param array  $data  The report data.
	 * @return string
	 */
	private function generate_pdf_html( $title, $data ) {
		ob_start();
		?>
		<!DOCTYPE html>
		<html>
		<head>
			<meta charset="utf-8">
			<title><?php echo esc_html( $title ); ?></title>
			<style>
				body {
					font-family: Arial, sans-serif;
					margin: 20px;
					color: #333;
				}
				h1 {
					color: #1a3a52;
					border-bottom: 2px solid #00796b;
					padding-bottom: 10px;
				}
				table {
					width: 100%;
					border-collapse: collapse;
					margin-top: 20px;
				}
				th {
					background-color: #1a3a52;
					color: white;
					padding: 12px;
					text-align: left;
					font-weight: bold;
				}
				td {
					padding: 10px;
					border-bottom: 1px solid #ddd;
				}
				tr:nth-child(even) {
					background-color: #f9f9f9;
				}
				.summary {
					background-color: #f0f0f0;
					padding: 15px;
					border-radius: 5px;
					margin: 20px 0;
				}
				.footer {
					margin-top: 30px;
					font-size: 12px;
					color: #666;
					border-top: 1px solid #ddd;
					padding-top: 10px;
				}
			</style>
		</head>
		<body>
			<h1><?php echo esc_html( $title ); ?></h1>
			<p>Generated on <?php echo esc_html( current_time( 'F j, Y g:i a' ) ); ?></p>

			<?php
			if ( is_array( $data ) ) {
				foreach ( $data as $key => $value ) {
					if ( is_array( $value ) ) {
						echo '<div class="summary"><strong>' . esc_html( $key ) . ':</strong>';
						echo '<table><thead><tr>';

						// Create table header
						if ( isset( $value[0] ) && is_array( $value[0] ) ) {
							foreach ( array_keys( $value[0] ) as $header ) {
								echo '<th>' . esc_html( $header ) . '</th>';
							}
							echo '</tr></thead><tbody>';

							// Create table rows
							foreach ( $value as $row ) {
								echo '<tr>';
								foreach ( $row as $cell ) {
									echo '<td>' . esc_html( $cell ) . '</td>';
								}
								echo '</tr>';
							}
						} else {
							echo '<th>' . esc_html( $key ) . '</th></tr></thead><tbody>';
							foreach ( $value as $item ) {
								echo '<tr><td>' . esc_html( $item ) . '</td></tr>';
							}
						}

						echo '</tbody></table></div>';
					} else {
						echo '<div class="summary"><strong>' . esc_html( $key ) . ':</strong> ' . esc_html( $value ) . '</div>';
					}
				}
			}
			?>

			<div class="footer">
				<p>Naboo Database Report | <?php echo esc_html( get_bloginfo( 'name' ) ); ?></p>
			</div>
		</body>
		</html>
		<?php
		return ob_get_clean();
	}
}
