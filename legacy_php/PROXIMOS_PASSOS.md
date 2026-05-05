# 🚀 Próximos Passos - Melhorias e Funcionalidades

## 📋 Índice
1. [Sistema de Notificações](#sistema-de-notificações)
2. [Sistema de Pastas Personalizadas](#sistema-de-pastas-personalizadas)
3. [Melhorias na Transmissão ao Vivo](#melhorias-na-transmissão-ao-vivo)
4. [Melhorias Gerais de Interface](#melhorias-gerais-de-interface)
5. [Responsividade](#responsividade)

---

## 🔔 Sistema de Notificações

### Funcionalidades a Implementar:

#### 1. **Ícone de Notificações no Header**
- [ ] Adicionar ícone de sino no top header (navbar)
- [ ] Badge com contador de notificações não lidas
- [ ] Dropdown/modal ao clicar mostrando todas as notificações
- [ ] Marcar como lida ao clicar na notificação

#### 2. **Notificações Flutuantes (Toast)**
- [ ] Sistema de notificações toast no canto da tela
- [ ] Animações de entrada/saída suaves
- [ ] Diferentes tipos: sucesso, erro, aviso, info
- [ ] Auto-dismiss após X segundos
- [ ] Som de notificação (opcional)

#### 3. **Tipos de Notificações**

##### 3.1. Comentários em Vídeos
- [ ] Notificar quando alguém comenta em vídeo que o usuário já comentou
- [ ] Notificar quando há nova resposta ao comentário do usuário
- [ ] Link direto para o vídeo/comentário

##### 3.2. Respostas
- [ ] Notificar quando há resposta ao comentário do usuário
- [ ] Notificar quando há resposta à resposta do usuário (thread)
- [ ] Mostrar preview do comentário/resposta

##### 3.3. Lives Ativas
- [ ] Notificar quando uma live agendada começa
- [ ] Notificar X minutos antes da live agendada começar
- [ ] Badge "AO VIVO" no ícone de notificações quando houver live ativa
- [ ] Link direto para assistir a live

##### 3.4. Outras Notificações
- [ ] Novo vídeo publicado em setor que o usuário segue
- [ ] Vídeo que o usuário curtiu recebeu novos comentários
- [ ] Menções do usuário em comentários (futuro)

#### 4. **Backend de Notificações**
- [ ] Criar tabela `notificacoes` no banco de dados
- [ ] Campos: id, usuario_id, tipo, titulo, mensagem, link, lida, created_at
- [ ] Endpoint PHP para criar notificações
- [ ] Endpoint PHP para buscar notificações (AJAX)
- [ ] Endpoint PHP para marcar como lida
- [ ] Endpoint PHP para marcar todas como lidas
- [ ] Sistema de polling ou WebSocket para atualizações em tempo real

#### 5. **Interface de Notificações**
- [ ] Modal/dropdown com lista de notificações
- [ ] Agrupamento por data (Hoje, Ontem, Esta Semana, etc.)
- [ ] Filtros por tipo de notificação
- [ ] Botão "Marcar todas como lidas"
- [ ] Indicador visual de notificações não lidas
- [ ] Paginação para muitas notificações

---

## 📁 Sistema de Pastas Personalizadas

### Funcionalidades a Implementar:

#### 1. **Criação e Gerenciamento de Pastas**
- [ ] Interface para criar pastas personalizadas
- [ ] Nomear pastas
- [ ] Escolher cor/ícone para cada pasta
- [ ] Editar nome/cor/ícone de pastas existentes
- [ ] Excluir pastas (com opção de mover vídeos ou excluir junto)
- [ ] Reordenar pastas (drag and drop)

#### 2. **Organização de Vídeos**
- [ ] Adicionar vídeos a pastas (múltipla seleção)
- [ ] Remover vídeos de pastas
- [ ] Mover vídeos entre pastas
- [ ] Visualizar vídeos por pasta na sidebar
- [ ] Contador de vídeos em cada pasta

#### 3. **Backend de Pastas**
- [ ] Criar tabela `pastas_usuario` no banco de dados
- [ ] Campos: id, usuario_id, nome, cor, icone, ordem, created_at, updated_at
- [ ] Criar tabela `pasta_videos` (relacionamento many-to-many)
- [ ] Campos: id, pasta_id, video_id, created_at
- [ ] Endpoints PHP para CRUD de pastas
- [ ] Endpoints PHP para gerenciar vídeos nas pastas

#### 4. **Interface de Pastas**
- [ ] Seção "Minhas Pastas" na sidebar
- [ ] Modal para criar/editar pasta
- [ ] Seletor de cores (paleta)
- [ ] Seletor de ícones (Font Awesome)
- [ ] Lista de pastas na sidebar com contador
- [ ] Visualização de vídeos filtrados por pasta
- [ ] Drag and drop para organizar pastas

#### 5. **Funcionalidades Extras**
- [ ] Pastas compartilhadas (futuro)
- [ ] Pastas públicas (futuro)
- [ ] Exportar lista de vídeos de uma pasta (futuro)
- [ ] Buscar vídeos dentro de pastas

---

## 📺 Melhorias na Transmissão ao Vivo

### Funcionalidades a Implementar:

#### 1. **Interface Visual Moderna**
- [ ] Redesign completo da tela de live
- [ ] Player de vídeo maior e mais destacado
- [ ] Chat ao vivo mais integrado e moderno
- [ ] Informações da live em cards elegantes
- [ ] Animações suaves de transição
- [ ] Indicador "AO VIVO" mais chamativo
- [ ] Contador de visualizadores em tempo real

#### 2. **Funcionalidades de Live**
- [ ] Chat em tempo real (WebSocket ou polling)
- [ ] Sistema de perguntas/perguntas frequentes
- [ ] Compartilhamento social melhorado
- [ ] Gravação automática da live (futuro)
- [ ] Replay da live após término (futuro)
- [ ] Qualidade de vídeo ajustável (futuro)

#### 3. **Agendamento de Lives**
- [ ] Calendário visual para agendar lives
- [ ] Notificações antes da live começar
- [ ] Lembretes configuráveis (15min, 1h, 1 dia antes)
- [ ] Histórico de lives passadas
- [ ] Estatísticas de lives (visualizações, engajamento)

#### 4. **Backend de Lives**
- [ ] Melhorar estrutura da tabela `transmissao_ao_vivo`
- [ ] Adicionar campos: thumbnail, duracao, visualizadores_atual, etc.
- [ ] Sistema de chat persistente (tabela `live_chat`)
- [ ] Endpoints para estatísticas de live

---

## 🎨 Melhorias Gerais de Interface

### 1. **Sidebar em Todas as Telas**
- [ ] Adicionar sidebar em `video_detalhes.php`
- [ ] Adicionar sidebar em `listar_usuarios.php`
- [ ] Adicionar sidebar em `listar_clientes.php`
- [ ] Adicionar sidebar em `cadastro_setores.php`
- [ ] Adicionar sidebar em todas as outras páginas do sistema
- [ ] Manter consistência visual em todas as telas

### 2. **Melhorias na Tela `video_detalhes.php`**
- [ ] Redesign completo da página de detalhes
- [ ] Player de vídeo maior e mais moderno
- [ ] Seção de comentários mais organizada
- [ ] Thread de comentários melhor visualizada
- [ ] Botões de ação (curtir, compartilhar) mais destacados
- [ ] Informações do vídeo em cards elegantes
- [ ] Sugestões de vídeos relacionados
- [ ] Breadcrumb para navegação

### 3. **Melhorias na Sidebar**
- [ ] Revisar e melhorar todos os ícones
- [ ] Ajustar cores para melhor contraste
- [ ] Adicionar animações sutis nos hovers
- [ ] Melhorar hierarquia visual
- [ ] Adicionar tooltips nos ícones
- [ ] Melhorar espaçamento e padding
- [ ] Adicionar seção de "Favoritos" ou "Assistir Depois"

### 4. **Melhorias de Cores e Design**
- [ ] Revisar paleta de cores em todo o sistema
- [ ] Garantir contraste adequado (acessibilidade)
- [ ] Aplicar design system consistente
- [ ] Melhorar tipografia (fontes, tamanhos, pesos)
- [ ] Adicionar mais espaçamento em branco
- [ ] Melhorar cards e containers

---

## 📱 Responsividade

### Melhorias a Implementar:

#### 1. **Sidebar Responsiva**
- [ ] Menu hambúrguer para mobile
- [ ] Sidebar colapsável em tablets
- [ ] Overlay quando sidebar aberta no mobile
- [ ] Ajustar tamanhos de fonte para mobile
- [ ] Touch-friendly (áreas de toque maiores)

#### 2. **Páginas Responsivas**
- [ ] `video_detalhes.php` totalmente responsivo
- [ ] `listar_usuarios.php` responsivo
- [ ] `listar_clientes.php` responsivo
- [ ] Todas as outras páginas responsivas
- [ ] Grid de vídeos adaptável (1 col mobile, 2 tablet, 3+ desktop)

#### 3. **Componentes Responsivos**
- [ ] Modais responsivos
- [ ] Formulários adaptáveis
- [ ] Tabelas com scroll horizontal ou cards no mobile
- [ ] Botões com tamanhos adequados para touch
- [ ] Inputs e selects otimizados para mobile

#### 4. **Testes de Responsividade**
- [ ] Testar em diferentes tamanhos de tela
- [ ] Testar em dispositivos móveis reais
- [ ] Ajustar breakpoints se necessário
- [ ] Garantir que não há overflow horizontal

---

## 🗄️ Estrutura de Banco de Dados Necessária

### Tabelas a Criar:

#### 1. `notificacoes`
```sql
CREATE TABLE notificacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    tipo VARCHAR(50) NOT NULL, -- 'comentario', 'resposta', 'live', 'video_novo'
    titulo VARCHAR(255) NOT NULL,
    mensagem TEXT,
    link VARCHAR(500),
    lida CHAR(1) DEFAULT 'N',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
);
```

#### 2. `pastas_usuario`
```sql
CREATE TABLE pastas_usuario (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    nome VARCHAR(255) NOT NULL,
    cor VARCHAR(7) DEFAULT '#ff6f00',
    icone VARCHAR(50) DEFAULT 'fas fa-folder',
    ordem INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
);
```

#### 3. `pasta_videos`
```sql
CREATE TABLE pasta_videos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pasta_id INT NOT NULL,
    video_id INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pasta_id) REFERENCES pastas_usuario(id) ON DELETE CASCADE,
    FOREIGN KEY (video_id) REFERENCES videos(id) ON DELETE CASCADE,
    UNIQUE KEY unique_pasta_video (pasta_id, video_id)
);
```

#### 4. `live_chat` (opcional, para chat persistente)
```sql
CREATE TABLE live_chat (
    id INT AUTO_INCREMENT PRIMARY KEY,
    live_id INT NOT NULL,
    usuario_id INT,
    nome VARCHAR(255),
    mensagem TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (live_id) REFERENCES transmissao_ao_vivo(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
);
```

---

## 📝 Priorização Sugerida

### Fase 1 - Alta Prioridade
1. ✅ Sidebar em todas as telas
2. ✅ Melhorias na sidebar (ícones, cores, modernização)
3. ✅ Responsividade geral
4. ✅ Melhorias em `video_detalhes.php`

### Fase 2 - Média Prioridade
1. ⏳ Sistema de notificações básico (ícone + dropdown)
2. ⏳ Notificações de comentários e respostas
3. ⏳ Melhorias na interface de live

### Fase 3 - Baixa Prioridade
1. ⏳ Sistema de pastas personalizadas
2. ⏳ Notificações de lives ativas
3. ⏳ Funcionalidades extras de live (chat, replay, etc.)

---

## 🎯 Observações

- Todas as funcionalidades devem seguir o design system estabelecido (laranja, cinza, branco, preto)
- Manter consistência visual em todo o sistema
- Priorizar experiência do usuário (UX)
- Garantir acessibilidade (contraste, tamanhos de fonte, etc.)
- Testar em diferentes navegadores
- Documentar código novo

---

**Última atualização:** 2024
**Status:** Planejamento

