function popupSubCat(termId) {
    const slider = jQuery(".ikea-category-slider");
    const clickedItem = jQuery(`.ikea-cat-item[data-term-id="${termId}"]`);
    const clickedName = clickedItem.find("span").text();
    const panel = jQuery(".ikea-mega-panel");
    const ikeaSubcatData = JSON.parse(document.getElementById("ikea-subcat-data").textContent);

    // gray out all
    jQuery(".ikea-cat-item").addClass("grayscale");
    // highlight the clicked one
    clickedItem.removeClass("grayscale");

    // Start with a loading state
    let html = `
        <div class="ikea-mega-content">
            <div class="ikea-mega-left">
                <h3>Explore ${clickedName}</h3>
                <ul>
                    <li>Loading...</li>
                </ul>
            </div>
        </div>
    `;
    panel.html(html);

    // calculate position
    const itemPosition = clickedItem.position();
    let panelTop = itemPosition.top + clickedItem.outerHeight() + 5;
    let panelLeft = itemPosition.left;

    const panelWidth = panel.outerWidth();
    const sliderWidth = slider.width();

    if (panelLeft + panelWidth > sliderWidth) {
        panelLeft = sliderWidth - panelWidth - 10;
        if (panelLeft < 0) panelLeft = 0;
    }

    panel.css({
        top: panelTop + "px",
        left: panelLeft + "px"
    }).fadeIn();

    const subcats = ikeaSubcatData[termId] || [];

    if (subcats.length > 0) {
        let dynamicHtml = `
        <div class="ikea-mega-content">
            <div class="ikea-mega-left">
                <h3>Explore ${clickedName}</h3>
                <ul>
    `;
        subcats.forEach(function (subcat) {
            dynamicHtml += `<li><a href="${subcat.link}">${subcat.name}</a></li>`;
        });
        dynamicHtml += `
                </ul>
            </div>
        </div>
    `;
        panel.html(dynamicHtml);
    } else {
        panel.html('No subcategories found.');
    }


    // Now run AJAX
    // jQuery.ajax({
    //     url: ikeaMenuAjax.ajax_url,
    //     type: 'POST',
    //     data: {
    //         action: 'get_subcategories',
    //         term_id: termId
    //     },
    //     success: function (response) {
    //         if (response.success) {
    //             let dynamicHtml = `
    //                 <div class="ikea-mega-content">
    //                     <div class="ikea-mega-left">
    //                         <h3>Explore ${clickedName}</h3>
    //                         <ul>
    //             `;
    //             response.data.forEach(function (subcat) {
    //                 dynamicHtml += `<li><a href="${subcat.link}">${subcat.name}</a></li>`;
    //             });
    //             dynamicHtml += `
    //                         </ul>
    //                     </div>
    //                 </div>
    //             `;
    //             panel.html(dynamicHtml);
    //         } else {
    //             // panel.fadeOut();
    //             panel.html('No subcategories found.');
    //         }
    //     },
    //     error: function () {
    //         alert('Error fetching subcategories.');
    //         panel.fadeOut();
    //     }
    // });
}

// close panel if clicking outside
jQuery(document).on('click', function (e) {
    if (!jQuery(e.target).closest('.ikea-cat-item, .ikea-mega-panel').length) {
        jQuery(".ikea-mega-panel").fadeOut();
        jQuery(".ikea-cat-item").removeClass("grayscale");
    }
});
