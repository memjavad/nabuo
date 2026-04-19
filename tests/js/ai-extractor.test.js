const fs = require('fs');
const path = require('path');

// Setup global DOM
document.body.innerHTML = `
    <div id="naboo-ai-upload-zone"></div>
    <input id="naboo-ai-file-input" type="file" />
    <button id="naboo-ai-select-btn"></button>
    <div id="naboo-ai-loading"><p></p></div>
    <div class="naboo-ai-upload-inner"></div>
    <div id="naboo-ai-form-wrapper"></div>
    <form id="naboo-ai-submit-form"></form>
    <button id="naboo-ai-restart-btn"></button>
`;

global.jQuery = require('jquery');
global.$ = global.jQuery;

// Mock FormData
global.FormData = class {
    append() {}
};

// Mock nabooAIExtractor
global.nabooAIExtractor = {
    nonce: 'test_nonce',
    ajax_url: 'http://test.local/wp-admin/admin-ajax.php'
};

global.alert = jest.fn();
console.error = jest.fn();

// Load the file to test
let code = fs.readFileSync(path.resolve(__dirname, '../../includes/public/js/ai-extractor.js'), 'utf8');

// Strip out IIFE and initialize AIExtractor in global scope
code = code.replace(/^\(function \(\$\) \{/m, '');
code = code.replace(/\}\)\(jQuery\);$/m, '');

const moduleScope = `
    let AIExtractor;
    ${code.replace('const AIExtractor = {', 'AIExtractor = {')}
    return AIExtractor;
`;

let extractedAIExtractor;
try {
    extractedAIExtractor = new Function('$', moduleScope)(global.$);
} catch (e) {
    console.error(e);
}

describe('AIExtractor', () => {
    beforeEach(() => {
        jest.clearAllMocks();
        global.fetch = jest.fn();
        if (extractedAIExtractor) {
            extractedAIExtractor.init();
        }
    });

    test('should show error when parsing invalid JSON response', async () => {
        const mockResponse = {
            ok: true,
            json: () => Promise.reject(new SyntaxError('Unexpected token < in JSON at position 0'))
        };
        global.fetch.mockResolvedValue(mockResponse);

        // Call the method
        await extractedAIExtractor.sendToBackend(new File([''], 'test.pdf'), 'Test Text');

        // Wait for next tick so promises can resolve
        await new Promise(process.nextTick);

        expect(global.alert).toHaveBeenCalledWith('Failed to parse AI response');
        // jQuery hide() sets display to none
        expect(extractedAIExtractor.$loading.css('display')).toBe('none');
    });
});
