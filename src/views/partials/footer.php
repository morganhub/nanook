<div id="nk-newsletter-modal" class="nk-newsletter-overlay" style="display:none;">
    <div class="nk-newsletter-card">
        <button id="nk-newsletter-close" class="nk-newsletter-close">&times;</button>
        <div class="nk-newsletter-content">
            <h3 class="nk-title-md">Restons en contact</h3>
            <p class="nk-text-body" style="font-size:0.95rem; margin-bottom:20px;">
                Inscrivez-vous pour découvrir les dernières créations de l'atelier Nanook en avant-première.
            </p>
            <form id="nk-newsletter-form">
                <input type="email" id="nk-newsletter-email" placeholder="Votre adresse email" required>
                <button type="submit" class="nk-btn-primary" style="margin-top:10px;">Je m'abonne</button>
            </form>
            <div id="nk-newsletter-msg" style="margin-top:10px; font-size:0.9rem;"></div>
        </div>
    </div>
</div>

<style>
    .nk-newsletter-overlay {
        position: fixed; inset: 0; z-index: 9999;
        background: rgba(0,0,0,0.5); backdrop-filter: blur(2px);
        display: flex; align-items: center; justify-content: center;
        opacity: 0; transition: opacity 0.4s ease;
    }
    .nk-newsletter-overlay.is-visible { opacity: 1; }

    .nk-newsletter-card {
        background: #FDFBF7; width: 90%; max-width: 450px;
        padding: 40px 30px; position: relative;
        box-shadow: 0 20px 50px rgba(0,0,0,0.15);
        transform: translateY(20px); transition: transform 0.4s ease;
        text-align: center; border: 1px solid #E5E5E5;
    }
    .nk-newsletter-overlay.is-visible .nk-newsletter-card { transform: translateY(0); }

    .nk-newsletter-close {
        position: absolute; top: 10px; right: 15px;
        background: none; border: none; font-size: 24px; cursor: pointer; color: #999;
    }
    #nk-newsletter-email {
        width: 100%; padding: 12px; border: 1px solid #ccc;
        font-family: inherit; font-size: 1rem; text-align: center;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const COOKIE_NAME = 'nanook_nl_popup';
        const DAYS_HIDDEN = 14;
        const DELAY_MS = 5000; 

        
        const getCookie = (name) => {
            const value = `; ${document.cookie}`;
            const parts = value.split(`; ${name}=`);
            if (parts.length === 2) return parts.pop().split(';').shift();
        };
        const setCookie = (name, val, days) => {
            const d = new Date();
            d.setTime(d.getTime() + (days*24*60*60*1000));
            document.cookie = `${name}=${val};expires=${d.toUTCString()};path=/`;
        };

        const modal = document.getElementById('nk-newsletter-modal');
        const closeBtn = document.getElementById('nk-newsletter-close');
        const form = document.getElementById('nk-newsletter-form');
        const msg = document.getElementById('nk-newsletter-msg');

        
        if (!getCookie(COOKIE_NAME)) {
            setTimeout(() => {
                modal.style.display = 'flex';
                
                setTimeout(() => modal.classList.add('is-visible'), 10);
            }, DELAY_MS);
        }

        const closeModal = () => {
            modal.classList.remove('is-visible');
            setTimeout(() => modal.style.display = 'none', 400);
            setCookie(COOKIE_NAME, 'seen', DAYS_HIDDEN);
        };

        if(closeBtn) closeBtn.addEventListener('click', closeModal);

        
        modal.addEventListener('click', (e) => {
            if(e.target === modal) closeModal();
        });

        
        if(form) {
            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                const email = document.getElementById('nk-newsletter-email').value;
                const btn = form.querySelector('button');

                btn.disabled = true; btn.textContent = '...';

                try {
                    const res = await fetch('/api/newsletter_subscribe.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({ email })
                    });
                    const data = await res.json();

                    if (data.success) {
                        form.style.display = 'none';
                        msg.style.color = '#15803d';
                        msg.textContent = "Merci pour votre inscription !";
                        
                        setTimeout(closeModal, 2000);
                        
                        setCookie(COOKIE_NAME, 'subscribed', 365);
                    } else {
                        msg.style.color = '#b91c1c';
                        msg.textContent = data.error || "Une erreur est survenue.";
                        btn.disabled = false; btn.textContent = "Je m'abonne";
                    }
                } catch (err) {
                    console.error(err);
                    btn.disabled = false;
                }
            });
        }
    });
</script>