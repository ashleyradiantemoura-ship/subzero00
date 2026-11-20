# SUBZERO — Copa Interclasse de Hóquei no Gelo

## Projeto unificado

Esta versão reúne as páginas originais e as páginas adicionais:
- Início
- Classificação
- Confrontos e agenda
- Gerenciamento de times
- Perfil de cada time
- Gerenciamento de jogadores
- Súmulas
- Artilharia
- Favoritos
- Login e logout

## Instalação no XAMPP

1. Extraia a pasta `SUBZERO-COPA-UNIFICADA` para `C:\xampp\htdocs\`.
2. Inicie Apache e MySQL no XAMPP.
3. Acesse `http://localhost/SUBZERO-COPA-UNIFICADA/`.
4. Configure os dados de acesso ao MySQL em `config/db.php`, se necessário.

## Banco de dados

O banco utilizado é `subzero`.

**Se você já tem dados cadastrados, NÃO importe `banco.sql`.** Esse arquivo é destinado a uma instalação nova e contém `DROP DATABASE IF EXISTS subzero`, que apaga e recria o banco.

A aplicação executa `garantir_schema_expandido($pdo)` nas páginas que precisam das novas funcionalidades. Essa rotina cria as tabelas adicionais (`jogadores`, `sumulas`, `favoritos`) e adiciona as colunas de agenda/foto sem apagar os times e confrontos existentes.

Para uma instalação totalmente nova, você pode importar `banco.sql` pelo phpMyAdmin.

## Contas de demonstração (somente se usar o banco.sql)

- Administrador: `admin@subzero.com` / `admin123`
- Visualizador: `viewer@subzero.com` / `viewer123`

## CSS

O arquivo `assets/css/sz.css` preserva o CSS da versão anterior. Foram acrescentadas ao final somente as regras necessárias para as novas páginas (elenco, súmulas, perfil do time, favoritos e atalhos).
