(() => {
  // Prevent Ctrl+S (Save)
  const handleKeydown = (event) => {
    if (event.ctrlKey && event.key.toLowerCase() === "s") {
      event.preventDefault();
    }
  };

  // Notify parent window
  const postToParent = (message) => {
    if (window.parent) {
      window.parent.postMessage(message, "*");
    }
  };

  // Handle touch start
  const handleTouchStart = () => {
    postToParent("touch-start");
  };

  // Init
  const init = () => {
    window.addEventListener("keydown", handleKeydown);
    document.addEventListener("touchstart", handleTouchStart);

    postToParent("page-loaded");
  };

  // Run immediately
  init();
})();
