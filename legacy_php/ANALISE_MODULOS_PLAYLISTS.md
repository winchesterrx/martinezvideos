# 📊 Análise: Módulos e Playlists - Estrutura do Banco de Dados

## 🎯 Objetivo
Analisar o estado atual do banco de dados e recriar a estrutura de **Módulos** e **Playlists** do zero, de forma organizada e correta.

---

## 📋 Estrutura Esperada

### 1. **Tabela `modulos`**
Relaciona módulos específicos dentro de cada setor.

**Campos:**
- `id` (INT, AUTO_INCREMENT, PRIMARY KEY)
- `setor_id` (INT, NOT NULL) - **Foreign Key para `setores.id`**
- `nome` (VARCHAR(255), NOT NULL)
- `descricao` (TEXT)
- `icone` (VARCHAR(100), DEFAULT 'fas fa-cube')
- `cor` (VARCHAR(7), DEFAULT '#6366f1')
- `ativo` (CHAR(1), DEFAULT 'S')
- `created_at` (DATETIME, DEFAULT CURRENT_TIMESTAMP)
- `updated_at` (DATETIME, DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP)

**Índices:**
- PRIMARY KEY: `id`
- INDEX: `idx_setor_id` (setor_id)
- INDEX: `idx_ativo` (ativo)
- FOREIGN KEY: `fk_modulos_setor` → `setores(id) ON DELETE CASCADE`

---

### 2. **Tabela `videos` - Campo `modulo_id`**
Adicionar campo para relacionar vídeos com módulos.

**Campo a adicionar:**
- `modulo_id` (INT, NULL) - **Foreign Key para `modulos.id`**

**Índices:**
- INDEX: `idx_modulo_id` (modulo_id)
- FOREIGN KEY: `fk_videos_modulo` → `modulos(id) ON DELETE SET NULL`

---

### 3. **Tabela `playlists`**
Armazena playlists criadas pelos usuários.

**Campos:**
- `id` (INT, AUTO_INCREMENT, PRIMARY KEY)
- `titulo` (VARCHAR(255), NOT NULL)
- `descricao` (TEXT)
- `usuario_id` (INT, NOT NULL) - **Foreign Key para `usuarios.id`**
- `cor` (VARCHAR(7), DEFAULT '#6366f1')
- `ativo` (CHAR(1), DEFAULT 'S')
- `created_at` (DATETIME, DEFAULT CURRENT_TIMESTAMP)
- `updated_at` (DATETIME, DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP)

**Índices:**
- PRIMARY KEY: `id`
- INDEX: `idx_usuario_id` (usuario_id)
- INDEX: `idx_ativo` (ativo)
- FOREIGN KEY: `fk_playlists_usuario` → `usuarios(id) ON DELETE CASCADE`

---

### 4. **Tabela `playlist_videos`**
Relaciona vídeos com playlists, mantendo a ordem sequencial.

**Campos:**
- `id` (INT, AUTO_INCREMENT, PRIMARY KEY)
- `playlist_id` (INT, NOT NULL) - **Foreign Key para `playlists.id`**
- `video_id` (INT, NOT NULL) - **Foreign Key para `videos.id`**
- `ordem` (INT, NOT NULL) - Ordem sequencial do vídeo na playlist
- `created_at` (DATETIME, DEFAULT CURRENT_TIMESTAMP)

**Índices:**
- PRIMARY KEY: `id`
- UNIQUE KEY: `unique_playlist_video` (playlist_id, video_id) - Evita duplicatas
- INDEX: `idx_playlist_ordem` (playlist_id, ordem) - Para ordenação rápida
- FOREIGN KEY: `fk_playlist_videos_playlist` → `playlists(id) ON DELETE CASCADE`
- FOREIGN KEY: `fk_playlist_videos_video` → `videos(id) ON DELETE CASCADE`

---

## 🔍 Checklist de Verificação

### Antes de Recriar:
- [ ] Verificar se a tabela `setores` existe
- [ ] Verificar se a tabela `usuarios` existe
- [ ] Verificar se a tabela `videos` existe
- [ ] Verificar se há dados em `videos.modulo_id` que não existem em `modulos`
- [ ] Verificar se há dados em `playlist_videos` que referenciam playlists/vídeos inexistentes

### Estrutura Atual (a verificar):
- [ ] Tabela `modulos` existe?
- [ ] Tabela `modulos` tem `setor_id` ou `sistema_id`?
- [ ] Tabela `videos` tem campo `modulo_id`?
- [ ] Foreign keys estão criadas corretamente?
- [ ] Índices estão criados?
- [ ] Tabela `playlists` existe?
- [ ] Tabela `playlist_videos` existe?

---

## 🚨 Problemas Conhecidos

1. **Coluna `sistema_id` em vez de `setor_id`**
   - ❌ Problema: Tabela `modulos` foi criada com `sistema_id` em vez de `setor_id`
   - ✅ Solução: Recriar tabela com nome correto

2. **Foreign Key `fk_videos_modulo` duplicada**
   - ❌ Problema: Tentativa de criar foreign key que já existe ou conflito de índices
   - ✅ Solução: Remover índices/foreign keys existentes antes de recriar

3. **Índices duplicados**
   - ❌ Problema: Múltiplas tentativas de criar o mesmo índice
   - ✅ Solução: Verificar e remover índices existentes antes de criar novos

---

## 📝 Scripts de Recriação

### Ordem de Execução:
1. **Remover estruturas antigas** (se existirem)
2. **Criar tabela `modulos`** (com `setor_id`)
3. **Adicionar campo `modulo_id` em `videos`** (se não existir)
4. **Criar foreign keys**
5. **Criar tabela `playlists`**
6. **Criar tabela `playlist_videos`**

---

## 🎨 Funcionalidades Implementadas (Frontend)

### Módulos:
- ✅ Página `cadastro_modulos.php` - Gerenciar módulos
- ✅ API `get_modulos.php` - Buscar módulos por setor
- ✅ Campo de seleção de módulo no upload (`index.php`)
- ✅ Suporte a `modulo_id` no `upload_ajax.php`

### Playlists:
- ✅ Página `gerenciar_playlists.php` - Listar e criar playlists
- ✅ Página `playlist_detalhes.php` - Gerenciar vídeos da playlist
- ✅ Ordenação drag-and-drop de vídeos na playlist

---

## 🔄 Próximos Passos

1. **Aguardar banco de dados do usuário**
2. **Analisar estrutura atual**
3. **Criar script SQL completo para recriação**
4. **Testar script em ambiente de desenvolvimento**
5. **Executar em produção**

---

## 📌 Notas Importantes

- ⚠️ **Backup obrigatório** antes de executar scripts de remoção
- ⚠️ Verificar se há dados importantes em `modulos` e `playlist_videos` antes de remover
- ⚠️ A foreign key `fk_videos_modulo` usa `ON DELETE SET NULL` para não perder vídeos se o módulo for excluído
- ⚠️ A foreign key `fk_modulos_setor` usa `ON DELETE CASCADE` para remover módulos se o setor for excluído

