import { Row, Col, Card, Statistic, Table, Tag, Typography, Spin } from 'antd';
import { useQuery } from '@tanstack/react-query';
import { Link } from 'react-router-dom';
import api from '../services/api';

export default function Dashboard() {
    const { data, isLoading } = useQuery({
        queryKey: ['dashboard'],
        queryFn: async () => (await api.get('/dashboard')).data.data,
    });

    if (isLoading) {
        return <Spin size="large" />;
    }

    const stats = data ?? {};

    return (
        <div>
            <Typography.Title level={3}>Tableau de bord</Typography.Title>

            <Row gutter={[16, 16]}>
                <Col xs={12} md={6}><Card><Statistic title="Total DA" value={stats.total ?? 0} /></Card></Col>
                <Col xs={12} md={6}><Card><Statistic title="En cours" value={stats.en_cours ?? 0} /></Card></Col>
                <Col xs={12} md={6}><Card><Statistic title="Proches d'une relance" value={stats.proches_relance ?? 0} valueStyle={{ color: '#d48806' }} /></Card></Col>
                <Col xs={12} md={6}><Card><Statistic title="En retard" value={stats.en_retard ?? 0} valueStyle={{ color: '#cf1322' }} /></Card></Col>
            </Row>

            <Row gutter={[16, 16]} style={{ marginTop: 16 }}>
                <Col xs={24} md={12}>
                    <Card title="Répartition par statut">
                        {(stats.par_statut ?? []).map((s) => (
                            <div key={s.statut_id} style={{ display: 'flex', justifyContent: 'space-between', padding: '6px 0' }}>
                                <Tag color={s.couleur}>{s.libelle}</Tag>
                                <strong>{s.total}</strong>
                            </div>
                        ))}
                    </Card>
                </Col>
                <Col xs={24} md={12}>
                    <Card title="DA récemment créées">
                        <Table
                            size="small"
                            rowKey="id"
                            pagination={false}
                            dataSource={stats.recentes ?? []}
                            columns={[
                                { title: 'N° DA', dataIndex: 'numero_da', render: (v, r) => <Link to={`/demandes/${r.id}`}>{v}</Link> },
                                { title: 'Désignation', dataIndex: 'designation', ellipsis: true },
                                { title: 'Statut', dataIndex: 'statut' },
                            ]}
                        />
                    </Card>
                </Col>
            </Row>
        </div>
    );
}
