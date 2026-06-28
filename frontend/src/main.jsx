import React from 'react';
import { createRoot } from 'react-dom/client';
import { BrowserRouter } from 'react-router-dom';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { ConfigProvider, App as AntApp } from 'antd';
import frFR from 'antd/locale/fr_FR';
import dayjs from 'dayjs';
import 'dayjs/locale/fr';
import { AuthProvider } from './auth/AuthContext';
import App from './App';
import './index.css';

dayjs.locale('fr');

const queryClient = new QueryClient({
    defaultOptions: {
        queries: { refetchOnWindowFocus: false, retry: 1 },
    },
});

createRoot(document.getElementById('app')).render(
    <React.StrictMode>
        <ConfigProvider locale={frFR} theme={{ token: { colorPrimary: '#2563eb' } }}>
            <AntApp>
                <QueryClientProvider client={queryClient}>
                    <BrowserRouter>
                        <AuthProvider>
                            <App />
                        </AuthProvider>
                    </BrowserRouter>
                </QueryClientProvider>
            </AntApp>
        </ConfigProvider>
    </React.StrictMode>,
);
