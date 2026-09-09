/**
 * TableHelper - Vanilla JS table builder with AJAX, filtering, and pagination
 * Optimized for minimal blade file code
 *
 * Optional config for rowspan/colspan:
 * - headerRows: array of header rows for multi-row/grouped headers. Each row is an array of cells.
 *   Each cell: { label: string, colspan?: number, rowspan?: number } (defaults 1).
 *   Example: headerRows: [
 *     [ { label: 'Name' }, { label: 'Timeout', colspan: 2 }, { label: 'Collapse', colspan: 2 }, { label: 'Booked' } ],
 *     [ { label: 'Operator' }, { label: 'Customer' }, { label: 'Operator' }, { label: 'Customer' } ]
 *   ]
 * - columns[].colspan: number or function(rowData, rowIndex) - cell colspan in tbody
 * - columns[].rowspan: number or function(rowData, rowIndex) - cell rowspan in tbody
 */
function th_escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, (c) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    })[c]);
}

function th_debounce(fn, wait = 500) {
    let t;
    return (...args) => {
        clearTimeout(t);
        t = setTimeout(() => fn(...args), wait);
    };
}
class TableHelper {
    constructor(config) {
        this.config = {
            containerId: config.containerId || 'table-container',
            apiUrl: config.apiUrl,
            columns: this._normalizeColumns(config.columns || []),
            perPage: config.perPage || 10,
            perPageOptions: config.perPageOptions || [10, 25, 50, 100],  // Options for rows per page dropdown
            filters: config.filters || {},
            onRowClick: config.onRowClick || null,
            onDataLoaded: config.onDataLoaded || null,
            emptyMessage: config.emptyMessage || 'No matching records found',
            autoInitDatePickers: config.autoInitDatePickers !== false,
            maxVisibleRows: config.maxVisibleRows || 10,  // Show up to 10 rows without scrolling
            dateRangeDefaults: config.dateRangeDefaults || null,  // Default date range config
            ...config
        };

        this.currentPage = 1;
        this.totalPages = 1;
        this.totalRecords = 0;
        this.gridData = [];
        this.filterValues = {};
        this.searchTerm = '';
        this.columnFilterValues = {}; // Store column filter values like search stores searchTerm
        this.selectedRowId = null;
        this._autoGenerateCompleted = false; // Track if auto-generation already ran
        this._dateRangeInitialized = false; // Track if default date ranges have been applied

        // Sorting state
        this.sortField = null;
        this.sortDirection = null;

        // Checkbox selection for bulk actions
        this.selectedRows = new Set(); // Track selected row IDs
        this.dynamicCheckbox = config.dynamicCheckbox === true;
        this._enableCheckboxResolver = typeof config.enableCheckbox === 'function' ? config.enableCheckbox : null;
        this.enableCheckbox = config.enableCheckbox !== false; // Standard boolean behavior remains the default
        this.bulkActions = config.bulkActions || []; // Array of bulk action configs
        this.onSelectionChange = config.onSelectionChange || null; // Callback when selection changes

        this._normalizedColumnFilters = this._normalizeColumnFilters(
            (this.config.filters && this.config.filters.columnFilters) || []
        );

        // Totals configuration
        this.totals = config.totals || null;
        this.totalsCached = null;
        this.totalsLoading = false;
        this._lastRenderedCheckboxEnabled = null;

        this.init();
    }

    _isCheckboxEnabled() {
        if (this.dynamicCheckbox && this._enableCheckboxResolver) {
            try {
                return this._enableCheckboxResolver(this) !== false;
            } catch (error) {
                console.warn('TableHelper enableCheckbox function failed:', error);
                return false;
            }
        }
        return this.enableCheckbox !== false;
    }

    static ajax(url, options = {}) {
        const {
            method = 'GET',
            data = {},
            headers = {},
            useJQuery = true,
            showLoader = false,
            showErrorAlert = true,
            success,
            error
        } = options;

        const getCsrfToken = () => {
            const meta = document.querySelector('meta[name="csrf-token"]');
            return meta ? meta.getAttribute('content') : '';
        };

        const defaultHeaders = {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': getCsrfToken(),
            'Accept': 'application/json',
            ...headers
        };

        const handleSuccess = (response) => {
            if (success) success(response);
            return response;
        };

        const handleError = (err) => {
            const errorMessage = err?.message || err?.responseJSON?.message || 'An error occurred';
            if (showErrorAlert && typeof $ !== 'undefined' && $.toast) {
                $.toast({ heading: 'Error', text: errorMessage, icon: 'error', position: 'top-right', loader: false, hideAfter: 3000 });
            }
            if (error) error(err);
            throw err;
        };

        if (useJQuery && typeof $ !== 'undefined' && $.ajax) {
            return new Promise((resolve, reject) => {
                $.ajax({
                    url,
                    method,
                    data: method === 'GET' ? data : JSON.stringify(data),
                    headers: defaultHeaders,
                    success: (response) => { try { resolve(handleSuccess(response)); } catch (e) { reject(e); } },
                    error: (xhr) => {
                        try {
                            handleError({
                                ...xhr,
                                responseJSON: xhr.responseJSON,
                                message: xhr.responseJSON?.message || xhr.statusText
                            });
                            reject(xhr);
                        } catch (e) { reject(e); }
                    }
                });
            });
        }

        const fetchOptions = { method, headers: defaultHeaders };
        if (method !== 'GET' && Object.keys(data).length > 0) {
            fetchOptions.body = JSON.stringify(data);
        }

        fetchOptions.credentials = 'same-origin';

        return fetch(url, fetchOptions)
            .then(response => {
                if (!response.ok) {
                    return response.json().then(err => {
                        throw {
                            status: response.status,
                            statusText: response.statusText,
                            responseJSON: err,
                            message: err.message || response.statusText
                        };
                    });
                }
                return response.json();
            })
            .then(handleSuccess)
            .catch(handleError);
    }

    static loadDropdown(selectId, url, options = {}) {
        const {
            valueField = 'id',
            textField = 'name',
            formatter,
            placeholder = '',
            placeholderValue = '',
            prependOptions = [],
            select2 = false,
            select2Options = {},
            useJQuery = true,
            onSuccess,
            onError
        } = options;

        const select = typeof selectId === 'string' ? document.getElementById(selectId) : selectId;
        if (!select) {
            console.warn(`Dropdown element #${selectId} not found`);
            return Promise.reject(new Error(`Element #${selectId} not found`));
        }

        if (placeholder) {
            select.innerHTML = `<option value="${placeholderValue}">${placeholder}</option>`;
        }

        return TableHelper.ajax(url, {
            method: 'GET',
            useJQuery,
            showErrorAlert: false
        })
            .then(response => {
                const data = response.data || response;
                if (!Array.isArray(data)) throw new Error('Expected array response');

                // Build prepend options
                const prependOptionsHtml = prependOptions.map(opt =>
                    `<option value="${opt.value}"${opt.selected ? ' selected' : ''}>${opt.text}</option>`
                ).join('');

                // Build data options
                const dataOptions = data.map(item => {
                    if (formatter) return formatter(item);
                    const value = item[valueField];
                    const text = item[textField];
                    return `<option value="${value}">${text}</option>`;
                }).join('');

                // Combine all options
                const allOptions = [
                    placeholder ? `<option value="${placeholderValue}">${placeholder}</option>` : '',
                    prependOptionsHtml,
                    dataOptions
                ].filter(Boolean).join('');

                select.innerHTML = allOptions;

                if (select2 && typeof $ !== 'undefined' && $.fn.select2) {
                    $(select).select2({
                        placeholder: placeholder || 'Select...',
                        allowClear: true,
                        width: '100%',
                        ...select2Options
                    });
                }

                onSuccess?.(data, select);
                return { data, select };
            })
            .catch(error => {
                console.error(`Error loading dropdown #${selectId}:`, error);
                onError?.(error, select);
                throw error;
            });
    }

    _normalizeColumns(columns) {
        if (!Array.isArray(columns)) return [];
        return columns.map((col, index) => {
            if (typeof col === 'object' && col !== null && (col.name || col.field || col.render)) return col;
            if (typeof col === 'string') {
                const parts = col.split('|');
                if (parts.length === 2) return { name: parts[0].trim(), field: parts[1].trim() };
                const fieldName = col.replace(/([A-Z])/g, ' $1').trim();
                const camelCase = col.charAt(0).toLowerCase() + col.slice(1);
                return { name: fieldName, field: camelCase };
            }
            return { name: `Column ${index + 1}`, field: `col${index}` };
        });
    }

    _normalizeColumnFilters(columnFilters) {
        if (!Array.isArray(columnFilters)) return [];
        return columnFilters.map((f, i) => {
            const out = { type: 'text', ...f };
            if (!out.param) out.param = out.field || `col_${out.column ?? i}`;
            if (!out.id) out.id = `${this.config.containerId}-cf-${out.param}`;
            if (out.column == null && out.field) {
                const idx = (this.config.columns || []).findIndex(c => c.field === out.field);
                out.column = idx >= 0 ? idx : null;
            }
            return out;
        });
    }

    _autoInitDatePickers() {
        if (!this.config.autoInitDatePickers) return;
        const dateInputs = document.querySelectorAll('.nepali-datepicker, [id*="datepicker"], [id*="date-picker"]');
        
        // Save existing values before reinitializing
        const existingValues = {};
        dateInputs.forEach(input => {
            if (input.id || input.name) {
                const key = input.id || input.name;
                existingValues[key] = input.value;
            }
        });
        
        dateInputs.forEach(input => {
            if (!input.nepaliDatePicker) return;
            const todayBS = this._getTodayBS();

            input.nepaliDatePicker({
                ndpYear: true, ndpMonth: true, ndpYearCount: 10,
                ndpTriggerButton: true, ndpTriggerButtonText: "📅",
                ndpEnglishInput: "nepali-datepicker-en",
                miniEnglishDates: true
            });
            
            // Restore existing value if it was set, otherwise use today's date
            const key = input.id || input.name;
            if (existingValues[key]) {
                input.value = existingValues[key];
            } else if (!input.value) {
                input.value = todayBS;
            }
        });
    }

