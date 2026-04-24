const test = require('node:test');
const assert = require('node:assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

test('fillGrokipediaForm handles sendMessage connection drops without crashing (success path)', async (t) => {
    const code = fs.readFileSync(path.join(__dirname, 'content.js'), 'utf8');

    let errorLogged = false;
    let errorMessage = '';
    let sendMessageCount = 0;

    const context = {
        chrome: {
            runtime: {
                onMessage: {
                    addListener: () => {}
                },
                sendMessage: (msg) => {
                    sendMessageCount++;
                    throw new Error("Extension context invalidated.");
                }
            }
        },
        document: {
            querySelector: (sel) => {
                if (sel.includes('button')) return { click: () => {} };
                if (sel.includes('input')) return { value: '', dispatchEvent: () => {} };
                if (sel.includes('textarea')) return { value: '', dispatchEvent: () => {} };
                return null;
            },
            querySelectorAll: (sel) => {
                if (sel.includes('button')) {
                    return [{ textContent: 'Suggest Article', click: () => {} }, { textContent: 'Submit', disabled: false, click: () => {} }];
                }
                return [];
            }
        },
        Event: class Event {},
        console: {
            error: (msg, err) => {
                errorLogged = true;
                errorMessage = msg;
            }
        },
        setTimeout: setTimeout,
        setInterval: setInterval,
        clearInterval: clearInterval,
        Date: Date,
        Array: Array,
        Error: Error,
        Promise: Promise
    };

    vm.createContext(context);
    vm.runInContext(code, context);

    // Call the function
    try {
        await context.fillGrokipediaForm('Test Title', 'http://example.com');
    } catch (e) {
        assert.fail(`Function crashed with error: ${e.message}`);
    }

    assert.ok(errorLogged, 'Should have logged an error');
    assert.strictEqual(errorMessage, 'Naboo Ext: Error sending success message to background');
    assert.ok(sendMessageCount > 0, 'sendMessage should have been called');
});

test('fillGrokipediaForm handles sendMessage connection drops without crashing (error path)', async (t) => {
    const code = fs.readFileSync(path.join(__dirname, 'content.js'), 'utf8');

    let nabooSyncErrorLogged = false;
    let failureSendErrorLogged = false;
    let sendMessageCount = 0;

    const context = {
        chrome: {
            runtime: {
                onMessage: {
                    addListener: () => {}
                },
                sendMessage: (msg) => {
                    sendMessageCount++;
                    throw new Error("Extension context invalidated.");
                }
            }
        },
        document: {
            querySelector: (sel) => {
                // Simulate missing elements to trigger the outer catch block
                return null;
            },
            querySelectorAll: (sel) => {
                return [];
            }
        },
        Event: class Event {},
        console: {
            error: (msg, err) => {
                if (msg.includes('Naboo Sync Error')) {
                    nabooSyncErrorLogged = true;
                } else if (msg.includes('Naboo Ext: Error sending failure message to background')) {
                    failureSendErrorLogged = true;
                }
            }
        },
        setTimeout: setTimeout,
        setInterval: setInterval,
        clearInterval: clearInterval,
        Date: Date,
        Array: Array,
        Error: Error,
        Promise: Promise
    };

    vm.createContext(context);
    vm.runInContext(code, context);

    // Call the function
    try {
        await context.fillGrokipediaForm('Test Title', 'http://example.com');
    } catch (e) {
        assert.fail(`Function crashed with error: ${e.message}`);
    }

    assert.ok(nabooSyncErrorLogged, 'Should have logged Naboo Sync Error');
    assert.ok(failureSendErrorLogged, 'Should have logged the failure send error');
    assert.ok(sendMessageCount > 0, 'sendMessage should have been called');
});
