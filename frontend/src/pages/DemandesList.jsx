import { useState } from 'react';
import { Table, Input, Select, Button, Space, Tag, Typography, DatePicker } from 'antd';
import { PlusOutlined, SearchOutlined } from '@ant-design/icons';
import { useQuery } from '@tanstack/react-query';
import { Link, useNavigate } from 'react-router-dom';
import dayjs from 'dayjs';
import api from '../services/api';
import { useAuth } from '../auth/AuthContext';

export default function DemandesList() {
    const navigate = useNavigate();
    const { can } = useAuth();
    const [params, setParams] = useState({ page: 1, par_page: 15, tri: 'date_creation_application', direction: 'desc' });

    const { data: statuts } = useQuery({
        queryKey: ['statuts'],
        queryFn: async () => (await api.get('/statuts')).data.data,
    });

    const { data, isLoading } = useQuery({
        queryKey: ['demandes', params],
        queryFn: async () => (await api.get('/demandes-achats', { params })).data,
    });

    const maj = (patch) => setParams((p) => ({ ...p, page: 1, ...patch }));

    const colonnes = [
        { title: 'N° DA', dataIndex: 'numero_da', sorter: true, render: (v, r) => <Link to={`/demandes/${r.id}`}>{v}</Link> },
        { title: 'Désignation', dataIndex: 'designation', ellipsis: true },
        { title: 'Affectation', dataIndex: 'affectation', ellipsis: true },
        { title: 'Statut', dataIndex: ['statut', 'libelle'], render: (v, r) => <Tag color={r.statut?.couleur}>{v}</Tag> },
        { title: 'Création réelle', dataIndex: 'date_creation_reelle', render: (v) => v ? dayjs(v).format('DD/MM/YYYY') : '' },
    ];

    return (
        <div>
            <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: 16 }}>
                <Typography.Title level={3} style={{ margin: 0 }}>Demandes d'achat</Typography.Title>
                {can('create da') && (
                    <Button type="primary" icon={<PlusOutlined />} onClick={() => navigate('/demandes/nouvelle')}>
                        Nouvelle DA
                    </Button>
                )}
            </div>

            <Space wrap style={{ marginBottom: 16 }}>
                <Input
                    placeholder="Rechercher (n° ou désignation)"
                    prefix={<SearchOutlined />}
                    allowClear
                    style={{ width: 260 }}
                    onChange={(e) => maj({ recherche: e.target.value })}
                />
                <Select
                    placeholder="Statut"
                    allowClear
                    style={{ width: 180 }}
                    options={(statuts ?? []).map((s) => ({ value: s.id, label: s.libelle }))}
                    onChange={(v) => maj({ statut_id: v })}
                />
                <DatePicker.RangePicker
                    format="DD/MM/YYYY"
                    onChange={(d) => maj({
                        date_debut: d?.[0]?.format('YYYY-MM-DD'),
                        date_fin: d?.[1]?.format('YYYY-MM-DD'),
                    })}
                />
            </Space>

            <Table
                rowKey="id"
                loading={isLoading}
                columns={colonnes}
                dataSource={data?.data ?? []}
                onChange={(pagination, filters, sorter) => {
                    const patch = { page: pagination.current, par_page: pagination.pageSize };
                    if (sorter?.field) {
                        patch.tri = Array.isArray(sorter.field) ? sorter.field[0] : sorter.field;
                        patch.direction = sorter.order === 'ascend' ? 'asc' : 'desc';
                    }
                    setParams((p) => ({ ...p, ...patch }));
                }}
                pagination={{
                    current: data?.meta?.current_page ?? 1,
                    pageSize: params.par_page,
                    total: data?.meta?.total ?? 0,
                    showSizeChanger: true,
                }}
            />
        </div>
    );
}
