/* ============================================
   budget-engine.js — Motor de Cálculo Presupuestario Centralizado
   Portal Transparencia Santo Domingo
   ============================================
   
   Réplica exacta de ApiController::calcularPresupuestoDinamico()
   para garantizar paridad de resultados entre modo Online (API Laravel)
   y modo Offline (localStorage / archivos estáticos).
   
   IMPORTANTE: Cualquier cambio en la fórmula del backend PHP debe
   replicarse aquí para mantener la coherencia.
   ============================================ */

const BudgetEngine = {

  // ─── Utilidades ──────────────────────────────────────────

  /**
   * Limpia y sanitiza un valor monetario en formato string.
   * Equivalente exacto de ApiController::cleanInt()
   * Maneja formatos como "$450.000", "520.000", "1,200,000", etc.
   * @param {*} v - Valor a limpiar
   * @returns {number} Entero limpio, o 0 si no es numérico
   */
  cleanVal(v) {
    return parseInt(String(v || '').replace(/[^\d-]/g, ''), 10) || 0;
  },

  /**
   * Formatea un número como moneda chilena (CLP).
   * @param {number} n - Valor numérico
   * @returns {string} Valor formateado (ej: "$1.234.567")
   */
  formatCLP(n) {
    return '$' + new Intl.NumberFormat('es-CL').format(n);
  },

  // ─── Acceso a Datos de localStorage ──────────────────────

  /**
   * Obtiene y parsea datos del localStorage de forma segura.
   * @param {string} key - Clave del localStorage
   * @returns {Array|Object|null} Datos parseados o null
   */
  _getStorage(key) {
    try {
      const raw = localStorage.getItem(key);
      if (!raw) return null;
      return JSON.parse(raw);
    } catch (e) {
      console.warn(`BudgetEngine: Error leyendo localStorage[${key}]:`, e);
      return null;
    }
  },

  /**
   * Obtiene el padrón de contribuyentes del localStorage.
   * @returns {Array} Array de contribuyentes (puede estar vacío)
   */
  getContribuyentes() {
    return this._getStorage('transparencia_carga_contribuyentes') || [];
  },

  /**
   * Busca un contribuyente por RUT limpio en el padrón local.
   * @param {string} cleanRut - RUT sin puntos ni guiones, en minúsculas
   * @returns {Object|null} Datos del contribuyente o null
   */
  getContribuyenteFromPadron(cleanRut) {
    const padron = this.getContribuyentes();
    return padron.find(c => {
      const cRut = (c.rut || '').replace(/[^0-9kK]/g, '').toLowerCase();
      return cRut === cleanRut;
    }) || null;
  },

  /**
   * Obtiene los metadatos generales del localStorage.
   * @returns {Object|null} Primera fila de metadata o null
   */
  getMetadata() {
    const meta = this._getStorage('transparencia_carga_metadata');
    if (Array.isArray(meta) && meta.length > 0) return meta[0];
    return meta;
  },

  /**
   * Obtiene los gastos por área del localStorage.
   * @returns {Array} Array de gastos por área (puede estar vacío)
   */
  getGastosAreas() {
    return this._getStorage('transparencia_carga_gastos') || [];
  },

  /**
   * Obtiene los proyectos del localStorage.
   * @returns {Array} Array de proyectos (puede estar vacío)
   */
  getProyectos() {
    return this._getStorage('transparencia_carga_proyectos') || [];
  },

  /**
   * Obtiene los servicios del localStorage.
   * @returns {Array} Array de servicios (puede estar vacío)
   */
  getServicios() {
    return this._getStorage('transparencia_carga_servicios') || [];
  },

  // ─── Motor de Cálculo Central ────────────────────────────

  /**
   * Calcula dinámicamente el presupuesto municipal (ingresos y gastos)
   * basándose en las contribuciones reales de los vecinos escaladas.
   * 
   * RÉPLICA EXACTA de ApiController::calcularPresupuestoDinamico()
   * 
   * @param {number} factorEscala - Factor de escalado (default: 2000)
   * @returns {Object} Estructura con real, escalado, ingresos, gastos
   */
  calcularPresupuestoDinamico(factorEscala = 2000) {
    const padron = this.getContribuyentes();
    const cv = this.cleanVal;

    // 1. Obtener sumatoria de aportes de vecinos reales
    let sumContribReal = 0;
    let sumCircReal = 0;
    let sumAseoReal = 0;
    let cantidadContribuyentes = 0;

    if (padron.length > 0) {
      padron.forEach(c => {
        sumContribReal += cv(c.aporte_contribucion);
        sumCircReal += cv(c.aporte_circulacion);
        sumAseoReal += cv(c.aporte_aseo);
      });
      cantidadContribuyentes = padron.length;
    }

    let totalVecinosReal = sumContribReal + sumCircReal + sumAseoReal;

    // Si el padrón está vacío, usar valores semilla (Alonso + Sofía)
    if (totalVecinosReal === 0) {
      sumContribReal = 485000 + 3500000;
      sumCircReal = 165000 + 1200000;
      sumAseoReal = 78000 + 300000;
      totalVecinosReal = sumContribReal + sumCircReal + sumAseoReal;
      cantidadContribuyentes = 2;
    }

    // 2. Escalado Inicial Realista
    const aporteContribucionTotal = sumContribReal * factorEscala;
    const aporteCirculacionTotal = sumCircReal * factorEscala;
    const aporteAseoTotal = sumAseoReal * factorEscala;
    const totalVecinosEscalado = totalVecinosReal * factorEscala;

    // 3. Proporción de Ingresos (Cálculo Inverso):
    // El aporte escalado de vecinos representa el 30% del Presupuesto Total (T)
    // T = V_escalado / 0.30
    const presupuestoTotalIngresos = Math.round(totalVecinosEscalado / 0.30);

    // El 70% restante se desglosa en rubros realistas:
    // FCM: 45%, Patentes: 15%, Otros: 10%
    let fcmTotal = Math.round(presupuestoTotalIngresos * 0.45);
    const patentesTotal = Math.round(presupuestoTotalIngresos * 0.15);
    const otrosIngresosTotal = Math.round(presupuestoTotalIngresos * 0.10);

    // Ajuste fino para sumar exactamente el presupuesto total (absorber redondeos en FCM)
    const sumaIngresosCalculados = totalVecinosEscalado + fcmTotal + patentesTotal + otrosIngresosTotal;
    const diferenciaIngresos = presupuestoTotalIngresos - sumaIngresosCalculados;
    if (diferenciaIngresos !== 0) {
      fcmTotal += diferenciaIngresos;
    }

    // 4. Coherencia Ingresos-Gastos:
    // Gastos = 92% de Ingresos (8% superávit)
    const presupuestoTotalGastos = Math.round(presupuestoTotalIngresos * 0.92);
    const superavit = presupuestoTotalIngresos - presupuestoTotalGastos;

    return {
      real: {
        aporte_contribucion: sumContribReal,
        aporte_circulacion: sumCircReal,
        aporte_aseo: sumAseoReal,
        total_vecinos: totalVecinosReal,
      },
      escalado: {
        aporte_contribucion: aporteContribucionTotal,
        aporte_circulacion: aporteCirculacionTotal,
        aporte_aseo: aporteAseoTotal,
        total_vecinos: totalVecinosEscalado,
      },
      ingresos: {
        total: presupuestoTotalIngresos,
        fcm: fcmTotal,
        patentes: patentesTotal,
        otros: otrosIngresosTotal,
      },
      gastos: {
        total: presupuestoTotalGastos,
        superavit: superavit,
      },
      cantidad_contribuyentes: cantidadContribuyentes,
      factor_escala: factorEscala,
    };
  },

  // ─── Generadores de Datos Derivados ──────────────────────

  /**
   * Genera los 6 ítems de recaudación con montos y porcentajes.
   * Equivalente a la generación de $recaudacionItems en getDestinoImpuestos()
   * 
   * @param {Object} calc - Resultado de calcularPresupuestoDinamico()
   * @returns {Array} Ítems de recaudación
   */
  getRecaudacionItems(calc) {
    const T = calc.ingresos.total;
    const pct = (monto) => T > 0 ? parseFloat(((monto / T) * 100).toFixed(2)) : 0;

    return [
      { nombre: 'Impuesto Territorial', monto: calc.escalado.aporte_contribucion, porcentaje: pct(calc.escalado.aporte_contribucion) },
      { nombre: 'Permisos de Circulación', monto: calc.escalado.aporte_circulacion, porcentaje: pct(calc.escalado.aporte_circulacion) },
      { nombre: 'Derechos de Aseo', monto: calc.escalado.aporte_aseo, porcentaje: pct(calc.escalado.aporte_aseo) },
      { nombre: 'Fondo Común Municipal', monto: calc.ingresos.fcm, porcentaje: 45.00 },
      { nombre: 'Patentes Municipales', monto: calc.ingresos.patentes, porcentaje: 15.00 },
      { nombre: 'Otros Ingresos', monto: calc.ingresos.otros, porcentaje: 10.00 },
    ];
  },

  /**
   * Escala un array de gastos por área proporcionalmente al nuevo presupuesto.
   * Equivalente a la lógica de escalado del frontend en presupuesto.html
   * 
   * @param {Object} calc - Resultado de calcularPresupuestoDinamico()
   * @param {Array} baseItems - Ítems base de gastos con {area, asignado, ejecutado, pctEjec}
   * @returns {Array} Ítems escalados
   */
  getGastosEscalados(calc, baseItems) {
    const totalGastosTarget = calc.gastos.total;
    const currentSum = baseItems.reduce((s, it) => s + it.asignado, 0);
    if (currentSum <= 0) return baseItems;

    const scaleRatio = totalGastosTarget / currentSum;
    return baseItems.map(it => ({
      area: it.area,
      asignado: Math.round(it.asignado * scaleRatio),
      ejecutado: Math.round(it.ejecutado * scaleRatio),
      pctEjec: it.pctEjec,
    }));
  },

  /**
   * Genera ítems de presupuesto por área desde el localStorage de gastos offline.
   * Incluye porcentajes de ejecución estándar por área.
   * 
   * @returns {Array|null} Ítems de presupuesto o null si no hay datos offline
   */
  getPresupuestoFromOfflineGastos() {
    const gastos = this.getGastosAreas();
    if (!gastos || gastos.length === 0) return null;

    const pctEjecMap = {
      'Educación': 92.6,
      'Salud': 95.0,
      'Seguridad Ciudadana': 95.0,
      'Obras Municipales': 87.9,
      'Servicios Comunitarios': 94.0,
      'Medio Ambiente': 95.0,
      'Cultura y Deporte': 89.9,
      'Administración': 77.0,
    };

    return gastos.map(row => {
      const asig = this.cleanVal(row.monto_asignado);
      const pct = pctEjecMap[row.area] || 92.6;
      return {
        area: row.area,
        asignado: asig,
        ejecutado: Math.round(asig * pct / 100),
        pctEjec: pct,
      };
    });
  },

  /**
   * Datos fallback estáticos de presupuesto por área.
   * Idénticos a los del backend ApiController::getPresupuesto() fallback.
   * 
   * @returns {Array} Ítems estáticos de presupuesto
   */
  getPresupuestoFallback() {
    return [
      { area: 'Educación', asignado: 3576000000, ejecutado: 3312000000, pctEjec: 92.6 },
      { area: 'Salud', asignado: 2384000000, ejecutado: 2265000000, pctEjec: 95.0 },
      { area: 'Seguridad Ciudadana', asignado: 1430000000, ejecutado: 1358000000, pctEjec: 95.0 },
      { area: 'Obras Municipales', asignado: 1192000000, ejecutado: 1048000000, pctEjec: 87.9 },
      { area: 'Servicios Comunitarios', asignado: 952000000, ejecutado: 895000000, pctEjec: 94.0 },
      { area: 'Medio Ambiente', asignado: 714000000, ejecutado: 678000000, pctEjec: 95.0 },
      { area: 'Cultura y Deporte', asignado: 595000000, ejecutado: 535000000, pctEjec: 89.9 },
      { area: 'Administración', asignado: 1077000000, ejecutado: 829000000, pctEjec: 77.0 },
    ];
  },

  /**
   * Prepara los datos completos de un contribuyente para la sesión offline.
   * Lee del padrón local y aplica los valores por defecto si no se encuentra.
   * 
   * @param {string} cleanRut - RUT limpio
   * @param {Object} defaults - Valores por defecto {nombre, rut, contrib, circ, aseo, mensual}
   * @returns {Object} Datos de sesión del contribuyente
   */
  buildContribuyenteSession(cleanRut, defaults) {
    const entry = this.getContribuyenteFromPadron(cleanRut);
    let aCont, aCirc, aAseo, nombre;

    if (entry) {
      aCont = this.cleanVal(entry.aporte_contribucion);
      aCirc = this.cleanVal(entry.aporte_circulacion);
      aAseo = this.cleanVal(entry.aporte_aseo);
      nombre = entry.nombre || defaults.nombre;
    } else {
      aCont = defaults.contrib;
      aCirc = defaults.circ;
      aAseo = defaults.aseo;
      nombre = defaults.nombre;
    }

    const total = aCont + aCirc + aAseo;
    const mes = Math.round(total / 12);

    return {
      nombre: nombre,
      rut: defaults.rut,
      recaudacionTotalUsuario: total,
      detalles: { contribucion: aCont, circulacion: aCirc, aseo: aAseo },
      mensual: entry ? Array(12).fill(mes) : (defaults.mensual || Array(12).fill(mes)),
    };
  },
};
