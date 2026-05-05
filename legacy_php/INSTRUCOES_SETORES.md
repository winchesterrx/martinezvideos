# 📋 Instruções para Configurar Setores no Registro

## ✅ O que foi feito

O sistema agora usa a tabela **`setores`** que já existe no seu banco de dados, ao invés de criar uma nova tabela de sistemas.

## 📋 Passo 1: Inserir Setores Faltantes

Execute o script SQL que está em `db/inserir_setores_faltantes.sql` no seu banco de dados MySQL.

### Opção 1: Via phpMyAdmin
1. Abra o arquivo `db/inserir_setores_faltantes.sql`
2. Copie todo o conteúdo
3. Cole no phpMyAdmin
4. Execute o script

### Opção 2: Via linha de comando
```bash
mysql -u seu_usuario -p nome_do_banco < db/inserir_setores_faltantes.sql
```

**Nota:** O script usa `INSERT IGNORE`, então não vai duplicar setores que já existem.

## 📋 Passo 2: Criar Tabela de Relacionamento

Execute o script SQL que está em `db/criar_tabela_usuario_setores.sql` para criar a tabela que relaciona usuários com setores.

```bash
mysql -u seu_usuario -p nome_do_banco < db/criar_tabela_usuario_setores.sql
```

Esta tabela permite que um usuário faça parte de **múltiplos setores**.

## 📋 Passo 3: Verificar

Execute estas queries para verificar:

```sql
-- Verificar setores cadastrados
SELECT * FROM setores WHERE ativo = 'S';

-- Verificar se a tabela de relacionamento foi criada
SHOW TABLES LIKE 'usuario_setores';

-- Ver estrutura da tabela
DESCRIBE usuario_setores;
```

## 🎯 Como Funciona Agora

### Na Página de Registro (`registro.php`)
- ✅ Busca setores da tabela `setores` (onde `ativo = 'S'`)
- ✅ Usuário pode selecionar **múltiplos setores**
- ✅ Cada setor tem um ícone apropriado
- ✅ Design moderno com cards interativos
- ✅ Cores da empresa (laranja, cinza, branco, preto)

### Estrutura de Dados

**Tabela `setores`:**
- `id` - ID do setor
- `nome` - Nome do setor
- `ativo` - Status (S/N)

**Tabela `usuario_setores`:**
- `id` - ID do relacionamento
- `usuario_id` - ID do usuário
- `setor_id` - ID do setor
- `created_at` - Data de criação

## 📝 Setores que Serão Inseridos

O script insere apenas os setores individuais (SEM as bancadas):
- Saúde
- Assistência Social
- Ensino
- Biblioteca
- Flowdocs
- Tributos
- Ouvidoria
- Protocolo
- Compras
- Licitação
- Frotas
- Almoxarifado
- Patrimônio
- Contabilidade
- Custos
- Terceiro Setor
- Controle Interno
- Gestor Municipal
- Documentos Eletrônicos
- Recursos Humanos
- Folha de Pagamento
- Administração

## 🔧 Gerenciar Setores

Para adicionar, editar ou remover setores, use a página:
- `cadastro_setores.php` (já existe no seu sistema)

## ⚠️ Importante

- ✅ A tabela `setores` já existe e está sendo usada
- ✅ Setores inativos (`ativo = 'N'`) não aparecem no registro
- ✅ Usuários podem fazer parte de múltiplos setores
- ✅ O relacionamento é salvo na tabela `usuario_setores`

## 🐛 Troubleshooting

### Erro: "Table 'usuario_setores' doesn't exist"
- Execute o script `db/criar_tabela_usuario_setores.sql`

### Setores não aparecem na página de registro
- Verifique se os setores estão com `ativo = 'S'`
- Verifique se a query está funcionando (veja console do navegador)

### Erro ao salvar setores selecionados
- Verifique se a tabela `usuario_setores` existe
- Verifique se há relacionamento correto com `usuarios` e `setores`

## ✅ Checklist

- [ ] Executou `db/inserir_setores_faltantes.sql`
- [ ] Executou `db/criar_tabela_usuario_setores.sql`
- [ ] Tabela `usuario_setores` criada
- [ ] Testou o registro de usuário com seleção de setores
- [ ] Verificou se os setores selecionados foram salvos

---

**Pronto!** Agora o sistema usa a tabela `setores` existente! 🎉

