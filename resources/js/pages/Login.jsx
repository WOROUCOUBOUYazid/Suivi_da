import { useState } from 'react';
import { Card, Form, Input, Button, Typography, Alert } from 'antd';
import { UserOutlined, LockOutlined } from '@ant-design/icons';
import { useNavigate, Navigate } from 'react-router-dom';
import { useAuth } from '../auth/AuthContext';

export default function Login() {
    const { login, user, chargement } = useAuth();
    const navigate = useNavigate();
    const [erreur, setErreur] = useState(null);
    const [enCours, setEnCours] = useState(false);

    if (!chargement && user) {
        return <Navigate to="/" replace />;
    }

    const onFinish = async ({ email, password }) => {
        setErreur(null);
        setEnCours(true);
        try {
            await login(email, password);
            navigate('/');
        } catch (e) {
            setErreur(e.response?.data?.message ?? 'Identifiants invalides.');
        } finally {
            setEnCours(false);
        }
    };

    return (
        <div style={{ minHeight: '100vh', display: 'flex', alignItems: 'center', justifyContent: 'center', background: '#f0f2f5' }}>
            <Card style={{ width: 380 }}>
                <div style={{ textAlign: 'center', marginBottom: 24 }}>
                    <Typography.Title level={3} style={{ marginBottom: 0 }}>Suivi des DA</Typography.Title>
                    <Typography.Text type="secondary">Connexion à votre espace</Typography.Text>
                </div>

                {erreur && <Alert type="error" message={erreur} showIcon style={{ marginBottom: 16 }} />}

                <Form layout="vertical" onFinish={onFinish} requiredMark={false}>
                    <Form.Item name="email" label="Email" rules={[{ required: true, type: 'email', message: 'Email requis' }]}>
                        <Input prefix={<UserOutlined />} placeholder="vous@entreprise.com" size="large" />
                    </Form.Item>
                    <Form.Item name="password" label="Mot de passe" rules={[{ required: true, message: 'Mot de passe requis' }]}>
                        <Input.Password prefix={<LockOutlined />} placeholder="••••••••" size="large" />
                    </Form.Item>
                    <Button type="primary" htmlType="submit" block size="large" loading={enCours}>
                        Se connecter
                    </Button>
                </Form>
            </Card>
        </div>
    );
}
