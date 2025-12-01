jQuery(document).ready(function($) {
    'use strict';

    class BenefitBoxSlider {
        constructor(container) {
            this.container = container;
            this.slider = container.querySelector('.benefit-slider-container');
            this.cards = container.querySelectorAll('.benefit-card');
            this.navContainer = container.querySelector('.benefit-slider-nav');
            this.prevArrow = container.querySelector('.benefit-arrow-prev');
            this.nextArrow = container.querySelector('.benefit-arrow-next');
            this.dots = [];
            
            this.currentIndex = 0;
            this.cardWidth = 280; // Default card width
            this.gap = 20;
            this.isTabletOrMobile = false;
            
            this.startX = 0;
            this.currentX = 0;
            this.startY = 0;
            this.currentY = 0;
            this.isDragging = false;
            this.dragThreshold = 50;
            this.currentTranslateX = 0; // Track current transform position
            this.maxTranslateX = 0; // Maximum allowed translateX (right boundary)
            this.minTranslateX = 0; // Minimum allowed translateX (left boundary)
            
            this.init();
        }
        
        init() {
            this.checkScreenSize();
            this.setupEventListeners();
            this.calculateBoundaries();
            
            if (this.isTabletOrMobile) {
                this.createNavigation();
                this.updateSliderPosition();
                this.updateArrowStates();
            }
        }
        
        checkScreenSize() {
            this.isTabletOrMobile = window.innerWidth <= 1024;
            
            if (this.isTabletOrMobile) {
                // Adjust card width based on screen size
                if (window.innerWidth <= 480) {
                    this.cardWidth = 240;
                    this.gap = 20;
                } else if (window.innerWidth <= 768) {
                    this.cardWidth = 260;
                    this.gap = 20;
                } else {
                    this.cardWidth = 280;
                    this.gap = 20;
                }
            }
            
            // Recalculate boundaries when screen size changes
            this.calculateBoundaries();
        }
        
        calculateBoundaries() {
            if (!this.slider || !this.container) return;
            
            const containerWidth = this.container.offsetWidth - 40; // Account for padding
            const totalCardsWidth = this.cards.length * (this.cardWidth + this.gap) - this.gap;
            
            if (this.isTabletOrMobile) {
                // For mobile/tablet, calculate based on slide navigation
                const cardsPerView = Math.floor(containerWidth / (this.cardWidth + this.gap));
                const totalSlides = Math.ceil(this.cards.length / cardsPerView);
                const maxSlideIndex = Math.max(0, totalSlides - 1);
                
                this.maxTranslateX = 0; // Rightmost position (first slide)
                this.minTranslateX = -(maxSlideIndex * cardsPerView * (this.cardWidth + this.gap)); // Leftmost position
            } else {
                // For desktop, allow dragging within content bounds
                this.maxTranslateX = 0; // Rightmost position
                
                // Calculate the minimum translateX to show all content without extra spacing
                if (totalCardsWidth <= containerWidth) {
                    // If all cards fit, don't allow dragging
                    this.minTranslateX = 0;
                } else {
                    // Allow dragging to show all content, accounting for container padding
                    this.minTranslateX = containerWidth - totalCardsWidth - 20; // Extra 20px for padding
                }
            }
        }
        
        setupEventListeners() {
            // Resize listener
            window.addEventListener('resize', () => {
                this.checkScreenSize();
                if (this.isTabletOrMobile) {
                    this.updateSliderPosition();
                    this.createNavigation();
                    this.updateArrowStates();
                } else {
                    this.removeNavigation();
                }
            });
            
            // Arrow navigation events
            if (this.prevArrow && this.nextArrow) {
                this.prevArrow.addEventListener('click', () => this.prevSlide());
                this.nextArrow.addEventListener('click', () => this.nextSlide());
            }
            
            // Touch events - all passive: false to allow preventDefault
            this.slider.addEventListener('touchstart', this.handleTouchStart.bind(this), { passive: false });
            this.slider.addEventListener('touchmove', this.handleTouchMove.bind(this), { passive: false });
            this.slider.addEventListener('touchend', this.handleTouchEnd.bind(this), { passive: false });
            this.slider.addEventListener('touchcancel', this.handleTouchEnd.bind(this), { passive: false });
            
            // Mouse events for desktop testing
            this.slider.addEventListener('mousedown', this.handleMouseDown.bind(this));
            this.slider.addEventListener('mousemove', this.handleMouseMove.bind(this));
            this.slider.addEventListener('mouseup', this.handleMouseUp.bind(this));
            this.slider.addEventListener('mouseleave', this.handleMouseUp.bind(this));
            
            // Prevent default drag behavior
            this.slider.addEventListener('dragstart', (e) => e.preventDefault());
            this.slider.addEventListener('selectstart', (e) => e.preventDefault());
        }
        
        createNavigation() {
            if (!this.isTabletOrMobile || !this.navContainer) return;
            
            // Clear existing dots
            this.navContainer.innerHTML = '';
            this.dots = [];
            
            // Calculate visible cards and total pages
            const containerWidth = this.container.offsetWidth - 40; // Account for padding
            const cardsPerView = Math.floor(containerWidth / (this.cardWidth + this.gap));
            const totalPages = Math.max(1, Math.ceil(this.cards.length / cardsPerView));
            
            // Create dots
            for (let i = 0; i < totalPages; i++) {
                const dot = document.createElement('button');
                dot.className = 'benefit-nav-dot';
                dot.setAttribute('aria-label', `Go to slide ${i + 1}`);
                
                if (i === this.currentIndex) {
                    dot.classList.add('active');
                }
                
                dot.addEventListener('click', () => this.goToSlide(i));
                
                this.navContainer.appendChild(dot);
                this.dots.push(dot);
            }
        }
        
        removeNavigation() {
            if (this.navContainer) {
                this.navContainer.innerHTML = '';
                this.dots = [];
            }
        }
        
        updateSliderPosition() {
            if (!this.slider) return;
            
            if (this.isTabletOrMobile) {
                const containerWidth = this.container.offsetWidth - 40; // Account for padding
                const cardsPerView = Math.floor(containerWidth / (this.cardWidth + this.gap));
                const translateX = -(this.currentIndex * cardsPerView * (this.cardWidth + this.gap));
                
                this.currentTranslateX = translateX; // Update tracked position
                this.slider.style.transform = `translateX(${translateX}px)`;
                
                // Update active dot
                this.dots.forEach((dot, index) => {
                    dot.classList.toggle('active', index === this.currentIndex);
                });
                
                // Update arrow states
                this.updateArrowStates();
            } else {
                // For desktop, just reset to original position
                this.currentTranslateX = 0;
                this.slider.style.transform = '';
            }
        }
        
        goToSlide(index) {
            const containerWidth = this.container.offsetWidth - 40;
            const cardsPerView = Math.floor(containerWidth / (this.cardWidth + this.gap));
            const maxIndex = Math.max(0, Math.ceil(this.cards.length / cardsPerView) - 1);
            
            this.currentIndex = Math.max(0, Math.min(index, maxIndex));
            this.updateSliderPosition();
        }
        
        nextSlide() {
            const containerWidth = this.container.offsetWidth - 40;
            const cardsPerView = Math.floor(containerWidth / (this.cardWidth + this.gap));
            const maxIndex = Math.max(0, Math.ceil(this.cards.length / cardsPerView) - 1);
            
            if (this.currentIndex < maxIndex) {
                this.currentIndex++;
                this.updateSliderPosition();
            }
        }
        
        prevSlide() {
            if (this.currentIndex > 0) {
                this.currentIndex--;
                this.updateSliderPosition();
            }
        }
        
        updateArrowStates() {
            if (!this.prevArrow || !this.nextArrow || !this.isTabletOrMobile) return;
            
            const containerWidth = this.container.offsetWidth - 40;
            const cardsPerView = Math.floor(containerWidth / (this.cardWidth + this.gap));
            const maxIndex = Math.max(0, Math.ceil(this.cards.length / cardsPerView) - 1);
            
            // Update previous arrow
            this.prevArrow.classList.toggle('disabled', this.currentIndex === 0);
            
            // Update next arrow
            this.nextArrow.classList.toggle('disabled', this.currentIndex >= maxIndex);
        }
        
        // Touch event handlers
        handleTouchStart(e) {
            this.startX = e.touches[0].clientX;
            this.startY = e.touches[0].clientY;
            this.currentY = this.startY;
            this.isDragging = true;
            
            // Store current transform position
            this.slider.style.transition = 'none';
            this.slider.style.userSelect = 'none';
            this.slider.style.webkitUserSelect = 'none';
            e.preventDefault();
        }
        
        handleTouchMove(e) {
            if (!this.isDragging) return;
            
            this.currentX = e.touches[0].clientX;
            this.currentY = e.touches[0].clientY;
            const deltaX = this.currentX - this.startX;
            const deltaY = this.currentY - this.startY;

            // If the user is scrolling vertically more than horizontally, allow native scrolling
            if (Math.abs(deltaY) > Math.abs(deltaX)) {
                return;
            }

            // Horizontal swipe: prevent page scroll and move slider
            e.preventDefault();
            
            // Calculate new position with boundaries
            const newTranslateX = this.currentTranslateX + deltaX;
            const constrainedTranslateX = Math.max(this.minTranslateX, Math.min(this.maxTranslateX, newTranslateX));
            
            this.slider.style.transform = `translateX(${constrainedTranslateX}px)`;
        }
        
        handleTouchEnd(e) {
            if (!this.isDragging) return;
            
            this.isDragging = false;
            this.slider.style.transition = 'transform 0.3s ease-in-out';
            this.slider.style.userSelect = '';
            this.slider.style.webkitUserSelect = '';
            
            const deltaX = this.currentX - this.startX;
            
            if (this.isTabletOrMobile && Math.abs(deltaX) > this.dragThreshold) {
                if (deltaX > 0) {
                    this.prevSlide();
                } else {
                    this.nextSlide();
                }
            } else {
                // Update current position to maintain the dragged position with boundaries
                const newTranslateX = this.currentTranslateX + deltaX;
                this.currentTranslateX = Math.max(this.minTranslateX, Math.min(this.maxTranslateX, newTranslateX));
            }
            e.preventDefault();
        }
        
        // Mouse event handlers (for desktop testing)
        handleMouseDown(e) {
            this.startX = e.clientX;
            this.isDragging = true;
            
            // Store current transform position
            this.slider.style.transition = 'none';
            this.slider.style.userSelect = 'none';
            this.slider.style.webkitUserSelect = 'none';
            e.preventDefault();
        }
        
        handleMouseMove(e) {
            if (!this.isDragging) return;
            
            this.currentX = e.clientX;
            const deltaX = this.currentX - this.startX;
            
            // Calculate new position with boundaries
            const newTranslateX = this.currentTranslateX + deltaX;
            const constrainedTranslateX = Math.max(this.minTranslateX, Math.min(this.maxTranslateX, newTranslateX));
            
            this.slider.style.transform = `translateX(${constrainedTranslateX}px)`;
            e.preventDefault();
        }
        
        handleMouseUp(e) {
            if (!this.isDragging) return;
            
            this.isDragging = false;
            this.slider.style.transition = 'transform 0.3s ease-in-out';
            this.slider.style.userSelect = '';
            this.slider.style.webkitUserSelect = '';
            
            const deltaX = this.currentX - this.startX;
            
            if (this.isTabletOrMobile && Math.abs(deltaX) > this.dragThreshold) {
                if (deltaX > 0) {
                    this.prevSlide();
                } else {
                    this.nextSlide();
                }
            } else {
                // Update current position to maintain the dragged position with boundaries
                const newTranslateX = this.currentTranslateX + deltaX;
                this.currentTranslateX = Math.max(this.minTranslateX, Math.min(this.maxTranslateX, newTranslateX));
            }
            e.preventDefault();
        }
    }
    
    // Initialize sliders (idempotent to avoid duplicate init in Elementor editor)
    function initBenefitSliders() {
        const containers = document.querySelectorAll('.benefit-card-grid');
        containers.forEach(container => {
            if (container.__benefitBoxInitialized) {
                return;
            }
            container.__benefitBoxInitialized = true;
            new BenefitBoxSlider(container);
        });
    }

    // Bind delegated event handlers once (avoid duplicate bindings)
    let benefitHandlersBound = false;
    function bindBenefitHandlersOnce() {
        if (benefitHandlersBound) return;
        benefitHandlersBound = true;

        // WhatsApp click handler (delegated + namespaced)
        $(document)
            .off('click.benefitWhatsapp', '.whatsapp-card')
            .on('click.benefitWhatsapp', '.whatsapp-card', function() {
                const whatsappNumber = $(this).data('whatsapp-number');
                const predefinedText = $(this).data('predefined-text');

                if (whatsappNumber && predefinedText) {
                    let message = predefinedText;
                    let pageTitle = document.title.replace(' - Senheng', '');
                    message = message.replace('[senheng_page_title]', pageTitle);
                    message = message.replace('[senheng_page_url]', window.location.origin + window.location.pathname);
                    const encodedMessage = encodeURIComponent(message);
                    const whatsappUrl = `https://wa.me/${String(whatsappNumber).replace(/[^0-9]/g, '')}?text=${encodedMessage}`;
                    window.open(whatsappUrl, '_blank');
                }
            });

        // Installment click handler (delegated)
        $(document)
            .off('click.benefitInstallment', '.installment-details')
            .on('click.benefitInstallment', '.installment-details', function() {
                if (window.console && console.log) console.log('Installment card clicked');
            });

        // Hover effects (delegated)
        $(document)
            .off('mouseenter.benefitHover', '.benefit-card')
            .on('mouseenter.benefitHover', '.benefit-card', function() { $(this).addClass('hover'); })
            .off('mouseleave.benefitHover', '.benefit-card')
            .on('mouseleave.benefitHover', '.benefit-card', function() { $(this).removeClass('hover'); });
    }
    
    // Initialize on DOM ready
    initBenefitSliders();
    bindBenefitHandlersOnce();
    
    // Re-initialize on AJAX content load (if needed)
    $(document).on('woocommerce_variation_has_changed', initBenefitSliders);

    // Elementor editor/preview compatibility: re-init when widgets render
    $(window).on('elementor/frontend/init', function() {
        initBenefitSliders();
        bindBenefitHandlersOnce();
        if (window.elementorFrontend && window.elementorFrontend.hooks) {
            window.elementorFrontend.hooks.addAction('frontend/element_ready/global', function($scope) {
                if ($scope && $scope.find && $scope.find('.benefit-card-grid').length) {
                    initBenefitSliders();
                }
            });
            window.elementorFrontend.hooks.addAction('frontend/element_ready/shortcode.default', function($scope) {
                if ($scope && $scope.find && $scope.find('.benefit-card-grid').length) {
                    initBenefitSliders();
                }
            });
        }
    });
});
