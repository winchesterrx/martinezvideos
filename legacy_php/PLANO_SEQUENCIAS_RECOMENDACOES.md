# 📋 Plano de Implementação: Sistema de Sequências e Recomendações de Vídeos

## 🎯 Objetivos

1. **Sistema de Sequências de Vídeos**: Permitir que vídeos sejam organizados em sequências numeradas (1, 2, 3...)
2. **Recomendações de Vídeos**: Exibir vídeos relacionados/sequenciais na página de detalhes
3. **Indicações na Live**: Mostrar vídeos relacionados ao lado ou abaixo da transmissão ao vivo

---

## 🗄️ Estrutura do Banco de Dados

### Opção 1: Adicionar campos na tabela `videos` (Recomendado)

```sql
-- Adicionar campos para sequências
ALTER TABLE videos 
ADD COLUMN is_sequencia TINYINT(1) DEFAULT 0 COMMENT '1 = faz parte de sequência, 0 = não',
ADD COLUMN sequencia_id INT NULL COMMENT 'ID do grupo de sequência',
ADD COLUMN sequencia_ordem INT NULL COMMENT 'Ordem na sequência (1, 2, 3...)',
ADD INDEX idx_sequencia (sequencia_id, sequencia_ordem);

-- Criar tabela para grupos de sequência (opcional, para melhor organização)
CREATE TABLE IF NOT EXISTS sequencias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL COMMENT 'Título da sequência (ex: "Curso de Consultório - Parte 1, 2, 3...")',
    setor_id INT NOT NULL,
    modulo_id INT NULL,
    descricao TEXT NULL,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (setor_id) REFERENCES setores(id) ON DELETE CASCADE,
    FOREIGN KEY (modulo_id) REFERENCES modulos(id) ON DELETE SET NULL,
    INDEX idx_setor_modulo (setor_id, modulo_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Adicionar foreign key para sequencias
ALTER TABLE videos 
ADD CONSTRAINT fk_videos_sequencia 
FOREIGN KEY (sequencia_id) REFERENCES sequencias(id) ON DELETE SET NULL;
```

### Opção 2: Tabela de relacionamento (Mais flexível)

```sql
-- Tabela para relacionar vídeos em sequências
CREATE TABLE IF NOT EXISTS video_sequencias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    video_id INT NOT NULL,
    sequencia_id INT NOT NULL,
    ordem INT NOT NULL COMMENT 'Ordem na sequência (1, 2, 3...)',
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (video_id) REFERENCES videos(id) ON DELETE CASCADE,
    FOREIGN KEY (sequencia_id) REFERENCES sequencias(id) ON DELETE CASCADE,
    UNIQUE KEY unique_video_sequencia (video_id, sequencia_id),
    INDEX idx_sequencia_ordem (sequencia_id, ordem)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Recomendação**: Usar **Opção 1** por ser mais simples e direta para o caso de uso.

---

## 📝 Modificações no Upload de Vídeos

### 1. Adicionar campos no formulário de upload (`index.php`)

```html
<!-- No modal de upload -->
<div class="form-group">
    <label>
        <input type="checkbox" id="isSequencia" name="is_sequencia" onchange="toggleSequenciaFields()">
        Este vídeo faz parte de uma sequência
    </label>
</div>

<div id="sequenciaFields" style="display: none;">
    <div class="form-group">
        <label>Escolha a sequência existente ou crie nova:</label>
        <select id="sequenciaSelect" name="sequencia_id" class="form-control">
            <option value="">-- Criar nova sequência --</option>
            <!-- Preencher via AJAX com sequências do setor/modulo selecionado -->
        </select>
    </div>
    
    <div id="novaSequenciaFields" style="display: none;">
        <div class="form-group">
            <label>Título da Sequência:</label>
            <input type="text" id="sequenciaTitulo" name="sequencia_titulo" class="form-control" 
                   placeholder="Ex: Curso de Consultório Médico">
        </div>
    </div>
    
    <div class="form-group">
        <label>Ordem na Sequência:</label>
        <input type="number" id="sequenciaOrdem" name="sequencia_ordem" class="form-control" 
               min="1" placeholder="Ex: 1, 2, 3...">
        <small class="form-text text-muted">
            Se deixar vazio, será automaticamente o próximo número da sequência
        </small>
    </div>
