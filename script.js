document.addEventListener('DOMContentLoaded', function() {
    

    const slider = document.querySelector('.slider');
    const slides = document.querySelectorAll('.slide');
    const prevBtn = document.querySelector('.prev-btn');
    const nextBtn = document.querySelector('.next-btn');
    const dots = document.querySelectorAll('.dot');
    
    if (slider && slides.length > 0) {
        let currentSlide = 0;
        const totalSlides = slides.length;
        
        function updateSlider() {
            slider.style.transform = `translateX(-${currentSlide * 100}%)`;
            slides.forEach((slide, index) => {
                slide.classList.toggle('active', index === currentSlide);
            });
            dots.forEach((dot, index) => {
                dot.classList.toggle('active', index === currentSlide);
            });
        }
        
        function nextSlide() {
            currentSlide = (currentSlide + 1) % totalSlides;
            updateSlider();
        }
        
        function prevSlide() {
            currentSlide = (currentSlide - 1 + totalSlides) % totalSlides;
            updateSlider();
        }
        
        if (nextBtn) nextBtn.addEventListener('click', nextSlide);
        if (prevBtn) prevBtn.addEventListener('click', prevSlide);
        
        dots.forEach((dot, index) => {
            dot.addEventListener('click', () => {
                currentSlide = index;
                updateSlider();
            });
        });
        
        let slideInterval = setInterval(nextSlide, 5000);
        const sliderContainer = document.querySelector('.slider-container');
        if (sliderContainer) {
            sliderContainer.addEventListener('mouseenter', () => clearInterval(slideInterval));
            sliderContainer.addEventListener('mouseleave', () => {
                slideInterval = setInterval(nextSlide, 5000);
            });
        }
        updateSlider();
    }


    const dropdown = document.querySelector('.dropdown');
    const navItemWithDropdown = document.querySelector('.nav-item:has(.dropdown)');
    
    if (dropdown && navItemWithDropdown) {
        let dropdownTimer;
        navItemWithDropdown.addEventListener('mouseenter', function() {
            clearTimeout(dropdownTimer);
            dropdown.style.display = 'block';
        });
        navItemWithDropdown.addEventListener('mouseleave', function() {
            dropdownTimer = setTimeout(() => dropdown.style.display = 'none', 200);
        });
        dropdown.addEventListener('mouseenter', () => clearTimeout(dropdownTimer));
        dropdown.addEventListener('mouseleave', () => {
            dropdownTimer = setTimeout(() => dropdown.style.display = 'none', 100);
        });
        dropdown.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                dropdown.style.display = 'none';
                alert(`Вы выбрали услугу "${this.textContent}"`);
                openModal();
            });
        });
    }
    

    const mobileMenuBtn = document.getElementById('mobile-menu-btn');
    const mobileMenu = document.getElementById('mobile-menu');
    
    if (mobileMenuBtn && mobileMenu) {
        mobileMenuBtn.addEventListener('click', function() {
            mobileMenu.classList.toggle('active');
            document.body.style.overflow = mobileMenu.classList.contains('active') ? 'hidden' : 'auto';
        });
        mobileMenu.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', () => {
                mobileMenu.classList.remove('active');
                document.body.style.overflow = 'auto';
            });
        });
    }
    

    const modal = document.getElementById('modal');
    const modalClose = document.getElementById('modalClose');
    const contactBtn = document.getElementById('contact-btn');
    const mainOrderBtn = document.getElementById('main-order-btn');
    const mobileContactBtn = document.getElementById('mobile-contact-btn');
    
    function openModal() {
        if (modal) {
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    }
    
    function closeModal() {
        if (modal) {
            modal.classList.remove('active');
            document.body.style.overflow = 'auto';
        }
    }
    
    if (contactBtn) contactBtn.addEventListener('click', openModal);
    if (mainOrderBtn) mainOrderBtn.addEventListener('click', openModal);
    if (mobileContactBtn) {
        mobileContactBtn.addEventListener('click', function(e) {
            e.preventDefault();
            if (mobileMenu) mobileMenu.classList.remove('active');
            document.body.style.overflow = 'auto';
            openModal();
        });
    }
    if (modalClose) modalClose.addEventListener('click', closeModal);
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) closeModal();
        });
    }
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal && modal.classList.contains('active')) closeModal();
    });
    

    document.querySelectorAll('.btn-price').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const card = this.closest('.pricing-card');
            const tariffName = card.querySelector('h3').textContent;
            const price = card.querySelector('.price').textContent;
            alert(`Вы выбрали тариф "${tariffName}" за ${price}. Откроем форму для оформления...`);
            openModal();
        });
    });
    

    const callbackForm = document.getElementById('callbackForm');
    const modalBody = document.querySelector('.modal-body');
    
    if (callbackForm && modalBody) {

        let modalResult = document.getElementById('modalResult');
        if (!modalResult) {
            modalResult = document.createElement('div');
            modalResult.id = 'modalResult';
            modalResult.style.marginTop = '20px';
            modalResult.style.padding = '15px';
            modalResult.style.borderRadius = '10px';
            modalResult.style.display = 'none';
            modalBody.appendChild(modalResult);
        }
        
        callbackForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(callbackForm);
            const data = {
                name: formData.get('name'),
                phone: formData.get('phone'),
                email: formData.get('email'),
                wishes: formData.get('wishes')
            };
            
            const submitBtn = this.querySelector('.modal-submit-btn');
            const originalText = submitBtn.textContent;
            submitBtn.textContent = 'Отправка...';
            submitBtn.disabled = true;
            
            modalResult.style.display = 'block';
            modalResult.innerHTML = '⏳ Отправка...';
            modalResult.style.background = '#e3f2fd';
            modalResult.style.color = '#0d47a1';
            
            try {
                const response = await fetch('/web_backend_8/api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const result = await response.json();
                
                if (result.success) {
                    modalResult.innerHTML = `
                        <div style="color: #28a745;">
                            ✅ ${result.message || 'Заявка принята!'}<br><br>
                            <strong>🔐 Ваши данные для входа:</strong><br>
                            📌 Логин: <strong style="background:#f0f0f0; padding:2px 8px; border-radius:5px;">${result.login}</strong><br>
                            🔑 Пароль: <strong style="background:#f0f0f0; padding:2px 8px; border-radius:5px;">${result.password}</strong><br><br>
                            <strong>✏️ Редактировать заявку:</strong><br>
                            🔗 <a href="/web_backend_8/edit.php" target="_blank" style="color: #28a745;">Нажмите сюда</a> чтобы войти и изменить данные.<br>
                            <small style="color: #666;">Введите логин и пароль на открывшейся странице.</small>
                        </div>
                    `;
                    modalResult.style.background = '#d4edda';
                    callbackForm.reset();
                    
       
                    setTimeout(() => {
                        closeModal();
                        modalResult.style.display = 'none';
                    }, 8000);
                } else {
                    let errors = '';
                    for (let field in result.errors) {
                        errors += `${result.errors[field]}<br>`;
                    }
                    modalResult.innerHTML = `<div style="color: #dc3545;">❌ ${errors}</div>`;
                    modalResult.style.background = '#f8d7da';
                }
            } catch (error) {
                modalResult.innerHTML = '<div style="color: #dc3545;">❌ Ошибка соединения</div>';
                modalResult.style.background = '#f8d7da';
            } finally {
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
            }
        });
    }
    

    const mainForm = document.getElementById('mainForm');
    const apiResult = document.getElementById('apiResult');
    
    if (mainForm && apiResult) {
        mainForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(mainForm);
            const data = {
                name: formData.get('name'),
                phone: formData.get('phone'),
                email: formData.get('email'),
                wishes: formData.get('wishes')
            };
            
            apiResult.style.display = 'block';
            apiResult.innerHTML = '⏳ Отправка...';
            apiResult.style.background = '#e3f2fd';
            apiResult.style.color = '#0d47a1';
            
            try {
                const response = await fetch('/web_backend_8/api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const result = await response.json();
                
                if (result.success) {
                    apiResult.innerHTML = `
                        <div style="color: #28a745;">
                            ✅ ${result.message || 'Заявка принята!'}<br><br>
                            <strong>🔐 Ваши данные для входа:</strong><br>
                            📌 Логин: <strong style="background:#f0f0f0; padding:2px 8px; border-radius:5px;">${result.login}</strong><br>
                            🔑 Пароль: <strong style="background:#f0f0f0; padding:2px 8px; border-radius:5px;">${result.password}</strong><br><br>
                            <strong>✏️ Редактировать заявку:</strong><br>
                            🔗 <a href="/web_backend_8/edit.php" target="_blank" style="color: #28a745;">Нажмите сюда</a> чтобы войти и изменить данные.<br>
                            <small style="color: #666;">Введите логин и пароль на открывшейся странице.</small>
                        </div>
                    `;
                    apiResult.style.background = '#d4edda';
                    mainForm.reset();
                } else {
                    let errors = '';
                    for (let field in result.errors) {
                        errors += `${result.errors[field]}<br>`;
                    }
                    apiResult.innerHTML = `<div style="color: #dc3545;">❌ ${errors}</div>`;
                    apiResult.style.background = '#f8d7da';
                }
            } catch (error) {
                apiResult.innerHTML = '<div style="color: #dc3545;">❌ Ошибка соединения</div>';
                apiResult.style.background = '#f8d7da';
            }
            
            setTimeout(() => {
                apiResult.style.display = 'none';
            }, 30000);
        });
    }
});
