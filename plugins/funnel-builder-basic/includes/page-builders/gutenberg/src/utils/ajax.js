const ajaxCall = (props) => {

    if (true !== this.ajax) {
        return;
    }
    let { attributes, widget_slug } = props;

    const response = fetch(ajaxurl + '?action=' + widget_slug + '_gutenberg&bwf_post_id=' + bwfop_funnels_data.post_id, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(attributes) // body data type must match "Content-Type" header
    });

    response.then((response) => {
        // The API call was successful!
        return response.text();
    }).then((html) => {
        this.setState({component_html: html});
        setTimeout(() => {
            this.ajax_after_work();
        }, 600);
    }).catch((err) => {
        // There was an error
        console.warn('Something went wrong.', err);
    });
}