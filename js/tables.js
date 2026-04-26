/* ============================================
   tables.js — Motor de Tablas Dinámicas
   Portal Transparencia Santo Domingo
   ============================================ */

class DynamicTable {
  constructor(containerId, options = {}) {
    this.container = document.getElementById(containerId);
    if (!this.container) return;
    this.data = options.data || [];
    this.columns = options.columns || [];
    this.pageSize = options.pageSize || 10;
    this.currentPage = 1;
    this.sortColumn = null;
    this.sortDirection = 'asc';
    this.searchTerm = '';
    this.filters = {};
    this.filteredData = [...this.data];
    this.onRowClick = options.onRowClick || null;
    this.render();
  }

  formatCurrency(value) {
    if (value == null) return '-';
    return '$' + new Intl.NumberFormat('es-CL').format(value);
  }

  formatDate(dateStr) {
    if (!dateStr) return '-';
    const d = new Date(dateStr + 'T00:00:00');
    return d.toLocaleDateString('es-CL', { day: '2-digit', month: '2-digit', year: 'numeric' });
  }

  formatPercent(value) {
    if (value == null) return '-';
    return value.toFixed(1) + '%';
  }

  applyFiltersAndSearch() {
    let result = [...this.data];
    // Search
    if (this.searchTerm) {
      const term = this.searchTerm.toLowerCase();
      result = result.filter(row =>
        this.columns.some(col => {
          const val = row[col.key];
          return val != null && String(val).toLowerCase().includes(term);
        })
      );
    }
    // Filters
    Object.keys(this.filters).forEach(key => {
      const filterVal = this.filters[key];
      if (filterVal && filterVal !== '__all__') {
        result = result.filter(row => String(row[key]) === filterVal);
      }
    });
    // Sort
    if (this.sortColumn) {
      const col = this.columns.find(c => c.key === this.sortColumn);
      result.sort((a, b) => {
        let aVal = a[this.sortColumn];
        let bVal = b[this.sortColumn];
        if (col && (col.type === 'currency' || col.type === 'number' || col.type === 'percent')) {
          aVal = Number(aVal) || 0;
          bVal = Number(bVal) || 0;
        } else {
          aVal = String(aVal || '').toLowerCase();
          bVal = String(bVal || '').toLowerCase();
        }
        if (aVal < bVal) return this.sortDirection === 'asc' ? -1 : 1;
        if (aVal > bVal) return this.sortDirection === 'asc' ? 1 : -1;
        return 0;
      });
    }
    this.filteredData = result;
    this.currentPage = 1;
  }

  getPageData() {
    const start = (this.currentPage - 1) * this.pageSize;
    return this.filteredData.slice(start, start + this.pageSize);
  }

  getTotalPages() {
    return Math.ceil(this.filteredData.length / this.pageSize) || 1;
  }

  renderCell(row, col) {
    const val = row[col.key];
    if (col.render) return col.render(val, row);
    switch (col.type) {
      case 'currency': return `<td class="currency">${this.formatCurrency(val)}</td>`;
      case 'date': return `<td>${this.formatDate(val)}</td>`;
      case 'percent': return `<td>${this.formatPercent(val)}</td>`;
      case 'status':
        const cls = val === 'Completado' || val === 'Vigente' ? 'tag-completado'
          : val === 'En Ejecución' || val === 'En Proceso' ? 'tag-ejecucion' : 'tag-archivado';
        return `<td><span class="tag ${cls}">${val || '-'}</span></td>`;
      case 'progress':
        const pct = Number(val) || 0;
        const pColor = pct >= 100 ? '#52B788' : pct >= 50 ? '#F77F00' : '#E8433E';
        return `<td><div class="d-flex align-items-center gap-2"><div class="progress-bar-custom" style="width:80px"><div class="fill" style="width:${pct}%;background:${pColor}"></div></div><span style="font-size:0.75rem">${pct}%</span></div></td>`;
      default: return `<td>${val != null ? val : '-'}</td>`;
    }
  }

