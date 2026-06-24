import { Routes, Route } from 'react-router-dom';
import ProtectedRoute from './auth/ProtectedRoute';
import AppLayout from './components/AppLayout';
import Login from './pages/Login';
import Dashboard from './pages/Dashboard';
import DemandesList from './pages/DemandesList';
import DemandeForm from './pages/DemandeForm';
import DemandeDetail from './pages/DemandeDetail';
import Users from './pages/admin/Users';
import Parametres from './pages/admin/Parametres';
import Logs from './pages/admin/Logs';

export default function App() {
    return (
        <Routes>
            <Route path="/login" element={<Login />} />

            <Route
                element={
                    <ProtectedRoute>
                        <AppLayout />
                    </ProtectedRoute>
                }
            >
                <Route path="/" element={<Dashboard />} />
                <Route path="/demandes" element={<DemandesList />} />
                <Route path="/demandes/nouvelle" element={<DemandeForm />} />
                <Route path="/demandes/:id" element={<DemandeDetail />} />
                <Route path="/demandes/:id/modifier" element={<DemandeForm />} />

                <Route
                    path="/admin/utilisateurs"
                    element={
                        <ProtectedRoute permission="manage users">
                            <Users />
                        </ProtectedRoute>
                    }
                />
                <Route
                    path="/admin/parametres"
                    element={
                        <ProtectedRoute permission="manage settings">
                            <Parametres />
                        </ProtectedRoute>
                    }
                />
                <Route
                    path="/admin/logs"
                    element={
                        <ProtectedRoute permission="manage settings">
                            <Logs />
                        </ProtectedRoute>
                    }
                />
            </Route>
        </Routes>
    );
}
