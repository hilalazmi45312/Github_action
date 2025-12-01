import './frontend.scss';
document.addEventListener('DOMContentLoaded', (event) => {
    const btn = document.querySelectorAll( '.bwfop-poup-button-wrap .bwf-btn-popup');
    if ( btn ) {
        btn.forEach( button => {
            button.addEventListener( 'click', (e) => {
                button.parentElement.nextElementSibling.classList.add('show_popup_form');
                document.body.style.overflow = 'hidden';
            })
            let closeButton = button.closest('.bwfop-popup-form-container').querySelector( '.bwf_pp_close' );
            closeButton.addEventListener( 'click', () => {
                button.closest('.bwfop-popup-form-container').querySelector('.bwf_pp_overlay').classList.remove('show_popup_form');
                if (document.body.style.removeProperty) {
                    document.body.style.removeProperty('overflow');
                } else {
                    document.body.style.removeAttribute('overflow');
                }
            });
        })
    }
});