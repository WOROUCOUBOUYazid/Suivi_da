import { useState } from 'react';
import {
    Card, Descriptions, Tag, Timeline, Button, Space, Typography, Modal, Form,
    Select, Input, DatePicker, List, Divider, App as AntApp, Spin, Popconfirm,
} from 'antd';
import { DownloadOutlined, SwapOutlined, CheckCircleOutlined, EditOutlined } from '@ant-design/icons';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { useParams, useNavigate } from 'react-router-dom';
import dayjs from 'dayjs';
import api from '../services/api';
import { useAuth } from '../auth/AuthContext';

export default function DemandeDetail() {
    const { id } = useParams();
    const navigate = useNavigate();
    const { can, estAdmin } = useAuth();
    const { message } = AntApp.useApp();
    const queryClient = useQueryClient();
    const [statutModal, setStatutModal] = useState(false);
    const [commentaire, setCommentaire] = useState('');
    const [form] = Form.useForm();

    const { data: da, isLoading } = useQuery({
        queryKey: ['demande', id],
        queryFn: async () => (await api.get(`/demandes-achats/${id}`)).data.data,
    });

    const { data: statuts } = useQuery({
        queryKey: ['statuts'],
        queryFn: async () => (await api.get('/statuts')).data.data,
    });

    const rafraichir = () => {
        queryClient.invalidateQueries({ queryKey: ['demande', id] });
    };

    const changerStatut = useMutation({
        mutationFn: (valeurs) => api.post(`/demandes-achats/${id}/statut`, valeurs),
        onSuccess: () => { message.success('Statut mis à jour'); setStatutModal(false); form.resetFields(); rafraichir(); },
        onError: (e) => message.error(e.response?.data?.message ?? 'Erreur lors du changement de statut'),
    });

    const cloturer = useMutation({
        mutationFn: () => api.post(`/demandes-achats/${id}/cloturer`),
        onSuccess: () => { message.success('DA clôturée'); rafraichir(); },
        onError: (e) => message.error(e.response?.data?.message ?? 'Erreur'),
    });

    const ajouterCommentaire = useMutation({
        mutationFn: (contenu) => api.post(`/demandes-achats/${id}/commentaires`, { contenu }),
        onSuccess: () => { message.success('Commentaire ajouté'); setCommentaire(''); rafraichir(); },
    });

    if (isLoading || !da) {
        return <Spin size="large" />;
    }

    // Options de statut : standard => uniquement statuts d'ordre supérieur + clôture ; admin => tous.
    const optionsStatut = (statuts ?? [])
        .filter((s) => estAdmin() || s.est_cloture || s.ordre > (da.statut?.ordre ?? 0))
        .filter((s) => s.id !== da.statut_id)
        .map((s) => ({ value: s.id, label: s.libelle }));

    const telechargerPdf = async () => {
        const res = await api.get(`/demandes-achats/${id}/pdf`, { responseType: 'blob' });
        const url = window.URL.createObjectURL(new Blob([res.data]));
        const a = document.createElement('a');
        a.href = url;
        a.download = `Fiche_${da.numero_da}.pdf`;
        a.click();
        window.URL.revokeObjectURL(url);
    };

    return (
        <div>
            <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: 16 }}>
                <Typography.Title level={3} style={{ margin: 0 }}>
                    {da.numero_da} <Tag color={da.statut?.couleur}>{da.statut?.libelle}</Tag>
                </Typography.Title>
                <Space>
                    <Button icon={<DownloadOutlined />} onClick={telechargerPdf}>PDF</Button>
                    {can('edit da') && !da.date_cloture && (
                        <Button icon={<EditOutlined />} onClick={() => navigate(`/demandes/${id}/modifier`)}>Modifier</Button>
                    )}
                    {can('edit da') && (
                        <Button type="primary" icon={<SwapOutlined />} onClick={() => setStatutModal(true)}>Changer le statut</Button>
                    )}
                    {can('close da') && !da.date_cloture && (
                        <Popconfirm title="Clôturer cette DA ?" onConfirm={() => cloturer.mutate()}>
                            <Button danger icon={<CheckCircleOutlined />}>Clôturer</Button>
                        </Popconfirm>
                    )}
                </Space>
            </div>

            <Card style={{ marginBottom: 16 }}>
                <Descriptions bordered column={{ xs: 1, md: 2 }}>
                    <Descriptions.Item label="Désignation">{da.designation}</Descriptions.Item>
                    <Descriptions.Item label="Affectation">{da.affectation}</Descriptions.Item>
                    <Descriptions.Item label="Quantité">{da.quantite}</Descriptions.Item>
                    <Descriptions.Item label="Demandeur">{da.createur?.nom_complet}</Descriptions.Item>
                    <Descriptions.Item label="Création réelle">{da.date_creation_reelle ? dayjs(da.date_creation_reelle).format('DD/MM/YYYY') : ''}</Descriptions.Item>
                    <Descriptions.Item label="Création application">{da.date_creation_application ? dayjs(da.date_creation_application).format('DD/MM/YYYY') : ''}</Descriptions.Item>
                    <Descriptions.Item label="Problématique" span={2}>{da.problematique}</Descriptions.Item>
                    <Descriptions.Item label="Solution proposée" span={2}>{da.apport_solution}</Descriptions.Item>
                    <Descriptions.Item label="Existant" span={2}>{da.existant ?? 'Néant'}</Descriptions.Item>
                </Descriptions>
            </Card>

            <Card title="Historique des statuts" style={{ marginBottom: 16 }}>
                <Timeline
                    items={(da.historiques ?? []).map((h) => ({
                        color: h.nouveau_statut ? 'blue' : 'gray',
                        children: (
                            <div>
                                <strong>{h.ancien_statut ? `${h.ancien_statut} → ` : ''}{h.nouveau_statut}</strong>
                                <div>{h.commentaire}</div>
                                <Typography.Text type="secondary">
                                    {h.utilisateur?.nom_complet} — {h.date_changement ? dayjs(h.date_changement).format('DD/MM/YYYY HH:mm') : ''}
                                </Typography.Text>
                            </div>
                        ),
                    }))}
                />
            </Card>

            <Card title="Commentaires">
                <List
                    dataSource={da.commentaires ?? []}
                    locale={{ emptyText: 'Aucun commentaire' }}
                    renderItem={(c) => (
                        <List.Item>
                            <List.Item.Meta
                                title={c.utilisateur?.nom_complet}
                                description={`${c.contenu} — ${c.created_at ? dayjs(c.created_at).format('DD/MM/YYYY HH:mm') : ''}`}
                            />
                        </List.Item>
                    )}
                />
                <Divider />
                <Space.Compact style={{ width: '100%' }}>
                    <Input
                        value={commentaire}
                        onChange={(e) => setCommentaire(e.target.value)}
                        placeholder="Ajouter un commentaire…"
                        onPressEnter={() => commentaire && ajouterCommentaire.mutate(commentaire)}
                    />
                    <Button type="primary" onClick={() => commentaire && ajouterCommentaire.mutate(commentaire)} loading={ajouterCommentaire.isPending}>
                        Envoyer
                    </Button>
                </Space.Compact>
            </Card>

            <Modal
                title="Changer le statut"
                open={statutModal}
                onCancel={() => setStatutModal(false)}
                onOk={() => form.submit()}
                confirmLoading={changerStatut.isPending}
                okText="Valider"
            >
                <Form form={form} layout="vertical" onFinish={(v) => changerStatut.mutate({
                    ...v,
                    date_estimee_action: v.date_estimee_action?.format('YYYY-MM-DD'),
                })}>
                    <Form.Item name="statut_id" label="Nouveau statut" rules={[{ required: true, message: 'Statut requis' }]}>
                        <Select options={optionsStatut} placeholder="Choisir un statut" />
                    </Form.Item>
                    <Form.Item name="commentaire" label="Commentaire">
                        <Input.TextArea rows={3} />
                    </Form.Item>
                    <Form.Item name="date_estimee_action" label="Date estimée de prochaine action (prioritaire pour la relance)">
                        <DatePicker format="DD/MM/YYYY" style={{ width: '100%' }} />
                    </Form.Item>
                    <Form.Item name="delai_personnalise_relance_jours" label="Ou délai personnalisé avant relance (jours)">
                        <Input type="number" min={1} />
                    </Form.Item>
                </Form>
            </Modal>
        </div>
    );
}
