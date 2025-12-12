// ========================================
// CIBERCAFÉ PRO - JAVASCRIPT COMPLETO 2025
// Funcionalidades interactivas avanzadas
// ========================================

document.addEventListener('DOMContentLoaded', function() {
    // Inicializar todas las funcionalidades
    initForms();
    initTables();
    initAnimations();
    initKeyboardShortcuts();
    initAutoRefresh();
});

// ===== FORMULARIOS DINÁMICOS =====
function initForms() {
    // Calculadoras automáticas
    document.querySelectorAll('input[type="number"]').forEach(input => {
        input.addEventListener('input', function() {
            handleNumberInput(this);
        });
    });

    // Toggle password visibility
    document.querySelectorAll('input[type="password"]').forEach(input => {
        const toggle = document.createElement('i');
        toggle.className = 'fas fa-eye toggle-password';
        toggle.style.cssText = 'position:absolute;right:15px;top:50%;transform:translateY(-50%);cursor:pointer;color:#666;pointer-events:all;z-index:10;';
        input.parentNode.style.position = 'relative';
        input.parentNode.appendChild(toggle);
        
        toggle.addEventListener('click', function() {
            const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
            input.setAttribute('type', type);
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });
    });
}

function handleNumberInput(input) {
    // Impresiones - calcular total
    if (input.name === 'paginas_bn' || input.name === 'paginas_color') {
        calcularImpresionTotal();
    }
}

// ===== IMPRESIONES - CALCULADORA =====
function calcularImpresionTotal() {
    const bn = parseInt(document.querySelector('input[name="paginas_bn"]?.value') || 0);
    const color = parseInt(document.querySelector('input[name="paginas_color"]?.valu') || 0);
    const total = (bn * 2) + (color * 5);
    
    const totalElement = document.getElementById('totalImpresion');
    if (totalElement) {
        totalElement.textContent = total.toFixed(2);
        totalElement.style.color = total > 0 ? '#28a745' : '#666';
        totalElement.parentElement.classList.toggle('total-active', total > 0);
    }
}

// ===== TABLAS - FUNCIONALIDADES =====
function initTables() {
    // Responsive tables
    document.querySelectorAll('.data-table').forEach(table => {
        table.addEventListener('click', function(e) {
            if (e.target.closest('.btn-icon')) {
                e.stopPropagation();
            }
        });
    });

    // Sort tables
    document.querySelectorAll('.data-table th').forEach((th, index) => {
        th.style.cursor = 'pointer';
        th.addEventListener('click', function() {
            sortTable(this.closest('table'), index);
        });
    });
}

function sortTable(table, columnIndex) {
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    
    rows.sort((a, b) => {
        const aText = a.cells[columnIndex].textContent.trim();
        const bText = b.cells[columnIndex].textContent.trim();
        
        // Detectar números
        const aNum = parseFloat(aText.replace(/[^\d.]/g, ''));
        const bNum = parseFloat(bText.replace(/[^\d.]/g, ''));
        
        if (!isNaN(aNum) && !isNaN(bNum)) {
            return bNum - aNum;
        }
        return aText.localeCompare(bText);
    });
    
    rows.forEach(row => tbody.appendChild(row));
}

// ===== ANIMACIONES =====
function initAnimations() {
    // Animate stats on scroll
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-in');
            }
        });
    });
    
    document.querySelectorAll('.stat-card, .action-card').forEach(card => {
        observer.observe(card);
    });

    // Pulse effect for active sessions
    document.querySelectorAll('.status-dot.green').forEach(dot => {
        dot.style.animation = 'pulse 2s infinite';
    });
}

