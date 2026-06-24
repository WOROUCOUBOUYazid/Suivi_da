import { Tabs, Table, InputNumber, Switch, Button, Typography, App as AntApp, Form, Input, Space } from 'antd';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import api from '../../services/api';

function ConfigurationRelances() {
    const { message } = AntApp.useApp();
    const queryClient = useQueryClient();

    const { data, isLoading } = useQuery({
        queryKey: ['configuration-relances'],
        queryFn: async () => (await api.get('/configuration-relances')).data.data,
    });

    const maj = useMutation({
        mutationFn: ({ statut_id, ...v }) => api.put(`/configuration-relances/${statut_id}`, v),
        onSuccess: () => { message.success('Configuration enregistrée'); queryClient.invalidateQueries({ queryKey: ['configuration-relances'] }); },
        onError: () => message.error('Erreur'),
    });

    const colonnes = [
        { title: 'Statut', dataIndex: 'statut' },
        {
            title: '1re relance (jours)', dataIndex: 'delai_premiere_relance_jours',
            render: (v, r) => <InputNumber min={0} defaultValue={v ?? 0} onChange={(val) => (r._premiere = val)} />,
        },
        {
            title: 'Relances suivantes (jours)', dataIndex: 'delai_relance_suivante_jours',
            render: (v, r) => <InputNumber min={0} defaultValue={v ?? 0} onChange={(val) => (r._suivante = val)} />,
        },
        {
            title: 'Actif', dataIndex: 'actif',
            render: (v, r) => <Switch defaultChecked={v} onChange={(val) => (r._actif = val)} />,
        },
        {
            title: 'Action',
            render: (_, r) => (
                <Button size="small" type="primary" onClick={() => maj.mutate({
                    statut_id: r.statut_id,
                    delai_premiere_relance_jours: r._premiere ?? r.delai_premiere_relance_jours ?? 0,
                    delai_relance_suivante_jours: r._suivante ?? r.delai_relance_suivante_jours ?? 0,
                    actif: r._actif ?? r.actif ?? false,
                })}>Enregistrer</Button>
            ),
        },
    ];

    return <Table rowKey="statut_id" loading={isLoading} columns={colonnes} dataSource={data ?? []} pagination={false} />;
}

function ParametresApp() {
    const { message } = AntApp.useApp();
    const queryClient = useQueryClient();
    const [form] = Form.useForm();

    const { data } = useQuery({
        queryKey: ['parametres'],
        queryFn: async () => (await api.get('/parametres')).data.data,
    });

    const tous = data ? Object.values(data).flat() : [];

    const maj = useMutation({
        mutationFn: (valeurs) => api.put('/parametres', {
            parametres: Object.entries(valeurs).map(([cle, valeur]) => ({ cle, valeur: String(valeur ?? '') })),
        }),
        onSuccess: () => { message.success('Paramètres enregistrés'); queryClient.invalidateQueries({ queryKey: ['parametres'] }); },
        onError: () => message.error('Erreur'),
    });

    return (
        <Form
            form={form}
            layout="vertical"
            style={{ maxWidth: 600 }}
            key={tous.map((p) => p.cle).join()}
            initialValues={Object.fromEntries(tous.map((p) => [p.cle, p.valeur]))}
            onFinish={(v) => maj.mutate(v)}
        >
            {tous.map((p) => (
                <Form.Item key={p.cle} name={p.cle} label={p.description ?? p.cle}>
                    <Input />
                </Form.Item>
            ))}
            <Button type="primary" htmlType="submit" loading={maj.isPending}>Enregistrer</Button>
        </Form>
    );
}

export default function Parametres() {
    return (
        <div>
            <Typography.Title level={3}>Paramètres</Typography.Title>
            <Tabs
                items={[
                    { key: 'relances', label: 'Délais de relance', children: <ConfigurationRelances /> },
                    { key: 'app', label: 'Paramètres généraux', children: <ParametresApp /> },
                ]}
            />
        </div>
    );
}
