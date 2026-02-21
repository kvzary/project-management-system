// Fix Filament Alpine.js sidebar store initialization
// This fixes the "Cannot read properties of undefined (reading 'isOpen')" error

// Clear any null values from localStorage that might cause issues
(() => {
	const storedCollapsedGroups = localStorage.getItem('collapsedGroups');
	const storedIsOpen = localStorage.getItem('isOpen');

	// If collapsedGroups is null or 'null' string, set it to empty array
	if (storedCollapsedGroups === null || storedCollapsedGroups === 'null') {
		localStorage.setItem('collapsedGroups', JSON.stringify([]));
	}

	// If isOpen is null or 'null' string, set it to true
	if (storedIsOpen === null || storedIsOpen === 'null') {
		localStorage.setItem('isOpen', 'true');
	}
})();

// Intercept Alpine.js initialization to fix the sidebar store
document.addEventListener('alpine:init', () => {
	// Wait for the sidebar store to be registered
	const checkStore = setInterval(() => {
		if (window.Alpine && window.Alpine.store('sidebar')) {
			const sidebar = window.Alpine.store('sidebar');

			// Ensure collapsedGroups is always an array
			if (sidebar.collapsedGroups === null || sidebar.collapsedGroups === undefined) {
				sidebar.collapsedGroups = [];
			}

			clearInterval(checkStore);
		}
	}, 10);

	// Stop checking after 2 seconds to prevent infinite loop
	setTimeout(() => clearInterval(checkStore), 2000);
});
