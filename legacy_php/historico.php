<?php
session_start();
require_once 'db/conexao.php';

$usuario_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : null;

if (!$usuario_id) {
    header('Location: login.php');
    exit;
}

$tipo = isset($_GET['tipo']) ? $_GET['tipo'] : 'todos'; // 'continuar', 'completos', 'todos'
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Histórico - Plataforma de Treinamentos</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: #f5f5f5;
            color: #333;
        }
        
        .historico-container {
            max-width: 1200px;
            margin: 80px auto 40px;
            padding: 0 20px;
        }
        
        .historico-header {
            background: white;
            padding: 30px;
            border-radius: 16px;
            margin-bottom: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .historico-header h1 {
            font-size: 28px;
            margin-bottom: 10px;
            color: #1e293b;
        }
        
        .historico-filters {
            display: flex;
            gap: 12px;
            margin-top: 20px;
            flex-wrap: wrap;
        }
        
        .filter-btn {
            padding: 8px 16px;
            border: 2px solid #e5e7eb;
            background: white;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
            font-weight: 500;
        }
        
        .filter-btn.active {
            background: linear-gradient(135deg, #ff6f00, #ff8c1a);
            color: white;
            border-color: #ff6f00;
        }
        
        .historico-actions {
            display: flex;
            gap: 12px;
            margin-top: 20px;
            flex-wrap: wrap;
        }
        
        .btn-action {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-export {
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: white;
        }
        
        .btn-clear {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
        }
        
        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }
        
        .historico-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .historico-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        
        .historico-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.15);
        }
        
        .historico-stats {
            padding: 20px;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            background: white;
            border-radius: 16px;
            margin-bottom: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .stat-box {
            text-align: center;
        }
        
        .stat-number {
            font-size: 32px;
            font-weight: 700;
            color: #ff6f00;
        }
        
        .stat-label {
            font-size: 14px;
            color: #64748b;
            margin-top: 4px;
        }
    </style>
</head>
<body>
    <div class="historico-container">
        <div class="historico-header">
            <h1><i class="fas fa-history"></i> Meu Histórico</h1>
            <p>Visualize todos os vídeos que você assistiu</p>
            
            <div class="historico-filters">
                <button class="filter-btn active" data-tipo="todos" onclick="filtrarHistorico('todos')">
                    <i class="fas fa-list"></i> Todos
                </button>
                <button class="filter-btn" data-tipo="continuar" onclick="filtrarHistorico('continuar')">
                    <i class="fas fa-play-circle"></i> Continuar Assistindo
                </button>
                <button class="filter-btn" data-tipo="completos" onclick="filtrarHistorico('completos')">
                    <i class="fas fa-check-circle"></i> Completos
                </button>
            </div>
            
            <div class="historico-actions">
                <button class="btn-action btn-export" onclick="exportarHistorico('csv')">
                    <i class="fas fa-file-csv"></i> Exportar CSV
                </button>
                <button class="btn-action btn-export" onclick="exportarHistorico('json')">
                    <i class="fas fa-file-code"></i> Exportar JSON
                </button>
                <button class="btn-action btn-clear" onclick="limparHistorico('<?= $tipo ?>')">
                    <i class="fas fa-trash"></i> Limpar Histórico
                </button>
            </div>
        </div>
        
        <div class="historico-stats" id="historico-stats">
            <!-- Carregado via JavaScript -->
        </div>
        
        <div class="historico-grid" id="historico-grid">
            <!-- Carregado via JavaScript -->
        </div>
    </div>
    
    <script>
        let tipoAtual = 'todos';
        
        function filtrarHistorico(tipo) {
            tipoAtual = tipo;
            document.querySelectorAll('.filter-btn').forEach(btn => {
                btn.classList.remove('active');
                if (btn.dataset.tipo === tipo) btn.classList.add('active');
            });
            loadHistorico();
        }
        
        function loadHistorico() {
            fetch(`get_historico.php?tipo=${tipoAtual}&limite=100`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        renderHistorico(data.historico);
                        renderStats(data.historico);
                    }
                })
                .catch(error => console.error('Erro:', error));
        }
        
        function renderHistorico(historico) {
            const grid = document.getElementById('historico-grid');
            if (!grid) return;
            
            if (historico.length === 0) {
                grid.innerHTML = '<div style="grid-column: 1/-1; text-align: center; padding: 60px; color: #94a3b8;"><i class="fas fa-history fa-3x" style="margin-bottom: 20px; opacity: 0.3;"></i><p>Nenhum vídeo no histórico</p></div>';
                return;
            }
            
            grid.innerHTML = historico.map(item => {
                const porcentagem = parseFloat(item.porcentagem_assistida) || 0;
                const progressClass = porcentagem >= 100 ? 'video-progress-complete-continuar' : '';
                const dataFormatada = new Date(item.visualizado_em).toLocaleDateString('pt-BR');
                
                return `
                    <div class="historico-card">
                        <div class="video-card-thumbnail" style="padding-top: 56.25%; position: relative;">
                            ${item.url_video ? `
                                <video class="video-thumbnail-preview" preload="metadata" muted playsinline style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover;">
                                    <source src="${escapeHtml(item.url_video)}" type="video/mp4">
                                </video>
                            ` : ''}
                            <div class="video-progress-continuar ${progressClass}">
                                <div class="video-progress-bar-continuar" style="width: ${porcentagem}%"></div>
                            </div>
                            <a href="video_detalhes.php?id=${item.video_id}" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; background: rgba(0,0,0,0.3); opacity: 0; transition: opacity 0.3s;">
                                <i class="fas fa-play" style="color: white; font-size: 48px;"></i>
                            </a>
                        </div>
                        <div style="padding: 16px;">
                            <h3 style="font-size: 16px; margin-bottom: 8px; color: #1e293b;">${escapeHtml(item.titulo)}</h3>
                            <p style="font-size: 12px; color: #64748b; margin-bottom: 12px;">${escapeHtml(item.setor_nome)} ${item.modulo_nome ? '• ' + escapeHtml(item.modulo_nome) : ''}</p>
                            <div style="display: flex; justify-content: space-between; align-items: center; font-size: 12px; color: #94a3b8;">
                                <span>${porcentagem.toFixed(0)}% assistido</span>
                                <span>${dataFormatada}</span>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
        }
        
        function renderStats(historico) {
            const stats = document.getElementById('historico-stats');
            if (!stats) return;
            
            const total = historico.length;
            const completos = historico.filter(h => h.completou == 1).length;
            const continuar = historico.filter(h => h.completou == 0 && h.tempo_assistido > 0).length;
            
            stats.innerHTML = `
                <div class="stat-box">
                    <div class="stat-number">${total}</div>
                    <div class="stat-label">Total de Vídeos</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number">${completos}</div>
                    <div class="stat-label">Completos</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number">${continuar}</div>
                    <div class="stat-label">Para Continuar</div>
                </div>
            `;
        }
        
        function limparHistorico(tipo) {
            if (!confirm(`Tem certeza que deseja limpar o histórico ${tipo === 'todos' ? 'completo' : tipo}?`)) return;
            
            const formData = new FormData();
            formData.append('tipo', tipo);
            
            fetch('limpar_historico.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    loadHistorico();
                } else {
                    alert(data.message || 'Erro ao limpar histórico');
                }
            });
        }
        
        function exportarHistorico(formato) {
            window.open(`exportar_historico.php?formato=${formato}`, '_blank');
        }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        // Carregar ao iniciar
        document.addEventListener('DOMContentLoaded', loadHistorico);
    </script>
</body>
</html>

