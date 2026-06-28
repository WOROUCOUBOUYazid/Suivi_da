import { useEffect } from 'react';
import { Card, Form, Input, InputNumber, DatePicker, Button, Typography, Space, App as AntApp, Spin } from 'antd';
import { useQuery, useMutation } from '@tanstack/react-query';
import { useParams, useNavigate } from 'react-router-dom';
import dayjs from 'dayjs';
import api from '../services/api';

export default function DemandeForm() {
    const { id } = useParams();
    const edition = Boolean(id);
    const navigate = useNavigate();
    const { message } = AntApp.useApp();
    const [form] = Form.useForm();

    const { data: da, isLoading } = useQuery({
        queryKey: ['demande', id],
        queryFn: async () => (await api.get(`/demandes-achats/${id}`)).data.data,
        enabled: edition,
    });

    useEffect(() => {
        if (da) {
            form.setFieldsValue({
                ...da,
                date_creation_reelle: da.date_creation_reelle ? dayjs(da.date_creation_reelle) : null,
                date_estimee_action: da.date_estimee_action ? dayjs(da.date_estimee_action) : null,
            });
        }
    }, [da, form]);

    const enregistrer = useMutation({
        mutationFn: (valeurs) => {
            const payload = {
                ...valeurs,
                date_creation_reelle: valeurs.date_creation_reelle?.format('YYYY-MM-DD'),
                date_estimee_action: valeurs.date_estimee_action?.format('YYYY-MM-DD'),
            };
            return edition
                ? api.put(`/demandes-achats/${id}`, payload)
                : api.post('/demandes-achats', payload);
        },
        onSuccess: ({ data }) => {
            message.success(edition ? 'DA mise à jour' : 'DA créée');
            navigate(`/demandes/${data.data.id}`);
        },
        onError: (e) => {
            const erreurs = e.response?.data?.errors;
            if (erreurs) {
                form.setFields(Object.entries(erreurs).map(([name, msgs]) => ({ name, errors: msgs })));
            } else {
                message.error(e.response?.data?.message ?? 'Erreur');
            }
        },
    });

    if (edition && isLoading) {
        return <Spin size="large" />;
    }

    return (
        <Card>
            <Typography.Title level={3}>{edition ? `Modifier ${da?.numero_da ?? ''}` : 'Nouvelle demande d\'achat'}</Typography.Title>
            <Form form={form} layout="vertical" onFinish={(v) => enregistrer.mutate(v)} style={{ maxWidth: 700 }}>
                <Form.Item name="numero_da" label="Numéro DA" rules={[
                    { required: true, message: 'Numéro requis' },
                    { pattern: /^DA_\d{7}$/, message: 'Format attendu : DA_0000001' },
                ]}>
                    <Input placeholder="DA_0000001" disabled={edition} />
                </Form.Item>
                <Form.Item name="designation" label="Désignation" rules={[{ required: true }]}>
                    <Input />
                </Form.Item>
                <Form.Item name="affectation" label="Affectation" rules={[{ required: true }]}>
                    <Input />
                </Form.Item>
                <Form.Item name="quantite" label="Quantité" rules={[{ required: true }]}>
                    <InputNumber min={0} style={{ width: '100%' }} />
                </Form.Item>
                <Form.Item name="problematique" label="Problématique" rules={[{ required: true }]}>
                    <Input.TextArea rows={3} />
                </Form.Item>
                <Form.Item name="apport_solution" label="Solution proposée" rules={[{ required: true }]}>
                    <Input.TextArea rows={3} />
                </Form.Item>
                <Form.Item name="existant" label="Existant">
                    <Input.TextArea rows={2} />
                </Form.Item>
                <Form.Item name="date_creation_reelle" label="Date de création réelle" rules={[{ required: true }]}>
                    <DatePicker format="DD/MM/YYYY" style={{ width: '100%' }} />
                </Form.Item>

                <Space>
                    <Button type="primary" htmlType="submit" loading={enregistrer.isPending}>Enregistrer</Button>
                    <Button onClick={() => navigate(-1)}>Annuler</Button>
                </Space>
            </Form>
        </Card>
    );
}
