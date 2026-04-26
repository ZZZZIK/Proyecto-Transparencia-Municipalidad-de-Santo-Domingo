/* ============================================
   app.js — Lógica Principal
   Portal Transparencia Santo Domingo
   ============================================ */

// Utility functions
const App = {
  formatCLP: (n) => '$' + new Intl.NumberFormat('es-CL').format(n),
  formatDate: (s) => s ? new Date(s+'T00:00:00').toLocaleDateString('es-CL') : '-',
  
  // Accessibility — Floating Widget (estilo municipalidad)
  _a11yFontLevel: 0,
  _a11yModes: {},

  initA11y() {
    const html = document.documentElement;
    const panel = document.getElementById('a11yPanel');
    const fab = document.getElementById('a11yFab');
    if (!fab || !panel) return;

    // Toggle panel
    fab.addEventListener('click', (e) => {
      e.stopPropagation();
      panel.classList.toggle('open');
    });
    document.addEventListener('click', (e) => {
      if (!panel.contains(e.target) && !fab.contains(e.target)) {
        panel.classList.remove('open');
      }
    });

    // Action handlers
    const actions = {
      'font-increase': () => {
        if (App._a11yFontLevel < 2) App._a11yFontLevel++;
        html.style.fontSize = [16, 20, 24][App._a11yFontLevel] + 'px';
      },
      'font-decrease': () => {
        if (App._a11yFontLevel > 0) App._a11yFontLevel--;
        html.style.fontSize = [16, 20, 24][App._a11yFontLevel] + 'px';
      },
      'grayscale': () => html.classList.toggle('a11y-grayscale'),
      'contrast': () => {
        html.classList.toggle('a11y-contrast');
        document.body.classList.toggle('high-contrast');
      },
      'negative': () => html.classList.toggle('a11y-negative'),
      'light-bg': () => html.classList.toggle('a11y-light-bg'),
      'underline': () => html.classList.toggle('a11y-underline'),
      'readable': () => html.classList.toggle('a11y-readable'),
      'reset': () => {
        App._a11yFontLevel = 0;
        html.style.fontSize = '';
        html.classList.remove('a11y-grayscale','a11y-contrast','a11y-negative','a11y-light-bg','a11y-underline','a11y-readable','a11y-font-0','a11y-font-1','a11y-font-2');
        document.body.classList.remove('high-contrast');
        panel.querySelectorAll('button.active').forEach(b => b.classList.remove('active'));
      }
    };

    panel.querySelectorAll('[data-a11y-action]').forEach(btn => {
      btn.addEventListener('click', () => {
        const action = btn.dataset.a11yAction;
        if (actions[action]) actions[action]();
        // Toggle active state (except font and reset)
        if (!['font-increase','font-decrease','reset'].includes(action)) {
          btn.classList.toggle('active');
        }
      });
    });
  },

  // Animate elements on scroll
  initScrollAnimations() {
    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('animate-in');
          observer.unobserve(entry.target);
        }
      });
    }, { threshold: 0.1 });
    document.querySelectorAll('.animate-on-scroll').forEach(el => observer.observe(el));
  },

  // Bar chart renderer (CSS-based, SII style)
  renderBarChart(containerId, data, options = {}) {
    const container = document.getElementById(containerId);
    if (!container) return;
    const maxVal = Math.max(...data.map(d => d.value));
    let html = '<div class="bar-chart">';
    data.forEach((item, i) => {
      const pct = maxVal > 0 ? (item.value / maxVal * 100) : 0;
      html += `
        <div class="bar-row animate-on-scroll" style="animation-delay:${i*0.08}s">
          <div class="bar-label">${item.label}</div>
          <div class="bar-track">
            <div class="bar-fill" style="width:${pct}%;background:${item.color || '#006FB3'}">${options.showPercent ? (item.percent||'')+'%' : ''}</div>
          </div>
          <div class="bar-amount">${App.formatCLP(item.value)}</div>
        </div>`;
    });
    html += '</div>';
    container.innerHTML = html;
    // Trigger animation
    setTimeout(() => {
      container.querySelectorAll('.bar-fill').forEach(bar => {
        bar.style.width = bar.style.width; // Force reflow
      });
    }, 100);
  },

  // Donut chart (SVG)
  renderDonut(containerId, data) {
    const container = document.getElementById(containerId);
    if (!container) return;
    const size = 200, cx = 100, cy = 100, r = 80;
    const circumference = 2 * Math.PI * r;
    let offset = 0;
    let paths = '';
    data.forEach(item => {
      const pct = item.percent / 100;
      const dash = circumference * pct;
      const gap = circumference - dash;
      paths += `<circle cx="${cx}" cy="${cy}" r="${r}" fill="none" stroke="${item.color}" stroke-width="24" stroke-dasharray="${dash} ${gap}" stroke-dashoffset="${-offset}" style="transition:all 1s ease"/>`;
      offset += dash;
    });
    container.innerHTML = `
      <svg viewBox="0 0 ${size} ${size}" style="max-width:${size}px;transform:rotate(-90deg)">
        <circle cx="${cx}" cy="${cy}" r="${r}" fill="none" stroke="#eee" stroke-width="24"/>
        ${paths}
      </svg>`;
  },

  // Fetch JSON data
  async loadJSON(path) {
    try {
      const resp = await fetch(path);
      if (!resp.ok) throw new Error(`HTTP ${resp.status}`);
      return await resp.json();
    } catch (e) {
      console.error('Error loading data:', path, e);
      return null;
    }
  },

  // Initialize page
  init() {
    this.initA11y();
    this.initScrollAnimations();
    // Animate bar charts on load
    setTimeout(() => {
      document.querySelectorAll('.bar-fill').forEach(bar => {
        const w = bar.dataset.width || bar.style.width;
        bar.style.width = '0%';
        requestAnimationFrame(() => { bar.style.width = w; });
      });
    }, 300);
  }
};

document.addEventListener('DOMContentLoaded', () => App.init());
