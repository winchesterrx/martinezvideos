# 🔧 Como Corrigir Dados com Encoding Incorreto

## Problema
Os dados já salvos no banco estão com encoding incorreto (ex: "MÃ©dico" em vez de "Médico", "apresentaÃ§Ã£o" em vez de "apresentação").

## Solução

### Opção 1: Script PHP (Recomendado - Mais Fácil)

1. **Acesse o arquivo no navegador:**
   ```
   http://seudominio.com/videoaulas/corrigir_dados_php.php
   ```

2. **O script irá:**
   - Detectar automaticamente dados com encoding incorreto
   - Corrigir cada registro
   - Mostrar um relatório do que foi corrigido

3. **Resultado:**
   - Você verá uma página com os dados corrigidos
   - Exemplo: "MÃ©dico" → "Médico"

### Opção 2: Script SQL

Execute o arquivo `db/corrigir_dados_existentes.sql` no phpMyAdmin ou MySQL Workbench.

**⚠️ ATENÇÃO:** Faça backup do banco antes de executar!

### Opção 3: Correção Manual (Para casos específicos)

Se quiser corrigir apenas um vídeo específico:

```sql
-- Exemplo: Corrigir título de um vídeo específico
UPDATE videos 
SET titulo = CONVERT(CAST(CONVERT(titulo USING latin1) AS BINARY) USING utf8mb4)
WHERE id = 1;
```

## Verificação

Após executar a correção, verifique se ainda há dados com encoding incorreto:

```sql
SELECT * FROM videos WHERE titulo LIKE '%Ã%' OR descricao LIKE '%Ã%';
SELECT * FROM comentarios WHERE conteudo LIKE '%Ã%';
SELECT * FROM setores WHERE nome LIKE '%Ã%';
```

Se retornar 0 resultados, todos os dados foram corrigidos! ✅

## Próximos Passos

1. ✅ Execute o script de correção
2. ✅ Verifique se os dados foram corrigidos
3. ✅ Teste criando um novo vídeo com acentos
4. ✅ Teste adicionando um comentário com emojis

## Notas Importantes

- **Backup:** Sempre faça backup antes de executar scripts de correção
- **Teste:** Teste primeiro em um ambiente de desenvolvimento se possível
- **Novos dados:** Após a correção, novos dados serão salvos corretamente automaticamente

