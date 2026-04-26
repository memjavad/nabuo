<?php
/**
 * AI Scale Extractor Core Class
 * Handles communication with the Google Gemini API to parse PDF studies.
 *
 * @package ArabPsychology\NabooDatabase\Core
 */

namespace ArabPsychology\NabooDatabase\Core;

/**
 * Class AI_Extractor
 */
class AI_Extractor {

	/**
	 * Constructor
	 */
	public function __construct() {
		// Initialization
	}

	/**
	 * Get the next available Google Gemini API key from the saved array and rotate the index.
	 *
	 * @return string The API key or empty string if none configured.
	 */
	private function get_next_api_key() {
		$keys = get_option( 'naboo_gemini_api_key', array() );
		
		// Fallback for single key string (backward compatibility)
		if ( ! is_array( $keys ) ) {
			return ! empty( $keys ) ? $keys : '';
		}

		// Filter out any empty keys just in case
		$valid_keys = array_values( array_filter( $keys, function( $key ) {
			return ! empty( trim( $key ) );
		} ) );

		if ( empty( $valid_keys ) ) {
			return '';
		}

		// Get current index
		$index = (int) get_option( 'naboo_gemini_api_key_index', 0 );
		
		// Ensure index is within bounds
		if ( $index >= count( $valid_keys ) ) {
			$index = 0;
		}

		// Select key
		$selected_key = $valid_keys[ $index ];

		// Increment and save next index
		$next_index = $index + 1;
		if ( $next_index >= count( $valid_keys ) ) {
			$next_index = 0;
		}
		
		// Optional: use a transient to avoid writing to the DB on every single request if high traffic, 
		// but since this is an admin action mostly, update_option is fine for now.
		update_option( 'naboo_gemini_api_key_index', $next_index );

		return $selected_key;
	}

	/**
	 * Helper function to prepare payload, execute API request to Gemini, and parse the response.
	 *
	 * @param string $api_url The full API endpoint URL including the key.
	 * @param string $prompt The prompt text to send to the AI.
	 * @param float $temperature The temperature setting for the generation.
	 * @param string|null $response_mime_type Optional mime type for the response (e.g. 'application/json').
	 * @param int $timeout Timeout for the HTTP request in seconds.
	 * @param string $error_prefix Prefix for any error messages returned.
	 * @return array|string|\WP_Error Extracted data array (if JSON), string (if text), or WP_Error on failure.
	 */
	private function call_gemini_api( $api_url, $prompt, $temperature = 0.1, $response_mime_type = null, $timeout = 300, $error_prefix = 'Gemini API Error: ' ) {
		$generation_config = array(
			'temperature' => $temperature,
		);

		if ( $response_mime_type !== null ) {
			$generation_config['responseMimeType'] = $response_mime_type;
		}

		$body = array(
			'contents' => array(
				array(
					'parts' => array(
						array(
							'text' => $prompt,
						),
					),
				),
			),
			'generationConfig' => $generation_config,
		);

		$request_args = array(
			'body'    => wp_json_encode( $body ),
			'headers' => array(
				'Content-Type' => 'application/json',
			),
			'timeout' => $timeout,
		);

		$response = wp_remote_post( $api_url, $request_args );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );

		if ( 200 !== $response_code ) {
			$error_data = json_decode( $response_body, true );
			$error_msg  = $error_data['error']['message'] ?? 'Unknown API Error';
			return new \WP_Error( 'api_error', $error_prefix . $error_msg );
		}

		$data = json_decode( $response_body, true );

		if ( ! isset( $data['candidates'][0]['content']['parts'][0]['text'] ) ) {
			return new \WP_Error( 'parse_error', __( 'Could not parse response from AI.', 'naboodatabase' ) );
		}

		$result_text = $data['candidates'][0]['content']['parts'][0]['text'];

		if ( $response_mime_type === 'application/json' ) {
			// Remove Markdown JSON wrapper if any
			$result_text = preg_replace( '/^```json\s*/i', '', $result_text );
			$result_text = preg_replace( '/\s*```$/', '', $result_text );
			$result_text = trim( $result_text );

			$extracted_data = json_decode( $result_text, true );

			if ( ! is_array( $extracted_data ) ) {
				return new \WP_Error( 'invalid_json', __( 'AI did not return a valid JSON object.', 'naboodatabase' ) );
			}
			return $extracted_data;
		}

