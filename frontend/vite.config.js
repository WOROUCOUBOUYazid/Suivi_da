import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

// Frontend autonome (SPA) : aucun couplage avec Laravel.
// L'URL de l'API est fournie via la variable d'environnement VITE_API_URL.
export default defineConfig({
    plugins: [react()],
    server: {
        port: 5173,
        host: true,
    },
});
