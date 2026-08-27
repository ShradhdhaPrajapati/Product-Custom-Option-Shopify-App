<?php
$sets = $sets ?? [];
?>
<!DOCTYPE html>

<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Test Product Options Variant</title>
    <meta name="viewport" content="width=device-width">
    <meta name="shopify-api-key" content="{{ env($response['appId'] . '_API_KEY') }}" />
    <script src="https://unpkg.com/@shopify/app-bridge@3.7.9/umd/index.js"></script>
    <script src="{{ asset('js/jquery.min.js') }}"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.3/tinymce.min.js" referrerpolicy="origin"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<div class="container">
    <div class="toggle-container">
        <div class="toggle-label">
            <span>
                Enable App :
                <span class="status {{ $response['isAppEnable'] ? 'active' : 'in-activate' }}">
                    {{ $response['isAppEnable'] ? 'Active' : 'In-Activate' }}
                </span>
            </span>
            <div id="loader"></div>
            <label class="toggle-switch">
                <input type="checkbox" id="toggleApp" {{ $response['isAppEnable'] ? 'checked' : '' }}>
                <span class="slider"></span>
            </label>
        </div>
    </div>

    <div id="view-dashboard">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0 fw-bold">Option Sets</h5>
            <div>
                <button class="btn btn-primary-custom" onclick="showCreateForm(false)">
                    Create Option Set ▼
                </button>
            </div>
        </div>

        <div class="card">
            <div class="toolbar-container">
                <div class="tab-group">
                    <button class="tab-btn active" onclick="filterTable('all')">All</button>
                    <button class="tab-btn" onclick="filterTable('active')">Active</button>
                    <button class="tab-btn" onclick="filterTable('draft')">Deactive</button>
                </div>
                <div class="search-box">
                    <i class="bi bi-search search-icons"></i>
                    <input type="text" class="form-control form-control-sm" placeholder="Search option sets..."
                        id="searchInput">
                    <span id="clearSearchBtn" class="clear-icon" onclick="clearSearch()"
                        style="display:none;">Cancel</span>
                </div>
            </div>

            <table class="table custom-table mb-0">
                <thead>
                    <tr>
                        <th width="40"><input type="checkbox" id="selectAll"></th>
                        <th>Option Name</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Updated</th>
                        <th>Products</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody id="setsTableBody">
                    @forelse($sets as $set)
                        <?php
                        $prodIds = $set->product_ids;
                        if (is_string($prodIds)) {
                            $prodIds = json_decode($prodIds, true);
                        }
                        if (!is_array($prodIds)) {
                            $prodIds = [];
                        }
                        
                        $productDisplay = '0 products';
                        if (in_array('ALL', $prodIds)) {
                            $productDisplay = 'All products';
                        } else {
                            $count = count($prodIds);
                            $productDisplay = $count . ' products';
                        }
                        
                        $statusClass = $set->status == 1 ? 'badge-active' : 'badge-draft';
                        $statusText = $set->status == 1 ? 'Active' : 'Draft';
                        ?>

                        <tr class="set-row" data-status="{{ $set->status == 1 ? 'active' : 'draft' }}"
                            data-json="{{ base64_encode(json_encode($set)) }}">
                            <td><input type="checkbox" class="row-checkbox" value="{{ $set->id }}"></td>

                            <td>
                                <span class="fw-bold text-dark">{{ $set->name }}</span>
                            </td>

                            <td>
                                <label class="switch">
                                    <input type="checkbox" class="status-toggle" data-id="{{ $set->id }}"
                                        {{ $set->status == 1 ? 'checked' : '' }}>
                                    <span class="slider round"></span>
                                </label>
                            </td>

                            <td class="text-muted small">
                                {{ $set->created_at ? \Carbon\Carbon::parse($set->created_at)->format('d/m/Y, H:i:s') : '-' }}
                            </td>

                            <td class="text-muted small">
                                {{ $set->updated_at ? \Carbon\Carbon::parse($set->updated_at)->format('d/m/Y, H:i:s') : '-' }}
                            </td>

                            <td>
                                <span class="text-dark">{{ $productDisplay }}</span>
                            </td>

                            <td class="text-end">
                                <button class="action-btn" onclick="editOptionSet(this)" title="Edit">
                                    <svg viewBox="0 0 20 20" class="action-icon" focusable="false" aria-hidden="true">
                                        <path fill-rule="evenodd"
                                            d="M15.65 2.54a2.15 2.15 0 0 1 3.03 3.03l-1.28 1.29-3.03-3.04 1.28-1.28Zm-2.56 2.57L4.52 13.68a.75.75 0 0 0-.2.37l-.95 3.8a.75.75 0 0 0 .91.91l3.8-.95a.75.75 0 0 0 .37-.2l8.57-8.57-3.93-3.93Z"
                                            fill="#5C5F62" />
                                    </svg>
                                </button>


                                <button class="action-btn" onclick="deleteOptionSet({{ $set->id }})"
                                    title="Delete">
                                    <svg viewBox="0 0 20 20" class="action-icon" focusable="false" aria-hidden="true">
                                        <path fill-rule="evenodd"
                                            d="M14 4h3a1 1 0 0 1 1 1v1H2V5a1 1 0 0 1 1-1h3V1.5A1.5 1.5 0 0 1 7.5 0h5A1.5 1.5 0 0 1 14 1.5V4Zm-6.5-1.5h5V4h-5V2.5ZM4 7h12v11a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V7Zm3 2.5a.75.75 0 0 0-1.5 0v8a.75.75 0 0 0 1.5 0v-8Zm3 0a.75.75 0 0 0-1.5 0v8a.75.75 0 0 0 1.5 0v-8Zm3 0a.75.75 0 0 0-1.5 0v8a.75.75 0 0 0 1.5 0v-8Z"
                                            fill="#D82C0D" />
                                    </svg>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                        <tr id="emptyStateRow">
                            <td colspan="7" class="text-center p-5">
                                <div class="text-muted">No option sets found. Click "Create option set" to start.</div>
                            </td>
                        </tr>
                        </tr>
                    @endforelse
                    <tr id="noResultsRow" style="display:none;">
                        <td colspan="7" class="text-center p-5">
                            <div class="text-muted">No matching option sets found.</div>
                        </td>
                    </tr>
                </tbody>
            </table>

            <div class="p-3 border-top bg-white text-center">
                <div id="pagination-controls" class="d-flex justify-content-center gap-2 mb-2"></div>
                <span class="text-muted small d-block" id="showingCount">Showing {{ count($sets) }} items</span>
            </div>
        </div>

        <div id="bulkActionBar" class="bulk-action-bar" style="display: none;">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="fw-bold me-2"><span id="selectedCount">0</span> selected</span>
                    <button class="btn btn-sm btn-outline-secondary me-2" onclick="deselectAll()">Cancel</button>
                </div>
                <div>
                    <button class="btn btn-sm btn-outline-primary me-2" onclick="bulkDuplicate()">
                        Duplicate
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="bulkDelete()">
                        Delete
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div id="view-create" style="display: none;">

        <div class="d-flex align-items-center mb-4 border-bottom pb-3">
            <button class="btn btn-light border me-3" onclick="showDashboard()">Back</button>
            <div style="flex: 1;">
                <input type="text" id="set_name" class="set-title-input" value="Untitled option set"
                    placeholder="Enter option set name">
            </div>
            <div>
                <button type="button" class="btn btn-success" onclick="saveOptionSet()">Save</button>
            </div>
        </div>

        <form id="mainOptionForm">
            <div class="row">

                <div class="col-md-7">

                    <div class="stepper">
                        <div class="step-item active" id="step1-btn" onclick="switchStep(1)">
                            <span class="step-number">1</span> Options
                        </div>
                        <div class="step-item" id="step2-btn" onclick="switchStep(2)">
                            <span class="step-number">2</span> Products & Customers
                        </div>
                    </div>

                    <h6 class="fw-bold">Options Name</h6>

                    <div id="step1-content">
                        <div id="fieldsContainer">
                        </div>

                        <div id="emptyState" class="empty-state">
                            <span class="magic-icon">🪄</span>
                            <h5>Getting started!</h5>
                            <p class="text-muted">Add your first option set by clicking add option.</p>
                            <button type="button" class="btn-add-main" onclick="openTypeModal()">Add option</button>
                        </div>

                        <div id="addMoreContainer" class="mt-3 text-start" style="display:none;">
                            <button type="button" class="btn btn-outline-dark" onclick="openTypeModal()">+ Add
                                option</button>
                        </div>
                    </div>

                    <div id="step2-content" style="display:none;">
                        <div class="card p-4 mb-3">
                            <h5 class="mb-3 fw-bold">Apply to Products</h5>

                            <div class="alert alert-light border small text-muted mb-3">
                                <i class="bi bi-info-circle me-2"></i> Each product can only show one option set.
                            </div>

                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="product_assignment"
                                    id="prod_all" value="all" onchange="toggleProductLogic()">
                                <label class="form-check-label" for="prod_all">All products</label>
                            </div>

                            <div class="form-check mb-3">
                                <input class="form-check-input" type="radio" name="product_assignment"
                                    id="prod_specific" value="specific" checked onchange="toggleProductLogic()">
                                <label class="form-check-label" for="prod_specific">Specific products</label>
                            </div>

                            <div id="specificProductSection" class="ms-4 border-start ps-3">
                                <div class="align-items-center gap-2">
                                    <button type="button" class="btn btn-outline-dark btn-sm"
                                        id="btnSelectProducts">Browse Products</button>

                                    <div id="tags-container" class="mt-2 d-flex flex-wrap gap-2"></div>
                                    <input type="hidden" id="selectedProductIds" name="product_ids">
                                </div>
                                <div id="productCountLabel" class="mt-2 text-muted small">0 products selected</div>
                            </div>
                        </div>

                        <div class="card p-4">
                            <h5 class="mb-3 fw-bold">Apply to Customers</h5>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="customer_assignment"
                                    id="cust_all" value="all" checked>
                                <label class="form-check-label" for="cust_all">All customers</label>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="col-md-5">
                    <div class="preview-card">
                        <div class="preview-image-placeholder"></div>
                        <div class="preview-line short"></div>
                        <div class="preview-line long"></div>
                        <div class="preview-line long"></div>

                        <hr class="my-4">

                        <div id="livePreviewContainer">
                        </div>

                        <button class="preview-btn">Add to cart</button>
                        <div class="text-center mt-2">
                            <a href="#" class="small text-primary text-decoration-none">Chat with us!</a>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <div id="typeModal" class="type-modal" onclick="closeTypeModal(event)">
        <div class="type-modal-content">

            <div class="modal-tabs">
                <div class="tab-item active" onclick="switchModalTab('options')">Option types</div>
            </div>

            <div id="modalSearchBar" class="modal-search-container">
                <i class="bi bi-search search-icon"></i>
                <input type="text" id="optionSearch" class="form-control search-input"
                    placeholder="Search templates" onkeyup="filterOptions()">
            </div>

            <div class="modal-body-scroll">

                <div id="tab-options" class="tab-content active">

                    <div class="option-category">
                        <h6 class="category-title">Text Input</h6>

                        <div class="option-row" onclick="addField('text', 'Text Box')">
                            <i class="bi bi-fonts opt-icon"></i> <span>Text box</span>
                        </div>

                        <div class="option-row" onclick="addField('number', 'Number Field')">
                            <i class="bi bi-123 opt-icon"></i> <span>Number field</span>
                        </div>

                        <div class="option-row" onclick="addField('textarea', 'Text Area')">
                            <i class="bi bi-pencil-square opt-icon"></i> <span>Text area</span>
                        </div>

                        <div class="option-row" onclick="addField('email', 'Email')">
                            <i class="bi bi-envelope-at opt-icon"></i> <span>Email</span>
                        </div>
                    </div>

                    <div class="option-category">
                        <h6 class="category-title">Choice List</h6>

                        <div class="option-row" onclick="addField('checkbox', 'Checkbox')">
                            <i class="bi bi-ui-checks opt-icon"></i> <span>Checkbox</span>
                        </div>

                        <div class="option-row" onclick="addField('radio', 'Radio Button')">
                            <i class="bi bi-record-circle opt-icon"></i> <span>Radio button</span>
                        </div>

                        <div class="option-row" onclick="addField('select', 'Dropdown')">
                            <i class="bi bi-chevron-down opt-icon"></i> <span>Dropdown menu</span>
                        </div>

                        <div class="option-row" onclick="addField('dropdown_thumbnail', 'Dropdown Thumbnail')">
                            <i class="bi bi-card-image opt-icon"></i> <span>Dropdown with thumbnail</span>
                        </div>

                        <div class="option-row" onclick="addField('switch', 'Switch')">
                            <i class="bi bi-toggles opt-icon"></i> <span>Switch</span>
                        </div>

                        <div class="option-row" onclick="addField('swatch', 'Swatch')">
                            <i class="bi bi-brush opt-icon"></i> <span>Swatch</span>
                        </div>
                    </div>

                    <div class="option-category">
                        <h6 class="category-title">Date & Others</h6>

                        <div class="option-row" onclick="addField('date', 'Date Picker')">
                            <i class="bi bi-calendar3 opt-icon"></i> <span>Date picker</span>
                        </div>

                        <div class="option-row" onclick="addField('file', 'File Upload')">
                            <i class="bi bi-cloud-upload opt-icon"></i> <span>File upload</span>
                        </div>

                        <div class="option-row" onclick="addField('paragraph', 'Paragraph')">
                            <i class="bi bi-text-paragraph opt-icon"></i> <span>Static Paragraph</span>
                        </div>
                        <div class="option-row" onclick="addField('button', 'Button')">
                            <i class="bi bi-cursor-fill opt-icon"></i> <span>Button</span>
                        </div>
                        <div class="option-row" onclick="addField('popup', 'Pop-up Modal')">
                            <i class="bi bi-app opt-icon"></i> <span>Pop-up Modal</span>
                        </div>
                        <div class="option-row" onclick="addField('divider', 'Divider')">
                            <i class="bi bi-hr opt-icon"></i> <span>Divider</span>
                        </div>
                    </div>
                </div>
            </div>

            <div id="hoverPreviewCard" class="hover-preview-card">
                <div class="preview-content">
                </div>
            </div>

        </div>
    </div>