</div>
```

### 2. JavaScript para gerenciar campos de sequência

```javascript
function toggleSequenciaFields() {
    const isSequencia = document.getElementById('isSequencia').checked;
    const sequenciaFields = document.getElementById('sequenciaFields');
    sequenciaFields.style.display = isSequencia ? 'block' : 'none';
    
    if (isSequencia) {
        loadSequencias(); // Carregar sequências do setor/modulo
    }
}

function loadSequencias() {
    const setorId = document.getElementById('setor_id').value;
    const moduloId = document.getElementById('modulo_id').value;
    
    if (!setorId) return;
    
    fetch(`get_sequencias.php?setor_id=${setorId}&modulo_id=${moduloId || ''}`)
        .then(response => response.json())
        .then(data => {
            const select = document.getElementById('sequenciaSelect');
            select.innerHTML = '<option value="">-- Criar nova sequência --</option>';
            
            data.sequencias.forEach(seq => {
                const option = document.createElement('option');
                option.value = seq.id;
                option.textContent = seq.titulo + ` (${seq.total_videos} vídeos)`;
                select.appendChild(option);
            });
        });
}

document.getElementById('sequenciaSelect').addEventListener('change', function() {
    const novaSequenciaFields = document.getElementById('novaSequenciaFields');
    novaSequenciaFields.style.display = this.value === '' ? 'block' : 'none';
});
```

### 3. Modificar `upload_ajax.php` para processar sequências

```php
// No upload_ajax.php
$is_sequencia = isset($_POST['is_sequencia']) && $_POST['is_sequencia'] === 'on' ? 1 : 0;
$sequencia_id = isset($_POST['sequencia_id']) && !empty($_POST['sequencia_id']) ? intval($_POST['sequencia_id']) : null;
$sequencia_ordem = isset($_POST['sequencia_ordem']) && !empty($_POST['sequencia_ordem']) ? intval($_POST['sequencia_ordem']) : null;
$sequencia_titulo = isset($_POST['sequencia_titulo']) ? trim($_POST['sequencia_titulo']) : '';

// Se é nova sequência, criar
if ($is_sequencia && empty($sequencia_id) && !empty($sequencia_titulo)) {
    $stmt_seq = $conexao->prepare("INSERT INTO sequencias (titulo, setor_id, modulo_id) VALUES (?, ?, ?)");
    $modulo_id_upload = isset($_POST['modulo_id']) && $_POST['modulo_id'] !== '' ? intval($_POST['modulo_id']) : null;
    $stmt_seq->bind_param("sii", $sequencia_titulo, $setor_id, $modulo_id_upload);
    $stmt_seq->execute();
    $sequencia_id = $stmt_seq->insert_id;
    $stmt_seq->close();
}

// Se não especificou ordem, pegar a próxima
if ($is_sequencia && $sequencia_id && empty($sequencia_ordem)) {
    $stmt_ordem = $conexao->prepare("SELECT COALESCE(MAX(sequencia_ordem), 0) + 1 as proxima_ordem FROM videos WHERE sequencia_id = ?");
    $stmt_ordem->bind_param("i", $sequencia_id);
    $stmt_ordem->execute();
    $result_ordem = $stmt_ordem->get_result();
    $row_ordem = $result_ordem->fetch_assoc();
    $sequencia_ordem = $row_ordem['proxima_ordem'];
    $stmt_ordem->close();
}

// Inserir vídeo com informações de sequência
$sql = "INSERT INTO videos (titulo, descricao, url_video, setor_id, modulo_id, is_sequencia, sequencia_id, sequencia_ordem, data_upload) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
$stmt = $conexao->prepare($sql);
$stmt->bind_param("sssiiiii", $titulo, $descricao, $videoPath, $setor_id, $modulo_id, $is_sequencia, $sequencia_id, $sequencia_ordem);
```

---

## 📄 Criar `get_sequencias.php`

```php
<?php
session_start();
require_once 'db/conexao.php';

