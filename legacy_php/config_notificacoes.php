<?php
session_start();
require_once 'db/conexao.php';

$usuario_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : null;

if (!$usuario_id) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurações de Notificações - Plataforma de Treinamentos</title>
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
        
        .config-container {
            max-width: 800px;
            margin: 80px auto 40px;
            padding: 0 20px;
        }
        
        .config-card {
            background: white;
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .config-card h2 {
            font-size: 24px;
            margin-bottom: 20px;
            color: #1e293b;
        }
        
        .config-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .config-item:last-child {
            border-bottom: none;
        }
        
        .config-label {
            flex: 1;
        }
        
        .config-label h3 {
            font-size: 16px;
            margin-bottom: 4px;
            color: #1e293b;
        }
        
        .config-label p {
            font-size: 13px;
            color: #64748b;
        }
        
        .toggle-switch {
            position: relative;
            width: 50px;
            height: 26px;
            background: #cbd5e1;
            border-radius: 13px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .toggle-switch.active {
            background: linear-gradient(135deg, #ff6f00, #ff8c1a);
        }
        
        .toggle-switch::after {
            content: '';
            position: absolute;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: white;
            top: 3px;
            left: 3px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        
        .toggle-switch.active::after {
            left: 27px;
        }
        
        .btn-save {
            padding: 12px 30px;
            background: linear-gradient(135deg, #ff6f00, #ff8c1a);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(255, 111, 0, 0.2);
        }
        
        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 111, 0, 0.3);
        }
    </style>
</head>
<body>
    <div class="config-container">
        <div class="config-card">
            <h2><i class="fas fa-bell"></i> Configurações de Notificações</h2>
            
            <div class="config-item">
                <div class="config-label">
                    <h3>Novos Vídeos</h3>
                    <p>Receber notificações quando novos vídeos forem publicados</p>
                </div>
                <div class="toggle-switch active" data-config="notificar_videos_novos" onclick="toggleConfig(this)"></div>
            </div>
            
            <div class="config-item">
                <div class="config-label">
                    <h3>Comentários</h3>
                    <p>Receber notificações de novos comentários em vídeos que você comentou</p>
                </div>
                <div class="toggle-switch active" data-config="notificar_comentarios" onclick="toggleConfig(this)"></div>
            </div>
            
            <div class="config-item">
                <div class="config-label">
                    <h3>Respostas</h3>
                    <p>Receber notificações quando alguém responder seus comentários</p>
                </div>
                <div class="toggle-switch active" data-config="notificar_respostas" onclick="toggleConfig(this)"></div>
            </div>
            
            <div class="config-item">
                <div class="config-label">
                    <h3>Lives</h3>
                    <p>Receber notificações quando uma transmissão ao vivo começar</p>
                </div>
                <div class="toggle-switch active" data-config="notificar_lives" onclick="toggleConfig(this)"></div>
            </div>
            
            <div class="config-item">
                <div class="config-label">
                    <h3>Apenas Favoritos</h3>
                    <p>Receber notificações apenas de setores/módulos favoritos</p>
                </div>
                <div class="toggle-switch" data-config="notificar_apenas_favoritos" onclick="toggleConfig(this)"></div>
            </div>
            
            <div class="config-item">
                <div class="config-label">
                    <h3>Notificações Push</h3>
                    <p>Receber notificações no navegador (requer permissão)</p>
                </div>
                <div class="toggle-switch active" data-config="push_notificacoes" onclick="toggleConfig(this)"></div>
            </div>
            
            <div style="margin-top: 30px; text-align: center;">
                <button class="btn-save" onclick="salvarConfiguracoes()">
                    <i class="fas fa-save"></i> Salvar Configurações
                </button>
            </div>
        </div>
    </div>
    
    <script>
        let config = {};
        
        function toggleConfig(element) {
            element.classList.toggle('active');
        }
        
        function salvarConfiguracoes() {
            const toggles = document.querySelectorAll('.toggle-switch');
            toggles.forEach(toggle => {
                const key = toggle.dataset.config;
                config[key] = toggle.classList.contains('active');
            });
            
            fetch('configurar_notificacoes.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(config)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Configurações salvas com sucesso!');
                } else {
                    alert(data.message || 'Erro ao salvar configurações');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao salvar configurações');
            });
        }
        
        // Carregar configurações ao iniciar
        document.addEventListener('DOMContentLoaded', function() {
            fetch('get_config_notificacoes.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.config) {
                        Object.keys(data.config).forEach(key => {
                            const toggle = document.querySelector(`[data-config="${key}"]`);
                            if (toggle && data.config[key]) {
                                toggle.classList.add('active');
                            }
                        });
                    }
                })
                .catch(error => console.error('Erro ao carregar configurações:', error));
        });
    </script>
</body>
</html>

