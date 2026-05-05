# 📚 Instruções: Sistema de Módulos e Playlists

## 🎯 Funcionalidades Implementadas

### 1. **Sistema de Módulos**
Cada setor (ex: Saúde, Biblioteca) pode ter vários módulos (ex: Farmácia, Ambulatório, Consultório).

### 2. **Sistema de Playlists**
Crie playlists com vídeos em sequência (passo 1, 2, 3... até 10).

---

## 📋 Passo 1: Criar as Tabelas no Banco de Dados

Execute os seguintes scripts SQL **NA ORDEM CORRETA**:

### ⚠️ IMPORTANTE: Ordem de Execução

1. **Primeiro**: Certifique-se de que a tabela `sistemas` existe
   - Se não existir, execute: `db/criar_tabela_sistemas.sql`

2. **Segundo**: Criar tabela de módulos
   - **Opção A (Recomendada)**: `db/criar_tabela_modulos_simples.sql` (mais fácil)
   - **Opção B**: `db/criar_tabela_modulos.sql` (versão completa com verificações)

3. **Terceiro**: Adicionar coluna modulo_id na tabela videos
   - **Opção A (Recomendada)**: `db/adicionar_modulo_id_videos_simples.sql` (mais fácil)
   - **Opção B**: `db/adicionar_modulo_id_videos.sql` (versão completa com verificações)

4. **Quarto**: Criar tabelas de playlists
   - Execute: `db/criar_tabela_playlists.sql`

### 🔧 Se Der Erro:

**Erro ao criar tabela modulos:**
- Verifique se a tabela `setores` existe
- A tabela `setores` já deve existir no seu banco de dados
- Use a versão simplificada: `db/criar_tabela_modulos_simples.sql`

**Erro ao adicionar coluna modulo_id:**
- Verifique se a tabela `modulos` existe
- Execute primeiro: `db/criar_tabela_modulos_simples.sql`
- Use a versão simplificada: `db/adicionar_modulo_id_videos_simples.sql`

---

## 📋 Passo 2: Cadastrar Módulos

1. Faça login como **administrador**
2. Acesse: `cadastro_modulos.php`
3. Para cada setor, adicione seus módulos:
   - **Setor**: Selecione o setor (ex: "Saúde")
   - **Nome do Módulo**: Digite o nome (ex: "Farmácia", "Ambulatório")
   - **Ícone**: Use classes Font Awesome (ex: `fas fa-pills`)
   - **Cor**: Escolha uma cor para o módulo
   - **Descrição**: (Opcional) Descreva o módulo

### Exemplo de Módulos para o Setor "Saúde":
- **Farmácia** (`fas fa-pills`, cor: `#e74c3c`)
- **Ambulatório** (`fas fa-hospital`, cor: `#3498db`)
- **Transporte** (`fas fa-ambulance`, cor: `#2ecc71`)
- **Consultório** (`fas fa-stethoscope`, cor: `#f39c12`)

---

## 📋 Passo 3: Postar Vídeos com Módulo

1. Acesse a página principal (`index.php`)
2. Clique em **"Upload de Vídeo"**
3. Preencha os campos:
   - **Título** (obrigatório)
   - **Descrição** (obrigatório)
   - **Setor** (obrigatório) - Selecione um setor
   - **Módulo** (opcional) - Aparecerá após selecionar um setor
   - **Arquivo de Vídeo** (obrigatório)
4. Clique em **"Enviar"**

> **Nota**: O módulo só aparecerá após selecionar um setor. Os módulos são carregados dinamicamente via AJAX baseado no setor selecionado.

---

## 📋 Passo 4: Criar e Gerenciar Playlists

### 4.1. Criar uma Playlist

1. Acesse: `gerenciar_playlists.php`
2. Clique em **"Nova Playlist"**
3. Preencha:
   - **Título**: Ex: "Treinamento Completo - Passo a Passo"
   - **Descrição**: (Opcional) Descreva o conteúdo
   - **Cor**: Escolha uma cor para a playlist
4. Clique em **"Criar Playlist"**

### 4.2. Adicionar Vídeos à Playlist

1. Na lista de playlists, clique em **"Gerenciar"** na playlist desejada
2. Clique em **"Adicionar Vídeo"**
3. Selecione um vídeo da lista
4. Clique em **"Adicionar"**

> **Nota**: Os vídeos são adicionados em sequência. O primeiro vídeo adicionado será o "Passo 1", o segundo será o "Passo 2", e assim por diante.

### 4.3. Reordenar Vídeos na Playlist

1. Na página de detalhes da playlist, **arraste e solte** os vídeos para reordená-los
2. A ordem será salva automaticamente
3. Os números de ordem (1, 2, 3...) serão atualizados automaticamente

### 4.4. Remover Vídeo da Playlist

1. Na página de detalhes da playlist, clique em **"Remover"** no vídeo desejado
2. Confirme a remoção
3. Os vídeos restantes serão reordenados automaticamente

---

## 🎨 Como Funciona

### Módulos
- **Relacionamento**: Setor → Módulos → Vídeos
- Um vídeo pode estar associado a um módulo específico
- Módulos são opcionais (vídeos podem não ter módulo)
- Módulos são filtrados por setor (ao selecionar um setor, apenas seus módulos aparecem)

### Playlists
- **Relacionamento**: Usuário → Playlists → Vídeos (com ordem)
- Cada usuário pode criar suas próprias playlists
- Administradores podem ver todas as playlists
- Vídeos podem ser reordenados por drag-and-drop
- Cada vídeo na playlist tem uma ordem sequencial (1, 2, 3...)

---

## 🔧 Arquivos Criados/Modificados

### Novos Arquivos:
- `db/criar_tabela_modulos.sql` - Script para criar tabela de módulos
- `db/adicionar_modulo_id_videos.sql` - Script para adicionar coluna modulo_id
- `db/criar_tabela_playlists.sql` - Script para criar tabelas de playlists
- `cadastro_modulos.php` - Página para gerenciar módulos
- `gerenciar_playlists.php` - Página para gerenciar playlists
- `playlist_detalhes.php` - Página de detalhes de uma playlist
- `get_modulos.php` - API para buscar módulos via AJAX

### Arquivos Modificados:
- `upload_ajax.php` - Adicionado suporte para `modulo_id`
- `index.php` - Adicionado campo de módulo no formulário de upload (carregado dinamicamente baseado no setor)
- `cadastro_modulos.php` - Ajustado para usar setores ao invés de sistemas
- `get_modulos.php` - Ajustado para buscar módulos por setor

---

## 🚀 Próximos Passos (Sugestões)

1. **Exibir módulo nos cards de vídeo** - Mostrar badge do módulo nos cards
2. **Filtro por módulo** - Adicionar filtro de módulos na página principal
3. **Player de playlist** - Criar player que reproduz vídeos em sequência
4. **Compartilhar playlists** - Permitir compartilhamento de playlists
5. **Estatísticas de playlists** - Mostrar quantos vídeos foram assistidos

---

## ⚠️ Observações Importantes

1. **Módulos são opcionais**: Vídeos podem ser postados sem módulo
2. **Sistema é opcional**: Não é obrigatório selecionar sistema ao postar vídeo
3. **Playlists são pessoais**: Cada usuário vê apenas suas próprias playlists (exceto admins)
4. **Ordem sequencial**: A ordem dos vídeos na playlist é importante e pode ser alterada

---

## 📞 Suporte

Se encontrar algum problema:
1. Verifique se todas as tabelas foram criadas corretamente
2. Verifique se as foreign keys estão funcionando
3. Verifique os logs do PHP para erros
4. Certifique-se de que o usuário tem permissões adequadas

