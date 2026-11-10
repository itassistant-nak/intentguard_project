/**
 * IntentGuard Official Trust Seal Embeddable Widget
 * Version: 1.0.0
 * 
 * Usage:
 *   <script src="https://cdn.intentguard.internal/widget/intentguard-seal.js" async></script>
 *   <intentguard-seal merchant-id="IG-MCH-APEX0001" theme="light" size="medium"></intentguard-seal>
 * 
 * Or with standard data attributes:
 *   <div class="intentguard-seal-badge" data-merchant-id="IG-MCH-APEX0001"></div>
 */
(function() {
  'use strict';

  if (window.IntentGuardSealLoaded) return;
  window.IntentGuardSealLoaded = true;

  var API_BASE = window.INTENTGUARD_API_URL || 'http://localhost:8000/api/v1';

  var STYLES = `
    .ig-seal-container {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
      cursor: pointer;
      user-select: none;
      transition: all 0.2s ease;
      background: #FFFFFF;
      color: #101D35;
      border: 1px solid #E2E8F0;
      border-radius: 9999px;
      padding: 6px 14px;
      box-shadow: 0 1px 3px rgba(16, 29, 53, 0.08);
    }
    .ig-seal-container:hover {
      border-color: #176BEB;
      box-shadow: 0 4px 12px rgba(23, 107, 235, 0.15);
      transform: translateY(-1px);
    }
    .ig-seal-icon {
      width: 18px;
      height: 18px;
      flex-shrink: 0;
    }
    .ig-seal-text {
      display: flex;
      flex-direction: column;
      line-height: 1.15;
    }
    .ig-seal-title {
      font-size: 11px;
      font-weight: 700;
      letter-spacing: 0.04em;
      text-transform: uppercase;
      color: #101D35;
    }
    .ig-seal-sub {
      font-size: 10px;
      color: #64748B;
    }
    .ig-seal-score {
      font-size: 10px;
      font-weight: 700;
      background: #ECFDF5;
      color: #0E9B6C;
      border: 1px solid rgba(18, 185, 129, 0.3);
      padding: 1px 6px;
      border-radius: 9999px;
      margin-left: 4px;
    }
    .ig-modal-backdrop {
      position: fixed;
      top: 0;
      left: 0;
      width: 100vw;
      height: 100vh;
      background: rgba(16, 29, 53, 0.65);
      backdrop-filter: blur(4px);
      display: flex;
      align-items: center;
      justify-content: center;
      z-index: 999999;
      animation: ig-fade-in 0.2s ease-out;
    }
    .ig-modal-box {
      background: #FFFFFF;
      border: 1px solid #E2E8F0;
      border-radius: 16px;
      width: 90%;
      max-width: 480px;
      padding: 24px;
      box-shadow: 0 20px 25px -5px rgba(16, 29, 53, 0.15), 0 8px 10px -6px rgba(16, 29, 53, 0.1);
      color: #101D35;
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
      position: relative;
    }
    .ig-modal-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      border-bottom: 1px solid #E2E8F0;
      padding-bottom: 14px;
      margin-bottom: 16px;
    }
    .ig-modal-close {
      background: none;
      border: none;
      color: #64748B;
      font-size: 20px;
      cursor: pointer;
      line-height: 1;
      padding: 4px;
    }
    .ig-modal-close:hover {
      color: #101D35;
    }
    .ig-modal-badge {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      background: #ECFDF5;
      color: #0E9B6C;
      border: 1px solid rgba(18, 185, 129, 0.3);
      padding: 3px 8px;
      border-radius: 9999px;
      font-size: 11px;
      font-weight: 600;
    }
    .ig-modal-row {
      display: flex;
      justify-content: space-between;
      font-size: 12px;
      margin-bottom: 8px;
    }
    .ig-modal-row span:first-child {
      color: #64748B;
    }
    .ig-modal-row span:last-child {
      color: #101D35;
      font-weight: 600;
    }
    .ig-guarantee-box {
      background: #F3F6FB;
      border-left: 3px solid #176BEB;
      padding: 10px 12px;
      border-radius: 6px;
      font-size: 11px;
      color: #334155;
      line-height: 1.45;
      margin: 16px 0;
    }
    @keyframes ig-fade-in {
      from { opacity: 0; }
      to { opacity: 1; }
    }
  `;

  function injectStyles() {
    if (document.getElementById('ig-seal-styles')) return;
    var style = document.createElement('style');
    style.id = 'ig-seal-styles';
    style.textContent = STYLES;
    document.head.appendChild(style);
  }

  function getShieldSvg() {
    return '<svg class="ig-seal-icon" viewBox="0 0 24 24" fill="none" stroke="#176BEB" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' +
      '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>' +
      '<path d="m9 12 2 2 4-4"/>' +
      '</svg>';
  }

  function showDetailsModal(data) {
    var backdrop = document.createElement('div');
    backdrop.className = 'ig-modal-backdrop';

    var merchant = data || {
      intentguard_id: 'IG-MCH-XXXXXXXX',
      business_name: 'Verified IntentGuard Merchant',
      status: 'verified',
      compliance_score: 96.50
    };

    backdrop.innerHTML = [
      '<div class="ig-modal-box">',
        '<div class="ig-modal-header">',
          '<div style="display:flex;align-items:center;gap:8px;">',
            getShieldSvg(),
            '<div>',
              '<div style="font-weight:bold;font-size:14px;color:#101D35;">IntentGuard Certified Merchant</div>',
              '<div style="font-size:11px;color:#64748B;">Cryptographically Enforced E-Commerce</div>',
            '</div>',
          '</div>',
          '<button class="ig-modal-close" id="ig-close-btn">&times;</button>',
        '</div>',
        '<div style="margin-bottom:14px;">',
          '<span class="ig-modal-badge">&#10003; CONTRACTUALLY VERIFIED</span>',
        '</div>',
        '<div class="ig-modal-row">',
          '<span>Legal Business:</span>',
          '<span>' + (merchant.business_name || 'Apex Electronics Ltd') + '</span>',
        '</div>',
        '<div class="ig-modal-row">',
          '<span>Merchant ID:</span>',
          '<span style="font-family:monospace;color:#176BEB;">' + (merchant.intentguard_id || 'IG-MCH-APEX0001') + '</span>',
        '</div>',
        '<div class="ig-modal-row">',
          '<span>Compliance Score:</span>',
          '<span style="color:#0E9B6C;">' + (merchant.compliance_score ? Number(merchant.compliance_score).toFixed(1) : '96.5') + ' / 100.0</span>',
        '</div>',
        '<div class="ig-modal-row">',
          '<span>Active Agreement:</span>',
          '<span>Policy v1.0.0 (Digitally Signed)</span>',
        '</div>',
        '<div class="ig-guarantee-box">',
          '<strong>IntentGuard Purchase Integrity Guarantee:</strong><br/>',
          'This merchant has legally agreed to IntentGuard Automated Payment Capture Interception. What you confirm at checkout strictly matches what gets charged, or payment capture is automatically held.',
        '</div>',
        '<div style="text-align:center;font-size:10px;color:#64748B;margin-top:10px;">',
          'Powered by IntentGuard &bull; Cryptographic Provenance SHA-256',
        '</div>',
      '</div>'
    ].join('');

    backdrop.querySelector('#ig-close-btn').onclick = function() {
      document.body.removeChild(backdrop);
    };

    backdrop.onclick = function(e) {
      if (e.target === backdrop) {
        document.body.removeChild(backdrop);
      }
    };

    document.body.appendChild(backdrop);
  }

  function renderSealElement(el) {
    var merchantId = el.getAttribute('merchant-id') || el.getAttribute('data-merchant-id') || 'IG-MCH-APEX0001';

    var container = document.createElement('div');
    container.className = 'ig-seal-container';
    container.title = 'Click to inspect IntentGuard Merchant Verification and Compliance Score';

    container.innerHTML = [
      getShieldSvg(),
      '<div class="ig-seal-text">',
        '<span class="ig-seal-title">IntentGuard Protected</span>',
        '<span class="ig-seal-sub">Purchase Integrity Certified</span>',
      '</div>',
      '<span class="ig-seal-score" id="ig-score-pill">96.5</span>'
    ].join('');

    el.innerHTML = '';
    el.appendChild(container);

    // Fetch live merchant telemetry if possible
    fetch(API_BASE + '/merchants/' + merchantId)
      .then(function(res) {
        if (res.ok) return res.json();
        return null;
      })
      .then(function(json) {
        var mData = json ? (json.data || json) : null;
        if (mData && mData.compliance_score) {
          var pill = container.querySelector('#ig-score-pill');
          if (pill) {
            pill.textContent = Number(mData.compliance_score).toFixed(1);
          }
        }
        container.onclick = function() {
          showDetailsModal(mData || { intentguard_id: merchantId });
        };
      })
      .catch(function() {
        container.onclick = function() {
          showDetailsModal({ intentguard_id: merchantId });
        };
      });
  }

  function init() {
    injectStyles();

    // Query custom tags <intentguard-seal>
    var customTags = document.querySelectorAll('intentguard-seal');
    for (var i = 0; i < customTags.length; i++) {
      renderSealElement(customTags[i]);
    }

    // Query elements with class .intentguard-seal-badge or [data-intentguard-seal]
    var dataTags = document.querySelectorAll('.intentguard-seal-badge, [data-intentguard-seal]');
    for (var j = 0; j < dataTags.length; j++) {
      renderSealElement(dataTags[j]);
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  // Register Custom Element if supported
  if (typeof customElements !== 'undefined' && !customElements.get('intentguard-seal')) {
    var IntentGuardSealElement = function() {
      return Reflect.construct(HTMLElement, [], IntentGuardSealElement);
    };
    Object.setPrototypeOf(IntentGuardSealElement.prototype, HTMLElement.prototype);
    Object.setPrototypeOf(IntentGuardSealElement, HTMLElement);
    IntentGuardSealElement.prototype.connectedCallback = function() {
      renderSealElement(this);
    };
    try {
      customElements.define('intentguard-seal', IntentGuardSealElement);
    } catch (e) {
      // Ignored if already registered
    }
  }
})();