header('Content-Type: application/json; charset=utf-8');
mysqli_set_charset($conexao, "utf8mb4");

$setor_id = isset($_GET['setor_id']) ? intval($_GET['setor_id']) : 0;
$modulo_id = isset($_GET['modulo_id']) && $_GET['modulo_id'] !== '' ? intval($_GET['modulo_id']) : null;

if ($setor_id <= 0) {
    echo json_encode(['success' => false, 'sequencias' => []], JSON_UNESCAPED_UNICODE);
    exit;
}

$where = "WHERE setor_id = ?";
$params = [$setor_id];
$types = "i";

if ($modulo_id) {
    $where .= " AND modulo_id = ?";
    $params[] = $modulo_id;
    $types .= "i";
}

$query = "SELECT s.id, s.titulo, COUNT(v.id) as total_videos 
          FROM sequencias s 
          LEFT JOIN videos v ON v.sequencia_id = s.id 
          $where 
          GROUP BY s.id 
          ORDER BY s.titulo ASC";

$stmt = mysqli_prepare($conexao, $query);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $sequencias = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $sequencias[] = [
            'id' => intval($row['id']),
            'titulo' => $row['titulo'],
            'total_videos' => intval($row['total_videos'])
        ];
    }
    
    echo json_encode(['success' => true, 'sequencias' => $sequencias], JSON_UNESCAPED_UNICODE);
    mysqli_stmt_close($stmt);
} else {
    echo json_encode(['success' => false, 'sequencias' => []], JSON_UNESCAPED_UNICODE);
}
?>
```

---

## 🎬 Exibir Recomendações em `video_detalhes.php`

### 1. Buscar vídeos relacionados

```php
// No início do arquivo, após buscar o vídeo atual
$video_atual = // ... código existente ...

// Buscar vídeos da mesma sequência
$videos_relacionados = [];
if ($video_atual['is_sequencia'] && $video_atual['sequencia_id']) {
    $query_relacionados = "SELECT v.*, s.nome as setor_nome, m.nome as modulo_nome,
                          (SELECT COUNT(*) FROM curtidas WHERE curtidas.video_id = v.id) AS curtidas,
                          (SELECT COUNT(*) FROM comentarios WHERE comentarios.video_id = v.id) AS total_comentarios
                          FROM videos v
                          JOIN setores s ON v.setor_id = s.id
                          LEFT JOIN modulos m ON v.modulo_id = m.id
                          WHERE v.sequencia_id = ? AND v.id != ?
                          ORDER BY v.sequencia_ordem ASC
                          LIMIT 6";
    
    $stmt_rel = $conexao->prepare($query_relacionados);
    $stmt_rel->bind_param("ii", $video_atual['sequencia_id'], $video_atual['id']);
    $stmt_rel->execute();
    $result_rel = $stmt_rel->get_result();
    
    while ($row = $result_rel->fetch_assoc()) {
        $videos_relacionados[] = $row;
    }
    $stmt_rel->close();
}