    _getTodayBS() {
        // Don't call initNepaliDatepickers() as it resets all datepicker values
        // Calculate today's Nepali date directly instead
        if (typeof NepaliFunctions !== 'undefined' && NepaliFunctions.BS && NepaliFunctions.BS.GetCurrentDate) {
            const today = NepaliFunctions.BS.GetCurrentDate();
            return `${today.year}-${String(today.month).padStart(2, '0')}-${String(today.day).padStart(2, '0')}`;
        }
        // Fallback to AD date if NepaliFunctions not available
        const today = new Date();
        return `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;
    }

    _showAlert(icon, title, text) {
        if (typeof $ !== 'undefined' && $.toast) {
            $.toast({ heading: title, text: text, icon: icon, position: 'top-right' });
        } else {
            alert(`${title}: ${text}`);
        }
    }

    /**
     * Show animated loading spinner overlay on table
     */
    _showTableLoader(message = 'Fetching data...') {
        const container = document.getElementById(this.config.containerId);
        if (!container) return;

        // Create wrapper if it doesn't exist
        const wrapper = container.parentNode;
        if (!wrapper.classList.contains('table-container-wrapper')) {
            wrapper.classList.add('table-container-wrapper');
            wrapper.style.position = 'relative';
        }

        // Remove existing loader if present
        this._hideTableLoader();

        // Create loader overlay
        const loader = document.createElement('div');
        loader.id = `${this.config.containerId}-loader`;
        loader.className = 'table-loader-overlay';
        loader.innerHTML = `
            <div class="table-loader-spinner">
                <div class="th-loader-dots">
                    <div class="th-loader-dot"></div>
                    <div class="th-loader-dot"></div>
                    <div class="th-loader-dot"></div>
                </div>
                <div class="loader-text">${message}</div>
            </div>
        `;

        wrapper.appendChild(loader);
    }

    /**
     * Hide loading spinner overlay
     */
    _hideTableLoader() {
        const loader = document.getElementById(`${this.config.containerId}-loader`);
        if (loader) {
            loader.remove();
        }
    }

    /**
     * Inject sticky header CSS styles, dynamic height calculation, and responsive design
     * Makes table headers stay visible when scrolling vertically
     * Adjusts table height based on rows per page
     * Makes table responsive on mobile devices (<768px)
     */
    _injectStickyHeaderStyles() {
        // Check if styles already injected
        if (document.getElementById('table-helper-sticky-styles')) {
            return;
        }

        const styleId = 'table-helper-sticky-styles';
        const style = document.createElement('style');
        style.id = styleId;
        style.innerHTML = `
            /* Sticky header for table - Desktop */
            .table-sticky-header {
                width: 100%;
                border-collapse: collapse;
                min-width: 800px;
            }
            
            .table-sticky-header thead {
                position: sticky;
                top: 0;
                z-index: 10;
                background-color: #f8f9fa;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            }
            
            .table-sticky-header thead th {
                padding: 12px 8px;
                font-weight: 600;
                color: #333;
                background-color: #f8f9fa;
                border-bottom: 2px solid #dee2e6;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
            
            .table-responsive-sticky {
                overflow-y: visible;
                overflow-x: auto;
                border: 1px solid #dee2e6;
                border-radius: 0.25rem;
                width: 100%;
            }
            
            .table-responsive-sticky table {
                margin-bottom: 0;
                width: 100%;
            }
            
            .table-sticky-header tbody tr {
                height: 40px;
                background-color: #fff;
            }
            
            .table-sticky-header tbody tr:nth-child(even) {
                background-color: #f9f9f9;
            }
            
            .table-sticky-header tbody tr:hover {
                background-color: #f5f5f5;
            }

            .table-sticky-header tbody td {
                border-bottom: 1px solid #dee2e6;
                padding: 8px 6px;
                vertical-align: middle;
            }

            /* Custom table dropdown - renders fixed to viewport, never clipped */
            .th-dropdown-btn {
                cursor: pointer;
            }
            .th-dropdown-menu {
                display: none;
                position: fixed;
                z-index: 1000;
                background: #fff;
                border: 1px solid #dee2e6;
                border-radius: 0.375rem;
                box-shadow: 0 0.5rem 1rem rgba(0,0,0,.15);
                padding: 0.25rem 0;
                min-width: 10rem;
                list-style: none;
                margin: 0;
            }
            .th-dropdown-menu.show {
                display: block;
            }
            .th-dropdown-menu li a {
                display: block;
                padding: 0.35rem 1rem;
                color: #212529;
                text-decoration: none;
                white-space: nowrap;
                font-size: 0.875rem;
            }
            .th-dropdown-menu li a:hover {
                background-color: #f8f9fa;
            }

            /* Loading Spinner Styles */
            .table-loader-overlay {
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(248, 250, 252, 0.88);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 1000;
                border-radius: 8px;
            }

            .table-loader-spinner {
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 12px;
            }

            .th-loader-dots {
                display: flex;
                gap: 8px;
                align-items: center;
            }

            .th-loader-dot {
                width: 8px;
                height: 8px;
                border-radius: 50%;
                background: #1a3a6b;
                animation: th-dot-bounce 1.2s ease-in-out infinite;
            }

            .th-loader-dot:nth-child(1) { animation-delay: 0s; }
            .th-loader-dot:nth-child(2) { animation-delay: 0.2s; }
            .th-loader-dot:nth-child(3) { animation-delay: 0.4s; }

            @keyframes th-dot-bounce {
                0%, 60%, 100% { transform: translateY(0); opacity: 0.45; }
                30% { transform: translateY(-8px); opacity: 1; }
            }

            .loader-text {
                color: #64748b;
                font-weight: 500;
                font-size: 13px;
                letter-spacing: 0.02em;
            }

            .table-container-wrapper {
                position: relative;
                display: inline-block;
                width: 100%;
            }

            /* Sorting Indicators - Modern FontAwesome Icons (via markup) */
            .sort-indicator {
                display: inline-flex;
                align-items: center;
                margin-left: 8px; /* space after text */
                color: #94a3b8; /* slate-400 visible */
                transition: all 0.2s ease;
            }

            .sortable-header:hover .sort-indicator {
                color: #64748b; /* slate-500 hover */
            }

            .sortable-header .sort-indicator.sort-asc,
            .sortable-header .sort-indicator.sort-desc {
                color: #14b8a6 !important; /* teal-500 */
            }

            .sortable-header .sort-indicator.sort-asc i::before {
                content: "\f0de"; /* sort-up */
            }

            .sortable-header .sort-indicator.sort-desc i::before {
                content: "\f0dd"; /* sort-down */
            }

            /* ============================================
               GLOBAL SEARCH / FILTER BUTTON STYLES
               ============================================ */
            .op-btn-search {
                background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%) !important;
                border: none !important;
                color: white !important;
                font-weight: 600 !important;
                border-radius: 8px !important;
                padding: 0 1.1rem !important;
                height: 34px !important;
                display: inline-flex !important;
                align-items: center !important;
                justify-content: center !important;
                gap: 6px !important;
                transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1) !important;
                box-shadow: 0 2px 6px rgba(20, 184, 166, 0.25) !important;
                font-size: 0.875rem !important;
                white-space: nowrap !important;
            }

            .op-btn-search:hover {
                transform: translateY(-1px) !important;
                box-shadow: 0 4px 12px rgba(20, 184, 166, 0.35) !important;
                filter: brightness(1.05) !important;
                color: white !important;
            }

            .op-btn-search:active {
                transform: translateY(0) !important;
                box-shadow: 0 1px 4px rgba(20, 184, 166, 0.2) !important;
            }

            .op-search-container .input-group .input-group-text {
                border-radius: 8px 0 0 8px !important;
                border-color: #e2e8f0 !important;
                background: #f8fafc !important;
            }

            .op-search-container .input-group .form-control {
                border-radius: 0 8px 8px 0 !important;
                border-color: #e2e8f0 !important;
                background: #f8fafc !important;
                transition: all 0.2s ease !important;
            }

            .op-search-container .input-group .form-control:focus {
                border-color: #14b8a6 !important;
                background: #fff !important;
                box-shadow: 0 0 0 3px rgba(20, 184, 166, 0.1) !important;
            }

            /* Sortable header hover effect */
            .sortable-header {
                user-select: none;
                white-space: nowrap;
            }

            .sortable-header:hover {
                background-color: rgba(20, 184, 166, 0.04) !important;
            }

            /* ============================================
               RESPONSIVE DESIGN FOR MOBILE (<768px)
               ============================================ */
            @media screen and (max-width: 768px) {
                /* Convert table layout to block for mobile */
                .table-sticky-header,
                .table-sticky-header thead,
                .table-sticky-header tbody,
                .table-sticky-header th,
                .table-sticky-header td,
                .table-sticky-header tr {
                    display: block;
                    width: 100%;
                }

                /* Hide table header row on mobile */
                .table-sticky-header thead {
                    position: relative;
                    top: 0;
                    box-shadow: none;
                    display: none;
                    border: none;
                }

                .table-sticky-header thead tr {
                    display: none;
                }

                /* Style rows as cards on mobile */
                .table-sticky-header tbody tr {
                    display: block;
                    margin-bottom: 1.5rem;
                    border: 1px solid #ddd;
                    padding: 0;
                    border-radius: 5px;
                    height: auto;
                    background-color: #fff;
                    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
                    width: 100%;
                    box-sizing: border-box;
                }

                .table-sticky-header tbody tr:nth-child(even) {
                    background-color: #fff;
                }

                .table-sticky-header tbody tr:hover {
                    background-color: #f9f9f9;
                }

                /* Style cells with stacked label-value layout */
                .table-sticky-header tbody td {
                    display: block;
                    border: none;
                    border-bottom: 1px solid #eee;
                    padding: 10px 12px;
                    text-align: left;
                    word-wrap: break-word;
                    overflow-wrap: break-word;
                    width: 100%;
                    box-sizing: border-box;
                    position: relative;
                }

                /* Label on top as pseudo-element */
                .table-sticky-header tbody td::before {
                    content: attr(data-label);
                    font-weight: 600;
                    color: #666;
                    display: block;
                    font-size: 0.85em;
                    margin-bottom: 4px;
                    word-wrap: break-word;
                    overflow-wrap: break-word;
                }

                /* Value below label */
                .table-sticky-header tbody td .cell-value {
                    display: block;
                    word-wrap: break-word;
                    overflow-wrap: break-word;
                    word-break: break-word;
                    font-size: 0.95em;
                    color: #333;
                    line-height: 1.4;
                }

                /* Remove bottom border from last cell in row */
                .table-sticky-header tbody td:last-child {
                    border-bottom: none;
                }

                /* Adjust responsive container */
                .table-responsive-sticky {
                    overflow-y: visible;
                    overflow-x: visible;
                    border: none;
                    border-radius: 0;
                }

                .table-responsive-sticky table {
                    min-width: auto;
                }

                /* Stack action buttons on mobile */
                .btn-group {
                    display: flex;
                    flex-direction: column;
                    gap: 5px;
                    width: 100%;
                }

                .btn-group .btn {
                    width: 100%;
                }

                /* Totals row on mobile */
                .table-totals-row {
                    display: block;
                    margin-bottom: 1.5rem;
                    border: 2px solid #333;
                    padding: 0;
                    border-radius: 5px;
                    background-color: #f0f0f0;
                    width: 100%;
                    box-sizing: border-box;
                }

                .table-totals-row td {
                    display: block;
                    border: none;
                    border-bottom: 1px solid #999;
                    padding: 10px 12px;
                }

                .table-totals-row td:last-child {
                    border-bottom: none;
                }
            }

            /* ============================================
               TOTALS ROW STYLING
               ============================================ */
            .table-totals-row {
                background-color: #f0f0f0;
                font-weight: bold;
                border-top: 2px solid #333;
                position: sticky;
                bottom: 0;
                z-index: 5;
            }

            .table-totals-row td {
                padding: 8px 6px;
                border-bottom: 2px solid #333;
                background-color: #f0f0f0;
                font-weight: 600;
                height: 40px;
                line-height: 24px;
            }

            .totals-label {
                font-weight: 700;
                color: #333;
                padding-left: 12px;
            }

            .totals-cell {
                text-align: right;
                color: #0d6efd;
                font-weight: 700;
                padding-right: 12px;
            }

            /* Loading animation for totals row */
            .totals-loading {
                background-color: #f9f9f9;
                animation: shimmer 1.5s infinite;
            }

            .totals-loading td {
                background-color: #f9f9f9;
            }

            @keyframes shimmer {
                0%, 100% { background-color: #f9f9f9; }
                50% { background-color: #e9e9e9; }
            }
        `;

        document.head.appendChild(style);
    }

    /**
     * Calculate optimal table height based on rows per page and actual data
     * Adjusts container height to fit actual rows or per_page setting (whichever is smaller)
     */
    _calculateTableHeight() {
        const perPage = this.config.perPage || 10; // Use current perPage setting
        const rowHeight = 45; // pixels per row
        const headerRowHeight = 43; // pixels for thead header row only
        const filterRowHeight = 45; // pixels for filter row (if exists)
        const totalsRowHeight = 40; // pixels for totals row (sticky at bottom)

        // Get actual row count
        const actualRowCount = this.gridData.length;

        // Check if filter row exists
        const container = document.getElementById(this.config.containerId);
        const hasFilterRow = container && container.querySelector('thead tr:nth-child(2)') !== null;
        const hasTotalsRow = this.totals && this.totals.enabled; // Check if totals row is enabled

        const headerHeight = headerRowHeight + (hasFilterRow ? filterRowHeight : 0);

        // If no data, show minimal height (just header)
        if (actualRowCount === 0) {
            return headerHeight + 80; // Just header + small buffer for "no data" message
        }

        // Show all actual rows up to the current perPage setting
        // If user sets perPage to 25, show up to 25 rows (plus space for sticky totals row if enabled)
        const rowsToShow = Math.min(actualRowCount, perPage);
        const extraSpace = hasTotalsRow ? totalsRowHeight : 0;
        const calculatedHeight = headerHeight + (rowsToShow * rowHeight) + extraSpace;

        // Return calculated height - dynamically sized based on actual rows (up to perPage setting)
        return calculatedHeight;
    }

    /**
     * Apply dynamic height to table container based on per page selection and actual data
     * Adjusts height to fit actual rows (up to perPage) without unnecessary white space
     * When per_page changes, this is called to recalculate and apply the appropriate height
     */
    _applyTableHeight() {
        const container = document.getElementById(this.config.containerId);
        if (!container) return;

        const responsiveDiv = container.querySelector('.table-responsive-sticky');
        if (!responsiveDiv) return;

        const tableHeight = this._calculateTableHeight();
        const actualRowCount = this.gridData.length;
        const perPage = this.config.perPage || 10; // Use current perPage setting

        // If actual rows are less than or equal to perPage, show all without scrolling
        // If actual rows are MORE than perPage, show perPage rows and allow scrolling
        if (actualRowCount <= perPage) {
            // Show all rows without vertical scrolling, only horizontal for wide tables
            responsiveDiv.style.height = 'auto';
            responsiveDiv.style.overflowY = 'visible';
            responsiveDiv.style.overflowX = 'auto';
        } else {
            // Show up to perPage with vertical scrolling for the rest
            responsiveDiv.style.height = tableHeight + 'px';
            responsiveDiv.style.overflowY = 'auto';
            responsiveDiv.style.overflowX = 'auto';
        }

    }

    /**
     * Auto-generate column filters from API response data structure
     * Extracts field names from first data object and creates filter definitions
     * Usage: call after first data load to auto-populate columnFilters
     * @returns {boolean} true if filters were generated, false otherwise
     */
    _autoGenerateColumnFilters(dataArray) {
        if (!dataArray || !Array.isArray(dataArray) || dataArray.length === 0) {
            return false;
        }

        // Check if auto-generation is explicitly disabled
        const autoGenerateEnabled = this.config.filters?.autoGenerateColumnFilters !== false;
        if (!autoGenerateEnabled) {
            return false;
        }

        const firstRow = dataArray[0];
        const fieldNames = Object.keys(firstRow);

        // Map of database field names to filter types (defaults to 'text')
        const fieldTypeMap = {
            paidFlg: 'select',
            receivedFlg: 'select',
            isBothway: 'select',
            isBargain: 'select',
            cashbackCall: 'select'
        };

        // Options for select filters (0=No/False, 1=Yes/True)
        const selectOptionsMap = {
            paidFlg: [
                { value: '0', label: 'No' },
                { value: '1', label: 'Yes' }
            ],
            receivedFlg: [
                { value: '0', label: 'No' },
                { value: '1', label: 'Yes' }
            ],
            isBothway: [
                { value: '0', label: 'No' },
                { value: '1', label: 'Yes' }
            ],
            isBargain: [
                { value: '0', label: 'No' },
                { value: '1', label: 'Yes' }
            ],
            cashbackCall: [
                { value: '0', label: 'No' },
                { value: '1', label: 'Yes' }
            ]
        };

        // Fields to skip (too verbose, IDs, internal fields)
        let skipFields = ['id', 'ticketDetailId', 'tripMasterId', 'passengerDetailId', 'operatorId', 'merchantCommission', 'serviceCode'];

        // Add excluded columns from configuration
        const excludeColumns = this.config.filters?.excludeColumns || [];
        if (Array.isArray(excludeColumns)) {
            skipFields = [...skipFields, ...excludeColumns];
        }

        // Generate filters for filterable fields
        const generatedFilters = fieldNames
            .filter(field => !skipFields.includes(field))
            .map(field => {
                const type = fieldTypeMap[field] || 'text';
                const displayName = this._fieldNameToDisplay(field);

                const filterDef = {
                    field: field,
                    type: type,
                    param: field, // Use database field name as param
                    placeholder: displayName
                };

                // Add options for select fields
                if (type === 'select' && selectOptionsMap[field]) {
                    filterDef.options = selectOptionsMap[field];
                    filterDef.allowBlank = true;
                }

                return filterDef;
            });

        // Update config and normalize
        if (!this.config.filters) this.config.filters = {};
        if (!this.config.filters.columnFilters) this.config.filters.columnFilters = [];

        // Merge: keep manually defined ones, add auto-generated ones not already present
        const manualFields = new Set((this.config.filters.columnFilters || []).map(f => f.field));
        const newFilters = generatedFilters.filter(f => !manualFields.has(f.field));

        // Only return true if new filters were actually added
        if (newFilters.length > 0) {
            this.config.filters.columnFilters = [...(this.config.filters.columnFilters || []), ...newFilters];
            this._normalizedColumnFilters = this._normalizeColumnFilters(this.config.filters.columnFilters);
            return true;
        }

        return false;
    }

    /**
     * Convert camelCase field name to display label
     * tktSrlNo → "Ticket No.", operatorName → "Operator Name"
     */
    _fieldNameToDisplay(field) {
        // Common abbreviations
        const abbrevMap = {
            'Tkt': 'Ticket',
            'Srl': 'Serial',
            'No': 'No.',
            'BS': '(BS)',
            'Id': 'ID',
            'Flg': 'Flag',
            'Plg': 'Flag',
            'PM': 'PM',
            'AM': 'AM'
        };

        // Insert space before capitals and expand abbreviations
        let display = field
            .replace(/([A-Z])/g, ' $1') // Insert space before capitals
            .trim();

        // Replace abbreviations
        Object.entries(abbrevMap).forEach(([abbrev, full]) => {
            const regex = new RegExp(`\\b${abbrev}\\b`, 'g');
            display = display.replace(regex, full);
        });

        // Cleanup multiple spaces and capitalize first letter
        display = display.replace(/\s+/g, ' ').trim();
        return display.charAt(0).toUpperCase() + display.slice(1);
    }

    /**
     * Get today's date in YYYY-MM-DD format
     * Supports both Nepali and AD calendar systems
     */
    _getTodayDate(useNepaliCalendar = false) {
        if (useNepaliCalendar && typeof initNepaliDatepickers === 'function') {
            return this._getTodayBS();
        }
        const today = new Date();
        const year = today.getFullYear();
        const month = String(today.getMonth() + 1).padStart(2, '0');
        const day = String(today.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    /**
     * Get first day of current month
     * @param {string} todayDate - Date in YYYY-MM-DD format
     * @returns {string} First day of month in YYYY-MM-DD format
     */
    _getFirstOfMonth(todayDate) {
        const parts = todayDate.split('-');
        const year = parts[0];
        const month = parts[1];
        return `${year}-${month}-01`;
    }

    /**
     * Apply default date range based on dateRangeDefaults config
     * Options:
     * - type: 'today' (current day), 'thisMonth' (1st to today), 'lastMonth' (30 days back), 'custom'
     * - fromDate/toDate: for custom type
     * - useNepaliCalendar: boolean (default false)
     * Sets the date inputs without triggering change events
     */
    _applyDefaultDateRange() {
        if (this._dateRangeInitialized || !this.config.dateRangeDefaults) return;

        const config = this.config.dateRangeDefaults;
        const fromSelector = config.fromSelector || '#from-datepicker';
        const toSelector = config.toSelector || '#to-datepicker';
        const useNepaliCalendar = config.useNepaliCalendar || false;

        let fromDate, toDate;
        const todayDate = this._getTodayDate(useNepaliCalendar);

        switch (config.type) {
            case 'today':
                fromDate = toDate = todayDate;
                break;
            case 'thisMonth':
                fromDate = this._getFirstOfMonth(todayDate);
                toDate = todayDate;
                break;
            case 'lastMonth':
                toDate = todayDate;
                // For both AD and Nepali calendar
                if (useNepaliCalendar) {
                    // Keep it simple for Nepali - 30 days back
                    const parts = todayDate.split('-');
                    let year = parseInt(parts[0]);
                    let month = parseInt(parts[1]);
                    let day = parseInt(parts[2]);

                    // Go back 30 days in Nepali calendar
                    day -= 30;
                    if (day <= 0) {
                        month -= 1;
                        if (month <= 0) {
                            month = 12;
                            year -= 1;
                        }
                        day += 32; // Max days in a month (approximate)
                    }
                    fromDate = `${year}-${String(month).padStart(2, '0')}-${String(Math.max(1, day)).padStart(2, '0')}`;
                } else {
                    const lastMonthDate = new Date(new Date(todayDate).getTime() - 30 * 24 * 60 * 60 * 1000);
                    fromDate = lastMonthDate.getFullYear() + '-' +
                        String(lastMonthDate.getMonth() + 1).padStart(2, '0') + '-' +
                        String(lastMonthDate.getDate()).padStart(2, '0');
                }
                break;
            case 'custom':
                fromDate = config.fromDate || todayDate;
                toDate = config.toDate || todayDate;
                break;
            default:
                fromDate = this._getFirstOfMonth(todayDate);
                toDate = todayDate;
        }

        // Set values without triggering change events
        const $fromInput = $(fromSelector);
        const $toInput = $(toSelector);

        if ($fromInput.length && $toInput.length) {
            // Temporarily unbind all change events to prevent double requests
            const fromChangeHandlers = $._data($fromInput[0], 'events')?.change || [];
            const toChangeHandlers = $._data($toInput[0], 'events')?.change || [];

            $fromInput.off('change');
            $toInput.off('change');

            // Set the values
            $fromInput.val(fromDate);
            $toInput.val(toDate);

            // Re-attach change handlers
            fromChangeHandlers.forEach(handler => {
                $fromInput.on('change', handler.handler);
            });
            toChangeHandlers.forEach(handler => {
                $toInput.on('change', handler.handler);
            });

            this._dateRangeInitialized = true;
        }
    }

    init() {
        this._autoInitDatePickers();
        this._applyDefaultDateRange();  // Apply default date range before setting up filters
        this.setupFilters();
        this.initColumnSorting(); // Initialize column sorting
        this.loadTable();
    }

    setupFilters() {
        const { filters } = this.config;
        if (!filters.dateRange) return;

        $('#applyFilters').on('click', () => {
            if (this.validateFilters()) {
                // Clear global search when applying filters
                this._clearGlobalSearch();
                this.currentPage = 1;
                this.loadTable();
            }
        });

        $('#clearFilters').on('click', () => this.clearFilters());

        filters.autoReload?.forEach(selector => {
            $(selector).on('change', () => {
                if (this.validateFilters()) {
                    // Clear global search when auto-reload filters change
                    this._clearGlobalSearch();
                    this.currentPage = 1;
                    this.loadTable();
                }
            });
        });
    }

    validateFilters() {
        const { dateValidation } = this.config;
        if (!dateValidation?.enabled) return true;

        const fromDate = $(dateValidation.fromSelector || '#from-datepicker').val();
        const toDate = $(dateValidation.toSelector || '#to-datepicker').val();

        if (!fromDate || !toDate) {
            this._showAlert('warning', 'Invalid Dates', dateValidation.requiredMessage || 'Please select both From and To dates.');
            return false;
        }
        if (fromDate > toDate) {
            this._showAlert('error', 'Invalid Date Range', 'From Date cannot be greater than To Date.');
            return false;
        }

        if (dateValidation.maxDays) {
            const diffDays = Math.ceil(Math.abs(new Date(toDate) - new Date(fromDate)) / 86400000);
            if (diffDays >= dateValidation.maxDays) {
                this._showAlert('error', 'Invalid Date Range', `Date range must be less than ${dateValidation.maxDays} days.`);
                return false;
            }
        }
        return true;
    }

    getFilterValues() {
        const { filters } = this.config;
        const params = {};

        // Global standalone search: when a global search term is active and the page opts in
        // (config.globalSearchStandalone), send ONLY the search param so it behaves as a pure
        // lookup — ignoring the date range and every other filter on the server. The filter
        // inputs are left untouched in the UI, so the user's date range / filters stay selected
        // and apply again automatically once the search is cleared.
        if (this.config.globalSearchStandalone && this.config.search && this.searchTerm) {
            const searchConfig = typeof this.config.search === 'object' ? this.config.search : {};
            const searchParam = searchConfig.param || 'search';
            params[searchParam] = this.searchTerm;
            return params;
        }

        // Helper: safely get value from element, trying both jQuery and native methods
        const getElementValue = (id) => {
            if (!id) return null;
            try {
                const el = document.getElementById(id);
                if (el) {
                    // Try jQuery first if available
                    if (typeof $ !== 'undefined' && $(el).length) {
                        const jqVal = $(el).val();
                        if (jqVal != null && jqVal !== '') return jqVal;
                    }
                    // Fallback to native value
                    if (el.value != null && el.value !== '') return el.value;
                }
            } catch (e) { /* ignore */ }
            return null;
        };

        // 1. Always include date range if configured
        if (filters.dateRange) {
            const { fromId = 'from-datepicker', toId = 'to-datepicker', dateTypeParam } = filters.dateRange;
            const startVal = getElementValue(fromId);
            const endVal = getElementValue(toId);

            // Always include dates if they exist (even if empty string from init)
            if (startVal) params.start_date = startVal;
            if (endVal) params.end_date = endVal;

            // Include date_type if specified
            if (dateTypeParam) {
                const dateTypeVal = getElementValue(dateTypeParam) || $(`#${dateTypeParam}`).val();
                if (dateTypeVal != null && dateTypeVal !== '') params.date_type = dateTypeVal;
            }
        }

        // 2. Always include additional filters (these are page-level filters like view_type, operator, etc.)
        if (filters.additional && Array.isArray(filters.additional)) {
            filters.additional.forEach(filter => {
                const filterId = typeof filter === 'string' ? filter : filter.id;
                const param = typeof filter === 'string'
                    ? filter.replace(/([A-Z])/g, '_$1').toLowerCase()
                    : (filter.param || filter.id);

                const value = getElementValue(filterId) || $(`#${filterId}`).val();
                if (value != null && value !== '') params[param] = value;
            });
        }

        // 3. Include column filters (header row filters)
        // First, check instance-stored values (set by setupColumnFilterEvents), then fallback to DOM
        if (filters.columnFilters && Array.isArray(filters.columnFilters)) {
            const storedColumnValues = this.columnFilterValues || {};
            const defs = this._normalizedColumnFilters || [];

            defs.forEach(def => {
                let value = null;

                // First, check if value was stored on instance by setupColumnFilterEvents
                if (storedColumnValues[def.param] != null && storedColumnValues[def.param] !== '') {
                    value = storedColumnValues[def.param];
                } else {
                    // Fallback: try by generated id (in case called directly without event handler)
                    value = getElementValue(def.id);

                    // Fallback: by name attribute matching param
                    if ((value == null || value === '') && def.param) {
                        try {
                            const byName = document.querySelector(`[name="${def.param}"]`);
                            if (byName) value = byName.value ?? null;
                        } catch (e) { /* ignore */ }
                    }

                    // Fallback: inside table container with data-field
                    if ((value == null || value === '') && def.field) {
                        try {
                            const containerSelector = `#${this.config.containerId}`;
                            const byDataField = document.querySelector(`${containerSelector} [data-field="${def.field}"]`);
                            if (byDataField) value = byDataField.value ?? null;
                        } catch (e) { /* ignore */ }
                    }

                    // Fallback: by placeholder
                    if ((value == null || value === '') && def.placeholder) {
                        try {
                            const byPlaceholder = document.querySelector(`input[placeholder='${def.placeholder}'], select[placeholder='${def.placeholder}']`);
                            if (byPlaceholder) value = byPlaceholder.value ?? null;
                        } catch (e) { /* ignore */ }
                    }
                }

                // Include if non-empty
                if (value != null && value !== '') params[def.param] = value;
            });
        }

        // 4. Include search term if present
        if (this.config.search && this.searchTerm) {
            const searchConfig = typeof this.config.search === 'object' ? this.config.search : {};
            const searchParam = searchConfig.param || 'search';
            params[searchParam] = this.searchTerm;
        }

        return params;
    }

