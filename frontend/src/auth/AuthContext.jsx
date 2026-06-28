import { createContext, useContext, useEffect, useState, useCallback } from 'react';
import api, { tokenStore } from '../services/api';

const AuthContext = createContext(null);

export function AuthProvider({ children }) {
    const [user, setUser] = useState(null);
    const [chargement, setChargement] = useState(true);

    const chargerProfil = useCallback(async () => {
        if (!tokenStore.get()) {
            setChargement(false);
            return;
        }
        try {
            const { data } = await api.get('/me');
            setUser(data.data ?? data);
        } catch {
            tokenStore.clear();
        } finally {
            setChargement(false);
        }
    }, []);

    useEffect(() => {
        chargerProfil();
    }, [chargerProfil]);

    const login = async (email, password) => {
        const { data } = await api.post('/login', { email, password });
        tokenStore.set(data.token);
        setUser(data.user.data ?? data.user);
        return data.user;
    };

    const logout = async () => {
        try {
            await api.post('/logout');
        } catch {
            // ignore
        }
        tokenStore.clear();
        setUser(null);
    };

    // Vérifie une permission applicative.
    const can = (permission) => Boolean(user?.permissions?.includes(permission));
    const estAdmin = () => can('view all da');

    return (
        <AuthContext.Provider value={{ user, chargement, login, logout, can, estAdmin }}>
            {children}
        </AuthContext.Provider>
    );
}

export function useAuth() {
    const ctx = useContext(AuthContext);
    if (!ctx) {
        throw new Error('useAuth doit être utilisé dans AuthProvider');
    }
    return ctx;
}