</div>

<div id="fieldTemplateNew" style="display:none;">
    <div class="option-item-card">

        <div class="option-header" onclick="toggleAccordion(this)">
            <div class="d-flex align-items-center gap-2">
                <span class="fw-bold field-title-display text-dark" style="font-size:15px;">New Option</span>
                <span class="field-type-badge">Text</span>
            </div>
            <div>
                <button type="button" class="btn-close" onclick="removeFieldNew(this, event)"></button>
            </div>
        </div>

        <div class="option-body open">

            <div class="input-group-split">
                <div class="input-wrapper">
                    <label class="form-label-custom">On product page</label>
                    <input type="text" class="form-control-custom field-label" placeholder="e.g. Size"
                        onkeyup="updatePreview()">
                    <span class="char-count">0/250</span>
                </div>
            </div>

            <input type="hidden" class="field-type">

            <div class="mt-4">
                <div class="field-values-div" style="display:none;">
                    <div class="values-section">
                        <div class="values-header">
                            <span style="font-size:12px; font-weight:600; color:#5c5f62;">Option Values & Price</span>
                        </div>

                        <div class="values-list-container"></div>

                        <div class="mt-2">
                            <button type="button" class="btn-bulk-actions" onclick="addValueRow(this)">+ Add
                                Value</button>
                            <button type="button" class="btn-bulk-actions" onclick="openBulkAddModal(this)">Bulk
                                add</button>
                        </div>
                    </div>
                </div>
                <div class="checkbox-wrapper mt-4 pt-3 border-top">
                    <input class="field-required" type="checkbox" id="req_field" onchange="updatePreview()">
                    <label class="checkbox-label" for="req_field">This option is required</label>
                </div>
            </div>


            <div class="values-section generic-price-section" style="display:none;">
                <div class="values-header">
                </div>
                <div class="d-flex gap-2">
                    <div class="price-input-group" style="max-width: 200px;">
                        <span class="input-prefix">$</span>
                        <input type="number" class="form-control-custom input-w-prefix" placeholder="0.00">
                    </div>
                    <input type="text" class="form-control-custom" placeholder="SKU (Optional)">
                </div>
            </div>
        </div>
    </div>
</div>

<div id="swatchSettingsModal" class="swatch-modal-overlay" style="display:none;">
    <div class="swatch-modal-box">
        <div class="swatch-modal-header">
            <h5>Add swatch</h5>

            <span class="close-swatch-modal" onclick="closeSwatchModal()" style="cursor: pointer;">&times;</span>
        </div>
        <div class="swatch-modal-body">
            <div class="swatch-type-tabs mb-3">
                <label class="me-3">
                    <input type="radio" name="swatch_type_select" value="color" checked
                        onchange="toggleSwatchType('color')">
                    Color Swatch
                </label>
                <label>
                    <input type="radio" name="swatch_type_select" value="image"
                        onchange="toggleSwatchType('image')">
                    Image Swatch
                </label>
            </div>

            <div id="swatchColorSection">
                <label class="form-label-custom">Color Picker (Hex)</label>
                <div class="d-flex align-items-center gap-3">
                    <input type="color" id="modalColorInput" class="form-control form-control-color"
                        value="#ff0000" title="Choose your color">
                    <input type="text" id="modalHexInput" class="form-control" value="#ff0000"
                        style="max-width: 100px;">
                </div>
            </div>

            <div id="swatchImageSection" style="display:none;">
                <label class="form-label-custom">Upload Image</label>

                <div class="image-upload-area" onclick="document.getElementById('modalImageFile').click()">
                    <input type="file" id="modalImageFile" style="display:none;" accept="image/*"
                        onchange="handleModalImageUpload(this)">
                    <span id="modalUploadText">Click to add image</span>
                    <img id="modalImagePreview" src=""
                        style="display:none; max-width: 100px; max-height: 100px; border-radius: 4px;">
                    <div id="swatchLoader" class="thumbnail-loader"
                        style="display:none;position:absolute;top:0;left:0;width:100%;height:100%;
                            background:rgba(255,255,255,0.8);z-index:9999;
                            align-items:center;justify-content:center;">
                        <div class="spinner"></div>
                    </div>
                </div>
                {{-- <small class="text-muted d-block mt-2">
                    Recommended image size: 100×100 px. Maximum file size: 300 KB.
                </small> --}}
            </div>
        </div>
        <div class="swatch-modal-footer">
            <button class="btn btn-light border" onclick="closeSwatchModal()">Cancel</button>
            <button class="btn btn-dark" onclick="saveSwatchData()">Done</button>
        </div>
    </div>
</div>

<div id="bulkAddModal" class="swatch-modal-overlay" style="display:none;">
    <div class="swatch-modal-box" style="width: 500px;">
        <div class="swatch-modal-header">
            <h5>Bulk Add Values</h5>
            <span class="close-swatch-modal" onclick="closeBulkAddModal()" style="cursor: pointer;">&times;</span>
        </div>
        <div class="swatch-modal-body">
            <p class="text-muted small">Enter each value on a new line. (e.g. Red, Blue, Green)</p>
            <textarea id="bulkAddTextarea" class="form-control-custom" rows="8" placeholder="Red&#10;Blue&#10;Green"></textarea>
        </div>
        <div class="swatch-modal-footer">
            <button class="btn btn-light border" onclick="closeBulkAddModal()">Cancel</button>
            <button class="btn btn-dark" onclick="saveBulkData()">Add Values</button>
        </div>
    </div>
</div>

{{-- loader css --}}
<style>
    .thumbnail-loader {
        display: none;
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(255, 255, 255, 0.8);
        z-index: 9999;
        align-items: center;
        justify-content: center;
    }

    .spinner {
        width: 20px;
        height: 20px;
        border: 3px solid #ddd;
        border-top: 3px solid #000;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        from {
            transform: rotate(0deg);
        }

        to {
            transform: rotate(360deg);
        }
    }
</style>

<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: "Arial", sans-serif;
    }

    body {
        background-color: #f8f8f8;
        height: 100vh;
        padding: 20px;
    }

    .container {
        background: #ffffff;
        padding: 25px;
        border-radius: 10px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        width: 100%;
        line-height: 1.5;
        margin-bottom: 10px;
    }

    h1 {
        color: #333;
        margin-bottom: 10px;
    }

    p {
        color: #555;
        font-size: 16px;
        margin-bottom: 15px;
    }

    .btn {
        display: inline-block;
        margin-top: 15px;
        padding: 10px 20px;
        background: #090a09;
        color: white;
        text-decoration: none;
        font-weight: bold;
        border-radius: 5px;
        transition: 0.3s;
    }

    .btn:hover {
        background: #323233;
        color: #fff;
    }

    ul {
        margin-top: 10px;
        padding-left: 20px;
    }

    ul li {
        color: #333;
        font-size: 14px;
        margin-bottom: 5px;
    }

    .toggle-container {
        margin-top: 20px;
        padding: 15px;
        background: #f4f4f4;
        border-radius: 8px;
        text-align: center;
    }

    .toggle-container .status.active {
        color: #008010;
    }

    .toggle-container .status.in-activate {
        color: #6c8000;
    }

    .toggle-label {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 16px;
        color: #333;
        font-weight: bold;
    }

    .toggle-switch {
        position: relative;
        width: 50px;
        height: 25px;
    }

    .toggle-switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }

    .slider {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #ccc;
        border-radius: 25px;
        transition: 0.3s;
        cursor: pointer;
    }

    .slider:before {
        content: "";
        position: absolute;
        width: 20px;
        height: 20px;
        left: 3px;
        bottom: 3px;
        background-color: white;
        border-radius: 50%;
        transition: 0.3s;
    }

    input:checked+.slider {
        background-color: #007aff;
    }

    input:checked+.slider:before {
        transform: translateX(25px);
    }

    #loader {
        display: none;
        border: 3px solid #f3f3f3;
        border-top: 3px solid #3498db;
        border-radius: 50%;
        width: 25px;
        height: 25px;
        animation: spin 1s linear infinite;
    }

    .container {
        margin: 40px auto;
        padding: 24px;
        border: 1px solid #e1e1e1;
        border-radius: 12px;
        background-color: #f9f9f9;
        font-family: Arial, sans-serif;
    }

    .toggle-container {
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
    }

    .toggle-label span.status {
        font-weight: bold;
        margin-left: 10px;
        padding: 3px 8px;
        border-radius: 5px;
        font-size: 14px;
    }

    .toggle-switch {
        position: relative;
        display: inline-block;
        width: 50px;
        height: 24px;
    }

    .toggle-switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }

    .toggle-switch .slider {
        position: absolute;
        cursor: pointer;
        background-color: #ccc;
        border-radius: 24px;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        transition: 0.4s;
    }

    .toggle-switch .slider::before {
        position: absolute;
        content: "";
        height: 18px;
        width: 18px;
        border-radius: 50%;
        left: 3px;
        bottom: 3px;
        background-color: white;
        transition: 0.4s;
    }

    .toggle-switch input:checked+.slider::before {
        transform: translateX(26px);
    }

    .quantity-limit-settings {
        margin-top: 30px;
    }

    .quantity-limit-settings h3 {
        margin-bottom: 20px;
        font-size: 18px;
        color: #333;
        border-bottom: 1px solid #ddd;
        padding-bottom: 5px;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        display: block;
        font-weight: 600;
        margin-bottom: 8px;
        color: #444;
    }

    .form-group input {
        width: 100%;
        padding: 8px 10px;
        border: 1px solid #ccc;
        border-radius: 6px;
        font-size: 14px;
    }


    @keyframes spin {
        0% {
            transform: rotate(0deg);
        }

        100% {
            transform: rotate(360deg);
        }
    }
</style>

