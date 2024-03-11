/**
 * @file
 * Overrides registerCoreBlocks.
 */

/* eslint func-names: ["error", "never"] */
(function(Drupal, wp, drupalSettings) {
  function registerCoreBlocks() {
    const { blocks, blockLibrary } = wp;
    const {
      setDefaultBlockName,
      setGroupingBlockName,
      setUnregisteredTypeHandlerName,
      unregisterBlockVariation,
    } = blocks;
    const { __experimentalGetCoreBlocks } = blockLibrary;
    const {
      allowedBlocks,
    } = drupalSettings.editor.formats.gutenberg.editorSettings;

    const defaultBlocks = [
      'core/block',
      'core/heading',
      'core/list',
      'core/list-item',
      'core/paragraph',
      'core/pattern',
      'core/missing',
    ];

    const coreBlocks = __experimentalGetCoreBlocks();
    const allowedCoreBlocks = coreBlocks.filter(
      block =>
        (allowedBlocks && allowedBlocks.hasOwnProperty(block.name) &&
          allowedBlocks[block.name]) ||
        defaultBlocks.includes(block.name),
    );

    allowedCoreBlocks.forEach(block => {
      block.init();
    });

    setUnregisteredTypeHandlerName('core/missing');
    setDefaultBlockName('core/paragraph');
    setGroupingBlockName('core/group');

    // Unregister core/embed variations.
    if (allowedBlocks) {
      Object.keys(allowedBlocks).forEach(key => {
        const value = allowedBlocks[key];
        if (key.startsWith('core-embed') && !value) {
          unregisterBlockVariation('core/embed', key.split('core-embed/')[1]);
        }
      });
    }
  }

  function __experimentalRegisterExperimentalCoreBlocks() {
    return null;
  }

  wp.blockLibrary = {
    ...wp.blockLibrary,
    registerCoreBlocks,
    __experimentalRegisterExperimentalCoreBlocks,
  };
})(Drupal, wp, drupalSettings);
