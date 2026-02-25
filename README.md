# GestAll - Gestao de TI (MVP escalavel)

Sistema leve em PHP + SQLite, com estrutura por areas para expansao futura.

## O que ja tem
- Login com usuario/senha (sessao PHP)
- Area "Gerenciamento da TI"
- Controle de ativos e contratos
- Cadastro de colaboradores
- Cadastro de categorias de equipamento
- Cadastro de tipos de contrato
- Cadastro de status do ativo
- Campo de observacao e caminho de documento assinado
- Busca por TAG, serial, categoria, status, contrato e responsavel

## Rodar com Docker
1. Na raiz do projeto: `docker compose up --build`
2. Acesse: `http://localhost:8080/index.php`
3. Login inicial:
   - Usuario: `admin`
   - Senha: `admin123`

## Rodar com XAMPP (alternativa)
1. Copie para `htdocs/gestall`
2. Acesse: `http://localhost/gestall/index.php`
3. Habilite `pdo_sqlite` e `sqlite3` no `php.ini`

## Banco
O SQLite e criado automaticamente em `storage/database.sqlite` no primeiro acesso.

## Arquitetura para expandir
- `app/Controllers`: entrada por modulo/area
- `app/Repositories`: acesso a dados
- `app/Services`: migracoes e servicos
- `app/Views`: telas por modulo (`areas`, `ti`)

Essa estrutura facilita incluir novas areas como `gerenciamento comercial` sem quebrar o modulo de TI.
