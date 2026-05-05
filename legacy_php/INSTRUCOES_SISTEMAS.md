# 🚀 Instruções para Configurar o Sistema de Seleção de Sistemas

## 📋 Passo 1: Criar as Tabelas no Banco de Dados

Execute o script SQL que está em `db/criar_tabela_sistemas.sql` no seu banco de dados MySQL.

### Opção 1: Via phpMyAdmin ou MySQL Workbench
1. Abra o arquivo `db/criar_tabela_sistemas.sql`
2. Copie todo o conteúdo
3. Cole no phpMyAdmin ou MySQL Workbench
4. Execute o script

### Opção 2: Via linha de comando
```bash
mysql -u seu_usuario -p nome_do_banco < db/criar_tabela_sistemas.sql
```

## 📋 Passo 2: Verificar se as Tabelas Foram Criadas

Execute esta query para verificar:
```sql
SHOW TABLES LIKE 'sistemas';
SHOW TABLES LIKE 'usuario_sistemas';
```

## 📋 Passo 3: Acessar o Gerenciamento de Sistemas

1. Faça login como administrador
2. Acesse: `cadastro_sistemas.php`
3. Você poderá:
   - ✅ Adicionar novos sistemas
   - ✅ Editar sistemas existentes (ativar/inativar)
   - ✅ Excluir sistemas
   - ✅ Definir ícones e cores personalizadas

## 🎨 Como Funciona

### Na Página de Registro (`registro.php`)
- Os usuários veem cards interativos com os sistemas disponíveis
- Podem selecionar múltiplos sistemas
- Cada sistema tem seu próprio ícone e cor
- Design moderno e responsivo

### No Gerenciamento (`cadastro_sistemas.php`)
- Apenas administradores podem acessar
- Interface simples para CRUD completo
- Validação de permissões

## 🎯 Sistemas Padrão Incluídos

O script SQL já inclui 21 sistemas baseados no organograma:
- Bancada 1 - Saúde
- Bancada 2 - Tributos
- Bancada 3 - Compras/Licitação
- Bancada 4 - Contabilidade
- Bancada 5 - Recursos Humanos
- Administração
- Flowdocs
- E mais 14 sistemas...

## 🔧 Personalização

### Adicionar Ícones
Use classes do Font Awesome:
- `fas fa-heartbeat` (Saúde)
- `fas fa-calculator` (Contabilidade)
- `fas fa-shopping-cart` (Compras)
- Veja mais em: https://fontawesome.com/icons

### Definir Cores
Use códigos hexadecimais:
- `#e74c3c` (Vermelho)
- `#3498db` (Azul)
- `#2ecc71` (Verde)
- `#f39c12` (Laranja)

## ⚠️ Importante

- A tabela `sistemas` deve existir antes de usar a página de registro
- Se não existir, a página mostrará uma mensagem informativa
- Os sistemas inativos não aparecem na página de registro
- Apenas administradores podem gerenciar sistemas

## 🐛 Troubleshooting

### Erro: "Table 'sistemas' doesn't exist"
- Execute o script SQL `db/criar_tabela_sistemas.sql`

### Sistemas não aparecem na página de registro
- Verifique se os sistemas estão com `ativo = 'S'`
- Verifique se a query está funcionando (veja console do navegador)

### Erro ao salvar sistemas selecionados
- Verifique se a tabela `usuario_sistemas` existe
- Verifique se há relacionamento correto com `usuarios` e `sistemas`

## ✅ Checklist

- [ ] Executou o script SQL
- [ ] Tabelas `sistemas` e `usuario_sistemas` criadas
- [ ] Acessou `cadastro_sistemas.php` como admin
- [ ] Testou adicionar um novo sistema
- [ ] Testou o registro de usuário com seleção de sistemas
- [ ] Verificou se os sistemas selecionados foram salvos

---

**Pronto!** Agora você tem um sistema completo de seleção de sistemas com design moderno! 🎉

