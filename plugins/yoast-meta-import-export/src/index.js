"use client"

import { render, useState } from "@wordpress/element"
import { __ } from "@wordpress/i18n"
import {
    Panel,
    PanelBody,
    PanelRow,
    Button,
    TabPanel,
    Notice,
    CheckboxControl,
    __experimentalText as Text,
    ProgressBar,
} from '@wordpress/components';

import FileUpload from './components/FileUpload';
import './style.css';

const YoastMetaApp = () => {
    const [loading, setLoading] = useState(false);
    const [notice, setNotice] = useState(null);
    const [progress, setProgress] = useState({ current: 0, total: 0 });
    const [csvData, setCsvData] = useState(null);
    const [exportOptions, setExportOptions] = useState({
        postTypes: ['post', 'page'], // Default selected
        taxonomies: ['category', 'post_tag'] // Default selected
    });
    const [resetKey, setResetKey] = useState(0);
    const [importOptions, setImportOptions] = useState({
        dryRun: false,
        force: false,
        clearExisting: false
    });

    // Converter states
    const [converterResetKey, setConverterResetKey] = useState(0);
    const [rawCsvContent, setRawCsvContent] = useState(null);
    const [conversionResult, setConversionResult] = useState(null);

    const showNotice = (type, message) => {
        setNotice({ type, message });
        setTimeout(() => setNotice(null), 5000);
    };

    const handleFileSelect = (fileContent, fileName) => {
        const data = parseCSV(fileContent);
        setCsvData(data);
        setProgress({ current: 0, total: data.length });

        // Show file info notice (don't auto-clear)
        setNotice({ type: 'info', message: `CSV "${fileName}" loaded successfully: ${data.length} entries ready for import.` });
    };

    const handleExport = async () => {
        setLoading(true);
        setNotice(null);

        try {
            const response = await fetch(window.yoastMetaIe.ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'yoast_meta_ie_export',
                    nonce: yoastMetaIe.nonce,
                    export_options: JSON.stringify(exportOptions),
                }),
            });

            if (response.ok) {
                const blob = await response.blob();
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'yoast-meta-export-' + new Date().toISOString().split('T')[0] + '.csv';
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                document.body.removeChild(a);
                showNotice('success', window.yoastMetaIe.translations.exportCompleted);
            } else {
                showNotice('error', window.yoastMetaIe.translations.exportFailed);
            }
        } catch (error) {
            showNotice('error', window.yoastMetaIe.translations.exportError + error.message);
        }

        setLoading(false);
    };

    const handleImport = async () => {
        if (!csvData) return;

        setLoading(true);
        setNotice(null); // Clear the CSV loaded notice when import starts

        const batchSize = 10;
        let updatedTotal = 0;
        let errors = [];
        let processedCount = 0;

        // Set initial progress
        setProgress({ current: 0, total: csvData.length });

        for (let i = 0; i < csvData.length; i += batchSize) {
            const batch = csvData.slice(i, i + batchSize);

            try {
                const response = await fetch(window.yoastMetaIe.ajaxUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'yoast_meta_ie_import_batch',
                        nonce: yoastMetaIe.nonce,
                        batch: JSON.stringify(batch),
                        options: JSON.stringify(importOptions),
                    }),
                });

                const result = await response.json();

                if (result.success) {
                    if (!result.data.dry_run) {
                        updatedTotal += result.data.updated;
                    }
                    if (result.data.errors.length > 0) {
                        errors = errors.concat(result.data.errors);
                    }
                } else {
                    errors.push(result.data);
                }
            } catch (error) {
                errors.push(error.message);
            }

            // Update progress
            processedCount += batch.length;
            setProgress({ current: processedCount, total: csvData.length });

            // Add delay between batches to avoid rate limits (1000ms)
            if (i + batchSize < csvData.length) {
                await new Promise(resolve => setTimeout(resolve, 1000));
            }
        }

        setLoading(false);

        if (errors.length > 0) {
            showNotice('warning', window.yoastMetaIe.translations.importCompletedWithErrors + errors.join(', '));
        } else {
            // Successful completion - reset the form to initial state
            setCsvData(null);
            setProgress({ current: 0, total: 0 });
            setNotice(null); // Clear any existing notices
            setResetKey(prev => prev + 1); // Reset file upload component
            setImportOptions({ // Reset import options to defaults
                dryRun: false,
                force: false,
                clearExisting: false
            });

            if (importOptions.dryRun) {
                showNotice('info', window.yoastMetaIe.translations.dryRunCompleted + updatedTotal + window.yoastMetaIe.translations.postsTermsWouldBeUpdated);
            } else {
                showNotice('success', window.yoastMetaIe.translations.importCompleted + updatedTotal + window.yoastMetaIe.translations.postsTerms);
            }
        }
    };

    const parseCSV = (csvText) => {
        const lines = csvText.trim().split('\n');
        const result = [];

        for (let line of lines) {
            const row = [];
            let current = '';
            let inQuotes = false;

            for (let i = 0; i < line.length; i++) {
                const char = line[i];
                if (char === '"') {
                    if (inQuotes && i + 1 < line.length && line[i + 1] === '"') {
                        current += '"';
                        i++;
                    } else {
                        inQuotes = !inQuotes;
                    }
                } else if (char === ',' && !inQuotes) {
                    row.push(current);
                    current = '';
                } else {
                    current += char;
                }
            }
            row.push(current);
            result.push(row);
        }

        const headers = result[0].map(h => h.replace(/"/g, '').trim());
        const data = result.slice(1).map(row => {
            const obj = {};
            headers.forEach((h, i) => {
                obj[h.toLowerCase().replace(/ /g, '_').replace(/\//g, '_')] = row[i] ? row[i].replace(/"/g, '').trim() : '';
            });
            return obj;
        });

        return data;
    };

    // Converter functions
    const handleConverterFileSelect = (fileContent, fileName) => {
        setRawCsvContent(fileContent);
        setConversionResult(null);
        setNotice({ type: 'info', message: `CSV "${fileName}" loaded. Click "Convert CSV" to process.` });
    };

    const handleConvertCsv = async () => {
        if (!rawCsvContent) return;

        setLoading(true);
        setNotice(null);

        try {
            const response = await fetch(window.yoastMetaIe.ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'yoast_meta_ie_convert_csv',
                    nonce: yoastMetaIe.nonce,
                    csv_content: rawCsvContent,
                }),
            });

            const result = await response.json();

            if (result.success && result.data.success) {
                setConversionResult(result.data);
                const stats = result.data.stats;
                showNotice('success', `Conversion complete! ${stats.converted} entries converted, ${stats.errors} errors, ${stats.skipped} skipped.`);
            } else {
                const errorMsg = result.data?.error || result.data || 'Unknown error';
                showNotice('error', 'Conversion failed: ' + errorMsg);
            }
        } catch (error) {
            showNotice('error', 'Conversion error: ' + error.message);
        }

        setLoading(false);
    };

    const handleDownloadConverted = () => {
        if (!conversionResult?.csv_output) return;

        const blob = new Blob([conversionResult.csv_output], { type: 'text/csv;charset=utf-8;' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'converted-yoast-import-' + new Date().toISOString().split('T')[0] + '.csv';
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        document.body.removeChild(a);
    };

    const handleResetConverter = () => {
        setRawCsvContent(null);
        setConversionResult(null);
        setConverterResetKey(prev => prev + 1);
        setNotice(null);
    };
    const tabs = [
        {
            name: 'export',
            title: window.yoastMetaIe.translations.export,
            content: (
                <Panel>
                    <PanelBody>
                        <PanelRow>
                            <h4>
                                {window.yoastMetaIe.translations.exportDescription}
                            </h4>
                        </PanelRow>
                        <PanelRow>
                            <Text><strong>Select Post Types to Export:</strong></Text>
                        </PanelRow>
                        <div className="yoast-meta-ie-post-type-selection">
                            {window.yoastMetaIe.postTypes && window.yoastMetaIe.postTypes.map(postType => (
                                <PanelRow key={postType.name}>
                                    <CheckboxControl
                                        label={`${postType.label} (${postType.name})`}
                                        checked={exportOptions.postTypes.includes(postType.name)}
                                        onChange={(checked) => {
                                            if (checked) {
                                                setExportOptions({
                                                    ...exportOptions,
                                                    postTypes: [...exportOptions.postTypes, postType.name]
                                                });
                                            } else {
                                                setExportOptions({
                                                    ...exportOptions,
                                                    postTypes: exportOptions.postTypes.filter(type => type !== postType.name)
                                                });
                                            }
                                        }}
                                    />
                                </PanelRow>
                            ))}
                        </div>
                        <PanelRow>
                            <Text><strong>Select Taxonomies to Export:</strong></Text>
                        </PanelRow>
                        <div className="yoast-meta-ie-taxonomy-selection">
                            {window.yoastMetaIe.taxonomies && window.yoastMetaIe.taxonomies.map(taxonomy => (
                                <PanelRow key={taxonomy.name}>
                                    <CheckboxControl
                                        label={`${taxonomy.label} (${taxonomy.name})`}
                                        checked={exportOptions.taxonomies.includes(taxonomy.name)}
                                        onChange={(checked) => {
                                            if (checked) {
                                                setExportOptions({
                                                    ...exportOptions,
                                                    taxonomies: [...exportOptions.taxonomies, taxonomy.name]
                                                });
                                            } else {
                                                setExportOptions({
                                                    ...exportOptions,
                                                    taxonomies: exportOptions.taxonomies.filter(type => type !== taxonomy.name)
                                                });
                                            }
                                        }}
                                    />
                                </PanelRow>
                            ))}
                        </div>
                        <PanelRow>
                            <Button
                                isPrimary
                                onClick={handleExport}
                                disabled={loading || (exportOptions.postTypes.length === 0 && exportOptions.taxonomies.length === 0)}
                            >
                                {loading ? window.yoastMetaIe.translations.exporting : window.yoastMetaIe.translations.exportCsv}
                            </Button>
                        </PanelRow>
                    </PanelBody>
                </Panel>
            ),
        },
        {
            name: 'import',
            title: window.yoastMetaIe.translations.import,
            content: (
                <Panel>
                    <PanelBody>
                        <PanelRow>
                            <h4>
                                {window.yoastMetaIe.translations.importDescription}
                            </h4>
                        </PanelRow>
                        <PanelRow>
                            <CheckboxControl
                                label={window.yoastMetaIe.translations.dryRun}
                                checked={importOptions.dryRun}
                                onChange={(checked) => setImportOptions({ ...importOptions, dryRun: checked })}
                            />
                        </PanelRow>
                        <PanelRow>
                            <CheckboxControl
                                label={window.yoastMetaIe.translations.forceImport}
                                checked={importOptions.force}
                                onChange={(checked) => setImportOptions({ ...importOptions, force: checked })}
                            />
                        </PanelRow>
                        <PanelRow>
                            <CheckboxControl
                                label={window.yoastMetaIe.translations.clearExisting}
                                checked={importOptions.clearExisting}
                                onChange={(checked) => setImportOptions({ ...importOptions, clearExisting: checked })}
                            />
                        </PanelRow>
                        <PanelRow>
                            <FileUpload key={resetKey} onFileSelect={handleFileSelect} disabled={loading} />
                        </PanelRow>
                        {csvData && (
                            <PanelRow>
                                <Text>
                                    <strong>{csvData.length} entries ready for import</strong>
                                </Text>
                            </PanelRow>
                        )}
                        {csvData && (
                            <PanelRow>
                                <Button
                                    isPrimary
                                    onClick={handleImport}
                                    disabled={loading}
                                >
                                    {loading ? (window.yoastMetaIe.translations.processing || 'Processing...') : (window.yoastMetaIe.translations.proceed || 'Proceed with Import')}
                                </Button>
                            </PanelRow>
                        )}
                    </PanelBody>
                </Panel>
            ),
        },
        {
            name: 'convert',
            title: 'Convert CSV',
            content: (
                <Panel>
                    <PanelBody>
                        <PanelRow>
                            <h4>
                                Convert URL-based SEO CSV to Import Format
                            </h4>
                        </PanelRow>
                        <PanelRow>
                            <Text>
                                Upload a CSV with columns: <strong>Groups, URL, Meta Status, Meta Description</strong>.
                                This tool will convert URLs to WordPress IDs for import.
                            </Text>
                        </PanelRow>
                        <PanelRow>
                            <FileUpload key={converterResetKey} onFileSelect={handleConverterFileSelect} disabled={loading} />
                        </PanelRow>
                        {rawCsvContent && !conversionResult && (
                            <PanelRow>
                                <Button
                                    isPrimary
                                    onClick={handleConvertCsv}
                                    disabled={loading}
                                >
                                    {loading ? 'Converting...' : 'Convert CSV'}
                                </Button>
                            </PanelRow>
                        )}
                        {conversionResult && (
                            <>
                                <PanelRow>
                                    <div style={{ background: '#f0f0f0', padding: '15px', borderRadius: '4px', width: '100%' }}>
                                        <Text><strong>Conversion Results:</strong></Text>
                                        <ul style={{ margin: '10px 0', paddingLeft: '20px' }}>
                                            <li>Total Rows: {conversionResult.stats.total_rows}</li>
                                            <li>Successfully Converted: {conversionResult.stats.converted}</li>
                                            <li>Errors: {conversionResult.stats.errors}</li>
                                            <li>Skipped: {conversionResult.stats.skipped}</li>
                                        </ul>
                                    </div>
                                </PanelRow>
                                <PanelRow>
                                    <Button
                                        isPrimary
                                        onClick={handleDownloadConverted}
                                        disabled={loading}
                                        style={{ marginRight: '10px' }}
                                    >
                                        Download Converted CSV
                                    </Button>
                                    <Button
                                        isSecondary
                                        onClick={handleResetConverter}
                                    >
                                        Reset
                                    </Button>
                                </PanelRow>
                                {conversionResult.errors.length > 0 && (
                                    <PanelRow>
                                        <div style={{ background: '#fef7f1', padding: '15px', borderRadius: '4px', width: '100%', maxHeight: '200px', overflow: 'auto' }}>
                                            <Text><strong>Errors ({conversionResult.errors.length}):</strong></Text>
                                            <ul style={{ margin: '10px 0', paddingLeft: '20px', fontSize: '12px' }}>
                                                {conversionResult.errors.slice(0, 50).map((error, idx) => (
                                                    <li key={idx}>{error}</li>
                                                ))}
                                                {conversionResult.errors.length > 50 && (
                                                    <li>... and {conversionResult.errors.length - 50} more errors</li>
                                                )}
                                            </ul>
                                        </div>
                                    </PanelRow>
                                )}
                            </>
                        )}
                    </PanelBody>
                </Panel>
            ),
        },
    ];

    return (
        <div className="yoast-meta-ie-app">
            {notice && (
                <Notice status={notice.type} isDismissible={false}>
                    {notice.message}
                </Notice>
            )}
            {progress.total > 0 && loading && (
                <Notice status="info" isDismissible={false}>
                    <div style={{ marginBottom: '10px' }}>
                        <strong>{window.yoastMetaIe.translations.processing || 'Processing'}: {progress.current} / {progress.total} items</strong>
                    </div>
                    <ProgressBar
                        value={(progress.current / progress.total) * 100}
                        color="#007cba"
                    />
                </Notice>
            )}
            <TabPanel
                className="yoast-meta-ie-tabs"
                tabs={tabs}
            >
                {(tab) => tab.content}
            </TabPanel>
        </div>
    );
};

// Initialize the React app when DOM is ready
wp.domReady(() => {
    const container = document.getElementById('yoast-meta-ie-app');
    if (container) {
        render(<YoastMetaApp />, container);
    }
});