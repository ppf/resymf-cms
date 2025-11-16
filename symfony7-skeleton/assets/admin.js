/**
 * ReSymf CMS - Admin JavaScript
 * Enhanced admin area functionality
 */

// Import styles
import './styles/admin.css';

// Slug Auto-Generation
class SlugGenerator {
    constructor() {
        this.init();
    }

    init() {
        // Find title and slug fields
        const titleFields = document.querySelectorAll('input[name*="[title]"], input[id*="_title"]');
        const slugFields = document.querySelectorAll('input[name*="[slug]"], input[id*="_slug"]');

        titleFields.forEach((titleField, index) => {
            const slugField = slugFields[index];
            if (slugField) {
                titleField.addEventListener('input', (e) => {
                    // Only auto-generate if slug is empty or was auto-generated
                    if (!slugField.dataset.manual) {
                        slugField.value = this.generateSlug(e.target.value);
                    }
                });

                // Mark slug as manual if user types in it
                slugField.addEventListener('input', () => {
                    slugField.dataset.manual = 'true';
                });
            }
        });
    }

    generateSlug(text) {
        return text
            .toLowerCase()
            .replace(/[^\w\s-]/g, '') // Remove special characters
            .replace(/\s+/g, '-')      // Replace spaces with hyphens
            .replace(/--+/g, '-')      // Replace multiple hyphens with single
            .replace(/^-+|-+$/g, '');  // Trim hyphens from start/end
    }
}

// Delete Confirmation
class DeleteConfirmation {
    constructor() {
        this.init();
    }

    init() {
        document.addEventListener('submit', (e) => {
            const form = e.target;
            if (form.matches('[data-confirm], form[action*="delete"]')) {
                const message = form.dataset.confirm || 'Are you sure you want to delete this item? This action cannot be undone.';
                if (!confirm(message)) {
                    e.preventDefault();
                }
            }
        });

        // Also handle delete links
        document.addEventListener('click', (e) => {
            if (e.target.matches('[data-confirm]')) {
                const message = e.target.dataset.confirm;
                if (!confirm(message)) {
                    e.preventDefault();
                }
            }
        });
    }
}

// Form Validation Enhancement
class FormValidator {
    constructor() {
        this.init();
    }

    init() {
        const forms = document.querySelectorAll('form[novalidate]');
        forms.forEach(form => {
            form.addEventListener('submit', (e) => {
                if (!form.checkValidity()) {
                    e.preventDefault();
                    e.stopPropagation();
                    this.showValidationErrors(form);
                }
                form.classList.add('was-validated');
            });
        });
    }

    showValidationErrors(form) {
        const invalidFields = form.querySelectorAll(':invalid');
        if (invalidFields.length > 0) {
            invalidFields[0].focus();
        }
    }
}

// Table Row Click (go to edit page)
class TableRowClick {
    constructor() {
        this.init();
    }

    init() {
        document.querySelectorAll('tr[data-href]').forEach(row => {
            row.style.cursor = 'pointer';
            row.addEventListener('click', (e) => {
                // Don't navigate if clicking on a button or link
                if (!e.target.closest('button, a, input, select, textarea')) {
                    window.location.href = row.dataset.href;
                }
            });
        });
    }
}

// Auto-hide Flash Messages
class FlashMessages {
    constructor() {
        this.init();
    }

    init() {
        const alerts = document.querySelectorAll('.alert:not(.alert-danger)');
        alerts.forEach(alert => {
            setTimeout(() => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }, 5000); // Auto-hide after 5 seconds
        });
    }
}

// Search Filter (Client-side)
class TableSearch {
    constructor() {
        this.init();
    }

    init() {
        const searchInputs = document.querySelectorAll('[data-table-search]');
        searchInputs.forEach(input => {
            const tableId = input.dataset.tableSearch;
            const table = document.getElementById(tableId);
            if (table) {
                input.addEventListener('input', (e) => {
                    this.filterTable(table, e.target.value);
                });
            }
        });
    }

    filterTable(table, searchTerm) {
        const rows = table.querySelectorAll('tbody tr');
        const term = searchTerm.toLowerCase();

        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(term) ? '' : 'none';
        });
    }
}

// Sortable Tables
class TableSort {
    constructor() {
        this.init();
    }

