/**
 * AI PDF Extractor JavaScript
 */
(function ($) {
    'use strict';

    if (typeof nabooAIExtractor === 'undefined') {
        return;
    }

    const AIExtractor = {

        init() {
            this.$zone = $('#naboo-ai-upload-zone');
            this.$input = $('#naboo-ai-file-input');
            this.$selectBtn = $('#naboo-ai-select-btn');
            this.$loading = $('#naboo-ai-loading');
            this.$loadingText = this.$loading.find('p');
            this.$inner = $('.naboo-ai-upload-inner');
            this.$formWrapper = $('#naboo-ai-form-wrapper');
            this.$form = $('#naboo-ai-submit-form');
            this.$restartBtn = $('#naboo-ai-restart-btn');

            this.bindEvents();
        },

        bindEvents() {
            // Click to select
            this.$selectBtn.on('click', () => {
                this.$input.click();
            });

            // File input change
            this.$input.on('change', (e) => {
                const files = e.target.files;
                if (files && files.length > 0) {
                    this.handleFileUpload(files[0]);
                }
            });

            // Drag and drop
            this.$zone.on('dragover dragenter', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.$zone.addClass('dragover');
            });

            this.$zone.on('dragleave dragend drop', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.$zone.removeClass('dragover');
            });

            this.$zone.on('drop', (e) => {
                const files = e.originalEvent.dataTransfer.files;
                if (files && files.length > 0) {
                    this.handleFileUpload(files[0]);
                }
            });

            // Form submission
            this.$form.on('submit', (e) => {
                e.preventDefault();
                this.submitAcquiredData();
            });

            // Specific AI refinement
            $(document).on('click', '.naboo-ai-refine-btn', (e) => {
                this.handleSingleFieldRefinement(e);
            });

            // Restart
            this.$restartBtn.on('click', () => {
                this.resetToUpload();
            });
        },

        handleFileUpload(file) {
            if (file.type !== 'application/pdf') {
                alert('Please upload a PDF file.');
                return;
            }

            // Show loading
            this.$inner.hide();
            this.$loading.show();

            // 1. Initialize PDF.js
            const pdfjsLib = window['pdfjs-dist/build/pdf'];
            if (!pdfjsLib) {
                alert('PDF.js failed to load. Please try again later.');
                this.$loading.hide();
                this.$inner.show();
                return;
            }
            pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.worker.min.js';

            // 2. Extract Text from PDF locally
            const reader = new FileReader();
            reader.onload = (e) => {
                const typedarray = new Uint8Array(e.target.result);
                pdfjsLib.getDocument(typedarray).promise.then((pdf) => {
                    const maxPages = pdf.numPages;
                    const countPromises = [];

                    for (let j = 1; j <= maxPages; j++) {
                        const pagePromise = pdf.getPage(j).then((page) => {
                            return page.getTextContent().then((text) => {
                                return text.items.map(s => s.str).join(' ');
                            });
                        });
                        countPromises.push(pagePromise);
                    }

                    Promise.all(countPromises).then((texts) => {
                        const extractedText = texts.join('\n');
                        this.pdfText = extractedText; // Store for single field refinement
                        this.sendToBackend(file, extractedText);
                    }).catch((err) => {
                        console.error(err);
                        alert('Failed to parse PDF text locally.');
                        this.$loading.hide();
                        this.$inner.show();
                    });
                }).catch((err) => {
                    console.error(err);
                    alert('Failed to read PDF document.');
                    this.$loading.hide();
                    this.$inner.show();
                });
            };
            reader.readAsArrayBuffer(file);
        },

        sendToBackend(file, extractedText) {
            this.$loadingText.text(`Analyzing document with Google AI... Please wait.`);
            const formData = new FormData();
            formData.append('action', 'naboo_process_pdf_extraction');
            formData.append('nonce', nabooAIExtractor.nonce);
            formData.append('scale_pdf', file);
            formData.append('extracted_text', extractedText);

            $.ajax({
                url: nabooAIExtractor.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: (response) => {
                    this.$loading.hide();

                    if (response.success) {
                        this.populateForm(response.data);
                    } else {
                        this.$inner.show();
                        alert(response.data.message || 'An error occurred during extraction.');
                    }
                },
                error: () => {
                    this.$loading.hide();
                    this.$inner.show();
                    alert('An error occurred. Please try again.');
                }
            });
        },

        populateForm(data) {
            const extracted = data.extracted;

            $('#ai_attachment_id').val(data.attachment_id);
            $('#ai_scale_title').val(extracted.title || '');
            $('#ai_scale_construct').val(extracted.construct || '');
            $('#ai_scale_keywords').val(extracted.keywords || '');
            $('#ai_scale_purpose').val(extracted.purpose || '');
            $('#ai_scale_abstract').val(extracted.abstract || '');
            $('#ai_scale_items').val(extracted.items || '');
            $('#ai_scale_items_list').val(extracted.items_list || '');
            $('#ai_scale_scoring_rules').val(extracted.scoring_rules || '');
            $('#ai_scale_r_code').val(extracted.r_code || '');
            $('#ai_scale_year').val(extracted.year || '');
            $('#ai_scale_language').val(extracted.language || '');
            $('#ai_scale_test_type').val(extracted.test_type || '');
            $('#ai_scale_format').val(extracted.format || '');
            $('#ai_scale_methodology').val(extracted.methodology || '');
            $('#ai_scale_reliability').val(extracted.reliability || '');
            $('#ai_scale_validity').val(extracted.validity || '');
            $('#ai_scale_factor_analysis').val(extracted.factor_analysis || '');
            $('#ai_scale_population').val(extracted.population || '');
            $('#ai_scale_age_group').val(extracted.age_group || '');
            $('#ai_scale_authors').val(extracted.authors || '');
            $('#ai_scale_author_details').val(extracted.author_details || '');
            $('#ai_scale_author_email').val(extracted.author_email || '');
            $('#ai_scale_author_orcid').val(extracted.author_orcid || '');

            // Hide upload zone, show form
            this.$zone.hide();
            this.$formWrapper.show();
        },

        submitAcquiredData() {
            const $submitBtn = $('#naboo-ai-final-submit-btn');
            const originalText = $submitBtn.text();

            $submitBtn.prop('disabled', true).text('Submitting...');

            const formData = this.$form.serialize() + '&action=naboo_submit_ai_scale&nonce=' + nabooAIExtractor.nonce;

            $.ajax({
                url: nabooAIExtractor.ajax_url,
                type: 'POST',
                data: formData,
                success: (response) => {
                    if (response.success) {
                        this.$formWrapper.html(`
							<div class="naboo-notice success" style="padding: 30px; text-align: center;">
								<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: #16a34a; margin-bottom: 15px;"><polyline points="20 6 9 17 4 12"></polyline></svg>
								<h2>Success!</h2>
								<p>${response.data.message}</p>
								<button class="naboo-btn naboo-btn-primary" style="margin-top:20px;" onclick="location.reload()">Upload Another Scale</button>
							</div>
						` );
                    } else {
                        alert(response.data.message || 'Error submitting scale.');
                        $submitBtn.prop('disabled', false).text(originalText);
                    }
                },
                error: () => {
                    alert('An error occurred during submission.');
                    $submitBtn.prop('disabled', false).text(originalText);
                }
            });
        },

        handleSingleFieldRefinement(e) {
            const $btn = $(e.currentTarget);
            const fieldName = $btn.data('field');
            const $input = $(`#ai_scale_${fieldName}`);

            if (!this.pdfText) {
                alert('Detailed text memory lost. Please restart and upload the PDF again.');
                return;
            }

            const currentValue = $input.val();
            let extraContext = '';

            // If we are refining authors, send the author_details as extra context
            if (fieldName === 'authors') {
                extraContext = $('#ai_scale_author_details').val() || '';
            }

            // Set loading state
            $btn.prop('disabled', true).html('<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="naboo-spin"><polyline points="23 4 23 10 17 10"></polyline><polyline points="1 20 1 14 7 14"></polyline><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path></svg> Thinking...');

            $.ajax({
                url: nabooAIExtractor.ajax_url,
                type: 'POST',
                data: {
                    action: 'naboo_refine_single_field',
                    nonce: nabooAIExtractor.nonce,
                    field_name: fieldName,
                    current_value: currentValue,
                    extra_context: extraContext,
                    extracted_text: this.pdfText.substring(0, 50000) // Send a good chunk for context
                },
                success: (response) => {
                    if (response.success && response.data.refined_text) {
                        $input.val(response.data.refined_text);
                    } else {
                        alert(response.data.message || 'Error communicating with AI.');
                    }
                },
                error: () => {
                    alert('An error occurred communicating with the server.');
                },
                complete: () => {
                    $btn.prop('disabled', false).html('<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg> AI');
                }
            });
        },

        resetToUpload() {
            this.$form[0].reset();
            this.$formWrapper.hide();
            this.$zone.show();
            this.$inner.show();
            this.$loading.hide();
        }
    };

    $(document).ready(() => {
        if ($('#naboo-ai-upload-zone').length) {
            AIExtractor.init();
        }
    });

})(jQuery);