    buildApiUrl(page = 1) {
        const params = this.getFilterValues();
        params.page = page;
        params.per_page = this.config.perPage;

        // Add sorting parameters if available
        if (this.sortField && this.sortDirection) {
            params.sort_field = this.sortField;
            params.sort_direction = this.sortDirection;
        }

        // Add extra parameters if function is defined
        if (typeof this.config.extraParams === 'function') {
            const extraParams = this.config.extraParams();
            Object.assign(params, extraParams);
        }

        const queryString = Object.entries(params)
            .filter(([_, v]) => v != null && v !== '')
            .map(([k, v]) => `${encodeURIComponent(k)}=${encodeURIComponent(v)}`)
            .join('&');

        const separator = this.config.apiUrl.includes('?') ? '&' : '?';
        return `${this.config.apiUrl}${separator}${queryString}`;
    }

    /**
     * Render thead with optional rowspan/colspan via config.headerRows.
     * headerRows: array of header rows. Each row is an array of cells.
     * Each cell: { label: string, colspan?: number, rowspan?: number, align?: string, width?: string, class?: string } (defaults 1 for span).
     * Example: headerRows: [
     *   [ { label: 'Name' }, { label: 'Timeout', colspan: 2 }, { label: 'Collapse', colspan: 2 }, { label: 'Booked' } ],
     *   [ { label: 'Operator' }, { label: 'Customer' }, { label: 'Operator' }, { label: 'Customer' } ]
     * ]  -> first row has grouped headers; second row has sub-labels (no cell for columns with rowspan from above).
     * If headerRows is not set, falls back to single row from columns.
     */
    renderHeader() {
        const { columns } = this.config;
        const headerRows = this.config.headerRows;

        let headerMarkup = '';

        if (headerRows && Array.isArray(headerRows) && headerRows.length > 0) {
            // Multi-row header with rowspan/colspan support
            headerMarkup = headerRows.map((rowCells, rowIdx) => {
                let cellsHtml = '';
                if (this._isCheckboxEnabled()) {
                    if (rowIdx === 0) {
                        cellsHtml += `<th style="width: 40px; text-align: center;" rowspan="${headerRows.length}">
                            <input type="checkbox" id="select-all-checkbox" class="form-check-input" title="Select all rows">
                        </th>`;
                    }
                    // rowIdx > 0: checkbox column is covered by rowspan from row 0, no extra cell
                }
                rowCells.forEach(cell => {
                    const label = cell.label != null ? cell.label : '';
                    const colspan = cell.colspan != null && cell.colspan > 0 ? cell.colspan : 1;
                    const rowspan = cell.rowspan != null && cell.rowspan > 0 ? cell.rowspan : 1;
                    const cAttr = colspan > 1 ? ` colspan="${colspan}"` : '';
                    const rAttr = rowspan > 1 ? ` rowspan="${rowspan}"` : '';
                    const styleParts = [];
                    if (cell.width) styleParts.push(`width: ${cell.width}`);
                    if (cell.align) styleParts.push(`text-align: ${cell.align}`);
                    const styleAttr = styleParts.length ? ` style="${styleParts.join('; ')}"` : '';
                    const cls = cell.class ? ` class="${cell.class}"` : '';
                    cellsHtml += `<th${cAttr}${rAttr}${styleAttr}${cls}>${label}</th>`;
                });
                return `<tr>${cellsHtml}</tr>`;
            }).join('');
            // Checkbox: only first row gets the checkbox th with rowspan; other rows don't add a th for it
            if (this._isCheckboxEnabled() && headerRows.length > 1) {
                // We already added one th with rowspan in row 0, so we must not add another in row 1+
                // So the code above is correct: row 0 has checkbox th with rowspan=N; row 1..N-1 don't have checkbox cell
            }
        } else {
            // Default: single header row from columns
            let headerCells = '';
            if (this._isCheckboxEnabled()) {
                headerCells += `<th style="width: 40px; text-align: center;">
                    <input type="checkbox" id="select-all-checkbox" class="form-check-input" title="Select all rows">
                </th>`;
            }
            headerCells += columns.map((col, colIndex) => {
                const name = col.name || col.header || '';
                const width = col.width ? ` style="width: ${col.width}"` : '';

                let canSort = false;
                const enableSortColumns = this.config.enableSortColumns || [];
                if (Array.isArray(enableSortColumns) && enableSortColumns.length > 0) {
                    canSort = col.field && enableSortColumns.includes(col.field);
                }

                if (canSort) {
                    return `<th${width} data-field="${col.field || colIndex}" class="sortable-header" style="cursor: pointer;" title="Click to sort">
                        ${name} <span class="sort-indicator"><i class="fa-solid fa-sort"></i></span>
                    </th>`;
                }
                return `<th${width}>${name}</th>`;
            }).join('');
            headerMarkup = `<tr>${headerCells}</tr>`;
        }

        const filterDefs = this._normalizedColumnFilters || [];
        if (!filterDefs.length) {
            return `<thead>${headerMarkup}</thead>`;
        }

        const inputsByCol = new Map();
        filterDefs.forEach(def => {
            if (def.column != null) inputsByCol.set(def.column, def);
        });

        let filterCells = '';
        if (this._isCheckboxEnabled()) {
            filterCells += `<th></th>`;
        }
        filterCells += columns.map((col, colIndex) => {
            if (col.type === 'actions' || col.actions) {
                return `<th></th>`;
            }
            const excludedColumns = this.config.filters?.excludeColumns || [];
            if (col.field && Array.isArray(excludedColumns) && excludedColumns.includes(col.field)) {
                return `<th></th>`;
            }
            const def = inputsByCol.get(colIndex);
            if (!def) return `<th></th>`;
            const placeholder = def.placeholder ?? (def.field || def.param || 'Filter');
            const dataFieldAttr = def.field ? ` data-field="${def.field}"` : ` data-field="${def.param}"`;
            if (def.type === 'select') {
                const opts = (def.options || [])
                    .map(o => {
                        const val = (o && (o.value != null)) ? o.value : o;
                        const label = (o && (o.label || o.text)) ? (o.label || o.text) : String(val);
                        return `<option value="${String(val)}">${label}</option>`;
                    }).join('');
                const blank = def.allowBlank === false ? '' : `<option value="">All</option>`;
                return `<th><select id="${def.id}" class="form-select form-select-sm"${dataFieldAttr} placeholder="${placeholder}" data-options-url="${def.optionsUrl || ''}">${blank}${opts}</select></th>`;
            }
            return `<th><input id="${def.id}" type="text" class="form-control form-control-sm" placeholder="${placeholder}"${dataFieldAttr}></th>`;
        }).join('');

        const filterRow = `<tr>${filterCells}</tr>`;
        this._loadDynamicFilterOptions();

        return `<thead>${headerMarkup}${filterRow}</thead>`;
    }

