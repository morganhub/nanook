document.addEventListener('DOMContentLoaded', () => {

    
    const cartDrawer = document.getElementById('cartDrawer');
    const cartOverlay = document.getElementById('cartOverlay');
    const cartTrigger = document.getElementById('cartTrigger');
    const cartClose = document.getElementById('cartClose');
    const cartCountEl = document.getElementById('cartCount');
    const cartBody = document.getElementById('cartBody');
    const cartTotal = document.getElementById('cartTotal');

    
    function openCart() {
        if (cartDrawer && cartOverlay) {
            cartDrawer.classList.add('is-open');
            cartOverlay.classList.add('is-open');
        }
    }

    function closeCart() {
        if (cartDrawer && cartOverlay) {
            cartDrawer.classList.remove('is-open');
            cartOverlay.classList.remove('is-open');
        }
    }

    if (cartTrigger) cartTrigger.addEventListener('click', openCart);
    if (cartClose) cartClose.addEventListener('click', closeCart);
    if (cartOverlay) cartOverlay.addEventListener('click', closeCart);

    
    async function updateCart(action, payload = {}) {
        try {
            
            const res = await fetch('/api/cart.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action, ...payload })
            });
            const data = await res.json();

            if (data.success) {
                renderCart(data.cart); 
                if (action === 'add') openCart(); 
            } else {
                console.error("Erreur serveur:", data.message);
            }
        } catch (e) {
            console.error("Erreur réseau panier:", e);
        }
    }

    
    function renderCart(cart) {
        
        if (cartCountEl) cartCountEl.innerText = cart.count;

        
        if (cartBody) {
            if (cart.items.length === 0) {
                cartBody.innerHTML = '<p style="text-align:center; color:#888; margin-top:50px;">Votre panier est vide.</p>';
            } else {
                cartBody.innerHTML = cart.items.map(item => {
                    
                    const imgSrc = item.image
                        ? '/storage/product_images/' + item.image
                        : '/assets/img/placeholder.jpg';

                    return `
            <div style="display:flex; gap:10px; margin-bottom:15px; border-bottom:1px solid #f0f0f0; padding-bottom:10px;">
                <div style="width:60px; height:80px; background:#f9f9f9; overflow:hidden; flex-shrink:0;">
                   <img src="${imgSrc}" style="width:100%; height:100%; object-fit:cover;" alt="${item.name}">
                </div>
                <div style="flex:1;">
                    <div style="font-weight:700; font-size:0.9rem; margin-bottom:2px;">${item.name}</div>
                    ${item.variant_name ? `<div style="font-size:0.8rem; color:#888;">${item.variant_name}</div>` : ''}
                    <div style="font-size:0.8rem; color:#888; margin-top:4px;">Qté: ${item.quantity}</div>
                    <div style="margin-top:4px; font-weight:600;">${parseFloat(item.line_total).toFixed(2)} €</div>
                </div>
                <button onclick="window.removeItem('${item.key}')" style="color:#999; font-size:1.2rem; padding:0 10px; border:none; background:none; cursor:pointer;">&times;</button>
            </div>
            `;
                }).join('');
            }
        }

        
        if (cartTotal) cartTotal.innerText = parseFloat(cart.total).toFixed(2) + ' €';
    }

    
    window.removeItem = function(key) {
        updateCart('remove', { key });
    };

    
    const quickButtons = document.querySelectorAll('.nk-quick-add-btn');
    quickButtons.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const productId = btn.dataset.id;
            const hasVariants = btn.dataset.hasVariants === '1';

            if (hasVariants) {
                
                window.location.href = btn.closest('a').href;
            } else {
                
                updateCart('add', {
                    product_id: productId,
                    quantity: 1
                });
            }
        });

    });

    
    const addToCartForm = document.getElementById('addToCartForm');
    if (addToCartForm) {
        addToCartForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const btn = document.getElementById('btnAddToCart');
            const originalText = btn.innerText;
            btn.innerText = "Ajout...";
            btn.disabled = true;

            const formData = new FormData(addToCartForm);

            
            const payload = {
                product_id: formData.get('product_id'),
                variant_id: formData.get('variant_id') || null,
                quantity: formData.get('quantity')
                
            };

            updateCart('add', payload).then(() => {
                
                btn.innerText = originalText;
                btn.disabled = false;
            });
        });
    }

    
    const header = document.getElementById('mainHeader');
    if (header) {
        window.addEventListener('scroll', () => {
            if (window.scrollY > 50) {
                header.classList.add('is-scrolled');
            } else {
                header.classList.remove('is-scrolled');
            }
        });
    }

    
    updateCart('get');


    const burgerBtn = document.getElementById('burgerBtn');
    const menuDrawer = document.getElementById('menuDrawer');
    const menuOverlay = document.getElementById('menuOverlay');
    const menuClose = document.getElementById('menuClose');

    function openMenu() {
        if(menuDrawer && menuOverlay) {
            menuDrawer.classList.add('is-open');
            menuDrawer.style.transform = 'translateX(0)'; 
            menuOverlay.classList.add('is-open');
        }
    }

    function closeMenu() {
        if(menuDrawer && menuOverlay) {
            menuDrawer.classList.remove('is-open');
            menuDrawer.style.transform = 'translateX(-100%)';
            menuOverlay.classList.remove('is-open');
        }
    }

    if(burgerBtn) burgerBtn.addEventListener('click', openMenu);
    if(menuClose) menuClose.addEventListener('click', closeMenu);
    if(menuOverlay) menuOverlay.addEventListener('click', closeMenu);






        const attrRadios = document.querySelectorAll('.js-attr-radio');

        const btnAddToCart = document.getElementById('btnAddToCart');
        const stockMsg = document.getElementById('stockMessageArea');
        const priceDisplay = document.querySelector('.js-price-display');
        const descDisplay = document.getElementById('shortDescDisplay');
        const variantInput = document.getElementById('selectedVariantId');
        const quantityInput = document.getElementById('quantityInput');

        if( btnAddToCart && priceDisplay) {
            const mainImg = document.getElementById('mainImg');
            const thumbsContainer = document.getElementById('thumbsContainer');
            const mainImgWrap = document.querySelector('.nk-main-image-wrapper');

            let currentImages = [];
            let currentIdx = 0;
            let sliderInterval = null;
            let touchStartX = 0;
            let touchEndX = 0;


            function startSlider() {
                stopSlider();
                sliderInterval = setInterval(() => nextImage(), 4000);
            }

            function stopSlider() {
                if (sliderInterval) clearInterval(sliderInterval);
            }

            function showImage(index) {
                if (!currentImages.length) return;
                if (index >= currentImages.length) index = 0;
                if (index < 0) index = currentImages.length - 1;
                currentIdx = index;

                mainImg.style.opacity = '0.8';
                setTimeout(() => {
                    mainImg.src = currentImages[currentIdx];
                    mainImg.style.opacity = '1';
                }, 100);

                document.querySelectorAll('.nk-thumb').forEach(t => t.classList.remove('active'));
                const activeThumb = document.querySelector(`.nk-thumb[data-index="${currentIdx}"]`);
                if (activeThumb) {
                    activeThumb.classList.add('active');
                    if (thumbsContainer) {
                        const leftPos = activeThumb.offsetLeft - (thumbsContainer.clientWidth / 2) + (activeThumb.clientWidth / 2);
                        thumbsContainer.scrollTo({left: leftPos, behavior: 'smooth'});
                    }
                }
            }

            function nextImage() {
                showImage(currentIdx + 1);
            }

            function prevImage() {
                showImage(currentIdx - 1);
            }

            function initGallery(imagesData) {
                stopSlider();
                currentImages = imagesData.map(img => img.file_path ? '/storage/product_images/' + img.file_path : '/assets/img/placeholder.jpg');
                thumbsContainer.innerHTML = '';
                currentImages.forEach((src, idx) => {
                    const thumb = document.createElement('div');
                    thumb.className = (idx === 0) ? 'nk-thumb active' : 'nk-thumb';
                    thumb.dataset.index = idx;
                    thumb.innerHTML = `<img src="${src}" alt="">`;
                    thumb.addEventListener('click', () => {
                        stopSlider();
                        showImage(idx);
                    });
                    thumbsContainer.appendChild(thumb);
                });
                currentIdx = 0;
                if (currentImages.length) mainImg.src = currentImages[0];
                if (currentImages.length > 1) startSlider();
            }

            if (mainImgWrap) {
                mainImgWrap.addEventListener('touchstart', e => {
                    touchStartX = e.changedTouches[0].screenX;
                    stopSlider();
                }, {passive: true});
                mainImgWrap.addEventListener('touchend', e => {
                    touchEndX = e.changedTouches[0].screenX;
                    if (touchStartX - touchEndX > 50) nextImage();
                    if (touchEndX - touchStartX > 50) prevImage();
                }, {passive: true});
            }

            initGallery(productBase.images);


            const formatPrice = (p) => new Intl.NumberFormat('fr-FR', {
                style: 'decimal',
                minimumFractionDigits: 2
            }).format(p) + ' €';

            const formatDate = (dateString) => {
                let dateObj;
                if (dateString) {
                    dateObj = new Date(dateString);
                } else {
                    const now = new Date();
                    now.setMonth(now.getMonth() + 3);
                    dateObj = now;
                }
                return new Intl.DateTimeFormat('fr-FR', {month: 'long', year: 'numeric'}).format(dateObj);
            };


            function updateButtonState(stock, canPreorder, availDate) {
                const currentQty = parseInt(quantityInput.value) || 1;
                btnAddToCart.disabled = false;
                btnAddToCart.textContent = "Ajouter au panier";
                btnAddToCart.style.backgroundColor = "#1A1A2E";
                stockMsg.style.display = 'none';

                if (stock <= 0) {
                    if (canPreorder) {
                        const dateTxt = formatDate(availDate);
                        stockMsg.style.display = 'block';
                        stockMsg.style.color = '#C18C5D';
                        stockMsg.innerHTML = `Précommande : Expédition prévue en ${dateTxt}`;
                        btnAddToCart.textContent = "Précommander";
                        btnAddToCart.style.backgroundColor = "#C18C5D";
                    } else {
                        btnAddToCart.disabled = true;
                        btnAddToCart.textContent = "Rupture de stock";
                        btnAddToCart.style.backgroundColor = "#ccc";
                    }
                } else if (currentQty > stock) {
                    if (canPreorder) {
                        const dateTxt = formatDate(availDate);
                        stockMsg.style.display = 'block';
                        stockMsg.style.color = '#C18C5D';
                        stockMsg.innerHTML = `Attention : ${stock} en stock immédiat, le reste en précommande (Dispo ${dateTxt}).`;
                        btnAddToCart.textContent = "Commander (Stock + Précommande)";
                    } else {
                        stockMsg.style.display = 'block';
                        stockMsg.style.color = '#b91c1c';
                        stockMsg.innerHTML = `Seulement ${stock} pièces disponibles.`;
                    }
                }
            }


            function updateAttributeStates() {
                const groups = Array.from(document.querySelectorAll('.nk-form-group.js-attr-group'));

                groups.forEach((group, groupIndex) => {
                    const currentGroupId = group.dataset.groupId;
                    let previousSelections = [];
                    let isPreviousComplete = true;


                    for (let i = 0; i < groupIndex; i++) {
                        const prevGroup = groups[i];
                        const checked = prevGroup.querySelector('input:checked');
                        if (checked) {
                            previousSelections.push(parseInt(checked.value));
                        } else {
                            isPreviousComplete = false;
                        }
                    }

                    group.querySelectorAll('.js-attr-radio').forEach(input => {
                        const candidateId = parseInt(input.value);
                        const label = input.closest('label');

                        let isAvailable = false;
                        let isPreorderOnly = true;


                        for (const comboKey in combinations) {
                            const comboIds = comboKey.split('_').map(Number);
                            const variant = combinations[comboKey];

                            if (!comboIds.includes(candidateId)) continue;

                            let matchPrevious = true;
                            for (const prevId of previousSelections) {
                                if (!comboIds.includes(prevId)) {
                                    matchPrevious = false;
                                    break;
                                }
                            }

                            if (matchPrevious) {

                                if (variant.stock > 0) {
                                    isAvailable = true;
                                    isPreorderOnly = false;
                                    break;
                                } else if (Number(variant.preorder) === 1) {
                                    isAvailable = true;

                                }
                            }
                        }


                        label.classList.remove('is-unavailable');
                        label.classList.remove('is-preorder');
                        label.style.display = '';

                        if (groupIndex === 0) {

                            if (!isAvailable) {
                                label.classList.add('is-unavailable');
                            } else if (isPreorderOnly) {
                                label.classList.add('is-preorder');
                            }
                        } else {

                            if (!isPreviousComplete) {

                            } else {
                                if (!isAvailable) {
                                    label.style.display = 'none';
                                    if (input.checked) input.checked = false;
                                } else if (isPreorderOnly) {
                                    label.classList.add('is-preorder');
                                }
                            }
                        }
                    });
                });
            }

            function checkCombination() {
                updateAttributeStates();

                document.querySelectorAll('.nk-attributes-section .nk-form-group').forEach(group => {
                    const checked = group.querySelector('input:checked');
                    const labelSpan = group.querySelector('.js-selected-val');
                    if (labelSpan) labelSpan.textContent = checked ? ' : ' + checked.dataset.name : '';

                    group.querySelectorAll('.nk-attr-label').forEach(lbl => {
                        if (lbl.querySelector('input:checked')) lbl.classList.add('active');
                        else lbl.classList.remove('active');
                    });
                });

                const groups = document.querySelectorAll('.nk-attributes-section .nk-form-group');
                let selectedIds = [];
                let allSelected = true;

                groups.forEach(group => {
                    const checked = group.querySelector('input:checked');
                    if (checked) selectedIds.push(parseInt(checked.value));
                    else allSelected = false;
                });

                if (!allSelected) {
                    btnAddToCart.disabled = true;
                    btnAddToCart.textContent = "Choisir les options";
                    stockMsg.style.display = 'none';
                    return;
                }

                selectedIds.sort((a, b) => a - b);
                const key = selectedIds.join('_');
                const variant = combinations[key];

                if (!variant) {
                    btnAddToCart.disabled = true;
                    btnAddToCart.textContent = "Indisponible";
                    stockMsg.style.display = 'none';
                    return;
                }

                variantInput.value = variant.id;
                const finalPrice = (variant.price !== null && variant.price > 0) ? variant.price : productBase.price;
                priceDisplay.textContent = formatPrice(finalPrice);

                if (variant.desc && variant.desc.trim() !== "") descDisplay.innerHTML = variant.desc;
                else descDisplay.innerHTML = productBase.desc;

                if (variant.images && variant.images.length > 0) initGallery(variant.images);
                else initGallery(productBase.images);

                updateButtonState(variant.stock, Number(variant.preorder) === 1, variant.date);
            }


            if (document.querySelector('.js-attr-radio')) {
                attrRadios.forEach(r => r.addEventListener('change', checkCombination));


                const groups = document.querySelectorAll('.nk-attributes-section .nk-form-group');
                let isAnyChecked = false;
                groups.forEach(g => {
                    if (g.querySelector('input:checked')) isAnyChecked = true;
                });


                if (!isAnyChecked && Object.keys(combinations).length > 0) {
                    let targetKey = null;


                    for (const key in combinations) {
                        const variant = combinations[key];
                        if (variant.stock > 0 || Number(variant.preorder) === 1) {
                            targetKey = key;
                            break;
                        }
                    }


                    if (!targetKey) {
                        targetKey = Object.keys(combinations)[0];
                    }


                    if (targetKey) {
                        const ids = targetKey.split('_');
                        ids.forEach(id => {
                            const input = document.querySelector(`.js-attr-radio[value="${id}"]`);
                            if (input) input.checked = true;
                        });
                    }
                }


                checkCombination();
            } else {
                updateButtonState(productBase.stock, Number(productBase.preorder) === 1, productBase.date);
            }

            document.querySelectorAll('.js-qty-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    let val = parseInt(quantityInput.value);
                    const isInc = (btn.dataset.action === 'inc');
                    if (isInc) quantityInput.value = val + 1;
                    else if (val > 1) quantityInput.value = val - 1;

                    if (document.querySelector('.js-attr-radio')) checkCombination();
                    else updateButtonState(productBase.stock, Number(productBase.preorder) === 1, productBase.date);
                });
            });
        }


});




