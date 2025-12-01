<?php
/** @var array $data */
$cards = isset($data['cards']) && is_array($data['cards']) ? $data['cards'] : [];
?>

<div class="benefit-card-grid">
    <!-- Slider container for mobile/tablet -->
    <div class="benefit-slider-container">
        <?php foreach ($cards as $card): ?>
            <?php
            $cardClasses = ['benefit-card'];
            if (!empty($card['is_installment'])) {
                $cardClasses[] = 'installment-details';
            }
            if (!empty($card['is_whatsapp'])) {
                $cardClasses[] = 'whatsapp-card';
            }
            ?>
            <div class="<?php echo esc_attr(implode(' ', $cardClasses)); ?>" 
                 <?php echo !empty($card['is_installment']) ? 'id="installment-details"' : ''; ?>
                 <?php echo !empty($card['is_whatsapp']) ? 'data-whatsapp-number="' . esc_attr($card['whatsapp_number']) . '" data-predefined-text="' . esc_attr($card['predefined_text']) . '"' : ''; ?>>
                <div class="benefit-card-icon">
                    <img src="<?php echo esc_url($card['icon']); ?>" alt="">
                </div>
                <div class="benefit-card-title"><?php echo wp_kses_post($card['title']); ?></div>
                <?php if (!empty($card['subtitle'])): ?>
                    <div class="benefit-card-subtitle"><?php echo wp_kses_post($card['subtitle']); ?></div>
                <?php endif; ?>

                <!-- <div class="benefit-card-cta" aria-label="More details">
                    <span class="benefit-card-cta-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" class="IconControl_centeredIcon___gj73"><path d="M16 8.5h-4.5V4a1.5 1.5 0 0 0-3 0v4.5H4a1.5 1.5 0 0 0 0 3h4.5V16a1.5 1.5 0 0 0 3 0v-4.5H16a1.5 1.5 0 0 0 0-3z"></path></svg>
                    </span>
                </div> -->
            </div>
        <?php endforeach; ?>
    </div>
    
    <!-- Navigation arrows -->
    <div class="benefit-slider-arrows">
        <button class="benefit-arrow benefit-arrow-prev" aria-label="Previous">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 36 36" class="IconControl_paddleNavIcons__ZzEXW"><path d="M20 25c-.384 0-.768-.146-1.06-.44l-5.5-5.5a1.5 1.5 0 0 1 0-2.12l5.5-5.5a1.5 1.5 0 1 1 2.12 2.12L16.622 18l4.44 4.44A1.5 1.5 0 0 1 20 25z"></path></svg>
        </button>
        <button class="benefit-arrow benefit-arrow-next" aria-label="Next">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 36 36" class="IconControl_paddleNavIcons__ZzEXW"><path d="M22.56 16.938l-5.508-5.5a1.493 1.493 0 0 0-2.116.003 1.502 1.502 0 0 0 .004 2.121L19.384 18l-4.444 4.438A1.502 1.502 0 0 0 15.996 25c.382 0 .764-.145 1.056-.438l5.508-5.5a1.502 1.502 0 0 0 0-2.125z"></path></svg>
        </button>
    </div>
    
    <!-- Navigation dots -->
    <div class="benefit-slider-nav"></div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle WhatsApp card clicks
    document.querySelectorAll('.whatsapp-card').forEach(function(card) {
        card.addEventListener('click', function() {
            const whatsappNumber = this.getAttribute('data-whatsapp-number');
            const predefinedText = this.getAttribute('data-predefined-text');
            
            if (whatsappNumber && predefinedText) {
                // Replace shortcodes with actual values
                let message = predefinedText;
                let pageTitle = document.title.replace(' - Senheng', ''); // Remove " - Senheng" suffix
                message = message.replace('[senheng_page_title]', pageTitle);
                message = message.replace('[senheng_page_url]', window.location.origin + window.location.pathname);
                
                // Encode the message for WhatsApp URL
                const encodedMessage = encodeURIComponent(message);
                const whatsappUrl = `https://wa.me/${whatsappNumber.replace(/[^0-9]/g, '')}?text=${encodedMessage}`;
                
                // Open WhatsApp
                window.open(whatsappUrl, '_blank');
            }
        });
    });
});
</script>


