(function () {
    const STATUS_LABELS = {
        'pending': 'En attente',
        'confirmed': 'Confirmée',
        'shipped': 'Livraison en cours',
        'delivered': 'Livrée',
        'cancelled': 'Annulée',
    };

    const STATUS_BADGE_CLASSES = {
        'pending': 'badge badge-status-pending',
        'confirmed': 'badge badge-status-confirmed',
        'shipped': 'badge badge-status-shipped',
        'delivered': 'badge badge-status-delivered',
        'cancelled': 'badge badge-status-cancelled',
    };

    const ALLOWED_CARRIERS = ['colissimo', 'ups', 'fedex', 'dhl'];

    function statusLabel(status) {
        return STATUS_LABELS[status] || status;
    }

    function statusBadgeClass(status) {
        return STATUS_BADGE_CLASSES[status] || 'badge';
    }

    function createStatusModal(options) {
        const { apiBaseUrl, onStatusUpdated } = options;
        let currentOrderId = null;
        let currentStatus = null;

        const overlay = document.createElement('div');
        overlay.className = 'modal-overlay hidden';

        const modal = document.createElement('div');
        modal.className = 'modal-card';

        const title = document.createElement('h3');
        title.textContent = 'Changer le statut';
        modal.appendChild(title);

        const form = document.createElement('form');
        form.className = 'modal-form';

        const statusGroup = document.createElement('div');
        statusGroup.className = 'form-group';
        const statusLabelEl = document.createElement('label');
        statusLabelEl.textContent = 'Nouveau statut';
        const statusSelect = document.createElement('select');
        for (const key of Object.keys(STATUS_LABELS)) {
            const opt = document.createElement('option');
            opt.value = key;
            opt.textContent = STATUS_LABELS[key];
            statusSelect.appendChild(opt);
        }
        statusGroup.appendChild(statusLabelEl);
        statusGroup.appendChild(statusSelect);
        form.appendChild(statusGroup);

        const trackingQuestion = document.createElement('div');
        trackingQuestion.className = 'form-group';
        const trackingLabel = document.createElement('label');
        trackingLabel.textContent = 'Y a-t-il un numéro de suivi ?';
        const trackingChoices = document.createElement('div');
        trackingChoices.className = 'radio-group';
        const radioYes = document.createElement('label');
        const radioYesInput = document.createElement('input');
        radioYesInput.type = 'radio';
        radioYesInput.name = 'has_tracking';
        radioYesInput.value = 'yes';
        radioYesInput.checked = true;
        radioYes.appendChild(radioYesInput);
        radioYes.appendChild(document.createTextNode(' Oui'));

        const radioNo = document.createElement('label');
        const radioNoInput = document.createElement('input');
        radioNoInput.type = 'radio';
        radioNoInput.name = 'has_tracking';
        radioNoInput.value = 'no';
        radioNo.appendChild(radioNoInput);
        radioNo.appendChild(document.createTextNode(' Non'));

        trackingChoices.appendChild(radioYes);
        trackingChoices.appendChild(radioNo);
        trackingQuestion.appendChild(trackingLabel);
        trackingQuestion.appendChild(trackingChoices);
        form.appendChild(trackingQuestion);

        const trackingFields = document.createElement('div');
        trackingFields.className = 'tracking-fields';

        const carrierGroup = document.createElement('div');
        carrierGroup.className = 'form-group';
        const carrierLabel = document.createElement('label');
        carrierLabel.textContent = 'Transporteur';
        const carrierSelect = document.createElement('select');
        const defaultCarrier = document.createElement('option');
        defaultCarrier.value = '';
        defaultCarrier.textContent = 'Sélectionner';
        carrierSelect.appendChild(defaultCarrier);
        for (const carrier of ALLOWED_CARRIERS) {
            const opt = document.createElement('option');
            opt.value = carrier;
            opt.textContent = carrier.toUpperCase();
            carrierSelect.appendChild(opt);
        }
        carrierGroup.appendChild(carrierLabel);
        carrierGroup.appendChild(carrierSelect);

        const trackingNumberGroup = document.createElement('div');
        trackingNumberGroup.className = 'form-group';
        const trackingNumberLabel = document.createElement('label');
        trackingNumberLabel.textContent = 'Numéro de suivi';
        const trackingNumberInput = document.createElement('input');
        trackingNumberInput.type = 'text';
        trackingNumberInput.placeholder = 'Ex: 1Z999AA10123456784';
        trackingNumberGroup.appendChild(trackingNumberLabel);
        trackingNumberGroup.appendChild(trackingNumberInput);

        trackingFields.appendChild(carrierGroup);
        trackingFields.appendChild(trackingNumberGroup);
        form.appendChild(trackingFields);

        const errorBox = document.createElement('div');
        errorBox.className = 'modal-error hidden';
        form.appendChild(errorBox);

        const buttons = document.createElement('div');
        buttons.className = 'modal-actions';
        const cancelBtn = document.createElement('button');
        cancelBtn.type = 'button';
        cancelBtn.className = 'btn-secondary';
        cancelBtn.textContent = 'Annuler';
        const submitBtn = document.createElement('button');
        submitBtn.type = 'submit';
        submitBtn.className = 'btn-primary';
        submitBtn.textContent = 'Enregistrer';
        buttons.appendChild(cancelBtn);
        buttons.appendChild(submitBtn);
        form.appendChild(buttons);

        modal.appendChild(form);
        overlay.appendChild(modal);
        document.body.appendChild(overlay);

        function toggleTrackingFields() {
            const isShipped = statusSelect.value === 'shipped';
            const hasTracking = radioYesInput.checked && isShipped;
            trackingQuestion.style.display = isShipped ? 'block' : 'none';
            trackingFields.style.display = hasTracking ? 'block' : 'none';
            if (!isShipped) {
                radioYesInput.checked = false;
                radioNoInput.checked = true;
            }
        }

        function resetForm(status) {
            statusSelect.value = status || 'pending';
            radioYesInput.checked = true;
            radioNoInput.checked = false;
            carrierSelect.value = '';
            trackingNumberInput.value = '';
            errorBox.textContent = '';
            errorBox.classList.add('hidden');
            trackingQuestion.style.display = statusSelect.value === 'shipped' ? 'block' : 'none';
            toggleTrackingFields();
        }

        function showError(message) {
            errorBox.textContent = message;
            errorBox.classList.remove('hidden');
        }

        async function submitForm(event) {
            event.preventDefault();
            if (!currentOrderId) {
                showError('Commande invalide.');
                return;
            }
            const selectedStatus = statusSelect.value;
            const hasTracking = radioYesInput.checked && selectedStatus === 'shipped';
            const trackingNumber = trackingNumberInput.value.trim();
            const carrier = carrierSelect.value;

            if (selectedStatus === 'shipped' && hasTracking) {
                if (!trackingNumber) {
                    showError('Merci de renseigner le numéro de suivi.');
                    return;
                }
                if (!ALLOWED_CARRIERS.includes(carrier)) {
                    showError('Merci de sélectionner un transporteur.');
                    return;
                }
            }

            submitBtn.disabled = true;
            submitBtn.textContent = 'Enregistrement…';

            try {
                const res = await fetch(apiBaseUrl + '/orders/update_status.php', {
                    method: 'POST',
                    credentials: 'include',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        order_id: currentOrderId,
                        status: selectedStatus,
                        has_tracking: hasTracking,
                        tracking_number: hasTracking ? trackingNumber : '',
                        tracking_carrier: hasTracking ? carrier : '',
                    })
                });
                const data = await res.json();
                if (!res.ok || !data.success) {
                    const errorMsg = data.error || 'Erreur lors de la mise à jour du statut.';
                    showError(errorMsg);
                    return;
                }
                closeModal();
                if (typeof onStatusUpdated === 'function') {
                    onStatusUpdated(data.data);
                }
            } catch (error) {
                console.error(error);
                showError('Erreur de communication avec le serveur.');
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Enregistrer';
            }
        }

        function closeModal() {
            overlay.classList.add('hidden');
        }

        function openModal(orderId, status) {
            currentOrderId = orderId;
            currentStatus = status || null;
            resetForm(currentStatus);
            overlay.classList.remove('hidden');
        }

        cancelBtn.addEventListener('click', closeModal);
        overlay.addEventListener('click', (event) => {
            if (event.target === overlay) {
                closeModal();
            }
        });
        radioYesInput.addEventListener('change', toggleTrackingFields);
        radioNoInput.addEventListener('change', toggleTrackingFields);
        statusSelect.addEventListener('change', toggleTrackingFields);
        form.addEventListener('submit', submitForm);

        toggleTrackingFields();
        trackingQuestion.style.display = 'none';

        return {
            openModal,
            closeModal,
        };
    }

    function injectStyles() {
        if (document.getElementById('admin-orders-modal-styles')) {
            return;
        }
        const style = document.createElement('style');
        style.id = 'admin-orders-modal-styles';
        style.textContent = `
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.45);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }
        .modal-overlay.hidden {
            display: none;
        }
        .modal-card {
            background: #fff;
            border-radius: 10px;
            padding: 16px 18px;
            width: min(420px, 90vw);
            box-shadow: 0 20px 60px rgba(0,0,0,0.18);
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }
        .modal-card h3 {
            margin: 0 0 10px 0;
            font-size: 16px;
        }
        .modal-form {
            display: flex;
            flex-direction: column;
            gap: 12px;
            font-size: 13px;
        }
        .modal-form label {
            font-weight: 600;
            color: #374151;
            display: block;
            margin-bottom: 4px;
        }
        .modal-form select,
        .modal-form input[type="text"] {
            width: 100%;
            padding: 8px 10px;
            border-radius: 6px;
            border: 1px solid #d1d5db;
            font-size: 13px;
        }
        .radio-group {
            display: flex;
            gap: 16px;
            font-weight: 500;
            color: #374151;
        }
        .radio-group input {
            margin-right: 4px;
        }
        .tracking-fields {
            border: 1px solid #e5e7eb;
            padding: 10px;
            border-radius: 8px;
            background: #f9fafb;
        }
        .modal-error {
            background: #fef2f2;
            color: #b91c1c;
            border: 1px solid #fecaca;
            padding: 8px 10px;
            border-radius: 6px;
            font-size: 12px;
        }
        .modal-error.hidden { display: none; }
        .modal-actions {
            display: flex;
            gap: 8px;
            justify-content: flex-end;
        }
        .btn-primary,
        .btn-secondary {
            padding: 8px 12px;
            border-radius: 4px;
            border: none;
            font-size: 13px;
            cursor: pointer;
        }
        .btn-primary { background: #111827; color: #fff; }
        .btn-secondary { background: #f3f4f6; color: #111827; border: 1px solid #d1d5db; }
        .btn-primary[disabled] { opacity: .6; cursor: default; }
        `;
        document.head.appendChild(style);
    }

    injectStyles();

    window.AdminOrders = {
        statusLabel,
        statusBadgeClass,
        createStatusModal,
    };
})();