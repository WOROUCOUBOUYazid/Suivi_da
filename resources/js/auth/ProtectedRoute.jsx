import { Navigate } from 'react-router-dom';
import { Spin } from 'antd';
import { useAuth } from './AuthContext';

export default function ProtectedRoute({ children, permission }) {
    const { user, chargement, can } = useAuth();

    if (chargement) {
        return (
            <div style={{ display: 'flex', justifyContent: 'center', alignItems: 'center', height: '100vh' }}>
                <Spin size="large" />
            </div>
        );
    }

    if (!user) {
        return <Navigate to="/login" replace />;
    }

    if (permission && !can(permission)) {
        return <Navigate to="/" replace />;
    }

    return children;
}