// Se não tem vídeos da sequência, buscar vídeos do mesmo setor/modulo
if (empty($videos_relacionados)) {
    $query_similares = "SELECT v.*, s.nome as setor_nome, m.nome as modulo_nome,
                        (SELECT COUNT(*) FROM curtidas WHERE curtidas.video_id = v.id) AS curtidas,
                        (SELECT COUNT(*) FROM comentarios WHERE comentarios.video_id = v.id) AS total_comentarios
                        FROM videos v
                        JOIN setores s ON v.setor_id = s.id
                        LEFT JOIN modulos m ON v.modulo_id = m.id
                        WHERE v.setor_id = ? AND v.id != ?";
    
    $params_sim = [$video_atual['setor_id'], $video_atual['id']];
    $types_sim = "ii";
    
    if ($video_atual['modulo_id']) {
        $query_similares .= " AND v.modulo_id = ?";
        $params_sim[] = $video_atual['modulo_id'];
        $types_sim .= "i";
    }
    
    $query_similares .= " ORDER BY v.data_upload DESC LIMIT 6";
    
    $stmt_sim = $conexao->prepare($query_similares);
    $stmt_sim->bind_param($types_sim, ...$params_sim);
    $stmt_sim->execute();
    $result_sim = $stmt_sim->get_result();
    
    while ($row = $result_sim->fetch_assoc()) {
        $videos_relacionados[] = $row;
    }
    $stmt_sim->close();
}
```

### 2. HTML para exibir recomendações

```html
<!-- Após o player de vídeo, antes dos comentários -->
<?php if (!empty($videos_relacionados)): ?>
<div class="videos-relacionados-section">
    <div class="videos-relacionados-header">
        <h3>
            <i class="fas fa-play-circle"></i>
            <?php if ($video_atual['is_sequencia']): ?>
                Próximos da Sequência
            <?php else: ?>
                Vídeos Relacionados
            <?php endif; ?>
        </h3>
    </div>
    
    <div class="videos-relacionados-grid">
        <?php foreach ($videos_relacionados as $video_rel): ?>
        <div class="video-relacionado-card">
            <a href="video_detalhes.php?id=<?= $video_rel['id'] ?>" class="video-relacionado-link">
                <div class="video-relacionado-thumbnail">
                    <?php if ($video_rel['is_sequencia'] && $video_rel['sequencia_ordem']): ?>
                    <div class="video-sequencia-badge">
                        <span>#<?= $video_rel['sequencia_ordem'] ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="video-play-overlay">
                        <i class="fas fa-play"></i>
                    </div>
                </div>
                <div class="video-relacionado-info">
                    <h4 class="video-relacionado-titulo"><?= htmlspecialchars($video_rel['titulo']) ?></h4>
                    <div class="video-relacionado-meta">
                        <span><i class="fas fa-eye"></i> <?= number_format($video_rel['visualizacoes'], 0, ',', '.') ?></span>
                        <span><i class="fas fa-heart"></i> <?= $video_rel['curtidas'] ?></span>
                    </div>
                </div>
            </a>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>
```

---

## 📺 Exibir Vídeos Relacionados na Live (`index.php`)

### Opção A: Ao lado da live (sidebar)

```html
<!-- Dentro do live-content-grid-refined, após o chat -->
<div class="live-recomendacoes-sidebar">
    <div class="live-recomendacoes-header">
        <h4><i class="fas fa-thumbs-up"></i> Recomendados</h4>
    </div>
    <div class="live-recomendacoes-list">
        <!-- Vídeos relacionados ao setor/modulo da live -->
    </div>
</div>
```

### Opção B: Abaixo da live (recomendado para mobile)

```html
<!-- Após o live-card-refined -->
<div class="live-recomendacoes-section">
    <div class="live-recomendacoes-header">
        <h3><i class="fas fa-video"></i> Vídeos Relacionados</h3>
    </div>
    <div class="live-recomendacoes-grid">
        <!-- Grid de vídeos relacionados -->
    </div>
</div>
```

**Recomendação**: Usar **Opção B** (abaixo) por ser mais responsiva e não competir com o chat.

---

## 🎨 CSS para Recomendações

```css
/* Vídeos Relacionados */
.videos-relacionados-section {
    margin: 40px 0;
    padding: 24px;
    background: #f9fafb;
    border-radius: 12px;
    border: 1px solid #e5e7eb;
}

.videos-relacionados-header h3 {
    font-size: 20px;
    font-weight: 700;
    color: #111827;
    margin: 0 0 20px 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.videos-relacionados-header i {
    color: #ef4444;
}

.videos-relacionados-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 20px;
}

.video-relacionado-card {
    background: #ffffff;
    border-radius: 12px;
    overflow: hidden;
    border: 1px solid #e5e7eb;
    transition: all 0.3s ease;
}

