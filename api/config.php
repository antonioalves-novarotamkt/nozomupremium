<?php
/* =========================================================
   Nozomu — configuração do sistema de reservas
   Edite APENAS este arquivo. Nada mais precisa ser mexido.
   ========================================================= */

/* 1) BANCO DE DADOS MySQL (recomendado)
   Pegue estes dados no painel da Locaweb, em "Banco de dados MySQL".
   Se você deixar DB_HOST em branco (''), o sistema funciona igual,
   guardando os dados em arquivo (api/data/store.json). Também é seguro. */
define('DB_HOST', '');          // ex.: 'mysql.nozomu.com.br'
define('DB_NAME', '');          // ex.: 'nozomu'
define('DB_USER', '');          // ex.: 'nozomu'
define('DB_PASS', '');          // sua senha do banco

/* 2) Senha do painel da equipe — a mesma que vocês digitam na tela.
   Se trocar aqui, troque também na tela de login do painel. */
define('STAFF_PIN', '8486');

/* 3) Quantos dias de backup automático manter no servidor. */
define('BACKUP_DAYS', 90);
