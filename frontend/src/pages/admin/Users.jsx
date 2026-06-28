import { useState } from 'react';
import { Table, Button, Tag, Space, Typography, Modal, Form, Input, Select, Switch, App as AntApp, Popconfirm } from 'antd';
import { PlusOutlined } from '@ant-design/icons';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import api from '../../services/api';

export default function Users() {
    const { message } = AntApp.useApp();
    const queryClient = useQueryClient();
    const [modal, setModal] = useState(false);
    const [edite, setEdite] = useState(null);
    const [form] = Form.useForm();

    const { data, isLoading } = useQuery({
        queryKey: ['users'],
        queryFn: async () => (await api.get('/users')).data,
    });

    const enregistrer = useMutation({
        mutationFn: (v) => (edite ? api.put(`/users/${edite.id}`, v) : api.post('/users', v)),
        onSuccess: () => {
            message.success('Utilisateur enregistré');
            setModal(false);
            form.resetFields();
            setEdite(null);
            queryClient.invalidateQueries({ queryKey: ['users'] });
        },
        onError: (e) => message.error(e.response?.data?.message ?? 'Erreur'),
    });

    const desactiver = useMutation({
        mutationFn: (id) => api.delete(`/users/${id}`),
        onSuccess: () => { message.success('Utilisateur désactivé'); queryClient.invalidateQueries({ queryKey: ['users'] }); },
    });

    const ouvrir = (user = null) => {
        setEdite(user);
        form.resetFields();
        if (user) {
            form.setFieldsValue({ ...user, role: user.roles?.[0] });
        }
        setModal(true);
    };

    const colonnes = [
        { title: 'Nom', render: (_, u) => u.nom_complet },
        { title: 'Email', dataIndex: 'email' },
        { title: 'Poste', dataIndex: 'poste' },
        { title: 'Connexion', dataIndex: 'type_connexion', render: (v) => <Tag>{v}</Tag> },
        { title: 'Rôle', dataIndex: 'roles', render: (r) => (r ?? []).map((x) => <Tag color="blue" key={x}>{x}</Tag>) },
        { title: 'Actif', dataIndex: 'actif', render: (v) => <Tag color={v ? 'green' : 'red'}>{v ? 'Oui' : 'Non'}</Tag> },
        {
            title: 'Actions',
            render: (_, u) => (
                <Space>
                    <Button size="small" onClick={() => ouvrir(u)}>Modifier</Button>
                    <Popconfirm title="Désactiver ?" onConfirm={() => desactiver.mutate(u.id)}>
                        <Button size="small" danger>Désactiver</Button>
                    </Popconfirm>
                </Space>
            ),
        },
    ];

    return (
        <div>
            <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: 16 }}>
                <Typography.Title level={3} style={{ margin: 0 }}>Utilisateurs</Typography.Title>
                <Button type="primary" icon={<PlusOutlined />} onClick={() => ouvrir()}>Nouvel utilisateur</Button>
            </div>

            <Table rowKey="id" loading={isLoading} columns={colonnes} dataSource={data?.data ?? []} />

            <Modal
                title={edite ? 'Modifier un utilisateur' : 'Nouvel utilisateur'}
                open={modal}
                onCancel={() => { setModal(false); setEdite(null); }}
                onOk={() => form.submit()}
                confirmLoading={enregistrer.isPending}
                okText="Enregistrer"
            >
                <Form form={form} layout="vertical" onFinish={(v) => enregistrer.mutate(v)} initialValues={{ type_connexion: 'sql', actif: true, role: 'Utilisateur' }}>
                    <Form.Item name="nom" label="Nom" rules={[{ required: true }]}><Input /></Form.Item>
                    <Form.Item name="prenom" label="Prénom" rules={[{ required: true }]}><Input /></Form.Item>
                    <Form.Item name="email" label="Email" rules={[{ required: true, type: 'email' }]}><Input /></Form.Item>
                    <Form.Item name="poste" label="Poste"><Input /></Form.Item>
                    <Form.Item name="type_connexion" label="Type de connexion" rules={[{ required: true }]}>
                        <Select options={[{ value: 'sql', label: 'SQL (email/mot de passe)' }, { value: 'windows', label: 'Windows (Active Directory)' }]} />
                    </Form.Item>
                    <Form.Item name="password" label={edite ? 'Nouveau mot de passe (laisser vide pour conserver)' : 'Mot de passe'}>
                        <Input.Password />
                    </Form.Item>
                    <Form.Item name="role" label="Rôle" rules={[{ required: true }]}>
                        <Select options={[{ value: 'Utilisateur', label: 'Utilisateur' }, { value: 'Administrateur', label: 'Administrateur' }]} />
                    </Form.Item>
                    <Form.Item name="actif" label="Actif" valuePropName="checked"><Switch /></Form.Item>
                </Form>
            </Modal>
        </div>
    );
}
