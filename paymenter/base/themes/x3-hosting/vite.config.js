import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import path from 'node:path';
export default defineConfig({
 plugins: [laravel({
 input: [path.resolve(import.meta.dirname, 'js/app.js'), path.resolve(import.meta.dirname, 'js/easymde-entry.js'), path.resolve(import.meta.dirname, 'css/app.css')],
 buildDirectory: 'x3-hosting', hotFile: 'public/x3-hosting.hot', refresh: true,
 }), tailwindcss()],
});
