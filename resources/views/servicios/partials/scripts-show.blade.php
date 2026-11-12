<script>
    // Modal de Pago Rápido de Cuota
    function abrirModalPagoRapido() {
        const modal = document.getElementById('modal-pago-cuota');
        const select = document.getElementById('modal-select-cuota');
        const form = document.getElementById('form-pago-cuota');
        if (modal && select && form) {
            const firstPending = Array.from(select.options).find(opt => !opt.disabled);
            if (firstPending) {
                select.value = firstPending.value;
                form.action = firstPending.dataset.url;
            }
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }
    }

    function pagarCuotaDirecto(cuotaId, numeroCuota, monto, actionUrl) {
        const modal = document.getElementById('modal-pago-cuota');
        const select = document.getElementById('modal-select-cuota');
        const form = document.getElementById('form-pago-cuota');
        if (modal && select && form) {
            select.value = cuotaId;
            form.action = actionUrl;
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }
    }

    function cerrarModalPagoRapido() {
        const modal = document.getElementById('modal-pago-cuota');
        if (modal) {
            modal.classList.add('hidden');
            document.body.style.overflow = '';
        }
    }

    function actualizarMontoModal(select) {
        const option = select.options[select.selectedIndex];
        const form = document.getElementById('form-pago-cuota');
        if (option && form && option.dataset.url) {
            form.action = option.dataset.url;
        }
    }

    document.getElementById('modal-pago-cuota')?.addEventListener('click', function(e) {
        if (e.target === this) cerrarModalPagoRapido();
    });

    // Smooth scroll y resaltado de la sección destino
    function scrollHacia(idElemento) {
        const elemento = document.getElementById(idElemento);
        if (elemento) {
            elemento.scrollIntoView({ behavior: 'smooth', block: 'center' });
            elemento.classList.add('ring-4', 'ring-indigo-300', 'transition-all');
            setTimeout(() => {
                elemento.classList.remove('ring-4', 'ring-indigo-300');
            }, 1800);
        }
    }

    // Modal de Urnas
    function abrirModalUrnas() {
        const modal = document.getElementById('modal-catalogo-urnas');
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        setTimeout(() => {
            const input = document.getElementById('filtro-urnas-input');
            if (input) input.focus();
        }, 100);
    }

    function cerrarModalUrnas() {
        const modal = document.getElementById('modal-catalogo-urnas');
        modal.classList.add('hidden');
        document.body.style.overflow = '';
    }

    // Cerrar modal al pulsar Escape o fondo
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') cerrarModalUrnas();
    });
    document.getElementById('modal-catalogo-urnas').addEventListener('click', function(e) {
        if (e.target === this) cerrarModalUrnas();
    });

    // Filtro de Urnas en vivo en el modal
    let categoriaActiva = 'todos';
    function filtrarCategoriaUrna(categoria) {
        categoriaActiva = categoria.toLowerCase();
        document.querySelectorAll('.btn-categoria-urna').forEach(btn => {
            if (btn.textContent.trim().toLowerCase() === categoriaActiva) {
                btn.className = 'btn-categoria-urna px-2.5 py-1 rounded-lg text-[11px] font-bold bg-indigo-900 text-white transition';
            } else {
                btn.className = 'btn-categoria-urna px-2.5 py-1 rounded-lg text-[11px] font-semibold bg-white border border-slate-200 text-slate-700 hover:bg-slate-100 transition';
            }
        });
        filtrarUrnasEnModal();
    }

    function filtrarUrnasEnModal() {
        const query = (document.getElementById('filtro-urnas-input').value || '').toLowerCase().trim();
        const items = document.querySelectorAll('.card-urna-item');
        let visibles = 0;

        items.forEach(card => {
            const modelo = card.getAttribute('data-modelo') || '';
            const color = card.getAttribute('data-color') || '';
            const categoria = card.getAttribute('data-categoria') || '';

            const coincideQuery = !query || modelo.includes(query) || color.includes(query) || categoria.includes(query);
            const coincideCat = categoriaActiva === 'todos' || categoria === categoriaActiva;

            if (coincideQuery && coincideCat) {
                card.classList.remove('hidden');
                visibles++;
            } else {
                card.classList.add('hidden');
            }
        });

        const noMsg = document.getElementById('no-urnas-msg');
        if (visibles === 0) {
            noMsg.classList.remove('hidden');
        } else {
            noMsg.classList.add('hidden');
        }
    }
</script>
