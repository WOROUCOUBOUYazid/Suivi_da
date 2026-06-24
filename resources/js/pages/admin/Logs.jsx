import { useState } from 'react';
import { Table, Typography, Space, Select, DatePicker, Button, Tag } from 'antd';
import { DownloadOutlined } from '@ant-design/icons';
import { useQuery } from '@tanstack/react-query';
import dayjs from 'dayjs';
import api from '../../services/api';

const ACTIONS = [
    'connexion', 'deconnexion', 'da_creation', 'da_modification', 'da_suppression',
    'da_changement_statut', 'da_cloture', 'commentaire_ajout', 'notification_envoi',
    'relance_envoi', 'parametre_modification', 'utilisateur_gestion',
];

export default function Logs() {
    const [params, setParams] = useState({ page: 1, par_page: 25 });

    const { data, isLoading } = useQuery({
        queryKey: ['logs', params],
        queryFn: async () => (await api.get('/logs', { params })).data,
    });

    const exporter = async () => {
        const res = await api.get('/logs/export', { params, responseType: 'blob' });
        const url = window.URL.createObjectURL(new Blob([res.data]));
        const a = document.createElement('a');
        a.href = url;
        a.download = `logs_${dayjs().format('YYYYMMDD_HHmmss')}.csv`;
        a.click();
        window.URL.revokeObjectURL(url);
    };

    const colonnes = [
        { title: 'Date', dataIndex: 'created_at', render: (v) => v ? dayjs(v).format('DD/MM/YYYY HH:mm:ss') : '' },
        { title: 'Utilisateur', dataIndex: ['utilisateur', 'nom_complet'] },
        { title: 'Action', dataIndex: 'action', render: (v) => <Tag>{v}</Tag> },
        { title: 'Description', dataIndex: 'description', ellipsis: true },
        { title: 'IP', dataIndex: 'ip_address' },
    ];

    return (
        <div>
            <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: 16 }}>
                <Typography.Title level={3} style={{ margin: 0 }}>Journaux</Typography.Title>
                <Button icon={<DownloadOutlined />} onClick={exporter}>Exporter CSV</Button>
            </div>

            <Space wrap style={{ marginBottom: 16 }}>
                <Select
                    placeholder="Action"
                    allowClear
                    style={{ width: 220 }}
                    options={ACTIONS.map((a) => ({ value: a, label: a }))}
                    onChange={(v) => setParams((p) => ({ ...p, page: 1, action: v }))}
                />
                <DatePicker.RangePicker
                    format="DD/MM/YYYY"
                    onChange={(d) => setParams((p) => ({
                        ...p, page: 1,
                        date_debut: d?.[0]?.format('YYYY-MM-DD'),
                        date_fin: d?.[1]?.format('YYYY-MM-DD'),
                    }))}
                />
            </Space>

            <Table
                rowKey="id"
                loading={isLoading}
                columns={colonnes}
                dataSource={data?.data ?? []}
                onChange={(pagination) => setParams((p) => ({ ...p, page: pagination.current, par_page: pagination.pageSize }))}
                pagination={{
                    current: data?.meta?.current_page ?? 1,
                    pageSize: params.par_page,
                    total: data?.meta?.total ?? 0,
                }}
            />
        </div>
    );
}
