/* ============================================
   app.js — Lógica Principal
   Portal Transparencia Santo Domingo
   ============================================ */

// Utility functions
const App = {
  formatCLP: (n) => '$' + new Intl.NumberFormat('es-CL').format(n),
  formatDate: (s) => s ? new Date(s+'T00:00:00').toLocaleDateString('es-CL') : '-',
  
  // Session management — ISO compliance
  isLoggedIn: () => sessionStorage.getItem('transparencia_session') !== null,
  getUserSession() {
    try {
      const sess = sessionStorage.getItem('transparencia_session');
      return sess ? JSON.parse(sess) : null;
    } catch (e) {
      return null;
    }
  },
  logout() {
    sessionStorage.removeItem('transparencia_session');
    // Si estamos en un subdirectorio public/pages, volvemos un nivel atrás
    const path = window.location.pathname;
    if (path.includes('/pages/')) {
      window.location.href = '../index.html';
    } else {
      window.location.href = 'index.html';
    }
  },

  getBaseApiUrl() {
    const path = window.location.pathname;
    if (path.includes('/public/')) {
      const idx = path.indexOf('/public/');
      return path.substring(0, idx) + '/public/api';
    }
    if (window.location.protocol.startsWith('http')) {
      return '/api';
    }
    return null;
  },
  
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

  // Fetch JSON data with graceful API fallback
  async loadJSON(path) {
    try {
      const baseApi = this.getBaseApiUrl();
      if (!baseApi) throw new Error("Offline file protocol detected, skipping API.");

      // Reemplaza ruta local por la ruta de la API dinámicamente
      let filename = path.split('/').pop().replace('.json', '');
      let apiPath = `${baseApi}/${filename}`;
      
      // Si la búsqueda contiene parámetros en la URL (ej. query params de RUT), los pasamos
      if (window.location.search) {
        apiPath += window.location.search;
      }
      
      const resp = await fetch(apiPath);
      if (!resp.ok) throw new Error(`API Fallback to ${path}`);
      const data = await resp.json();
      console.log('Datos cargados dinámicamente desde API:', apiPath);
      return data;
    } catch (e) {
      console.warn('API no disponible, cargando JSON estático de respaldo:', e);
      try {
        const resp = await fetch(path);
        if (!resp.ok) throw new Error(`HTTP ${resp.status}`);
        return await resp.json();
      } catch (err) {
        console.error('Error cargando datos estáticos:', err);
        return null;
      }
    }
  },

  // Fetch active transparency query periods
  async getPeriodosHabilitados() {
    try {
      const baseApi = this.getBaseApiUrl();
      if (!baseApi) throw new Error("Offline");

      const resp = await fetch(`${baseApi}/periodos`);
      if (!resp.ok) throw new Error("ServerError");
      const data = await resp.json();
      if (data && data.length > 0) {
        localStorage.setItem('transparencia_periodos', JSON.stringify(data));
        return data;
      }
      throw new Error("Empty");
    } catch (e) {
      console.warn("Utilizando periodos locales de localStorage:", e);
      const local = localStorage.getItem('transparencia_periodos');
      if (local) {
        return JSON.parse(local);
      }
      // Semillas por defecto si no hay nada
      const defaultPeriods = [];
      const years = [2023, 2024, 2025, 2026];
      years.forEach(y => {
        const months = ['anual', '1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12'];
        months.forEach(m => {
          defaultPeriods.push({
            anio: y,
            mes: m,
            nombre_mes: m === 'anual' ? 'Anual (completo)' : ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'][parseInt(m)-1],
            habilitado: y === 2026 ? 0 : 1 // 2026 deshabilitado por defecto
          });
        });
      });
      localStorage.setItem('transparencia_periodos', JSON.stringify(defaultPeriods));
      return defaultPeriods;
    }
  },

  // Dynamic navbar injection for Admin user role
  injectAdminNavbar() {
    try {
      const session = this.getUserSession();
      if (session && session.user && session.user.rol === 'admin') {
        const navCollapse = document.getElementById('navMain') || document.querySelector('.navbar-collapse');
        if (!navCollapse) return;

        if (document.getElementById('nav-admin-link')) return;

        const mainNavList = navCollapse.querySelector('ul.navbar-nav.mr-auto') || navCollapse.querySelector('ul.navbar-nav');
        if (mainNavList) {
          const li = document.createElement('li');
          li.className = 'nav-item';
          li.id = 'nav-admin-link';
          
          const path = window.location.pathname;
          const href = path.includes('/pages/') ? 'admin.html' : 'pages/admin.html';
          
          li.innerHTML = `
            <a class="nav-link" href="${href}" style="font-weight:700;color:#38bdf8 !important;">
              <span class="material-icons mr-1" style="font-size:16px;vertical-align:middle">admin_panel_settings</span> Administración
            </a>
          `;
          mainNavList.appendChild(li);
        }
      }
    } catch (e) {
      console.warn("No se pudo inyectar el link de administración en el navbar:", e);
    }
  },

  // Initialize page
  init() {
    this.initA11y();
    this.initScrollAnimations();
    this.injectAdminNavbar();
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
