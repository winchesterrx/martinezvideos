# Correção de Encoding - Caracteres Especiais e Emojis

## Problema Identificado
O sistema estava exibindo caracteres especiais incorretamente (ex: "Médico" aparecia como "MÃ©dico", "apresentação" como "apresentaÃ§Ã£o"). Isso ocorria devido à falta de configuração adequada de UTF-8/UTF8MB4 em toda a aplicação.

## Correções Implementadas

### 1. Arquivo de Conexão (`db/conexao.php`)
- ✅ Configurado `utf8mb4` na conexão MySQL
- ✅ Adicionados comandos `SET NAMES` e `SET CHARACTER SET` para garantir encoding correto

### 2. Arquivos PHP Principais
Todos os arquivos foram atualizados para usar `utf8mb4`:
- ✅ `index.php`
- ✅ `get_videos.php`
- ✅ `get_modulos.php`
- ✅ `upload_ajax.php`
- ✅ `video_detalhes.php`
- ✅ `cadastro_modulos.php`
- ✅ `cadastro_setores.php`
- ✅ `listar_usuarios.php`
- ✅ `listar_clientes.php`
- ✅ `add_comentario.php`
- ✅ `add_resposta.php`
- ✅ `edit_video.php`

### 3. Headers HTTP
- ✅ Todos os arquivos que retornam JSON agora incluem `charset=utf-8` no header
- ✅ Todos os `json_encode()` agora usam a flag `JSON_UNESCAPED_UNICODE`

### 4. HTML
- ✅ Meta tag `<meta charset="UTF-8">` já estava presente em todos os arquivos

## ⚠️ AÇÃO NECESSÁRIA: Corrigir o Banco de Dados

Para que os caracteres especiais funcionem completamente, você precisa executar o script SQL que corrige o charset das tabelas do banco de dados.

### ⚠️ ERRO COMUM: "Chave especificada longa demais (767 bytes)"

Se você encontrar o erro `#1071 - Chave especificada longa demais. O comprimento de chave máximo permitido é 767`, isso ocorre porque a tabela `usuarios` (e possivelmente `clientes`) tem um índice UNIQUE na coluna `email` que excede o limite quando convertido para `utf8mb4`.

**Solução Rápida:**
Execute o arquivo `db/corrigir_charset_email_simples.sql` primeiro para corrigir apenas o problema do email, depois continue com o script completo.

### Opção 1: Script Completo Corrigido (Recomendado)
Execute o arquivo `db/corrigir_charset_completo_fix.sql` no seu banco de dados MySQL.

Este script irá:
1. Alterar o charset do banco de dados para `utf8mb4`
2. Converter todas as tabelas para `utf8mb4`
3. **Remover e recriar índices UNIQUE** com prefixo adequado (191 caracteres)
4. Corrigir todas as colunas de texto para `utf8mb4`

### Opção 2: Script Simples (Se o completo der erro)
Execute `db/corrigir_charset_email_simples.sql` para corrigir apenas o problema do email, depois execute o restante do script completo manualmente.

### Opção 3: Verificação Manual
Se preferir verificar antes de alterar, execute `db/corrigir_charset_banco.sql` para ver o estado atual do banco.

### Como Executar:
1. Acesse seu painel MySQL (phpMyAdmin, MySQL Workbench, etc.)
2. Selecione o banco de dados `martinezvideo`
3. Execute o script `db/corrigir_charset_completo.sql`
4. Aguarde a conclusão (pode levar alguns minutos dependendo do tamanho do banco)

## ⚠️ IMPORTANTE: Dados Existentes

**ATENÇÃO**: Se você já tem dados no banco com encoding incorreto, eles podem precisar ser corrigidos manualmente ou re-inseridos após a correção do charset.

Para verificar se há dados com encoding incorreto:
```sql
SELECT * FROM videos WHERE titulo LIKE '%Ã%' OR descricao LIKE '%Ã%';
SELECT * FROM comentarios WHERE conteudo LIKE '%Ã%';
SELECT * FROM setores WHERE nome LIKE '%Ã%';
```

## Teste Após Correção

Após executar o script SQL, teste:
1. ✅ Criar um novo vídeo com título contendo acentos (ex: "Apresentação do Consultório Médico")
2. ✅ Adicionar um comentário com emojis (ex: "Ótimo vídeo! 👍😊")
3. ✅ Verificar se os dados antigos estão sendo exibidos corretamente

## Suporte a Emojis

Com `utf8mb4`, o sistema agora suporta:
- ✅ Todos os caracteres especiais (á, é, í, ó, ú, ç, etc.)
- ✅ Emojis (👍, 😊, ❤️, etc.)
- ✅ Caracteres de outros idiomas (中文, العربية, etc.)

## Arquivos Criados

- `db/corrigir_charset_banco.sql` - Script para verificar charset atual
- `db/corrigir_charset_completo.sql` - Script completo (versão original - pode dar erro no email)
- `db/corrigir_charset_completo_fix.sql` - **Script completo CORRIGIDO (recomendado)**
- `db/corrigir_charset_email_simples.sql` - Script simples para corrigir apenas o problema do email

## Status

✅ **Código PHP corrigido** - Todos os arquivos estão configurados corretamente
⏳ **Aguardando execução do SQL** - Execute o script SQL para finalizar a correção