    /**
     * Initialize column sorting functionality
     */
    initColumnSorting() {
        // Clear existing event listeners
        $('.sortable-header').off('click.tableHelper');

        // Add click event for sortable headers
        $(document).on('click.tableHelper', '.sortable-header', (e) => {
            const $header = $(e.currentTarget);
            const field = $header.data('field');

            if (!field) return;

            // Determine sort direction
            let sortDirection = 'asc';
            const currentIndicator = $header.find('.sort-indicator').first();

            if (currentIndicator.hasClass('sort-asc')) {
                sortDirection = 'desc';
            } else if (currentIndicator.hasClass('sort-desc')) {
                // If already descending, clear sorting
                sortDirection = '';
            }

            // Update indicators for all headers
            $('.sortable-header .sort-indicator').removeClass('sort-asc sort-desc');
            if (sortDirection) {
                $header.find('.sort-indicator').first().addClass(`sort-${sortDirection}`);
            }

            // Store sort state
            this.sortField = field;
            this.sortDirection = sortDirection;
            this.currentPage = 1;

            // Reload table with sorting parameters
            this.loadTable();
        });
    }

    /**
     * Add sorting parameters to API URL
     */
    addSortingToApiUrl(url) {
        if (!this.sortField) return url;

        const separator = url.includes('?') ? '&' : '?';
        const sortParam = this.sortDirection ? `&sort_field=${encodeURIComponent(this.sortField)}&sort_direction=${encodeURIComponent(this.sortDirection)}` : '';

        return url + sortParam;
    }

    /**
     * Load filter options dynamically from API endpoints
     * Supports optionsUrl in filter definitions for dynamic/database-driven options
     */
    _loadDynamicFilterOptions() {
        const filterDefs = this._normalizedColumnFilters || [];

        filterDefs.forEach(def => {
            // Skip if no URL or options already loaded
            if (!def.optionsUrl || def._optionsLoaded) return;

            const selectEl = document.getElementById(def.id);
            if (!selectEl) return;

            // Check cache first
            if (this._filterOptionsCache && this._filterOptionsCache[def.optionsUrl]) {
                this._populateSelectOptions(
                    selectEl,
                    this._filterOptionsCache[def.optionsUrl],
                    { valueField: def.valueField, labelField: def.labelField }
                );
                def._optionsLoaded = true;
                return;
            }

            // Fetch from API
            fetch(def.optionsUrl, { credentials: 'same-origin' })
                .then(response => response.json())
                .then(result => {
                    let options = [];
                    if (result.success && result.data) {
                        options = result.data;
                    } else if (Array.isArray(result)) {
                        options = result;
                    }

                    // Cache the options
                    if (!this._filterOptionsCache) this._filterOptionsCache = {};
                    this._filterOptionsCache[def.optionsUrl] = options;

                    // Populate select with optional custom field mapping
                    this._populateSelectOptions(
                        selectEl,
                        options,
                        { valueField: def.valueField, labelField: def.labelField }
                    );
                    def._optionsLoaded = true;
                })
                .catch(error => {
                    console.error(`Error loading options from ${def.optionsUrl}:`, error);
                });
        });
    }

    /**
     * Helper to populate select element with options
     * @param {HTMLSelectElement} selectEl - The select element to populate
     * @param {Array} options - Array of option objects
     * @param {Object} fieldMapping - Optional: { valueField: 'fieldName', labelField: 'fieldName' }
     */
    _populateSelectOptions(selectEl, options, fieldMapping = {}) {
        // Keep the "All" option
        const allOption = selectEl.querySelector('option[value=""]');
        const existingOptions = Array.from(selectEl.querySelectorAll('option[value!=""]'));

        // Remove old options (keep "All" option)
        existingOptions.forEach(opt => opt.remove());

        // Use custom mapping or auto-detect
        const valueField = fieldMapping.valueField;
        const labelField = fieldMapping.labelField;

        // Add new options
        options.forEach(o => {
            let val, label;

            // If custom mapping provided, use it
            if (valueField && o[valueField] != null) {
                val = o[valueField];
            } else {
                // Smart fallback detection for value
                val = (o && (o.value != null)) ? o.value
                    : (o && (o.id != null)) ? o.id
                        : o;
            }

            if (labelField && o[labelField] != null) {
                label = o[labelField];
            } else {
                // Smart fallback detection for label
                label = (o && (o.label != null)) ? o.label
                    : (o && (o.text != null)) ? o.text
                        : (o && (o.name != null)) ? o.name
                            : (o && (o.title != null)) ? o.title
                                : String(val);
            }

            const option = document.createElement('option');
            option.value = String(val);
            option.textContent = String(label);
            selectEl.appendChild(option);
        });
    }

    /**
     * Generate skeleton loader rows (shimmer animation)
     * Shows 5 placeholder rows with pulsing animation while data loads
     */
    _renderSkeletonLoader() {
        const { columns } = this.config;
        const skeletonRows = 5; // Show 5 loading rows

        let rows = '';
        for (let i = 0; i < skeletonRows; i++) {
            const cells = columns.map(() => {
                return `<td><div class="skeleton-loader"></div></td>`;
            }).join('');
            rows += `<tr>${cells}</tr>`;
        }

        return `<tbody>${rows}</tbody>`;
    }

    /**
     * Open modal by ID or class selector
     * Supports Bootstrap modals and custom modals
     */
    _openModal(selector, rowData = null) {
        let modalElement = null;

        // Use querySelector which handles both ID (#) and class (.) selectors
        if (selector.startsWith('#') || selector.startsWith('.')) {
            // CSS selector with # or .
            modalElement = document.querySelector(selector);
        } else {
            // Plain ID or class name - try both
            modalElement = document.getElementById(selector) || document.querySelector(`.${selector}`);
        }

        if (!modalElement) {
            console.warn(`Modal not found: ${selector}`);
            return false;
        }

        // Store row data in modal element for access in callbacks
        if (rowData) {
            modalElement.dataset.rowData = JSON.stringify(rowData);
        }

        // Try Bootstrap 5 modal
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            const modal = new bootstrap.Modal(modalElement);
            modal.show();
            return true;
        }

        // Try jQuery modal (Bootstrap 4 or custom)
        if (typeof $ !== 'undefined' && $.fn.modal) {
            $(modalElement).modal('show');
            return true;
        }

        // Fallback: show modal manually
        modalElement.style.display = 'block';
        modalElement.classList.add('show');
        document.body.classList.add('modal-open');

