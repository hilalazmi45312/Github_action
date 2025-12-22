<div class="wrap">
    <h1>Export Product URLs</h1>
    <p>Upload a CSV file containing product names (first column) to export their URLs.</p>
    
    <form method="post" action="" enctype="multipart/form-data">
        <?php wp_nonce_field('senheng_export_urls_action', 'senheng_export_urls_nonce'); ?>
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="product_csv">Upload CSV File</label>
                </th>
                <td>
                    <input type="file" name="product_csv" id="product_csv" accept=".csv">
                    <p class="description">
                        <strong>Format Requirement:</strong> The <strong>first column</strong> must contain the Product Names.<br>
                        Example content:<br>
                        <code>(Bundle Promo) LG 10.5KG Washer...</code><br>
                        <code>Samsung 13KG Washer...</code>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="product_names">Or Paste Product Names</label>
                </th>
                <td>
                    <textarea name="product_names" id="product_names" rows="10" cols="80" class="large-text code" placeholder="(Bundle Promo) LG 10.5KG Washer LG-FV1450S4W and 10KG Dryer LG-RH10VHP2W..."></textarea>
                    <p class="description">Enter each product name on a new line (optional if CSV is uploaded).</p>
                </td>
            </tr>
        </table>
        
        <p class="submit">
            <input type="submit" name="senheng_export_urls" id="submit" class="button button-primary" value="Export CSV">
        </p>
    </form>
</div>
