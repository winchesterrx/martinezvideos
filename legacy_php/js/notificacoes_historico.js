// ===== SISTEMA DE NOTIFICAÇÕES =====
let notificacoesInterval = null;

function initNotificacoes() {
    const isLoggedIn = document.querySelector('.user-header-info') !== null;
    if (!isLoggedIn) return;
    
    loadNotificacoes();
    setupNotificacoesDropdown();
    
    // Atualizar notificações a cada 30 segundos
    if (notificacoesInterval) clearInterval(notificacoesInterval);
    notificacoesInterval = setInterval(loadNotificacoes, 30000);
}

function loadNotificacoes() {
    fetch('get_notificacoes.php?limite=10&apenas_nao_lidas=false')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateNotificationBadge(data.nao_lidas);
                updateNotificationsDropdown(data.notificacoes, data.nao_lidas);
            }
        })
        .catch(error => console.error('Erro ao carregar notificações:', error));
}

function updateNotificationBadge(count) {
    const badge = document.getElementById('notification-badge');
    if (badge) {
        if (count > 0) {
            badge.textContent = count > 99 ? '99+' : count;
            badge.style.display = 'flex';
        } else {
            badge.style.display = 'none';
        }
    }
}

function updateNotificationsDropdown(notificacoes, nao_lidas) {
    const dropdown = document.getElementById('notifications-dropdown');
    const list = document.getElementById('notifications-list');
    
    if (!dropdown || !list) return;
    
    if (notificacoes.length === 0) {
        list.innerHTML = `
            <div class="notifications-empty">
                <i class="fas fa-bell-slash"></i>
                <p>Nenhuma notificação</p>
            </div>
        `;
        return;
    }
    
    list.innerHTML = notificacoes.map(notif => {
        const iconClass = getNotificationIcon(notif.tipo);
        const timeAgo = getTimeAgo(notif.created_at);
        const unreadClass = notif.lida === 'N' ? 'unread' : '';
        
        return `
            <div class="notification-item ${unreadClass}" data-id="${notif.id}" data-link="${notif.link || ''}">
                <div class="notification-icon ${notif.tipo}">
                    <i class="${iconClass}"></i>
                </div>
                <div class="notification-content">
                    <div class="notification-title">${escapeHtml(notif.titulo)}</div>
                    ${notif.mensagem ? `<div class="notification-message">${escapeHtml(notif.mensagem)}</div>` : ''}
                    <div class="notification-time">${timeAgo}</div>
                </div>
            </div>
        `;
    }).join('');
    
    // Adicionar event listeners
    list.querySelectorAll('.notification-item').forEach(item => {
        item.addEventListener('click', function() {
            const notifId = this.dataset.id;
            const link = this.dataset.link;
            
            // Marcar como lida
            marcarNotificacaoLida(notifId);
            
            // Redirecionar se houver link
            if (link) {
                window.location.href = link;
            }
        });
    });
}

function getNotificationIcon(tipo) {
    const icons = {
        'video_novo': 'fas fa-video',
        'comentario': 'fas fa-comment',
        'resposta': 'fas fa-reply',
        'live': 'fas fa-circle'
    };
    return icons[tipo] || 'fas fa-bell';
}

function getTimeAgo(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const diff = Math.floor((now - date) / 1000);
    
    if (diff < 60) return 'Agora';
    if (diff < 3600) return `${Math.floor(diff / 60)}min atrás`;
    if (diff < 86400) return `${Math.floor(diff / 3600)}h atrás`;
    if (diff < 604800) return `${Math.floor(diff / 86400)}d atrás`;
    return date.toLocaleDateString('pt-BR');
}

function setupNotificacoesDropdown() {
    const btn = document.getElementById('btn-notifications');
    const dropdown = document.getElementById('notifications-dropdown');
    
    if (!btn || !dropdown) return;
    
    btn.addEventListener('click', function(e) {
        e.stopPropagation();
        dropdown.classList.toggle('active');
    });
    
    // Fechar ao clicar fora
    document.addEventListener('click', function(e) {
        if (!dropdown.contains(e.target) && !btn.contains(e.target)) {
            dropdown.classList.remove('active');
        }
    });
}

function marcarNotificacaoLida(notificacaoId) {
    const formData = new FormData();
    formData.append('notificacao_id', notificacaoId);
    
    fetch('marcar_notificacao_lida.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadNotificacoes(); // Recarregar
        }
    })
    .catch(error => console.error('Erro ao marcar notificação:', error));
}

function marcarTodasLidas() {
    const formData = new FormData();
    formData.append('marcar_todas', 'true');
    
    fetch('marcar_notificacao_lida.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadNotificacoes(); // Recarregar
            showNotification('Todas as notificações marcadas como lidas', 'success');
        }
    })
    .catch(error => console.error('Erro:', error));
}

