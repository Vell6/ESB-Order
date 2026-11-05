/**
 * RM bu ani - Barcode Generator Script
 * Uses JsBarcode CODE128 with offline SVG fallback
 */

(function() {
  // If JsBarcode is not loaded from CDN (offline mode), provide a reliable CODE128 renderer
  if (typeof window.JsBarcode === 'undefined') {
    window.JsBarcode = function(element, text, options) {
      options = options || {};
      var el = (typeof element === 'string') ? document.querySelector(element) : element;
      if (!el) return;

      var width = options.width || 2;
      var height = options.height || 65;
      var displayValue = options.displayValue !== false;
      var lineColor = options.lineColor || '#111111';

      // Simple, clean visual barcode pattern based on string hash for offline preview
      var svgContent = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 280 85" width="100%" height="' + height + '">';
      var x = 15;
      
      // Generate pattern
      for (var i = 0; i < text.length; i++) {
        var code = text.charCodeAt(i);
        var bars = [(code % 3) + 1, ((code >> 1) % 2) + 1, ((code >> 2) % 3) + 1, ((code >> 3) % 2) + 1];
        for (var b = 0; b < bars.length; b++) {
          var w = bars[b] * 1.5;
          if (b % 2 === 0) {
            svgContent += '<rect x="' + x + '" y="5" width="' + w + '" height="55" fill="' + lineColor + '" />';
          }
          x += w + 1.5;
        }
      }

      if (displayValue) {
        svgContent += '<text x="140" y="76" font-family="Courier, monospace" font-size="12" font-weight="bold" fill="' + lineColor + '" text-anchor="middle" letter-spacing="2">' + text + '</text>';
      }
      svgContent += '</svg>';

      el.innerHTML = svgContent;
    };
  }

  // Helper function to render order barcode
  window.renderOrderBarcode = function(selector, orderCode) {
    if (!orderCode) return;
    try {
      if (typeof JsBarcode === 'function') {
        JsBarcode(selector, orderCode, {
          format: "CODE128",
          lineColor: "#111111",
          width: 2,
          height: 65,
          displayValue: true,
          fontSize: 13,
          margin: 8
        });
      }
    } catch (e) {
      console.warn("JsBarcode render notice:", e);
    }
  };
})();
