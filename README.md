# FinanceControl

## Admin geral

Em uma base existente, execute `config/migrar_admin_geral.sql` depois da migração de empresas. Para criar a conta global, abra o terminal na pasta `FinanceControl` e execute:

```powershell
php config/criar_admin_geral.php "Nome do desenvolvedor" dev@exemplo.com
```

O comando cria uma conta `superadmin` sem vínculo a uma empresa e gera uma senha aleatória, exibida uma única vez no terminal. Guarde-a em local seguro. Depois do login, selecione uma empresa em **Todas as empresas** para abrir seu painel. O admin geral pode trocar de empresa pelo menu; admins de empresa não podem acessar essa lista nem elevar contas a admin geral.

Em uma instalação nova, use `config/banco.sql`; não é necessário executar a migração de admin geral separadamente.

