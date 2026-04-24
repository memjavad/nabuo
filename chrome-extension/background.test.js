const fs = require('fs');
const assert = require('assert');

// Simple test framework
let passed = 0;
let failed = 0;

function it(name, fn) {
  try {
    const result = fn();
    if (result instanceof Promise) {
      return result.then(() => {
        console.log(`  ✓ ${name}`);
        passed++;
      }).catch(e => {
        console.error(`  ✗ ${name}`);
        console.error(e);
        failed++;
      });
    } else {
      console.log(`  ✓ ${name}`);
      passed++;
    }
  } catch (e) {
    console.error(`  ✗ ${name}`);
    console.error(e);
    failed++;
  }
}

async function runTests() {
  console.log('Running background.js tests...');

  // Mock Chrome API
  global.chrome = {
    tabs: {
      sendMessage: async () => {},
      create: () => {},
      get: () => {},
      update: () => {}
    },
    runtime: {
      lastError: null,
      onInstalled: { addListener: () => {} },
      onMessage: { addListener: () => {} }
    },
    storage: {
      local: { get: () => Promise.resolve({}), set: () => Promise.resolve() }
    }
  };
  global.fetch = () => Promise.resolve({ ok: true, json: () => [] });
  global.self = {
    onerror: null,
    onunhandledrejection: null
  };

  // Load background.js
  const code = fs.readFileSync(__dirname + '/background.js', 'utf8');
  eval(code);

  // Test: Error path for sendMessageToTab
  await it('should log error when chrome.tabs.sendMessage rejects', async () => {
    let errorLogged = false;
    let loggedError = null;
    let loggedMsg = null;

    const originalConsoleError = console.error;
    console.error = (msg, err) => {
      errorLogged = true;
      loggedMsg = msg;
      loggedError = err;
    };

    // Mock setTimeout to execute immediately
    const originalSetTimeout = global.setTimeout;
    let timeoutPromise;
    global.setTimeout = (cb) => {
      timeoutPromise = cb();
    };

    const mockError = new Error('Connection failed');
    global.chrome.tabs.sendMessage = async () => {
      throw mockError;
    };

    try {
      await sendMessageToTab(1, 'Test', 'http://example.com');

      // Wait for the simulated setTimeout callback to resolve
      if (timeoutPromise) {
        await timeoutPromise;
      }

      if (!errorLogged) throw new Error('Expected console.error to be called');
      if (loggedMsg !== 'Failed to send message to tab') throw new Error(`Expected message 'Failed to send message to tab', got '${loggedMsg}'`);
      if (loggedError !== mockError) throw new Error('Expected error object to be passed to console.error');
    } finally {
      console.error = originalConsoleError;
      global.setTimeout = originalSetTimeout;
    }
  });

  console.log(`\nResults: ${passed} passed, ${failed} failed`);
  if (failed > 0) process.exit(1);
}

runTests();