(function() {
    
    const DELAY_BEFORE_TRACKING = 2000; 
    let hasTracked = false;

    
    
    function getPageContext() {
        const path = window.location.pathname;

        if (path === '/' || path === '/index.php') return { type: 'home' };
        if (path.startsWith('/c/')) return { type: 'category', id: null }; 
        if (path.startsWith('/p/')) {
            
            const input = document.querySelector('input[name="product_id"]');
            const pid = input ? input.value : null;
            return { type: 'product', id: pid };
        }
        if (path === '/checkout') return { type: 'checkout' };
        if (path === '/panier') return { type: 'cart' };

        return { type: 'other' };
    }

    function sendStat() {
        if (hasTracked) return;
        hasTracked = true;

        const context = getPageContext();

        
        fetch('/api/stats.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(context),
            keepalive: true
        }).catch(() => {}); 

        
        removeListeners();
    }

    function initTracking() {
        
        ['mousemove', 'touchstart', 'scroll', 'keydown', 'click'].forEach(evt => {
            document.addEventListener(evt, onHumanInteraction, { passive: true, once: true });
        });
    }

    function onHumanInteraction() {
        
        sendStat();
    }

    function removeListeners() {
        ['mousemove', 'touchstart', 'scroll', 'keydown', 'click'].forEach(evt => {
            document.removeEventListener(evt, onHumanInteraction);
        });
    }


    const fltooltip_init = () => {
        let tooltipContainer = document.getElementById('fltooltip_container');
        if (!tooltipContainer) {
            tooltipContainer = document.createElement('div');
            tooltipContainer.id = 'fltooltip_container';
            tooltipContainer.innerHTML = '<div id="fltooltip_content"></div><div id="fltooltip_arrow"></div>';
            document.body.appendChild(tooltipContainer);
        }

        const tooltipContent = document.getElementById('fltooltip_content');
        let touchTimer = null;
        let checkInterval = null;
        let currentTriggerElement = null;
        let mousePos = { x: 0, y: 0 };

        
        document.addEventListener('mousemove', (e) => {
            mousePos.x = e.clientX;
            mousePos.y = e.clientY;
        });

        const checkCursorOverlap = () => {
            if (!currentTriggerElement || !tooltipContainer.classList.contains('visible')) {
                return;
            }
            const rect = currentTriggerElement.getBoundingClientRect();
            
            const isOver = (
                mousePos.x >= rect.left - 2 &&
                mousePos.x <= rect.right + 2 &&
                mousePos.y >= rect.top - 2 &&
                mousePos.y <= rect.bottom + 2
            );

            if (!isOver) {
                fltooltip_hide();
            }
        };

        const fltooltip_show = (element) => {
            const text = element.dataset.fltooltip;
            if (!text) return;

            currentTriggerElement = element;
            tooltipContent.innerHTML = text;

            const rect = element.getBoundingClientRect();
            tooltipContainer.classList.remove('arrow-right', 'arrow-left');

            
            const tooltipRect = tooltipContainer.getBoundingClientRect();

            
            let left = rect.right + 10;
            const elementCenterY = rect.top + (rect.height / 2);
            let top = elementCenterY - (tooltipRect.height / 2);
            let positionIsLeft = false;

            
            if (left + tooltipRect.width > window.innerWidth - 10) {
                left = rect.left - tooltipRect.width - 10;
                positionIsLeft = true;
            }

            
            if (top < 10) top = 10;
            else if (top + tooltipRect.height > window.innerHeight - 10) {
                top = window.innerHeight - 10 - tooltipRect.height;
            }

            
            if (positionIsLeft) {
                tooltipContainer.classList.add('arrow-right');
            } else {
                tooltipContainer.classList.add('arrow-left');
            }

            tooltipContainer.style.top = `${top}px`;
            tooltipContainer.style.left = `${left}px`;
            tooltipContainer.classList.add('visible');

            if (!checkInterval) {
                checkInterval = setInterval(checkCursorOverlap, 200);
            }
        };

        const fltooltip_hide = () => {
            tooltipContainer.classList.remove('visible');
            currentTriggerElement = null;
            if (checkInterval) {
                clearInterval(checkInterval);
                checkInterval = null;
            }
        };

        
        const attachTooltips = () => {
            document.querySelectorAll('[data-fltooltip]:not([data-fltooltip-attached])').forEach(el => {
                el.setAttribute('data-fltooltip-attached', 'true');

                
                if(el.hasAttribute('title')) {
                    el.setAttribute('data-original-title', el.getAttribute('title'));
                    el.removeAttribute('title');
                }

                
                el.addEventListener('mouseenter', () => fltooltip_show(el));
                el.addEventListener('mouseleave', fltooltip_hide);

                
                el.addEventListener('touchstart', (e) => {
                    
                    mousePos.x = e.touches[0].clientX;
                    mousePos.y = e.touches[0].clientY;

                    touchTimer = setTimeout(() => {
                        fltooltip_show(el);
                        
                        if (window.navigator && window.navigator.vibrate) {
                            window.navigator.vibrate(50);
                        }
                    }, 500); 
                }, { passive: true });

                el.addEventListener('touchend', () => {
                    if (touchTimer) {
                        clearTimeout(touchTimer); 
                        touchTimer = null;
                    }
                    fltooltip_hide(); 
                });

                el.addEventListener('touchmove', () => {
                    if (touchTimer) {
                        clearTimeout(touchTimer); 
                        touchTimer = null;
                    }
                    fltooltip_hide();
                });
            });
        };

        
        attachTooltips();

        
        
        
    };

    
    fltooltip_init();


    function rot13(str) {
        return str.replace(/[a-zA-Z]/g, function(c) {
            return String.fromCharCode((c <= "Z" ? 90 : 122) >= (c = c.charCodeAt(0) + 13) ? c : c - 26);
        });
    }

    const mailLinks = document.querySelectorAll('.mailme');

    mailLinks.forEach(link => {
        
        const encoded = link.getAttribute('data-enc');
        if (!encoded) return;

        
        const email = rot13(encoded);

        
        link.href = 'mailto:' + email;

        
        link.title = 'Envoyer un email à ' + email;

        
        link.removeAttribute('data-enc');
    });
    
    setTimeout(initTracking, DELAY_BEFORE_TRACKING);

})();