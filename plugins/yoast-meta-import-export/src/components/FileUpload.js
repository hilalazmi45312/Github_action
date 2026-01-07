import { useState, useRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Button, Notice } from '@wordpress/components';

const FileUpload = ({ onFileSelect, accept = '.csv', label, maxSize = 10 * 1024 * 1024, disabled = false }) => {
    const [dragActive, setDragActive] = useState(false);
    const [error, setError] = useState('');
    const [fileName, setFileName] = useState('');
    const fileInputRef = useRef();

    const handleFile = (file) => {
        setError('');

        // Validate file type
        if (!file.name.toLowerCase().endsWith('.csv')) {
            setError(window.yoastMetaIe.translations.pleaseSelectCsv);
            return;
        }

        // Validate file size
        if (file.size > maxSize) {
            setError(window.yoastMetaIe.translations.fileTooLarge);
            return;
        }

        // Read file content
        const reader = new FileReader();
        reader.onload = (e) => {
            try {
                const content = e.target.result;
                setFileName(file.name);
                onFileSelect(content, file.name);
            } catch (err) {
                setError(window.yoastMetaIe.translations.failedToRead);
            }
        };

        reader.onerror = () => {
            setError(window.yoastMetaIe.translations.failedToRead);
        };

        reader.readAsText(file);
    };

    const handleDrop = (e) => {
        e.preventDefault();
        e.stopPropagation();
        setDragActive(false);

        const files = e.dataTransfer.files;
        if (files && files[0]) {
            handleFile(files[0]);
        }
    };

    const handleDragOver = (e) => {
        e.preventDefault();
        e.stopPropagation();
        setDragActive(true);
    };

    const handleDragLeave = (e) => {
        e.preventDefault();
        e.stopPropagation();
        setDragActive(false);
    };

    const handleInputChange = (e) => {
        const files = e.target.files;
        if (files && files[0]) {
            handleFile(files[0]);
        }
    };

    const openFileDialog = () => {
        if (!disabled) {
            fileInputRef.current?.click();
        }
    };

    const clearFile = () => {
        setFileName('');
        setError('');
        onFileSelect('', '');
        if (fileInputRef.current) {
            fileInputRef.current.value = '';
        }
    };

    return (
        <div className="yoast-meta-ie-file-upload">
            {label && <label className="yoast-meta-ie-file-upload-label">{label}</label>}

            <div
                className={`yoast-meta-ie-file-drop-zone ${dragActive ? 'drag-active' : ''} ${fileName ? 'has-file' : ''} ${disabled ? 'disabled' : ''}`}
                onDrop={handleDrop}
                onDragOver={handleDragOver}
                onDragLeave={handleDragLeave}
                onClick={openFileDialog}
            >
                <input
                    ref={fileInputRef}
                    type="file"
                    accept={accept}
                    onChange={handleInputChange}
                    style={{ display: 'none' }}
                    disabled={disabled}
                />

                {fileName ? (
                    <div className="yoast-meta-ie-file-selected">
                        <div className="yoast-meta-ie-file-name">
                            <strong>{window.yoastMetaIe.translations.selectedFile}</strong> {fileName}
                        </div>
                        <Button
                            variant="link"
                            onClick={(e) => {
                                e.stopPropagation();
                                clearFile();
                            }}
                            className="yoast-meta-ie-clear-file"
                            disabled={disabled}
                        >
                            {window.yoastMetaIe.translations.clear}
                        </Button>
                    </div>
                ) : (
                    <div className="yoast-meta-ie-file-placeholder">
                        <div className="yoast-meta-ie-upload-icon">
                            📊
                        </div>
                        <div className="yoast-meta-ie-upload-text">
                            {window.yoastMetaIe.translations.dropCsvFile}
                        </div>
                        <div className="yoast-meta-ie-upload-hint">
                            {window.yoastMetaIe.translations.maxFileSize}
                        </div>
                    </div>
                )}
            </div>

            {error && (
                <Notice status="error" isDismissible={false}>
                    {error}
                </Notice>
            )}
        </div>
    );
};

export default FileUpload;