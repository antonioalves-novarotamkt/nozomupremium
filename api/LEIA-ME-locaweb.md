# Nozomu — instalação na Locaweb

## Plano recomendado
**Hospedagem de Sites** da Locaweb (qualquer plano pago, o mais simples já serve).
O que precisa ter: **PHP** e **MySQL**. Os dois vêm nesses planos.

Se o plano tiver PHP mas você não quiser mexer com banco de dados, o sistema
funciona igual guardando os dados em arquivo — basta deixar `DB_HOST` em branco.

## Como subir

1. No painel da Locaweb, abra o **Gerenciador de Arquivos** (ou use FTP).
2. Entre na pasta pública do site (`public_html` ou `www`).
3. Envie **todos** os arquivos do site, mantendo as pastas:

```
index.html
menu.html
ambiente.html
reservas.html
robots.txt
sitemap.xml
assets/...
api/
  api.php
  config.php
```

4. Dê permissão de escrita **777** (ou 775) na pasta `api/`.
   O sistema cria sozinho as pastas `api/data` e `api/backups`.

## Banco de dados (recomendado)

1. No painel da Locaweb: **Banco de dados → MySQL → Criar**.
2. Anote host, nome do banco, usuário e senha.
3. Abra `api/config.php` e preencha:

```php
define('DB_HOST', 'mysql.seudominio.com.br');
define('DB_NAME', 'nozomu');
define('DB_USER', 'nozomu');
define('DB_PASS', 'sua-senha');
```

As tabelas são criadas automaticamente no primeiro acesso.

## Testar

Abra no navegador: `https://www.nozomu.com.br/api/api.php?a=ping`

Deve aparecer algo como `{"ok":true,"modo":"mysql","php":"8.2"}`.
Se aparecer `"modo":"arquivo"`, está funcionando em modo arquivo (também ok).

## O que muda no dia a dia

- As reservas e vouchers ficam **no servidor**, não no navegador.
- Gerente, recepção e caixa veem os mesmos dados, em qualquer aparelho.
- O painel atualiza sozinho a cada 10 segundos.
- **Atualizar o site não apaga nada** — só não sobrescreva `api/config.php`,
  `api/data/` e `api/backups/` ao enviar arquivos novos.

## Backup

Automático, no próprio servidor:

- `api/backups/nz-latest.json` — cópia a cada alteração.
- `api/backups/nz-AAAA-MM-DD.json` — uma cópia por dia, mantidas 90 dias.

No painel da equipe: **🗄 Backups do servidor** lista tudo, com botão para
baixar ou restaurar. As pastas de backup são bloqueadas para acesso externo.

## Senha do painel

`8486`. Para trocar, altere em dois lugares: `api/config.php` (`STAFF_PIN`) e a
tela de login do painel no site.
