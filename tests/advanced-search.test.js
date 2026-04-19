/**
 * @jest-environment jsdom
 */

beforeEach(() => {
  document.body.innerHTML = `
    <div id="naboo-slide-wrapper"></div>
    <div id="naboo-slide-track"></div>
    <div id="naboo-search-results-wrapper"></div>
    <div id="naboo-topbar-count"></div>
    <button id="naboo-add-row"></button>
    <div id="naboo-search-rows">
      <div class="naboo-search-row">
        <input type="text" class="naboo-row-term" id="naboo-row-1-term" value="test" />
      </div>
    </div>
    <div id="naboo-recent-searches-wrap"></div>
    <div id="naboo-recent-list"></div>
    <button id="naboo-clear-history-btn"></button>
  `;

  window.nabooAdvancedSearch = {
    api_url: 'https://example.com/api',
    nonce: 'dummy-nonce'
  };

  // Create a proper fetch mock that returns a rejected Promise
  window.fetch = jest.fn(() => Promise.reject(new Error('Simulated network error')));
});

afterEach(() => {
  jest.restoreAllMocks();
});

test('Advanced search handles network error correctly', async () => {
  const fs = require('fs');
  const path = require('path');
  const scriptContent = fs.readFileSync(path.resolve(__dirname, '../includes/public/js/advanced-search.js'), 'utf8');

  const modifiedScript = scriptContent.replace(
    'function doSearch() {',
    'window.doSearch = function doSearch() {'
  );

  eval(modifiedScript);

  // This triggers fetch and the catch block
  window.doSearch();

  // Flush promises
  await new Promise(process.nextTick);
  await new Promise(resolve => setTimeout(resolve, 0));

  const resultsWrap = document.getElementById('naboo-search-results-wrapper');
  expect(resultsWrap.innerHTML).toContain('Connection error: Simulated network error');
});