{{-- option set css --}}
<style>
    .polaris-badge {
        display: inline-flex;
        align-items: center;
        background-color: #e1e3e5;
        /* આછી ગ્રે બેકગ્રાઉન્ડ */
        color: #202223;
        font-size: 13px;
        padding: 4px 8px 4px 12px;
        border-radius: 4px;
        margin: 4px;
        font-weight: 500;
    }

    .remove-btn {
        background: none;
        border: none;
        margin-left: 8px;
        cursor: pointer;
        color: #5c5f62;
        font-size: 16px;
        line-height: 1;
        display: flex;
        align-items: center;
    }

    .remove-btn:hover {
        color: #202223;
    }

    #tags-container {
        display: flex;
        flex-wrap: wrap;
        margin-top: 10px;
        padding: 5px;
    }

    .toolbar-container {
        padding: 15px 20px;
        border-bottom: 1px solid #ebebeb;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .tab-group {
        display: flex;
        gap: 10px;
    }

    .tab-btn {
        background: none;
        border: none;
        padding: 5px 12px;
        border-radius: 4px;
        font-weight: 500;
        color: #5c5f62;
        cursor: pointer;
        font-size: 14px;
    }

    .tab-btn.active {
        background-color: #edeeef;
        color: #202223;
        font-weight: 600;
    }

    .search-box {
        position: relative;
        width: 500px;
    }

    .search-box input {
        padding-left: 35px;
        padding-right: 30px;
        font-size: 14px;
    }

    .clear-icon {
        position: absolute;
        right: 10px;
        top: 50%;
        transform: translateY(-50%);
        color: #999;
        cursor: pointer;
        font-weight: bold;
        font-size: 14px;
        z-index: 3;
    }

    .clear-icon:hover {
        color: #333;
    }

    .search-icons {
        position: absolute;
        left: 10px;
        top: 50%;
        transform: translateY(-50%);
        color: #999;
    }

    .bulk-action-bar {
        position: fixed;
        bottom: 20px;
        left: 50%;
        transform: translateX(-50%);
        background: #fff;
        padding: 15px 25px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        border: 1px solid #e1e3e5;
        width: 60%;
        z-index: 1000;
        animation: slideUp 0.3s ease-out;
    }

    @keyframes slideUp {
        from {
            bottom: -100px;
            opacity: 0;
        }

        to {
            bottom: 20px;
            opacity: 1;
        }
    }

    .custom-table {
        border-collapse: separate;
        border-spacing: 0;
        width: 100%;
    }

    .custom-table thead th {
        background-color: #f7f8f9;
        border-bottom: 1px solid #e1e3e5;
        border-top: 1px solid #e1e3e5;
        color: #5c5f62;
        font-weight: 600;
        font-size: 13px;
        padding: 12px 16px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .custom-table tbody td {
        border-bottom: 1px solid #e1e3e5;
        padding: 14px 16px;
        vertical-align: middle;
        color: #202223;
        font-size: 14px;
        background: #fff;
    }

    .custom-table tbody tr:hover td {
        background-color: #fcfcfc;
    }

    .badge-status {
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 600;
        display: inline-block;
    }

    .badge-active {
        background-color: #aee9d1;
        color: #1e66d1;
    }

    .badge-draft {
        background-color: #e4e5e7;
        color: #4a4a4a;
    }

    .action-btn {
        background: none;
        border: 1px solid transparent;
        border-radius: 4px;
        padding: 6px;
        cursor: pointer;
        margin-left: 5px;
        transition: all 0.2s;
    }

    .action-btn:hover {
        background-color: #f1f2f3;
        border-color: #dbe1e6;
    }

    .action-icon {
        width: 20px;
        height: 20px;
    }

    .text-end {
        text-align: center !important;
    }

    .btn-primary-custom {
        background-color: #2c2c2c;
        color: white;
        border: none;
        padding: 8px 16px;
        border-radius: 4px;
        font-weight: 600;
        font-size: 14px;
    }

    .btn-primary-custom:hover {
        background-color: #000;
        color: white;
    }

    .text-muted-small {
        font-size: 12px;
        color: #8c9196;
    }

    .d-none {
        display: none !important;
    }
</style>

{{-- New Create option set css --}}
<style>
    .paragraph-settings-container {
        background: #fff;
        padding: 10px 0;
        margin-top: 10px;
    }

    .shopify-checkbox-group {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 10px;
    }

    .shopify-checkbox-group input[type="checkbox"] {
        width: 16px;
        height: 16px;
        accent-color: #2c6ecb;
        cursor: pointer;
    }

    .shopify-checkbox-group label {
        font-size: 14px;
        color: #202223;
        cursor: pointer;
        margin-bottom: 0;
    }

    .rich-editor-box {
        border: 1px solid #dcdcdc;
        border-radius: 4px;
        background: #fff;
        margin-top: 5px;
        box-shadow: 0 1px 0 rgba(0, 0, 0, 0.05);
    }

    .rich-toolbar {
        background: #fcfcfc;
        border-bottom: 1px solid #e1e3e5;
        padding: 8px 12px;
        display: flex;
        gap: 15px;
        border-radius: 4px 4px 0 0;
        align-items: center;
    }

    .rt-icon {
        font-weight: 600;
        font-size: 14px;
        color: #5c5f62;
        cursor: pointer;
        padding: 4px 6px;
        border-radius: 3px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .rt-icon:hover {
        background: #edeeef;
        color: #202223;
    }

    .rt-separator {
        width: 1px;
        height: 18px;
        background: #dcdcdc;
    }

    .para-content-input {
        width: 100%;
        border: none;
        padding: 15px;
        min-height: 150px;
        resize: vertical;
        outline: none;
        font-family: -apple-system, BlinkMacSystemFont, "San Francisco", "Segoe UI", Roboto, "Helvetica Neue", sans-serif;
        font-size: 14px;
        line-height: 1.5;
        color: #202223;
        border-radius: 0 0 4px 4px;
    }

    .file-settings-container {
        background: #f9fafb;
        border: 1px solid #e1e3e5;
        padding: 20px;
        border-radius: 8px;
        margin-top: 15px;
    }

    .file-type-group {
        margin-top: 15px;
    }

    .file-type-option {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 8px;
        cursor: pointer;
    }

    .file-type-desc {
        font-size: 12px;
        color: #6d7175;
    }

    .create-header {
        border-bottom: 1px solid #e1e3e5;
        padding-bottom: 20px;
        margin-bottom: 20px;
    }

    .set-title-input {
        font-size: 20px;
        font-weight: bold;
        border: 1px dashed #ccc;
        padding: 5px 10px;
        width: 100%;
        max-width: 400px;
        color: #333;
        margin-top: 15px;
    }

    .set-title-input:focus {
        border: 1px solid #008060;
        outline: none;
        background: #fff;
    }

    .stepper {
        display: flex;
        align-items: center;
        margin-bottom: 30px;
    }

    .step-item {
        display: flex;
        align-items: center;
        padding: 10px 20px;
        background: #f4f6f8;
        border-radius: 4px;
        margin-right: 10px;
        color: #6d7175;
        font-weight: 500;
        cursor: pointer;
        position: relative;
    }

    .step-item.active {
        background: #e1e1e1;
        color: #242726;
        font-weight: 700;
    }

    .step-item.active::after {
        content: '';
        position: absolute;
        right: -10px;
        top: 50%;
        transform: translateY(-50%);
        border-left: 10px solid #e1e1e1;
        border-top: 10px solid transparent;
        border-bottom: 10px solid transparent;
        z-index: 1;
    }

    .step-number {
        background: #fff;
        border-radius: 50%;
        width: 24px;
        height: 24px;
        text-align: center;
        line-height: 24px;
        font-size: 12px;
        margin-right: 8px;
        border: 1px solid #c9cccf;
    }

    .step-item.active .step-number {
        color: #242726;
    }

    .swatch-modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 10000;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .swatch-modal-box {
        background: #fff;
        width: 500px;
        border-radius: 10px;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);
        overflow: hidden;
    }

    .swatch-modal-header {
        padding: 15px 20px;
        border-bottom: 1px solid #eee;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .swatch-modal-body {
        padding: 20px;
    }

    .swatch-modal-footer {
        padding: 15px 20px;
        border-top: 1px solid #eee;
        text-align: right;
        background: #f9f9f9;
    }

    .swatch-preview-btn {
        width: 40px;
        height: 38px;
        border: 1px solid #ccc;
        border-radius: 4px;
        cursor: pointer;
        display: inline-block;
        background-size: cover;
        background-position: center;
    }

    .image-upload-area {
        border: 2px dashed #ccc;
        padding: 20px;
        text-align: center;
        border-radius: 6px;
        cursor: pointer;
    }

    .image-upload-area:hover {
        background-color: #f0f0f0;
    }

    .empty-state {
        text-align: center;
        padding: 60px 20px;
        background: #fff;
        border: 1px dashed #dbe1e6;
        border-radius: 8px;
    }

    .magic-icon {
        font-size: 40px;
        margin-bottom: 15px;
        display: block;
    }

    .btn-add-main {
        background: #333;
        color: white;
        padding: 10px 25px;
        border-radius: 5px;
        text-decoration: none;
        display: inline-block;
        margin-top: 15px;
    }

    .preview-card {
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.05);
        padding: 20px;
        position: sticky;
        top: 20px;
    }

    .preview-image-placeholder {
        background: #f1f2f3;
        height: 200px;
        width: 100%;
        border-radius: 4px;
        margin-bottom: 20px;
    }

    .preview-line {
        height: 10px;
        background: #f1f2f3;
        margin-bottom: 10px;
        border-radius: 2px;
    }

    .preview-line.short {
        width: 60%;
    }

    .preview-line.long {
        width: 100%;
    }

    .preview-btn {
        width: 100%;
        border: 1px solid #333;
        background: #fff;
        padding: 10px;
        font-weight: bold;
        text-align: center;
        margin-top: 20px;
    }

    .option-item-card {
        border: 1px solid #e1e3e5;
        border-radius: 8px;
        margin-bottom: 15px;
        background: #fff;
        overflow: hidden;
    }

    .option-header {
        padding: 15px;
        background: #f9fafb;
        border-bottom: 1px solid #e1e3e5;
        display: flex;
        justify-content: space-between;
        align-items: center;
        cursor: pointer;
    }

    .option-body {
        padding: 20px;
        display: none;
    }

    .option-body.open {
        display: block;
    }

    .type-modal {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 9999;
        display: none;
        align-items: center;
        justify-content: center;
    }

    .type-modal-content {
        background: #fff;
        width: 600px;
        max-width: 90%;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        display: flex;
        overflow: hidden;
    }

    .type-sidebar {
        width: 150px;
        background: #f4f6f8;
        padding: 15px;
        border-right: 1px solid #e1e3e5;
    }

    .type-list {
        flex: 1;
        padding: 20px;
        max-height: 500px;
        overflow-y: auto;
    }

    .type-option {
        padding: 10px;
        border: 1px solid #e1e3e5;
        border-radius: 4px;
        margin-bottom: 10px;
        cursor: pointer;
        display: flex;
        align-items: center;
    }

    .type-option:hover {
        border-color: #008060;
        background: #f0fdf4;
    }

    .type-icon {
        margin-right: 10px;
        font-size: 18px;
    }
</style>

{{-- active status toggale css --}}
<style>
    .switch {
        position: relative;
        display: inline-block;
        width: 42px;
        height: 22px;
    }

    .switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }

    .slider {
        position: absolute;
        cursor: pointer;
        inset: 0;
        background-color: #ccc;
        transition: .3s;
        border-radius: 22px;
    }

    .slider:before {
        position: absolute;
        content: "";
        height: 16px;
        width: 16px;
        left: 3px;
        bottom: 3px;
        background-color: white;
        transition: .3s;
        border-radius: 50%;
    }

    input:checked+.slider {
        background-color: #008010;
    }

    input:checked+.slider:before {
        transform: translateX(20px);
    }
</style>

{{-- modal-tabs css --}}
<style>
    .type-modal {
        display: none;
        position: fixed;
        z-index: 1050;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: hidden;
        background-color: rgba(0, 0, 0, 0.5);
        align-items: center;
        justify-content: center;
    }

    .type-modal-content {
        background-color: #fff;
        width: 750px;
        height: 600px;
        border-radius: 12px;
        display: flex;
        flex-direction: column;
        position: relative;
        box-shadow: 0 5px 25px rgba(0, 0, 0, 0.2);
    }

    .modal-tabs {
        display: flex;
        border-bottom: 2px solid #f1f1f1;
        background: #f8f9fa;
        border-radius: 12px 12px 0 0;
        padding: 0 20px;
    }

    .tab-item {
        padding: 18px 25px;
        font-weight: 600;
        color: #5c5f62;
        cursor: pointer;
        position: relative;
    }

    .tab-item:hover {
        color: #000;
    }

    .tab-item.active {
        color: #000;
        border-bottom: 3px solid #000;
        margin-bottom: -2px;
    }

    .modal-search-container {
        padding: 15px 25px;
        border-bottom: 1px solid #eee;
        position: relative;
    }

    .search-input {
        padding-left: 35px;
        border-radius: 6px;
        border: 1px solid #ccc;
    }

    .search-icon {
        position: absolute;
        left: 35px;
        top: 50%;
        transform: translateY(-50%);
        color: #999;
    }

    .modal-body-scroll {
        flex: 1;
        overflow-y: auto;
        padding: 10px 0;
    }

    .option-category {
        margin-bottom: 15px;
    }

    .category-title {
        padding: 10px 25px;
        font-size: 13px;
        color: #6d7175;
        text-transform: uppercase;
        font-weight: 700;
        letter-spacing: 0.5px;
    }

    .option-row {
        padding: 12px 25px;
        cursor: pointer;
        display: flex;
        align-items: center;
        transition: background 0.2s;
    }

    .option-row:hover {
        background-color: #f1f2f3;
    }

    .opt-icon {
        width: 24px;
        margin-right: 15px;
        font-size: 16px;
        color: #5c5f62;
    }

    .hover-preview-card {
        display: none;
        position: absolute;
        right: -260px;
        top: 100px;
        width: 250px;
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
        padding: 15px;
        border: 1px solid #e1e3e5;
        z-index: 1060;
    }

    .hover-preview-card::after {
        content: "";
        position: absolute;
        top: 20px;
        left: -10px;
        border-width: 10px 10px 10px 0;
        border-style: solid;
        border-color: transparent #fff transparent transparent;
        filter: drop-shadow(-2px 0 2px rgba(0, 0, 0, 0.05));
    }

    .preview-field-label {
        display: block;
        height: 10px;
        width: 60%;
        background: #e1e3e5;
        margin-bottom: 8px;
        border-radius: 2px;
    }

    .preview-field-input {
        width: 100%;
        height: 30px;
        border: 1px solid #ccc;
        border-radius: 4px;
        background: #fff;
    }