// ===== MODALES Y MÁQUINAS =====
function showMaquinasDisponibles() {
    const modal = document.getElementById('modalMaquinas');
    if (modal) {
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
}

function hideModal() {
    const modal = document.getElementById('modalMaquinas');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
}

// Toggle forms genérico
function toggleForm(formId) {
    const form = document.getElementById(formId);
    const title = document.getElementById('formTitle');
    
    if (form) {
        if (form.style.display === 'block' || form.style.display === '') {
            form.style.display = 'none';
            form.reset();
            if (document.getElementById('userAction')) document.getElementById('userAction').value = 'add_usuario';
            if (document.getElementById('maquinaAction')) document.getElementById('maquinaAction').value = 'add_maquina';
            if (title) title.textContent = form.id.includes('user') ? 'Nuevo Usuario' : 'Nueva Máquina';
        } else {
            form.style.display = 'block';
            const firstInput = form.querySelector('input, select');
            if (firstInput) firstInput.focus();
        }
    }
}

// ===== CRUD FUNCTIONS =====
function editUser(id, nombre, email, telefono, rol) {
    toggleForm('userForm');
    document.getElementById('userId').value = id;
    document.getElementById('userAction').value = 'edit_usuario';
    document.querySelector('input[name="nombre"]').value = nombre;
    document.querySelector('input[name="email"]').value = email;
    document.querySelector('input[name="telefono"]').value = telefono || '';
    document.querySelector('select[name="rol"]').value = rol;
    document.getElementById('userPassword').required = false;
    document.getElementById('userPassword').placeholder = 'Dejar vacío para no cambiar';
}

function editMaquina(id, numero, ip, ram, estado) {
    toggleForm('maquinaForm');
    document.getElementById('maquinaId').value = id;
    document.getElementById('maquinaAction').value = 'edit_maquina';
    document.getElementById('formTitle').textContent = `Editando: ${numero}`;
    document.querySelector('input[name="numero"]').value = numero;
    document.querySelector('input[name="ip_address"]').value = ip || '';
    document.querySelector('input[name="ram_gb"]').value = ram;
    document.querySelector('select[name="estado"]').value = estado;
}

function deleteUser(id, nombre) {
    if (confirm(`🗑️ ¿Eliminar usuario "${nombre}"?\n\nEsta acción es permanente.`)) {
        submitDelete('delete_usuario', id);
    }
}

function deleteMaquina(id, numero) {
    if (confirm(`🗑️ ¿Eliminar máquina "${numero}"?\n\nEsta acción es permanente.`)) {
        submitDelete('delete_maquina', id);
    }
}

function iniciarSesion(maquinaId, numero) {
    if (confirm(`▶️ Iniciar sesión en "${numero}"?\n\nLa máquina quedará OCUPADA.`)) {
        submitAction('iniciar_sesion', { maquina_id: maquinaId });
    }
}

function finalizarSesion(sesionId) {
    if (confirm(`⏹️ Finalizar sesión #${sesionId}?\n\nSe calculará el tiempo y liberará la máquina.`)) {
        submitAction('finalizar_sesion', { sesion_id: sesionId });
    }
}

// ===== AJAX SUBMIT =====
function submitAction(action, data = {}) {
    const formData = new FormData();
    formData.append('action', action);
    
    Object.entries(data).forEach(([key, value]) => {
        formData.append(key, value);
    });
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    }).then(response => {
        window.location.reload();
    }).catch(error => {
        console.error('Error:', error);
        alert('Error en la operación. Recargando...');
        window.location.reload();
    });
}

function submitDelete(action, id) {
    const formData = new FormData();
    formData.append('action', action);
    formData.append('id', id);
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    }).then(() => {
        window.location.reload();
    });
}

// ===== ATAJOS DE TECLADO =====
function initKeyboardShortcuts() {
    document.addEventListener('keydown', function(e) {
        // ESC - cerrar modales
        if (e.key === 'Escape') {
            hideModal();
            document.querySelectorAll('.form-modal[style*="block"]').forEach(form => {
                form.style.display = 'none';
            });
        }
        
        // Ctrl/Cmd + Enter - submit forms
        if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
            document.querySelectorAll('form').forEach(form => {
                if (form.style.display !== 'none') {
                    form.submit();
                }
            });
        }
    });
}

// ===== AUTO-REFRESH =====
function initAutoRefresh() {
    // Detectar páginas que necesitan refresh
    const needsRefresh = document.querySelector('.data-table') || 
                        document.querySelector('#sesionesActivas') ||
                        document.body.id === 'dashboard';
    
    if (needsRefresh) {
        setTimeout(() => window.location.reload(), 30000); // 30 segundos
    }
}

// ===== UTILIDADES =====
// Cerrar modales al click fuera
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal-overlay')) {
        hideModal();
    }
});

// Prevenir zoom en inputs móviles
document.querySelectorAll('input, select').forEach(input => {
    input.addEventListener('focus', function() {
        this.setAttribute('readonly', 'readonly');
        setTimeout(() => this.removeAttribute('readonly'), 100);
    });
});

// Smooth scroll para anclas
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
        e.preventDefault();
        document.querySelector(this.getAttribute('href')).scrollIntoView({
            behavior: 'smooth'
        });
    });
});

// ===== SESIONES - TIMER LIVE =====
function initSessionTimers() {
    const sesiones = document.querySelectorAll('tr[data-sesion-id]');
    sesiones.forEach(row => {
        const sesionId = row.dataset.sesionId;
        const duracionCell = document.getElementById(`duracion-${sesionId}`);
        
        if (duracionCell) {
            updateDuracionLive(sesionId, duracionCell);
        }
    });
}

function updateDuracionLive(sesionId, cell) {
    // Simular timer live (datos reales vienen del servidor)
    cell.textContent = '🔴 Live';
    cell.classList.add('live-timer');
}

// Exportar funciones globales
window.toggleForm = toggleForm;
window.editUser = editUser;
window.editMaquina = editMaquina;
window.deleteUser = deleteUser;
window.deleteMaquina = deleteMaquina;
window.iniciarSesion = iniciarSesion;
window.finalizarSesion = finalizarSesion;
window.showMaquinasDisponibles = showMaquinasDisponibles;
window.hideModal = hideModal;
window.submitAction = submitAction;
