/**
 * ajax-search.js - Live Product Search with AJAX
 * 
 * Provides real-time product search functionality in header:
 * - Live dropdown suggestions while typing (min 2 characters)
 * - Keyboard navigation (arrow keys, Enter, Escape)
 * - Mouse click to select product
 * - Fuzzy matching from backend FULLTEXT search
 * 
 * Backend Endpoint: products.php?ajax=search&q=QUERY
 * Returns: JSON array of product objects with name, price, image1, product_id
 * Dependencies: HTML elements with IDs: nav-search-input, nav-search-dropdown, nav-search-form
 */

document.addEventListener('DOMContentLoaded', function() {
	// ========== DOM ELEMENT REFERENCES ========== 
	const searchInput = document.getElementById('nav-search-input');
	const dropdown = document.getElementById('nav-search-dropdown');
	const form = document.getElementById('nav-search-form');
	if (!searchInput || !dropdown || !form) return;

	// ========== STATE VARIABLES ========== 
	let lastQuery = '';              // Store last search query (unused, kept for potential future use)
	let activeIndex = -1;            // Currently highlighted result (-1 = none selected)
	let results = [];                // Array of search result objects from backend

	// ========== CLEAR DROPDOWN ========== 
	// Hide dropdown and reset state (called on blur, Escape, search < 2 chars)
	function clearDropdown() {
		dropdown.innerHTML = '';
		dropdown.style.display = 'none';
		results = [];
		activeIndex = -1;
	}

	// ========== RENDER DROPDOWN ========== 
	// Display search results as clickable items with product thumbnail, name, price
	// Highlights activeIndex with 'active' class for keyboard navigation
	function renderDropdown(items) {
		if (!items.length) {
			clearDropdown();
			return;
		}
		dropdown.innerHTML = items.map((item, i) => `
			<div class="nav-search-item${i === activeIndex ? ' active' : ''}" data-index="${i}" tabindex="-1">
				<img src="${item.image1}" alt="${item.name}" class="nav-search-thumb" />
				<span class="nav-search-name">${item.name}</span>
				<span class="nav-search-price">${parseFloat(item.price).toFixed(2)}$</span>
			</div>
		`).join('');
		dropdown.style.display = 'block';
	}

	// ========== SEARCH INPUT EVENT (LIVE SEARCH) ========== 
	// Fetch results from backend when user types (min 2 characters, debounced per input)
	// Resets active selection on new search
	searchInput.addEventListener('input', function(e) {
		const q = searchInput.value.trim();
		if (q.length < 2) {
			clearDropdown();
			return;
		}
		lastQuery = q;
		fetch(`${form.getAttribute('data-base')}/products.php?ajax=search&q=` + encodeURIComponent(q))
			.then(r => r.json())
			.then(data => {
				results = data;
				activeIndex = -1;
				renderDropdown(results);
			})
			.catch(() => clearDropdown());
	});

	// ========== KEYBOARD NAVIGATION IN DROPDOWN ========== 
	// Arrow Down: Move to next result (wraps to beginning)
	// Arrow Up: Move to previous result (wraps to end)
	// Enter: Navigate to selected product
	// Escape: Close dropdown
	searchInput.addEventListener('keydown', function(e) {
		if (!results.length || dropdown.style.display === 'none') return;
		if (e.key === 'ArrowDown') {
			e.preventDefault();
			activeIndex = (activeIndex + 1) % results.length;
			renderDropdown(results);
		} else if (e.key === 'ArrowUp') {
			e.preventDefault();
			activeIndex = (activeIndex - 1 + results.length) % results.length;
			renderDropdown(results);
		} else if (e.key === 'Enter') {
			if (activeIndex >= 0 && results[activeIndex]) {
				window.location.href = `${form.getAttribute('data-base')}/product.php?id=${results[activeIndex].product_id}`;
				clearDropdown();
				e.preventDefault();
			}
		} else if (e.key === 'Escape') {
			clearDropdown();
		}
	});

	// ========== MOUSE CLICK ON RESULT ========== 
	// Navigate to product page when user clicks a dropdown item
	// Uses mousedown instead of click to handle before blur event
	dropdown.addEventListener('mousedown', function(e) {
		const item = e.target.closest('.nav-search-item');
		if (item) {
			const idx = parseInt(item.getAttribute('data-index'), 10);
			if (results[idx]) {
				window.location.href = `${form.getAttribute('data-base')}/product.php?id=${results[idx].product_id}`;
				clearDropdown();
				e.preventDefault();
			}
		}
	});

	// ========== HIDE DROPDOWN ON BLUR ========== 
	// Close dropdown when user clicks outside or tabs away (120ms delay allows click to register)
	searchInput.addEventListener('blur', function() {
		setTimeout(clearDropdown, 120);
	});

	// ========== FORM SUBMIT HANDLER ========== 
	// Intercept form submission: if item selected in dropdown, navigate to product
	// Otherwise allows normal form submission for full search page
	form.addEventListener('submit', function(e) {
		if (dropdown.style.display !== 'none' && activeIndex >= 0 && results[activeIndex]) {
			window.location.href = `${form.getAttribute('data-base')}/product.php?id=${results[activeIndex].product_id}`;
			clearDropdown();
			e.preventDefault();
		}
	});
});