</style>

<script>
    var app = null;
    (async function() {
        const params = new URLSearchParams(window.location.search);
        const shop = params.get("shop");

        if (!shop) {
            console.error("Missing shop or host parameters.");
            return;
        }
    })();

    document.getElementById("toggleApp").addEventListener("change", async function() {
        var isChecked = $(this).is(":checked") ? 1 : 0;
        var url = "<?php echo env('SHOPIFY_APP_URL'); ?>/api/appStatus";
        $("#loader").show();
        fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    _token: $('meta[name="csrf-token"]').attr("content"),
                    shop: "{{ $response['shop'] }}",
                    app_id: "{{ $response['appId'] }}",
                    status: isChecked
                })
            })
            .then(response => response.json())
            .then(function(data) {
                $("#loader").hide();
                $('.toggle-container .status').text(data.message);
                if (data.status == 1) {
                    $('.toggle-container .status').addClass('active');
                    $('.toggle-container .status').removeClass('in-activate');
                } else {
                    $('.toggle-container .status').addClass('in-activate');
                    $('.toggle-container .status').removeClass('active');
                }
            })
            .catch(function(error) {
                alert(error);
            });
    });
</script>

<style>
    .date-settings-container {
        background: #f9fafb;
        border: 1px solid #e1e3e5;
        padding: 15px;
        border-radius: 6px;
        margin-top: 15px;
    }

    .day-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 8px 0;
        border-bottom: 1px solid #eee;
    }

    .day-row:last-child {
        border-bottom: none;
    }

    .day-label {
        width: 120px;
        font-weight: 500;
    }

    .day-toggle {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .time-inputs {
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .time-inputs input {
        width: 80px !important;
        padding: 5px !important;
    }

    .custom-toggle-wrapper {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .custom-toggle-switch {
        position: relative;
        display: inline-block;
        width: 44px;
        height: 24px;
        margin-bottom: 0;
    }

    .custom-toggle-switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }

    .custom-toggle-slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #ccc;
        transition: .4s;
        border-radius: 34px;
    }

    .custom-toggle-slider:before {
        position: absolute;
        content: "";
        height: 18px;
        width: 18px;
        left: 3px;
        bottom: 3px;
        background-color: white;
        transition: .4s;
        border-radius: 50%;
    }

    .custom-toggle-switch input:checked+.custom-toggle-slider {
        background-color: #2c2c2c;
    }

    .custom-toggle-switch input:checked+.custom-toggle-slider:before {
        transform: translateX(20px);
    }

    .input-group-split {
        display: flex;
        gap: 20px;
        margin-bottom: 20px;
    }

    .input-wrapper {
        flex: 1;
        position: relative;
    }

    .char-count {
        position: absolute;
        right: 10px;
        top: 38px;
        font-size: 11px;
        color: #999;
    }

    .form-label-custom {
        font-size: 13px;
        font-weight: 600;
        color: #303030;
        margin-bottom: 6px;
        display: block;
    }

    .form-control-custom {
        width: 100%;
        padding: 8px 12px;
        border: 1px solid #dcdcdc;
        border-radius: 6px;
        font-size: 14px;
        color: #202223;
        transition: border-color 0.2s;
    }

    .form-control-custom:focus {
        outline: none;
        box-shadow: 0 0 0 1px #c5c7c6;
    }

    .value-row-item {
        display: flex;
        gap: 10px;
        margin-bottom: 10px;
        align-items: center;
    }

    .value-row-item .input-group-text {
        flex: 2;
    }

    .value-row-item .input-group-price {
        flex: 1;
        position: relative;
    }

    .value-row-item .input-group-price span {
        position: absolute;
        left: 8px;
        top: 50%;
        transform: translateY(-50%);
        color: #888;
        z-index: 1;
    }

    .value-row-item .input-group-price input {
        padding-left: 20px !important;
    }

    .btn-remove-value {
        border: none;
        background: transparent;
        color: #d82c0d;
        cursor: pointer;
        padding: 5px;
    }

    .btn-remove-value:hover {
        background-color: #fbeae5;
        border-radius: 4px;
    }

    .value-row-item {
        display: flex;
        gap: 10px;
        margin-bottom: 10px;
        align-items: center;
    }

    .thumbnail-upload-wrapper {
        position: relative;
        width: 40px;
        height: 38px;
        border: 1px solid #dcdcdc;
        border-radius: 4px;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        background: #f9fafb;
        cursor: pointer;
        flex-shrink: 0;
    }

    .thumbnail-upload-wrapper input[type="file"] {
        position: absolute;
        width: 100%;
        height: 100%;
        opacity: 0;
        cursor: pointer;
        z-index: 2;
    }

    .thumbnail-preview {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: none;
    }

    .upload-icon {
        font-size: 18px;
        color: #babfc3;
    }

    .input-group-text {
        flex: 2;
    }

    .input-group-price {
        flex: 1;
        position: relative;
        min-width: 80px;
    }

    .input-group-price span {
        position: absolute;
        left: 8px;
        top: 50%;
        transform: translateY(-50%);
        color: #888;
        z-index: 1;
        font-size: 13px;
    }

    .input-group-price input {
        padding-left: 20px !important;
    }

    .drag-handle {
        color: #babfc3;
        cursor: grab;
        padding: 0 5px;
        font-size: 18px;
    }

    .btn-remove-value {
        border: none;
        background: transparent;
        color: #d82c0d;
        cursor: pointer;
        padding: 5px;
        display: flex;
        align-items: center;
    }

    .btn-remove-value:hover {
        background-color: #fbeae5;
        border-radius: 4px;
    }

    .values-section {
        background-color: #f7f8f9;
        border: 1px solid #e1e3e5;
        border-radius: 8px;
        padding: 15px;
        margin-top: 15px;
    }

    .values-header {
        display: flex;
        justify-content: space-between;
        margin-bottom: 10px;
    }

    .badge-platinum {
        background: #dff5f0;
        color: #007a5e;
        font-size: 11px;
        padding: 2px 6px;
        border-radius: 4px;
        text-transform: uppercase;
        font-weight: 700;
        letter-spacing: 0.5px;
        margin-left: 5px;
    }

    .values-table-row {
        display: flex;
        align-items: center;
        gap: 10px;
        background: #fff;
        border: 1px solid #e1e3e5;
        border-radius: 6px;
        padding: 8px;
        margin-bottom: 8px;
    }

    .drag-handle {
        color: #babfc3;
        cursor: grab;
        padding: 0 5px;
        font-size: 18px;
    }

    .value-input-group {
        flex: 2;
    }

    .price-input-group {
        flex: 1;
        position: relative;
    }

    .input-prefix {
        position: absolute;
        left: 8px;
        top: 50%;
        transform: translateY(-50%);
        color: #8c9196;
        font-size: 12px;
    }

    .input-w-prefix {
        padding-left: 20px !important;
    }

    .btn-bulk-actions {
        font-size: 13px;
        color: #005bd3;
        background: none;
        border: none;
        padding: 0;
        cursor: pointer;
        margin-right: 15px;
        font-weight: 500;
    }

    .btn-bulk-actions:hover {
        text-decoration: underline;
    }

    .checkbox-wrapper {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-top: 15px;
    }

    .checkbox-wrapper input[type="checkbox"] {
        width: 16px;
        height: 16px;
        cursor: pointer;
        accent-color: #0d6efd;
    }

    .checkbox-label {
        font-size: 14px;
        color: #202223;
        cursor: pointer;
    }

    .option-header {
        background: #fff;
        padding: 15px 20px;
    }

    .field-type-badge {
        background: #f4f6f8;
        border: 1px solid #dfe3e8;
        color: #454f5b;
        font-weight: 600;
        font-size: 11px;
        padding: 2px 8px;
        border-radius: 10px;
    }
</style>


{{-- app js all --}}
<script>
    const urlParams = new URLSearchParams(window.location.search);
    const host = urlParams.get('host');

    var AppBridge = window['app-bridge'];
    var actions = AppBridge ? AppBridge.actions : null;
    var createApp = AppBridge ? AppBridge.default : null;
    var ResourcePicker = actions ? actions.ResourcePicker : null;

    var app = null;
    var Toast = actions ? actions.Toast : null;
    var productPicker = null;

    function showToast(message, isError = false) {
        if (!Toast || !app) {
            if (isError) {
                console.error(message);
            } else {
                console.log(message);
            }
            return;
        }

        var toastOptions = {
            message: message,
            duration: 3000,
            isError: isError
        };

        var toast = Toast.create(app, toastOptions);
        toast.dispatch(Toast.Action.SHOW);
    }

    if (!host) {
        console.error('Error: Host parameter missing. ResourcePicker will not work.');
    } else {
        if (createApp) {
            app = createApp({
                apiKey: '{{ env($response['appId'] . '_API_KEY') }}',
                host: host,
                forceRedirect: true
            });
        } else {
            console.error("App Bridge Library not loaded correctly. Check <head> script tag.");
        }
    }

    const Redirect = actions ? actions.Redirect : null;

    if (!host && Redirect && app) {
        const redirect = Redirect.create(app);
        redirect.dispatch(Redirect.Action.ADMIN_PATH, {
            path: '/apps/{{ env($response['appId']) }}',
        });
    }

    var currentEditId = null;

    function showCreateForm(isEdit = false) {
        $('#view-dashboard').hide();
        $('#view-create').fadeIn();
        $('#fieldsContainer').empty();
        $('#livePreviewContainer').empty();

        if (!isEdit) {
            $('#set_name').val('Untitled option set');
            $('#selectedProductIds').val('');
            $('#productCountLabel').text('0 products selected')
                .removeClass('fw-bold text-success').addClass('text-muted');

            $('#emptyState').show();
            $('#addMoreContainer').hide();

            // Radio Buttons Reset
            $('#prod_specific').prop('checked', true);
            toggleProductLogic(); // Section show

            currentEditId = null;
        }
        switchStep(1);
    }

    function resetCreateState() {
        currentEditId = null;
    }

    function showDashboard() {
        $('#view-create').hide();
        $('#view-dashboard').fadeIn();
    }

    var currentTabStatus = 'all';
    let currentPage = 1;
    const itemsPerPage = 10;

    function filterTable(status) {
        if (event && event.target && $(event.target).hasClass('tab-btn')) {
            $('.tab-btn').removeClass('active');
            $(event.target).addClass('active');
        }

        currentTabStatus = status;
        applyFilters();
    }

    function applyFilters() {
        var query = $('#searchInput').val().toLowerCase().trim();
        var visibleCount = 0;
        var totalRows = $('.set-row').length;

        if (totalRows === 0) {
            $('#emptyStateRow').show();
            $('#noResultsRow').hide();
            $('#pagination-controls').html('');
            $('#showingCount').text('Showing 0 items');
            return;
        }

        // reset
        $('.set-row').removeClass('filtered-row').hide();

        $('.set-row').each(function() {
            var row = $(this);
            var rowStatus = row.data('status');
            var rowName = row.find('td:nth-child(2)').text().toLowerCase().trim();

            var isStatusMatch = (currentTabStatus === 'all') || (rowStatus === currentTabStatus);
            var isSearchMatch = (query === '') || rowName.includes(query);

            if (isStatusMatch && isSearchMatch) {
                row.addClass('filtered-row').show(); // pela jevu immediate show
                visibleCount++;
            }
        });

        if (visibleCount === 0) {
            $('.set-row').hide();
            $('#noResultsRow').show();
            $('#showingCount').text('Showing 0 items');
            $('#pagination-controls').html('');
        } else {
            $('#noResultsRow').hide();
            paginateVisibleRows(1); // filtered rows par pagination
        }

        if (query.length > 0) {
            $('#clearSearchBtn').show();
        } else {
            $('#clearSearchBtn').hide();
        }
    }
    // pagination
    function paginateVisibleRows(page = 1) {
        currentPage = page;

        const filteredRows = $('.set-row.filtered-row');
        const totalItems = filteredRows.length;
        const totalPages = Math.ceil(totalItems / itemsPerPage);

        // બધા rows hide
        $('.set-row').hide();

        // current page ના rows show
        const start = (currentPage - 1) * itemsPerPage;
        const end = start + itemsPerPage;

        filteredRows.slice(start, end).show();

        $('#showingCount').text('Showing ' + totalItems + ' items');

        renderPaginationControls(totalPages);
    }

    function renderPaginationControls(totalPages) {
        const container = $('#pagination-controls');
        container.html('');

        if (totalPages === 0) {
            return;
        }

        // Prev button
        const prevBtn = $(`
        <button class="btn btn-sm px-3 py-1 btn-light border">&laquo;</button>
    `);

        if (currentPage === 1) {
            prevBtn.prop('disabled', true);
        }

        prevBtn.on('click', function() {
            if (currentPage > 1) {
                paginateVisibleRows(currentPage - 1);
            }
        });

        container.append(prevBtn);

        // Page number buttons
        for (let i = 1; i <= totalPages; i++) {
            const btn = $(`
            <button class="btn btn-sm px-3 py-1 ${i === currentPage ? 'btn-dark' : 'btn-light border'}">${i}</button>
        `);

            btn.on('click', function() {
                paginateVisibleRows(i);
            });

            container.append(btn);
        }

        // Next button
        const nextBtn = $(`
        <button class="btn btn-sm px-3 py-1 btn-light border">&raquo;</button>
    `);

        if (currentPage === totalPages) {
            nextBtn.prop('disabled', true);
        }

        nextBtn.on('click', function() {
            if (currentPage < totalPages) {
                paginateVisibleRows(currentPage + 1);
            }
        });

        container.append(nextBtn);
    }

    $('#searchInput').on('keyup', function() {
        applyFilters();
    });

    function clearSearch() {
        $('#searchInput').val('');
        applyFilters();
    }

    function switchStep(step) {
        $('.step-item').removeClass('active');
        $('#step' + step + '-btn').addClass('active');

        if (step === 1) {
            $('#step1-content').show();
            $('#step2-content').hide();
        } else {
            $('#step1-content').hide();
            $('#step2-content').show();
        }
    }

    function openTypeModal() {
        $('#typeModal').css('display', 'flex');
        switchModalTab('options');
    }

    function closeTypeModal(e) {
        if (e.target.id === 'typeModal') {
            $('#typeModal').hide();
            hidePreview(); // Ensure preview is closed
        }
    }

    function switchModalTab(tabName) {
        // Buttons
        $('.tab-item').removeClass('active');
        if (tabName === 'options') {
            $('.tab-item:first-child').addClass('active');
            $('#tab-options').show();
            $('#tab-templates').hide();
            $('#modalSearchBar').show(); // Show search only for options
        } else {
            $('.tab-item:last-child').addClass('active');
            $('#tab-options').hide();
            $('#tab-templates').show();
            $('#modalSearchBar').hide();
        }
    }

    function filterOptions() {
        var input = document.getElementById("optionSearch");
        var filter = input.value.toUpperCase();
        var rows = document.getElementsByClassName("option-row");
        var categories = document.getElementsByClassName("option-category");

        // Loop through all option rows
        for (var i = 0; i < rows.length; i++) {
            var span = rows[i].getElementsByTagName("span")[1]; // The text span
            if (span) {
                var txtValue = span.textContent || span.innerText;
                if (txtValue.toUpperCase().indexOf(filter) > -1) {
                    rows[i].style.display = "";
                } else {
                    rows[i].style.display = "none";
                }
            }
        }
    }

    function hidePreview() {
        $('#hoverPreviewCard').hide();
    }

    function closeTypeModal(e) {
        if (e.target.id === 'typeModal') {
            $('#typeModal').hide();
        }
    }

    function toggleAccordion(header) {
        $(header).next('.option-body').slideToggle();
    }

    function addField(type, typeLabel) {
        $('#typeModal').hide();
        $('#emptyState').hide();
        $('#addMoreContainer').show();

        var template = $('#fieldTemplateNew').html();
        var $newItem = $(template);

        $newItem.find('.field-type').val(type);
        $newItem.find('.field-type-badge').text(typeLabel);
        $newItem.find('.field-title-display').text(typeLabel);

        if (['select', 'checkbox', 'radio', 'dropdown_thumbnail', 'swatch', 'switch', 'button'].includes(type)) {
            $newItem.find('.field-values-div').show();
            var addBtn = $newItem.find('.btn-bulk-actions').first();
            addValueRow(addBtn);
        } else if (type === 'date') {
            $newItem.find('.field-values-div').hide();
            let dateHTML = getDateConfigHTML();
            $newItem.find('.checkbox-wrapper').before(dateHTML);
        } else if (type === 'file') {
            $newItem.find('.field-values-div').hide();
            let fileHTML = getFileConfigHTML();
            $newItem.find('.checkbox-wrapper').before(fileHTML);
        } else if (type === 'paragraph') {
            $newItem.find('.field-values-div').hide();
            let editorId = 'editor_' + Date.now();
            let paraHTML = getParagraphConfigHTML(editorId);
            $newItem.find('.checkbox-wrapper').before(paraHTML);

            setTimeout(() => {
                if (typeof tinymce !== 'undefined') {
                    tinymce.init({
                        selector: '#' + editorId,
                        height: 200,
                        menubar: false,
                        statusbar: false,
                        plugins: 'lists link',
                        toolbar: 'undo redo | bold italic underline | alignleft aligncenter | bullist numlist | link',
                        branding: false,
                        setup: function(editor) {
                            editor.on('change keyup', function() {
                                editor.save();
                            });
                        }
                    });
                } else {
                    console.error("TinyMCE not loaded!");
                }
            }, 500);
        } else if (type === 'popup') {
            $newItem.find('.field-values-div').hide();

            // Create unique ID for TinyMCE
            let editorId = 'popup_editor_' + Date.now();

            let popupHTML = getPopupConfigHTML(editorId);
            $newItem.find('.checkbox-wrapper').before(popupHTML);

            // Initialize TinyMCE
            setTimeout(() => {
                if (typeof tinymce !== 'undefined') {
                    tinymce.init({
                        selector: '#' + editorId,
                        height: 200,
                        menubar: false,
                        statusbar: false,
                        plugins: 'lists link table',
                        toolbar: 'undo redo | bold italic | alignleft aligncenter | bullist numlist | link table',
                        branding: false,
                        setup: function(editor) {
                            editor.on('change keyup', function() {
                                editor.save();
                            });
                        }
                    });
                }
            }, 500);
        } else if (type === 'divider') {
            $newItem.find('.field-values-div').hide();
            let divHTML = getDividerConfigHTML();
            $newItem.find('.checkbox-wrapper').before(divHTML);
            // Dividers usually don't need a label on frontend, so we can default the label input to "Divider"
            $newItem.find('.field-label').val('Divider');
        }

        $('#fieldsContainer').append($newItem);
        updatePreview();
    }

    function removeFieldNew(btn, e) {
        e.stopPropagation();
        $(btn).closest('.option-item-card').remove();
        if ($('#fieldsContainer').children().length === 0) {
            $('#emptyState').show();
            $('#addMoreContainer').hide();
        }
        updatePreview();
    }

    function updatePreview() {
        var $container = $('#livePreviewContainer');
        $container.empty();

        $('#fieldsContainer .option-item-card').each(function(index) {
            var label = $(this).find('.field-label').val() || 'Untitled Option';
            var type = $(this).find('.field-type').val();
            // var values = $(this).find('.field-values').val();
            var required = $(this).find('.field-required').is(':checked');

            // Update Accordion Title in Builder List
            $(this).find('.field-title-display').text(label);

            var values = [];
            $(this).find('.value-row-item').each(function() {
                var n = $(this).find('.single-value-name').val();
                var p = $(this).find('.single-value-price').val();
                var c = $(this).find('.single-value-color').val() || '';
                var i = $(this).find('.single-value-image').val();

                if (n || i || (type === 'swatch' && c)) values.push({
                    name: n || c,
                    price: p,
                    color: c,
                    image: i
                });
            });

            // Generate Preview HTML
            var html = '<div class="mb-3">';
            html += '<label class="form-label small fw-bold">' + label + (required ?
                ' <span class="text-danger">*</span>' : '') + '</label>';

            if (type === 'text') {
                html += '<input type="text" class="form-control form-control-sm" placeholder="Type here...">';
            } else if (type === 'textarea') {
                html += '<textarea class="form-control form-control-sm" rows="2"></textarea>';
            } else if (type === 'divider') {
                let dSize = $(this).find('.div-size').val() || 1;
                let dColor = $(this).find('.div-color').val() || '#000000';
                let dStyle = $(this).find('.div-style').val() || 'solid';
                html += `<div style="width:100%; padding: 10px 0;">
                            <hr style="
                                border: 0 !important;
                                border-top: ${dSize}px ${dStyle} ${dColor} !important;
                                background: transparent !important;
                                height: 0 !important;
                                margin: 0 !important;
                                opacity: 1 !important;
                            ">
                        </div>`;
            } else if (type === 'number') {
                html += '<input type="number" class="form-control form-control-sm" placeholder="0">';
            } else if (type === 'email') {
                html +=
                    '<input type="email" class="form-control form-control-sm" placeholder="example@email.com">';
            } else if (type === 'select' || type === 'dropdown_thumbnail') {
                html += '<select class="form-select form-select-sm"><option>-- Select --</option>';
                if (values.length > 0) {
                    values.forEach(function(val) {
                        var pText = val.price ? ` (+$${val.price})` : '';
                        html += `<option>${val.name}${pText}</option>`;
                    });
                }
                html += '</select>';
            } // Checkbox
            else if (type === 'checkbox') {
                if (values.length > 0) {
                    values.forEach(function(val) {
                        var pText = val.price ? ` (+$${val.price})` : '';
                        html += `<div class="form-check">
                                <input class="form-check-input" type="checkbox">
                                <label class="form-check-label small">${val.name}${pText}</label>
                              </div>`;
                    });
                } else {
                    // Default Single Checkbox
                    html +=
                        '<div class="form-check"><input class="form-check-input" type="checkbox"><label class="form-check-label small">Yes</label></div>';
                }
            }
            // Radio Buttons
            else if (type === 'radio') {
                if (values.length > 0) {
                    values.forEach(function(val) {
                        var pText = val.price ? ` (+$${val.price})` : '';
                        html += `<div class="form-check">
                                <input class="form-check-input" type="radio" name="preview_radio_${index}">
                                <label class="form-check-label small">${val.name}${pText}</label>
                             </div>`;
                    });
                }
            }
            //SWITCH
            else if (type === 'switch') {
                let valOn = (values.length > 0) ? values[0].name : 'Yes';
                let priceOn = (values.length > 0 && values[0].price) ? ` (+$${values[0].price})` : '';

                // HTML Generate karo (Toggle Switch)
                html += `<div class="custom-toggle-wrapper">
                            <label class="custom-toggle-switch">
                                <input type="checkbox" checked disabled> <span class="custom-toggle-slider"></span>
                            </label>
                            <span style="font-size: 14px; color: #333;">${valOn}${priceOn}</span>
                         </div>`;
            }
            // Swatch
            else if (type === 'swatch') {
                html += '<div class="d-flex gap-2">';
                values.forEach(v => {
                    var bg = v.color ? v.color : '#ddd';
                    html +=
                        `<div style="width:60px; height:60px; border-radius:10%; background-color:${bg}; border:1px solid #ccc;"></div>`;
                });
                html += '</div>';
            }
            // Dropdown with thumbnail
            else if (['select', 'dropdown_thumbnail'].includes(type)) {
                html += '<select class="form-select form-select-sm"><option>-- Select --</option>';
                values.forEach(v => html += `<option>${v.name}</option>`);
                html += '</select>';
            }

            html += '</div>';
            $container.append(html);
        });
    }

    function toggleProductLogic() {
        if ($('#prod_all').is(':checked')) {
            $('#specificProductSection').slideUp();
        } else {
            $('#specificProductSection').slideDown();
        }
    }

    function deleteOptionSet(id) {

        Swal.fire({
            title: 'Are you sure?',
            text: "You want to delete this option set?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel'
        }).then((result) => {

            if (!result.isConfirmed) {
                return;
            }

            $.ajax({
                url: "{{ route('options.delete') }}",
                method: "POST",
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                data: {
                    id: id
                },
                success: function(res) {

                    if (res.success) {

                        Swal.fire({
                            icon: 'success',
                            title: 'Deleted!',
                            text: res.message || 'Option set deleted successfully.',
                            timer: 1500,
                            showConfirmButton: false
                        });

                        $('input[value="' + id + '"]').closest('tr').fadeOut(300, function() {
                            $(this).remove();
                            applyFilters();
                        });

                    } else {

                        Swal.fire({
                            icon: 'error',
                            title: 'Failed!',
                            text: res.message || 'Delete failed.'
                        });

                    }
                },
                error: function(xhr) {

                    console.error(xhr);

                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: 'Something went wrong while deleting.'
                    });
                }
            });

        });
    }

    let picker = null;

    function setupProductPicker(preselectedProducts = []) {
        if (!app) return;

        productPicker = ResourcePicker.create(app, {
            resourceType: ResourcePicker.ResourceType.Product,
            showVariants: false,
            allowMultiple: true,
            selection: preselectedProducts
        });

        productPicker.subscribe(ResourcePicker.Action.SELECT, function(data) {
            updateProductTagsUI(data.selection);
        });
    }

    $(document).ready(function() {

        setupProductPicker();

        $(document).on('click', '#btnSelectProducts', function(e) {
            e.preventDefault();

            if (productPicker) {
                productPicker.dispatch(ResourcePicker.Action.OPEN);
            } else {
                console.error("Product Picker not initialized.");
                showToast("Error: App Bridge not loaded. Please refresh the page.", true);
            }
        });
    });

    function fetchProductDetails(ids) {
        $.ajax({
            url: "{{ route('products.details') }}",
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            data: {
                ids: ids
            },
            success: function(response) {
                updateProductTagsUI(response.products);
                setupProductPicker(response.products);
            }
        });
    }

    function updateProductTagsUI(products) {
        let container = $('#tags-container');
        container.empty();

        let ids = [];
        products.forEach(product => {
            ids.push(product.id);

            let tag = document.createElement('span');
            tag.className = 'polaris-badge';
            tag.innerHTML = `
                ${product.title}
                <button type="button" class="remove-btn" onclick="removeProduct('${product.id}')">×</button>
            `;
            container.append(tag);
        });

        $('#selectedProductIds').val(JSON.stringify(ids));
        $('#productCountLabel').text(ids.length + ' products selected')
            .toggleClass('fw-bold text-success', ids.length > 0)
            .toggleClass('text-muted', ids.length === 0);
    }

    function removeProduct(productId) {
        let currentIds = JSON.parse($('#selectedProductIds').val() || '[]');
        let updatedIds = currentIds.filter(id => id !== productId);

        $('#selectedProductIds').val(JSON.stringify(updatedIds));
        $('#productCountLabel').text(updatedIds.length + ' products selected')
            .toggleClass('fw-bold text-success', updatedIds.length > 0)
            .toggleClass('text-muted', updatedIds.length === 0);
        $(`[onclick="removeProduct('${productId}')"]`).closest('span').remove();
    }

    function saveOptionSet() {
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        var optionsData = [];
        var isValid = true;

        $('#fieldsContainer .option-item-card').each(function() {
            var $card = $(this);
            var type = $card.find('.field-type').val();
            var label = $card.find('.field-label').val();

            if (!label) {
                showToast("Please fill all option labels.", true);
                isValid = false;
                return;
            }

            var vals = [];
            $card.find('.values-list-container .value-row-item').each(function() {
                var $row = $(this);
                var n = $row.find('.single-value-name').val();
                var p = $row.find('.single-value-price').val();
                var c = $row.find('.single-value-color').val() || '';
                var i = $row.find('.single-value-image').val();

                if (n || i || (type === 'swatch' && c)) {
                    vals.push({
                        name: n || c,
                        price: p,
                        color: c,
                        image: i
                    });
                }
            });

            if (type === 'switch' && vals.length === 0) {
                vals = [{
                        name: 'Yes',
                        price: '0',
                        image: ''
                    },
                    {
                        name: 'No',
                        price: '0',
                        image: ''
                    }
                ];
            }

            if (type === 'date') {
                var dateConfig = {};
                var hasData = false;
                $(this).find('.day-row').each(function() {
                    var day = $(this).find('.date-day-check').data('day');
                    var isEnabled = $(this).find('.date-day-check').is(':checked');
                    var isAllDay = $(this).find('.date-allday-check').is(':checked');
                    var start = $(this).find('.date-start').val();
                    var end = $(this).find('.date-end').val();

                    dateConfig[day] = {
                        enabled: isEnabled,
                        all_day: isAllDay,
                        start: start,
                        end: end
                    };
                    hasData = true;
                });

                if (hasData) {
                    vals.push(dateConfig);
                }
            }
            if (type === 'file') {
                var fileConfig = {};

                var maxQty = $(this).find('.file-max-qty').val();
                var price = $(this).find('.file-price').val();
                var sku = $(this).find('.file-sku').val();
                var restriction = $(this).find('.file-type-radio:checked').val();
                var customExt = $(this).find('.file-custom-ext').val();

                fileConfig = {
                    max_qty: maxQty,
                    price: price,
                    sku: sku,
                    restriction: restriction,
                    custom_ext: customExt
                };

                vals.push(fileConfig);
            }

            if (typeof tinymce !== 'undefined') {
                tinymce.triggerSave();
            }

            if (type === 'paragraph') {
                let content = $(this).find('.para-content-input').val();

                var paraConfig = {
                    content: content,
                    visible_in_app: $(this).find('.para-visible-app').is(':checked'),
                    hide_label: $(this).find('.para-hide-label').is(':checked')
                };
                vals.push(paraConfig);
            }
            if (type === 'popup') {
                let textArea = $(this).find('.popup-content-input');
                let editorId = textArea.attr('id');
                let content = '';

                if (editorId && tinymce.get(editorId)) {
                    content = tinymce.get(editorId).getContent();
                } else {
                    content = textArea.val();
                }

                var popupConfig = {
                    popup_label: $(this).find('.popup-link-label').val(),
                    content: content,
                    visible_in_app: $(this).find('.popup-visible-app').is(':checked'),
                    hide_label: $(this).find('.popup-hide-label').is(':checked')
                };
                vals.push(popupConfig);
            }
            if (type === 'divider') {
                var divConfig = {
                    size: $(this).find('.div-size').val(),
                    color: $(this).find('.div-color').val(),
                    style: $(this).find('.div-style').val(),
                    visible_in_app: $(this).find('.div-visible-app').is(':checked'),
                    hide_label: true // Force true for dividers
                };
                vals.push(divConfig);
            }

            optionsData.push({
                type: type,
                label: $(this).find('.field-label').val(),
                values: JSON.stringify(vals),
                required: $(this).find('.field-required').is(':checked') ? 1 : 0

            });
        });

        if (!isValid) return;

        var finalProductIds = $('#prod_all').is(':checked') ? ["ALL"] : (JSON.parse($('#selectedProductIds').val() ||
            '[]'));
        var setName = $('#set_name').val();

        if (!setName) {
            showToast("Please enter option set name", true);
            return;
        }

        var btn = $('.btn-success');
        btn.text('Saving...').prop('disabled', true);

        $.ajax({
            url: "{{ route('options.save') }}",
            method: "POST",
            headers: {
                "X-Shopify-Shop-Domain": "{{ $response['shop'] }}",
                "X-App-Id": "{{ $response['appId'] }}",
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            data: {
                name: setName,
                product_ids: finalProductIds,
                options: optionsData,
                id: currentEditId
            },
            success: function(res) {
                var msg = currentEditId ? "Option set updated successfully" :
                    "Option set created successfully";
                showToast(msg, false);

                setTimeout(function() {
                    location.reload();
                }, 1000);
            },
            error: function(xhr) {
                btn.text('Save').prop('disabled', false);
                var msg = "Error saving options.";
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                }
                showToast(msg, true);
                console.error(xhr);
            }
        });
    }

    function editOptionSet(btn) {
        var row = $(btn).closest('tr');
        var encodedData = row.attr('data-json');
        var data = encodedData ? JSON.parse(atob(encodedData)) : null;
        if (!data) return;

        currentEditId = data.id;
        showCreateForm(true);
        $('#set_name').val(data.name);

        let savedProductIds = typeof data.product_ids === 'string' ? JSON.parse(data.product_ids) : data.product_ids;
        if (!Array.isArray(savedProductIds)) {
            savedProductIds = [];
        }

        if (savedProductIds && savedProductIds.includes('ALL')) {
            $('#prod_all').prop('checked', true);
            toggleProductLogic();
        } else {
            $('#prod_specific').prop('checked', true);
            $('#specificProductSection').show();

            $.ajax({
                url: "{{ url('/get-product-details') }}",
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    'X-Shopify-Shop-Domain': "{{ $response['shop'] }}"
                },
                data: {
                    ids: savedProductIds
                },
                success: function(response) {
                    if (response.products && response.products.length > 0) {
                        updateProductTagsUI(response.products);
                        setupProductPicker(response.products);
                    }
                }
            });
        }

        $('#fieldsContainer').empty();
        $('#emptyState').hide();
        $('#addMoreContainer').show();

        data.options.forEach(function(opt) {
            var template = $('#fieldTemplateNew').html();
            var $item = $(template);

            $item.find('.field-type').val(opt.type);
            $item.find('.field-type-badge').text(getTypeLabel(opt.type));
            $item.find('.field-label').val(opt.label);
            $item.find('.field-title-display').text(opt.label);
            $item.find('.field-required').prop('checked', opt.required == 1);

            if (['select', 'checkbox', 'radio', 'dropdown_thumbnail', 'swatch', 'switch', 'button'].includes(opt
                    .type)) {
                $item.find('.field-values-div').show();
                var addBtn = $item.find('.btn-bulk-actions').first();

                try {
                    var parsed = JSON.parse(opt.values);
                    if (Array.isArray(parsed) && parsed.length > 0) {
                        parsed.forEach(function(v) {
                            addValueRow(addBtn, v.name, v.price, v.image, v.color);
                        });
                    } else {

                        if (opt.type === 'switch') {
                            addValueRow(addBtn, 'Yes');
                            addValueRow(addBtn, 'No');
                        } else {
                            addValueRow(addBtn);
                        }
                    }
                } catch (e) {

                    if (opt.type === 'switch') {
                        addValueRow(addBtn, 'Yes');
                        addValueRow(addBtn, 'No');
                    } else {
                        addValueRow(addBtn);
                    }
                }
            } else if (opt.type === 'date') {
                $item.find('.field-values-div').hide();

                let dateHTML = getDateConfigHTML();
                $item.find('.checkbox-wrapper').before(dateHTML);

                try {
                    var dateDataArray = JSON.parse(opt.values);

                    if (Array.isArray(dateDataArray) && dateDataArray.length > 0) {
                        var config = dateDataArray[0];

                        for (const [day, settings] of Object.entries(config)) {
                            let row = $item.find(`.date-day-check[data-day="${day}"]`).closest('.day-row');

                            if (row.length > 0) {
                                row.find('.date-day-check').prop('checked', settings.enabled);
                                row.find('.date-allday-check').prop('checked', settings.all_day);
                                row.find('.date-start').val(settings.start);
                                row.find('.date-end').val(settings.end);

                                if (settings.all_day) {
                                    row.find('.time-inputs').css('visibility', 'hidden');
                                } else {
                                    row.find('.time-inputs').css('visibility', 'visible');
                                }
                            }
                        }
                    }
                } catch (e) {
                    console.error("Error loading date settings:", e);
                }
            } else if (opt.type === 'file') {
                $item.find('.field-values-div').hide();

                let fileHTML = getFileConfigHTML();
                $item.find('.checkbox-wrapper').before(fileHTML);

                try {
                    var fileDataArray = JSON.parse(opt.values);
                    if (Array.isArray(fileDataArray) && fileDataArray.length > 0) {
                        var config = fileDataArray[0];

                        $item.find('.file-max-qty').val(config.max_qty);
                        $item.find('.file-price').val(config.price);
                        $item.find('.file-sku').val(config.sku);
                        $item.find(`.file-type-radio[value="${config.restriction}"]`).prop('checked', true);

                        if (config.restriction === 'custom') {
                            $item.find('.file-custom-ext').val(config.custom_ext).show();
                        }
                    }
                } catch (e) {
                    console.error("Error loading file settings:", e);
                }
            } else if (opt.type === 'paragraph') {
                $item.find('.field-values-div').hide();

                let editorId = 'editor_edit_' + Math.floor(Math.random() * 100000);
                let paraHTML = getParagraphConfigHTML(editorId);
                $item.find('.checkbox-wrapper').before(paraHTML);

                try {
                    var paraData = JSON.parse(opt.values);
                    if (Array.isArray(paraData) && paraData.length > 0) {
                        var config = paraData[0];

                        $item.find('.para-content-input').val(config.content);
                        $item.find('.para-visible-app').prop('checked', config.visible_in_app);
                        $item.find('.para-hide-label').prop('checked', config.hide_label);

                        setTimeout(() => {
                            if (typeof tinymce !== 'undefined') {
                                if (tinymce.get(editorId)) {
                                    tinymce.get(editorId).remove();
                                }

                                tinymce.init({
                                    selector: '#' + editorId,
                                    height: 200,
                                    menubar: false,
                                    statusbar: false,
                                    plugins: 'lists link',
                                    toolbar: 'undo redo | bold italic underline | alignleft aligncenter | bullist numlist | link',
                                    branding: false,
                                    setup: function(editor) {
                                        editor.on('init', function() {
                                            if (config.content) {
                                                editor.setContent(config.content);
                                            }
                                        });
                                        editor.on('change keyup', function() {
                                            editor.save();
                                        });
                                    }
                                });
                            }
                        }, 500);
                    }
                } catch (e) {
                    console.error("Error loading paragraph data", e);
                }
            } else if (opt.type === 'popup') {
                $item.find('.field-values-div').hide();

                let editorId = 'popup_edit_' + Math.floor(Math.random() * 100000);
                let popupHTML = getPopupConfigHTML(editorId);
                $item.find('.checkbox-wrapper').before(popupHTML);

                try {
                    var popupData = JSON.parse(opt.values);
                    if (Array.isArray(popupData) && popupData.length > 0) {
                        var config = popupData[0];
                        $item.find('.popup-link-label').val(config.popup_label);
                        $item.find('.popup-visible-app').prop('checked', config.visible_in_app);
                        $item.find('.popup-hide-label').prop('checked', config.hide_label);

                        setTimeout(() => {
                            if (typeof tinymce !== 'undefined') {
                                if (tinymce.get(editorId)) tinymce.get(editorId).remove(); // Cleanup

                                tinymce.init({
                                    selector: '#' + editorId,
                                    height: 200,
                                    menubar: false,
                                    statusbar: false,
                                    plugins: 'lists link table',
                                    toolbar: 'undo redo | bold italic | alignleft aligncenter | bullist numlist | link table',
                                    branding: false,
                                    setup: function(editor) {
                                        editor.on('init', function() {
                                            if (config.content) {
                                                editor.setContent(config.content);
                                            }
                                        });
                                        editor.on('change keyup', function() {
                                            editor.save();
                                        });
                                    }
                                });
                            }
                        }, 500);
                    }
                } catch (e) {
                    console.error(e);
                }
            } else if (opt.type === 'divider') {
                $item.find('.field-values-div').hide();
                let divHTML = getDividerConfigHTML();
                $item.find('.checkbox-wrapper').before(divHTML);

                try {
                    var divData = JSON.parse(opt.values);
                    if (Array.isArray(divData) && divData.length > 0) {
                        var config = divData[0];
                        $item.find('.div-size').val(config.size);
                        $item.find('.div-color').val(config.color);
                        $item.find('.div-style').val(config.style);
                        $item.find('.div-visible-app').prop('checked', config.visible_in_app);
                    }
                } catch (e) {
                    console.error(e);
                }
            }

            $('#fieldsContainer').append($item);
        });
        if (savedProductIds.includes('ALL')) {
            $('#prod_all').prop('checked', true);
            $('#prod_specific').prop('checked', false);
            $('#specificProductSection').hide();
            $('#productCountLabel').text('0 products selected');
        } else {
            $('#prod_specific').prop('checked', true);
            $('#selectedProductIds').val(JSON.stringify(savedProductIds));
            $('#productCountLabel').text(savedProductIds.length + ' products selected').addClass(
                'fw-bold text-success');
            $('#specificProductSection').show();
        }
        updatePreview();
    }

    $('#selectAll').change(function() {
        var isChecked = $(this).is(':checked');
        $('.row-checkbox').prop('checked', isChecked);
        updateBulkBar();
    });

    $(document).on('change', '.row-checkbox', function() {
        var allChecked = $('.row-checkbox').length === $('.row-checkbox:checked').length;
        $('#selectAll').prop('checked', allChecked);
        updateBulkBar();
    });

    function updateBulkBar() {
        var count = $('.row-checkbox:checked').length;
        $('#selectedCount').text(count);

        if (count > 0) {
            $('#bulkActionBar').fadeIn(200);
        } else {
            $('#bulkActionBar').fadeOut(200);
        }
    }

    function deselectAll() {
        $('.row-checkbox').prop('checked', false);
        $('#selectAll').prop('checked', false);
        updateBulkBar();
    }

    function bulkDelete() {

        var ids = [];

        $('.row-checkbox:checked').each(function() {
            ids.push($(this).val());
        });

        if (ids.length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'No option selected',
                text: 'Please select at least one option set.'
            });
            return;
        }

        Swal.fire({
            title: 'Are you sure?',
            text: `You want to delete ${ids.length} option sets?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, delete them!',
            cancelButtonText: 'Cancel'
        }).then((result) => {

            if (!result.isConfirmed) return;

            var $btn = $('#bulkActionBar .btn-danger');
            var originalText = $btn.text();

            $btn.prop('disabled', true).text('Deleting...');

            $.ajax({
                url: "{{ route('options.bulk-delete') }}",
                method: "POST",
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    "X-Shopify-Shop-Domain": "{{ $response['shop'] }}",
                    "X-App-Id": "{{ $response['appId'] }}"
                },
                data: {
                    ids: ids
                },
                success: function(res) {

                    Swal.fire({
                        icon: 'success',
                        title: 'Deleted!',
                        text: `${ids.length} option sets deleted successfully.`,
                        timer: 1500,
                        showConfirmButton: false
                    });

                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                },
                error: function(xhr) {

                    var msg = "Error deleting options";

                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        msg = xhr.responseJSON.message;
                    }

                    Swal.fire({
                        icon: 'error',
                        title: 'Failed!',
                        text: msg
                    });

                    $btn.prop('disabled', false).text(originalText);
                }
            });

        });
    }

    function bulkDuplicate() {
        var ids = [];
        $('.row-checkbox:checked').each(function() {
            ids.push($(this).val());
        });

        if (ids.length === 0) return;

        var $btn = $('#bulkActionBar .btn-outline-primary');
        var originalText = $btn.text();
        $btn.prop('disabled', true).text('Duplicating...');

        $.ajax({
            url: "{{ route('options.bulk-duplicate') }}",
            method: "POST",
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                "X-Shopify-Shop-Domain": "{{ $response['shop'] }}",
                "X-App-Id": "{{ $response['appId'] }}"
            },
            data: {
                ids: ids
            },
            success: function(res) {
                showToast("Duplicated successfully", false);
                setTimeout(function() {
                    location.reload();
                }, 1000);
            },
            error: function(xhr) {
                var msg = "Error duplicating options";
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                }
                showToast(msg, true);
                $btn.prop('disabled', false).text(originalText);
            }
        });
    }

    function getTypeLabel(type) {
        return {
            text: 'Textbox',
            textarea: 'Textarea',
            email: 'Email',
            checkbox: 'Checkbox',
            select: 'Dropdown',
            radio: 'Radio button',
            dropdown_thumbnail: 'Dropdown with Thumbnail',
            swatch: 'Swatch',
            switch: 'Switch',
            button: 'Button',
            divider: 'Divider'
        } [type] || 'Option';
    }

    function addValueRow(btn, valText = '', valPrice = '', valImage = '', valColor = '#ff0000') {
        var container = $(btn).closest('.values-section').find('.values-list-container');
        var fieldType = $(btn).closest('.option-item-card').find('.field-type').val();

        var visualHtml = '';

        if (fieldType === 'dropdown_thumbnail') {
            let imgDisplay = (valImage && valImage.length > 5) ? 'display:block;' : 'display:none;';
            let iconDisplay = (valImage && valImage.length > 5) ? 'display:none;' : 'display:block;';

            visualHtml = `
            <div class="thumbnail-upload-wrapper" title="Upload Image" style="position:relative; width:40px; height:38px; border:1px solid #ccc; border-radius:4px; overflow:hidden; background:#f9f9f9; display:flex; align-items:center; justify-content:center;">
                <input type="file" accept="image/*" onchange="handleDropdownImageUpload(this)" style="position:absolute; width:100%; height:100%; opacity:0; cursor:pointer; z-index:2;">
                <img class="thumbnail-preview" src="${valImage}" style="width:100%; height:100%; object-fit:cover; ${imgDisplay}">
                <span class="upload-icon" style="font-size:18px; color:#999; ${iconDisplay}">+</span>
                <div class="thumbnail-loader"
                    style="display:none;position:absolute;top:0;left:0;width:100%;height:100%;
                    background:rgba(255,255,255,0.8);z-index:5;
                    align-items:center;justify-content:center;">
                    <div class="spinner"></div>
                </div>
                <input type="hidden" class="single-value-image" value="${valImage}">
            </div>`;
        } else if (fieldType === 'swatch') {
            let bgStyle = 'background-color: ' + (valColor || '#ff0000');
            if (valImage && valImage.length > 5) {
                bgStyle = "background-image: url('" + valImage + "'); background-color: transparent;";
            }
            visualHtml =
                `<div class="swatch-preview-btn" style="width:40px; height:38px; border:1px solid #ccc; background-color:${valColor};
                border-radius:4px; overflow:hidden; ${bgStyle}; cursor:pointer;" onclick="openSwatchModal(this)"></div>`;
        }

        var rowHtml = `
        <div class="value-row-item" style="display:flex; gap:10px; margin-bottom:10px; align-items:center;">
            <div class="drag-handle" style="cursor:move; color:#ccc;">⋮⋮</div>

            ${visualHtml}

            <input type="hidden" class="single-value-image" value="${valImage}">
            <input type="hidden" class="single-value-color" value="${valColor}">

            <div class="input-group-text" style="flex:2;">
                <input type="text" class="form-control-custom single-value-name" placeholder="Label" value="${valText}">
            </div>
            <div class="input-group-price" style="flex:1; position:relative;">
                <span style="position:absolute; left:8px; top:50%; transform:translateY(-50%); color:#888;">$</span>
                <input type="number" class="form-control-custom single-value-price" placeholder="0.00" value="${valPrice}" style="padding-left:20px;">
            </div>
            <button type="button" class="btn-remove-value" onclick="$(this).closest('.value-row-item').remove()">
            <svg viewBox="0 0 20 20" style="width:20px; height:20px;" fill="#D82C0D"><path d="M14 4h3a1 1 0 0 1 1 1v1H2V5a1 1 0 0 1 1-1h3V1.5A1.5 1.5 0 0 1 7.5 0h5A1.5 1.5 0 0 1 14 1.5V4Zm-6.5-1.5h5V4h-5V2.5ZM4 7h12v11a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V7Zm3 2.5a.75.75 0 0 0-1.5 0v8a.75.75 0 0 0 1.5 0v-8Zm3 0a.75.75 0 0 0-1.5 0v8a.75.75 0 0 0 1.5 0v-8Zm3 0a.75.75 0 0 0-1.5 0v8a.75.75 0 0 0 1.5 0v-8Z"></path></svg>
            </button>
        </div>`;

        container.append(rowHtml);
    }

    function previewThumbnail(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            var $wrapper = $(input).closest('.thumbnail-upload-wrapper');

            reader.onload = function(e) {
                $wrapper.find('.thumbnail-preview').attr('src', e.target.result).show();
                $wrapper.find('.upload-icon').hide();
                $wrapper.find('.single-value-image').val(e.target.result);
            }

            reader.readAsDataURL(input.files[0]);
        }
    }

    function openSwatchModal(btnElement) {
        editingRow = $(btnElement).closest('.value-row-item');
        let currentColor = editingRow.find('.single-value-color').val();
        let currentImage = editingRow.find('.single-value-image').val();

        $('#modalColorInput').val(currentColor);
        $('#modalHexInput').val(currentColor);
        $('#modalImagePreview').attr('src', currentImage).toggle(!!currentImage);
        $('#modalUploadText').toggle(!currentImage);

        if (currentImage && currentImage.length > 5) {
            $('input[name="swatch_type_select"][value="image"]').prop('checked', true);
            toggleSwatchType('image');
        } else {
            $('input[name="swatch_type_select"][value="color"]').prop('checked', true);
            toggleSwatchType('color');
        }
        $('#swatchSettingsModal').fadeIn();
    }

    function toggleSwatchType(type) {
        if (type === 'color') {
            $('#swatchColorSection').show();
            $('#swatchImageSection').hide();
        } else {
            $('#swatchColorSection').hide();
            $('#swatchImageSection').show();
        }
    }

    $('#modalColorInput').on('input', function() {
        $('#modalHexInput').val(this.value);
    });
    $('#modalHexInput').on('input', function() {
        $('#modalColorInput').val(this.value);
    });

    function handleModalImageUpload(input) {
        const file = input.files[0];
        if (!file) return;

        $('#swatchLoader').css('display', 'flex');

        const img = new Image();

        img.onload = function() {
            // if (img.width > 300 || img.height > 300) {
            //     showToast("Maximum allowed size is 300x300px.");
            //     input.value = '';
            //     return;
            // }

            let formData = new FormData();
            formData.append('swatch_image', file);
            formData.append('shop', '{{ $response['shop'] }}');

            $.ajax({
                url: "{{ route('option.upload') }}",
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    $('#swatchLoader').hide();
                    if (response.success) {
                        let cdnUrl = response.url;
                        $('#modalImagePreview').attr('src', cdnUrl).show();
                        $('#modalUploadText').hide();

                        if (typeof editingRow !== 'undefined' && editingRow !== null) {
                            editingRow.find('.single-value-image').val(cdnUrl);
                        }
                    } else {
                        showToast("Upload failed: " + response.message, "error");
                    }
                },
                error: function(xhr) {
                    $('#swatchLoader').hide();
                    console.error(xhr.responseText);
                    showToast("Server error, please check logs.", "error");
                }
            });
        };
        img.src = URL.createObjectURL(file);
    }

    function saveSwatchData() {
        if (!editingRow) return;
        let type = $('input[name="swatch_type_select"]:checked').val();
        let previewBtn = editingRow.find('.swatch-preview-btn');
        let colorInput = editingRow.find('.single-value-color');
        let imageInput = editingRow.find('.single-value-image');

        if (type === 'color') {
            let color = $('#modalColorInput').val();
            colorInput.val(color);
            imageInput.val('');
            previewBtn.css({
                'background-color': color,
                'background-image': 'none'
            });
        } else {
            let imgUrl = $('#modalImagePreview').attr('src');
            imageInput.val(imgUrl);
            previewBtn.css({
                'background-color': 'transparent',
                'background-image': `url('${imgUrl}')`
            });
        }
        closeSwatchModal();
        updatePreview();
    }

    function closeSwatchModal() {
        $('#swatchSettingsModal').fadeOut();
        editingRow = null;
    }

    function handleDropdownImageUpload(input) {
        const file = input.files[0];
        if (!file) return;

        let $wrapper = $(input).closest('.thumbnail-upload-wrapper');

        // Show Loader
        $wrapper.find('.thumbnail-loader').css('display', 'flex');

        const img = new Image();
        img.src = URL.createObjectURL(file);

        img.onload = function() {
            // if (img.width > 300 || img.height > 300) {
            //     showToast("Maximum allowed size is 300x300px. Please upload a smaller image.");
            //     input.value = '';
            //     return;
            // }

            let formData = new FormData();
            formData.append('swatch_image', file);
            formData.append('shop', '{{ $response['shop'] }}');

            $.ajax({
                url: "{{ route('option.upload') }}",
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },

                success: function(response) {

                    // Hide Loader
                    $wrapper.find('.thumbnail-loader').hide();

                    if (response.success) {
                        let cdnUrl = response.url;

                        $wrapper.find('.thumbnail-preview')
                            .attr('src', cdnUrl)
                            .show();

                        $wrapper.find('.upload-icon').hide();
                        $wrapper.find('.single-value-image').val(cdnUrl);

                    } else {
                        showToast("Upload failed: " + response.message, "error");
                    }
                },

                error: function(xhr) {

                    // Hide Loader
                    $wrapper.find('.thumbnail-loader').hide();

                    console.error(xhr.responseText);
                    showToast("Server error, please check logs.", "error");
                }
            });
        };
    }

    function getDateConfigHTML() {
        const days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        let html = `<div class="date-settings-container">
            <h6 class="fw-bold mb-3">Selectable Dates & Times</h6>
            <div class="day-header small text-muted mb-2 d-flex justify-content-between">
                <span>Day</span>
                <span>All Day</span>
                <span>Time Range</span>
            </div>`;

        days.forEach(day => {
            let dayKey = day.toLowerCase();
            html += `
            <div class="day-row">
                <div class="day-label">
                    <input type="checkbox" class="form-check-input date-day-check" data-day="${dayKey}" checked> ${day}
                </div>
                <div class="day-toggle">
                    <input type="checkbox" class="form-check-input date-allday-check" onchange="toggleTimeInputs(this)"> All day
                </div>
                <div class="time-inputs" style="visibility: visible;">
                    <input type="time" class="form-control-custom date-start" value="09:00">
                    <span>to</span>
                    <input type="time" class="form-control-custom date-end" value="18:00">
                </div>
            </div>`;
        });

        html += `</div>`;
        return html;
    }

    function toggleTimeInputs(checkbox) {
        let row = $(checkbox).closest('.day-row');
        if ($(checkbox).is(':checked')) {
            row.find('.time-inputs').css('visibility', 'hidden');
        } else {
            row.find('.time-inputs').css('visibility', 'visible');
        }
    }

    function getFileConfigHTML() {
        return `
        <div class="file-settings-container">

            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label-custom">Max quantity <span class="text-muted">ⓘ</span></label>
                    <input type="number" class="form-control-custom file-max-qty" value="1" min="1" max="10">
                </div>
                <div class="col-md-4">
                    <label class="form-label-custom">Price add-on</label>
                    <div class="price-input-group">
                        <span class="input-prefix">$</span>
                        <input type="number" class="form-control-custom input-w-prefix file-price" placeholder="0.00">
                    </div>
                </div>
            </div>

            <div class="file-type-restrictions">
                <label class="form-label-custom mb-2">File type restrictions</label>

                <div class="file-type-group">
                    <label class="file-type-option">
                        <input type="radio" name="file_type_rd_${Date.now()}" class="file-type-radio" value="all" checked>
                        <span>All files</span>
                    </label>

                    <label class="file-type-option">
                        <input type="radio" name="file_type_rd_${Date.now()}" class="file-type-radio" value="image">
                        <div>
                            <span>Image files</span>
                            <div class="file-type-desc">(jpeg, jpg, svg, png, tiff, tif)</div>
                        </div>
                    </label>

                    <label class="file-type-option">
                        <input type="radio" name="file_type_rd_${Date.now()}" class="file-type-radio" value="document">
                        <div>
                            <span>Document files</span>
                            <div class="file-type-desc">(pdf, doc, docx, html, htm, xls, xlsx, txt)</div>
                        </div>
                    </label>

                    <label class="file-type-option">
                        <input type="radio" name="file_type_rd_${Date.now()}" class="file-type-radio" value="custom">
                        <span>Custom files (extensions)</span>
                    </label>
                    <input type="text" class="form-control-custom mt-1 file-custom-ext" placeholder=".zip, .mp4" style="margin-left: 25px; width: 90%;">
                </div>
            </div>
        </div>`;
    }

    $(document).on('change', '.file-type-radio', function() {
        let container = $(this).closest('.file-type-group');
        if ($(this).val() === 'custom') {
            container.find('.file-custom-ext').slideDown();
        } else {
            container.find('.file-custom-ext').slideUp();
        }
    });

    function getParagraphConfigHTML(uniqueId) {
        return `
        <div class="paragraph-settings-container" style="background:#fff; padding:15px; border:1px solid #e1e3e5; border-radius:6px; margin-top:10px;">

            <div class="form-check mb-2">
                <input type="checkbox" class="form-check-input para-visible-app" id="vis_${uniqueId}">
                <label class="form-check-label text-muted" for="vis_${uniqueId}">Visible only in the app</label>
            </div>

            <div class="form-check mb-3">
                <input type="checkbox" class="form-check-input para-hide-label" id="hide_${uniqueId}">
                <label class="form-check-label text-muted" for="hide_${uniqueId}">Hide option name</label>
            </div>

            <label class="form-label-custom fw-bold">Content</label>
            <textarea id="${uniqueId}" class="para-content-input" placeholder="Enter your text here..."></textarea>
        </div>`;
    }

    function getPopupConfigHTML(uniqueId) {
        return `
        <div class="popup-settings-container" style="background:#fff; padding:15px; border:1px solid #e1e3e5; border-radius:6px; margin-top:10px;">

            <div class="form-check mb-2">
                <input type="checkbox" class="form-check-input popup-visible-app" id="vis_${uniqueId}">
                <label class="form-check-label text-muted" for="vis_${uniqueId}">Visible only in the app</label>
            </div>

            <div class="form-check mb-3">
                <input type="checkbox" class="form-check-input popup-hide-label" id="hide_${uniqueId}">
                <label class="form-check-label text-muted" for="hide_${uniqueId}">Hide option name</label>
            </div>
            <div class="mb-3">
                <label class="form-label-custom">Label on pop-up <span class="text-muted">(Clickable text)</span></label>
                <input type="text" class="form-control-custom popup-link-label" placeholder="e.g. Size Guide">
            </div>

            <label class="form-label-custom fw-bold">Content</label>
            <textarea id="${uniqueId}" class="popup-content-input" placeholder="Enter content here..."></textarea>
        </div>`;
    }

    function getDividerConfigHTML() {
        return `
        <div class="divider-settings-container" style="background:#fff; padding:15px; border:1px solid #e1e3e5; border-radius:6px; margin-top:10px;">

            <div class="form-check mb-3">
                <input type="checkbox" class="form-check-input div-visible-app" onchange="updatePreview()">
                <label class="form-check-label text-muted">Visible only in the app</label>
            </div>

            <div class="form-check mb-3">
                <input type="checkbox" class="form-check-input div-hide-label" checked disabled>
                <label class="form-check-label text-muted">Hide option name (Always hidden for divider)</label>
            </div>

            <div class="row align-items-end">
                <div class="col-md-4">
                    <label class="form-label-custom">Size (px)</label>
                    <input type="number" class="form-control-custom div-size" value="1" min="1" oninput="updatePreview()">
                </div>
                <div class="col-md-4">
                    <label class="form-label-custom">Color</label>
                    <div class="d-flex align-items-center gap-2">
                        <input type="color" class="form-control form-control-color div-color" value="#000000" style="width:100%;" oninput="updatePreview()">
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label-custom">Style</label>
                    <select class="form-select form-select-sm div-style" style="padding: 8px; font-size:14px;" onchange="updatePreview()">
                        <option value="solid">Solid</option>
                        <option value="dashed">Dashed</option>
                        <option value="dotted">Dotted</option>
                        <option value="double">Double</option>
                    </select>
                </div>
            </div>
        </div>`;
    }

    var currentBulkButton = null;

    function openBulkAddModal(btn) {
        currentBulkButton = btn;
        $('#bulkAddTextarea').val('');
        $('#bulkAddModal').fadeIn();
    }

    function closeBulkAddModal() {
        $('#bulkAddModal').fadeOut();
        currentBulkButton = null;
    }

    function saveBulkData() {
        if (!currentBulkButton) return;

        var text = $('#bulkAddTextarea').val();

        if (!text.trim()) {
            closeBulkAddModal();
            return;
        }

        var lines = text.split('\n');

        lines.forEach(function(line) {
            var cleanLine = line.trim();
            if (cleanLine !== '') {
                addValueRow(currentBulkButton, cleanLine);
            }
        });

        closeBulkAddModal();

        updatePreview();
    }
</script>

{{-- active status toggale js --}}
<script>
    $(document).on('change', '.status-toggle', function() {

        let $toggle = $(this);
        let optionSetId = $toggle.data('id');
        let status = $toggle.is(':checked') ? 1 : 0;

        $.ajax({
            url: "{{ route('options.status') }}",
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            data: {
                id: optionSetId,
                status: status
            },
            success: function() {

                let $row = $toggle.closest('tr');
                let newStatus = status === 1 ? 'active' : 'draft';

                // row status update
                $row.attr('data-status', newStatus);
                $row.data('status', newStatus);

                applyFilters();
                showToast('Status updated successfully', false);

            },
            error: function() {
                showToast('Failed to update status', true);
                setTimeout(function() {
                    location.reload();
                }, 1000);
            }
        });
    });
    $(document).ready(function() {
        applyFilters();
    });
</script>

</html>