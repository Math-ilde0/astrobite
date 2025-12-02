// Live search dropdown for header search bar
document.addEventListener('DOMContentLoaded', function() {
	const searchInput = document.getElementById('nav-search-input');
	const dropdown = document.getElementById('nav-search-dropdown');
	const form = document.getElementById('nav-search-form');
	if (!searchInput || !dropdown || !form) return;

	let lastQuery = '';
	let activeIndex = -1;
	let results = [];

	function clearDropdown() {
		dropdown.innerHTML = '';
		dropdown.style.display = 'none';
		results = [];
		activeIndex = -1;
	}

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

	// Keyboard navigation
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

	// Mouse click on dropdown item
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

	// Hide dropdown on blur (with small delay for click)
	searchInput.addEventListener('blur', function() {
		setTimeout(clearDropdown, 120);
	});

	// Prevent form submit if dropdown is open and something is selected
	form.addEventListener('submit', function(e) {
		if (dropdown.style.display !== 'none' && activeIndex >= 0 && results[activeIndex]) {
			window.location.href = `${form.getAttribute('data-base')}/product.php?id=${results[activeIndex].product_id}`;
			clearDropdown();
			e.preventDefault();
		}
	});
});