// ===== SISTEMA DE HISTÓRICO =====
function loadContinuarAssistindo() {
    // Verifica se está logado de múltiplas formas
    const userHeaderInfo = document.querySelector('.user-header-info');
    const userHeaderName = document.querySelector('.user-header-name');
    const isLoggedIn = userHeaderInfo !== null || userHeaderName !== null;
    
    const section = document.getElementById('continuar-assistindo-section');
    
    console.log('loadContinuarAssistindo - isLoggedIn:', isLoggedIn, 'section:', section);
    
    if (!isLoggedIn) {
        console.log('Usuário não logado, ocultando seção');
        if (section) section.style.display = 'none';
        return;
    }
    
    if (!section) {
        console.warn('Seção continuar-assistindo-section não encontrada');
        return;
    }
    
    console.log('Carregando histórico...');
    fetch('get_historico.php?tipo=continuar&limite=6')
        .then(response => {
            console.log('Resposta get_historico.php:', response.status, response.statusText);
            if (!response.ok) {
                throw new Error('Erro HTTP: ' + response.status);
            }
            return response.text().then(text => {
                console.log('Resposta bruta get_historico.php:', text.substring(0, 200));
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('Erro ao fazer parse do JSON:', e);
                    console.error('Resposta recebida:', text);
                    throw new Error('Resposta inválida do servidor');
                }
            });
        })
        .then(data => {
            console.log('Dados do histórico:', data);
            if (data.success && data.historico && data.historico.length > 0) {
                console.log('Renderizando', data.historico.length, 'vídeos para continuar');
                renderContinuarAssistindo(data.historico);
                section.style.display = 'block';
                // Mostra divisor se houver seções secundárias
                updateSectionDivider();
            } else {
                console.log('Nenhum histórico para continuar');
                section.style.display = 'none';
                updateSectionDivider();
            }
        })
        .catch(error => {
            console.error('Erro ao carregar histórico:', error);
            if (section) section.style.display = 'none';
            updateSectionDivider();
        });
}

// Função global para atualizar divisor entre seções
function updateSectionDivider() {
    const continuarSection = document.getElementById('continuar-assistindo-section');
    const recomendacoesSection = document.getElementById('recomendacoesSection');
    const divider = document.getElementById('sectionDivider');
    
    if (divider) {
        const continuarVisible = continuarSection && 
            window.getComputedStyle(continuarSection).display !== 'none' &&
            continuarSection.style.display !== 'none';
        const recomendacoesVisible = recomendacoesSection && 
            window.getComputedStyle(recomendacoesSection).display !== 'none' &&
            recomendacoesSection.style.display !== 'none';
        
        const hasSecondarySections = continuarVisible || recomendacoesVisible;
        divider.style.display = hasSecondarySections ? 'block' : 'none';
    }
}

function renderContinuarAssistindo(historico) {
    const track = document.getElementById('continuar-track');
    if (!track) {
        console.warn('Track continuar-track não encontrado');
        return;
    }
    
    track.innerHTML = historico.map(item => {
        const porcentagem = parseFloat(item.porcentagem_assistida) || 0;
        const progressClass = porcentagem >= 100 ? 'video-progress-complete-continuar' : '';
        
        return `
            <div class="carousel-item">
            <div class="video-card video-card-continuar" data-video-id="${item.video_id}">
                <div class="video-card-thumbnail" data-video-id="${item.video_id}">
                    ${item.url_video ? `
                        <video class="video-thumbnail-preview" preload="metadata" muted playsinline>
                            <source src="${escapeHtml(item.url_video)}" type="video/mp4">
                        </video>
                    ` : `
                        <div class="video-thumbnail-fallback">
                            <i class="fas fa-video"></i>
                        </div>
                    `}
                    <div class="continuar-badge">
                        <i class="fas fa-play-circle"></i> Continuar
                    </div>
                    <div class="video-progress-continuar ${progressClass}">
                        <div class="video-progress-bar-continuar" style="width: ${porcentagem}%"></div>
                    </div>
                    <div class="video-duration">${formatDuration(item.duracao)}</div>
                    <a href="video_detalhes.php?id=${item.video_id}" class="video-play-overlay">
                        <i class="fas fa-play"></i>
                    </a>
                </div>
                <div class="video-card-content">
                    <div class="video-card-tags">
                        <div class="video-card-setor">
                            <i class="fas fa-tag"></i>
                            <span>${escapeHtml(item.setor_nome)}</span>
                        </div>
                        ${item.modulo_nome ? `
                            <div class="video-card-modulo">
                                <i class="fas fa-cube"></i>
                                <span>${escapeHtml(item.modulo_nome)}</span>
                            </div>
                        ` : ''}
                    </div>
                    <h3 class="video-card-title">${escapeHtml(item.titulo)}</h3>
                    <p class="video-card-description">${escapeHtml(item.descricao || '')}</p>
                    <div class="video-card-stats">
                        <span class="stat-item stat-views">
                            <i class="fas fa-eye"></i> ${parseInt(item.visualizacoes || 0).toLocaleString('pt-BR')}
                        </span>
                        <span class="stat-item">
                            ${porcentagem.toFixed(0)}% assistido
                        </span>
                    </div>
                </div>
            </div>
        `;
    }).join('');
    
    // Reinicializar listeners e previews
    if (typeof initVideoCardListeners === 'function') initVideoCardListeners();
    if (typeof setupVideoPreviews === 'function') setupVideoPreviews();
    if (typeof loadVideoDurations === 'function') loadVideoDurations();
    
    // Atualizar botões do carrossel
    setTimeout(() => {
        if (typeof updateCarouselButtons === 'function') updateCarouselButtons('continuar');
    }, 100);
}

function limparHistorico(tipo) {
    if (!confirm(`Tem certeza que deseja limpar o histórico ${tipo === 'todos' ? 'completo' : tipo}?`)) {
        return;
    }
    
    const formData = new FormData();
    formData.append('tipo', tipo);
    
    fetch('limpar_historico.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            if (tipo === 'continuar') {
                loadContinuarAssistindo();
            } else {
                window.location.reload();
            }
        } else {
            showNotification(data.message || 'Erro ao limpar histórico', 'error');
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        showNotification('Erro ao limpar histórico', 'error');
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

// Inicializar ao carregar a página
document.addEventListener('DOMContentLoaded', function() {
    initNotificacoes();
    loadContinuarAssistindo();
});