    init() {
        document.querySelectorAll('th[data-sort]').forEach(header => {
            header.style.cursor = 'pointer';
            header.innerHTML += ' <span class="sort-icon">⇅</span>';

            header.addEventListener('click', () => {
                const table = header.closest('table');
                const tbody = table.querySelector('tbody');
                const rows = Array.from(tbody.querySelectorAll('tr'));
                const index = Array.from(header.parentElement.children).indexOf(header);
                const isAscending = header.classList.contains('asc');

                // Sort rows
                rows.sort((a, b) => {
                    const aValue = a.children[index].textContent.trim();
                    const bValue = b.children[index].textContent.trim();

                    if (isAscending) {
                        return bValue.localeCompare(aValue);
                    } else {
                        return aValue.localeCompare(bValue);
                    }
                });

                // Update table
                rows.forEach(row => tbody.appendChild(row));

                // Update sort indicator
                header.closest('tr').querySelectorAll('th').forEach(th => {
                    th.classList.remove('asc', 'desc');
                });
                header.classList.toggle('asc', !isAscending);
                header.classList.toggle('desc', isAscending);
            });
        });
    }
}

// Character Counter for Textareas
class CharacterCounter {
    constructor() {
        this.init();
    }

    init() {
        document.querySelectorAll('textarea[maxlength]').forEach(textarea => {
            const maxLength = textarea.getAttribute('maxlength');
            const counter = document.createElement('div');
            counter.className = 'character-counter text-muted small mt-1';
            textarea.parentElement.appendChild(counter);

            const updateCounter = () => {
                const remaining = maxLength - textarea.value.length;
                counter.textContent = `${remaining} characters remaining`;
                counter.style.color = remaining < 50 ? '#dc3545' : '#6c757d';
            };

            textarea.addEventListener('input', updateCounter);
            updateCounter();
        });
    }
}

// Sidebar Toggle for Mobile
class SidebarToggle {
    constructor() {
        this.init();
    }

    init() {
        // Add toggle button for mobile
        const sidebar = document.querySelector('.admin-sidebar');
        if (sidebar && window.innerWidth <= 768) {
            const toggleBtn = document.createElement('button');
            toggleBtn.className = 'btn btn-primary sidebar-toggle';
            toggleBtn.innerHTML = '☰';
            toggleBtn.style.cssText = 'position: fixed; top: 70px; left: 10px; z-index: 1000;';

            toggleBtn.addEventListener('click', () => {
                sidebar.classList.toggle('show');
            });

            document.body.appendChild(toggleBtn);
        }
    }
}

// Loading Spinner
class LoadingSpinner {
    static show() {
        const spinner = document.createElement('div');
        spinner.className = 'spinner-overlay';
        spinner.innerHTML = '<div class="spinner-border text-light" role="status"><span class="visually-hidden">Loading...</span></div>';
        spinner.id = 'loading-spinner';
        document.body.appendChild(spinner);
    }

    static hide() {
        const spinner = document.getElementById('loading-spinner');
        if (spinner) {
            spinner.remove();
        }
    }
}

// Form Auto-save (for drafts)
class FormAutoSave {
    constructor() {
        this.init();
    }

    init() {
        const forms = document.querySelectorAll('form[data-autosave]');
        forms.forEach(form => {
            let timeout;
            const inputs = form.querySelectorAll('input, textarea, select');

            inputs.forEach(input => {
                input.addEventListener('input', () => {
                    clearTimeout(timeout);
                    timeout = setTimeout(() => {
                        this.saveDraft(form);
                    }, 2000);
                });
            });

            // Load saved draft on page load
            this.loadDraft(form);
        });
    }

    saveDraft(form) {
        const formData = new FormData(form);
        const data = Object.fromEntries(formData);
        const key = `draft_${form.id || 'form'}`;
        localStorage.setItem(key, JSON.stringify(data));

        // Show save indicator
        this.showSaveIndicator();
    }

    loadDraft(form) {
        const key = `draft_${form.id || 'form'}`;
        const draft = localStorage.getItem(key);
        if (draft) {
            const data = JSON.parse(draft);
            Object.keys(data).forEach(name => {
                const input = form.querySelector(`[name="${name}"]`);
                if (input && !input.value) {
                    input.value = data[name];
                }
            });
        }
    }

    showSaveIndicator() {
        let indicator = document.getElementById('autosave-indicator');
        if (!indicator) {
            indicator = document.createElement('div');
            indicator.id = 'autosave-indicator';
            indicator.className = 'badge bg-success position-fixed';
            indicator.style.cssText = 'bottom: 20px; right: 20px; z-index: 1000;';
            document.body.appendChild(indicator);
        }
        indicator.textContent = '✓ Draft saved';
        indicator.style.display = 'block';

        setTimeout(() => {
            indicator.style.display = 'none';
        }, 2000);
    }
}

// Initialize all features when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    new SlugGenerator();
    new DeleteConfirmation();
    new FormValidator();
    new TableRowClick();
    new FlashMessages();
    new TableSearch();
    new TableSort();
    new CharacterCounter();
    new SidebarToggle();
    new FormAutoSave();

    console.log('✓ ReSymf CMS Admin features initialized');
});

// Export for use in other modules
export {
    SlugGenerator,
    DeleteConfirmation,
    FormValidator,
    LoadingSpinner,
    TableSearch,
    TableSort
};
