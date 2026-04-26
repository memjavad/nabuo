## 2024-05-14 - Test Suite Organization

**Learning:** When dealing with multiple isolated PHP test files that mock global functions, `exec('php test-file.php')` is an effective strategy to combine them into a single test run without causing fatal redeclaration errors.

**Action:** Continue using `exec()` to chain standalone PHP test scripts if a formal test suite (like PHPUnit) is not available and global namespace conflicts are a concern.
