# 🎯 Sistema de Recomendações Inteligentes

## 📋 Visão Geral

Sistema de recomendações baseado em **machine learning simples** que analisa o histórico de visualizações do usuário para sugerir vídeos personalizados.

## 🗄️ Estrutura do Banco de Dados

### Tabela: `usuario_historico`
Armazena o histórico de visualizações de cada usuário:
- `usuario_id`: ID do usuário (NULL para anônimos)
- `video_id`: ID do vídeo assistido
- `setor_id`: Setor do vídeo
- `modulo_id`: Módulo do vídeo (opcional)
- `tempo_assistido`: Tempo assistido em segundos
- `completou`: Se assistiu até o final (0 ou 1)
- `visualizado_em`: Data/hora da visualização

### Tabela: `usuario_preferencias`
Cache de preferências do usuário (opcional, para performance):
- `usuario_id`: ID do usuário
- `setores_favoritos`: JSON com setores mais acessados
- `modulos_favoritos`: JSON com módulos mais acessados
- `ultima_atualizacao`: Data da última atualização

## 🧠 Algoritmo de Recomendação

### Para Usuários Logados:

1. **Análise de Setores Favoritos**
   - Busca os 3 setores mais acessados pelo usuário
   - Prioriza vídeos desses setores (peso: 3)

2. **Análise de Módulos Favoritos**
   - Busca os 3 módulos mais acessados pelo usuário
   - Prioriza vídeos desses módulos (peso: 2)

3. **Exclusão de Vídeos Já Assistidos**
   - Remove vídeos que o usuário já visualizou

4. **Scoring Final**
   ```
   Score = (Setor Favorito × 3) + (Módulo Favorito × 2) + (Visualizações × 0.1) + (Recente × 0.5)
   ```

### Para Usuários Anônimos:

- Mostra vídeos mais populares e recentes
- Scoring baseado em:
  - Visualizações totais (peso: 0.5)
  - Curtidas (peso: 2)
  - Vídeos recentes (peso: 3)

## 📁 Arquivos Criados

1. **`db/criar_sistema_recomendacoes.sql`**
   - Script SQL para criar as tabelas necessárias

2. **`get_recomendacoes.php`**
   - Endpoint que retorna recomendações personalizadas
   - Parâmetros: `limite` (padrão: 6)

3. **`registrar_visualizacao.php`**
   - Endpoint para registrar visualizações no histórico
   - Parâmetros: `video_id`, `tempo_assistido`, `completou`

## 🎨 Interface

### Seção de Recomendações (`index.php`)

- **Localização**: Aparece antes da seção de vídeos principais
- **Visibilidade**: Só aparece quando não há filtros ativos
- **Design**: Card moderno com gradiente e badge "RECOMENDADO"
- **Carregamento**: Via AJAX ao carregar a página

### Badge de Recomendação

- Ícone: ✨ (sparkles)
- Texto: "RECOMENDADO"
- Cor: Gradiente roxo (#6366f1 → #8b5cf6)
- Animação: Pulsante suave

## 🔄 Fluxo de Funcionamento

1. **Usuário assiste um vídeo** (`video_detalhes.php`)
   - Após 5 segundos, registra visualização
   - Atualiza histórico a cada 30 segundos
   - Registra se completou o vídeo

2. **Sistema analisa histórico**
   - Identifica setores/módulos mais acessados
   - Calcula scores para cada vídeo

3. **Recomendações são exibidas**
   - Carregadas via AJAX na página principal
   - Atualizadas automaticamente conforme histórico

## 🚀 Como Usar

### 1. Executar Script SQL

```sql
-- Execute o script para criar as tabelas
SOURCE db/criar_sistema_recomendacoes.sql;
```

### 2. Testar Recomendações

1. Faça login no sistema
2. Assista alguns vídeos de diferentes setores/módulos
3. Volte para a página principal
4. A seção "Recomendado para Você" aparecerá com vídeos personalizados

### 3. Verificar Histórico

```sql
-- Ver histórico de um usuário
SELECT * FROM usuario_historico WHERE usuario_id = 1 ORDER BY visualizado_em DESC;

-- Ver setores mais acessados
SELECT setor_id, COUNT(*) as total 
FROM usuario_historico 
WHERE usuario_id = 1 
GROUP BY setor_id 
ORDER BY total DESC;
```

## 📊 Métricas do Algoritmo

- **Precisão**: Baseada em setores/módulos mais acessados
- **Diversidade**: Inclui vídeos populares e recentes
- **Personalização**: Adapta-se ao histórico de cada usuário
- **Performance**: Cache de preferências (futuro)

## 🔮 Melhorias Futuras

1. **Filtro Colaborativo**
   - "Usuários que assistiram X também assistiram Y"

2. **Análise de Conteúdo**
   - Tags, palavras-chave, duração

3. **Machine Learning**
   - Modelo de predição mais sofisticado

4. **Cache Inteligente**
   - Atualização automática de preferências

5. **Feedback do Usuário**
   - Botão "Não gostei" para refinar recomendações

## ⚠️ Observações

- Recomendações só aparecem quando **não há filtros ativos**
- Usuários anônimos recebem recomendações genéricas
- O sistema aprende conforme o usuário assiste mais vídeos
- Histórico é mantido mesmo após logout (baseado em sessão/IP)