.video-relacionado-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
    border-color: #ef4444;
}

.video-relacionado-thumbnail {
    position: relative;
    width: 100%;
    height: 0;
    padding-bottom: 56.25%;
    background: #000;
    overflow: hidden;
}

.video-sequencia-badge {
    position: absolute;
    top: 8px;
    left: 8px;
    z-index: 2;
    background: rgba(239, 68, 68, 0.95);
    color: #ffffff;
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 700;
}

.video-play-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.video-relacionado-card:hover .video-play-overlay {
    opacity: 1;
}

.video-play-overlay i {
    color: #ffffff;
    font-size: 48px;
}

.video-relacionado-info {
    padding: 16px;
}

.video-relacionado-titulo {
    font-size: 15px;
    font-weight: 600;
    color: #111827;
    margin: 0 0 10px 0;
    line-height: 1.4;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.video-relacionado-meta {
    display: flex;
    gap: 16px;
    font-size: 13px;
    color: #6b7280;
}

.video-relacionado-meta i {
    color: #ef4444;
    margin-right: 4px;
}

/* Recomendações na Live */
.live-recomendacoes-section {
    margin-top: 30px;
    padding: 24px;
    background: #ffffff;
    border-radius: 12px;
    border: 1px solid #e5e7eb;
}

.live-recomendacoes-header h3 {
    font-size: 18px;
    font-weight: 700;
    color: #111827;
    margin: 0 0 20px 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.live-recomendacoes-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
    gap: 16px;
}

@media (max-width: 768px) {
    .videos-relacionados-grid,
    .live-recomendacoes-grid {
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 12px;
    }
}
```

---

## ✅ Checklist de Implementação

### Fase 1: Banco de Dados
- [ ] Criar tabela `sequencias`
- [ ] Adicionar campos `is_sequencia`, `sequencia_id`, `sequencia_ordem` na tabela `videos`
- [ ] Criar índices apropriados
- [ ] Testar relacionamentos

### Fase 2: Upload
- [ ] Adicionar checkbox "Faz parte de sequência" no formulário
- [ ] Adicionar campos de seleção/criação de sequência
- [ ] Criar `get_sequencias.php`
- [ ] Modificar `upload_ajax.php` para processar sequências
- [ ] Testar upload com e sem sequência

### Fase 3: Exibição
- [ ] Adicionar seção de recomendações em `video_detalhes.php`
- [ ] Buscar vídeos relacionados (sequência ou similares)
- [ ] Criar cards de vídeos relacionados
- [ ] Adicionar CSS para recomendações
- [ ] Testar exibição

### Fase 4: Live
- [ ] Decidir posição (lado ou abaixo)
- [ ] Buscar vídeos relacionados ao setor/modulo da live
- [ ] Exibir grid de recomendações
- [ ] Testar responsividade

### Fase 5: Melhorias
- [ ] Adicionar badge de "Parte X" nos cards
- [ ] Indicar progresso da sequência
- [ ] Adicionar navegação "Anterior/Próximo" na sequência
- [ ] Melhorar UX com animações

---

## 💡 Ideias Extras

1. **Barra de Progresso da Sequência**: Mostrar quantos vídeos da sequência o usuário já assistiu
2. **Navegação Rápida**: Botões "Anterior" e "Próximo" na página de detalhes
3. **Playlist Automática**: Criar playlists automáticas para sequências
4. **Estatísticas de Sequência**: Mostrar quantas pessoas completaram a sequência
5. **Badge de Sequência**: Badge especial nos cards de vídeo que fazem parte de sequência

---

## 📝 Notas Importantes

- **Compatibilidade**: Garantir que vídeos antigos continuem funcionando (is_sequencia = 0)
- **Validação**: Validar que a ordem não seja duplicada na mesma sequência
- **Performance**: Usar índices adequados para queries de sequências
- **UX**: Tornar claro para o usuário que está assistindo uma sequência

---

**Data de Criação**: Hoje  
**Data Prevista de Implementação**: Amanhã  
**Status**: 📋 Planejamento Completo

