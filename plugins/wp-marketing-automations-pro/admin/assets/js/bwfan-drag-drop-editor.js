(function ($) {
    class BWFAN_Email_Editor {
        formatAmount = null;
        productToolEvent = null;

        constructor() {
            this.registerEvents();
            this.initIziModals();
            this.token = bwfan_automation_drag_drop.token;

            this.initCurrency();
            this.initProductToolModal();
        }

        initCurrency() {
            if(  typeof wc == 'undefined' || !wc || !wc.currency || !bwfan_automation_drag_drop.currency ) {
                return;
            }

            this.formatAmount = wc.currency.default(bwfan_automation_drag_drop.currency).formatAmount;
        }

        initIziModals() {
            var that = this;

            /** iziModal Action For Single Autonami */
            if ($('#modal-autonami-product-tool').length > 0) {
                $('#modal-autonami-product-tool').iziModal({
                    width: 786,
                    radius: 5,
                    padding: 0,
                    background: 'rgb(255, 255, 255)',
                    overlayColor: 'rgba(0, 0, 0, 0.6)',
                    zindex: 9999999,
                    transitionIn: 'none',
                    onOpening: function () {
                        that.renderProductToolModal();
                    }
                });
            }
        }

        registerEvents() {
            const self = this;
            this.registerListeners();
            const body = $('body');
            body.on('click', '#bwf-launch-editor-modal', (e) => {
                self.openModal();
                e.preventDefault();
            });
        }

        getCurrentDesign() {
            const design = $('#bwfan-editor-design').val();
            return !!design ? design : '';
        }

        setDesign(body, json = '') {
            !!json && $('#bwfan-editor-design').val(json);
            !!body && $('#bwfan-editor-body').val(body);
        }

        closeModal() {
            $('body').removeClass('bwf-c-modal-open');
            $('#bwf-modal-editor').removeClass('active');
            $('#bwf_email_editor_frame').remove();
        }

        openModal() {
            $('body').addClass('bwf-c-modal-open');
            $('#bwf-modal-editor').addClass('active');
            const iframeSrc = bwfan_automation_drag_drop.iframe_src.replace(/([^:]\/)\/+/g, "$1");
            $('#bwf-modal-editor-inner').append('<iframe style="width:100%;height:100%" src="'+iframeSrc+'" id="bwf_email_editor_frame" ></iframe>');
            setTimeout(function () {
                $('#bwf-c-editor-modal-close').show();
                $('#bwf-c-editor-modal-merge-tags').show();
            }, 1000);
        }

        openMergeTagsModal() {
            $('#modal-show-merge-tags').iziModal('open');
        }

        openLinkTriggerModal() {
            $('#modal-autonami-link-trigger-selector').iziModal('open');
        }

        registerListeners() {
            const self = this;
            window.addEventListener('message', function (event) {
                if (
                    -1 === event.origin.indexOf('app.getautonami.com') ||
                    !event.data ||
                    !event.data.action ||
                    event.data.token !== self.token
                ) {
                    return;
                }

                switch (event.data.action) {
                    case 'get_design':
                        self.getDesignListener(event);
                        break;
                    case 'save_design':
                        self.saveDesignListener(event);
                        break;
                    case 'send_test_email':
                        self.sendTestEmailListener(event);
                        break;
                    case 'wp_image_upload':
                        self.getWPMediaURL(event);
                        break;
                    case 'open_merge_tags':
                        self.openMergeTagsModal();
                        break;
                    case 'open_link_trigger':
                        self.openLinkTriggerModal();
                        break;
                    case 'close_editor':
                        self.closeModal();
                        break;
                    case 'select_product_for_tool':
                        self.productToolEvent = event;
                        self.selectProduct();
                        break;
                }
            });
        }

        getDesignListener(event) {
            const jsonContent = this.getCurrentDesign();

            this.sendPostMessage('get_design', {
                gotDesign: true,
                json: jsonContent,
                subject: '',
                mergeTags: {},
                enableLinkTrigger: true,
            }, event.origin);
        }

        saveDesignListener(event) {
            /** Check for required data to send test email */
            const {body, design} = event.data.data;
            if (!body || !design) {
                return;
            }

            this.setDesign(body, design);
            this.saveAutomation();
            this.sendPostMessage('save_design', {
                isSaved: true,
            }, event.origin);
        }

        saveAutomation() {
            $('.wr_tw_save .wr-form-btn').trigger('click');
        }

        sendPostMessage(action, data, origin) {
            const iframe = document.getElementById('bwf_email_editor_frame');

            !!iframe && iframe.contentWindow.postMessage(
                {
                    token: this.token,
                    action,
                    data,
                },
                origin
            );
        }

        sendTestEmailListener(event) {
            /** Check for required data to send test email */
            const {email, body, design} = event.data.data;
            if (!email || !body || !design) {
                return;
            }

            this.setDesign(body, design);
            this.sendTestEmail(email, event.origin);
        }

        sendTestEmail(email, origin) {
            const self = this;
            if ('' === email) {
                return;
            }

            let form_data = $(
                '#bwfan-actions-form-container'
            ).bwfan_serializeAndEncode();
            form_data = bwfan_deserialize_obj(form_data);

            const group_id = $('.bwfan-selected-action').attr(
                'data-group-id'
            );
            const data_to_send = form_data.bwfan[group_id];

            data_to_send.source = BWFAN_Auto.uiDataDetail.trigger.source;
            data_to_send.event = BWFAN_Auto.uiDataDetail.trigger.event;
            data_to_send._wpnonce = bwfanParams.ajax_nonce;
            data_to_send.automation_id =
                bwfan_automation_data.automation_id;
            data_to_send.email = email;
            const ajax = new bwf_ajax();
            ajax.ajax('test_email', data_to_send);

            ajax.success = (resp) => {
                if (resp.status == true) {
                    self.sendPostMessage('send_test_email', {
                        isSent: true,
                        showSnackbar: true
                    }, origin);
                } else {
                    self.sendPostMessage('send_test_email', {
                        isSent: false,
                        showSnackbar: true
                    }, origin);
                }
            };
        }

        getWPMediaURL(event) {
            const self = this;
            if (!wp || !wp.media) {
                return '';
            }

            if (this.mediaLib) {
                this.mediaLib.open();
                return;
            }

            this.mediaLib = wp.media({
                title: 'Select or Upload Media',
                button: {
                    text: 'Select Image',
                },
                multiple: false,
            });

            this.mediaLib.on('select', function () {
                // Get media attachment details from the frame state
                const attachment = self.mediaLib
                    .state()
                    .get('selection')
                    .first()
                    .toJSON();

                self.sendPostMessage('wp_image_upload', {
                    isUploaded: true,
                    imageURL: attachment.url,
                }, event.origin);
            });

            // Finally, open the modal on click
            this.mediaLib.open();
        };

        //async uploadImageListener(event) {
        //    const {image} = event.data.data;
        //    if (!image) {
        //        return;
        //    }
        //
        //    const formData = new FormData();
        //    formData.append('image', image);
        //
        //    const response = await window.fetch('<?php //echo rest_url( '/autonami-admin/upload-image' ) ?>//', {
        //        headers: {
        //            'X-WP-Nonce': wpApiSettings.nonce
        //        },
        //        method: 'POST',
        //        body: formData
        //    });
        //
        //    const jsonResult = await response.json();
        //    if (parseInt(jsonResult.code) === 200 && !!jsonResult.result) {
        //        this.sendPostMessage('upload_media', {
        //            isUploaded: true,
        //            imageURL: jsonResult.result,
        //        }, event.origin);
        //    }
        //
        //    this.sendPostMessage('upload_media', {
        //        isUploaded: false,
        //        imageURL: '',
        //    }, event.origin);
        //}

        selectProduct() {
            $('#modal-autonami-product-tool').iziModal('open');
        }

        onProductSelect( product ) {
            this.sendPostMessage('select_product_for_tool', {
                product: {
                    ...product,
                    formattedPrice: this.formatAmount( product.price ),
                },
            }, this.productToolEvent.origin);

            $('#modal-autonami-product-tool').iziModal('close');
        }

        initProductToolModal() {
            let search = '';
            let limit = 10;
            let offset = 0;

            const that = this;

            /** Pagination Clicks */
            $(document).on('click', '#bwf-c-product-modal-prev', function() {
                offset = offset - limit;
                that.renderProductToolModal( search, limit, offset );
            });

            $('body').on('click', '#bwf-c-product-modal-next', function() {
                offset = offset + limit;
                that.renderProductToolModal( search, limit, offset );
            });

            /** Search event */
            const debouncedSearch = _.debounce(function() {
                that.renderProductToolModal( search, limit, offset );
            }, 700);

            $(document).on('input', '#bwf-c-product-tool-search input', function(e) {
                search = e.target.value;
                debouncedSearch();
            });

            /** On select */
            $(document).on('click', '#bwf-c-product-tool-select', function(e) {
                let productData = $(this).data('product');
                productData = JSON.parse( decodeURIComponent( productData ) );
                that.onProductSelect( productData );
            });
        }

        renderProductsLoading() {
            let html = `<table class="bwf-c-product-modal-table"><tbody>`;
            Array.from( Array( 10 ).keys() ).map( ( product, index ) => {
                html += `<tr>
                    <td>
                        <div class="bwf-c-product-modal-row">
                            <p class="bwf-placeholder-loader is-image"></p>
                            <div class="bwf-c-product-modal-row-info">
                                <span class="bwf-tag-label">
                                    <p class="bwf-placeholder-loader" style="width: 70px;"></p>
                                </span>
                                <span class="bwf-tag-slug">
                                    <p class="bwf-placeholder-loader" style="height: 10px;"></p>
                                </span>
                            </div>
                        </div>
                    </td>
                    <td class="bwf-button-cell">
                        <p class="bwf-placeholder-loader" style="height: 32px; width: 80px;"></p>
                    </td>
                </tr>`;

            } );

            html += `</tbody></table>`;

            $('#modal-autonami-product-tool-content').html(html);
        }

        async renderProductToolModal( search = '', limit = 10, offset = 0 ) {
            this.renderProductsLoading();

            const data = await this.getProducts( search, limit, offset );
            if( !! data && !! data.error ) {
                return `<div>${ data.error }</div>`;
            }

            const { products, totalCount } = data;

            const html = await this.getProductsHTML( products );
            const pagination = this.getProductsPaginationHTML( limit, offset, totalCount );
            $('#modal-autonami-product-tool-content').html( html + pagination );
        }

        getProductsPaginationHTML( limit = 10, offset = 0, total = 0 ) {
            const page = ( offset / limit ) + 1;
            const totalPages = Math.ceil( total / limit );

            const nextPossible = ( totalPages - page ) > 0;
            const prevPossible = page > 1;

            const html = `<div class="bwf-c-product-modal-pagination">
                <div class="bwf-pagination-page-arrows">
                    <span class="bwf-pagination-page-arrows-label" role="status" aria-live="polite">Page ${ page } of ${ totalPages }</span>
                    <div class="bwf-pagination-page-arrows-buttons">
                        <button type="button" ${ ! prevPossible && 'disabled=""' } class="components-button bwf-pagination-link" aria-label="Previous Page" id="bwf-c-product-modal-prev">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" role="img" aria-hidden="true" focusable="false">
                                <path d="M14.6 7l-1.2-1L8 12l5.4 6 1.2-1-4.6-5z"></path>
                            </svg>
                        </button>
                        <button type="button" ${ ! nextPossible && 'disabled=""' } class="components-button bwf-pagination-link is-active" aria-label="Next Page" id="bwf-c-product-modal-next">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" role="img" aria-hidden="true" focusable="false">
                                <path d="M10.6 6L9.4 7l4.6 5-4.6 5 1.2 1 5.4-6z"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>`;

            return html;
        }

        async getProductsHTML( products = [] ) {
            let html = `<table class="bwf-c-product-modal-table"><tbody>`;
            products.map( ( product, index ) => {
                const price = `Price: ${ this.formatAmount( product.price ) }`;

                const stockQuantity = !! product.stock_quantity && `Stock: ${ product.stock_quantity }`;

                const stockStatus = product.stock_status;

                const description = [ price, stockQuantity, stockStatus ]
                    .filter( Boolean )
                    .join( ' | ' );

                const image = Array.isArray( product.images ) && product.images[ 0 ] ? product.images[ 0 ].src : ''
                const imageHTML = !!image ? `<img class="bwf-c-product-modal-image" src='${image}' />` : '';

                html += `<tr>
                    <td>
                        <div class="bwf-c-product-modal-row">
                            ${imageHTML}
                            <div class="bwf-c-product-modal-row-info">
                                <span class="bwf-tag-label">
                                    ${ product.name }${ ' ' }
                                    <span class="bwf-c-product-id">
                                        #${ product.id }
                                    </span>
                                </span>
                                <span class="bwf-tag-slug">
                                    ${ description }
                                </span>
                            </div>
                        </div>
                    </td>
                    <td class="bwf-button-cell">
                        <span id="bwf-c-product-tool-select" data-product="${ encodeURIComponent( JSON.stringify(product) ) }" class="bwf-tag-item-select">Select</span>
                    </td>
                </tr>`;

            } );

            html += `</tbody></table>`;

            return html;
        }

        async getProducts( search = '', limit = 10, offset = 0 ) {
            const query = {
                search,
                per_page: limit,
                page: parseInt( offset / limit ) + 1,
                orderby: 'popularity',
            };

            let response = '';
            try {
                response = await wp.apiFetch( {
                    path: wp.url.addQueryArgs( '/wc-analytics/products', query ),
                    parse: false,
                } );
            } catch ( error ) {
                return { error };
            }
        
            return {
                products: await response.json(),
                totalCount: parseInt( response.headers.get( 'x-wp-total' ), 10 ),
            };
        }
    }

    new BWFAN_Email_Editor();
})(jQuery);