  render() {
    const pageData = this.getPageData();
    const totalPages = this.getTotalPages();
    const start = (this.currentPage - 1) * this.pageSize + 1;
    const end = Math.min(this.currentPage * this.pageSize, this.filteredData.length);

    let html = `
      <div class="table-container">
        <div class="table-toolbar">
          <input type="text" class="search-input" placeholder="Buscar..." value="${this.searchTerm}" id="${this.container.id}-search">
          <div class="d-flex align-items-center gap-2">
            <select class="form-control form-control-sm" id="${this.container.id}-pagesize" style="width:auto;font-size:0.8rem">
              <option value="10" ${this.pageSize===10?'selected':''}>10</option>
              <option value="25" ${this.pageSize===25?'selected':''}>25</option>
              <option value="50" ${this.pageSize===50?'selected':''}>50</option>
            </select>
            <button class="btn btn-sm btn-outline-primary" onclick="window._tables['${this.container.id}'].exportCSV()" title="Exportar CSV">
              <span class="material-icons" style="font-size:1rem">download</span> CSV
            </button>
          </div>
        </div>
        <div style="overflow-x:auto">
          <table class="dynamic-table">
            <thead><tr>`;
    
    this.columns.forEach(col => {
      const sorted = this.sortColumn === col.key;
      const icon = sorted ? (this.sortDirection === 'asc' ? '▲' : '▼') : '⇅';
      html += `<th class="${sorted?'sorted':''}" data-key="${col.key}">${col.label}<span class="sort-icon">${icon}</span></th>`;
    });

    html += `</tr></thead><tbody>`;
    
    if (pageData.length === 0) {
      html += `<tr><td colspan="${this.columns.length}" style="text-align:center;padding:30px;color:#999">No se encontraron resultados</td></tr>`;
    } else {
      pageData.forEach((row, i) => {
        html += `<tr class="animate-in" style="animation-delay:${i*0.03}s" ${this.onRowClick ? `onclick="window._tables['${this.container.id}'].onRowClick(${JSON.stringify(row).replace(/"/g,'&quot;')})"` : ''}>`;
        this.columns.forEach(col => { html += this.renderCell(row, col); });
        html += `</tr>`;
      });
    }

    html += `</tbody></table></div>
      <div class="table-pagination">
        <span>Mostrando ${this.filteredData.length>0?start:0}-${end} de ${this.filteredData.length} registros</span>
        <div>`;
    
    if (totalPages > 1) {
      html += `<button class="page-btn" ${this.currentPage===1?'disabled':''} data-page="${this.currentPage-1}">‹</button>`;
      for (let i = 1; i <= Math.min(totalPages, 7); i++) {
        html += `<button class="page-btn ${this.currentPage===i?'active':''}" data-page="${i}">${i}</button>`;
      }
      if (totalPages > 7) html += `<span>...</span><button class="page-btn" data-page="${totalPages}">${totalPages}</button>`;
      html += `<button class="page-btn" ${this.currentPage===totalPages?'disabled':''} data-page="${this.currentPage+1}">›</button>`;
    }

    html += `</div></div></div>`;
    this.container.innerHTML = html;
    this.bindEvents();
  }

  bindEvents() {
    const searchInput = document.getElementById(`${this.container.id}-search`);
    if (searchInput) {
      let timeout;
      searchInput.addEventListener('input', (e) => {
        clearTimeout(timeout);
        timeout = setTimeout(() => {
          this.searchTerm = e.target.value;
          this.applyFiltersAndSearch();
          this.render();
        }, 300);
      });
    }
    const pageSizeSelect = document.getElementById(`${this.container.id}-pagesize`);
    if (pageSizeSelect) {
      pageSizeSelect.addEventListener('change', (e) => {
        this.pageSize = parseInt(e.target.value);
        this.currentPage = 1;
        this.render();
      });
    }
    this.container.querySelectorAll('thead th').forEach(th => {
      th.addEventListener('click', () => {
        const key = th.dataset.key;
        if (this.sortColumn === key) {
          this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
          this.sortColumn = key;
          this.sortDirection = 'asc';
        }
        this.applyFiltersAndSearch();
        this.render();
      });
    });
    this.container.querySelectorAll('.page-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        const page = parseInt(btn.dataset.page);
        if (page >= 1 && page <= this.getTotalPages()) {
          this.currentPage = page;
          this.render();
        }
      });
    });
  }

  setFilter(key, value) {
    this.filters[key] = value;
    this.applyFiltersAndSearch();
    this.render();
  }

  exportCSV() {
    const headers = this.columns.map(c => c.label).join(',');
    const rows = this.filteredData.map(row =>
      this.columns.map(col => {
        let val = row[col.key];
        if (typeof val === 'string' && val.includes(',')) val = `"${val}"`;
        return val != null ? val : '';
      }).join(',')
    );
    const csv = '\uFEFF' + headers + '\n' + rows.join('\n');
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `transparencia_${Date.now()}.csv`;
    a.click();
    URL.revokeObjectURL(url);
  }

  updateData(newData) {
    this.data = newData;
    this.applyFiltersAndSearch();
    this.render();
  }
}

// Global registry
window._tables = window._tables || {};
