# 📌 Como Adicionar Vídeos aos Recomendados

## Passo 1: Executar o Script SQL

Primeiro, você precisa adicionar o campo `recomendado` na tabela `videos`. Execute o script SQL:

```sql
-- Execute este comando no seu banco de dados
ALTER TABLE videos 
ADD COLUMN IF NOT EXISTS recomendado TINYINT(1) DEFAULT 0 COMMENT '1 = vídeo recomendado manualmente' 
AFTER visualizacoes;

-- Adiciona índice para melhorar performance
CREATE INDEX IF NOT EXISTS idx_recomendado ON videos(recomendado);
```

**OU** execute o arquivo SQL diretamente:
- Abra o arquivo `db/adicionar_campo_recomendado.sql` no seu cliente MySQL (phpMyAdmin, MySQL Workbench, etc.)
- Execute o script

## Passo 2: Como Usar

### Para Administradores:

1. **Visualizar o botão**: 
   - Faça login como administrador
   - Na página principal, você verá um botão de estrela (⭐) em cada card de vídeo

2. **Adicionar aos Recomendados**:
   - Clique no botão de estrela (⭐) no card do vídeo
   - A estrela ficará dourada (⭐) indicando que o vídeo está recomendado
   - O vídeo aparecerá automaticamente na seção "Recomendado para Você"

3. **Remover dos Recomendados**:
   - Clique novamente no botão de estrela dourada
   - A estrela voltará ao estado normal
   - O vídeo será removido dos recomendados

## Como Funciona

- **Prioridade**: Vídeos marcados manualmente como recomendados têm **prioridade máxima** na seção de recomendações
- **Visibilidade**: Apenas administradores podem ver e usar o botão de recomendar
- **Atualização**: As recomendações são atualizadas automaticamente após marcar/desmarcar um vídeo

## Exemplo de Uso via SQL

Se preferir marcar vídeos diretamente no banco de dados:

```sql
-- Marcar vídeo como recomendado
UPDATE videos SET recomendado = 1 WHERE id = 1;

-- Remover recomendação
UPDATE videos SET recomendado = 0 WHERE id = 1;

-- Ver todos os vídeos recomendados
SELECT * FROM videos WHERE recomendado = 1;
```

## Arquivos Criados/Modificados

1. ✅ `db/adicionar_campo_recomendado.sql` - Script SQL para adicionar o campo
2. ✅ `toggle_recomendado.php` - Endpoint para marcar/desmarcar vídeos
3. ✅ `get_recomendacoes.php` - Modificado para priorizar vídeos recomendados
4. ✅ `index.php` - Adicionado botão nos cards de vídeo

## Notas Importantes

- ⚠️ O campo `recomendado` é criado automaticamente na primeira vez que você usar o botão (se não existir)
- ✅ Vídeos recomendados manualmente sempre aparecem primeiro na seção de recomendações
- 🔒 Apenas administradores podem marcar vídeos como recomendados

