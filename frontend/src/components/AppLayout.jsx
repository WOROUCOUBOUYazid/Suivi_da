import { Layout, Menu, Dropdown, Avatar, Typography } from 'antd';
import {
    DashboardOutlined,
    FileTextOutlined,
    TeamOutlined,
    SettingOutlined,
    HistoryOutlined,
    TagsOutlined,
    LogoutOutlined,
    UserOutlined,
} from '@ant-design/icons';
import { Outlet, useNavigate, useLocation } from 'react-router-dom';
import { useAuth } from '../auth/AuthContext';

const { Header, Sider, Content } = Layout;

export default function AppLayout() {
    const { user, logout, can } = useAuth();
    const navigate = useNavigate();
    const location = useLocation();

    const items = [
        { key: '/', icon: <DashboardOutlined />, label: 'Tableau de bord' },
        { key: '/demandes', icon: <FileTextOutlined />, label: "Demandes d'achat" },
    ];

    if (can('manage users')) {
        items.push({ key: '/admin/utilisateurs', icon: <TeamOutlined />, label: 'Utilisateurs' });
    }
    if (can('manage settings')) {
        items.push({ key: '/admin/statuts', icon: <TagsOutlined />, label: 'Statuts' });
        items.push({ key: '/admin/parametres', icon: <SettingOutlined />, label: 'Paramètres' });
        items.push({ key: '/admin/logs', icon: <HistoryOutlined />, label: 'Journaux' });
    }

    // Sélection : on prend la route de base la plus longue qui matche.
    const selected = items
        .map((i) => i.key)
        .filter((k) => (k === '/' ? location.pathname === '/' : location.pathname.startsWith(k)))
        .sort((a, b) => b.length - a.length)
        .slice(0, 1);

    const menuUtilisateur = {
        items: [
            { key: 'logout', icon: <LogoutOutlined />, label: 'Déconnexion' },
        ],
        onClick: async () => {
            await logout();
            navigate('/login');
        },
    };

    return (
        <Layout style={{ minHeight: '100vh' }}>
            <Sider breakpoint="lg" collapsedWidth="0" theme="dark">
                <div style={{ color: '#fff', textAlign: 'center', padding: '16px', fontWeight: 'bold', fontSize: 16 }}>
                    Suivi DA
                </div>
                <Menu
                    theme="dark"
                    mode="inline"
                    selectedKeys={selected}
                    items={items}
                    onClick={({ key }) => navigate(key)}
                />
            </Sider>
            <Layout>
                <Header style={{ background: '#fff', display: 'flex', justifyContent: 'flex-end', alignItems: 'center', paddingInline: 24 }}>
                    <Dropdown menu={menuUtilisateur} placement="bottomRight">
                        <span style={{ cursor: 'pointer' }}>
                            <Avatar icon={<UserOutlined />} style={{ marginRight: 8 }} />
                            <Typography.Text>{user?.nom_complet}</Typography.Text>
                        </span>
                    </Dropdown>
                </Header>
                <Content style={{ margin: 24 }}>
                    <Outlet />
                </Content>
            </Layout>
        </Layout>
    );
}