        return true;
    }

    /**
     * Render action buttons for action column
     * @param {Array} actions - Array of action definitions
     * @param {Object} rowData - Current row data
     * @param {number} rowIndex - Current row index
     * @returns {string} HTML string of action buttons
     */
    _renderActionButtons(actions, rowData, rowIndex) {
        if (!actions || !Array.isArray(actions) || actions.length === 0) {
            return '';
        }

        const buttonGroup = actions.map((action, actionIndex) => {
            // Default action types with icons
            const actionDefaults = {
                edit: {
                    icon: 'fas fa-pen',
                    label: 'Edit',
                    class: 'btn btn-sm btn-primary',
                    title: 'Edit',
                    showIcon: true, // Icons show by default
                    showLabel: true
                },
                delete: {
                    icon: 'fas fa-trash',
                    label: 'Delete',
                    class: 'btn btn-sm btn-danger',
                    title: 'Delete',
                    showIcon: true,
                    showLabel: true
                },
                show: {
                    icon: 'fas fa-eye',
                    label: 'View',
                    class: 'btn btn-sm btn-info',
                    title: 'View',
                    showIcon: true,
                    showLabel: true
                },
                view: {
                    icon: 'fas fa-eye',
                    label: 'View',
                    class: 'btn btn-sm btn-info',
                    title: 'View',
                    showIcon: true,
                    showLabel: true
                }
            };

            // Merge defaults with custom action
            let finalAction = {};
            if (typeof action === 'string') {
                // Simple string action type (e.g., 'edit', 'delete')
                const defaults = actionDefaults[action] || {};
                finalAction = {
                    ...defaults,
                    type: action,
                    // Ensure icon and showIcon are set from defaults
                    icon: defaults.icon || '',
                    showIcon: defaults.showIcon !== undefined ? defaults.showIcon : true
                };
            } else {
                // Object with custom configuration
                const actionType = action.type || action.action || 'custom';
                const defaults = actionDefaults[actionType] || {};
                finalAction = {
                    ...defaults,
                    ...action,
                    // Ensure icon is preserved from defaults if not overridden
                    icon: action.icon !== undefined ? action.icon : (defaults.icon || ''),
                    showIcon: action.showIcon !== undefined ? action.showIcon : (defaults.showIcon !== undefined ? defaults.showIcon : true)
                };
            }

            const {
                type = 'custom',
                icon = '',
                label = '',
                class: btnClass = 'btn btn-sm btn-secondary',
                title = label || type,
                modal = null,
                onClick = null,
                url = null,
                confirm = null,
                confirmTitle = 'Confirm',
                dataAttributes = {},
                visible = true,
                disabled = false,
                tooltip = null,
                showIcon = true, // Default: show icons
                showLabel = true // Default: show labels
            } = finalAction;

            // Debug: Log to see what's happening (remove after testing)
            if (typeof action === 'string') {
            }

            // Check visibility condition
            if (typeof visible === 'function') {
                if (!visible(rowData, rowIndex)) return '';
            } else if (visible === false) {
                return '';
            }

            // Check disabled condition
            let isDisabled = disabled;
            if (typeof disabled === 'function') {
                isDisabled = disabled(rowData, rowIndex);
            }

            // Build data attributes
            let dataAttrs = '';
            Object.entries(dataAttributes).forEach(([key, value]) => {
                const attrValue = typeof value === 'function' ? value(rowData, rowIndex) : value;
                dataAttrs += ` data-${key}="${String(attrValue).replace(/"/g, '&quot;')}"`;
            });

            // Add row data as data attribute
            dataAttrs += ` data-row-index="${rowIndex}"`;
            dataAttrs += ` data-action-index="${actionIndex}"`;
            dataAttrs += ` data-row-data='${JSON.stringify(rowData).replace(/'/g, "&#39;")}'`;

            // Generate unique action ID
            const actionId = `${this.config.containerId}-action-${rowIndex}-${actionIndex}`;

            // Build button HTML - respect showIcon and showLabel flags
            // IMPORTANT: Check if icon exists and showIcon is true

            // Handle dynamic label and class functions
            const resolvedLabel = typeof label === 'function' ? label(rowData, rowIndex) : label;
            const resolvedClass = typeof btnClass === 'function' ? btnClass(rowData, rowIndex) : btnClass;

            const shouldShowIcon = showIcon !== false && icon && icon.trim() !== '';
            const shouldShowLabel = showLabel !== false && resolvedLabel && String(resolvedLabel).trim() !== '';

            // Build icon HTML with proper spacing - ensure it's always included if icon exists
            const iconHtml = shouldShowIcon ? `<i class="${icon}" aria-hidden="true"></i>` : '';
            // Build label HTML with space before if icon exists
            const labelHtml = shouldShowLabel ? (shouldShowIcon ? ` ${resolvedLabel}` : resolvedLabel) : '';

            const disabledAttr = isDisabled ? ' disabled' : '';
            const tooltipAttr = tooltip ? ` data-bs-toggle="tooltip" data-bs-title="${tooltip}"` : '';

            return `
                <button 
                    type="button" 
                    class="${resolvedClass}${disabledAttr ? ' disabled' : ''}" 
                    id="${actionId}"
                    title="${title}"
                    ${tooltipAttr}
                    ${dataAttrs}
                    ${disabledAttr}
                    data-action-type="${type}"
                    data-action-index="${actionIndex}"
                    ${modal ? `data-modal="${modal}"` : ''}
                    ${url ? `data-url="${url}"` : ''}
                    ${confirm ? `data-confirm="${confirm}"` : ''}
                    ${confirm ? `data-confirm-title="${confirmTitle}"` : ''}
                >
                    ${iconHtml}${labelHtml}
                </button>
            `;
        }).filter(html => html.trim() !== '').join(' ');

        return `<div class="btn-group" role="group">${buttonGroup}</div>`;
    }

    renderBody(data) {
        const { columns, onRowClick } = this.config;

        // Adjust colspan to include checkbox column
        const colspanAdjust = this._isCheckboxEnabled() ? columns.length + 1 : columns.length;

        if (!data.length) {
            return `<tbody><tr><td colspan="${colspanAdjust}" class="text-center">${this.config.emptyMessage}</td></tr></tbody>`;
        }

        // Track rowspan: for each column, how many rows the current cell still spans (0 = none)
        const rowspanRemaining = new Array(columns.length).fill(0);

        const rows = data.map((row, rowIndex) => {
            const rowData = row.data || row;
            const rowId = this._getRowId(row, rowIndex);
            const rowIdStr = String(rowId);
            const isSelected = this.selectedRows.has(rowIdStr);
            const selectedClass = this.selectedRowId === rowId ? 'table-row-selected' : '';
            const customRowClass = typeof this.config.rowClass === 'function' ? this.config.rowClass(rowData, rowIndex) :
                (typeof this.config.rowClass === 'string' ? this.config.rowClass : '');
            const rowClass = `${onRowClick ? 'cursor-pointer' : ''} ${selectedClass} ${customRowClass}`.trim();

            let cellsHtml = '';

            if (this._isCheckboxEnabled()) {
                cellsHtml += `<td style="text-align: center;" data-label="Select">
                    <input type="checkbox" class="form-check-input row-checkbox" data-row-id="${rowIdStr}" data-row-index="${rowIndex}" ${isSelected ? 'checked' : ''}>
                </td>`;
            }

            // Support column.colspan and column.rowspan (number or function(rowData, rowIndex))
            let skipCols = 0;
            columns.forEach((column, colIndex) => {
                if (rowspanRemaining[colIndex] > 0) {
                    rowspanRemaining[colIndex]--;
                    return;
                }
                if (skipCols > 0) {
                    skipCols--;
                    return;
                }
                let content = '';
                if (column.type === 'actions' || column.actions) {
                    const actions = column.actions || [];
                    content = this._renderActionButtons(actions, rowData, rowIndex);
                } else if (column.isSerialNo || column.isIndex) {
                    const serialNo = ((this.currentPage - 1) * this.config.perPage) + rowIndex + 1;
                    content = serialNo;
                } else if (column.render) {
                    content = column.render(rowData, rowIndex, this);
                } else if (column.field) {
                    content = th_escapeHtml(rowData[column.field] ?? 'N/A');
                } else {
                    content = th_escapeHtml(rowData[colIndex] ?? 'N/A');
                }

                const align = column.align ? ` style="text-align: ${column.align}"` : '';
                const dataLabel = column.name || column.header || `Column ${colIndex + 1}`;
                const dataLabelAttr = ` data-label="${dataLabel}"`;
                const colspanVal = column.colspan != null
                    ? (typeof column.colspan === 'function' ? column.colspan(rowData, rowIndex) : column.colspan)
                    : 1;
                if (colspanVal > 1) skipCols = colspanVal - 1;
                const colspanAttr = colspanVal > 1 ? ` colspan="${colspanVal}"` : '';
                const rowspanVal = column.rowspan != null
                    ? (typeof column.rowspan === 'function' ? column.rowspan(rowData, rowIndex) : column.rowspan)
                    : 0;
                if (rowspanVal > 1) rowspanRemaining[colIndex] = rowspanVal - 1;
                const rowspanAttr = rowspanVal > 1 ? ` rowspan="${rowspanVal}"` : '';
                cellsHtml += `<td class="${column.class || ''}"${colspanAttr}${rowspanAttr}${align}${dataLabelAttr}><span class="cell-value">${content}</span></td>`;
            });

            return `<tr class="${rowClass}" data-row-index="${rowIndex}" data-row-id="${rowIdStr}">${cellsHtml}</tr>`;
        }).join('');

        return `<tbody>${rows}</tbody>`;
    }

    renderPagination() {
        if (!this.config.pagination) return '';

        const { currentPage, totalPages } = this;
        const maxVisible = 5;
        let startPage = Math.max(1, currentPage - Math.floor(maxVisible / 2));
        let endPage = Math.min(totalPages, startPage + maxVisible - 1);
        if (endPage - startPage < maxVisible - 1) {
            startPage = Math.max(1, endPage - maxVisible + 1);
        }

        let pages = '';
        if (startPage > 1) {
            pages += `<li class="page-item"><a class="page-link" href="javascript:void(0);" data-page="1">1</a></li>`;
            if (startPage > 2) pages += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
        }

        for (let i = startPage; i <= endPage; i++) {
            pages += `<li class="page-item ${i === currentPage ? 'active' : ''}"><a class="page-link" href="javascript:void(0);" data-page="${i}">${i}</a></li>`;
        }

        if (endPage < totalPages) {
            if (endPage < totalPages - 1) pages += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
            pages += `<li class="page-item"><a class="page-link" href="javascript:void(0);" data-page="${totalPages}">${totalPages}</a></li>`;
        }

        const from = ((currentPage - 1) * this.config.perPage) + 1;
        const to = Math.min(currentPage * this.config.perPage, this.totalRecords);

        // Build rows per page options dropdown
        const showPerPage = this.config.perPageOptions && this.config.perPageOptions.length > 0;

        let perPageHtml = '';
        if (showPerPage) {
            const perPageOptions = this.config.perPageOptions.map(option =>
                `<option value="${option}" ${option === this.config.perPage ? 'selected' : ''}>${option}</option>`
            ).join('');

            perPageHtml = `<div class="cq-per-page">
                <label for="${this.config.containerId}-per-page" class="form-label me-2 mb-0">Rows per page:</label>
                <select id="${this.config.containerId}-per-page" class="form-select form-select-sm" style="width: 80px; display: inline-block;" data-table-id="${this.config.containerId}">
                    ${perPageOptions}
                </select>
            </div>`;
        }

        return `<div class="d-flex justify-content-between align-items-center mt-2 gap-3 flex-wrap">
            ${perPageHtml}
            <nav aria-label="Page navigation">
                <ul class="pagination pagination-sm mb-0">
                    <li class="page-item ${currentPage === 1 ? 'disabled' : ''}"><a class="page-link" href="javascript:void(0);" data-page="${currentPage - 1}">Previous</a></li>
                    ${pages}
                    <li class="page-item ${currentPage === totalPages ? 'disabled' : ''}"><a class="page-link" href="javascript:void(0);" data-page="${currentPage + 1}">Next</a></li>
                </ul>
            </nav>
            <div class="text-muted"><small>Showing ${from} to ${to} of ${this.totalRecords} entries</small></div>
        </div>`;
    }

    /**
     * Render bulk action toolbar
     * Shows when rows are selected with action buttons
     */
    renderBulkActionToolbar() {
        const container = document.getElementById(this.config.containerId);
        const toolbarId = `${this.config.containerId}-bulk-toolbar`;
        let toolbar = document.getElementById(toolbarId);

        if (!toolbar) {
            toolbar = document.createElement('div');
            toolbar.id = toolbarId;
            toolbar.className = 'mb-3 p-3 bg-light border rounded d-none';
            toolbar.style.display = 'none';
            container.parentNode.insertBefore(toolbar, container);
        }

        const selectedCount = this.selectedRows.size;
        if (!this._isCheckboxEnabled() || selectedCount === 0) {
            toolbar.classList.add('d-none');
            toolbar.style.display = 'none';
            return;
        }

        // Show toolbar
        toolbar.classList.remove('d-none');
        toolbar.style.display = 'block';

        // Build action buttons
        const actionButtons = this.bulkActions.map((action, index) => {
            const btnClass = action.class || 'btn btn-sm btn-primary';
            const icon = action.icon ? `<i class="${action.icon}"></i> ` : '';
            const label = action.label || action.title || 'Action';
            // Add spacing (ms-2 = margin-start) to each button except first
            const spacing = index > 0 ? 'ms-2' : '';
            return `
                <button type="button" class="${btnClass} ${spacing}" data-bulk-action-index="${index}" title="${label}">
                    ${icon}${label}
                </button>
            `;
        }).join('');

        toolbar.innerHTML = `
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <strong>${selectedCount} row(s) selected</strong>
                </div>
                <div class="d-flex gap-2 align-items-center">
                    ${actionButtons}
                    <button type="button" class="btn btn-sm btn-secondary" id="${toolbarId}-clear">
                        Clear Selection
                    </button>
                </div>
            </div>
        `;

        // Re-attach event handlers after toolbar HTML is updated
        this._setupToolbarHandlers();
    }

    /**
     * Setup event handlers for toolbar buttons (called after toolbar HTML is rendered)
     */
    _setupToolbarHandlers() {
        const toolbarId = `${this.config.containerId}-bulk-toolbar`;
        const toolbar = document.getElementById(toolbarId);

        if (!toolbar || this.selectedRows.size === 0) return;

        // Bulk action buttons
        toolbar.querySelectorAll('[data-bulk-action-index]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();

                const actionIndex = parseInt(btn.dataset.bulkActionIndex);
                const action = this.bulkActions[actionIndex];


                if (action && typeof action.handler === 'function') {
                    try {
                        const selectedData = this.getSelectedRows();
                        // console.log('Selected data:', selectedData);
                        action.handler(selectedData, this);
                    } catch (error) {
                        console.error('Error in bulk action handler:', error);
                        if (typeof $ !== 'undefined' && $.toast) {
                            $.toast({ heading: 'Error', text: error.message, icon: 'error', position: 'top-right' });
                        } else {
                            alert('Error: ' + error.message);
                        }
                    }
                } else {
                    console.warn('Action not found or handler not a function:', { action });
                }
            });
        });

        // Clear selection button
        const clearBtn = toolbar.querySelector(`#${toolbarId}-clear`);
        if (clearBtn) {
            clearBtn.addEventListener('click', () => {
                this.clearAllSelections();
            });
        }
    }

    loadTable(page = 1) {
        this.currentPage = page;
        const container = document.getElementById(this.config.containerId);

        // Show animated loader
        this._showTableLoader('Fetching data...');

        fetch(this.buildApiUrl(page), { credentials: 'same-origin' })
            .then(response => response.json().then(result => ({ response, result })))
            .then(({ result }) => {
                // Hide loader when data arrives
                this._hideTableLoader();

                if (result.success === false) {
                    this._showAlert(result.icon || 'error', result.title || 'Error', result.message || 'Error loading data');
                    container.innerHTML = '';
                    this.gridData = [];
                    this.totalRecords = this.totalPages = 0;
                    this.renderTable([]);
                    return;
                }

                let data = [], total = 0;
                if (Array.isArray(result.data)) {
                    data = result.data;
                    total = result.total || 0;
                } else if (result.data?.data) {
                    data = result.data.data;
                    total = result.data.total || result.total || 0;
                } else if (Array.isArray(result.rows)) {
                    data = result.rows;
                    total = result.total || result.rows.length;
                } else if (Array.isArray(result)) {
                    data = result;
                    total = result.length;
                } else {
                    this._showAlert('warning', 'Warning', 'Unexpected response format from server');
                    this.renderTable([]);
                    return;
                }

                const checkboxEnabled = this._isCheckboxEnabled();

                this.totalRecords = total;
                this.totalPages = Math.ceil(total / this.config.perPage);
                this.gridData = data;

                if (!checkboxEnabled && this.selectedRows.size > 0) {
                    this.selectedRows.clear();
                }

                // Auto-generate column filters whenever data is available and not yet completed
                // This handles the case where initial load has no data, but filtering brings data
                let filtersGenerated = false;
                if (!this._autoGenerateCompleted && data.length > 0) {
                    filtersGenerated = this._autoGenerateColumnFilters(data);
                    // Only mark as completed if filters were actually generated
                    if (filtersGenerated) {
                        this._autoGenerateCompleted = true;
                    }
                }

                this.config.onDataLoaded?.(data, result);

                // If filters were generated, regenerate entire table (including header with new filters)
                // Otherwise, just update the body
                const checkboxVisibilityChanged = this._lastRenderedCheckboxEnabled !== checkboxEnabled;

                if (filtersGenerated || checkboxVisibilityChanged) {
                    this.renderTable(data);
                } else {
                    this.renderTableBody(data);
                }

                // Fetch totals if configured
                if (this.totals && this.totals.enabled && this.totals.apiUrl) {
                    this._fetchAndRenderTotals();
                }
            })
            .catch(error => {
                // Hide loader on error
                this._hideTableLoader();

                console.error('Error:', error);
                this._showAlert('error', 'Error', 'Error loading data. Please try again.');
                container.innerHTML = '';
                this.gridData = [];
                this.totalRecords = this.totalPages = 0;
                this.renderTable([]);
            });
    }

    renderTable(data) {
        const container = document.getElementById(this.config.containerId);
        const searchId = `${this.config.containerId}-search-wrapper`;
        this._lastRenderedCheckboxEnabled = this._isCheckboxEnabled();

        if (this.config.search && !document.getElementById(searchId)) {
            const wrapper = document.createElement('div');
            wrapper.id = searchId;
            wrapper.className = 'mb-1 d-flex gap-2 align-items-center op-search-container'; // Lower margin as we use modal structure

            // Get search configuration
            const searchConfig = typeof this.config.search === 'object' ? this.config.search : {};
            const placeholder = searchConfig.placeholder || 'Search...';
            const searchParam = searchConfig.param || 'search';
            const searchButtonLabel = searchConfig.buttonLabel || 'Search';
            const searchInputStyle = searchConfig.inputStyle || 'max-width: 320px; flex: 1;';
            const searchButtonStyle = searchConfig.buttonStyle || ''; // Let CSS handle it
            const isPremium = searchConfig.style === 'premium'; // Check if premium style is requested

            // Render based on premium style
            if (isPremium) {
                // Premium teal design: rounded input-group with teal button (Only for specific tables)
                wrapper.innerHTML = `
                    <div class="input-group" style="${searchInputStyle}">
                        <input type="text" id="${this.config.containerId}-search" class="form-control" placeholder="${placeholder}" style="border-color:#e2e8f0; background:#f8fafc; border-radius:8px 0 0 8px; height:36px; font-size:0.875rem;">
                        <button type="button" id="${this.config.containerId}-search-btn" class="btn op-btn-search" style="border-radius:0 8px 8px 0 !important;">
                            ${searchButtonLabel}
                        </button>
                    </div>
                `;
            } else {
                // Normal default design (Bootstrap standard styling)
                wrapper.innerHTML = `
                    <div class="input-group" style="${searchInputStyle}">
                        <input type="text" id="${this.config.containerId}-search" class="form-control" placeholder="${placeholder}">
                        <button type="button" id="${this.config.containerId}-search-btn" class="btn btn-secondary">
                            ${searchButtonLabel}
                        </button>
                    </div>
                `;
            }

            container.parentNode.insertBefore(wrapper, container);
            this.setupSearchHandler();
        }

        // Add sticky header styling if enabled (default: true)
        const stickyHeaderClass = this.config.stickyHeader !== false ? 'table-sticky-header' : '';
        const tableResponsiveClass = stickyHeaderClass ? 'table-responsive-sticky' : 'table-responsive';

        container.innerHTML = `
            <div class="${tableResponsiveClass}">
                <table class="${this.config.tableClass || 'table'} ${stickyHeaderClass}">
                    ${this.renderHeader()}${this.renderBody(data)}
                </table>
            </div>
        `;

        // Inject table styles (sorting, loading, search, sticky header) - always necessary for global styles
        this._injectStickyHeaderStyles();

        if (this.config.pagination) {
            const pagId = `${this.config.containerId}-pagination`;
            const existing = document.getElementById(pagId);
            if (existing) existing.remove();
            const wrapper = document.createElement('div');
            wrapper.id = pagId;
            wrapper.className = 'cq-grid-pagination';
            wrapper.innerHTML = this.renderPagination();
            container.parentNode.insertBefore(wrapper, container.nextSibling);
        }

        this.setupTableEvents();
        this.setupColumnFilterEvents();

        // Apply dynamic height based on rows per page
        if (this.config.stickyHeader !== false) {
            this._applyTableHeight();
        }
    }

    /**
     * Update only the table body and pagination without reloading the entire table
     * Used for filter/search operations to avoid page flicker
     */
    renderTableBody(data) {
        const container = document.getElementById(this.config.containerId);
        const table = container.querySelector('table');

        if (!table) {
            // If table doesn't exist, render the full table
            this.renderTable(data);
            return;
        }

        // Update only the tbody
        const tbody = table.querySelector('tbody');
        if (tbody) {
            tbody.innerHTML = this.renderBody(data);
        }

        // Update pagination if enabled
        if (this.config.pagination) {
            const pagId = `${this.config.containerId}-pagination`;
            const existing = document.getElementById(pagId);
            if (existing) {
                existing.innerHTML = this.renderPagination();
            }
        }

        // Re-attach event listeners
        this.setupTableEvents();

        // Apply dynamic height when per_page changes
        if (this.config.stickyHeader !== false) {
            this._applyTableHeight();
        }
    }

    setupSearchHandler() {
        if (!this.config.search) return;
        const searchInput = document.getElementById(`${this.config.containerId}-search`);
        const searchBtn = document.getElementById(`${this.config.containerId}-search-btn`);
        if (!searchInput) return;

        if (this.searchTerm) searchInput.value = this.searchTerm;

        // Search button click event
        if (searchBtn) {
            searchBtn.addEventListener('click', () => {
                this.searchTerm = searchInput.value.trim();

                // Clear all other filters when performing global search, unless the page opts to
                // keep them (globalSearchStandalone) — then the inputs stay visible in the UI and
                // getFilterValues() simply omits them from the request while a search is active.
                if (!this.config.globalSearchStandalone) {
                    this._clearOtherFilters();
                }

                this.currentPage = 1;
                this.loadTable(1);
            });
        }

        // Enter key in search input to trigger search
        searchInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                this.searchTerm = searchInput.value.trim();

                // Clear all other filters when performing global search, unless the page opts to
                // keep them (globalSearchStandalone) — then the inputs stay visible in the UI and
                // getFilterValues() simply omits them from the request while a search is active.
                if (!this.config.globalSearchStandalone) {
                    this._clearOtherFilters();
                }

                this.currentPage = 1;
                this.loadTable(1);
            } else if (e.key === 'Escape') {
                searchInput.value = this.searchTerm = '';
                this.currentPage = 1;
                this.loadTable(1);
            }
        });
    }

    setupTableEvents() {
        const { onRowClick } = this.config;
        const container = `#${this.config.containerId}`;

        document.querySelectorAll(`${container} tbody tr`).forEach(row => {
            row.addEventListener('click', (e) => {
                if (e.target.tagName === 'A' || e.target.tagName === 'BUTTON' || e.target.closest('a, button')) return;
                
                const rowId = row.dataset.rowId;
                const index = parseInt(row.dataset.rowIndex);
                const rowData = this.gridData[index];

                // Handle Shift+Click for row selection (when checkboxes are enabled)
                if (this._isCheckboxEnabled() && e.shiftKey && this._lastSelectedRowId !== null) {
                    const allRows = Array.from(document.querySelectorAll(`${container} tbody tr`));
                    const lastIndex = allRows.findIndex(r => r.dataset.rowId === this._lastSelectedRowId);
                    const currentIndex = allRows.findIndex(r => r.dataset.rowId === rowId);

                    if (lastIndex !== -1 && currentIndex !== -1) {
                        const start = Math.min(lastIndex, currentIndex);
                        const end = Math.max(lastIndex, currentIndex);
                        const checkboxes = document.querySelectorAll(`${container} .row-checkbox`);

                        // Select/deselect all rows in range based on target row's checkbox state
                        const targetCheckbox = document.querySelector(`${container} .row-checkbox[data-row-id="${rowId}"]`);
                        const shouldSelect = targetCheckbox ? !targetCheckbox.checked : true;

                        for (let i = start; i <= end; i++) {
                            const r = allRows[i];
                            const id = r.dataset.rowId;
                            const cb = checkboxes[i];
                            if (shouldSelect) {
                                this.selectRow(id);
                                if (cb) cb.checked = true;
                                r.classList.add('table-row-selected');
                            } else {
                                this.deselectRow(id);
                                if (cb) cb.checked = false;
                                r.classList.remove('table-row-selected');
                            }
                        }

                        this._lastSelectedRowId = rowId;
                        onRowClick?.(rowData, index, e);
                        return;
                    }
                }

                // Normal row click behavior (for highlighting, not selection)
                if (e.target.type === 'checkbox' || e.target.closest('.row-checkbox')) return;

                document.querySelector(`${container} tbody tr.table-row-selected`)?.classList.remove('table-row-selected');

                if (this.selectedRowId === rowId) {
                    this.selectedRowId = null;
                    row.classList.remove('table-row-selected');
                } else {
                    this.selectedRowId = rowId;
                    row.classList.add('table-row-selected');
                }

                onRowClick?.(rowData, index, e);
            });
        });

        // Custom dropdown handler — click-based, position fixed to viewport
        const tblContainer = document.getElementById(this.config.containerId);
        if (tblContainer && !tblContainer._thDropdownAttached) {
            tblContainer._thDropdownAttached = true;

            // Click to open/close dropdown
            tblContainer.addEventListener('click', (e) => {
                const dropdown = e.target.closest('.th-dropdown');
                if (!dropdown) return;

                const btn = dropdown.querySelector('.th-dropdown-btn');
                const menu = dropdown.querySelector('.th-dropdown-menu');
                if (!btn || !menu) return;

                // Only handle clicks on the dropdown button itself, not on menu items
                // This allows onclick handlers on menu items to work properly
                if (!e.target.closest('.th-dropdown-btn')) {
                    return;
                }

                // Prevent event from bubbling up (only for button clicks)
                e.stopPropagation();

                // Close all other open menus
                document.querySelectorAll('.th-dropdown-menu.show').forEach(m => {
                    if (m !== menu) m.classList.remove('show');
                });

                // Toggle current menu
                const isOpen = menu.classList.contains('show');
                if (isOpen) {
                    menu.classList.remove('show');
                } else {
                    const rect = btn.getBoundingClientRect();
                    menu.style.top = rect.bottom + 2 + 'px';
                    menu.style.left = rect.left + 'px';
                    menu.classList.add('show');
                }
            }, true);

            // Close dropdown when clicking outside the table container
            document.addEventListener('click', (e) => {
                // Check if click is outside the table container
                if (!e.target.closest(`#${this.config.containerId}`)) {
                    document.querySelectorAll('.th-dropdown-menu.show').forEach(m => {
                        m.classList.remove('show');
                    });
                }
            }, true);

            // Close dropdown when hovering over sidebar navigation
            const sidebar = document.querySelector('.topnav');
            if (sidebar) {
                sidebar.addEventListener('mouseenter', () => {
                    document.querySelectorAll('.th-dropdown-menu.show').forEach(m => {
                        m.classList.remove('show');
                    });
                }, true);
            }
        }

        // Setup checkbox event handlers if enabled
        if (this._isCheckboxEnabled()) {
            this._setupCheckboxHandlers();
        }

        // Setup action button event handlers
        this._setupActionButtons();

        if (this.config.pagination) {
            const pagContainer = document.getElementById(`${this.config.containerId}-pagination`);
            pagContainer?.querySelectorAll('.page-link').forEach(link => {
                link.addEventListener('click', (e) => {
                    e.preventDefault();
                    const page = parseInt(link.dataset.page);
                    if (page && page !== this.currentPage && page >= 1 && page <= this.totalPages) {
                        this.loadTable(page);
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                    }
                });
            });

            // Setup rows per page dropdown
            const perPageSelect = document.getElementById(`${this.config.containerId}-per-page`);
            if (perPageSelect) {
                perPageSelect.addEventListener('change', (e) => {
                    const newPerPage = parseInt(e.target.value);
                    if (newPerPage && newPerPage !== this.config.perPage) {
                        this.config.perPage = newPerPage;
                        this.currentPage = 1;  // Reset to first page when changing per_page
                        this.loadTable(1);
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                    }
                });
            }
        }
    }

    /**
     * Setup checkbox event handlers for row selection
     */
    _setupCheckboxHandlers() {
        const container = document.getElementById(this.config.containerId);
        if (!container) return;

        // Track last selected row for Shift+Click
        this._lastSelectedRowId = null;

        // Track Shift key state globally
        this._shiftKeyPressed = false;

        // Global Shift key tracking
        document.removeEventListener('keydown', this._shiftKeyHandler);
        document.removeEventListener('keyup', this._shiftKeyHandler);
        this._shiftKeyHandler = (e) => {
            if (e.key === 'Shift') {
                this._shiftKeyPressed = e.type === 'keydown';
            }
        };
        document.addEventListener('keydown', this._shiftKeyHandler);
        document.addEventListener('keyup', this._shiftKeyHandler);

        // Select all checkbox
        const selectAllCheckbox = container.querySelector('#select-all-checkbox');
        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', (e) => {
                if (e.target.checked) {
                    this.selectAllRows();
                } else {
                    this.clearAllSelections();
                    this._lastSelectedRowId = null;
                }
            });
        }

        // Individual row checkboxes - use click event (not change) to get shiftKey
        container.removeEventListener('click', this._checkboxClickHandler);
        this._checkboxClickHandler = (e) => {
            if (!e.target.classList.contains('row-checkbox')) return;

            const checkbox = e.target;
            const rowId = checkbox.dataset.rowId;
            const rowIndex = parseInt(checkbox.dataset.rowIndex || '0');

            // Handle Shift+Click for range selection
            if (this._shiftKeyPressed && this._lastSelectedRowId !== null && this._lastSelectedRowId !== rowId) {
                e.preventDefault();
                e.stopPropagation();

                // Get all checkboxes in order
                const allCheckboxes = Array.from(container.querySelectorAll('.row-checkbox'));

                const lastIndex = allCheckboxes.findIndex(cb => cb.dataset.rowId === this._lastSelectedRowId);
                const currentIndex = allCheckboxes.findIndex(cb => cb.dataset.rowId === rowId);

                if (lastIndex !== -1 && currentIndex !== -1 && lastIndex !== currentIndex) {
                    const start = Math.min(lastIndex, currentIndex);
                    const end = Math.max(lastIndex, currentIndex);

                    // Determine if we're selecting or deselecting based on current checkbox state
                    const shouldSelect = !checkbox.checked; // checkbox.checked is the OLD state before click

                    // Select/deselect all rows in range
                    for (let i = start; i <= end; i++) {
                        const cb = allCheckboxes[i];
                        const id = cb.dataset.rowId;
                        cb.checked = shouldSelect;
                        if (shouldSelect) {
                            this.selectRow(id);
                        } else {
                            this.deselectRow(id);
                        }
                    }
                }
            } else {
                // Normal single row selection
                if (checkbox.checked) {
                    this.selectRow(rowId);
                } else {
                    this.deselectRow(rowId);
                }
            }

            // Update last selected row
            this._lastSelectedRowId = rowId;
        };

        container.addEventListener('click', this._checkboxClickHandler, true);
    }

    /**
     * Ensures date range filters are set to today's date if they exist but are not set
     */
    _ensureDateRangeFilters() {
        const { filters } = this.config;
        if (filters.dateRange) {
            const todayBS = this._getTodayBS();
            const { fromId = 'from-datepicker', toId = 'to-datepicker' } = filters.dateRange;

            // Check if date range elements exist and are empty
            const fromEl = document.getElementById(fromId);
            const toEl = document.getElementById(toId);

            if (fromEl && !fromEl.value) {
                fromEl.value = todayBS;
            }
            if (toEl && !toEl.value) {
                toEl.value = todayBS;
            }

            // Also update jQuery if it's available
            if (typeof $ !== 'undefined') {
                if (fromEl && !$(`#${fromId}`).val()) {
                    $(`#${fromId}`).val(todayBS);
                }
                if (toEl && !$(`#${toId}`).val()) {
                    $(`#${toId}`).val(todayBS);
                }
            }
        }
    }

    /**
     * Clears all other filters except global search
     */
    _clearOtherFilters() {
        const { filters } = this.config;

        // Clear date range filters
        if (filters.dateRange) {
            const { fromId = 'from-datepicker', toId = 'to-datepicker' } = filters.dateRange;
            if (typeof $ !== 'undefined') {
                $(`#${fromId}`).val('');
                $(`#${toId}`).val('');
            } else {
                const fromEl = document.getElementById(fromId);
                const toEl = document.getElementById(toId);
                if (fromEl) fromEl.value = '';
                if (toEl) toEl.value = '';
            }
        }

        // Clear additional filters
        if (filters.additional && Array.isArray(filters.additional)) {
            filters.additional.forEach(filter => {
                const filterId = typeof filter === 'string' ? filter : filter.id;
                if (typeof $ !== 'undefined') {
                    $(`#${filterId}`).val('');
                } else {
                    const el = document.getElementById(filterId);
                    if (el) el.value = '';
                }
            });
        }

        // Clear column filter values
        if (filters.columnFilters && Array.isArray(filters.columnFilters)) {
            this._normalizedColumnFilters.forEach(def => {
                const el = document.getElementById(def.id);
                if (el) el.value = '';
            });
            // Reset stored column filter values
            this.columnFilterValues = {};
        }

        // Reset filter storage objects
        this.filterValues = {};
    }

    /**
     * Clears global search when other filters are applied
     */
    _clearGlobalSearch() {
        if (this.config.search) {
            const searchInput = document.getElementById(`${this.config.containerId}-search`);
            if (searchInput) {
                searchInput.value = '';
            }
            this.searchTerm = '';
        }
    }

    /**
     * Setup event handlers for action buttons
     */
    _setupActionButtons() {
        const container = `#${this.config.containerId}`;
        const actionButtons = document.querySelectorAll(`${container} [data-action-type]`);

        actionButtons.forEach(button => {
            // Remove existing listeners to prevent duplicates
            const newButton = button.cloneNode(true);
            button.parentNode.replaceChild(newButton, button);

            newButton.addEventListener('click', (e) => {
                e.stopPropagation(); // Prevent row click

                const actionType = newButton.dataset.actionType;
                const actionIndex = parseInt(newButton.dataset.actionIndex);
                const rowIndex = parseInt(newButton.dataset.rowIndex);
                const rowData = this.gridData[rowIndex];

                // Parse row data if available
                let parsedRowData = rowData;
                try {
                    if (newButton.dataset.rowData) {
                        parsedRowData = JSON.parse(newButton.dataset.rowData);
                    }
                } catch (e) {
                    console.warn('Failed to parse row data:', e);
                }

                // Check for confirmation
                const confirmMsg = newButton.dataset.confirm;
                if (confirmMsg) {
                    const confirmTitle = newButton.dataset.confirmTitle || 'Confirm';
                    if (!confirm(confirmMsg)) {
                        return;
                    }
                }

                this._handleAction(newButton, actionType, actionIndex, parsedRowData, rowIndex);
            });
        });

        // Initialize tooltips if Bootstrap is available
        if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
            const tooltipElements = document.querySelectorAll(`${container} [data-bs-toggle="tooltip"]`);
            tooltipElements.forEach(el => {
                new bootstrap.Tooltip(el);
            });
        }
    }

    /**
     * Handle action button click
     */
    _handleAction(button, actionType, actionIndex, rowData, rowIndex) {
        const modal = button.dataset.modal;
        const url = button.dataset.url;
        const onClick = button.onclick;

        // Find action configuration by matching action index first (most reliable)
        const columns = this.config.columns || [];
        let actionConfig = null;

        for (const col of columns) {
            if ((col.type === 'actions' || col.actions) && col.actions) {
                // If we have an actionIndex, use it directly
                if (!isNaN(actionIndex) && actionIndex >= 0 && col.actions[actionIndex]) {
                    actionConfig = col.actions[actionIndex];
                    break;
                }

                // Fall back to finding action by type/action name
                const action = col.actions.find(a => {
                    if (typeof a === 'string') {
                        return a === actionType;
                    }
                    const aType = a.type || a.action;
                    return aType === actionType;
                });

                if (action) {
                    actionConfig = typeof action === 'string' ? { type: action } : action;
                    break;
                }
            }
        }

        // Handle modal opening
        if (modal) {
            this._openModal(modal, rowData);
        }

        // Handle URL navigation
        if (url) {
            // Replace placeholders in URL with row data
            let finalUrl = url;
            Object.keys(rowData || {}).forEach(key => {
                finalUrl = finalUrl.replace(`{${key}}`, rowData[key]);
            });
            window.location.href = finalUrl;
        }

        // Handle custom action from config (check both properties)
        if (actionConfig) {
            // Try onClick first (primary handler)
            if (typeof actionConfig.onClick === 'function') {
                actionConfig.onClick(rowData, rowIndex, button, this);
                return; // If onClick exists, use it and skip action
            }

            // Fall back to action property
            if (typeof actionConfig.action === 'function') {
                actionConfig.action(rowData, rowIndex, button, this);
                return;
            }
        }

        // Handle inline onclick (if set via render)
        if (onClick && typeof onClick === 'function') {
            onClick.call(button, rowData, rowIndex, button, this);
        }
    }

    setupColumnFilterEvents() {
        if (!this._normalizedColumnFilters.length) return;

        const onChange = th_debounce(() => {
            // Like search: store filter state on instance BEFORE calling loadTable
            this.columnFilterValues = {};
            this._normalizedColumnFilters.forEach(def => {
                const el = document.getElementById(def.id);
                if (el && el.value != null && el.value !== '') {
                    this.columnFilterValues[def.param] = el.value;
                }
            });

            // Clear global search when column filters are applied
            this._clearGlobalSearch();

            // Auto-load today's date range if date range filters exist but are not set
            // this._ensureDateRangeFilters();

            this.currentPage = 1;
            this.loadTable(1);
        }, 500);

        this._normalizedColumnFilters.forEach(def => {
            const el = document.getElementById(def.id);
            if (!el) return;
            const params = this.getFilterValues();
            if (params[def.param] != null && el.value !== params[def.param]) {
                el.value = params[def.param];
            }
            const eventName = def.type === 'select' ? 'change' : 'input';
            el.addEventListener(eventName, () => onChange());
        });
    }

    clearFilters() {
        const { filters } = this.config;
        const todayBS = this._getTodayBS();

        if (filters.dateRange) {
            const { fromId = 'from-datepicker', toId = 'to-datepicker' } = filters.dateRange;
            $(`#${fromId}`).val(todayBS);
            $(`#${toId}`).val(todayBS);
        }

        const resetFilter = (filter) => {
            const filterId = typeof filter === 'string' ? filter : filter.id;
            const defaultValue = typeof filter === 'string' ? '' : (filter.defaultValue ?? '');
            $(`#${filterId}`).val(defaultValue);
        };

        filters.additional?.forEach(resetFilter);
        filters.columnFilters?.forEach((def) => {
            const id = def.id || `${this.config.containerId}-cf-${(def.param || def.field || '')}`;
            const el = document.getElementById(id);
            if (el) el.value = '';
        });

        // Clear global search
        if (this.config.search) {
            const searchInput = document.getElementById(`${this.config.containerId}-search`);
            if (searchInput) searchInput.value = '';
            this.searchTerm = '';
        }

        // Reset stored filter values
        this.columnFilterValues = {};
        this.filterValues = {};

        // Reset sorting state
        this.sortField = null;
        this.sortDirection = null;

        // Clear sort indicators in the UI
        $('.sort-indicator').removeClass('sort-asc sort-desc');

        // Clear cached totals
        this.totalsCached = null;
        this._removeTotalsRow();

        // Page-level clear hook (e.g. reset select2 widgets) — runs before the reload so any
        // custom resets are reflected. Use this instead of binding a second #clearFilters handler,
        // which would call clearFilters() twice and double the table/totals requests.
        if (typeof this.config.onClear === 'function') {
            this.config.onClear();
        }

        this.currentPage = 1;
        this.loadTable();
    }

    /**
     * Clear only the global search while keeping other filters
     */
    clearGlobalSearch() {
        if (this.config.search) {
            const searchInput = document.getElementById(`${this.config.containerId}-search`);
            if (searchInput) {
                searchInput.value = '';
            }
            this.searchTerm = '';
            // Reload table to reflect cleared search
            this.loadTable(this.currentPage);
        }
    }

    /**
     * Clear only other filters while keeping global search
     */
    clearOtherFilters() {
        this._clearOtherFilters();
        // Reload table to reflect cleared filters
        this.loadTable(this.currentPage);
    }

    getRowData(index) { return this.gridData[index]; }
    getAllData() { return this.gridData; }

    /**
     * Get row ID from row object and index
     * Consistent method to extract row ID used everywhere
     */
    _getRowId(row, rowIndex) {
        return row.id !== undefined ? row.id : rowIndex;
    }

    /**
     * Get all selected rows with their data
     * @returns {Array} Array of {id, data} objects for selected rows
     */
    getSelectedRows() {
        const selectedRowsData = [];
        this.gridData.forEach((row, rowIndex) => {
            const rowId = this._getRowId(row, rowIndex);
            // Convert to string for consistent comparison
            const rowIdStr = String(rowId);

            if (this.selectedRows.has(rowIdStr)) {
                const rowData = row.data || row;
                selectedRowsData.push({
                    id: rowId,
                    data: rowData
                });
            }
        });
        return selectedRowsData;
    }

    /**
     * Get count of selected rows
     */
    getSelectedCount() {
        return this.selectedRows.size;
    }

    /**
     * Select a row by ID
     */
    selectRow(rowId) {
        const rowIdStr = String(rowId); // Ensure string for consistent Set comparison
        this.selectedRows.add(rowIdStr);
        this._updateCheckboxUI();
        this.renderBulkActionToolbar();
        if (this.onSelectionChange) this.onSelectionChange(this.selectedRows);
    }

    /**
     * Deselect a row by ID
     */
    deselectRow(rowId) {
        const rowIdStr = String(rowId); // Ensure string for consistent Set comparison
        this.selectedRows.delete(rowIdStr);
        this._updateCheckboxUI();
        this.renderBulkActionToolbar();
        if (this.onSelectionChange) this.onSelectionChange(this.selectedRows);
    }

    /**
     * Select all rows on current page
     */
    selectAllRows() {
        this.gridData.forEach((row, rowIndex) => {
            const rowId = this._getRowId(row, rowIndex);
            const rowIdStr = String(rowId);
            this.selectedRows.add(rowIdStr);
        });
        this._updateCheckboxUI();
        this.renderBulkActionToolbar();
        if (this.onSelectionChange) this.onSelectionChange(this.selectedRows);
    }

    /**
     * Clear all selections
     */
    clearAllSelections() {
        this.selectedRows.clear();
        this._updateCheckboxUI();
        this.renderBulkActionToolbar();
        if (this.onSelectionChange) this.onSelectionChange(this.selectedRows);
    }

    /**
     * Update checkbox UI to match selectedRows state
     */
    _updateCheckboxUI() {
        const container = document.getElementById(this.config.containerId);
        if (!container) return;

        // Update individual row checkboxes
        container.querySelectorAll('.row-checkbox').forEach(checkbox => {
            const rowIdStr = checkbox.dataset.rowId; // Already stored as string from renderBody
            checkbox.checked = this.selectedRows.has(rowIdStr);
        });

        // Update select-all checkbox
        const selectAllCheckbox = container.querySelector('#select-all-checkbox');
        if (selectAllCheckbox && this.gridData.length > 0) {
            const allSelected = this.gridData.every((row, rowIndex) => {
                const rowId = this._getRowId(row, rowIndex);
                const rowIdStr = String(rowId);
                return this.selectedRows.has(rowIdStr);
            });
            const someSelected = this.gridData.some((row, rowIndex) => {
                const rowId = this._getRowId(row, rowIndex);
                const rowIdStr = String(rowId);
                return this.selectedRows.has(rowIdStr);
            });
            selectAllCheckbox.checked = allSelected;
            selectAllCheckbox.indeterminate = someSelected && !allSelected;
        }
    }

    refresh() { this.loadTable(this.currentPage); }

    /**
     * Get all currently applied filters as an object
     * Used for export and other operations that need all filter values
     */
    getAppliedFilters() {
        return this.getFilterValues();
    }

    updateConfig(newConfig) {
        this.config = { ...this.config, ...newConfig };
        this._normalizedColumnFilters = this._normalizeColumnFilters(
            (this.config.filters && this.config.filters.columnFilters) || []
        );
        this.loadTable(this.currentPage);
    }

    /**
     * Dynamically update columns and re-render table without losing current page/filters
     * Preserves current page number, filters, and search while updating column structure
     * @param {Array|Function} newColumns - New column definitions array or callback function
     */
    updateColumns(newColumns) {
        // If newColumns is a function, call it to get the actual columns
        const columns = typeof newColumns === 'function' ? newColumns() : newColumns;

        // Normalize the new columns
        this.config.columns = this._normalizeColumns(columns || []);

        // Re-render only the table (header and body) without calling loadTable
        // This preserves current page and all filter states
        const container = document.getElementById(this.config.containerId);

        // Add sticky header styling if enabled (default: true)
        const stickyHeaderClass = this.config.stickyHeader !== false ? 'table-sticky-header' : '';
        const tableResponsiveClass = stickyHeaderClass ? 'table-responsive-sticky' : 'table-responsive';

        container.innerHTML = `
            <div class="${tableResponsiveClass}">
                <table class="${stickyHeaderClass}">
                    ${this.renderHeader()}${this.renderBody(this.gridData)}
                </table>
            </div>
        `;

        // Inject table styles (sorting, loading, search, sticky header) - always necessary for global styles
        this._injectStickyHeaderStyles();

        // Re-attach event handlers
        this.setupTableEvents();
        this.setupColumnFilterEvents();

        // Apply dynamic height based on current data
        if (this.config.stickyHeader !== false) {
            this._applyTableHeight();
        }
    }

    /**
     * Build columns with conditional additions based on filter values
     * Used for conditional column rendering like cancelled status columns
     * @param {Object} baseColumns - Base column definitions
     * @param {Object} conditions - Filter conditions {filterName: filterValue}
     * @param {Object} conditionalColumns - Columns to add if condition matches {filterName: [columns]}
     * @returns {Array} Final columns array
     */
    buildColumnsWithConditions(baseColumns = [], conditions = {}, conditionalColumns = {}) {
        let columns = [...baseColumns];

        // Add conditional columns based on conditions
        Object.entries(conditionalColumns).forEach(([key, colsToAdd]) => {
            if (conditions[key] && Array.isArray(colsToAdd)) {
                columns.push(...colsToAdd);
            }
        });

        return columns;
    }

    /**
     * Build columns with conditional positioning
     * Allows inserting columns at specific positions and appending columns at the end
     * @param {Array} baseColumns - Base columns array
     * @param {Object} config - Configuration object:
     *   - insertAfterIndex: {number} - Insert conditional columns after this index
     *   - insertColumns: {Array} - Conditional columns to insert (if should insert)
     *   - shouldInsert: {boolean} - Whether to insert the columns
     *   - appendColumns: {Array} - Conditional columns to append at the end (if should append)
     *   - shouldAppend: {boolean} - Whether to append the columns
     * @returns {Array} Final columns array with conditionally positioned columns
     */
    buildColumnsWithPositioning(baseColumns = [], config = {}) {
        let columns = [...baseColumns];

        // Insert columns at specific position if condition is met
        if (config.shouldInsert && config.insertColumns && Array.isArray(config.insertColumns)) {
            const insertIndex = (config.insertAfterIndex ?? -1) + 1;
            columns.splice(insertIndex, 0, ...config.insertColumns);
        }

        // Append columns at the end if condition is met
        if (config.shouldAppend && config.appendColumns && Array.isArray(config.appendColumns)) {
            columns.push(...config.appendColumns);
        }

        return columns;
    }

    /**
     * Fetch totals from API and render totals row
     * Uses same filters as main table query
     */
    _fetchAndRenderTotals() {
        if (!this.totals || !this.totals.apiUrl) return;

        // Show loading row first
        this._renderTotalsLoadingRow();
        this.totalsLoading = true;

        // Build totals API URL with same filters
        const totalsUrl = this._buildTotalsUrl();

        fetch(totalsUrl, { credentials: 'same-origin' })
            .then(response => response.json())
            .then(result => {
                if (result.success && result.totals) {
                    this.totalsCached = result.totals;
                    this._renderTotalsRow(result.totals);
                } else {
                    console.error('Totals API error:', result.message || 'Unknown error');
                    this._removeTotalsRow();
                }
            })
            .catch(error => {
                console.error('Error fetching totals:', error);
                this._removeTotalsRow();
            })
            .finally(() => {
                this.totalsLoading = false;
            });
    }

    /**
     * Build totals API URL with same filters as main table
     */
    _buildTotalsUrl() {
        const params = this.getFilterValues();

        // Add sorting if available
        if (this.sortField && this.sortDirection) {
            params.sort_field = this.sortField;
            params.sort_direction = this.sortDirection;
        }

        // Same extra parameters the list request sends — the totals row has to be computed
        // over the same filter set as the rows above it, otherwise the two disagree.
        if (typeof this.config.extraParams === 'function') {
            Object.assign(params, this.config.extraParams());
        }

        const queryString = Object.entries(params)
            .filter(([_, v]) => v != null && v !== '')
            .map(([k, v]) => `${encodeURIComponent(k)}=${encodeURIComponent(v)}`)
            .join('&');

        const separator = this.totals.apiUrl.includes('?') ? '&' : '?';
        return `${this.totals.apiUrl}${separator}${queryString}`;
    }

    /**
     * Render loading row for totals
     */
    _renderTotalsLoadingRow() {
        const table = document.querySelector(`#${this.config.containerId} table`);
        if (!table) return;

        // Remove existing totals row
        this._removeTotalsRow();

        const tbody = table.querySelector('tbody');
        const cols = this.config.columns.length + (this._isCheckboxEnabled() ? 1 : 0);

        const loadingRow = document.createElement('tr');
        loadingRow.className = 'table-totals-row totals-loading';
        loadingRow.id = 'table-totals-row-loading';

        let html = `<td colspan="${cols}" style="text-align: center; padding: 15px;">
            <span style="color: #0d6efd; font-weight: 600;">
                <i class="fas fa-hourglass-half"></i> Loading totals...
            </span>
        </td>`;

        loadingRow.innerHTML = html;
        tbody.appendChild(loadingRow);
    }

    /**
     * Render actual totals row
     * @param {Object} totals - Totals data from API
     */
    _renderTotalsRow(totals) {
        const table = document.querySelector(`#${this.config.containerId} table`);
        if (!table) return;

        // Remove loading row and any existing totals row (guards against duplicate TOTAL rows
        // if two totals fetches resolve, e.g. from overlapping reloads).
        const loadingRow = document.getElementById('table-totals-row-loading');
        if (loadingRow) loadingRow.remove();
        const existingTotalsRow = document.getElementById('table-totals-row');
        if (existingTotalsRow) existingTotalsRow.remove();

        const tbody = table.querySelector('tbody');
        const columns = this.config.columns;
        const colMap = this.totals.columns || {};

        // Build totals row
        const totalsRow = document.createElement('tr');
        totalsRow.className = 'table-totals-row';
        totalsRow.id = 'table-totals-row';

        let cells = '';

        // Add checkbox column cell if enabled
        if (this._isCheckboxEnabled()) {
            cells += '<td class="totals-label"></td>';
        }

        // Add cells for each column
        columns.forEach((col, colIndex) => {
            const fieldName = col.field || col.name;
            const totalsKey = colMap[fieldName];

            if (colIndex === 0) {
                // First column gets the label
                cells += `<td class="totals-label">${this.totals.label || 'TOTAL'}</td>`;
            } else if (totalsKey && totals[totalsKey] !== undefined) {
                // Show totals value if configured
                const value = this._formatTotalValue(totals[totalsKey]);
                cells += `<td class="totals-cell">${value}</td>`;
            } else {
                // Empty cell
                cells += '<td></td>';
            }
        });

        totalsRow.innerHTML = cells;
        tbody.appendChild(totalsRow);
    }

    /**
     * Format total value for display
     * @param {*} value - Value to format
     * @returns {string} Formatted value
     */
    _formatTotalValue(value) {
        if (value === null || value === undefined) return '-';

        // Check if it's a number
        if (typeof value === 'number') {
            // If it has decimal places, show 2 decimals, otherwise show as integer
            return value % 1 !== 0 ? value.toFixed(2) : parseInt(value).toLocaleString();
        }

        return String(value);
    }

    /**
     * Remove totals row from table
     */
    _removeTotalsRow() {
        const totalsRow = document.getElementById('table-totals-row');
        const loadingRow = document.getElementById('table-totals-row-loading');

        if (totalsRow) totalsRow.remove();
        if (loadingRow) loadingRow.remove();
    }

    /**
     * Refresh totals for current filters
     * Called when filters change
     */
    refreshTotals() {
        if (this.totals && this.totals.enabled) {
            this._fetchAndRenderTotals();
        }
    }
}
window.TableHelper = TableHelper;
