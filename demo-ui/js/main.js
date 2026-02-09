// DOM Elements
document.addEventListener('DOMContentLoaded', function() {
  // Tab functionality
  const tabButtons = document.querySelectorAll('.tab-button');
  const tabPanes = document.querySelectorAll('.tab-pane');
  
  // Products table
  const productsTable = document.getElementById('products-list');
  const selectAllCheckbox = document.getElementById('select-all');
  const searchInput = document.getElementById('search-input');
  const filterButton = document.querySelector('.filter-button');
  const filterMenu = document.querySelector('.filter-menu');
  const filterOptions = document.querySelectorAll('.filter-item input');
  const generateButton = document.querySelector('.generate-button');
  
  // Modals
  const addProductModal = document.getElementById('add-product-modal');
  const editProductModal = document.getElementById('edit-product-modal');
  const viewDescriptionModal = document.getElementById('view-description-modal');
  const generationModal = document.getElementById('generation-modal');
  const modalCloseButtons = document.querySelectorAll('.modal-close');
  const modalCancelButtons = document.querySelectorAll('.modal-cancel');
  
  // Add Product Form
  const addProductForm = {
    name: document.getElementById('product-name'),
    category: document.getElementById('product-category'),
    features: document.getElementById('product-features'),
    saveButton: addProductModal.querySelector('.modal-save')
  };
  
  // Edit Product Form
  const editProductForm = {
    name: document.getElementById('edit-product-name'),
    category: document.getElementById('edit-product-category'),
    features: document.getElementById('edit-product-features'),
    saveButton: editProductModal.querySelector('.modal-save')
  };
  
  // View Description Elements
  const descriptionContent = document.getElementById('description-content');
  const editDescriptionButton = viewDescriptionModal.querySelector('.modal-edit');
  
  // Generation Progress Elements
  const progressBar = document.getElementById('generation-progress-bar');
  const progressText = document.getElementById('generation-progress-text');
  const generationStatus = document.getElementById('generation-status');
  
  // Settings Form
  const settingsForm = {
    apiKey: document.getElementById('api-key'),
    domainName: document.getElementById('domain-name'),
    model: document.getElementById('model-selection'),
    textStyle: document.getElementById('text-style'),
    maxCharsProducts: document.getElementById('max-chars-products'),
    maxCharsDescriptions: document.getElementById('max-chars-descriptions'),
    maxCharsBlog: document.getElementById('max-chars-blog'),
    saveButton: document.querySelector('.settings-save-button'),
    testButton: document.querySelector('.primary-button'),
    disableButton: document.querySelector('.danger-button')
  };

  // Upgrade button
  const upgradeButton = document.querySelector('.upgrade-button');
  
  // Content Type Checkboxes
  const contentTypeCheckboxes = document.querySelectorAll('.content-type-checkbox');
  const optionCheckboxes = document.querySelectorAll('.option-checkbox');
  
  // Info Icons
  const infoIcons = document.querySelectorAll('.info-icon');
  
  // New Editor Elements
  const contentTypeSelect = document.getElementById('content-type-select');
  const keywordsInput = document.getElementById('keywords-input');
  const contentSearchInput = document.getElementById('content-search-input');
  const filterToggleButton = document.querySelector('.filter-toggle-button');
  const fieldCheckboxes = document.querySelector('.field-checkboxes');
  const fieldFilterCheckboxes = document.querySelectorAll('.field-checkbox input');
  const generationProgressSmall = document.getElementById('generation-progress-small');
  
  // State variables
  let products = [...productsData];
  let selectedProducts = new Set();
  let editingProductId = null;
  let filters = {
    generated: false,
    notGenerated: false
  };
  let currentContentType = 'product';
  let settings = {...settingsData};
  
  // Demo mode - no API key required
  const demoMode = true;
  
  // Initialize the app
  initializeApp();
  
  /**
   * Initialize the application
   */
  function initializeApp() {
    // Φόρτωση ρυθμίσεων από localStorage αν υπάρχουν
    if (typeof localStorage !== 'undefined') {
      const savedSettings = localStorage.getItem('pdgSettings');
      if (savedSettings) {
        try {
          settings = JSON.parse(savedSettings);
        } catch (e) {
          console.error('Error parsing saved settings:', e);
        }
      }
    }
    
    renderProductsTable();
    populateSettingsForm();
    setupEventListeners();
    
    // Show field checkboxes based on content type selection
    updateFieldCheckboxes(contentTypeSelect.value);
    
    // In demo mode, hide/show elements accordingly
    if (demoMode) {
      document.querySelectorAll('.api-key-required').forEach(el => {
        el.style.display = 'none';
      });
    }
  }
  
  /**
   * Set up all event listeners
   */
  function setupEventListeners() {
    // Tab navigation
    tabButtons.forEach(button => {
      button.addEventListener('click', () => {
        const tabName = button.getAttribute('data-tab');
        activateTab(tabName);
      });
    });
    
    // Table actions
    selectAllCheckbox.addEventListener('change', handleSelectAll);
    searchInput.addEventListener('input', handleSearch);
    filterButton.addEventListener('click', toggleFilterMenu);
    filterOptions.forEach(option => {
      option.addEventListener('change', handleFilterChange);
    });
    
    // Action buttons
    generateButton.addEventListener('click', handleBulkGenerate);
    
    // Upgrade button
    upgradeButton.addEventListener('click', () => {
      alert('This feature is not implemented in the demo. In a real application, this would redirect to a payment page.');
    });
    
    // Close modals
    modalCloseButtons.forEach(button => {
      button.addEventListener('click', () => closeAllModals());
    });
    
    modalCancelButtons.forEach(button => {
      button.addEventListener('click', () => closeAllModals());
    });
    
    // Form submissions
    addProductForm.saveButton.addEventListener('click', handleAddProduct);
    editProductForm.saveButton.addEventListener('click', handleUpdateProduct);
    settingsForm.saveButton.addEventListener('click', handleSaveSettings);
    settingsForm.testButton.addEventListener('click', handleTestConnection);
    settingsForm.disableButton.addEventListener('click', handleDisableAPI);
    
    // Content Type Checkboxes
    contentTypeCheckboxes.forEach(checkbox => {
      checkbox.addEventListener('change', handleContentTypeChange);
    });
    
    // Option Checkboxes
    document.querySelectorAll('.option-checkbox').forEach(checkbox => {
      checkbox.addEventListener('change', handleOptionChange);
    });
    
    // Info Icons
    infoIcons.forEach(icon => {
      icon.addEventListener('mouseenter', showTooltip);
      icon.addEventListener('mouseleave', hideTooltip);
    });
    
    // Outside click for filter menu
    document.addEventListener('click', (e) => {
      if (!filterButton.contains(e.target) && !filterMenu.contains(e.target)) {
        filterMenu.classList.add('hidden');
      }
    });
    
    // Content Type select
    contentTypeSelect.addEventListener('change', handleEditorContentTypeChange);
    
    // Filter Toggle Button
    filterToggleButton.addEventListener('click', function() {
      fieldCheckboxes.classList.toggle('show');
    });
    
    // Field checkboxes
    fieldFilterCheckboxes.forEach(checkbox => {
      checkbox.addEventListener('change', updateFieldVisibility);
    });
    
    // Click outside to close field checkboxes
    document.addEventListener('click', function(e) {
      if (!filterToggleButton.contains(e.target) && !fieldCheckboxes.contains(e.target)) {
        fieldCheckboxes.classList.remove('show');
      }
    });
    
    // Generation continue button
    document.getElementById('generation-continue-btn').addEventListener('click', () => {
      // Κλείνουμε το modal αλλά η διαδικασία συνεχίζεται στο παρασκήνιο
      document.getElementById('generation-modal').classList.remove('show');
    });
  }
  
  /**
   * Activate a tab
   */
  function activateTab(tabName) {
    // Update tab buttons
    tabButtons.forEach(button => {
      button.classList.remove('active');
      if (button.getAttribute('data-tab') === tabName) {
        button.classList.add('active');
      }
    });
    
    // Update tab content
    tabPanes.forEach(pane => {
      pane.classList.remove('active');
      if (pane.id === `${tabName}-tab`) {
        pane.classList.add('active');
      }
    });
  }
  
  /**
   * Render the products table
   */
  function renderProductsTable() {
    // Clear the table
    productsTable.innerHTML = '';
    
    // Filter products
    let filteredProducts = products;
    
    // Apply text search
    if (searchInput.value.trim()) {
      const searchTerm = searchInput.value.trim().toLowerCase();
      filteredProducts = filteredProducts.filter(product => 
        product.name.toLowerCase().includes(searchTerm) || 
        product.category.toLowerCase().includes(searchTerm)
      );
    }
    
    // Apply filters
    if (filters.generated && !filters.notGenerated) {
      filteredProducts = filteredProducts.filter(product => product.status === 'generated');
    } else if (!filters.generated && filters.notGenerated) {
      filteredProducts = filteredProducts.filter(product => product.status === 'pending');
    }
    
    // Αντιστοίχιση από dropdown content type σε settings content type
    let settingsContentType = mapContentTypeToSettings(currentContentType);
    
    // Πάρε τις επιλεγμένες επιλογές από τις ρυθμίσεις
    let enabledOptions = [];
    if (settings.contentTypes[settingsContentType] && settings.contentTypes[settingsContentType].enabled) {
      const contentTypeSettings = settings.contentTypes[settingsContentType];
      
      // Συλλέγουμε τις ενεργοποιημένες επιλογές εκτός από το 'name'
      for (const [option, isEnabled] of Object.entries(contentTypeSettings.options)) {
        if (option !== 'name' && isEnabled) {
          enabledOptions.push(option);
        }
      }
    } else {
      console.log(`Προειδοποίηση: Δεν βρέθηκαν ρυθμίσεις για τον τύπο περιεχομένου '${settingsContentType}' ή είναι απενεργοποιημένος.`);
    }
    
    // Render each product
    filteredProducts.forEach(product => {
      const row = document.createElement('tr');
      
      // Checkbox cell
      const checkboxCell = document.createElement('td');
      const checkbox = document.createElement('input');
      checkbox.type = 'checkbox';
      checkbox.checked = selectedProducts.has(product.id);
      checkbox.addEventListener('change', () => {
        if (checkbox.checked) {
          selectedProducts.add(product.id);
        } else {
          selectedProducts.delete(product.id);
        }
        updateSelectAllState();
      });
      checkboxCell.appendChild(checkbox);
      row.appendChild(checkboxCell);
      
      // Name cell
      const nameCell = document.createElement('td');
      nameCell.textContent = product.name;
      row.appendChild(nameCell);
      
      // Προσθήκη κελιών βάσει των ενεργοποιημένων επιλογών από τις ρυθμίσεις
      enabledOptions.forEach(option => {
        const cell = document.createElement('td');
        
        // Αντιστοίχιση από option στο κατάλληλο πεδίο του προϊόντος
        if (option === 'description') {
          if (product.description) {
            const shortDesc = product.description.substring(0, 150) + (product.description.length > 150 ? '...' : '');
            cell.textContent = shortDesc;
          } else {
            cell.innerHTML = '<span class="not-generated">Not generated</span>';
          }
        } else if (option === 'short-description') {
          // Δημιουργούμε μια σύντομη περιγραφή αν υπάρχει κανονική περιγραφή
          if (product.description) {
            cell.textContent = product.description.substring(0, 80) + '...';
          } else {
            cell.innerHTML = '<span class="not-generated">Not generated</span>';
          }
        } else if (option === 'seo-keyword' || option === 'keyword') {
          cell.textContent = product.category || 'N/A';
        } else if (option === 'seo-title' || option === 'title') {
          cell.textContent = product.name || 'N/A';
        } else if (option === 'seo-meta-description' || option === 'meta-description') {
          if (product.description) {
            cell.textContent = product.description.substring(0, 120) + '...';
          } else {
            cell.innerHTML = '<span class="not-generated">Not generated</span>';
          }
        } else if (option === 'image-alt' || option === 'alt-text' || option === 'image-alt-description') {
          cell.textContent = `Alt text for ${product.name}` || 'N/A';
        } else if (option === 'caption') {
          cell.textContent = product.name || 'N/A';
        } else {
          // Για άλλα πεδία που μπορεί να προστεθούν μελλοντικά
          cell.textContent = 'N/A';
        }
        
        row.appendChild(cell);
      });
      
      // Status cell
      const statusCell = document.createElement('td');
      const statusBadge = document.createElement('span');
      statusBadge.className = `status-badge ${product.status}`;
      statusBadge.textContent = product.status === 'generated' ? 'Generated' : 'Pending';
      statusCell.appendChild(statusBadge);
      row.appendChild(statusCell);
      
      // Actions cell
      const actionsCell = document.createElement('td');
      actionsCell.className = 'actions-cell';
      
      // View button (only if description exists)
      if (product.description) {
        const viewButton = document.createElement('button');
        viewButton.className = 'table-action-btn view-btn';
        viewButton.innerHTML = '<i class="fa-solid fa-eye"></i>';
        viewButton.addEventListener('click', () => handleViewDescription(product));
        actionsCell.appendChild(viewButton);
      }
      
      // Generate button
      const generateButton = document.createElement('button');
      generateButton.className = 'table-action-btn generate-btn';
      generateButton.innerHTML = '<i class="fa-solid fa-wand-magic-sparkles"></i>';
      generateButton.addEventListener('click', () => {
        selectedProducts.clear();
        selectedProducts.add(product.id);
        handleBulkGenerate();
      });
      actionsCell.appendChild(generateButton);
      
      // Edit button
      const editButton = document.createElement('button');
      editButton.className = 'table-action-btn edit-btn';
      editButton.innerHTML = '<i class="fa-solid fa-pen-to-square"></i>';
      editButton.addEventListener('click', () => handleEditProduct(product));
      actionsCell.appendChild(editButton);
      
      // Delete button
      const deleteButton = document.createElement('button');
      deleteButton.className = 'table-action-btn delete-btn';
      deleteButton.innerHTML = '<i class="fa-solid fa-trash"></i>';
      deleteButton.addEventListener('click', () => {
        if (confirm(`Are you sure you want to delete "${product.name}"?`)) {
          handleDeleteProduct(product.id);
        }
      });
      actionsCell.appendChild(deleteButton);
      
      row.appendChild(actionsCell);
      productsTable.appendChild(row);
    });
    
    // If no products, show message
    if (filteredProducts.length === 0) {
      const row = document.createElement('tr');
      const colSpan = 4 + enabledOptions.length; // Adjust colspan based on visible columns
      row.innerHTML = `
        <td colspan="${colSpan}" style="text-align: center; padding: 20px;">
          <p>No products found${searchInput.value ? ' matching "' + searchInput.value + '"' : ''}.</p>
        </td>
      `;
      productsTable.appendChild(row);
    }
  }
  
  /**
   * Handle select all checkbox
   */
  function handleSelectAll() {
    const isChecked = selectAllCheckbox.checked;
    
    // Update the visible checkboxes
    const checkboxes = productsTable.querySelectorAll('input[type="checkbox"]');
    checkboxes.forEach(checkbox => {
      checkbox.checked = isChecked;
    });
    
    // Update the selected products set
    selectedProducts.clear();
    if (isChecked) {
      products.forEach(product => {
        selectedProducts.add(product.id);
      });
    }
  }
  
  /**
   * Update the state of the select all checkbox
   */
  function updateSelectAllState() {
    const checkboxes = productsTable.querySelectorAll('input[type="checkbox"]');
    const checkedCount = productsTable.querySelectorAll('input[type="checkbox"]:checked').length;
    
    if (checkedCount === 0) {
      selectAllCheckbox.checked = false;
      selectAllCheckbox.indeterminate = false;
    } else if (checkedCount === checkboxes.length - 1) { // -1 to exclude the select all checkbox
      selectAllCheckbox.checked = true;
      selectAllCheckbox.indeterminate = false;
    } else {
      selectAllCheckbox.indeterminate = true;
    }
  }
  
  /**
   * Handle search input
   */
  function handleSearch() {
    renderProductsTable();
  }
  
  /**
   * Toggle the filter menu
   */
  function toggleFilterMenu() {
    filterMenu.classList.toggle('hidden');
  }
  
  /**
   * Handle filter changes
   */
  function handleFilterChange(e) {
    const filterName = e.target.name;
    
    if (filterName === 'filter-generated') {
      filters.generated = e.target.checked;
    } else if (filterName === 'filter-not-generated') {
      filters.notGenerated = e.target.checked;
    }
    
    renderProductsTable();
  }
  
  /**
   * Show a modal
   */
  function showModal(modal) {
    closeAllModals();
    modal.classList.add('show');
  }
  
  /**
   * Close all modals
   */
  function closeAllModals() {
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
      modal.classList.remove('show');
    });
  }
  
  /**
   * Handle adding a new product
   */
  function handleAddProduct() {
    const name = addProductForm.name.value.trim();
    const category = addProductForm.category.value;
    const featuresText = addProductForm.features.value.trim();
    
    if (!name || !category || !featuresText) {
      alert('Please fill in all fields');
      return;
    }
    
    const features = featuresText.split('\n').filter(f => f.trim() !== '');
    
    const newProduct = {
      id: Date.now(), // Use timestamp as id
      name,
      category: getCategoryLabel(category),
      status: 'pending',
      date: new Date().toISOString().slice(0, 10),
      features,
      description: ''
    };
    
    products.unshift(newProduct);
    renderProductsTable();
    
    // Reset form
    addProductForm.name.value = '';
    addProductForm.category.value = '';
    addProductForm.features.value = '';
    
    closeAllModals();
  }
  
  /**
   * Handle editing a product
   */
  function handleEditProduct(product) {
    editingProductId = product.id;
    
    // Fill the form
    editProductForm.name.value = product.name;
    editProductForm.category.value = getCategoryValue(product.category);
    editProductForm.features.value = product.features.join('\n');
    
    showModal(editProductModal);
  }
  
  /**
   * Handle updating a product
   */
  function handleUpdateProduct() {
    const name = editProductForm.name.value.trim();
    const category = editProductForm.category.value;
    const featuresText = editProductForm.features.value.trim();
    
    if (!name || !category || !featuresText) {
      alert('Please fill in all fields');
      return;
    }
    
    const features = featuresText.split('\n').filter(f => f.trim() !== '');
    
    // Find and update the product
    const productIndex = products.findIndex(p => p.id === editingProductId);
    if (productIndex !== -1) {
      // Mark as pending if the product details changed
      const product = products[productIndex];
      const statusChanged = 
        product.name !== name || 
        product.category !== getCategoryLabel(category) || 
        !arraysEqual(product.features, features);
      
      products[productIndex] = {
        ...product,
        name,
        category: getCategoryLabel(category),
        features,
        status: statusChanged ? 'pending' : product.status,
        description: statusChanged ? '' : product.description
      };
      
      renderProductsTable();
    }
    
    closeAllModals();
  }
  
  /**
   * Handle viewing a product description
   */
  function handleViewDescription(product) {
    // Display the description or a placeholder
    if (product.status === 'generated' && product.description) {
      descriptionContent.innerHTML = `
        <h3>${product.name}</h3>
        <p class="product-category">${product.category}</p>
        <div class="description-text">${formatDescription(product.description)}</div>
        <div class="features-list">
          <h4>Key Features:</h4>
          <ul>
            ${product.features.map(feature => `<li>${feature}</li>`).join('')}
          </ul>
        </div>
      `;
    } else {
      descriptionContent.innerHTML = `
        <h3>${product.name}</h3>
        <p class="product-category">${product.category}</p>
        <div class="description-text">No description generated yet. Click "Generate" to create a description.</div>
        <div class="features-list">
          <h4>Key Features:</h4>
          <ul>
            ${product.features.map(feature => `<li>${feature}</li>`).join('')}
          </ul>
        </div>
      `;
    }
    
    showModal(viewDescriptionModal);
  }
  
  /**
   * Handle bulk generation
   */
  function handleBulkGenerate() {
    if (selectedProducts.size === 0) {
      alert('Please select at least one product');
      return;
    }
    
    // Skip API key check in demo mode
    if (!demoMode && !settings.apiKey) {
      alert('Please set your OpenAI API key in Settings first');
      activateTab('settings');
      return;
    }
    
    showModal(generationModal);
    simulateGeneration();
  }
  
  /**
   * Simulate generation process
   * (In a real application, this would call the OpenAI API)
   */
  function simulateGeneration() {
    const totalProducts = selectedProducts.size;
    let processed = 0;
    
    // Reset progress bars
    progressBar.style.width = '0%';
    progressText.textContent = '0%';
    generationStatus.textContent = 'Preparing to generate descriptions...';
    
    // Reset the small progress bar too
    const smallProgressBar = document.querySelector('.progress-bar-small');
    const smallProgressPercent = document.querySelector('.progress-percent');
    smallProgressBar.style.width = '0%';
    smallProgressPercent.textContent = '0%';
    
    // Get the selected products
    const productsToGenerate = products.filter(product => 
      selectedProducts.has(product.id)
    );
    
    // Process each product with delay to simulate API calls
    function processNext() {
      if (processed < productsToGenerate.length) {
        const product = productsToGenerate[processed];
        generationStatus.textContent = `Generating description for "${product.name}"...`;
        
        // Simulate API call with timeout
        setTimeout(() => {
          // Generate a fake description based on features
          const description = simulateDescriptionGeneration(product);
          
          // Update the product
          const productIndex = products.findIndex(p => p.id === product.id);
          if (productIndex !== -1) {
            products[productIndex] = {
              ...product,
              status: 'generated',
              description
            };
          }
          
          processed++;
          const progress = Math.round((processed / totalProducts) * 100);
          
          // Update both progress bars
          progressBar.style.width = `${progress}%`;
          progressText.textContent = `${progress}%`;
          smallProgressBar.style.width = `${progress}%`;
          smallProgressPercent.textContent = `${progress}%`;
          
          processNext();
        }, 1500);
      } else {
        // All done
        generationStatus.textContent = 'All descriptions generated successfully! An email has been sent to your address.';
        
        // Don't automatically close the modal anymore
        // Allow the user to click "Continue Working"
        
        // Still update the table
        renderProductsTable();
        selectedProducts.clear();
        updateSelectAllState();
      }
    }
    
    // Start processing
    processNext();
  }
  
  /**
   * Simulate description generation
   * (In a real app, this would call OpenAI API)
   */
  function simulateDescriptionGeneration(product) {
    // Get keywords from input
    const keywords = keywordsInput.value.split(',').map(k => k.trim()).filter(k => k);
    const keywordsText = keywords.length > 0 
      ? `With its ${keywords.join(', ')} style, ` 
      : '';
    
    // This is just a simple template. In a real app, this would use the OpenAI API
    const templates = [
      `Discover our premium ${product.name}, designed to exceed your expectations with exceptional quality and performance. ${keywordsText}${product.features[0] || ''}. This ${product.category.toLowerCase()} product also features ${product.features[1] || ''} and ${product.features[2] || ''}. Perfect for everyday use, our ${product.name} combines innovation with reliability to deliver an outstanding experience.`,
      
      `Introducing the remarkable ${product.name}, a standout product in our ${product.category} collection. ${keywordsText}With its ${product.features[0] || ''}, you'll experience unparalleled performance. We've also incorporated ${product.features[1] || ''} for added convenience. Crafted with attention to detail, this product represents the perfect balance of form and function.`,
      
      `Elevate your experience with our exceptional ${product.name}. ${keywordsText}This innovative ${product.category.toLowerCase()} product features ${product.features[0] || ''} for optimal performance. We've also included ${product.features[1] || ''} and ${product.features[2] || ''} to ensure complete satisfaction. Whether for personal or professional use, our ${product.name} offers reliable, high-quality performance every time.`
    ];
    
    // Randomly select a template
    const template = templates[Math.floor(Math.random() * templates.length)];
    return template;
  }
  
  /**
   * Format description for display
   */
  function formatDescription(description) {
    return description.replace(/\n/g, '<br>');
  }
  
  /**
   * Populate the settings form
   */
  function populateSettingsForm() {
    // Βασικές ρυθμίσεις
    settingsForm.apiKey.value = settings.apiKey || '';
    settingsForm.domainName.value = settings.domainName || '';
    settingsForm.model.value = settings.model || 'gpt-3.5-turbo';
    settingsForm.textStyle.value = settings.textStyle || 'professional';
    settingsForm.maxCharsProducts.value = settings.maxCharsProducts || '250';
    settingsForm.maxCharsDescriptions.value = settings.maxCharsDescriptions || '250';
    settingsForm.maxCharsBlog.value = settings.maxCharsBlog || '250';
    
    // Επιλογές περιεχομένου
    Object.keys(settings.contentTypes).forEach(type => {
      const checkbox = document.getElementById(`content-${type}`);
      if (checkbox) {
        checkbox.checked = settings.contentTypes[type].enabled;
        
        // Επιλογές για αυτόν τον τύπο περιεχομένου
        if (settings.contentTypes[type].options) {
          Object.keys(settings.contentTypes[type].options).forEach(option => {
            const optionCheckbox = document.getElementById(`${type}-${option}`);
            if (optionCheckbox) {
              optionCheckbox.checked = settings.contentTypes[type].options[option];
              
              // Αν το parent checkbox δεν είναι επιλεγμένο, απενεργοποιούμε το option
              if (!checkbox.checked) {
                optionCheckbox.disabled = true;
              } else {
                optionCheckbox.disabled = false;
              }
            }
          });
        }
      }
    });
  }
  
  /**
   * Handle save settings
   */
  function handleSaveSettings() {
    // Ενημέρωση του settings object από το UI
    updateSettingsFromUI();
    
    // Έλεγχος για κάθε ενεργοποιημένο τύπο περιεχομένου αν έχει τουλάχιστον μία επιλογή
    let noOptionsSelected = false;
    let contentTypeWithoutOptions = '';
    
    Object.keys(settings.contentTypes).forEach(type => {
      if (settings.contentTypes[type].enabled) {
        // Ελέγχουμε αν υπάρχει τουλάχιστον μία επιλογή ενεργοποιημένη
        const hasEnabledOption = Object.values(settings.contentTypes[type].options).some(value => value === true);
        
        if (!hasEnabledOption) {
          noOptionsSelected = true;
          contentTypeWithoutOptions = type;
        }
      }
    });
    
    // Αν υπάρχει τύπος περιεχομένου χωρίς επιλογές, εμφανίζουμε μήνυμα προειδοποίησης
    if (noOptionsSelected) {
      const contentTypeName = {
        'image': 'Image',
        'products': 'Products',
        'product-category': 'Product Category',
        'post': 'Blog Post',
        'pages': 'Pages'
      }[contentTypeWithoutOptions] || contentTypeWithoutOptions;
      
      // Εμφάνιση ειδοποίησης με τη χρήση της νέας συνάρτησης
      showCustomAlert({
        title: 'Απαιτείται επιλογή',
        message: `
          <p>Για τον τύπο περιεχομένου <strong>${contentTypeName}</strong> πρέπει να επιλέξετε τουλάχιστον μία από τις διαθέσιμες επιλογές.</p>
          <p>Κάθε ενεργός τύπος περιεχομένου χρειάζεται τουλάχιστον μία ενεργή επιλογή για να λειτουργήσει σωστά.</p>
        `,
        buttonText: 'Κατάλαβα',
        isSuccess: false
      });
      
      return; // Διακόπτουμε την αποθήκευση
    }
    
    // Πρόσθετες ρυθμίσεις
    settings.apiKey = settingsForm.apiKey.value;
    settings.domainName = settingsForm.domainName.value;
    settings.model = settingsForm.model.value;
    settings.textStyle = settingsForm.textStyle.value;
    settings.maxCharsProducts = settingsForm.maxCharsProducts.value;
    settings.maxCharsDescriptions = settingsForm.maxCharsDescriptions.value;
    settings.maxCharsBlog = settingsForm.maxCharsBlog.value;
    
    console.log('Αποθηκεύονται οι ρυθμίσεις:', settings);
    
    // Αποθήκευση σε localStorage (προαιρετικό)
    if (typeof localStorage !== 'undefined') {
      localStorage.setItem('pdgSettings', JSON.stringify(settings));
      console.log('Οι ρυθμίσεις αποθηκεύτηκαν στο localStorage');
    }
    
    // Ενημέρωση των πεδίων στον editor με βάση τις νέες ρυθμίσεις
    updateFieldCheckboxes(currentContentType);
    
    // Αλλαγή προς την καρτέλα editor για να δει ο χρήστης τις αλλαγές
    activateTab('editor');
    
    // Εμφάνιση μηνύματος επιτυχίας
    showCustomAlert({
      title: 'Επιτυχία',
      message: '<p>Οι ρυθμίσεις αποθηκεύτηκαν με επιτυχία!</p>',
      buttonText: 'OK',
      isSuccess: true
    });
  }
  
  /**
   * Update settings object from UI values without saving
   */
  function updateSettingsFromUI() {
    // Ενημερώνουμε το settings object από τα UI elements
    contentTypeCheckboxes.forEach(checkbox => {
      const type = checkbox.id.replace('content-', '');
      
      if (!settings.contentTypes[type]) {
        settings.contentTypes[type] = { enabled: false, options: {} };
      }
      
      settings.contentTypes[type].enabled = checkbox.checked;
      
      // Επιλογές για αυτόν τον τύπο περιεχομένου
      const optionCheckboxes = document.querySelectorAll(`.option-checkbox[id^="${type}-"]`);
      optionCheckboxes.forEach(option => {
        const optionName = option.id.replace(`${type}-`, '');
        settings.contentTypes[type].options[optionName] = option.checked;
      });
    });
  }
  
  /**
   * Handle content type change
   */
  function handleContentTypeChange(e) {
    const contentType = e.target;
    const type = contentType.id.replace('content-', '');
    const options = document.querySelectorAll(`.option-checkbox[id^="${type}-"]`);
    
    options.forEach(option => {
      option.disabled = !contentType.checked;
      if (!contentType.checked) {
        option.checked = false;
      }
    });
  }
  
  /**
   * Handle test connection
   */
  function handleTestConnection() {
    const apiKey = settingsForm.apiKey.value;
    if (!apiKey) {
      alert('Παρακαλώ εισάγετε το API key σας πρώτα.');
      return;
    }
    
    // Simulate API test
    alert('Δοκιμή σύνδεσης επιτυχής! Το API key σας είναι έγκυρο.');
  }
  
  /**
   * Handle disable API
   */
  function handleDisableAPI() {
    if (confirm('Είστε βέβαιοι ότι θέλετε να απενεργοποιήσετε τη σύνδεση API;')) {
      settingsForm.apiKey.value = '';
      settingsForm.domainName.value = '';
      alert('Η σύνδεση API απενεργοποιήθηκε.');
    }
  }
  
  /**
   * Show tooltip on info icon hover
   */
  function showTooltip(e) {
    const tooltip = document.createElement('div');
    tooltip.className = 'tooltip';
    tooltip.textContent = e.target.getAttribute('title');
    
    // Position the tooltip
    const rect = e.target.getBoundingClientRect();
    tooltip.style.position = 'absolute';
    tooltip.style.top = `${rect.bottom + 5}px`;
    tooltip.style.left = `${rect.left}px`;
    tooltip.style.backgroundColor = 'rgba(0, 0, 0, 0.8)';
    tooltip.style.color = 'white';
    tooltip.style.padding = '5px 10px';
    tooltip.style.borderRadius = '4px';
    tooltip.style.fontSize = '12px';
    tooltip.style.zIndex = '1000';
    
    document.body.appendChild(tooltip);
    e.target.tooltip = tooltip;
  }
  
  /**
   * Hide tooltip on info icon mouse leave
   */
  function hideTooltip(e) {
    if (e.target.tooltip) {
      e.target.tooltip.remove();
      e.target.tooltip = null;
    }
  }
  
  /**
   * Get category label from value
   */
  function getCategoryLabel(value) {
    const category = categories.find(c => c.value === value);
    return category ? category.label : '';
  }
  
  /**
   * Get category value from label
   */
  function getCategoryValue(label) {
    const category = categories.find(c => c.label === label);
    return category ? category.value : '';
  }
  
  /**
   * Compare two arrays for equality
   */
  function arraysEqual(a, b) {
    if (a.length !== b.length) return false;
    return a.every((val, index) => val === b[index]);
  }
  
  /**
   * Συνάρτηση βοηθός για την αντιστοίχιση τύπων περιεχομένου
   */
  function mapContentTypeToSettings(editorContentType) {
    // Αντιστοίχιση των τιμών του dropdown (value στο HTML) στα κλειδιά του αντικειμένου contentTypes στις ρυθμίσεις
    const contentTypeMap = {
      'product': 'products', // Η τιμή στο dropdown είναι 'product' αλλά στις ρυθμίσεις είναι 'products'
      'product-category': 'product-category',
      'post': 'post',
      'page': 'pages', // Η τιμή στο dropdown είναι 'page' αλλά στις ρυθμίσεις είναι 'pages'
      'image': 'image'
    };
    
    return contentTypeMap[editorContentType] || editorContentType;
  }
  
  /**
   * Content Type select change handler
   */
  function handleEditorContentTypeChange(e) {
    currentContentType = e.target.value;
    updateFieldCheckboxes(currentContentType);
  }
  
  /**
   * Update field checkboxes based on content type
   */
  function updateFieldCheckboxes(contentType) {
    // Hide all field checkboxes first
    fieldFilterCheckboxes.forEach(checkbox => {
      checkbox.parentElement.style.display = 'none';
      checkbox.checked = false; // Αρχικά απενεργοποιούμε όλα τα checkboxes
    });
    
    // Αντιστοίχιση από dropdown content type σε settings content type
    let settingsContentType = mapContentTypeToSettings(contentType);
    
    console.log(`Ενημέρωση πεδίων για τύπο: ${contentType} -> ${settingsContentType}`);
    
    // Show only relevant fields based on the selected content type and settings
    if (settings.contentTypes[settingsContentType] && settings.contentTypes[settingsContentType].enabled) {
      const contentTypeSettings = settings.contentTypes[settingsContentType];
      const enabledOptions = contentTypeSettings.options;
      
      console.log(`Επιλογές για ${settingsContentType}:`, enabledOptions);
      
      // Update checkboxes visibility based on settings
      for (const [option, isEnabled] of Object.entries(enabledOptions)) {
        // Αντιστοίχιση από ονόματα επιλογών σε ονόματα πεδίων
        let fieldName = option;
        
        // Αντιστοίχιση ονομάτων από settings σε field names
        if (option === 'alt-text') fieldName = 'image-alt';
        if (option === 'seo-meta-description') fieldName = 'meta-description';
        if (option === 'image-alt-description') fieldName = 'image-alt';
        
        const checkbox = document.getElementById(`field-${fieldName}`);
        if (checkbox && isEnabled) {
          checkbox.parentElement.style.display = 'flex';
          checkbox.checked = isEnabled;  // Ενημερώνουμε το checkbox ανάλογα με τη ρύθμιση
        } else if (isEnabled) {
          console.log(`Προειδοποίηση: Δεν βρέθηκε το checkbox για το πεδίο '${fieldName}'`);
        }
      }
    } else {
      console.log(`Προειδοποίηση: Δεν βρέθηκαν ρυθμίσεις για τον τύπο περιεχομένου '${settingsContentType}' ή είναι απενεργοποιημένος`);
    }
    
    // Ενημέρωση των στηλών στον πίνακα απευθείας από τις ρυθμίσεις
    updateTableColumns(settingsContentType);
  }
  
  /**
   * Update the visibility of fields in the table based on checkboxes
   */
  function updateFieldVisibility() {
    // Αυτή η συνάρτηση δεν είναι πλέον απαραίτητη, όλα γίνονται στο updateTableColumns
    renderProductsTable();
  }
  
  /**
   * Update table columns based on the selected content type and checked fields
   */
  function updateTableColumns(contentType) {
    const tableHeader = document.querySelector('.products-table thead tr');
    
    // Keep only the first, second, and last two columns (checkbox, name, status, actions)
    const checkboxCell = tableHeader.children[0].cloneNode(true);
    const nameCell = tableHeader.children[1].cloneNode(true);
    const statusCell = tableHeader.querySelector('th:nth-last-child(2)').cloneNode(true);
    const actionsCell = tableHeader.querySelector('th:last-child').cloneNode(true);
    
    // Clear the header row
    tableHeader.innerHTML = '';
    
    // Add the standard columns
    tableHeader.appendChild(checkboxCell);
    tableHeader.appendChild(nameCell);
    
    // Αντί να παίρνουμε τα πεδία από τα checkboxes του editor, τα παίρνουμε απευθείας από τις ρυθμίσεις
    const settingsContentType = contentType || currentContentType;
    
    console.log(`Ενημέρωση στηλών για τύπο: ${settingsContentType}`);
    
    if (settings.contentTypes[settingsContentType] && settings.contentTypes[settingsContentType].enabled) {
      const contentTypeSettings = settings.contentTypes[settingsContentType];
      const enabledOptions = contentTypeSettings.options;
      
      console.log(`Επιλογές για στήλες (${settingsContentType}):`, enabledOptions);
      
      // Προσθέτουμε στήλες για κάθε επιλεγμένη επιλογή στις ρυθμίσεις, εκτός από το name που είναι ήδη στάνταρ στήλη
      for (const [option, isEnabled] of Object.entries(enabledOptions)) {
        if (option !== 'name' && isEnabled) {
          const th = document.createElement('th');
          
          // Μετατροπή του option ID σε user-friendly όνομα
          let fieldName = option.split('-').map(word => 
            word.charAt(0).toUpperCase() + word.slice(1)
          ).join(' ');
          
          th.textContent = fieldName;
          tableHeader.appendChild(th);
        }
      }
    } else {
      console.log(`Προειδοποίηση: Δεν βρέθηκαν ρυθμίσεις για τις στήλες του τύπου '${settingsContentType}' ή είναι απενεργοποιημένος`);
    }
    
    // Add the status and actions columns
    tableHeader.appendChild(statusCell);
    tableHeader.appendChild(actionsCell);
    
    // Refresh the table data
    renderProductsTable();
  }
  
  /**
   * Handle delete product
   */
  function handleDeleteProduct(productId) {
    // Αφαίρεση του προϊόντος από τον πίνακα products
    products = products.filter(p => p.id !== productId);
    
    // Αφαίρεση του προϊόντος από τα επιλεγμένα
    if (selectedProducts.has(productId)) {
      selectedProducts.delete(productId);
      updateSelectAllState();
    }
    
    // Ενημέρωση του πίνακα
    renderProductsTable();
  }
  
  /**
   * Ελέγχει αν υπάρχουν επιλεγμένες επιλογές για έναν τύπο περιεχομένου
   */
  function checkContentTypeOptionsSelected(type) {
    const optionCheckboxes = document.querySelectorAll(`.option-checkbox[id^="${type}-"]`);
    const selectedOptions = Array.from(optionCheckboxes).filter(checkbox => checkbox.checked);
    return selectedOptions.length > 0;
  }
  
  /**
   * Χειριστής για αλλαγή σε option checkbox
   */
  function handleOptionChange(e) {
    const optionCheckbox = e.target;
    const parentSection = optionCheckbox.closest('.content-type-section');
    const contentTypeCheckbox = parentSection.querySelector('.content-type-checkbox');
    const contentType = contentTypeCheckbox.id.replace('content-', '');
    
    // Αν απενεργοποιεί μια επιλογή, ελέγχουμε αν είναι η τελευταία
    if (!optionCheckbox.checked) {
      // Ελέγχουμε αν υπάρχουν άλλες επιλεγμένες επιλογές
      const optionCheckboxes = parentSection.querySelectorAll('.option-checkbox');
      const selectedOptions = Array.from(optionCheckboxes).filter(checkbox => checkbox.checked && checkbox !== optionCheckbox);
      
      if (selectedOptions.length === 0 && contentTypeCheckbox.checked) {
        const contentTypeName = {
          'image': 'Image',
          'products': 'Products',
          'product-category': 'Product Category',
          'post': 'Blog Post',
          'pages': 'Pages'
        }[contentType] || contentType;
        
        // Εμφάνιση προειδοποίησης με τη νέα συνάρτηση
        showCustomAlert({
          title: 'Τουλάχιστον μία επιλογή',
          message: `
            <p>Πρέπει να υπάρχει τουλάχιστον μία επιλεγμένη επιλογή για τον τύπο περιεχομένου <strong>${contentTypeName}</strong>.</p>
            <p>Αν θέλετε να απενεργοποιήσετε όλες τις επιλογές, παρακαλώ απενεργοποιήστε πρώτα ολόκληρο τον τύπο περιεχομένου.</p>
          `,
          buttonText: 'Κατάλαβα',
          isSuccess: false
        });
        
        // Επαναφέρουμε το checkbox στην επιλεγμένη κατάσταση
        optionCheckbox.checked = true;
        return;
      }
    }
    
    // Αν αλλάξει ένα option, ενημερώνουμε τις ρυθμίσεις αλλά δεν αποθηκεύουμε ακόμα
    updateSettingsFromUI();
    
    // Αν το περιεχόμενο είναι το τρέχον επιλεγμένο, ενημερώνουμε και τις στήλες
    if (contentType === mapContentTypeToSettings(currentContentType)) {
      updateFieldCheckboxes(currentContentType);
    }
  }
  
  /**
   * Εμφανίζει ένα προσαρμοσμένο παράθυρο ειδοποίησης
   * @param {Object} options - Οι παράμετροι για το παράθυρο ειδοποίησης
   * @param {string} options.title - Ο τίτλος του παραθύρου
   * @param {string} options.message - Το κύριο μήνυμα (HTML)
   * @param {string} options.buttonText - Το κείμενο του κουμπιού
   * @param {boolean} options.isSuccess - Αν είναι ειδοποίηση επιτυχίας (πράσινο θέμα) ή προειδοποίησης (κόκκινο θέμα)
   * @param {Function} options.callback - Προαιρετικό callback μετά το κλείσιμο
   */
  function showCustomAlert(options) {
    const { title, message, buttonText = 'OK', isSuccess = false, callback = null } = options;
    
    // Δημιουργία του modal div
    const modalDiv = document.createElement('div');
    modalDiv.className = 'custom-alert-modal';
    
    // Επιλογή του εικονιδίου με βάση τον τύπο ειδοποίησης
    const icon = isSuccess ? 'fa-check-circle' : 'fa-triangle-exclamation';
    
    // Δημιουργία του περιεχομένου
    modalDiv.innerHTML = `
      <div class="custom-alert-content">
        <div class="custom-alert-header ${isSuccess ? 'success-header' : ''}">
          <h2><i class="fa-solid ${icon}"></i> ${title}</h2>
        </div>
        <div class="custom-alert-body">
          ${message}
        </div>
        <div class="custom-alert-footer">
          <button class="custom-alert-button ${isSuccess ? 'success-button' : ''}">${buttonText}</button>
        </div>
      </div>
    `;
    
    // Προσθήκη στο σώμα του εγγράφου
    document.body.appendChild(modalDiv);
    
    // Δημιουργία των στυλ
    const style = document.createElement('style');
    style.textContent = `
      .custom-alert-modal {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 1000;
      }
      .custom-alert-content {
        background-color: white;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        width: 400px;
        max-width: 90%;
        overflow: hidden;
      }
      .custom-alert-header {
        background-color: #f8d7da;
        color: #721c24;
        padding: 15px;
        border-bottom: 1px solid #f5c6cb;
      }
      .custom-alert-header h2 {
        margin: 0;
        font-size: 18px;
        display: flex;
        align-items: center;
      }
      .custom-alert-header i {
        margin-right: 10px;
        font-size: 20px;
      }
      .custom-alert-body {
        padding: 20px;
        color: #333;
      }
      .custom-alert-footer {
        padding: 15px;
        text-align: right;
        border-top: 1px solid #e9ecef;
      }
      .custom-alert-button {
        background-color: #6354b2;
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 4px;
        cursor: pointer;
        font-weight: bold;
      }
      .custom-alert-button:hover {
        background-color: #503d9e;
      }
      .success-header {
        background-color: #e9e4f5 !important;
        color: #503d9e !important;
        border-color: #d1c7ee !important;
      }
      .success-button {
        background-color: #6354b2 !important;
      }
      .success-button:hover {
        background-color: #503d9e !important;
      }
    `;
    
    document.head.appendChild(style);
    
    // Προσθήκη event listener στο κουμπί
    const button = modalDiv.querySelector('.custom-alert-button');
    button.addEventListener('click', () => {
      document.body.removeChild(modalDiv);
      document.head.removeChild(style);
      
      // Καλούμε το callback αν έχει οριστεί
      if (callback) {
        callback();
      }
    });
  }
}); 