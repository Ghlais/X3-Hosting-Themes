export function createComparison(products, labels) {
    return {
        products, selected: [], message: '', missing: labels.missing,
        get items() { return this.products.filter(item => this.selected.includes(item.id)); },
        has(id) { return this.selected.includes(String(id)); },
        toggle(id) {
            id = String(id); this.message = '';
            if (this.has(id)) { this.selected = this.selected.filter(value => value !== id); return; }
            if (!this.products.some(item => item.id === id)) return;
            if (this.selected.length >= 3) { this.message = labels.limit; return; }
            this.selected.push(id);
        },
        clear() { this.selected = []; this.message = ''; },
        openComparison() {
            if (this.selected.length < 2) { this.message = labels.need; return; }
            this.message = ''; this.$refs.comparison.showModal();
        },
        destroy() { this.$refs.comparison?.close(); }
    };
}
