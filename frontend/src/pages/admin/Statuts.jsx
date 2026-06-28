import { useState } from 'react';
import { Table, Button, Tag, Space, Typography, Modal, Form, Input, InputNumber, Switch, App as AntApp, Popconfirm } from 'antd';
import { PlusOutlined } from '@ant-design/icons';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import api from '../../services/api';

// Transforme un libellé en slug (minuscules, sans accents, tirets).
function slugify(valeur) {
    return (valeur ?? '')
        .normalize('NFKD')
        .replace(/[^\x00-\x7F]/g, '')
        .toLowerCase()
        .trim()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');
}

export default function Statuts() {
    const { message } = AntApp.useApp();
    const queryClient = useQueryClient();
    const [modal, setModal] = useState(false);
    const [edite, setEdite] = useState(null);
    const [form] = Form.useForm();

    const { data, isLoading } = useQuery({
        queryKey: ['statuts'],
        queryFn: async () => (await api.get('/statuts')).data,
    });

    const statuts = data?.data ?? [];

    const enregistrer = useMutation({
        mutationFn: (v) => (edite ? api.put(`/statuts/${edite.id}`, v) : api.post('/statuts', v)),
        onSuccess: () => {
            message.success('Statut enregistré');
            setModal(false);
            form.resetFields();
            setEdite(null);
            queryClient.invalidateQueries({ queryKey: ['statuts'] });
        },
        onError: (e) => message.error(e.response?.data?.message ?? 'Erreur lors de l\'enregistrement'),
    });

    const supprimer = useMutation({
        mutationFn: (id) => api.delete(`/statuts/${id}`),
        onSuccess: () => { message.success('Statut supprimé'); queryClient.invalidateQueries({ queryKey: ['statuts'] }); },
        onError: (e) => message.error(e.response?.data?.message ?? 'Suppression impossible'),
    });

    const ouvrir = (statut = null) => {
        setEdite(statut);
        form.resetFields();
        if (statut) {
            form.setFieldsValue(statut);
        } else {
            // Proposer le prochain ordre disponible.
            const prochainOrdre = statuts.reduce((max, s) => Math.max(max, s.ordre ?? 0), 0) + 1;
            form.setFieldsValue({ ordre: prochainOrdre, est_cloture: false, couleur: '#3B82F6' });
        }
        setModal(true);
    };

    // En création, génère le slug à partir du libellé tant qu'il n'a pas été modifié à la main.
    const onValuesChange = (changed) => {
        if (!edite && changed.libelle !== undefined && !form.isFieldTouched('slug')) {
            form.setFieldsValue({ slug: slugify(changed.libelle) });
        }
    };

    const colonnes = [
        { title: 'Ordre', dataIndex: 'ordre', width: 80, sorter: (a, b) => a.ordre - b.ordre, defaultSortOrder: 'ascend' },
        { title: 'Libellé', dataIndex: 'libelle' },
        { title: 'Slug', dataIndex: 'slug', render: (v) => <Typography.Text code>{v}</Typography.Text> },
        {
            title: 'Couleur',
            dataIndex: 'couleur',
            render: (v) => v ? (
                <Space>
                    <span style={{ display: 'inline-block', width: 16, height: 16, borderRadius: 4, background: v, border: '1px solid #d9d9d9' }} />
                    <Typography.Text type="secondary">{v}</Typography.Text>
                </Space>
            ) : '—',
        },
        { title: 'Clôture', dataIndex: 'est_cloture', render: (v) => <Tag color={v ? 'green' : 'default'}>{v ? 'Oui' : 'Non'}</Tag> },
        {
            title: 'Actions',
            width: 200,
            render: (_, s) => (
                <Space>
                    <Button size="small" onClick={() => ouvrir(s)}>Modifier</Button>
                    <Popconfirm
                        title="Supprimer ce statut ?"
                        description="Impossible si des DA l'utilisent."
                        onConfirm={() => supprimer.mutate(s.id)}
                    >
                        <Button size="small" danger>Supprimer</Button>
                    </Popconfirm>
                </Space>
            ),
        },
    ];

    return (
        <div>
            <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: 16 }}>
                <Typography.Title level={3} style={{ margin: 0 }}>Statuts</Typography.Title>
                <Button type="primary" icon={<PlusOutlined />} onClick={() => ouvrir()}>Nouveau statut</Button>
            </div>

            <Table rowKey="id" loading={isLoading} columns={colonnes} dataSource={statuts} pagination={false} />

            <Modal
                title={edite ? 'Modifier un statut' : 'Nouveau statut'}
                open={modal}
                onCancel={() => { setModal(false); setEdite(null); }}
                onOk={() => form.submit()}
                confirmLoading={enregistrer.isPending}
                okText="Enregistrer"
            >
                <Form form={form} layout="vertical" onFinish={(v) => enregistrer.mutate(v)} onValuesChange={onValuesChange}>
                    <Form.Item name="libelle" label="Libellé" rules={[{ required: true, message: 'Libellé requis' }]}>
                        <Input placeholder="Ex. Attente de devis" />
                    </Form.Item>
                    <Form.Item
                        name="slug"
                        label="Slug (identifiant technique)"
                        rules={[
                            { required: true, message: 'Slug requis' },
                            { pattern: /^[a-z0-9-]+$/, message: 'Minuscules, chiffres et tirets uniquement' },
                        ]}
                    >
                        <Input placeholder="attente-devis" />
                    </Form.Item>
                    <Form.Item name="ordre" label="Ordre" rules={[{ required: true, message: 'Ordre requis' }]}>
                        <InputNumber min={1} style={{ width: '100%' }} />
                    </Form.Item>
                    <Form.Item name="couleur" label="Couleur (hex)" rules={[{ pattern: /^#[0-9A-Fa-f]{6}$/, message: 'Format #RRGGBB' }]}>
                        <Input placeholder="#3B82F6" />
                    </Form.Item>
                    <Form.Item name="description" label="Description">
                        <Input.TextArea rows={2} />
                    </Form.Item>
                    <Form.Item name="est_cloture" label="Statut de clôture" valuePropName="checked" tooltip="Une DA atteignant ce statut est considérée comme clôturée.">
                        <Switch />
                    </Form.Item>
                </Form>
            </Modal>
        </div>
    );
}