		return trim( $result_text );
	}

	/**
	 * Extract data from raw text extracted from a PDF
	 *
	 * @param string $extracted_text The raw text from the scale PDF.
	 * @return array|WP_Error Parsed scale data array or WP_Error on failure.
	 */
	public function extract_from_text( $extracted_text ) {
		$api_key = $this->get_next_api_key();
		if ( empty( $api_key ) ) {
			return new \WP_Error( 'no_api_key', __( 'Google Gemini API key not configured.', 'naboodatabase' ) );
		}

		$prompt = "You are an expert psychometrician. I am providing you with the extracted raw text from a research paper outlining the development, validation, or translation of a psychological scale.
Extract the relevant scale metrics into this EXACT JSON structure. Leave blank strings if not found. Do not include markdown formatting blocks (like ```json), and DO NOT use any HTML tags. All extracted content MUST BE raw text without any styling, tags, or markup.
For the 'r_code' field, you MUST write R code to automate the scoring of this scale. CRITICAL: The 'r_code' field must be strictly raw, plain-text script.
For the 'source_reference' field, extract ANY and ALL references explicitly mentioned in the original text. Format each as a full APA citation separated by line breaks. DO NOT hallucinate or generate a new reference if missing.
CRITICAL INSTRUCTION: Remove any copyright boilerplate text such as \"(PsycTests Database Record (c) 2020 APA, all rights reserved)\" or similar from all extracted fields.
CRITICAL INSTRUCTION: DO NOT hallucinate, invent, or infer any data from your pre-trained knowledge. If a specific piece of information is NOT explicitly mentioned or supported by the provided text, you MUST leave the field blank or return 'Information not available'.
CRITICAL INSTRUCTION: DO NOT prefix any of your extracted values with the field name or label. Output ONLY the raw data value itself (e.g., output `1965` instead of `Year: 1965`, or `English` instead of `Language: English`). Do NOT include any additional descriptions or conversational text for simple fields like Year, Items, Language, Test Type, or Format.
CRITICAL INSTRUCTION: For the 'keywords' field, you MUST generate exactly 5 relevant keywords separated by commas. Return ONLY the 5 comma-separated words with absolutely no other text, labels, bullet points, or explanations.
CRITICAL INSTRUCTION: For the 'authors' and 'author_details' fields, you MUST cross-check both the abstract and the source reference (APA citation) to ensure extreme accuracy. The authors listed MUST perfectly match the primary developers of the scale. Return ONLY the names in a comma-separated list. IMPORTANT: Format each name as \"Firstname Lastname\" (e.g., John Doe, Jane Smith). NEVER use commas within a single author's name.
CRITICAL INSTRUCTION: NEVER include bracketed placeholder text (e.g., [insert link here]). If a value is missing, use the specified fallback or an empty string, but never a descriptive placeholder or instruction for the user.

{
  \"title\": \"\",
  \"keywords\": \"\",
  \"construct\": \"\",
  \"purpose\": \"\",
  \"abstract\": \"\",
  \"items\": \"\",
  \"items_list\": \"\",
  \"year\": \"\",
  \"language\": \"\",
  \"test_type\": \"\",
  \"format\": \"\",
  \"methodology\": \"\",
  \"reliability\": \"\",
  \"validity\": \"\",
  \"factor_analysis\": \"\",
  \"population\": \"\",
  \"age_group\": \"\",
  \"authors\": \"\",
  \"author_details\": \"\",
  \"author_email\": \"Information not available\",
  \"author_orcid\": \"Information not available\",
  \"scoring_rules\": \"\",
  \"r_code\": \"\",
  \"administration_method\": \"\",
  \"instrument_type\": \"\",
  \"source_reference\": \"\"
}

Here is the extracted text:
" . substr( $extracted_text, 0, 100000 ); // Limit to 100k chars to avoid blowing up payload

		$model_name = get_option( 'naboo_gemini_model', 'gemini-2.5-flash' );
		$api_url = 'https://generativelanguage.googleapis.com/v1beta/models/' . $model_name . ':generateContent?key=' . $api_key;

		$extracted_data = $this->call_gemini_api( $api_url, $prompt, 0.1, 'application/json', 300 );

		if ( is_wp_error( $extracted_data ) ) {
			return $extracted_data;
		}

		// --- Secondary Refinement via Gemma 27B ---
		$refinement_prompt = "You are an expert psychometrician. I am providing you with previously extracted JSON data about a psychological scale, as well as the beginning of the original research paper.
Please refine the 'title' and 'abstract' according to the following rules:
1. Rewrite the 'title' to represent the name of the scale itself, NOT the name of the study or research paper. Look at the original text if needed.
2. Rewrite the 'abstract' to represent a description about the scale itself (what it measures, how it is used), rather than the study's abstract.
CRITICAL INSTRUCTION: You MUST rely exclusively on the provided context text. DO NOT hallucinate or generate information that is not explicitly supported by the text.
Return a new JSON object containing ONLY the 'title' and 'abstract' keys. Do not include any other fields, and do not include markdown formatting.

{
  \"title\": \"[refined title here]\",
  \"abstract\": \"[refined abstract here]\"
}

Original Text Context (first 15000 characters):
" . substr( $extracted_text, 0, 15000 ) . "

Extracted Data:
" . wp_json_encode( $extracted_data );

		$gemma_model = 'gemma-4-31b-it';
		$refinement_api_url = 'https://generativelanguage.googleapis.com/v1beta/models/' . $gemma_model . ':generateContent?key=' . $api_key;

		$refined_data = $this->call_gemini_api( $refinement_api_url, $refinement_prompt, 0.1, 'application/json', 300 );

		if ( ! is_wp_error( $refined_data ) && is_array( $refined_data ) ) {
			// Merge ONLY the refined title and abstract back into the main payload.
			if ( ! empty( $refined_data['title'] ) ) {
				$extracted_data['title'] = $refined_data['title'];
			}
			if ( ! empty( $refined_data['abstract'] ) ) {
				$extracted_data['abstract'] = $refined_data['abstract'];
			}
		}

		return $extracted_data;
	}

	/**
	 * Refine a single specific field using Gemini API based on the PDF text.
	 *
	 * @param string $extracted_text The raw text from the scale PDF.
	 * @param string $field_name The field to refine.
	 * @param string $current_value The current value of the field.
	 * @return string|\WP_Error Refined text or WP_Error.
	 */
	public function refine_single_field( $extracted_text, $field_name, $current_value, $extra_context = '' ) {
		$api_key = $this->get_next_api_key();
		if ( empty( $api_key ) ) {
			return new \WP_Error( 'no_api_key', __( 'Google Gemini API key not configured.', 'naboodatabase' ) );
		}

		$field_descriptions = array(
			'title'           => 'The official name of the psychological scale (NOT the name of the research paper).',
			'construct'       => 'The psychological construct being measured.',
			'keywords'        => 'A comma-separated list of keywords related to the scale or study.',
			'purpose'         => 'The intended purpose of the scale.',
			'abstract'        => 'A brief description of the scale, what it measures, and how it is used.',
			'items'           => 'The total number of items in the scale (should be only a number).',
			'items_list'      => 'The actual list of items/questions/statements that make up the scale. Format as a simple text list with line breaks.',
			'year'            => 'The year the scale was published (should be a number).',
			'language'        => 'The language the scale was developed or translated into.',
			'test_type'       => 'The type of test (e.g., Self-report, Observer-rating, Performance-based).',
			'format'          => 'The response format (e.g., 5-point Likert scale, dichotomous).',
			'methodology'     => 'The overarching methodology of the study.',
			'reliability'     => 'Details regarding the reliability of the scale (e.g., Cronbach\'s alpha, test-retest).',
			'validity'        => 'Details regarding the validity of the scale (e.g., construct, convergent, discriminant).',
			'factor_analysis' => 'Details about factor analysis (e.g., CFA, EFA, factor structures).',
			'population'      => 'The target population the scale was designed or validated for.',
			'age_group'       => 'The intended age group for the scale.',
			'authors'         => 'A comma-separated list of the names of the primary developers/authors. Format each as "Firstname Lastname" (e.g., John Doe, Jane Smith). You MUST strictly cross-check the abstract and source reference to ensure the authors match exactly. NEVER use commas within a single author\'s name.',
			'author_details'  => 'Details about the primary developers/authors of the scale, including their affiliations. You MUST strictly cross-check the abstract and the source reference to ensure the details match the true authors of the scale. Format as a simple text list separated by line breaks.',
			'author_email'    => 'The corresponding author\'s email address. If not found, you MUST return \"Information not available\".',
			'author_orcid'    => 'The corresponding author\'s ORCID iD. If not found, you MUST return \"Information not available\".',
			'scoring_rules'   => 'The exact instructions for scoring the scale. Format as clear raw text with line breaks for readability.',
			'r_code'          => 'R code to automate the scoring of this scale based strictly on the Scoring Rules.',
			'administration_method' => 'How the scale is administered (e.g., Paper-and-pencil, Computerized, Online, Interview, Observation).',
			'instrument_type' => 'The category of the psychological instrument (e.g., Personality Inventory, Cognitive Test, Diagnostic Interview, Projective Technique, Rating Scale).',
			'source_reference'=> 'The full APA-formatted citation(s) of the original source paper or ANY references explicitly mentioned in the text. CRITICAL: Extract ALL references found and format each as an APA citation separated by line breaks. DO NOT hallucinate or invent a reference.',
		);

		$field_desc = $field_descriptions[ $field_name ] ?? $field_name;

		$prompt = "You are an expert psychometrician. I am providing you with the extracted raw text from a research paper outlining a psychological scale.
I need you to extract or refine only a single specific field based on the text.
The field to refine is: \"{$field_name}\".
Context about this field: {$field_desc}
The current drafted value is: \"{$current_value}\".";

		if ( ! empty( $extra_context ) ) {
			$prompt .= "\nADDITIONAL CONTEXT FOR REFINEMENT:\n\"{$extra_context}\"\n";
		}

		$prompt .= "
Instructions:
1. Search the text specifically for information matching this field's context.
2. Remove any copyright boilerplate text such as \"(PsycTests Database Record (c) 2020 APA, all rights reserved)\" or similar.
3. Return ONLY the raw string value to place in this field. Do not include markdown, prefixes, quotes, or JSON formatting. Just the extracted text. If nothing is found, return an empty string.
4. CRITICAL: NEVER include bracketed placeholder text (e.g., [insert link here] or [provide details]). Only output real data or an empty string.
5. CRITICAL INSTRUCTION: You MUST rely exclusively on the provided context text. DO NOT generate information that is not explicitly supported by the text. If the text does not contain the answer, return an empty string or the specified fallback.

Here is the extracted text:
" . substr( $extracted_text, 0, 50000 );

		$model_name = get_option( 'naboo_gemini_model', 'gemini-2.5-flash' );
		$api_url = 'https://generativelanguage.googleapis.com/v1beta/models/' . $model_name . ':generateContent?key=' . $api_key;

		$result_text = $this->call_gemini_api( $api_url, $prompt, 0.2, null, 300, 'AI Processing failed.' );

		if ( is_wp_error( $result_text ) ) {
			// If it's a parse error, original code returned empty string.
			if ( $result_text->get_error_code() === 'parse_error' ) {
				return '';
			}
			// For api_error, original code returned the error
			return $result_text;
		}

		return $result_text;
	}

	/**
	 * Improve or refine an already published field without full PDF context.
	 *
	 * @param int $post_id The scale post ID.
	 * @param string $field_name The field to refine.
	 * @param string $current_value The current value of the field.
	 * @return string|\WP_Error Refined text or WP_Error.
	 */
	public function refine_published_field( $post_id, $field_name, $current_value ) {
		$api_key = $this->get_next_api_key();
		if ( empty( $api_key ) ) {
			return new \WP_Error( 'no_api_key', __( 'Google Gemini API key not configured.', 'naboodatabase' ) );
		}

		$scale_title = get_the_title( $post_id );

		$prompt = "You are an expert psychometrician and editor. You are reviewing the metadata for a psychological scale titled \"{$scale_title}\".\n";
		$prompt .= "I need you to improve and refine the following field: \"{$field_name}\".\n\n";
		$prompt .= "CRITICAL INSTRUCTION: Remove any copyright boilerplate text such as \"(PsycTests Database Record (c) 2020 APA, all rights reserved)\" or similar before returning the result.\n";
		$prompt .= "CRITICAL INSTRUCTION: Format your response as raw text without any HTML tags or markdown styling.\n";
		$prompt .= "CRITICAL INSTRUCTION: NEVER include bracketed placeholder text (e.g., [insert link here] or [provide details]). Only output real data or the specified fallback string.\n";
		$prompt .= "CRITICAL INSTRUCTION: DO NOT hallucinate, invent, or infer any data from your pre-trained knowledge. If a specific piece of information is NOT explicitly mentioned or supported by the provided text, you MUST leave the field blank or return an empty string/specified fallback.\n";

		if ( $field_name === 'author_details' ) {
			$abstract_info = get_post_meta( $post_id, '_naboo_scale_abstract', true );
			$source_ref = get_post_meta( $post_id, '_naboo_scale_source_reference', true );
			if ( $abstract_info || $source_ref ) {
				$prompt .= "CONTEXT FROM ABSTRACT:\n\"{$abstract_info}\"\n\nCONTEXT FROM SOURCE REFERENCE:\n\"{$source_ref}\"\n\n";
			}
			$prompt .= "CRITICAL INSTRUCTION: Reformat the author information into a clear raw text list separated by line breaks. Include the author names, their affiliations, and their ORCID if available. You MUST cross-check this against the provided Abstract and Source Reference to ensure extreme accuracy.\n";
		} elseif ( $field_name === 'author_orcid' ) {
			$prompt .= "CRITICAL INSTRUCTION: Extract the ORCID numbers and format them as full URLs (e.g., https://orcid.org/0000-0000-0000-0000). If there are multiple, separate them with a comma. If NO ORCID IS FOUND in the original text, you MUST return EXACTLY: \"Information not available\".\n";
		} elseif ( $field_name === 'author_email' ) {
			$prompt .= "CRITICAL INSTRUCTION: Extract the corresponding author's email address. If NO EMAIL IS FOUND in the original text, you MUST return EXACTLY: \"Information not available\".\n";
		} elseif ( $field_name === 'abstract' ) {
			$prompt .= "CRITICAL INSTRUCTION: Rewrite this abstract so that it focuses SPECIFICALLY on the scale itself (what it measures, its psychometric properties, its intended use), rather than talking broadly about the original study or research paper. Format as raw text paragraphs separated by line breaks.\n";
		} elseif ( $field_name === 'items_list' ) {
			$prompt .= "CRITICAL INSTRUCTION: Reformat the scale items into a clear numbered list using raw text and line breaks.\n";
		} elseif ( $field_name === 'year' ) {
			$prompt .= "CRITICAL INSTRUCTION: write the year. Output ONLY the 4-digit number of the year (e.g. 1965) with absolutely no other text, labels, or explanation.\n";
		} elseif ( $field_name === 'items' ) {
			$prompt .= "CRITICAL INSTRUCTION: write the number of items in the scale. Output ONLY the raw numerical digit (e.g. 10) with absolutely no other text, labels, or explanation.\n";
		} elseif ( $field_name === 'keywords' ) {
			$prompt .= "CRITICAL INSTRUCTION: Re-evaluate the keywords for this scale. Output EXACTLY 5 relevant keywords separated by commas. Return ONLY the 5 comma-separated words with absolutely no other text, labels (e.g. no 'Keywords:'), bullet points, or explanations.\n";
		} elseif ( $field_name === 'test_type' ) {
			$prompt .= "CRITICAL INSTRUCTION: Analyze the text and assign ONE OR MORE of the exact following predefined values that best match: Self-report questionnaire, Performance test, Projective test, Observational rating scale, Interview schedule, Checklist, Inventory. DO NOT invent your own categories. Return ONLY the comma-separated values you chose with NO other text or labels.\n";
		} elseif ( $field_name === 'scoring_rules' ) {
			$prompt .= "CRITICAL INSTRUCTION: Extract and format the Likert response terms (e.g., 1 = Strongly Disagree, 2 = Disagree, etc.) and any specific scoring instructions (e.g., reverse-scored items, subscale sums). Make it a highly readable raw text format with line breaks.\n";
		} elseif ( $field_name === 'r_code' ) {
			$prompt .= "CRITICAL INSTRUCTION: Write an R code snippet that automates the scoring of this scale based strictly on its Scoring Rules. It should take a dataframe of item responses and compute the total score(s) and any subscale scores, factoring in reverse coding if applicable according to the Scoring Rules. ABSOLUTELY DO NOT output any HTML tags (like <code> or <strong>) or markdown formatting in this field. Output ONLY plain text, raw R code.\n";
		} elseif ( $field_name === 'language' ) {
			$prompt .= "CRITICAL INSTRUCTION: Analyze the text and assign ONLY ONE PRIMARY language from the exact following predefined values: Arabic, English, French, Spanish, Bilingual (Arabic & English), Non-Verbal/Culture-Free. DO NOT invent your own categories. Return ONLY the single most relevant language name you chose with NO commas, NO lists, and NO other text or labels.\n";
		} elseif ( $field_name === 'format' ) {
			$prompt .= "CRITICAL INSTRUCTION: Analyze the text and assign ONE OR MORE of the exact following predefined values that best match: Likert scale, Dichotomous (Yes/No, True/False), Multiple Choice, Semantic Differential, Open-ended, Visual Analogue. DO NOT invent your own categories. Return ONLY the comma-separated values you chose with NO other text or labels or descriptions.\n";
		} elseif ( $field_name === 'population' ) {
			$prompt .= "CRITICAL INSTRUCTION: Analyze the text and assign ONE OR MORE of the exact following predefined values that best match: General Population, Clinical Patients, Students, Employees/Professionals, Parents, Children/Adolescents. DO NOT invent your own categories. Return ONLY the comma-separated values you chose.\n";
		} elseif ( $field_name === 'age_group' ) {
			$prompt .= "CRITICAL INSTRUCTION: Analyze the text and assign ONE OR MORE of the exact following predefined values that best match: Children (0-12), Adolescents (13-17), Adults (18-64), Older Adults (65+), All Ages. DO NOT invent your own categories. Return ONLY the comma-separated values you chose.\n";
		} elseif ( $field_name === 'administration_method' ) {
			$prompt .= "CRITICAL INSTRUCTION: Analyze the text and identify how the scale is administered. Choose ONLY from: Paper-and-pencil, Computerized, Online, Interview, Observation. If multiple apply, comma-separate them.\n";
		} elseif ( $field_name === 'instrument_type' ) {
			$prompt .= "CRITICAL INSTRUCTION: Analyze the text and assign the appropriate category of the instrument. Choose ONE OR MORE of: Personality Inventory, Cognitive Test, Diagnostic Interview, Projective Technique, Rating Scale. DO NOT invent your own categories.\n";
		} elseif ( $field_name === 'authors' ) {
			$author_info = get_post_meta( $post_id, '_naboo_scale_author_details', true );
			$abstract_info = get_post_meta( $post_id, '_naboo_scale_abstract', true );
			$source_ref = get_post_meta( $post_id, '_naboo_scale_source_reference', true );
			if ( $author_info ) {
				$prompt .= "CONTEXT FROM 'Author Information' FIELD:\n\"{$author_info}\"\n\n";
			}
			if ( $abstract_info || $source_ref ) {
				$prompt .= "CONTEXT FROM ABSTRACT:\n\"{$abstract_info}\"\n\nCONTEXT FROM SOURCE REFERENCE:\n\"{$source_ref}\"\n\n";
			}
			$prompt .= "CRITICAL INSTRUCTION: Extract ONLY the names of the primary developers/authors. You MUST strictly cross-check against the Abstract and Source Reference context to ensure extreme accuracy. Return them as a simple comma-separated list of full names (e.g., John Doe, Jane Smith). IMPORTANT: Use \"Firstname Lastname\" format and NEVER include commas within a single name. Do not include affiliations or titles.\n";
		} elseif ( $field_name === 'category' ) {
			$prompt .= "CRITICAL INSTRUCTION: Analyze the scale and assign one or more relevant categories. Return them as a simple comma-separated list of category names.\n";
		} elseif ( $field_name === 'source_reference' ) {
			$prompt .= "CRITICAL INSTRUCTION: Format this text to include ANY AND ALL references mentioned in the draft. Each reference should be a perfect, formal APA citation separated by line breaks. If the provided text does NOT contain any valid references, DO NOT invent or hallucinate one; return an empty string instead.\n";
		} elseif ( in_array( $field_name, array('reliability', 'validity', 'factor_analysis') ) ) {
			$prompt .= "CRITICAL INSTRUCTION: Ensure the text sounds like a professional summary of psychometric properties. Make it concise and authoritative.\n";
		} else {
			$prompt .= "CRITICAL INSTRUCTION: Improve the clarity and professionalism of this text. Fix any typos or grammatical errors. Ensure it is concise.\n";
		}

		$prompt .= "\nHere is the current value of the field:\n\"{$current_value}\"\n\n";
		$prompt .= "Return ONLY the refined raw text. Do not include markdown formatting, HTML tags, or conversational filler. Just the polished text itself.";

		$model_name = 'gemma-4-31b-it'; // Hardcoded per user request
		$api_url = 'https://generativelanguage.googleapis.com/v1beta/models/' . $model_name . ':generateContent?key=' . $api_key;

		$result_text = $this->call_gemini_api( $api_url, $prompt, 0.4, null, 120, 'AI Processing failed.' );

		if ( is_wp_error( $result_text ) ) {
			if ( $result_text->get_error_code() === 'parse_error' ) {
				return $current_value; // Return original if AI fails to generate text
			}
			return $result_text;
		}

		return $result_text;
	}
}
