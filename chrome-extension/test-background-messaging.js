const vm = require('vm');
const fs = require('fs');
const assert = require('assert');

let errored = false;
let errorMessage = '';
let caughtError = null;

const context = {
  chrome: {
    tabs: {
      sendMessage: (tabId, message) => {
        return Promise.reject(new Error('Test rejection message'));
      }
    },
    runtime: {
      onInstalled: { addListener: () => {} },
      onMessage: { addListener: () => {} },
      lastError: null
    },
    storage: {
      local: {
        get: () => {},
        set: () => {}
      }
    }
  },
  console: {
    warn: () => {},
    error: (msg, err) => {
      errored = true;
      errorMessage = msg;
      caughtError = err;
    }
  },
  setTimeout: async (cb) => { await cb(); }, // Execute immediately
  self: {}
};

vm.createContext(context);
const code = fs.readFileSync(__dirname + '/background.js', 'utf8');
vm.runInContext(code, context);

// We need to await the setTimeout callback to finish execution
async function runTest() {
  // Overwrite setTimeout to directly expose the promise
  let promiseToWait;
  context.setTimeout = (cb) => { promiseToWait = cb(); };

  context.sendMessageToTab(1, 'Test Title', 'http://example.com');

  if (promiseToWait) {
    await promiseToWait;
  }

  try {
    assert.strictEqual(errored, true, 'console.error should have been called');
    assert.strictEqual(errorMessage, 'Failed to send message to tab', 'correct error prefix');
    assert.strictEqual(caughtError.message, 'Test rejection message', 'correct error object');
    console.log('✅ sendMessageToTab error path test passed!');
  } catch (e) {
    console.error('❌ Test failed:', e.message);
    process.exit(1);
  }
}

runTest();
