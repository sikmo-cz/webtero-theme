import { helpers } from '../helpers.js';

var example = {
  options: {
    // global options
  },

  selectors: {
    // global selectors
  },

  init: function () {
    const self = this;

    self.loadFunctionInside();
  },

  loadFunctionInside: function () {
    const self = this;

    // do something
  }
}

// Export
export { example };