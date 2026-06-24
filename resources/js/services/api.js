import axios from 'axios';

const STORAGE_KEY = 'suivi_da_token';

export const tokenStore = {
    get: () => localStorage.getItem(STORAGE_KEY),
    set: (token) => localStorage.setItem(STORAGE_KEY, token),
    clear: () => localStorage.removeItem(STORAGE_KEY),
};

const api = axios.create({
    baseURL: '/api',
    headers: {
        Accept: 'application/json',
    },
});

// Injecte le token Bearer sur chaque requête.
api.interceptors.request.use((config) => {
    const token = tokenStore.get();
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
});

// Déconnexion automatique sur 401.
api.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response?.status === 401) {
            tokenStore.clear();
            if (window.location.pathname !== '/login') {
                window.location.href = '/login';
            }
        }
        return Promise.reject(error);
    },
);

export default api;
