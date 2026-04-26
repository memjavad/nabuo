🧪 [Testing improvement for ajax_process_pending_scale]

🎯 **What:** I added a comprehensive test suite (`tests/Test_Pending_Processor.php`) for the `ajax_process_pending_scale` method within the `Pending_Processor` class, which was previously untested.
📊 **Coverage:** The test suite covers invalid nonce scenarios, unauthorized access attempts, missing IDs, missing post types, happy paths (valid scale), and fallback error scenarios like missing information.
✨ **Result:** Improved overall test coverage of the application, ensuring safer database processes.
